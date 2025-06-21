<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RevenueController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Route 1: The Homepage ('/')
// This will automatically redirect users from the homepage to your dashboard.
Route::get('/', function () {
    return redirect('/dashboard');
});

// Route 2: The Dashboard Page
// This route returns the main dashboard view you created.
Route::get('/dashboard', function () {
    return view('dashboard'); // Assumes your file is named dashboard.blade.php
});


// --- ALL YOUR API ROUTES ---
// These should stay exactly as they were. They are called by the dashboard's JavaScript.
Route::get('/api/olap/revenue-rollup', [RevenueController::class, 'getRevenueRollup']);
Route::get('/api/olap/drilldown/{country}', [RevenueController::class, 'getDrilldownByCountry']);
Route::get('/api/olap/sales-cube', [RevenueController::class, 'getSalesCube']);
Route::get('/api/olap/slice/{product}', [RevenueController::class, 'getSliceByProduct']);
Route::get('/api/olap/dice', [RevenueController::class, 'getDicePerformance']);
Route::get('/api/olap/pivot-dealer', [RevenueController::class, 'getPivotDealer']);
Route::get('/api/olap/annual-trend', [RevenueController::class, 'getAnnualTrend']);
Route::get('/api/olap/market-share', [RevenueController::class, 'getMarketShare']);
Route::get('/api/olap/dealer-efficiency', [RevenueController::class, 'getDealerEfficiency']);
Route::get('/api/olap/mom-growth', [RevenueController::class, 'getMonthOverMonthGrowth']);
Route::get('/api/olap/top-product-by-location', [RevenueController::class, 'getTopProductByLocation']);