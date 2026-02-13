@extends('layouts.admin')

@section('title', 'Dashboard')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-sm btn-outline-secondary" onclick="loadDashboard()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <i class="bi bi-receipt icon float-end"></i>
                <div class="label">Today's Transactions</div>
                <div class="value" id="todayTransactions">0</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <i class="bi bi-currency-dollar icon float-end"></i>
                <div class="label">Today's Revenue</div>
                <div class="value" id="todayRevenue">₱0.00</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <i class="bi bi-droplet icon float-end"></i>
                <div class="label">Gallons Sold</div>
                <div class="value" id="todayGallons">0</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <i class="bi bi-exclamation-triangle icon float-end"></i>
                <div class="label">Overdue Gallons</div>
                <div class="value" id="overdueGallons">0</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sales Trend</h5>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary active" id="btnDaily" onclick="changePeriod('daily')">Daily</button>
                    <button type="button" class="btn btn-outline-primary" id="btnWeekly" onclick="changePeriod('weekly')">Weekly</button>
                    <button type="button" class="btn btn-outline-primary" id="btnMonthly" onclick="changePeriod('month')">Monthly</button>
                    <button type="button" class="btn btn-outline-primary" id="btnYearly" onclick="changePeriod('year')">Yearly</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Transaction Types</h5>
            </div>
            <div class="card-body">
                <canvas id="typeChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Gallon Status & Recent Transactions -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Gallon Inventory</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>In Station</span>
                        <strong id="gallonsIn">0</strong>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-success" id="progressIn" style="width: 0%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Out</span>
                        <strong id="gallonsOut">0</strong>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-warning" id="progressOut" style="width: 0%"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Missing</span>
                        <strong id="gallonsMissing">0</strong>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-danger" id="progressMissing" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Transactions</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody id="recentTransactions">
                            <tr>
                                <td colspan="5" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let salesChart, typeChart;
    let currentPeriod = 'daily'; // default period

    // Format currency
    function formatCurrency(amount) {
        return '₱' + parseFloat(amount).toFixed(2);
    }

    // Format datetime
    function formatDateTime(dateString) {
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(dateString).toLocaleString('en-US', options);
    }

    // Change chart period
    function changePeriod(period) {
        currentPeriod = period;
        
        // Update button states
        document.getElementById('btnDaily').classList.remove('active');
        document.getElementById('btnWeekly').classList.remove('active');
        document.getElementById('btnMonthly').classList.remove('active');
        document.getElementById('btnYearly').classList.remove('active');
        
        if (period === 'daily') {
            document.getElementById('btnDaily').classList.add('active');
        } else if (period === 'weekly') {
            document.getElementById('btnWeekly').classList.add('active');
        } else if (period === 'month') {
            document.getElementById('btnMonthly').classList.add('active');
        } else if (period === 'year') {
            document.getElementById('btnYearly').classList.add('active');
        }
        
        // Reload chart with new period
        loadSalesChart();
    }

    async function loadDashboard() {
        try {
            const response = await fetch(API_BASE_URL + '/dashboard', {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Dashboard API error:', errorText);
                throw new Error(`Failed to load dashboard data: ${response.status}`);
            }

            const data = await response.json();
            console.log('Dashboard data loaded:', data);

            // Update stats cards
            document.getElementById('todayTransactions').textContent = data.today.transactions;
            document.getElementById('todayRevenue').textContent = formatCurrency(data.today.revenue);
            document.getElementById('todayGallons').textContent = data.today.gallons_sold;
            document.getElementById('overdueGallons').textContent = data.gallons.overdue;

            // Update gallon inventory
            const total = data.gallons.total;
            document.getElementById('gallonsIn').textContent = data.gallons.in_station;
            document.getElementById('gallonsOut').textContent = data.gallons.out;
            document.getElementById('gallonsMissing').textContent = data.gallons.missing;
            
            document.getElementById('progressIn').style.width = (data.gallons.in_station / total * 100) + '%';
            document.getElementById('progressOut').style.width = (data.gallons.out / total * 100) + '%';
            document.getElementById('progressMissing').style.width = (data.gallons.missing / total * 100) + '%';

            // Update recent transactions
            updateRecentTransactions(data.recent_transactions);

            // Load charts
            await loadSalesChart();
            await loadTypeChart();
        } catch (error) {
            console.error('Error loading dashboard:', error);
            document.getElementById('recentTransactions').innerHTML = 
                '<tr><td colspan="5" class="text-center text-danger">Error loading data. Please refresh the page.</td></tr>';
        }
    }

    function updateRecentTransactions(transactions) {
        const tbody = document.getElementById('recentTransactions');
        
        if (transactions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No transactions yet</td></tr>';
            return;
        }

        tbody.innerHTML = transactions.map(t => `
            <tr>
                <td><small>${t.transaction_code}</small></td>
                <td>${t.customer_name}</td>
                <td><span class="badge bg-primary">${t.transaction_type}</span></td>
                <td><strong>${formatCurrency(t.total_amount)}</strong></td>
                <td><small>${formatDateTime(t.created_at)}</small></td>
            </tr>
        `).join('');
    }

    async function loadSalesChart() {
        try {
            // Sales Chart
            const salesResponse = await fetch(API_BASE_URL + `/dashboard/sales-chart?period=${currentPeriod}`, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });
            
            if (!salesResponse.ok) {
                console.error('Sales chart API error:', await salesResponse.text());
                return;
            }
            
            const salesData = await salesResponse.json();

            const salesCtx = document.getElementById('salesChart').getContext('2d');
            
            if (salesChart) salesChart.destroy();
            
            salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: salesData.map(d => d.label),
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: salesData.map(d => d.revenue),
                        borderColor: '#1E88E5',
                        backgroundColor: 'rgba(30, 136, 229, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error loading sales chart:', error);
        }
    }

    async function loadTypeChart() {
        try {
            const typeResponse = await fetch(API_BASE_URL + '/dashboard/transaction-type-breakdown?period=today', {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });
            
            if (!typeResponse.ok) {
                console.error('Type chart API error:', await typeResponse.text());
                return;
            }
            
            const typeData = await typeResponse.json();

            const typeCtx = document.getElementById('typeChart').getContext('2d');
            
            if (typeChart) typeChart.destroy();
            
            typeChart = new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Walk-in', 'Delivery', 'Refill Only'],
                    datasets: [{
                        data: [
                            typeData.walk_in.count,
                            typeData.delivery.count,
                            typeData.refill_only.count
                        ],
                        backgroundColor: ['#1E88E5', '#FFA726', '#66BB6A']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error loading type chart:', error);
        }
    }

    // Load dashboard on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadDashboard();
        
        // Auto refresh every 30 seconds
        setInterval(loadDashboard, 30000);
    });
</script>
@endpush
