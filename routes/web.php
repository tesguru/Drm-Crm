<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\GmailAccountController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

// ============================================================
// PUBLIC ROUTES
// ============================================================
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/auth/google',
    [GoogleController::class, 'redirect'])
    ->name('auth.google');

// THIS IS THE KEY FIX — callback not callbackAccount
Route::get('/auth/google/callback',
    [GoogleController::class, 'callback'])
    ->name('auth.google.callback');

Route::post('/logout',
    [GoogleController::class, 'logout'])
    ->name('logout');

// ============================================================
// PROTECTED ROUTES
// ============================================================
Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    // Add Gmail account
    Route::get('/auth/google/account',
        [GoogleController::class, 'redirectAccount'])
        ->name('auth.google.account');

    // View Routes
    Route::get('/gmail-accounts', function () {
        return view('gmail-accounts.index');
    })->name('gmail-accounts.index');

    Route::get('/templates', function () {
        return view('templates.index');
    })->name('templates.index');

    Route::get('/campaigns', function () {
        return view('campaigns.index');
    })->name('campaigns.index');

    Route::get('/campaigns/{id}', function ($id) {
        return view('campaigns.show', ['id' => $id]);
    })->name('campaigns.show');

    // Gmail Accounts API
    Route::get('/api/gmail-accounts',
        [GmailAccountController::class, 'index']);
    Route::post('/api/gmail-accounts/{id}/toggle',
        [GmailAccountController::class, 'toggle']);
    Route::post('/api/gmail-accounts/{id}/limit',
        [GmailAccountController::class, 'updateLimit']);
    Route::post('/api/gmail-accounts/{id}/reset-daily',
        [GmailAccountController::class, 'resetDaily']);
    Route::delete('/api/gmail-accounts/{id}',
        [GmailAccountController::class, 'destroy']);

    // Templates API
    Route::get('/api/templates',
        [TemplateController::class, 'index']);
    Route::get('/api/templates/all',
        [TemplateController::class, 'all']);
    Route::post('/api/templates',
        [TemplateController::class, 'store']);
    Route::put('/api/templates/{id}',
        [TemplateController::class, 'update']);
    Route::delete('/api/templates/{id}',
        [TemplateController::class, 'destroy']);

    // Campaigns API
    Route::get('/api/campaigns',
        [CampaignController::class, 'index']);
    Route::post('/api/campaigns/preview-split',
        [CampaignController::class, 'previewSplit']);
    Route::get('/api/campaigns/{id}',
        [CampaignController::class, 'show']);
    Route::post('/api/campaigns',
        [CampaignController::class, 'store']);
    Route::post('/api/campaigns/{id}/retry-failed',
        [CampaignController::class, 'retryFailed']);
    Route::delete('/api/campaigns/{id}',
        [CampaignController::class, 'destroy']);

    // Follow-ups API
    Route::get('/api/follow-ups/{id}/status',
        [FollowUpController::class, 'status']);
    Route::post('/api/follow-ups/{id}/send',
        [FollowUpController::class, 'send']);
    Route::post('/api/follow-ups/{id}/check-replies',
        [FollowUpController::class, 'checkReplies']);

        Route::get('/api/follow-ups/{id}/progress',
    [FollowUpController::class, 'progress']);
});