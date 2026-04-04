<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="theme-color" content="#0C0E12">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo e(config('app.name', 'Church')); ?>">
    <meta name="description" content="Your church community app — worship, events, prayer, and more.">
    <title><?php echo e(config('app.name', 'Church')); ?></title>
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icon-192.png">
    <?php echo app('Illuminate\Foundation\Vite')->reactRefresh(); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/pwa-entry.jsx']); ?>
</head>
<body class="bg-black text-white">
    <div id="pwa-root"></div>

    <script>
        // Register service worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js')
                    .then(function (reg) {
                        // Check for updates
                        reg.addEventListener('updatefound', function () {
                            const newWorker = reg.installing;
                            newWorker.addEventListener('statechange', function () {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // New version available — app can show update prompt
                                    window.dispatchEvent(new CustomEvent('sw-update-available'));
                                }
                            });
                        });
                    });
            });
        }
    </script>
</body>
</html>
<?php /**PATH /home/alphaome/nkhoj.com/resources/views/pwa.blade.php ENDPATH**/ ?>