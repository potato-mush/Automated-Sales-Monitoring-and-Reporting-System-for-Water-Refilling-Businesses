@extends('layouts.admin')

@section('title', 'Print QR Codes')

@push('styles')
<style>
    @media print {
        /* Hide navigation and UI elements */
        .no-print,
        .navbar,
        .sidebar,
        nav {
            display: none !important;
        }
        
        /* Make main content full width */
        main {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .container-fluid,
        .row {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .print-page {
            page-break-after: always;
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        .qr-grid {
            margin: 0;
            padding: 20px;
        }
    }
    
    .qr-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        padding: 20px;
    }
    
    .qr-item {
        border: 2px dashed #ddd;
        padding: 15px;
        text-align: center;
        background: white;
        page-break-inside: avoid;
    }
    
    .qr-item canvas {
        max-width: 100%;
        height: auto;
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
    
    .qr-label {
        margin-top: 8px;
        font-weight: bold;
        font-size: 14px;
        font-family: monospace;
   }
    
    .qr-details {
        font-size: 11px;
        color: #666;
        margin-top: 4px;
    }
    
    @page {
        size: letter;
        margin: 0.5in;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
    <h1 class="h2">Print Gallon QR Codes</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-outline-secondary me-2" onclick="showPrintPreview()" id="previewBtn" style="display:none;">
            <i class="bi bi-eye me-2"></i>Preview
        </button>
        <button type="button" class="btn btn-primary" onclick="handlePrint()" id="printBtn" style="display:none;">
            <i class="bi bi-printer me-2"></i>Print
        </button>
    </div>
</div>

<!-- Generation Controls -->
<div class="card mb-4 no-print">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>QR Code Generation Settings</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Start Number</label>
                <input type="number" id="startNumber" class="form-control" value="1" min="1" readonly>
                <small class="text-muted">Auto-detected from database</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Number of QR Codes</label>
                <input type="number" id="qrCount" class="form-control" value="28" min="1" max="100">
                <small class="text-muted">Max 100 per batch</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Prefix</label>
                <input type="text" id="prefix" class="form-control" value="WR-GAL-" maxlength="15" readonly>
                <small class="text-muted">Fixed prefix format</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-success w-100" onclick="generateQRCodes()">
                    <i class="bi bi-arrow-repeat me-2"></i>Generate Preview
                </button>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Range:</strong> <span id="rangePreview">Click Generate to see preview</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Message -->
<div id="loadingMessage" class="alert alert-info no-print" style="display: none;">
    <i class="bi bi-hourglass-split me-2"></i>Generating QR codes, please wait...
</div>

<!-- QR Codes Grid -->
<div id="qrContainer">
    <div class="alert alert-info no-print" id="initialMessage">
        <div class="spinner-border spinner-border-sm me-2" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        Loading QR code library, please wait...
    </div>
</div>
@endsection

@push('scripts')
<!-- Use local QRCode library -->
<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script>
    // Verify QRCode library loaded
    window.addEventListener('load', function() {
        if (typeof QRCode !== 'undefined') {
            console.log('✅ QRCode library loaded from local file');
        } else {
            console.error('❌ QRCode library failed to load');
        }
    });

    // Store generated QR code parameters for later saving
    let generatedQRParams = null;
    let qrCodesSaved = false;

    // Load next available gallon number on page load
    document.addEventListener('DOMContentLoaded', async function() {
        const initialMsg = document.getElementById('initialMessage');
        
        // Wait a bit for library to load
        await new Promise(resolve => setTimeout(resolve, 100));
        
        if (typeof QRCode === 'undefined') {
            console.error('QRCode library not available');
            if (initialMsg) {
                initialMsg.className = 'alert alert-danger no-print';
                initialMsg.innerHTML = `
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Error:</strong> QRCode library could not be loaded.
                    <br><small>Please contact support if this persists.</small>
                `;
            }
            return;
        }
        
        console.log('✅ QRCode library ready');
        
        // Update initial message to show ready state
        if (initialMsg) {
            initialMsg.className = 'alert alert-success no-print';
            initialMsg.innerHTML = `
                <i class="bi bi-check-circle me-2"></i>
                <strong>Ready!</strong> Click "Generate Preview" above to create QR codes.
                <br><small>Default layout: 4 columns × 7 rows (28 QR codes per page)</small>
            `;
        }
        
        await loadNextGallonNumber();
        
        // Update preview when count changes
        const qrCountInput = document.getElementById('qrCount');
        if (qrCountInput) {
            qrCountInput.addEventListener('input', updateRangePreview);
        }
    });

    async function loadNextGallonNumber() {
        try {
            const response = await fetch(`${API_BASE_URL}/gallons?per_page=1`, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                const gallons = data.data || [];
                
                if (gallons.length > 0) {
                    // Extract the last gallon code and calculate next number
                    const lastCode = gallons[0].gallon_code;
                    const match = lastCode.match(/\d+$/);
                    
                    if (match) {
                        const lastNumber = parseInt(match[0]);
                        const nextNumber = lastNumber + 1;
                        document.getElementById('startNumber').value = nextNumber;
                        updateRangePreview();
                    }
                } else {
                    document.getElementById('startNumber').value = 1;
                    updateRangePreview();
                }
            }
        } catch (error) {
            console.error('Error loading last gallon:', error);
            document.getElementById('startNumber').value = 1;
            updateRangePreview();
        }
    }

    function updateRangePreview() {
        const startNumber = parseInt(document.getElementById('startNumber').value);
        const qrCount = parseInt(document.getElementById('qrCount').value);
        const prefix = document.getElementById('prefix').value;
        const endNumber = startNumber + qrCount - 1;
        
        const startCode = `${prefix}${String(startNumber).padStart(4, '0')}`;
        const endCode = `${prefix}${String(endNumber).padStart(4, '0')}`;
        
        document.getElementById('rangePreview').textContent = `${startCode} to ${endCode} (${qrCount} codes)`;
    }

    async function handlePrint() {
        // Save to database before printing
        if (generatedQRParams && !qrCodesSaved) {
            const { startNumber, qrCount, prefix } = generatedQRParams;
            
            const saveBtn = document.getElementById('printBtn');
            const originalHtml = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            
            try {
                await saveGallonCodesToBackend(startNumber, qrCount, prefix);
                qrCodesSaved = true;
                
                // Update start number for next batch after successful save
                const nextNumber = startNumber + qrCount;
                document.getElementById('startNumber').value = nextNumber;
                updateRangePreview();
                
                showSuccessMessage('QR codes saved to database successfully!');
                
                // Wait a moment for the success message, then print
                setTimeout(() => {
                    window.print();
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalHtml;
                }, 500);
            } catch (error) {
                console.error('Error saving QR codes:', error);
                alert('Failed to save QR codes to database. Print cancelled.');
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalHtml;
            }
        } else if (qrCodesSaved) {
            // Already saved, just print
            window.print();
        } else {
            alert('Please generate QR codes first.');
        }
    }

    function showPrintPreview() {
        const previewWindow = window.open('', '_blank', 'width=800,height=600');
        const content = document.getElementById('qrContainer').innerHTML;
        
        previewWindow.document.write(`
            <html>
            <head>
                <title>QR Codes Print Preview</title>
                <style>
                    body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
                    .qr-grid {
                        display: grid;
                        grid-template-columns: repeat(4, 1fr);
                        gap: 15px;
                        padding: 20px;
                    }
                    .qr-item {
                        border: 2px dashed #ddd;
                        padding: 15px;
                        text-align: center;
                        background: white;
                        page-break-inside: avoid;
                    }
                    .qr-container {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        margin: 0 auto;
                    }
                    .qr-item canvas,
                    .qr-item img,
                    .qr-container canvas,
                    .qr-container img {
                        max-width: 100%;
                        height: auto !important;
                        display: block;
                    }
                    .qr-label {
                        margin-top: 8px;
                        font-weight: bold;
                        font-size: 14px;
                        font-family: monospace;
                    }
                    .qr-details {
                        font-size: 11px;
                        color: #666;
                        margin-top: 4px;
                    }
                    .print-page {
                        page-break-after: always;
                    }
                    @media print {
                        @page { size: letter; margin: 0.5in; }
                    }
                    .no-print { display: none; }
                </style>
            </head>
            <body>
                ${content}
                <div style="margin-top: 20px; text-align: center; padding: 20px;">
                    <button onclick="if(window.opener && window.opener.handlePrint) { window.opener.handlePrint(); window.close(); } else { window.print(); }" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
                        Print Now
                    </button>
                    <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; margin-left: 10px;">
                        Close
                    </button>
                </div>
            </body>
            </html>
        `);
        previewWindow.document.close();
    }

    async function generateQRCodes() {
        try {
            // Check if QRCode library is loaded
            if (typeof QRCode === 'undefined') {
                alert('⚠️ QRCode library is not available.\n\nPlease refresh the page (Ctrl+F5) and ensure you have an internet connection.');
                return;
            }

            // Reset save status when generating new codes
            qrCodesSaved = false;
            generatedQRParams = null;

            const startNumber = parseInt(document.getElementById('startNumber').value);
            const qrCount = parseInt(document.getElementById('qrCount').value);
            const prefix = document.getElementById('prefix').value.trim();

            if (qrCount < 1 || qrCount > 100) {
                alert('Please enter a number between 1 and 100');
                return;
            }

            console.log(`Generating ${qrCount} QR codes starting from ${prefix}${String(startNumber).padStart(4, '0')}`);

            document.getElementById('loadingMessage').style.display = 'block';
            document.getElementById('qrContainer').innerHTML = '';

            const qrPerPage = 28;
            const totalPages = Math.ceil(qrCount / qrPerPage);

            for (let page = 0; page < totalPages; page++) {
                const pageDiv = document.createElement('div');
                pageDiv.className = 'print-page';
                
                const gridDiv = document.createElement('div');
                gridDiv.className = 'qr-grid';

                const startIdx = page * qrPerPage;
                const endIdx = Math.min(startIdx + qrPerPage, qrCount);

                for (let i = startIdx; i < endIdx; i++) {
                    const gallonNumber = startNumber + i;
                    const gallonCode = `${prefix}${String(gallonNumber).padStart(4, '0')}`;  // Changed to 4 digits
                    
                    const qrItem = document.createElement('div');
                    qrItem.className = 'qr-item';
                    
                    // Create container for QR code
                  const qrContainer = document.createElement('div');
                    qrContainer.className = 'qr-container';
                    qrContainer.style.width = '150px';
                    qrContainer.style.height = '150px';
                    qrContainer.style.margin = '0 auto';
                    
                    const label = document.createElement('div');
                    label.className = 'qr-label';
                    label.textContent = gallonCode;
                    
                    const details = document.createElement('div');
                    details.className = 'qr-details';
                    details.textContent = 'Water Refilling Station';
                    
                    qrItem.appendChild(qrContainer);
                    qrItem.appendChild(label);
                    qrItem.appendChild(details);
                    
                    gridDiv.appendChild(qrItem);
                    
                    const qrData = JSON.stringify({
                        code: gallonCode,
                        type: 'gallon',
                        station: 'Water Refilling Station',
                        generated: new Date().toISOString().split('T')[0]
                    });
                    
                    // Create QR code using qrcodejs library
                    new QRCode(qrContainer, {
                        text: qrData,
                        width: 150,
                        height: 150,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }

                pageDiv.appendChild(gridDiv);
                document.getElementById('qrContainer').appendChild(pageDiv);
            }

            document.getElementById('loadingMessage').style.display = 'none';
            
            // Store parameters for saving when print is executed
            generatedQRParams = { startNumber, qrCount, prefix };
            qrCodesSaved = false;
            
            // Show print and preview buttons
            document.getElementById('previewBtn').style.display = 'inline-block';
            document.getElementById('printBtn').style.display = 'inline-block';
            
            showSuccessMessage(`Successfully generated ${qrCount} QR codes (${totalPages} page${totalPages > 1 ? 's' : ''}). Click Print to save and print!`);
        } catch (error) {
            console.error('Error generating QR codes:', error);
            document.getElementById('loadingMessage').style.display = 'none';
            
            let errorMessage = 'Error generating QR codes: ';
            if (error.message) {
                errorMessage += error.message;
            } else {
                errorMessage += 'Please try again.';
            }
            
            alert(errorMessage);
        }
    }

    async function saveGallonCodesToBackend(startNumber, count, prefix) {
        const gallons = [];
        
        for (let i = 0; i < count; i++) {
            const gallonNumber = startNumber + i;
            const gallonCode = `${prefix}${String(gallonNumber).padStart(4, '0')}`;  // Changed to 4 digits
            gallons.push({
                gallon_code: gallonCode,
                status: 'IN'
            });
        }

        try {
            const response = await fetch(`${API_BASE_URL}/gallons/bulk-create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ gallons })
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Failed to save gallons to database');
            }
            
            const result = await response.json();
            console.log('Successfully saved gallons to database:', result);
            return result;
        } catch (error) {
            console.error('Error saving gallons to backend:', error);
            throw error;
        }
    }

    function showSuccessMessage(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show no-print';
        alertDiv.innerHTML = `
            <i class="bi bi-check-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.getElementById('qrContainer');
        container.insertBefore(alertDiv, container.firstChild);
    }
</script>
@endpush

