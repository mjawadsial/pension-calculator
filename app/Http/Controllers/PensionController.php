<?php

namespace App\Http\Controllers;

use App\Services\PensionCalculatorService;
use App\Services\ThemeCatalogService;
use App\Support\DateInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PensionController extends Controller
{
    public function index(Request $request, ThemeCatalogService $themes)
    {
        $this->applyLocale($request);

        return view('pension.index', [
            'themes' => $themes->all(),
        ]);
    }

    public function calculate(
        Request $request,
        PensionCalculatorService $service,
        ThemeCatalogService $themes
    ) {
        $this->applyLocale($request);

        Log::info('[Pension] Calculate request received', [
            'payload' => $request->except(['_token']),
        ]);

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'designation' => 'nullable|string|max:100',
                'bps' => 'required|integer|between:1,22',
                'pension_type' => 'required|in:superannuation,retiring,death_during_service,voluntary',
                'dob' => 'required|date_format:d/m/Y',
                'date_of_joining' => 'required|date_format:d/m/Y',
                'date_of_retirement' => 'required|date_format:d/m/Y',
                'basic_pay' => 'required|numeric|min:1000',
                'special_pay' => 'nullable|numeric|min:0',
                'personal_pay' => 'nullable|numeric|min:0',
                'qualification_pay' => 'nullable|numeric|min:0',
                'retiring_increment' => 'nullable|numeric|min:0|max:100',
                'commutation' => 'required|numeric|between:0,35',
                'government_type' => 'required|in:federal,khyber_pakhtunkhwa',
            ]);

            $dob = DateInput::parse($validated['dob']);
            $dateOfJoining = DateInput::parse($validated['date_of_joining']);
            $dateOfRetirement = DateInput::parse($validated['date_of_retirement']);

            if ($dob->gte($dateOfRetirement)) {
                throw ValidationException::withMessages([
                    'dob' => 'Date of birth must be before date of retirement.',
                ]);
            }

            if ($dateOfJoining->gte($dateOfRetirement)) {
                throw ValidationException::withMessages([
                    'date_of_joining' => 'Date of joining must be before date of retirement.',
                ]);
            }

            $validated['dob'] = $dob->format('Y-m-d');
            $validated['date_of_joining'] = $dateOfJoining->format('Y-m-d');
            $validated['date_of_retirement'] = $dateOfRetirement->format('Y-m-d');

            $result = $service->calculate($validated);

            Log::info('[Pension] Calculation completed', [
                'name' => $validated['name'],
                'final_pension' => $result['final_pension'] ?? null,
            ]);

            return view('pension.result', [
                'result' => $result,
                'validated' => $validated,
                'themes' => $themes->all(),
            ]);
        } catch (ValidationException $exception) {
            Log::warning('[Pension] Validation failed', [
                'errors' => $exception->errors(),
                'payload' => $request->except(['_token']),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            Log::error('[Pension] Calculation failed', [
                'message' => $exception->getMessage(),
                'payload' => $request->except(['_token']),
            ]);

            throw $exception;
        }
    }

    private function applyLocale(Request $request): void
    {
        $locale = $request->session()->get('locale', 'en');
        app()->setLocale(in_array($locale, ['en', 'ur'], true) ? $locale : 'en');
    }
}
