<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RevenueController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [RevenueController::class, 'index']);
Route::get('/api/revenue', [RevenueController::class, 'getRevenueData']);
Route::get('/api/branches', [RevenueController::class, 'getBranches']);
Route::get('/api/revenue-summary', [DashboardController::class, 'revenueSummary']);
