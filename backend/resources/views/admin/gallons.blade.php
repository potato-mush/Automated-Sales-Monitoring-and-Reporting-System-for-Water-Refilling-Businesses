@extends('layouts.admin')

@section('title', 'Gallons Management')

@push('styles')
<style>
    @media print {
        /* Hide everything except QR modal content */
        body * {
            visibility: hidden;
        }
        
        #qrModalContent,
        #qrModalContent * {
            visibility: visible;
        }
        
        /* Position QR code at top-left */
        #qrModalContent {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: auto !important;
            transform: none !important;
            page-break-after: avoid !important;
            page-break-before: avoid !important;
            page-break-inside: avoid !important;
        }
        
        body,
        html {
            height: 100vh !important;
            overflow: hidden !important;
        }
        
        .modal,
        .modal-dialog,
        .modal-content,
        .modal-body {
            position: static !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
            height: auto !important;
            overflow: hidden !important;
        }
        
        .modal-header,
        .modal-footer,
        .btn-close {
            display: none !important;
        }
        
        .qr-print-item {
            page-break-after: avoid !important;
            page-break-before: avoid !important;
        }
        
        @page {
            size: letter;
            margin: 0.5in;
        }
    }
    
    /* Match bulk print QR item styling */
    .qr-print-item {
        border: 2px dashed #ddd;
        padding: 15px;
        text-align: center;
        background: white;
        display: inline-block;
        width: auto;
        page-break-inside: avoid;
    }
    
    .qr-container {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .qr-container canvas,
    .qr-container img {
        max-width: 100%;
        height: auto !important;
        display: block;
    }
    
    #qrDisplay {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    #qrDisplay canvas,
    #qrDisplay img {
        max-width: 100%;
        height: auto !important;
        display: block;
    }
    
    .qr-code-label {
        margin-top: 8px;
        font-weight: bold;
        font-size: 14px;
        font-family: monospace;
        text-align: center;
    }
    
    .qr-code-details {
        font-size: 11px;
        color: #666;
        margin-top: 4px;
        text-align: center;
    }
    
    /* Center modal content */
    .modal-body {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 30px;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gallons Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-sm btn-outline-secondary me-2" onclick="loadGallons()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" id="filterStatus" onchange="loadGallons()">
                    <option value="">All Status</option>
                    <option value="IN">In Station</option>
                    <option value="OUT">Out</option>
                    <option value="MISSING">Missing</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Gallons Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>QR Code</th>
                        <th>Status</th>
                        <th>Customer</th>
                        <th>Borrowed Date</th>
                        <th>Return Date</th>
                        <th>Days Out</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="gallonsTable">
                    <tr>
                        <td colspan="7" class="text-center">Loading...</td>
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

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gallon QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="qrModalContent">
                <div class="qr-print-item">
                    <div id="qrDisplay"></div>
                    <div class="qr-code-label" id="qrCodeLabel"></div>
                    <div class="qr-code-details">Water Refilling Station</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printQRCode()">
                    <i class="bi bi-printer me-2"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Load QRCode library -->
<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script>
    let currentPage = 1;
    let qrModalInstance = null;
    let currentGallonCode = null;

    function formatDate(dateString) {
        if (!dateString) return '-';
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    }

    async function loadGallons(page = 1) {
        const status = document.getElementById('filterStatus').value;
        
        let url = `${API_BASE_URL}/gallons?page=${page}`;
        if (status) url += `&status=${status}`;

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
                console.error('Gallons API error:', await response.text());
                throw new Error('Failed to load gallons');
            }

            const data = await response.json();
            console.log('Gallons loaded:', data);
            displayGallons(data.data || data);
            updatePagination(data);
            currentPage = page;
        } catch (error) {
            console.error('Error loading gallons:', error);
            document.getElementById('gallonsTable').innerHTML = 
                '<tr><td colspan="7" class="text-center text-danger">Error loading gallons</td></tr>';
        }
    }

    function displayGallons(gallons) {
        const tbody = document.getElementById('gallonsTable');
        
        if (gallons.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No gallons found</td></tr>';
            return;
        }

        tbody.innerHTML = gallons.map(g => {
            let statusBadge = '';
            if (g.status === 'IN') {
                statusBadge = '<span class="badge bg-success">In Station</span>';
            } else if (g.status === 'OUT') {
                statusBadge = '<span class="badge bg-warning">Out</span>';
            } else if (g.status === 'MISSING') {
                statusBadge = '<span class="badge bg-danger">Missing</span>';
            }

            const daysOut = g.overdue_days || '-';
            
            return `
                <tr>
                    <td><code>${g.gallon_code}</code></td>
                    <td>${statusBadge}</td>
                    <td>${g.last_transaction && g.last_transaction.customer_name ? g.last_transaction.customer_name : '-'}</td>
                    <td>${formatDate(g.last_borrowed_date)}</td>
                    <td>${formatDate(g.last_returned_date)}</td>
                    <td>${daysOut}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="showGallonQR('${g.gallon_code}')" title="Show QR Code">
                            <i class="bi bi-qr-code"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updatePagination(data) {
        const pagination = document.getElementById('pagination');
        let html = '';

        if (data.prev_page_url) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="loadGallons(${currentPage - 1}); return false;">Previous</a></li>`;
        }

        for (let i = 1; i <= data.last_page; i++) {
            if (i === data.current_page) {
                html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadGallons(${i}); return false;">${i}</a></li>`;
            }
        }

        if (data.next_page_url) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="loadGallons(${currentPage + 1}); return false;">Next</a></li>`;
        }

        pagination.innerHTML = html;
    }

    function showGallonQR(gallonCode) {
        currentGallonCode = gallonCode;
        
        // Clear previous QR code
        const qrDisplay = document.getElementById('qrDisplay');
        qrDisplay.innerHTML = '';
        
        // Set label
        document.getElementById('qrCodeLabel').textContent = gallonCode;
        
        // Create container for QR code (matching bulk print size)
        const qrContainer = document.createElement('div');
        qrContainer.className = 'qr-container';
        qrContainer.style.width = '150px';
        qrContainer.style.height = '150px';
        qrContainer.style.margin = '0 auto';
        qrDisplay.appendChild(qrContainer);
        
        // Generate QR code data
        const qrData = JSON.stringify({
            code: gallonCode,
            type: 'gallon',
            station: 'Water Refilling Station',
            generated: new Date().toISOString().split('T')[0]
        });
        
        // Create QR code (matching bulk print size)
        try {
            new QRCode(qrContainer, {
                text: qrData,
                width: 150,
                height: 150,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
            
            // Show modal
            const modalEl = document.getElementById('qrModal');
            if (!qrModalInstance) {
                qrModalInstance = new bootstrap.Modal(modalEl);
            }
            qrModalInstance.show();
        } catch (error) {
            console.error('Error generating QR code:', error);
            alert('Failed to generate QR code. Please refresh the page.');
        }
    }
    
    function printQRCode() {
        window.print();
    }

    function viewGallon(qrCode) {
        showGallonQR(qrCode);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Verify QRCode library is loaded
        if (typeof QRCode === 'undefined') {
            console.error('QRCode library not loaded');
        }
        
        loadGallons();
    });
</script>
@endpush
