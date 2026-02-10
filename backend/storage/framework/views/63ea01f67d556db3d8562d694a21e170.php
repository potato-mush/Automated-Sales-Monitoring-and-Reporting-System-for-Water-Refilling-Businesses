<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> - Water Refilling System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>">
    
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
    <?php echo $__env->make('partials.navbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="container-fluid">
        <div class="row">
            <?php echo $__env->make('partials.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="toastContainer"></div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Configuration -->
    <script>
        const API_BASE_URL = '<?php echo e(url('/api')); ?>';
        const BASE_URL = '<?php echo e(url('/')); ?>';
        
        // CSRF Token for API calls
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // Get authenticated user data
        <?php if(auth()->guard()->check()): ?>
        const authUser = {
            id: <?php echo e(auth()->user()->id); ?>,
            name: "<?php echo e(auth()->user()->name); ?>",
            email: "<?php echo e(auth()->user()->email); ?>",
            role: "<?php echo e(auth()->user()->role); ?>"
        };
        <?php else: ?>
        const authUser = null;
        <?php endif; ?>
    </script>
    
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\DarkNight_007\Downloads\Automated-Sales-Monitoring-and-Reporting-System-for-Water-Refilling-Businesses\backend\resources\views/layouts/admin.blade.php ENDPATH**/ ?>