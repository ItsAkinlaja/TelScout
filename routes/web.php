<?php

use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    return view('landing');
});

// Pricing page
Route::get('/pricing', function () {
    return view('pricing');
});

// All other non-API routes serve the React SPA
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
