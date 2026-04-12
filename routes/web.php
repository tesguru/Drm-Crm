<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\GmailAccountController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/internal/send-initial',  [\App\Http\Controllers\Internal\EmailDispatchController::class, 'sendInitial']);
Route::get('/internal/send-followup', [\App\Http\Controllers\Internal\EmailDispatchController::class, 'sendFollowUp']);





Route::get('/oban-dashboard', function () {
    return view('oban.dashboard');
})->name('oban-dashboard');






Route::get('/oban-status', function () {
    $jobs = \DB::table('oban_jobs')
        ->orderBy('inserted_at', 'desc')
        ->limit(20)
        ->get()
        ->map(function ($job) {
            return [
                'id'           => $job->id,
                'state'        => $job->state,
                'queue'        => $job->queue,
                'worker'       => class_basename(str_replace('Elixir.', '', $job->worker)),
                'args'         => json_decode($job->args),
                'attempt'      => $job->attempt,
                'max_attempts' => $job->max_attempts,
                'inserted_at'  => $job->inserted_at,
                'scheduled_at' => $job->scheduled_at,
                'attempted_at' => $job->attempted_at,
                'completed_at' => $job->completed_at,
                'discarded_at' => $job->discarded_at,
            ];
        });

    $summary = [
        'available'  => \DB::table('oban_jobs')->where('state', 'available')->count(),
        'scheduled'  => \DB::table('oban_jobs')->where('state', 'scheduled')->count(),
        'executing'  => \DB::table('oban_jobs')->where('state', 'executing')->count(),
        'completed'  => \DB::table('oban_jobs')->where('state', 'completed')->count(),
        'retryable'  => \DB::table('oban_jobs')->where('state', 'retryable')->count(),
        'discarded'  => \DB::table('oban_jobs')->where('state', 'discarded')->count(),
        'cancelled'  => \DB::table('oban_jobs')->where('state', 'cancelled')->count(),
    ];

    return response()->json([
        'summary' => $summary,
        'jobs'    => $jobs,
    ]);
});
Route::get('/test-oban', function () {
    \DB::table('oban_jobs')->insert([
        'state'        => \DB::raw("'available'::oban_job_state"),
        'queue'        => 'default',
        'worker'       => 'Elixir.DomainOutreach.Workers.TestWorker',
        'args'         => \DB::raw("'{\"message\":\"Hello from Laravel! Oban is working!\"}'::jsonb"),
        'errors'       => \DB::raw("ARRAY[]::jsonb[]"),
        'tags'         => \DB::raw("ARRAY[]::text[]"),
        'meta'         => \DB::raw("'{}'::jsonb"),
        'attempted_by' => \DB::raw("ARRAY[]::text[]"),
        'priority'     => 0,
        'attempt'      => 0,
        'max_attempts' => 3,
        'inserted_at'  => now(),
        'scheduled_at' => now()->addMinute(),
    ]);

    return response()->json([
        'success'  => true,
        'message'  => '✅ Job scheduled! Watch Elixir terminal in 1 minute.',
        'fires_at' => now()->addMinute()->toTimeString(),
    ]);
});
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