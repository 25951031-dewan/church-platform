<?php $__env->startSection('title', 'Ministries - ' . ($settings->church_name ?? config('app.name'))); ?>
<?php $__env->startSection('meta_description', 'Explore our church ministries and find ways to serve and grow in your faith.'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span class="sep">/</span>
            <span>Ministries</span>
        </div>
        <h1 style="font-size:2.2rem;margin-bottom:0.5rem;">Our Ministries</h1>
        <p style="color:var(--text-muted);max-width:600px;">Discover the many ways you can serve, grow, and connect with our church community.</p>
    </div>
</div>

<div class="content-body">
    <div class="container">
        <div class="card-grid">
            <?php $__empty_1 = true; $__currentLoopData = $ministries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ministry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a href="/ministries/<?php echo e($ministry->slug); ?>" class="card" style="color:var(--text);">
                <?php if($ministry->image): ?>
                <img src="/storage/<?php echo e($ministry->image); ?>" alt="<?php echo e($ministry->name); ?>">
                <?php else: ?>
                <div style="height:200px;background:linear-gradient(135deg,var(--surface),var(--card));display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-hands-helping" style="font-size:3rem;color:var(--gold);opacity:0.5;"></i>
                </div>
                <?php endif; ?>
                <div class="card-body">
                    <h3 class="card-title"><?php echo e($ministry->name); ?></h3>
                    <?php if($ministry->category): ?>
                    <span class="badge badge-gold" style="margin-bottom:0.5rem;"><?php echo e($ministry->category); ?></span>
                    <?php endif; ?>
                    <p class="card-excerpt"><?php echo e(\Illuminate\Support\Str::limit(strip_tags($ministry->description), 120)); ?></p>
                    <?php if($ministry->leader_name): ?>
                    <div class="card-meta" style="margin-top:0.75rem;">
                        <span><i class="fas fa-user"></i> <?php echo e($ministry->leader_name); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if($ministry->meeting_schedule): ?>
                    <div class="card-meta">
                        <span><i class="fas fa-clock"></i> <?php echo e($ministry->meeting_schedule); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-muted);">
                <i class="fas fa-hands-helping" style="font-size:2.5rem;opacity:0.3;margin-bottom:1rem;display:block;"></i>
                <p>No ministries available at the moment.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/alphaome/nkhoj.com/resources/views/public/ministries.blade.php ENDPATH**/ ?>