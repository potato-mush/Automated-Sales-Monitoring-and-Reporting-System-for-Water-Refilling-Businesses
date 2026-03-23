@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Settings</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- System Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>System Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="200">System Name:</th>
                        <td>Water Refilling System</td>
                    </tr>
                    <tr>
                        <th>Version:</th>
                        <td>1.0.0</td>
                    </tr>
                    <tr>
                        <th>API Endpoint:</th>
                        <td><code>{{ url('/api') }}</code></td>
                    </tr>
                    <tr>
                        <th>Current User:</th>
                        <td>{{ auth()->user()->name }}</td>
                    </tr>
                    <tr>
                        <th>User Role:</th>
                        <td><span class="badge bg-primary">{{ ucfirst(auth()->user()->role) }}</span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Business Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-building me-2"></i>Business Settings</h5>
            </div>
            <div class="card-body">
                <form id="businessSettingsForm" onsubmit="updateBusinessSettings(event)">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Business Name</label>
                        <input type="text" class="form-control" id="businessName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Business Address</label>
                        <textarea class="form-control" id="businessAddress" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Business Phone</label>
                        <input type="text" class="form-control" id="businessPhone">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gallon Price</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="gallonPrice" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Delivery Fee</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="deliveryFee" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Overdue Days Threshold</label>
                            <input type="number" min="1" class="form-control" id="overdueDays" required>
                            <small class="text-muted">Days before a gallon is marked as overdue</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Missing Days Threshold</label>
                            <input type="number" min="1" class="form-control" id="missingDays" required>
                            <small class="text-muted">Days before a gallon is marked as missing</small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Business Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Account Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-gear me-2"></i>Account Settings</h5>
            </div>
            <div class="card-body">
                <form id="accountSettingsForm" onsubmit="updateProfile(event)">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="profileName" value="{{ auth()->user()->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="profileEmail" value="{{ auth()->user()->email }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-key me-2"></i>Change Password</h5>
            </div>
            <div class="card-body">
                <form id="changePasswordForm" onsubmit="changePassword(event)">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" id="newPassword" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmPassword" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-lock me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" onclick="exportData()">
                        <i class="bi bi-download me-2"></i>Export System Logs
                    </button>
                    <button class="btn btn-outline-info" onclick="viewLogs()">
                        <i class="bi bi-journal-text me-2"></i>View System Logs
                    </button>
                    <button class="btn btn-outline-warning" onclick="clearSystemLogs()">
                        <i class="bi bi-trash me-2"></i>Clear System Logs
                    </button>
                    <button class="btn btn-outline-danger" onclick="showClearCacheModal()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Clear Application Cache
                    </button>
                </div>
            </div>
        </div>

        <!-- Support Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-question-circle me-2"></i>Support</h5>
            </div>
            <div class="card-body">
                <p><strong>Need Help?</strong></p>
                <p class="text-muted small">Contact your system administrator for technical support and assistance.</p>
                <hr>
                <p class="mb-1"><i class="bi bi-envelope me-2"></i><small>support@waterrefilling.local</small></p>
                <p class="mb-0"><i class="bi bi-phone me-2"></i><small>+63 XXX XXX XXXX</small></p>
            </div>
        </div>

        <!-- About -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-square me-2"></i>About</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-0">
                    Water Refilling System is designed to help manage water refilling station operations including sales monitoring, inventory management, and reporting.
                </p>
                <hr>
                <p class="small text-muted mb-0">
                    <strong>© 2026 Water Refilling System</strong><br>
                    All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- System Logs Modal -->
<div class="modal fade" id="logsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">System Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="filterAction" onchange="loadSystemLogs()">
                                <option value="">All Actions</option>
                                <option value="login">Login</option>
                                <option value="logout">Logout</option>
                                <option value="inventory_create">Inventory Create</option>
                                <option value="inventory_update">Inventory Update</option>
                                <option value="inventory_delete">Inventory Delete</option>
                                <option value="inventory_adjust">Inventory Quantity Adjust</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="filterRole" onchange="loadSystemLogs()">
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="employee">Employee</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="filterPlatform" onchange="loadSystemLogs()">
                                <option value="">All Platforms</option>
                                <option value="web">Web</option>
                                <option value="mobile">Mobile</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-sm btn-primary w-100" onclick="refreshLogs()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Date/Time</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Platform</th>
                                <th>Device</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <tr>
                                <td colspan="8" class="text-center">Loading...</td>
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

<!-- Clear Logs Confirmation Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Clear System Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action will permanently delete all system logs and cannot be undone.
                </div>
                <form id="clearLogsForm" onsubmit="confirmClearLogs(event)">
                    <div class="mb-3">
                        <label class="form-label">Enter Your Password to Confirm</label>
                        <input type="password" class="form-control" id="clearLogsPassword" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Clear All Logs
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Clear Cache Confirmation Modal -->
<div class="modal fade" id="clearCacheModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Clear Application Cache</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    This will clear all application caches including:
                    <ul class="mb-0 mt-2">
                        <li>Application Cache</li>
                        <li>Configuration Cache</li>
                        <li>Route Cache</li>
                        <li>View Cache</li>
                    </ul>
                </div>
                <form id="clearCacheForm" onsubmit="confirmClearCache(event)">
                    <div class="mb-3">
                        <label class="form-label">Enter Your Password to Confirm</label>
                        <input type="password" class="form-control" id="clearCachePassword" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-arrow-clockwise me-2"></i>Clear Cache
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Load settings on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadSettings();
    });

    async function loadSettings() {
        try {
            const response = await fetch(`${API_BASE_URL}/settings`, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Failed to load settings');
            }

            const settings = await response.json();
            
            // Populate business settings
            document.getElementById('businessName').value = settings.business_name || '';
            document.getElementById('businessAddress').value = settings.business_address || '';
            document.getElementById('businessPhone').value = settings.business_phone || '';
            document.getElementById('gallonPrice').value = settings.gallon_price || '25.00';
            document.getElementById('deliveryFee').value = settings.delivery_fee || '50.00';
            document.getElementById('overdueDays').value = settings.overdue_days_threshold || '7';
            document.getElementById('missingDays').value = settings.missing_days_threshold || '30';
        } catch (error) {
            console.error('Error loading settings:', error);
            alert('Failed to load settings');
        }
    }

    async function updateBusinessSettings(e) {
        e.preventDefault();
        
        const settings = {
            business_name: document.getElementById('businessName').value,
            business_address: document.getElementById('businessAddress').value,
            business_phone: document.getElementById('businessPhone').value,
            gallon_price: document.getElementById('gallonPrice').value,
            delivery_fee: document.getElementById('deliveryFee').value,
            overdue_days_threshold: document.getElementById('overdueDays').value,
            missing_days_threshold: document.getElementById('missingDays').value
        };

        try {
            const response = await fetch(`${API_BASE_URL}/settings`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(settings)
            });

            const data = await response.json();

            if (response.ok) {
                alert('Business settings updated successfully');
                loadSettings();
            } else {
                alert(data.message || 'Failed to update settings');
            }
        } catch (error) {
            console.error('Error updating settings:', error);
            alert('Error updating settings');
        }
    }

    async function updateProfile(e) {
        e.preventDefault();
        
        const name = document.getElementById('profileName').value;
        const email = document.getElementById('profileEmail').value;

        try {
            const response = await fetch(`${API_BASE_URL}/profile`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ name, email })
            });

            const data = await response.json();

            if (response.ok) {
                location.reload();
            } else {
                alert(data.message || 'Failed to update profile');
            }
        } catch (error) {
            console.error('Error updating profile:', error);
            alert('Error updating profile');
        }
    }

    async function changePassword(e) {
        e.preventDefault();
        
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (newPassword !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }

        if (newPassword.length < 6) {
            alert('Password must be at least 6 characters');
            return;
        }

        try {
            const response = await fetch(`${API_BASE_URL}/change-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword,
                    new_password_confirmation: confirmPassword
                })
            });

            const data = await response.json();

            if (response.ok) {
                alert('Password changed successfully');
                document.getElementById('changePasswordForm').reset();
            } else {
                const errorMsg = data.errors?.current_password?.[0] || data.message || 'Failed to change password';
                alert(errorMsg);
            }
        } catch (error) {
            console.error('Error changing password:', error);
            alert('Error changing password');
        }
    }

    async function exportData() {
        try {
            const action = document.getElementById('filterAction')?.value || '';
            const role = document.getElementById('filterRole')?.value || '';
            const platform = document.getElementById('filterPlatform')?.value || '';
            
            let url = `${API_BASE_URL}/logs/export?`;
            if (action) url += `action=${action}&`;
            if (role) url += `role=${role}&`;
            if (platform) url += `platform=${platform}&`;
            
            // Open in new window to trigger download
            window.open(url, '_blank');
        } catch (error) {
            console.error('Error exporting data:', error);
            alert('Error exporting system logs');
        }
    }

    let logsModal;
    async function viewLogs() {
        if (!logsModal) {
            logsModal = new bootstrap.Modal(document.getElementById('logsModal'));
        }
        logsModal.show();
        await loadSystemLogs();
    }

    async function loadSystemLogs() {
        const action = document.getElementById('filterAction').value;
        const role = document.getElementById('filterRole').value;
        const platform = document.getElementById('filterPlatform').value;
        
        let url = `${API_BASE_URL}/logs?per_page=100`;
        if (action) url += `&action=${action}`;
        if (role) url += `&role=${role}`;
        if (platform) url += `&platform=${platform}`;

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
                throw new Error('Failed to load logs');
            }

            const data = await response.json();
            displayLogs(data.data || data);
        } catch (error) {
            console.error('Error loading logs:', error);
            document.getElementById('logsTableBody').innerHTML = 
                '<tr><td colspan="8" class="text-center text-danger">Error loading logs</td></tr>';
        }
    }

    function displayLogs(logs) {
        const tbody = document.getElementById('logsTableBody');
        
        if (logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No logs found</td></tr>';
            return;
        }

        const actionBadgeMap = {
            login: '<span class="badge bg-success">Login</span>',
            logout: '<span class="badge bg-secondary">Logout</span>',
            inventory_create: '<span class="badge bg-primary">Inventory Create</span>',
            inventory_update: '<span class="badge bg-info">Inventory Update</span>',
            inventory_delete: '<span class="badge bg-danger">Inventory Delete</span>',
            inventory_adjust: '<span class="badge bg-warning text-dark">Inventory Adjust</span>'
        };

        tbody.innerHTML = logs.map(log => {
            const actionBadge = actionBadgeMap[log.action]
                || `<span class="badge bg-secondary">${String(log.action || 'unknown').replace(/_/g, ' ')}</span>`;
            
            const roleBadge = log.user_role === 'admin'
                ? '<span class="badge bg-primary">Admin</span>'
                : '<span class="badge bg-info">Employee</span>';
            
            const platformBadge = log.platform === 'web'
                ? '<span class="badge bg-dark">Web</span>'
                : '<span class="badge bg-warning">Mobile</span>';

            return `
                <tr>
                    <td><small>${new Date(log.created_at).toLocaleString()}</small></td>
                    <td>
                        <div>${log.user_name}</div>
                        <small class="text-muted">${log.user_email}</small>
                    </td>
                    <td>${roleBadge}</td>
                    <td>${actionBadge}</td>
                    <td><small>${log.details || 'N/A'}</small></td>
                    <td>${platformBadge}</td>
                    <td><small>${log.device || 'N/A'}</small></td>
                    <td><small>${log.ip_address || 'N/A'}</small></td>
                </tr>
            `;
        }).join('');
    }

    function refreshLogs() {
        loadSystemLogs();
    }

    let clearLogsModal;
    function clearSystemLogs() {
        if (!clearLogsModal) {
            clearLogsModal = new bootstrap.Modal(document.getElementById('clearLogsModal'));
        }
        document.getElementById('clearLogsPassword').value = '';
        clearLogsModal.show();
    }

    async function confirmClearLogs(e) {
        e.preventDefault();
        
        const password = document.getElementById('clearLogsPassword').value;
        
        if (!password) {
            alert('Please enter your password');
            return;
        }

        try {
            const response = await fetch(`${API_BASE_URL}/logs/clear`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ password })
            });

            const data = await response.json();

            if (response.ok) {
                alert(`System logs cleared successfully. ${data.deleted_count} entries were deleted.`);
                clearLogsModal.hide();
                document.getElementById('clearLogsForm').reset();
                if (logsModal && document.getElementById('logsModal').classList.contains('show')) {
                    loadSystemLogs();
                }
            } else {
                alert(data.message || 'Failed to clear logs');
            }
        } catch (error) {
            console.error('Error clearing logs:', error);
            alert('Error clearing system logs');
        }
    }

    // Clear Cache Functions
    let clearCacheModal;
    function showClearCacheModal() {
        if (!clearCacheModal) {
            clearCacheModal = new bootstrap.Modal(document.getElementById('clearCacheModal'));
        }
        document.getElementById('clearCachePassword').value = '';
        clearCacheModal.show();
    }

    async function confirmClearCache(e) {
        e.preventDefault();
        
        const password = document.getElementById('clearCachePassword').value;
        
        if (!password) {
            alert('Please enter your password');
            return;
        }

        try {
            const response = await fetch(`${API_BASE_URL}/settings/clear-cache`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ password })
            });

            const data = await response.json();

            if (response.ok) {
                alert('Application cache cleared successfully!');
                clearCacheModal.hide();
                document.getElementById('clearCacheForm').reset();
            } else {
                alert(data.message || 'Failed to clear cache');
            }
        } catch (error) {
            console.error('Error clearing cache:', error);
            alert('Error clearing application cache');
        }
    }
</script>
@endpush

