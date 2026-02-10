<nav class="navbar navbar-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo e(route('admin.dashboard')); ?>">
            <i class="bi bi-droplet-fill me-2"></i>
            Water Refilling System
        </a>
        <div class="dropdown">
            <button class="btn btn-link text-white dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle me-1"></i>
                <span><?php echo e(auth()->user()->name ?? 'Admin'); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="<?php echo e(route('admin.logout')); ?>" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="dropdown-item">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php /**PATH C:\Users\DarkNight_007\Downloads\Automated-Sales-Monitoring-and-Reporting-System-for-Water-Refilling-Businesses\backend\resources\views/partials/navbar.blade.php ENDPATH**/ ?>