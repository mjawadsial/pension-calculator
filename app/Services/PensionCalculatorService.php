<?php

namespace App\Services;

use Carbon\Carbon;

class PensionCalculatorService
{
    private const NET_PENSION_INCREASES = [
        ['year' => 2011, 'rate' => 0.15],
        ['year' => 2015, 'rate' => 0.075],
        ['year' => 2022, 'rate' => 0.15],
        ['year' => 2023, 'rate' => 0.175],
        ['year' => 2024, 'rate' => 0.15],
        ['year' => 2025, 'rate' => 0.07],
    ];

    private const MEDICAL_ALLOWANCE_FOLLOW_UP_RATE = 0.25;

    private const COMMUTATION_FACTORS = [
        40 => 304.4736,
        41 => 295.6872,
        42 => 286.9512,
        43 => 278.2080,
        44 => 269.6556,
        45 => 261.1104,
        46 => 252.6456,
        47 => 244.2660,
        48 => 235.9836,
        49 => 227.8092,
        50 => 219.7548,
        51 => 211.8312,
        52 => 204.0600,
        53 => 196.4520,
        54 => 189.0204,
        55 => 181.7736,
        56 => 174.7224,
        57 => 167.8656,
        58 => 161.2080,
        59 => 154.7436,
        60 => 148.4628,
    ];

    private const MIN_PENSION_FEDERAL = 12000;
    private const MIN_PENSION_FAMILY = 9000;
    private const EARLY_RETIREMENT_PENALTY_AGE = 60;
    private const EARLY_RETIREMENT_PENALTY_RATE_PER_YEAR = 0.03;

    public function calculate(array $data): array
    {
        $retirementDate = Carbon::parse($data['date_of_retirement']);
        $joiningDate = Carbon::parse($data['date_of_joining']);
        $dob = Carbon::parse($data['dob']);

        $service = $this->qualifyingService($joiningDate, $retirementDate);
        $averagePay = $this->averagePay($data);
        $calculatedGrossPension = $this->grossPension($averagePay, $service['rounded_years']);
        $retirementAge = $this->retirementAge($dob, $retirementDate);
        $earlyRetirementPenalty = $this->earlyRetirementPenalty($calculatedGrossPension, $retirementAge);
        $grossPension = $earlyRetirementPenalty['gross_pension'];
        $commutation = $this->commutation(
            $grossPension,
            (float) $data['commutation'],
            $dob,
            $retirementDate
        );

        $netPension = $commutation['net_pension'];
        $bps = (int) $data['bps'];
        $pensionIncreases = $this->applyPercentageIncreases($netPension, self::NET_PENSION_INCREASES, 'pension');
        $medicalIncreases = $this->medicalAllowanceIncreases($netPension, $bps);

        $finalPension = $this->enforceMinimum(
            $pensionIncreases['final_amount'],
            $data['pension_type'] === 'death_during_service'
        );
        $finalMedicalAllowance = $medicalIncreases['final_amount'];
        $netPensionPayable = round($finalPension + $finalMedicalAllowance, 2);

        return [
            'service' => $service,
            'avg_pay' => $averagePay,
            'retirement_age' => $retirementAge,
            'early_retirement_penalty' => $earlyRetirementPenalty,
            'gross_pension' => $grossPension,
            'commutation' => $commutation,
            'increases' => [
                'breakdown' => array_merge($pensionIncreases['breakdown'], $medicalIncreases['breakdown']),
                'pension' => $pensionIncreases,
                'medical' => $medicalIncreases,
            ],
            'final_pension' => $finalPension,
            'final_medical_allowance' => $finalMedicalAllowance,
            'net_pension_payable' => $netPensionPayable,
            'min_applied' => $finalPension > $pensionIncreases['final_amount'],
            'service_warning' => $service['rounded_years'] < 10,
        ];
    }

    private function qualifyingService(Carbon $joining, Carbon $retirement): array
    {
        $years = $joining->diffInYears($retirement);
        $months = $joining->copy()->addYears($years)->diffInMonths($retirement);

        $roundedYears = $months >= 7 ? $years + 1 : $years;
        $roundedYears = min($roundedYears, 30);

        return [
            'years' => $years,
            'months' => $months,
            'rounded_years' => $roundedYears,
            'capped' => $roundedYears === 30 && ($years > 30 || ($years === 30 && $months > 0)),
        ];
    }

    private function averagePay(array $data): float
    {
        $total = (float) $data['basic_pay'];
        $total += (float) ($data['special_pay'] ?? 0);
        $total += (float) ($data['personal_pay'] ?? 0);
        $total += (float) ($data['qualification_pay'] ?? 0);

        $incrementPct = (float) ($data['retiring_increment'] ?? 0);
        if ($incrementPct > 0) {
            $total += $total * ($incrementPct / 100);
        }

        return round($total, 2);
    }

    private function grossPension(float $avgPay, int $years): float
    {
        return round(($avgPay * $years * 7) / 300, 2);
    }

    private function retirementAge(Carbon $dob, Carbon $retirementDate): int
    {
        return (int) $dob->diffInYears($retirementDate);
    }

    private function earlyRetirementPenalty(float $grossPension, int $retirementAge): array
    {
        $grossBefore = round($grossPension, 2);

        if ($retirementAge >= self::EARLY_RETIREMENT_PENALTY_AGE) {
            return [
                'applies' => false,
                'retirement_age' => $retirementAge,
                'penalty_percentage' => 0.0,
                'gross_pension_before' => $grossBefore,
                'gross_pension' => $grossBefore,
            ];
        }

        $yearsUnderSixty = self::EARLY_RETIREMENT_PENALTY_AGE - $retirementAge;
        $penaltyRate = $yearsUnderSixty * self::EARLY_RETIREMENT_PENALTY_RATE_PER_YEAR;
        $grossAfter = round($grossBefore * (1 - $penaltyRate), 2);

        return [
            'applies' => true,
            'retirement_age' => $retirementAge,
            'penalty_percentage' => round($penaltyRate * 100, 2),
            'gross_pension_before' => $grossBefore,
            'gross_pension' => $grossAfter,
        ];
    }

    private function ageNextBirthday(Carbon $dob, Carbon $retirementDate): int
    {
        return (int) $dob->diffInYears($retirementDate) + 1;
    }

    private function commutationFactor(int $ageNextBirthday): float
    {
        $clampedAge = min(max($ageNextBirthday, 40), 60);

        return self::COMMUTATION_FACTORS[$clampedAge];
    }

    private function commutation(float $grossPension, float $pct, Carbon $dob, Carbon $retirementDate): array
    {
        if ($pct <= 0) {
            return [
                'pct' => 0,
                'age_next_birthday' => $this->ageNextBirthday($dob, $retirementDate),
                'commutation_factor' => 0,
                'commuted_monthly' => 0,
                'lump_sum' => 0,
                'net_pension' => round($grossPension, 2),
            ];
        }

        $ageNextBirthday = $this->ageNextBirthday($dob, $retirementDate);
        $commutationFactor = $this->commutationFactor($ageNextBirthday);
        $commutedMonthly = round($grossPension * ($pct / 100), 2);
        $lumpSum = round($commutedMonthly * $commutationFactor, 2);
        $netPension = round($grossPension - $commutedMonthly, 2);

        return [
            'pct' => $pct,
            'age_next_birthday' => $ageNextBirthday,
            'commutation_factor' => $commutationFactor,
            'commuted_monthly' => $commutedMonthly,
            'lump_sum' => $lumpSum,
            'net_pension' => $netPension,
        ];
    }

    private function medicalAllowanceRate(int $bps): float
    {
        return $bps <= 16 ? 0.25 : 0.20;
    }

    private function medicalAllowanceIncreases(float $netPension, int $bps): array
    {
        $rate2010 = $this->medicalAllowanceRate($bps);
        $allowance2010 = round($netPension * $rate2010, 2);
        $followUpIncrease = round($allowance2010 * self::MEDICAL_ALLOWANCE_FOLLOW_UP_RATE, 2);
        $finalAmount = round($allowance2010 + $followUpIncrease, 2);
        $rateDisplay2010 = rtrim(rtrim(number_format($rate2010 * 100, 2, '.', ''), '0'), '.');
        $followUpDisplay = rtrim(rtrim(number_format(self::MEDICAL_ALLOWANCE_FOLLOW_UP_RATE * 100, 2, '.', ''), '0'), '.');

        $breakdown = [
            [
                'type' => 'medical',
                'year' => 2010,
                'rate' => $rate2010 * 100,
                'label' => "{$rateDisplay2010}% Medical Allowance of 2010",
                'increase' => $allowance2010,
                'amount_after' => $allowance2010,
            ],
            [
                'type' => 'medical',
                'year' => null,
                'rate' => self::MEDICAL_ALLOWANCE_FOLLOW_UP_RATE * 100,
                'label' => "{$followUpDisplay}% increase on Medical Allowance",
                'increase' => $followUpIncrease,
                'amount_after' => $finalAmount,
            ],
        ];

        return [
            'breakdown' => $breakdown,
            'total_increase' => round($finalAmount, 2),
            'final_amount' => $finalAmount,
        ];
    }

    /**
     * @param  array<int, array{year: int, rate: float}>  $steps
     */
    private function applyPercentageIncreases(float $baseAmount, array $steps, string $type): array
    {
        $amount = $baseAmount;
        $breakdown = [];

        foreach ($steps as $step) {
            $increase = round($amount * $step['rate'], 2);
            $amount = round($amount + $increase, 2);
            $breakdown[] = [
                'type' => $type,
                'year' => $step['year'],
                'rate' => $step['rate'] * 100,
                'label' => $this->increaseLabel($type, $step),
                'increase' => $increase,
                'amount_after' => $amount,
            ];
        }

        return [
            'breakdown' => $breakdown,
            'total_increase' => round($amount - $baseAmount, 2),
            'final_amount' => round($amount, 2),
        ];
    }

    private function increaseLabel(string $type, array $step): string
    {
        $rateDisplay = rtrim(rtrim(number_format($step['rate'] * 100, 2, '.', ''), '0'), '.');

        return "{$rateDisplay}% increase of {$step['year']}";
    }

    private function enforceMinimum(float $pension, bool $isFamily): float
    {
        $minimum = $isFamily ? self::MIN_PENSION_FAMILY : self::MIN_PENSION_FEDERAL;

        return max($pension, $minimum);
    }
}
