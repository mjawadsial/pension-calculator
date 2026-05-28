<?php

use App\Http\Controllers\PensionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PensionController::class, 'index'])->name('pension.index');
Route::post('/calculate', [PensionController::class, 'calculate'])->name('pension.calculate');
Route::get('/language/{locale}', function (string $locale) {
    if (!in_array($locale, ['en', 'ur'], true)) {
        $locale = 'en';
    }

    session(['locale' => $locale]);

    return redirect()->back();
})->name('language.switch');
