<?php

namespace Tests\Unit;

use App\Services\PensionCalculatorService;
use PHPUnit\Framework\TestCase;

class PensionCalculatorServiceTest extends TestCase
{
    public function test_service_years_are_capped_at_thirty(): void
    {
        $service = new PensionCalculatorService();
        $result = $service->calculate($this->payload([
            'date_of_joining' => '1980-01-01',
            'date_of_retirement' => '2024-10-10',
        ]));

        $this->assertSame(30, $result['service']['rounded_years']);
        $this->assertTrue($result['service']['capped']);
    }

    public function test_early_retirement_penalty_reduces_gross_pension_before_sixty(): void
    {
        $service = new PensionCalculatorService();
        $result = $service->calculate($this->payload([
            'dob' => '1972-11-12',
            'date_of_retirement' => '2026-12-31',
            'commutation' => 0,
        ]));

        $before = $result['early_retirement_penalty']['gross_pension_before'];
        $expectedPenaltyPct = (60 - $result['retirement_age']) * 3;
        $expectedGross = round($before * (1 - ($expectedPenaltyPct / 100)), 2);

        $this->assertTrue($result['early_retirement_penalty']['applies']);
        $this->assertEquals($expectedPenaltyPct, $result['early_retirement_penalty']['penalty_percentage']);
        $this->assertEquals($expectedGross, $result['gross_pension']);
        $this->assertEquals($expectedGross, $result['commutation']['net_pension']);
    }

    public function test_no_early_retirement_penalty_at_sixty_or_above(): void
    {
        $service = new PensionCalculatorService();
        $result = $service->calculate($this->payload([
            'dob' => '1964-01-01',
            'date_of_retirement' => '2024-01-01',
            'commutation' => 0,
        ]));

        $this->assertFalse($result['early_retirement_penalty']['applies']);
        $this->assertGreaterThanOrEqual(60, $result['retirement_age']);
    }

    public function test_commutation_lump_sum_uses_age_next_birthday_factor(): void
    {
        $service = new PensionCalculatorService();
        $result = $service->calculate($this->payload([
            'dob' => '1972-11-12',
            'date_of_retirement' => '2026-12-31',
            'commutation' => 35,
        ]));

        $commuted = $result['commutation']['commuted_monthly'];
        $factor = $result['commutation']['commutation_factor'];

        $this->assertSame(55, $result['commutation']['age_next_birthday']);
        $this->assertEquals(181.7736, $factor);
        $this->assertEquals(round($commuted * $factor, 2), $result['commutation']['lump_sum']);
    }

    public function test_zero_commutation_keeps_net_equal_to_gross(): void
    {
        $service = new PensionCalculatorService();
        $result = $service->calculate($this->payload(['commutation' => 0]));

        $this->assertEquals(0.0, $result['commutation']['lump_sum']);
        $this->assertSame($result['gross_pension'], $result['commutation']['net_pension']);
    }

    public function test_medical_allowance_2010_is_percentage_of_net_pension_not_increased_pension(): void
    {
        $service = new PensionCalculatorService();
        $result = $service->calculate($this->payload([
            'bps' => 16,
            'commutation' => 35,
        ]));

        $netPension = $result['commutation']['net_pension'];
        $expected2010 = round($netPension * 0.25, 2);

        $medical2010 = collect($result['increases']['medical']['breakdown'])
            ->firstWhere('year', 2010);

        $this->assertNotNull($medical2010);
        $this->assertEquals($expected2010, $medical2010['increase']);
    }

    public function test_death_during_service_pension_minimum_is_enforced(): void
    {
        $service = new PensionCalculatorService();
        $result = $service->calculate($this->payload([
            'pension_type' => 'death_during_service',
            'basic_pay' => 1000,
            'commutation' => 0,
        ]));

        $this->assertSame(9000.0, $result['final_pension']);
        $this->assertTrue($result['min_applied']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test User',
            'designation' => 'Clerk',
            'bps' => 12,
            'pension_type' => 'superannuation',
            'dob' => '1964-01-01',
            'date_of_joining' => '1990-01-01',
            'date_of_retirement' => '2024-01-01',
            'basic_pay' => 60000,
            'special_pay' => 0,
            'personal_pay' => 0,
            'qualification_pay' => 0,
            'retiring_increment' => 0,
            'commutation' => 35,
        ], $overrides);
    }
}
