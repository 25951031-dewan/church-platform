<?php $__env->startSection('title', ucfirst(str_replace('-', ' ', $section))); ?>

<?php $__env->startSection('content'); ?>
<div id="admin-app" data-section="<?php echo e($section); ?>"></div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php echo app('Illuminate\Foundation\Vite')->reactRefresh(); ?>
<?php echo app('Illuminate\Foundation\Vite')('resources/js/app.jsx'); ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/alphaome/nkhoj.com/resources/views/admin/manage.blade.php ENDPATH**/ ?>