<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailGeneratorController;

// Main generator page
Route::get('/', [EmailGeneratorController::class, 'index'])->name('email.index');

// Generate email
Route::post('/generate', [EmailGeneratorController::class, 'generate'])->name('email.generate');

// Generated email result
Route::get('/result/{email}', [EmailGeneratorController::class, 'result'])->name('email.result');

// Email history
Route::get('/history', [EmailGeneratorController::class, 'history'])->name('email.history');

// Show specific email details
Route::get('/history/{email}', [EmailGeneratorController::class, 'show'])->name('email.show');

// Delete email
Route::delete('/history/{email}', [EmailGeneratorController::class, 'destroy'])->name('email.destroy');
