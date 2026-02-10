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
                <form id="businessSettingsForm">
                    <div class="mb-3">
                        <label class="form-label">Business Name</label>
                        <input type="text" class="form-control" value="Water Refilling Station" readonly>
                        <small class="text-muted">Contact administrator to change business settings</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Slim Gallon Price</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" value="25.00" readonly>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Round Gallon Price</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" value="30.00" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gallon Return Period (Days)</label>
                        <input type="number" class="form-control" value="30" readonly>
                        <small class="text-muted">Number of days before a gallon is marked as overdue</small>
                    </div>
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
                        <i class="bi bi-download me-2"></i>Export Data
                    </button>
                    <button class="btn btn-outline-info" onclick="viewLogs()">
                        <i class="bi bi-journal-text me-2"></i>View System Logs
                    </button>
                    <button class="btn btn-outline-warning" onclick="clearCache()">
                        <i class="bi bi-trash me-2"></i>Clear Cache
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
@endsection

@push('scripts')
<script>
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

    function exportData() {
        alert('Data export feature coming soon');
    }

    function viewLogs() {
        alert('System logs viewer coming soon');
    }

    function clearCache() {
        if (confirm('Are you sure you want to clear the cache? You will be logged out.')) {
            window.location.href = "{{ route('admin.logout') }}";
        }
    }
</script>
@endpush

