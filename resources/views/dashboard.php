<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Revenue Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

    <style>
        body {
            background-color: #f4f7fc;
            font-family: 'Poppins', sans-serif;
        }
        .sidebar {
            background-color: #ffffff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            padding: 20px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.05);
        }
        .main-content {
            padding: 30px;
        }
        .stat-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h5 {
            color: #6c757d;
            font-size: 1rem;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: #343a40;
        }
        .chart-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        #revenueTable_wrapper {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .filter-section {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="main-content">
        <h2 class="mb-4">Revenue Dashboard</h2>

        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <h5>Total Revenue</h5>
                    <p class="stat-value" id="total-revenue">0</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5>Total Transactions</h5>
                    <p class="stat-value" id="total-transactions">0</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5>Top Performing Branch</h5>
                    <p class="stat-value" id="top-branch">N/A</p>
                </div>
            </div>
        </div>

        <div class="filter-section">
             <div class="row align-items-end">
                <div class="col-md-6">
                    <label for="branch-filter" class="form-label">Filter by Branch:</label>
                    <select id="branch-filter" class="form-select">
                        <option value="">All</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="date-range-picker" class="form-label">Filter by Date:</label>
                    <input type="text" id="date-range-picker" class="form-control">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="chart-container mb-4">
                    <canvas id="barChart"></canvas>
                    <div class="mt-2">
                        <label for="productSlider" class="form-label">Number of Products Shown: <span id="productSliderValue">5</span></label>
                        <input type="range" class="form-range" id="productSlider" min="1" value="5" step="1">
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container mb-4">
                    <canvas id="pieChart"></canvas>
                    <div class="mt-2">
                        <label for="dealerSlider" class="form-label">Number of Dealers Shown: <span id="dealerSliderValue">5</span></label>
                        <input type="range" class="form-range" id="dealerSlider" min="1" value="5" step="1">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <table id="revenueTable" class="table table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Dealer</th>
                        <th>Product</th>
                        <th>Revenue</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        let revenueTable;
        let barChart, pieChart;
        let originalData = [];

        // Chart.js Global Configuration
        Chart.defaults.font.family = "'Poppins', sans-serif";

        function formatCurrency(value) {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(value);
        }

        $(document).ready(function () {
            // Initialize Date Range Picker
           $('#date-range-picker').daterangepicker({
                opens: 'left',
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });

    $('#dealerSlider').on('input', function () {
        const count = parseInt($(this).val());
        $('#dealerSliderValue').text(count);
        updatePieChart(count);
    });


            $('#date-range-picker').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
                filterAndRenderData();
            });

            $('#date-range-picker').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                filterAndRenderData();
            });


            // Populate branch dropdown
            $.get('/api/branches', function (branches) {
                branches.forEach(branch => {
                    $('#branch-filter').append(`<option value="${branch}">${branch}</option>`);
                });
            });

            $('#productSlider').on('input', function () {
                const count = parseInt($(this).val());
                $('#productSliderValue').text(count);
                updateBarChart(count);
            });


            // Load initial data
            loadInitialData();

            // Reload on branch filter change
            $('#branch-filter').change(filterAndRenderData);
        });

        function loadInitialData() {
            $.get('/api/revenue', function (data) {
                originalData = data;
                // Initialize DataTable
                revenueTable = $('#revenueTable').DataTable({
                    data: [],
                    columns: [
                        { data: 'branch' },
                        { data: 'dealer' },
                        { data: 'product' },
                        { data: 'revenue', render: (d) => formatCurrency(d) },
                        { data: 'date', render: (d) => moment(d).format('YYYY-MM-DD') },
                    ],
                    responsive: true,
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search records...",
                    }
                });
                filterAndRenderData();
            });
        }

        function filterAndRenderData() {
            let filteredData = [...originalData];
            const selectedBranch = $('#branch-filter').val();
            const dateRange = $('#date-range-picker').val();

            // Branch filter
            if (selectedBranch) {
                filteredData = filteredData.filter(item => item.branch === selectedBranch);
            }

            // Date range filter
            if(dateRange) {
                const [start, end] = dateRange.split(' - ').map(d => moment(d, 'MM/DD/YYYY'));
                filteredData = filteredData.filter(item => {
                    const itemDate = moment(item.date);
                    return itemDate.isBetween(start, end, null, '[]');
                });
            }

            // Update UI
            renderDataTable(filteredData);
            renderCharts(filteredData);
            renderStatCards(filteredData);
        }

        function renderDataTable(data) {
             revenueTable.clear().rows.add(data).draw();
        }

        function renderStatCards(data) {
            const totalRevenue = data.reduce((sum, item) => sum + parseFloat(item.revenue), 0);
            const totalTransactions = data.length;

            let topBranch = 'N/A';
            if (data.length > 0) {
                const branchRevenue = {};
                data.forEach(item => {
                    branchRevenue[item.branch] = (branchRevenue[item.branch] || 0) + parseFloat(item.revenue);
                });
                topBranch = Object.keys(branchRevenue).reduce((a, b) => branchRevenue[a] > branchRevenue[b] ? a : b);
            }

            $('#total-revenue').text(formatCurrency(totalRevenue));
            $('#total-transactions').text(totalTransactions.toLocaleString());
            $('#top-branch').text(topBranch);
        }


        let fullDealerLabels = [];
        let fullDealerData = [];

        let fullProductLabels = [];
        let fullProductData = [];

        function renderCharts(data) {
            // Group data for charts
            const productMap = {};
            const dealerMap = {};
            data.forEach(item => {
                productMap[item.product] = (productMap[item.product] || 0) + parseFloat(item.revenue);
                dealerMap[item.dealer] = (dealerMap[item.dealer] || 0) + parseFloat(item.revenue);
            });

            // Prepare Bar Chart Data
            // Sort productMap descending by revenue
            const sortedProducts = Object.entries(productMap)
                .sort((a, b) => b[1] - a[1]); // b[1] - a[1] = descending by revenue

            // Separate labels and data
            const productLabels = sortedProducts.map(entry => entry[0]);
            const productData = sortedProducts.map(entry => entry[1]);

            fullProductLabels = productLabels;
            fullProductData = productData;

            const maxProducts = fullProductLabels.length;
            $('#productSlider').attr('max', maxProducts);
            const initialProductCount = Math.min(5, maxProducts);
            $('#productSlider').val(initialProductCount);
            $('#productSliderValue').text(initialProductCount);

            updateBarChart(initialProductCount);

            // Prepare Dealer Data (sorted descending)
            fullDealerLabels = Object.keys(dealerMap);
            fullDealerData = Object.values(dealerMap);

            const sortedDealers = fullDealerLabels
                .map((dealer, i) => ({ dealer, revenue: fullDealerData[i] }))
                .sort((a, b) => b.revenue - a.revenue);

            fullDealerLabels = sortedDealers.map(d => d.dealer);
            fullDealerData = sortedDealers.map(d => d.revenue);

            const maxDealers = fullDealerLabels.length;
            $('#dealerSlider').attr('max', maxDealers);
            const initialCount = Math.min(5, maxDealers);
            $('#dealerSlider').val(initialCount);
            $('#dealerSliderValue').text(initialCount);

            updatePieChart(initialCount);

            // Render Bar Chart
            if (barChart) barChart.destroy();
            barChart = new Chart(document.getElementById('barChart'), {
                type: 'bar',
                data: {
                    labels: productLabels,
                    datasets: [{
                        label: 'Revenue by Product',
                        data: productData,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'Revenue by Product', font: { size: 16 } }
                    },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        function updatePieChart(count) {
    const slicedLabels = fullDealerLabels.slice(0, count);
    const slicedData = fullDealerData.slice(0, count);

    const backgroundColors = slicedLabels.map((_, i) =>
        `hsl(${(i * 360 / count)}, 70%, 60%)`
    );

    if (pieChart) pieChart.destroy();
    pieChart = new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: {
            labels: slicedLabels,
            datasets: [{
                label: 'Revenue by Dealer',
                data: slicedData,
                backgroundColor: backgroundColors,
                borderColor: '#fff'
            }]
        },
        options: {
            plugins: {
                legend: { position: 'bottom' },
                title: { display: true, text: 'Revenue by Dealer', font: { size: 16 } }
            }
        }
    });
}

function updateBarChart(count) {
    const slicedLabels = fullProductLabels.slice(0, count);
    const slicedData = fullProductData.slice(0, count);

    if (barChart) barChart.destroy();
    barChart = new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: slicedLabels,
            datasets: [{
                label: 'Revenue by Product',
                data: slicedData,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: { display: false },
                title: { display: true, text: 'Revenue by Product', font: { size: 16 } }
            },
            scales: { y: { beginAtZero: true } }
        }
    });
}
    </script>
</body>
</html>