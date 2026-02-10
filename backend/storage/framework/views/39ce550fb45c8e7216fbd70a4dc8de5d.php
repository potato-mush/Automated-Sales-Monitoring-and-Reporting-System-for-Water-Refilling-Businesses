

<?php $__env->startSection('title', 'Reports'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reports</h1>
</div>

<!-- Report Selection -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-calendar-day me-2"></i>Daily Report</h5>
                <p class="card-text">View sales summary for a specific day</p>
                <input type="date" class="form-control mb-2" id="dailyDate" value="">
                <button class="btn btn-primary w-100" onclick="generateDailyReport()">
                    Generate Report
                </button>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-calendar-week me-2"></i>Weekly Report</h5>
                <p class="card-text">View current week's sales summary</p>
                <button class="btn btn-primary w-100" onclick="generateWeeklyReport()">
                    Generate Report
                </button>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-calendar-month me-2"></i>Monthly Report</h5>
                <p class="card-text">View monthly sales summary</p>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <select class="form-select" id="monthSelect">
                            <?php for($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo e($m); ?>"><?php echo e(date('F', mktime(0, 0, 0, $m, 1))); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <select class="form-select" id="yearSelect">
                            <?php for($y = date('Y'); $y >= 2024; $y--): ?>
                                <option value="<?php echo e($y); ?>"><?php echo e($y); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary w-100" onclick="generateMonthlyReport()">
                    Generate Report
                </button>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-calendar-range me-2"></i>Yearly Report</h5>
                <p class="card-text">View annual sales summary</p>
                <select class="form-select mb-2" id="yearlyYearSelect">
                    <?php for($y = date('Y'); $y >= 2024; $y--): ?>
                        <option value="<?php echo e($y); ?>"><?php echo e($y); ?></option>
                    <?php endfor; ?>
                </select>
                <button class="btn btn-primary w-100" onclick="generateYearlyReport()">
                    Generate Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Report Display -->
<div class="card" id="reportCard" style="display: none;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0" id="reportTitle">Report</h5>
        <button class="btn btn-sm btn-success" onclick="printReport()">
            <i class="bi bi-printer me-1"></i>Print
        </button>
    </div>
    <div class="card-body" id="reportContent"></div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    @media print {
        .navbar, .sidebar, .btn, .no-print, .border-bottom {
            display: none !important;
        }
        .col-md-10 {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    function formatCurrency(amount) {
        return '₱' + parseFloat(amount).toFixed(2);
    }

    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('dailyDate').value = new Date().toISOString().split('T')[0];
        const now = new Date();
        document.getElementById('monthSelect').value = now.getMonth() + 1;
        document.getElementById('yearSelect').value = now.getFullYear();
        document.getElementById('yearlyYearSelect').value = now.getFullYear();
    });

    async function generateDailyReport() {
        const date = document.getElementById('dailyDate').value;
        if (!date) { alert('Please select a date'); return; }
        try {
            const response = await fetch(`${API_BASE_URL}/dashboard/daily-report?date=${date}`, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }, 
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Failed to generate report');
            const data = await response.json();
            displayReport('Daily Report - ' + formatDate(date), data, 'daily');
        } catch (error) { 
            console.error('Error:', error); 
            alert('Failed to generate daily report. Please try again.');
        }
    }

    async function generateWeeklyReport() {
        try {
            const response = await fetch(`${API_BASE_URL}/dashboard/weekly-report`, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }, 
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Failed to generate report');
            const data = await response.json();
            displayReport('Weekly Report - ' + formatDate(data.start_date) + ' to ' + formatDate(data.end_date), data, 'weekly');
        } catch (error) { 
            console.error('Error:', error); 
            alert('Failed to generate weekly report. Please try again.');
        }
    }

    async function generateMonthlyReport() {
        const month = document.getElementById('monthSelect').value;
        const year = document.getElementById('yearSelect').value;
        try {
            const response = await fetch(`${API_BASE_URL}/dashboard/monthly-report?month=${month}&year=${year}`, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }, 
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Failed to generate report');
            const data = await response.json();
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July',  'August', 'September', 'October', 'November', 'December'];
            displayReport(`Monthly Report - ${monthNames[month - 1]} ${year}`, data, 'monthly');
        } catch (error) { 
            console.error('Error:', error); 
            alert('Failed to generate monthly report. Please try again.');
        }
    }

    async function generateYearlyReport() {
        const year = document.getElementById('yearlyYearSelect').value;
        try {
            const response = await fetch(`${API_BASE_URL}/dashboard/yearly-report?year=${year}`, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }, 
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Failed to generate report');
            const data = await response.json();
            displayReport(`Yearly Report - ${year}`, data, 'yearly');
        } catch (error) { 
            console.error('Error:', error); 
            alert('Failed to generate yearly report. Please try again.');
        }
    }

    function displayReport(title, data, type) {
        document.getElementById('reportTitle').textContent = title;
        
        const avgLabel = type === 'yearly' ? 'Avg Monthly Revenue' : 
                        type === 'monthly' ? 'Avg Daily Revenue' :
                        type === 'weekly' ? 'Avg Daily Revenue' : 'Total Revenue';
        const avgValue = type === 'yearly' ? data.average_monthly_revenue :
                        type === 'monthly' ? data.average_daily_revenue :
                        type === 'weekly' ? data.average_daily_revenue :
                        data.total_revenue;
        
        const content = `
            <div class="row mb-4">
                <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body text-center"><h6>Total Transactions</h6><h3>${data.total_transactions}</h3></div></div></div>
                <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body text-center"><h6>Total Revenue</h6><h3>${formatCurrency(data.total_revenue)}</h3></div></div></div>
                <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body text-center"><h6>Total Gallons</h6><h3>${data.total_gallons}</h3></div></div></div>
                <div class="col-md-3"><div class="card bg-warning text-white"><div class="card-body text-center"><h6>${avgLabel}</h6><h3>${formatCurrency(avgValue)}</h3></div></div></div>
            </div>
            <h5 class="mb-3">Breakdown by Transaction Type</h5>
            <table class="table table-bordered">
                <thead><tr><th>Type</th><th>Transactions</th><th>Revenue</th><th>Percentage</th></tr></thead>
                <tbody>
                    <tr><td>Walk-in</td><td>${data.by_type.walk_in.count}</td><td>${formatCurrency(data.by_type.walk_in.revenue)}</td><td>${((data.by_type.walk_in.revenue / data.total_revenue) * 100).toFixed(1)}%</td></tr>
                    <tr><td>Delivery</td><td>${data.by_type.delivery.count}</td><td>${formatCurrency(data.by_type.delivery.revenue)}</td><td>${((data.by_type.delivery.revenue / data.total_revenue) * 100).toFixed(1)}%</td></tr>
                    <tr><td>Refill Only</td><td>${data.by_type.refill_only.count}</td><td>${formatCurrency(data.by_type.refill_only.revenue)}</td><td>${((data.by_type.refill_only.revenue / data.total_revenue) * 100).toFixed(1)}%</td></tr>
                    <tr class="table-primary"><th>Total</th><th>${data.total_transactions}</th><th>${formatCurrency(data.total_revenue)}</th><th>100%</th></tr>
                </tbody>
            </table>`;
        document.getElementById('reportContent').innerHTML = content;
        document.getElementById('reportCard').style.display = 'block';
    }

    function printReport() { window.print(); }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\DarkNight_007\Downloads\Automated-Sales-Monitoring-and-Reporting-System-for-Water-Refilling-Businesses\backend\resources\views/admin/reports.blade.php ENDPATH**/ ?>