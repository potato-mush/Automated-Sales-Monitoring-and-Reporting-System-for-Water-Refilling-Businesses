@extends('layouts.admin')

@section('title', 'Inventory Management')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Inventory Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-outline-secondary me-2" onclick="viewInventoryLogs()">
            <i class="bi bi-journal-text me-1"></i>View All Logs
        </button>
        <button class="btn btn-primary" onclick="showAddItemModal()">
            <i class="bi bi-plus-circle me-1"></i>Add Item
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <i class="bi bi-box-seam icon float-end"></i>
                <div class="label">Total Items</div>
                <div class="value" id="totalItems">0</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <i class="bi bi-cash icon float-end"></i>
                <div class="label">Total Value</div>
                <div class="value" id="totalValue">₱0.00</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <i class="bi bi-exclamation-triangle icon float-end"></i>
                <div class="label">Low Stock Items</div>
                <div class="value" id="lowStockItems">0</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <i class="bi bi-grid icon float-end"></i>
                <div class="label">Categories</div>
                <div class="value">4</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select class="form-select" id="filterCategory" onchange="loadInventory()">
                    <option value="all">All Categories</option>
                    <option value="caps">Gallon Caps</option>
                    <option value="seals">Seals</option>
                    <option value="purification">Purification Materials</option>
                    <option value="supplies">Other Supplies</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Stock Status</label>
                <select class="form-select" id="filterStock" onchange="loadInventory()">
                    <option value="all">All Items</option>
                    <option value="1">Low Stock Only</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" id="searchInput" placeholder="Search items..." onkeyup="loadInventory()">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Unit Price</th>
                        <th>Total Value</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryTableBody">
                    <tr>
                        <td colspan="9" class="text-center">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Inventory Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="itemForm">
                    <input type="hidden" id="itemId">
                    <div class="mb-3">
                        <label class="form-label">Item Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="itemName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="itemCategory" required>
                            <option value="">Select category...</option>
                            <option value="caps">Gallon Caps</option>
                            <option value="seals">Seals</option>
                            <option value="purification">Purification Materials</option>
                            <option value="supplies">Other Supplies</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="itemQuantity" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="itemUnit" placeholder="e.g., pcs, kg, liters" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit Price (₱) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="itemPrice" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reorder Level <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="itemReorderLevel" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="itemDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveItem()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Adjust Quantity Modal -->
<div class="modal fade" id="adjustQuantityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Quantity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="adjustItemId">
                <p><strong id="adjustItemName"></strong></p>
                <p>Current Quantity: <strong id="adjustCurrentQty"></strong></p>
                <div class="mb-3">
                    <label class="form-label">Adjustment <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="adjustmentValue" placeholder="Use + for add, - for subtract">
                    <small class="text-muted">Example: +50 to add 50, -20 to subtract 20</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <textarea class="form-control" id="adjustmentReason" rows="2" placeholder="Optional reason for adjustment"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveAdjustment()">Adjust</button>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Logs Modal -->
<div class="modal fade" id="inventoryLogsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Inventory Management Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 150px;">Time</th>
                                <th style="min-width: 140px;">User</th>
                                <th style="min-width: 90px;">Role</th>
                                <th style="min-width: 130px;">Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody id="inventoryLogsTableBody">
                            <tr>
                                <td colspan="5" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let allItems = [];
    let inventoryLogsModal = null;
    const inventoryActions = new Set(['inventory_create', 'inventory_update', 'inventory_delete', 'inventory_adjust']);
    const categoryLabels = {
        'caps': 'Gallon Caps',
        'seals': 'Seals',
        'purification': 'Purification Materials',
        'supplies': 'Other Supplies'
    };

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

    function actionLabel(action) {
        const labels = {
            inventory_create: 'Added Item',
            inventory_update: 'Edited Item',
            inventory_delete: 'Deleted Item',
            inventory_adjust: 'Adjusted Quantity'
        };

        return labels[action] || action;
    }

    async function viewInventoryLogs() {
        if (!inventoryLogsModal) {
            inventoryLogsModal = new bootstrap.Modal(document.getElementById('inventoryLogsModal'));
        }

        inventoryLogsModal.show();
        await loadInventoryLogs();
    }

    async function loadInventoryLogs() {
        try {
            const response = await fetch(`${API_BASE_URL}/logs?per_page=500`, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Failed to load inventory logs');
            }

            const payload = await response.json();
            const logs = (payload.data || []).filter(log => inventoryActions.has(log.action));
            const tbody = document.getElementById('inventoryLogsTableBody');

            if (logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No inventory logs found</td></tr>';
                return;
            }

            tbody.innerHTML = logs.map(log => `
                <tr>
                    <td><small>${formatDateTime(log.created_at)}</small></td>
                    <td>${log.user_name || 'Unknown'}</td>
                    <td><span class="badge ${log.user_role === 'admin' ? 'bg-primary' : 'bg-info'}">${log.user_role || 'N/A'}</span></td>
                    <td><span class="badge bg-secondary">${actionLabel(log.action)}</span></td>
                    <td>${log.details || 'No details available'}</td>
                </tr>
            `).join('');
        } catch (error) {
            console.error('Error loading inventory logs:', error);
            document.getElementById('inventoryLogsTableBody').innerHTML =
                '<tr><td colspan="5" class="text-center text-danger">Error loading inventory logs</td></tr>';
        }
    }

    async function loadStatistics() {
        try {
            const response = await fetch(API_BASE_URL + '/inventory/statistics', {
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            document.getElementById('totalItems').textContent = data.total_items;
            document.getElementById('totalValue').textContent = formatCurrency(data.total_value);
            document.getElementById('lowStockItems').textContent = data.low_stock_items;
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }

    async function loadInventory() {
        try {
            const category = document.getElementById('filterCategory').value;
            const lowStock = document.getElementById('filterStock').value;
            const search = document.getElementById('searchInput').value;

            let url = API_BASE_URL + '/inventory?';
            if (category !== 'all') url += `category=${category}&`;
            if (lowStock === '1') url += `low_stock=1&`;
            if (search) url += `search=${encodeURIComponent(search)}&`;

            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });
            allItems = await response.json();
            renderInventory();
        } catch (error) {
            console.error('Error loading inventory:', error);
            document.getElementById('inventoryTableBody').innerHTML = 
                '<tr><td colspan="9" class="text-center text-danger">Error loading inventory</td></tr>';
        }
    }

    function renderInventory() {
        const tbody = document.getElementById('inventoryTableBody');
        
        if (allItems.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No items found</td></tr>';
            return;
        }

        tbody.innerHTML = allItems.map(item => {
            const needsReorder = item.quantity <= item.reorder_level;
            const statusBadge = needsReorder 
                ? '<span class="badge bg-warning">Low Stock</span>'
                : '<span class="badge bg-success">In Stock</span>';
            
            return `
                <tr>
                    <td>${item.item_name}</td>
                    <td>${categoryLabels[item.category]}</td>
                    <td><strong>${item.quantity}</strong></td>
                    <td>${item.unit}</td>
                    <td>${formatCurrency(item.unit_price)}</td>
                    <td>${formatCurrency(item.quantity * item.unit_price)}</td>
                    <td>${item.reorder_level}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="showAdjustModal(${item.id}, '${item.item_name}', ${item.quantity})" title="Adjust Quantity">
                                <i class="bi bi-arrows-expand"></i>
                            </button>
                            <button class="btn btn-outline-secondary" onclick="editItem(${item.id})" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteItem(${item.id}, '${item.item_name}')" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function resetFilters() {
        document.getElementById('filterCategory').value = 'all';
        document.getElementById('filterStock').value = 'all';
        document.getElementById('searchInput').value = '';
        loadInventory();
    }

    function showAddItemModal() {
        document.getElementById('modalTitle').textContent = 'Add Inventory Item';
        document.getElementById('itemForm').reset();
        document.getElementById('itemId').value = '';
        new bootstrap.Modal(document.getElementById('addItemModal')).show();
    }

    async function editItem(id) {
        try {
            const response = await fetch(API_BASE_URL + `/inventory/${id}`, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error('Failed to load item');
            }
            
            const item = await response.json();
            
            // Update modal title
            document.getElementById('modalTitle').textContent = 'Edit Inventory Item';
            
            // Auto-fill all form fields with item data
            document.getElementById('itemId').value = item.id || '';
            document.getElementById('itemName').value = item.item_name || '';
            document.getElementById('itemCategory').value = item.category || '';
            document.getElementById('itemQuantity').value = item.quantity || 0;
            document.getElementById('itemUnit').value = item.unit || '';
            document.getElementById('itemPrice').value = item.unit_price || 0;
            document.getElementById('itemReorderLevel').value = item.reorder_level || 0;
            document.getElementById('itemDescription').value = item.description || '';
            
            // Show the modal
            new bootstrap.Modal(document.getElementById('addItemModal')).show();
        } catch (error) {
            console.error('Error loading item:', error);
            alert('Error loading item details. Please try again.');
        }
    }

    async function saveItem() {
        const id = document.getElementById('itemId').value;
        const data = {
            item_name: document.getElementById('itemName').value,
            category: document.getElementById('itemCategory').value,
            quantity: parseInt(document.getElementById('itemQuantity').value),
            unit: document.getElementById('itemUnit').value,
            unit_price: parseFloat(document.getElementById('itemPrice').value),
            reorder_level: parseInt(document.getElementById('itemReorderLevel').value),
            description: document.getElementById('itemDescription').value
        };

        try {
            const url = id ? API_BASE_URL + `/inventory/${id}` : API_BASE_URL + '/inventory';
            const method = id ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });

            if (response.ok) {
                bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();
                await loadInventory();
                await loadStatistics();
                alert(id ? 'Item updated successfully' : 'Item added successfully');
            } else {
                const error = await response.json();
                alert('Error: ' + (error.message || 'Failed to save item'));
            }
        } catch (error) {
            console.error('Error saving item:', error);
            alert('Error saving item');
        }
    }

    function showAdjustModal(id, name, currentQty) {
        document.getElementById('adjustItemId').value = id;
        document.getElementById('adjustItemName').textContent = name;
        document.getElementById('adjustCurrentQty').textContent = currentQty;
        document.getElementById('adjustmentValue').value = '';
        document.getElementById('adjustmentReason').value = '';
        new bootstrap.Modal(document.getElementById('adjustQuantityModal')).show();
    }

    async function saveAdjustment() {
        const id = document.getElementById('adjustItemId').value;
        const adjustment = parseInt(document.getElementById('adjustmentValue').value);
        const reason = document.getElementById('adjustmentReason').value;

        try {
            const response = await fetch(API_BASE_URL + `/inventory/${id}/adjust`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({ adjustment, reason })
            });

            if (response.ok) {
                bootstrap.Modal.getInstance(document.getElementById('adjustQuantityModal')).hide();
                await loadInventory();
                await loadStatistics();
                alert('Quantity adjusted successfully');
            } else {
                const error = await response.json();
                alert('Error: ' + (error.message || 'Failed to adjust quantity'));
            }
        } catch (error) {
            console.error('Error adjusting quantity:', error);
            alert('Error adjusting quantity');
        }
    }

    async function deleteItem(id, name) {
        if (!confirm(`Are you sure you want to delete "${name}"?`)) return;

        try {
            const response = await fetch(API_BASE_URL + `/inventory/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                await loadInventory();
                await loadStatistics();
                alert('Item deleted successfully');
            } else {
                const error = await response.json();
                alert('Error: ' + (error.message || 'Failed to delete item'));
            }
        } catch (error) {
            console.error('Error deleting item:', error);
            alert('Error deleting item');
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadInventory();
        loadStatistics();
    });
</script>
@endpush
