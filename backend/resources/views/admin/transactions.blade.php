@extends('layouts.admin')

@section('title', 'Transactions')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Transactions</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-sm btn-outline-secondary" onclick="loadTransactions()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Transaction Type</label>
                <select class="form-select" id="filterType" onchange="loadTransactions()">
                    <option value="">All Types</option>
                    <option value="walk-in">Walk-in</option>
                    <option value="delivery">Delivery</option>
                    <option value="refill-only">Refill Only</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" id="filterDate" onchange="loadTransactions()">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-secondary w-100" onclick="clearFilters()">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaction Code</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Payment</th>
                        <th>Quantity</th>
                        <th>Total Amount</th>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="transactionsTable">
                    <tr>
                        <td colspan="9" class="text-center">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<nav class="mt-3">
    <ul class="pagination justify-content-center" id="pagination"></ul>
</nav>
@endsection

@push('scripts')
<script>
    let currentPage = 1;

    function formatCurrency(amount) {
        return '₱' + parseFloat(amount).toFixed(2);
    }

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

    async function loadTransactions(page = 1) {
        const type = document.getElementById('filterType').value;
        const date = document.getElementById('filterDate').value;

        let url = `${API_BASE_URL}/transactions?page=${page}`;
        if (type) url += `&type=${type}`;
        if (date) url += `&date=${date}`;

        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                console.error('Transactions API error:', await response.text());
                throw new Error('Failed to load transactions');
            }

            const data = await response.json();
            console.log('Transactions loaded:', data);
            updateTransactionsTable(data.data);
            updatePagination(data);
            currentPage = page;
        } catch (error) {
            console.error('Error loading transactions:', error);
            document.getElementById('transactionsTable').innerHTML = 
                '<tr><td colspan="9" class="text-center text-danger">Error loading transactions</td></tr>';
        }
    }

    function updateTransactionsTable(transactions) {
        const tbody = document.getElementById('transactionsTable');
        
        if (transactions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No transactions found</td></tr>';
            return;
        }

        tbody.innerHTML = transactions.map(t => `
            <tr>
                <td><small>${t.transaction_code}</small></td>
                <td>${t.customer_name}</td>
                <td><span class="badge bg-primary">${t.transaction_type}</span></td>
                <td><span class="badge bg-success">${t.payment_method}</span></td>
                <td>${t.quantity}</td>
                <td><strong>${formatCurrency(t.total_amount)}</strong></td>
                <td><small>${t.employee ? t.employee.name : 'N/A'}</small></td>
                <td><small>${formatDateTime(t.created_at)}</small></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="viewTransaction(${t.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    function updatePagination(data) {
        const pagination = document.getElementById('pagination');
        let html = '';

        if (data.prev_page_url) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="loadTransactions(${currentPage - 1}); return false;">Previous</a></li>`;
        }

        for (let i = 1; i <= data.last_page; i++) {
            if (i === data.current_page) {
                html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadTransactions(${i}); return false;">${i}</a></li>`;
            }
        }

        if (data.next_page_url) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="loadTransactions(${currentPage + 1}); return false;">Next</a></li>`;
        }

        pagination.innerHTML = html;
    }

    function clearFilters() {
        document.getElementById('filterType').value = '';
        document.getElementById('filterDate').value = '';
        loadTransactions();
    }

    function viewTransaction(id) {
        // Implement transaction details modal or redirect
        alert('View transaction details for ID: ' + id);
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadTransactions();
    });
</script>
@endpush
