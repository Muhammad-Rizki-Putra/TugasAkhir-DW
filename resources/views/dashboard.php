<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OLAP Analysis Dashboard</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .dataTables_filter input {
            width: 250px;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            transition: all 0.2s ease-in-out;
        }
        .dataTables_filter input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.4);
        }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 8px;
            color: #4b5563;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            margin-bottom: 4px;
        }
        .nav-link:hover {
            background-color: #e5e7eb;
        }
        .nav-link.active {
            background-color: #4f46e5;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }
        .nav-link .material-icons {
            margin-right: 12px;
        }
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #4f46e5;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        /* Custom slider style */
        input[type=range].slider {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            background: #d1d5db;
            outline: none;
            opacity: 0.7;
            -webkit-transition: .2s;
            transition: opacity .2s;
            border-radius: 4px;
        }
        input[type=range].slider:hover {
            opacity: 1;
        }
        input[type=range].slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            background: #4f46e5;
            cursor: pointer;
            border-radius: 50%;
        }
        input[type=range].slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            background: #4f46e5;
            cursor: pointer;
            border-radius: 50%;
        }
    </style>
</head>
<body class="text-gray-800">

    <div class="flex h-screen bg-gray-100">
        <aside class="w-72 bg-white p-4 shadow-lg overflow-y-auto">
            <h1 class="text-2xl font-bold text-gray-900 mb-6 px-2">OLAP Dashboard</h1>
            <nav id="olap-nav"></nav>
        </aside>

        <main class="flex-1 p-6 md:p-8 lg:p-10 overflow-y-auto">
            <div id="content-header" class="mb-6">
                <h2 id="current-analysis-title" class="text-3xl font-bold text-gray-900">Welcome</h2>
                <p id="current-analysis-description" class="text-gray-600 mt-1">Select an analysis from the sidebar to get started.</p>
            </div>
            
            <div id="filters-container" class="bg-white p-4 rounded-xl shadow-md mb-6 hidden"></div>

            <div id="dimension-selector" class="bg-white p-4 rounded-xl shadow-md mb-6 hidden">
                    <h3 class="text-lg font-semibold mb-3">Select Dimensions:</h3>
                    <div class="flex gap-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="form-checkbox text-indigo-600" checked id="dim-product" value="Product_Name">
                            <span class="ml-2">Product</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="form-checkbox text-indigo-600" checked id="dim-year" value="Year">
                            <span class="ml-2">Year</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="form-checkbox text-indigo-600" checked id="dim-country" value="Country_Name">
                            <span class="ml-2">Country</span>
                        </label>
                    </div>
                    <button id="apply-dimensions-btn" class="mt-4 bg-indigo-600 text-white font-semibold px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Apply Dimensions</button>
            </div>

            <div id="content-display" class="bg-white p-6 rounded-xl shadow-md min-h-[600px] flex items-center justify-center">
                <div id="loader" class="loader hidden"></div>
                <div id="error-message" class="text-center text-red-500 hidden"></div>
                <div id="results-container" class="w-full"></div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        // --- CONFIGURATION ---
        const ANALYSES = [
            { id: 'revenue-rollup', title: 'Revenue Roll-up', icon: 'summarize', description: 'Hierarchical revenue totals, from product models up to the grand total.' },
            { id: 'drilldown', title: 'Revenue Drill-down', icon: 'travel_explore', description: 'Analyze monthly revenue patterns for a specific country.', filters: [{type: 'text', id: 'country', label: 'Country Name', defaultValue: 'Indonesia'}]},
            { id: 'sales-cube', title: 'Sales Cube', icon: 'view_in_ar', description: 'Cross-dimensional analysis of sales by product, year, and country.' },
            { id: 'slice', title: 'Product Slice', icon: 'pie_chart', description: 'Focus on the quarterly sales performance of a single product.', filters: [{type: 'text', id: 'product', label: 'Product Name', defaultValue: 'Tahoe'}]},
            { id: 'dice-performance', title: 'Dice Performance', icon: 'casino', description: 'Pinpoint performance by filtering on branch, year, and quarter.', filters: [
                {type: 'text', id: 'branch', label: 'Branch Name', defaultValue: 'Alvis Motors'},
                {type: 'text', id: 'year', label: 'Year', defaultValue: '2017'},
                {type: 'text', id: 'quarter', label: 'Quarter', defaultValue: 'Q3'},
            ]},
            { id: 'pivot-dealer', title: 'Dealer Pivot', icon: 'pivot_table_chart', description: 'Compare dealer performance in selling specific product models (e.g., Audi vs. BMW).' },
            { id: 'annual-trend', title: 'Annual Growth Trend', icon: 'trending_up', description: 'Track year-over-year revenue growth percentages for each country.' },
            { id: 'market-share', title: 'Quarterly Market Share', icon: 'share', description: 'Understand product market share dominance within each quarter.' },
            { id: 'dealer-efficiency', title: 'Dealer Efficiency', icon: 'speed', description: 'Identify the most efficient dealers based on revenue generated per unit sold.' },
            { id: 'mom-growth', title: 'Month-over-Month Growth', icon: 'calendar_month', description: 'Monitor monthly sales momentum to detect trends or issues early.' },
            { id: 'top-product-by-location', title: 'Top Product per Location', icon: 'place', description: 'Discover the best-selling product in each business location.' }
        ];

        // --- NEW: Global state variables ---
        let originalData = [];
        let currentAnalysisId = '';
        let currentChart = null;
        let tableInstance = null;

        // --- UTILITY FUNCTIONS ---
        const formatCurrency = (value) => value ? parseFloat(value).toLocaleString('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits:0 }) : '$0';
        const formatNumber = (value) => value ? parseFloat(value).toLocaleString('en-US') : '0';
        const formatPercentage = (value) => value ? `${parseFloat(value).toFixed(2)}%` : '0.00%';
        
        // --- DOM ELEMENTS ---
        const $loader = $('#loader');
        const $errorMessage = $('#error-message');
        const $resultsContainer = $('#results-container');
        const $nav = $('#olap-nav');
        const $title = $('#current-analysis-title');
        const $description = $('#current-analysis-description');
        const $filtersContainer = $('#filters-container');

        // --- INITIALIZATION ---
        $(document).ready(function () {
            populateNav();
            if (ANALYSES.length > 0) {
                loadAnalysis(ANALYSES[0].id);
            }
        });

        // --- CORE LOGIC ---
        function populateNav() {
            ANALYSES.forEach(analysis => {
                const navLink = $(`<a class="nav-link" data-id="${analysis.id}"><span class="material-icons">${analysis.icon}</span><span>${analysis.title}</span></a>`);
                navLink.on('click', () => loadAnalysis(analysis.id));
                $nav.append(navLink);
            });
        }
        
        async function loadAnalysis(analysisId) {
            const analysis = ANALYSES.find(a => a.id === analysisId);
            if (!analysis) return;
            
            currentAnalysisId = analysisId; // NEW: Store current analysis ID

            updateHeader(analysis.title, analysis.description);
            setActiveNav(analysis.id);
            showLoading(true);
            $filtersContainer.empty().hide();

            $('#dimension-selector').toggle(analysisId === 'sales-cube');

            try {
                let endpoint = `/api/olap/${analysis.id.replace(/_/g, '-')}`;
                let params = new URLSearchParams();

                if (analysis.filters) {
                    buildFilters(analysis.id, analysis.filters);
                    const filterValues = getFilterValues(analysis.filters);
                    if (analysis.id === 'drilldown' || analysis.id === 'slice') {
                         endpoint = `/api/olap/${analysis.id}/${encodeURIComponent(Object.values(filterValues)[0])}`;
                    } else if (analysis.id === 'dice-performance') {
                        params = new URLSearchParams(filterValues);
                        endpoint = `/api/olap/dice?${params.toString()}`;
                    }
                }

                if (analysisId === 'sales-cube') {
                    const selectedDimensions = [];
                    $('#dimension-selector input:checked').each(function() {
                        selectedDimensions.push($(this).val());
                    });
                    // Baris-baris ini mengirim dimensi sebagai array (cth: dimensions[]=Product_Name&dimensions[]=Year)
                    selectedDimensions.forEach(dim => params.append('dimensions[]', dim)); 
                    endpoint = `/api/olap/sales-cube?${params.toString()}`;
                }
                
                const response = await fetch(endpoint);
                if (!response.ok) throw new Error(`Network response was not ok (Status: ${response.status})`);
                const data = await response.json();
                
                renderLayout(data); // MODIFIED: Call layout renderer
                
            } catch (error) {
                showError(`Failed to load data for "${analysis.title}". Please check the console and ensure the backend API is running.`);
                console.error("Fetch Error:", error);
            } finally {
                showLoading(false);
            }
        }

        $('#apply-dimensions-btn').on('click', () => loadAnalysis('sales-cube'));
        
        function buildFilters(analysisId, filters) {
            let filterHtml = '<div class="flex flex-wrap gap-4 items-end">';
            filters.forEach(filter => {
                filterHtml += `<div><label for="filter-${filter.id}" class="block text-sm font-medium text-gray-700 mb-1">${filter.label}</label><input type="${filter.type}" id="filter-${filter.id}" value="${filter.defaultValue}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></div>`;
            });
            filterHtml += `<div><button id="apply-filters-btn" class="bg-indigo-600 text-white font-semibold px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Apply</button></div>`;
            filterHtml += '</div>';

            $filtersContainer.html(filterHtml).show();
            $('#apply-filters-btn').on('click', () => loadAnalysis(analysisId));
        }

        function getFilterValues(filters) {
            const values = {};
            filters.forEach(filter => {
                values[filter.id] = $(`#filter-${filter.id}`).val();
            });
            return values;
        }

        /**
         * NEW: Renders the static layout (slider, canvas, table shell) once.
         * @param {Array} data - The full data array from the API.
         */
        function renderLayout(data) {
            originalData = [...data]; // Store the full dataset
            $resultsContainer.empty();

            if (!originalData || originalData.length === 0) {
                $resultsContainer.html('<p class="text-center text-gray-500">No data found for this analysis.</p>');
                return;
            }

            // --- NEW: Slider HTML ---
            const initialSliderValue = Math.min(originalData.length, 20); // Default to 20 items or less
            const sliderContainer = $(`
                <div class="mb-6 p-4 bg-gray-50 rounded-lg shadow-inner">
                    <div class="flex justify-between items-center mb-1">
                         <label for="chart-slider" class="block text-sm font-medium text-gray-700">Display Top Items</label>
                         <span id="slider-label" class="text-sm font-semibold text-indigo-600">${initialSliderValue} / ${originalData.length}</span>
                    </div>
                    <input type="range" id="chart-slider" min="1" max="${originalData.length}" value="${initialSliderValue}" class="slider">
                </div>
            `);
            $resultsContainer.append(sliderContainer);

            // --- Canvas and Table Shell ---
            const chartCanvas = $('<canvas id="analysisChart" class="mb-8" style="max-height: 400px;"></canvas>');
            const table = $(`<table id="analysisTable" class="display w-full"></table>`);
            $resultsContainer.append(chartCanvas).append(table);

            // --- Initial Render and Event Listener ---
            updateViews();
            $('#chart-slider').on('input', updateViews);
        }

        /**
         * NEW: Updates the chart and table based on the slider value.
         */
        function updateViews() {
            const sliderValue = parseInt($('#chart-slider').val());
            $('#slider-label').text(`${sliderValue} / ${originalData.length}`);
            
            const slicedData = originalData.slice(0, sliderValue);

            const ctx = $('#analysisChart')[0].getContext('2d');
            renderChart(ctx, slicedData);
            renderTable(slicedData);
        }
        
        /**
         * MODIFIED: Renders the chart with the provided (potentially sliced) data.
         * @param {CanvasRenderingContext2D} ctx - The context of the canvas element.
         * @param {Array} data - The data array to render.
         */
        function renderChart(ctx, data) {
            if (currentChart) {
                currentChart.destroy();
            }
            if(data.length === 0) return;

            const analysis = ANALYSES.find(a => a.id === currentAnalysisId);
            let chartType = 'bar';
            const keys = Object.keys(data[0]);
            let labelKey = keys[0], dataKey = keys[1];

            switch (currentAnalysisId) {
                case 'annual-trend':
                case 'mom-growth':
                case 'drilldown':
                case 'slice':
                    chartType = 'line';
                    labelKey = keys.find(k => k.toLowerCase().includes('year') || k.toLowerCase().includes('month') || k.toLowerCase().includes('quarter')) || keys[0];
                    dataKey = keys.find(k => k.toLowerCase().includes('growth') || k.toLowerCase().includes('revenue'));
                    break;
                case 'market-share':
                    chartType = 'pie';
                    labelKey = keys.find(k => k.toLowerCase().includes('product')) || keys[0];
                    dataKey = keys.find(k => k.toLowerCase().includes('share'));
                    break;
                default:
                    labelKey = keys[0];
                    dataKey = keys.find(k => k.toLowerCase().includes('revenue')) || keys[1];
                    break;
            }

            const labels = data.map(row => row[labelKey]);
            const chartData = data.map(row => parseFloat(row[dataKey]));

            const chartColors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#d946ef', '#ec4899'];
            currentChart = new Chart(ctx, {
                type: chartType,
                data: {
                    labels: labels,
                    datasets: [{
                        label: analysis.title,
                        data: chartData,
                        backgroundColor: chartType === 'pie' ? chartColors : (chartType === 'line' ? 'transparent' : '#4f46e5'),
                        borderColor: '#4f46e5',
                        borderWidth: chartType === 'line' ? 2 : 1,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, ticks: { callback: (v) => dataKey.includes('revenue') ? formatCurrency(v) : (dataKey.includes('share') || dataKey.includes('growth') ? formatPercentage(v) : formatNumber(v)) } } },
                    plugins: { legend: { display: chartType === 'pie' }, tooltip: { callbacks: { label: (c) => { let l = c.dataset.label || ''; if(l){l+=': '}; let v = c.parsed.y ?? c.parsed; return l + (dataKey.includes('revenue')?formatCurrency(v):dataKey.includes('share')||dataKey.includes('growth')?formatPercentage(v):formatNumber(v))} } } }
                }
            });
        }

        /**
         * NEW: Renders the table with provided (potentially sliced) data.
         * @param {Array} data - The data array to render.
         */
        function renderTable(data) {
            if (tableInstance) {
                tableInstance.destroy();
                $('#analysisTable').empty(); // Clear headers as well
            }
            if(data.length === 0) return;

            const columns = Object.keys(data[0]).map(key => ({
                title: key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                data: key,
                render: function(data, type, row) {
                    if (type === 'display') {
                        if (key.toLowerCase().includes('revenue') || key.toLowerCase().includes('price')) return formatCurrency(data);
                        if (key.toLowerCase().includes('unit') || key.toLowerCase().includes('total')) return formatNumber(data);
                        if (key.toLowerCase().includes('pct') || key.toLowerCase().includes('share') || key.toLowerCase().includes('growth')) return formatPercentage(data);
                    }
                    return data;
                }
            }));

            tableInstance = $('#analysisTable').DataTable({
                data: data,
                columns: columns,
                responsive: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                ordering: false,
                language: { search: "", searchPlaceholder: "Search records..." },
                destroy: true
            });
        }
        
        // --- UI HELPER FUNCTIONS ---
        function showLoading(isLoading) {
            $loader.toggleClass('hidden', !isLoading);
            $errorMessage.hide();
            if (isLoading) $resultsContainer.empty();
        }

        function showError(message) {
            $resultsContainer.empty();
            $errorMessage.text(message).show();
        }

        function updateHeader(title, description) {
            $title.text(title);
            $description.text(description);
        }

        function setActiveNav(analysisId) {
            $nav.find('.nav-link').removeClass('active');
            $nav.find(`.nav-link[data-id="${analysisId}"]`).addClass('active');
        }
    </script>
</body>
</html>