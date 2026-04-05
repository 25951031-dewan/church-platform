<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e(config('app.name', 'Church Platform')); ?></title>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    
    <script>
        window.__BOOTSTRAP_DATA__ = <?php echo json_encode($bootstrapData ?? [], 15, 512) ?>;
    </script>

    <?php echo app('Illuminate\Foundation\Vite')->reactRefresh(); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/client/main.tsx']); ?>
</head>
<body class="antialiased">
    <div id="root"></div>
</body>
</html>
<?php /**PATH /Users/siku/Documents/GitHub/church/resources/views/app.blade.php ENDPATH**/ ?>