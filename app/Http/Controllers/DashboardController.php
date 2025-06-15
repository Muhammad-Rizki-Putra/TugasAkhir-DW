<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function revenueSummary()
    {
        // Dummy data: normally you'd load & process from CSV files here
        $data = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr'],
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => [120000, 150000, 180000, 130000],
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                ],
            ],
        ];

        return response()->json($data);
    }
}