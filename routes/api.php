<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\FollowUpController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\GmailController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\AnalyticsController;

// ── Public ──────────────────────────────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Gmail OAuth callback (must be outside auth middleware)
Route::get('/gmail/callback', [GmailController::class, 'callback']);

// Multi-provider mail OAuth callback
Route::get('/mail/callback', [\App\Http\Controllers\Api\MailAccountController::class, 'callback']);

// ── Authenticated ────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/cv', [ProfileController::class, 'uploadCv']);

    // Companies
    Route::apiResource('companies', CompanyController::class);
    Route::post('/companies/{company}/exclude', [CompanyController::class, 'exclude']);
    Route::post('/companies/{company}/include', [CompanyController::class, 'include']);

    // Jobs
    Route::apiResource('jobs', JobController::class)->only(['index', 'show', 'store', 'destroy']);

    // Opportunities
    Route::get('/opportunities', [OpportunityController::class, 'index']);
    Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show']);
    Route::post('/opportunities/{opportunity}/approve', [OpportunityController::class, 'approve']);
    Route::post('/opportunities/{opportunity}/reject', [OpportunityController::class, 'reject']);
    Route::post('/opportunities/{opportunity}/score', [OpportunityController::class, 'score']);
    Route::post('/opportunities/{opportunity}/generate-email', [OpportunityController::class, 'generateEmail']);
    Route::patch('/opportunities/{opportunity}', [OpportunityController::class, 'update']);

    // Emails
    Route::get('/emails', [EmailController::class, 'index']);
    Route::get('/emails/{email}', [EmailController::class, 'show']);
    Route::post('/emails', [EmailController::class, 'store']);
    Route::patch('/emails/{email}', [EmailController::class, 'update']);
    Route::post('/emails/{email}/approve', [EmailController::class, 'approve']);
    Route::post('/emails/{email}/reject', [EmailController::class, 'reject']);
    Route::post('/emails/{email}/send', [EmailController::class, 'send']);
    Route::delete('/emails/{email}', [EmailController::class, 'destroy']);

    // Applications (Kanban CRM)
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/applications/{application}', [ApplicationController::class, 'show']);
    Route::patch('/applications/{application}', [ApplicationController::class, 'update']);
    Route::post('/applications/{application}/notes', [ApplicationController::class, 'addNote']);

    // Follow-ups
    Route::get('/follow-ups', [FollowUpController::class, 'index']);
    Route::post('/follow-ups/{followUp}/complete', [FollowUpController::class, 'complete']);
    Route::post('/follow-ups/{followUp}/cancel', [FollowUpController::class, 'cancel']);

    // Search / Discovery
    Route::post('/search/run', [SearchController::class, 'run']);
    Route::get('/search/runs', [SearchController::class, 'history']);
    Route::get('/search/runs/{run}', [SearchController::class, 'show']);

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index']);

    // Settings
    Route::get('/settings', [SettingsController::class, 'show']);
    Route::put('/settings', [SettingsController::class, 'update']);

    // Mail accounts (multi-provider: Gmail, Outlook, Zoho, SMTP)
    Route::get('/mail/accounts', [\App\Http\Controllers\Api\MailAccountController::class, 'index']);
    Route::post('/mail/accounts', [\App\Http\Controllers\Api\MailAccountController::class, 'connect']);
    Route::delete('/mail/accounts/{account}', [\App\Http\Controllers\Api\MailAccountController::class, 'disconnect']);
    Route::post('/mail/accounts/{account}/default', [\App\Http\Controllers\Api\MailAccountController::class, 'setDefault']);
    Route::post('/mail/accounts/{account}/test', [\App\Http\Controllers\Api\MailAccountController::class, 'test']);

    // Legacy Gmail OAuth (kept for backward compat)
    Route::get('/gmail/connect', [GmailController::class, 'connect']);
    Route::post('/gmail/disconnect', [GmailController::class, 'disconnect']);
    Route::get('/gmail/status', [GmailController::class, 'status']);
});
