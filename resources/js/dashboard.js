$(document).ready(function() {
    // Init datatable
    $('#salesTable').DataTable();

    // Load chart data via AJAX
    fetch('/api/revenue-summary')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: data.datasets
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Revenue Summary'
                        }
                    }
                },
            });
        });
});
