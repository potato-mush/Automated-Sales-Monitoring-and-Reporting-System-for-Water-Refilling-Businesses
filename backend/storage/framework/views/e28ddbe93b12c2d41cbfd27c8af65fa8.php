<nav id="adminSidebar" class="col-md-2 collapse d-md-block sidebar">
    <div class="position-sticky pt-3 pb-3">
        <div class="px-3 pb-2 text-uppercase text-muted small fw-semibold">Navigation</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.dashboard') ? 'active' : ''); ?>" 
                   href="<?php echo e(route('admin.dashboard')); ?>">
                    <i class="bi bi-speedometer2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.transactions*') ? 'active' : ''); ?>" 
                   href="<?php echo e(route('admin.transactions')); ?>">
                    <i class="bi bi-receipt"></i>Transactions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.gallons*') ? 'active' : ''); ?>" 
                   href="<?php echo e(route('admin.gallons')); ?>">
                    <i class="bi bi-droplet"></i>Gallons
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.reports*') ? 'active' : ''); ?>" 
                   href="<?php echo e(route('admin.reports')); ?>">
                    <i class="bi bi-file-earmark-text"></i>Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.inventory*') ? 'active' : ''); ?>" 
                   href="<?php echo e(route('admin.inventory')); ?>">
                    <i class="bi bi-box-seam"></i>Inventory
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.employees*') ? 'active' : ''); ?>" 
                   href="<?php echo e(route('admin.employees')); ?>">
                    <i class="bi bi-people"></i>Employees
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.qr-print') ? 'active' : ''); ?>" 
                   href="<?php echo e(route('admin.qr-print')); ?>">
                    <i class="bi bi-qr-code"></i>Print QR Codes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.settings*') ? 'active' : ''); ?>" 
                   href="<?php echo e(route('admin.settings')); ?>">
                    <i class="bi bi-gear"></i>Settings
                </a>
            </li>
        </ul>
    </div>
</nav>
<?php /**PATH C:\Users\DarkNight_007\Downloads\Automated-Sales-Monitoring-and-Reporting-System-for-Water-Refilling-Businesses\backend\resources\views/partials/sidebar.blade.php ENDPATH**/ ?>