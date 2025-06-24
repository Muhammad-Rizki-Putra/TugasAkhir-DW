<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OLAP Analysis Dashboard</title>
    
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- DataTables for advanced tables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    
    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* A light gray background */
        }
        /* Custom styles for DataTables search input */
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
        /* Style for the sidebar navigation */
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
        /* Custom loader style */
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
    </style>
</head>
<body class="text-gray-800">

    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar Navigation -->
        <aside class="w-72 bg-white p-4 shadow-lg overflow-y-auto">
            <h1 class="text-2xl font-bold text-gray-900 mb-6 px-2">OLAP Dashboard</h1>
            <nav id="olap-nav">
                <!-- Links will be dynamically populated by JS -->
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 md:p-8 lg:p-10 overflow-y-auto">
            <div id="content-header" class="mb-6">
                <h2 id="current-analysis-title" class="text-3xl font-bold text-gray-900">Welcome</h2>
                <p id="current-analysis-description" class="text-gray-600 mt-1">Select an analysis from the sidebar to get started.</p>
            </div>
            
            <!-- Filters section, hidden by default -->
            <div id="filters-container" class="bg-white p-4 rounded-xl shadow-md mb-6 hidden">
                
            </div>

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

            <!-- Content Display -->
            <div id="content-display" class="bg-white p-6 rounded-xl shadow-md min-h-[600px] flex items-center justify-center">
                
                <div id="loader" class="loader hidden"></div>
                <div id="error-message" class="text-center text-red-500 hidden"></div>
                <div id="results-container" class="w-full"></div>
            </div>
        </main>
    </div>

    <!-- jQuery and DataTables scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        // --- CONFIGURATION ---
        const ANALYSES = [
            { id: 'revenue-rollup', title: 'Revenue Roll-up', icon: 'summarize', description: 'Hierarchical revenue totals, from product models up to the grand total.' },
            // FIXED: Changed 'drilldown-country' to 'drilldown' to match the route definition
            { id: 'drilldown', title: 'Revenue Drill-down', icon: 'travel_explore', description: 'Analyze monthly revenue patterns for a specific country.', filters: [{type: 'text', id: 'country', label: 'Country Name', defaultValue: 'Indonesia'}]},
            { id: 'sales-cube', title: 'Sales Cube', icon: 'view_in_ar', description: 'Cross-dimensional analysis of sales by product, year, and country.' },
            // FIXED: Changed 'slice-product' to 'slice' to match the route definition
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

        // --- UTILITY FUNCTIONS ---
        const formatCurrency = (value) => value ? parseFloat(value).toLocaleString('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits:0 }) : '$0';
        const formatNumber = (value) => value ? parseFloat(value).toLocaleString('en-US') : '0';
        const formatPercentage = (value) => value ? `${parseFloat(value).toFixed(2)}%` : '0.00%';
        
        // --- DOM ELEMENTS ---
        const $loader = $('#loader');
        const $initialMessage = $('#initial-message');
        const $errorMessage = $('#error-message');
        const $resultsContainer = $('#results-container');
        const $nav = $('#olap-nav');
        const $title = $('#current-analysis-title');
        const $description = $('#current-analysis-description');
        const $filtersContainer = $('#filters-container');

        // --- INITIALIZATION ---
        $(document).ready(function () {
            populateNav();
            // Set the first analysis as active by default
            if (ANALYSES.length > 0) {
                loadAnalysis(ANALYSES[0].id);
            }
        });

        // --- CORE LOGIC ---

        /**
         * Populates the sidebar navigation with links for each analysis.
         */
        function populateNav() {
            ANALYSES.forEach(analysis => {
                const navLink = $(`
                    <a class="nav-link" data-id="${analysis.id}">
                        <span class="material-icons">${analysis.icon}</span>
                        <span>${analysis.title}</span>
                    </a>
                `);
                navLink.on('click', () => loadAnalysis(analysis.id));
                $nav.append(navLink);
            });
        }
        
        /**
         * Main function to load and render an analysis by its ID.
         * @param {string} analysisId - The ID of the analysis to load.
         */
        async function loadAnalysis(analysisId) {
            const analysis = ANALYSES.find(a => a.id === analysisId);
            if (!analysis) return;

            // Update UI state
            updateHeader(analysis.title, analysis.description);
            setActiveNav(analysis.id);
            showLoading(true);
            $filtersContainer.empty().hide(); // Clear existing filters

            // Show/hide dimension selector based on analysis type
            if (analysisId === 'sales-cube') {
                $('#dimension-selector').show();
            } else {
                $('#dimension-selector').hide();
            }

            try {
                let endpoint = `/api/olap/${analysis.id.replace(/_/g, '-')}`;
                let params = new URLSearchParams();

                // Existing filter handling (drilldown, slice, dice)
                if (analysis.filters) {
                    buildFilters(analysis.id, analysis.filters); // Rebuild filters for existing analyses
                    const filterValues = getFilterValues(analysis.filters);
                    
                    if (analysis.id === 'drilldown' || analysis.id === 'slice') {
                         endpoint = `/api/olap/${analysis.id}/${encodeURIComponent(Object.values(filterValues)[0])}`;
                    } else if (analysis.id === 'dice-performance') {
                        params = new URLSearchParams(filterValues); // Use params for dice
                        endpoint = `/api/olap/dice?${params.toString()}`;
                    }
                }

                // Handle Sales Cube specific dimension selection
                if (analysisId === 'sales-cube') {
                    const selectedDimensions = [];
                    $('#dimension-selector input[type="checkbox"]:checked').each(function() {
                        selectedDimensions.push($(this).val());
                    });
                    selectedDimensions.forEach(dim => params.append('dimensions[]', dim)); // Append as array
                    endpoint = `/api/olap/sales-cube?${params.toString()}`; // Update endpoint for sales cube
                }
                
                // Fetch and render data
                const response = await fetch(endpoint);
                if (!response.ok) throw new Error(`Network response was not ok (Status: ${response.status})`);
                const data = await response.json();
                
                renderData(analysis.id, data);
                
            } catch (error) {
                showError(`Failed to load data for "${analysis.title}". Please check the console and ensure the backend API is running.`);
                console.error("Fetch Error:", error);
            } finally {
                showLoading(false);
            }
        }

        $('#apply-dimensions-btn').on('click', () => loadAnalysis('sales-cube'));
        
        /**
         * Builds and displays the filter inputs for an analysis.
         * @param {string} analysisId - ID to link the "Apply" button.
         * @param {Array} filters - Array of filter configuration objects.
         */
        function buildFilters(analysisId, filters) {
            let filterHtml = '<div class="flex flex-wrap gap-4 items-end">';
            filters.forEach(filter => {
                filterHtml += `
                    <div>
                        <label for="filter-${filter.id}" class="block text-sm font-medium text-gray-700 mb-1">${filter.label}</label>
                        <input type="${filter.type}" id="filter-${filter.id}" value="${filter.defaultValue}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                `;
            });
            filterHtml += `
                <div>
                    <button id="apply-filters-btn" class="bg-indigo-600 text-white font-semibold px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Apply</button>
                </div>
            `;
            filterHtml += '</div>';

            $filtersContainer.html(filterHtml).show();
            $('#apply-filters-btn').on('click', () => loadAnalysis(analysisId));
        }

        /**
         * Gets the current values from the displayed filter inputs.
         * @param {Array} filters - The filter configuration objects.
         * @returns {Object} An object mapping filter IDs to their values.
         */
        function getFilterValues(filters) {
            const values = {};
            filters.forEach(filter => {
                values[filter.id] = $(`#filter-${filter.id}`).val();
            });
            return values;
        }

        /**
         * Renders the fetched data into a table or chart.
         * @param {string} analysisId - The ID of the analysis.
         * @param {Array} data - The data array from the API.
         */
        function renderData(analysisId, data) {
            $resultsContainer.empty();
            if (!data || data.length === 0) {
                $resultsContainer.html('<p class="text-center text-gray-500">No data found for this analysis.</p>');
                return;
            }
            
            // Create a table for the data
            const tableId = `table-${analysisId}`;
            const table = $(`<table id="${tableId}" class="display w-full"></table>`);
            $resultsContainer.append(table);

            // Dynamically generate columns from the first data object
            const columns = Object.keys(data[0]).map(key => ({
                title: key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()), // Format title
                data: key,
                // Custom rendering based on column name
                render: function(data, type, row) {
                    if (type === 'display') {
                        if (key.toLowerCase().includes('revenue') || key.toLowerCase().includes('price')) return formatCurrency(data);
                        if (key.toLowerCase().includes('unit') || key.toLowerCase().includes('total')) return formatNumber(data);
                        if (key.toLowerCase().includes('pct') || key.toLowerCase().includes('share') || key.toLowerCase().includes('growth')) return formatPercentage(data);
                    }
                    return data;
                }
            }));

            // Initialize DataTable
            $(`#${tableId}`).DataTable({
                data: data,
                columns: columns,
                responsive: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                language: { search: "", searchPlaceholder: "Search records..." }
            });
        }
        
        // --- UI HELPER FUNCTIONS ---
        
        function showLoading(isLoading) {
            $loader.toggleClass('hidden', !isLoading);
            $initialMessage.toggleClass('hidden', isLoading);
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