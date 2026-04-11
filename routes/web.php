<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailGeneratorController;
use App\Http\Controllers\WarmingController;
use App\Http\Controllers\WarmingApiController;
use App\Http\Controllers\CampaignController;

// ═══════════════════════════════════════════
// EMAIL GENERATOR
// ═══════════════════════════════════════════

Route::get('/', [EmailGeneratorController::class, 'index'])->name('email.index');
Route::post('/generate', [EmailGeneratorController::class, 'generate'])->name('email.generate');
Route::get('/result/{email}', [EmailGeneratorController::class, 'result'])->name('email.result');
Route::get('/history', [EmailGeneratorController::class, 'history'])->name('email.history');
Route::get('/history/{email}', [EmailGeneratorController::class, 'show'])->name('email.show');

// Quick Campaign from single variant (replaces direct sending)
Route::post('/history/{email}/quick-campaign', [EmailGeneratorController::class, 'quickCampaign'])->name('email.quick_campaign');

// Multi-template campaign from all variants
Route::post('/history/{email}/campaign-from-variants', [EmailGeneratorController::class, 'createCampaignFromVariants'])->name('email.campaign_from_variants');

// Delete email
Route::delete('/history/{email}', [EmailGeneratorController::class, 'destroy'])->name('email.destroy');

// ═══════════════════════════════════════════
// CAMPAIGNS
// ═══════════════════════════════════════════

Route::prefix('campaigns')->name('campaigns.')->controller(CampaignController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{campaign}', 'show')->name('show');
    Route::get('/{campaign}/edit', 'edit')->name('edit');
    Route::put('/{campaign}', 'update')->name('update');
    Route::post('/{campaign}/launch', 'launch')->name('launch');
    Route::post('/{campaign}/pause', 'pause')->name('pause');
    Route::post('/{campaign}/resume', 'resume')->name('resume');
    Route::delete('/{campaign}', 'destroy')->name('destroy');

    // Follow-up
    Route::get('/{campaign}/follow-up', 'createFollowUp')->name('follow_up');
    Route::post('/{campaign}/follow-up', 'storeFollowUp')->name('follow_up.store');
    Route::post('/{campaign}/generate-followup-email', 'generateFollowUpEmail')->name('generate_followup_email');
});

// ═══════════════════════════════════════════
// EMAIL WARMING
// ═══════════════════════════════════════════

Route::prefix('warming')->name('warming.')->controller(WarmingController::class)->group(function () {
    Route::get('/', 'dashboard')->name('dashboard');

    // Accounts
    Route::get('/accounts', 'accounts')->name('accounts');
    Route::post('/accounts', 'storeAccount')->name('accounts.store');
    Route::delete('/accounts/{account}', 'deleteAccount')->name('accounts.delete');
    Route::post('/accounts/{account}/toggle', 'toggleAccount')->name('accounts.toggle');
    Route::post('/accounts/{account}/login', 'loginAccount')->name('accounts.login');
    Route::post('/accounts/{account}/test', 'sendTest')->name('accounts.test');
    Route::post('/accounts/{account}/next-day', 'nextDay')->name('accounts.next_day');

    // Templates
    Route::get('/templates', 'templates')->name('templates');
    Route::post('/templates', 'storeTemplate')->name('templates.store');
    Route::put('/templates/{template}', 'updateTemplate')->name('templates.update');
    Route::delete('/templates/{template}', 'deleteTemplate')->name('templates.delete');

    // Strategies
    Route::get('/strategies', 'strategies')->name('strategies');

    // Logs
    Route::get('/logs', 'logs')->name('logs');
    Route::post('/logs/{log}/retry', 'retryLog')->name('logs.retry');

    // Settings & Quick Start
    Route::get('/settings', 'settings')->name('settings');
    Route::post('/settings', 'updateSettings')->name('settings.update');
    Route::post('/settings/quick-start', 'quickStartWarming')->name('quick_start');

    // Bot control
    Route::post('/bot/start', 'startBot')->name('bot.start');
    Route::post('/bot/stop', 'stopBot')->name('bot.stop');

    // Daily round
    Route::post('/accounts/{account}/daily-round', 'startDailyRound')->name('accounts.daily_round');

    // Saved recipients
    Route::post('/recipients/save', 'saveRecipients')->name('recipients.save');
    Route::delete('/recipients/{recipient}', 'deleteRecipient')->name('recipients.delete');
});

// ═══════════════════════════════════════════
// WARMING API (for Node.js bot)
// ═══════════════════════════════════════════

Route::prefix('api/warming')->controller(WarmingApiController::class)->group(function () {
    Route::get('/next-job', 'nextJob');
    Route::post('/report', 'report');
    Route::get('/account/{account}/session', 'sessionStatus');
    Route::post('/mark-logged-in', 'markLoggedIn');
    Route::get('/settings', 'getSettings');

    // Bot live monitor
    Route::post('/bot-log', 'pushBotLog');
    Route::get('/bot-status', 'getBotStatus');

    // Job verification (safety check before send)
    Route::get('/verify-job/{logId}', 'verifyJob');

    // Bot completion notification
    Route::post('/bot-complete', 'botComplete');
});
