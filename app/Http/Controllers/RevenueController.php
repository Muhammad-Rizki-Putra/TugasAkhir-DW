<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Make sure to import the DB facade

class RevenueController extends Controller
{
    /**
     * 1. Roll-up Revenue per Produk dan Model
     * FIXED: Table names changed to lowercase ('product').
     */
    public function getRevenueRollup()
    {
        $query = "
            SELECT p.Model_Name AS Model, p.Product_Name AS Product, SUM(r.Revenue) AS Total_Revenue
            FROM revenue r JOIN product p ON r.Product_ID = p.Product_ID
            GROUP BY p.Model_Name, p.Product_Name

            UNION ALL

            -- Group by Model (subtotal for products)
            SELECT p.Model_Name AS Model, 'TOTAL MODEL' AS Product, SUM(r.Revenue) AS Total_Revenue
            FROM revenue r JOIN product p ON r.Product_ID = p.Product_ID
            GROUP BY p.Model_Name

            UNION ALL

            -- Grand total
            SELECT 'TOTAL ALL' AS Model, '' AS Product, SUM(r.Revenue) AS Total_Revenue
            FROM revenue r

        ";

        $data = DB::select($query);
        return response()->json($data);
    }

    /**
     * 2. Drill-down Revenue per Bulan di Negara Tertentu
     * FIXED: Table names changed to lowercase ('date', 'branch').
     */
    public function getDrilldownByCountry($country)
    {
        $query = "
            SELECT d.year, d.Month, SUM(r.Revenue) AS Monthly_Revenue
            FROM revenue r
            JOIN date d ON r.Date_ID = d.Date_ID
            JOIN branch b ON r.Branch_ID = b.Branch_ID
            WHERE b.Country_Name = ?
            GROUP BY d.year, d.Month
            ORDER BY d.year ASC, CAST(d.Month AS INT) ASC
        ";

        $data = DB::select($query, [$country]); // Use parameter binding for security
        return response()->json($data);
    }
    
    /**
     * 3. Penjualan total dan revenue per produk, per tahun, dan per negara (CUBE)
     * FIXED: Table names changed to lowercase ('product', 'date', 'dealer', 'location').
     */
    public function getSalesCube(Request $request)
    {
        // Get selected dimensions from the request, default to all if none provided
        $selectedDimensions = $request->input('dimensions', ['Product_Name', 'Year', 'Country_Name']);

        // Ensure valid dimensions (security and data integrity)
        $allowedDimensions = ['Product_Name', 'Year', 'Country_Name', /* add other potential dimensions like 'Branch', 'Quarter' */];
        $validDimensions = array_intersect($selectedDimensions, $allowedDimensions);

        if (empty($validDimensions)) {
            // If no valid dimensions are selected, return a base aggregate or an error
            $query = "
                SELECT 'All Products' AS Product_Name, 'All Years' AS Year, 'All Countries' AS Country_Name, SUM(R.Revenue) AS Total_Revenue, SUM(R.Units_Sold) AS Total_Units
                FROM revenue R
            ";
        } else {
            // Build the GROUP BY clause dynamically
            $groupByColumns = implode(', ', array_map(function($dim) {
                // Map dimension names to actual table columns if they differ
                // e.g., 'Product_Name' -> 'P.Product_Name'
                if ($dim === 'Product_Name') return 'P.Product_Name';
                if ($dim === 'Year') return 'D.Year';
                if ($dim === 'Country_Name') return 'L.Country_Name';
                // Add more mappings if needed
                return $dim; // Fallback
            }, $validDimensions));

            $selectColumns = implode(', ', array_map(function($dim) {
                if ($dim === 'Product_Name') return 'P.Product_Name';
                if ($dim === 'Year') return 'D.Year';
                if ($dim === 'Country_Name') return 'L.Country_Name';
                // Add more mappings
                return "NULL AS " . $dim; // If a dimension isn't selected, return NULL for its column
            }, $validDimensions));


            // Build the JOIN clauses dynamically based on selected dimensions
            $joins = [];
            if (in_array('Product_Name', $validDimensions)) {
                $joins[] = 'JOIN product P ON R.Product_ID = P.Product_ID';
            }
            if (in_array('Year', $validDimensions)) {
                $joins[] = 'JOIN date D ON R.Date_ID = D.Date_ID';
            }
            if (in_array('Country_Name', $validDimensions)) {
                $joins[] = 'JOIN dealer De ON R.Dealer_ID = De.Dealer_ID';
                $joins[] = 'JOIN location L ON De.Location_ID = L.Location_ID';
            }
            // Add more joins for other dimensions

            $joinedTables = implode(' ', array_unique($joins)); // Use array_unique to avoid duplicate joins

            $query = "
                SELECT
                    " . (in_array('Product_Name', $validDimensions) ? 'P.Product_Name' : "'All Products' AS Product_Name") . ",
                    " . (in_array('Year', $validDimensions) ? 'D.Year' : "'All Years' AS Year") . ",
                    " . (in_array('Country_Name', $validDimensions) ? 'L.Country_Name' : "'All Countries' AS Country_Name") . ",
                    SUM(R.Revenue) AS Total_Revenue,
                    SUM(R.Units_Sold) AS Total_Units
                FROM revenue R
                $joinedTables
                GROUP BY $groupByColumns
                ORDER BY " . $groupByColumns; // Add ordering for better presentation
        }

        $data = DB::select($query);
        return response()->json($data);
    }


    /**
     * 4. Slice Revenue untuk Produk Tertentu
     * FIXED: Table names changed to lowercase ('product', 'date').
     */
    public function getSliceByProduct($product)
    {
        $query = "
            SELECT p.Product_Name, d.year, d.Quarter, SUM(r.Units_Sold) AS Total_Units, SUM(r.Revenue) AS Total_Revenue
            FROM revenue r
            JOIN product p ON r.Product_ID = p.Product_ID
            JOIN date d ON r.Date_ID = d.Date_ID
            WHERE p.Product_Name = ?
            GROUP BY p.Product_Name, d.year, d.Quarter
            ORDER BY d.year, d.Quarter
        ";

        $data = DB::select($query, [$product]);
        return response()->json($data);
    }
    
    /**
     * 5. Dice Revenue for a specific branch, quarter, and year
     * FIXED: Table names changed to lowercase ('product', 'branch', 'date').
     */
    public function getDicePerformance(Request $request)
    {
        $branch = $request->query('branch', 'Alvis Motors');
        $quarter = $request->query('quarter', 'Q3');
        $year = $request->query('year', '2017');

        $query = "
            SELECT p.Product_Name, SUM(r.Revenue) AS Product_Revenue
            FROM revenue r
            JOIN product p ON r.Product_ID = p.Product_ID
            JOIN branch b ON r.Branch_ID = b.Branch_ID
            JOIN date d ON r.Date_ID = d.Date_ID
            WHERE b.Branch_NM = ? AND d.Quarter = ? AND d.Year = ?
            GROUP BY p.Product_Name
            ORDER BY Product_Revenue DESC
        ";

        $data = DB::select($query, [$branch, $quarter, $year]);
        return response()->json($data);
    }
    
    /**
     * 6. Pivot Analisis Revenue per Dealer dan Kategori Produk
     * FIXED: The PIVOT function is not available in SQLite.
     * This query has been rewritten using conditional aggregation (CASE statements) to achieve the same result.
     * Also fixed table names to lowercase ('dealer', 'product').
     */
    public function getPivotDealer()
    {
        $query = "
            SELECT
                d.Dealer_NM,
                SUM(CASE WHEN p.Model_Name LIKE '%Audi%' THEN r.Revenue ELSE 0 END) AS Audi_Revenue,
                SUM(CASE WHEN p.Model_Name LIKE '%BMW%' THEN r.Revenue ELSE 0 END) AS BMW_Revenue,
                SUM(CASE WHEN p.Model_Name LIKE '%Audi%' OR p.Model_Name LIKE '%BMW%' THEN r.Revenue ELSE 0 END) AS Revenue_Combined
            FROM revenue r
            JOIN dealer d ON r.Dealer_ID = d.Dealer_ID
            JOIN product p ON r.Product_ID = p.Product_ID
            WHERE p.Model_Name LIKE '%Audi%' OR p.Model_Name LIKE '%BMW%'
            GROUP BY d.Dealer_NM
            ORDER BY Revenue_Combined DESC
        ";

        $data = DB::select($query);
        return response()->json($data);
    }
    
    /**
     * 7. Trend Revenue Tahunan per Negara
     * FIXED: Table names changed to lowercase ('branch', 'date').
     */
    public function getAnnualTrend()
    {
        $query = "
            SELECT
                b.Country_Name,
                d.Year,
                SUM(r.Revenue) AS Annual_Revenue,
                (SUM(r.Revenue) - LAG(SUM(r.Revenue), 1) OVER (PARTITION BY b.Country_Name ORDER BY d.Year)) * 100.0 /
                LAG(SUM(r.Revenue), 1) OVER (PARTITION BY b.Country_Name ORDER BY d.Year) AS Growth_Pct
            FROM revenue r
            JOIN branch b ON r.Branch_ID = b.Branch_ID
            JOIN date d ON r.Date_ID = d.Date_ID
            GROUP BY b.Country_Name, d.Year
            ORDER BY b.Country_Name, d.Year
        ";

        $data = DB::select($query);
        return response()->json($data);
    }

    /**
     * 8. Analisis Market Share Produk per Kuartal
     * FIXED: Table names changed to lowercase ('product', 'date').
     */
    public function getMarketShare()
    {
        $query = "
            SELECT
                d.Year,
                d.Quarter,
                p.Product_Name,
                SUM(r.Revenue) AS Product_Revenue,
                SUM(r.Revenue) * 100.0 / SUM(SUM(r.Revenue)) OVER (PARTITION BY d.Year, d.Quarter) AS Market_Share_Pct
            FROM revenue r
            JOIN product p ON r.Product_ID = p.Product_ID
            JOIN date d ON r.Date_ID = d.Date_ID
            GROUP BY d.Year, d.Quarter, p.Product_Name
            ORDER BY d.Year, d.Quarter, Market_Share_Pct DESC
        ";

        $data = DB::select($query);
        return response()->json($data);
    }

    /**
     * 9. Analisis Efisiensi Dealer (Revenue per Unit)
     * FIXED: Table names changed to lowercase ('dealer', 'location').
     */
    public function getDealerEfficiency()
    {
        $query = "
            SELECT
                d.Dealer_NM,
                l.Location_NM,
                SUM(r.Revenue) AS Total_Revenue,
                SUM(r.Units_Sold) AS Total_Units,
                SUM(r.Revenue) * 1.0 / SUM(r.Units_Sold) AS Revenue_per_Unit
            FROM revenue r
            JOIN dealer d ON r.Dealer_ID = d.Dealer_ID
            JOIN location l ON d.Location_ID = l.Location_ID
            GROUP BY d.Dealer_NM, l.Location_NM
            ORDER BY Revenue_per_Unit DESC
        ";

        $data = DB::select($query);
        return response()->json($data);
    }

    /**
     * 10. Perbandingan Revenue Bulan Ini vs Bulan Sebelumnya
     * FIXED: Table names changed to lowercase ('branch', 'date').
     */
    public function getMonthOverMonthGrowth()
    {
        $query = "
            WITH Monthly_Revenue AS (
                SELECT
                    b.Country_Name,
                    d.Year,
                    d.Month,
                    SUM(r.Revenue) AS Current_Month_Revenue,
                    LAG(SUM(r.Revenue), 1) OVER (PARTITION BY b.Country_Name ORDER BY d.Year, d.Month) AS Previous_Month_Revenue
                FROM revenue r
                JOIN branch b ON r.Branch_ID = b.Branch_ID
                JOIN date d ON r.Date_ID = d.Date_ID
                GROUP BY b.Country_Name, d.Year, d.Month
            )
            SELECT
                Country_Name,
                Year,
                Month,
                Current_Month_Revenue,
                Previous_Month_Revenue,
                (Current_Month_Revenue - Previous_Month_Revenue) * 100.0 / Previous_Month_Revenue AS MoM_Growth
            FROM Monthly_Revenue
            WHERE Previous_Month_Revenue IS NOT NULL
            ORDER BY Country_Name, Year, CAST(Month AS INT)
        ";

        $data = DB::select($query);
        return response()->json($data);
    }

    /**
     * 11. Analisis Produk dengan Revenue Tertinggi per Lokasi
     * FIXED: Table names changed to lowercase ('product', 'dealer', 'location').
     */
    public function getTopProductByLocation()
    {
        $query = "
            WITH Ranked_Products AS (
                SELECT
                    l.Location_NM,
                    p.Product_Name,
                    SUM(r.Revenue) AS Total_Revenue,
                    RANK() OVER (PARTITION BY l.Location_NM ORDER BY SUM(r.Revenue) DESC) as Revenue_Rank
                FROM revenue r
                JOIN product p ON r.Product_ID = p.Product_ID
                JOIN dealer d ON r.Dealer_ID = d.Dealer_ID
                JOIN location l ON d.Location_ID = l.Location_ID
                GROUP BY l.Location_NM, p.Product_Name
            )
            SELECT Location_NM, Product_Name, Total_Revenue
            FROM Ranked_Products
            WHERE Revenue_Rank = 1
            ORDER BY Total_Revenue DESC
        ";

        $data = DB::select($query);
        return response()->json($data);
    }
}
