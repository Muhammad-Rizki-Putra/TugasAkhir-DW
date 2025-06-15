<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function getRevenueData(Request $request)
    {
        $branchFilter = $request->input('branch');

        Log::info('Getting revenue data...');

        $revenueData = $this->readCsv(storage_path('app/revenue.csv'));
        Log::info('Revenue data count: ' . count($revenueData));

        // Log satu baris isi file
        Log::info('Sample row: ', $revenueData[0] ?? []);
        $branchData = $this->indexById(storage_path('app/branch.csv'), 'Branch_ID', 'Branch_NM');
        $dealerData = $this->indexById(storage_path('app/dealer.csv'), 'Dealer_ID', 'Dealer_NM');
        $dateData = $this->indexById(storage_path('app/date.csv'), 'Date_ID', 'Date');
        $productData = $this->indexById(storage_path('app/product.csv'), 'Product_ID', 'Product_Name');

        $result = [];

        foreach ($revenueData as $row) {
            $branchName = $branchData[$row['Branch_ID']] ?? 'Unknown';
            $dealerName = $dealerData[$row['Dealer_ID']] ?? 'Unknown';
            $date = $dateData[$row['Date_ID']] ?? 'Unknown';
            $product = $productData[$row['Product_ID']] ?? $row['Product_ID'];

            if ($branchFilter && $branchName !== $branchFilter) {
                continue;
            }

            $result[] = [
                'branch' => $branchName,
                'dealer' => $dealerName,
                'product' => $product,
                'revenue' => $row['Revenue'],
                'date' => $date,
            ];
        }

        return response()->json($result);
    }

    public function getBranches()
    {
        $branches = [];

        $data = $this->readCsv(storage_path('app/branch.csv'));
        foreach ($data as $row) {
            $branches[] = $row['Branch_NM'];
        }

        return response()->json(array_unique($branches));
    }

    // Fungsi bantu baca CSV sebagai array
    private function readCsv($path)
    {
        $rows = [];
        if (!file_exists($path)) return $rows;

        if (($handle = fopen($path, 'r')) !== false) {
            // Baca header
            $header = fgetcsv($handle, 1000, ',');

            // Hapus BOM jika ada
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $rows[] = array_combine($header, $data);
            }
            fclose($handle);
        }

        return $rows;
    }

    private function indexById($path, $idKey, $valueKey)
    {
        $data = $this->readCsv($path);
        $result = [];
        foreach ($data as $row) {
            $result[$row[$idKey]] = $row[$valueKey];
        }
        return $result;
    }
}

