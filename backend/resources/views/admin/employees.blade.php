@extends('layouts.admin')

@section('title', 'Employee Management')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Employee Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-primary" onclick="showAddEmployeeModal()">
            <i class="bi bi-plus-circle me-1"></i>Add Employee
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <i class="bi bi-people icon float-end"></i>
                <div class="label">Total Employees</div>
                <div class="value" id="totalEmployees">0</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <i class="bi bi-person-check icon float-end"></i>
                <div class="label">Active Employees</div>
                <div class="value" id="activeEmployees">0</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <i class="bi bi-shield-check icon float-end"></i>
                <div class="label">Admins</div>
                <div class="value" id="adminCount">0</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <i class="bi bi-person-badge icon float-end"></i>
                <div class="label">Employees</div>
                <div class="value" id="employeeCount">0</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Role</label>
                <select class="form-select" id="filterRole" onchange="loadEmployees()">
                    <option value="all">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="employee">Employee</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" id="filterStatus" onchange="loadEmployees()">
                    <option value="all">All Status</option>
                    <option value="1">Active Only</option>
                    <option value="0">Inactive Only</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" id="searchInput" placeholder="Search by name or email..." onkeyup="loadEmployees()">
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

<!-- Employees Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="employeesTableBody">
                    <tr>
                        <td colspan="7" class="text-center">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="employeeForm">
                    <input type="hidden" id="employeeId">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="employeeName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="employeeEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="employeeRole" required>
                            <option value="">Select role...</option>
                            <option value="admin">Admin</option>
                            <option value="employee">Employee</option>
                        </select>
                    </div>
                    <div class="mb-3" id="passwordField">
                        <label class="form-label">Password <span class="text-danger" id="passwordRequired">*</span></label>
                        <input type="password" class="form-control" id="employeePassword">
                        <small class="text-muted" id="passwordHint">Minimum 8 characters</small>
                    </div>
                    <div class="mb-3" id="confirmPasswordField">
                        <label class="form-label">Confirm Password <span class="text-danger" id="confirmRequired">*</span></label>
                        <input type="password" class="form-control" id="employeePasswordConfirm">
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="employeeActive" checked>
                            <label class="form-check-label" for="employeeActive">Active</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEmployee()">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let allEmployees = [];

    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    }

    async function loadStatistics() {
        try {
            const response = await fetch(API_BASE_URL + '/employees/statistics', {
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            document.getElementById('totalEmployees').textContent = data.total_employees;
            document.getElementById('activeEmployees').textContent = data.active_employees;
            document.getElementById('adminCount').textContent = data.admin_count;
            document.getElementById('employeeCount').textContent = data.employee_count;
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }

    async function loadEmployees() {
        try {
            const role = document.getElementById('filterRole').value;
            const status = document.getElementById('filterStatus').value;
            const search = document.getElementById('searchInput').value;

            let url = API_BASE_URL + '/employees?';
            if (role !== 'all') url += `role=${role}&`;
            if (status !== 'all') url += `is_active=${status}&`;
            if (search) url += `search=${encodeURIComponent(search)}&`;

            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });
            allEmployees = await response.json();
            renderEmployees();
        } catch (error) {
            console.error('Error loading employees:', error);
            document.getElementById('employeesTableBody').innerHTML = 
                '<tr><td colspan="7" class="text-center text-danger">Error loading employees</td></tr>';
        }
    }

    function renderEmployees() {
        const tbody = document.getElementById('employeesTableBody');
        
        if (allEmployees.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No employees found</td></tr>';
            return;
        }

        tbody.innerHTML = allEmployees.map(emp => {
            const roleBadge = emp.role === 'admin' 
                ? '<span class="badge bg-info">Admin</span>'
                : '<span class="badge bg-secondary">Employee</span>';
            
            const statusBadge = emp.is_active
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-danger">Inactive</span>';
            
            const deleteDisabled = emp.role === 'admin' ? 'disabled' : '';
            const deleteTitle = emp.role === 'admin' ? 'Admins cannot be deleted' : 'Delete';
            
            return `
                <tr>
                    <td>${emp.id}</td>
                    <td>${emp.name}</td>
                    <td>${emp.email}</td>
                    <td>${roleBadge}</td>
                    <td>${statusBadge}</td>
                    <td>${formatDate(emp.created_at)}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-${emp.is_active ? 'warning' : 'success'}" 
                                    onclick="toggleStatus(${emp.id})" 
                                    title="${emp.is_active ? 'Deactivate' : 'Activate'}">
                                <i class="bi bi-${emp.is_active ? 'pause' : 'play'}-circle"></i>
                            </button>
                            <button class="btn btn-outline-secondary" onclick="editEmployee(${emp.id})" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteEmployee(${emp.id}, '${emp.name}', '${emp.role}')" 
                                    title="${deleteTitle}" ${deleteDisabled}>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function resetFilters() {
        document.getElementById('filterRole').value = 'all';
        document.getElementById('filterStatus').value = 'all';
        document.getElementById('searchInput').value = '';
        loadEmployees();
    }

    function showAddEmployeeModal() {
        document.getElementById('modalTitle').textContent = 'Add Employee';
        document.getElementById('employeeForm').reset();
        document.getElementById('employeeId').value = '';
        document.getElementById('employeeActive').checked = true;
        
        // Make password required for new employee
        document.getElementById('employeePassword').required = true;
        document.getElementById('employeePasswordConfirm').required = true;
        document.getElementById('passwordRequired').style.display = 'inline';
        document.getElementById('confirmRequired').style.display = 'inline';
        document.getElementById('passwordHint').textContent = 'Minimum 8 characters';
        
        new bootstrap.Modal(document.getElementById('addEmployeeModal')).show();
    }

    async function editEmployee(id) {
        try {
            const response = await fetch(API_BASE_URL + `/employees/${id}`, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error('Failed to load employee');
            }
            
            const emp = await response.json();
            
            // Update modal title
            document.getElementById('modalTitle').textContent = 'Edit Employee';
            
            // Auto-fill all form fields with employee data
            document.getElementById('employeeId').value = emp.id || '';
            document.getElementById('employeeName').value = emp.name || '';
            document.getElementById('employeeEmail').value = emp.email || '';
            document.getElementById('employeeRole').value = emp.role || '';
            document.getElementById('employeeActive').checked = emp.is_active || false;
            
            // Make password optional for editing
            document.getElementById('employeePassword').value = '';
            document.getElementById('employeePasswordConfirm').value = '';
            document.getElementById('employeePassword').required = false;
            document.getElementById('employeePasswordConfirm').required = false;
            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('confirmRequired').style.display = 'none';
            document.getElementById('passwordHint').textContent = 'Leave blank to keep current password';
            
            // Show the modal
            new bootstrap.Modal(document.getElementById('addEmployeeModal')).show();
        } catch (error) {
            console.error('Error loading employee:', error);
            alert('Error loading employee details. Please try again.');
        }
    }

    async function saveEmployee() {
        const id = document.getElementById('employeeId').value;
        const password = document.getElementById('employeePassword').value;
        const passwordConfirm = document.getElementById('employeePasswordConfirm').value;
        
        // Validate passwords
        if (!id && !password) {
            alert('Password is required for new employees');
            return;
        }
        
        if (password && password !== passwordConfirm) {
            alert('Passwords do not match');
            return;
        }
        
        if (password && password.length < 8) {
            alert('Password must be at least 8 characters');
            return;
        }

        const data = {
            name: document.getElementById('employeeName').value,
            email: document.getElementById('employeeEmail').value,
            role: document.getElementById('employeeRole').value,
            is_active: document.getElementById('employeeActive').checked
        };

        if (password) {
            data.password = password;
            data.password_confirmation = passwordConfirm;
        }

        try {
            const url = id ? API_BASE_URL + `/employees/${id}` : API_BASE_URL + '/employees';
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
                bootstrap.Modal.getInstance(document.getElementById('addEmployeeModal')).hide();
                await loadEmployees();
                await loadStatistics();
                alert(id ? 'Employee updated successfully' : 'Employee created successfully');
            } else {
                const error = await response.json();
                let errorMsg = 'Failed to save employee';
                if (error.errors) {
                    errorMsg = Object.values(error.errors).flat().join('\n');
                } else if (error.message) {
                    errorMsg = error.message;
                }
                alert('Error: ' + errorMsg);
            }
        } catch (error) {
            console.error('Error saving employee:', error);
            alert('Error saving employee');
        }
    }

    async function toggleStatus(id) {
        try {
            const response = await fetch(API_BASE_URL + `/employees/${id}/toggle-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                await loadEmployees();
                await loadStatistics();
                alert('Employee status updated');
            } else {
                const error = await response.json();
                alert('Error: ' + (error.message || 'Failed to update status'));
            }
        } catch (error) {
            console.error('Error toggling status:', error);
            alert('Error updating employee status');
        }
    }

    async function deleteEmployee(id, name, role) {
        if (role === 'admin') {
            alert('Admin accounts cannot be deleted');
            return;
        }

        if (!confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`)) return;

        try {
            const response = await fetch(API_BASE_URL + `/employees/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                await loadEmployees();
                await loadStatistics();
                alert('Employee deleted successfully');
            } else {
                const error = await response.json();
                alert('Error: ' + (error.message || 'Failed to delete employee'));
            }
        } catch (error) {
            console.error('Error deleting employee:', error);
            alert('Error deleting employee');
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadEmployees();
        loadStatistics();
    });
</script>
@endpush
