<?php $__env->startSection('title', 'Library - ' . ($settings->church_name ?? config('app.name'))); ?>
<?php $__env->startSection('meta_description', 'Browse our church library - free books, resources, and study materials.'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span class="sep">/</span>
            <span>Library</span>
        </div>
        <h1 style="font-size:2.2rem;margin-bottom:0.5rem;">Church Library</h1>
        <p style="color:var(--text-muted);max-width:600px;">Browse our collection of books and resources to deepen your faith.</p>
    </div>
</div>

<div class="content-body">
    <div class="container">
        <div class="card-grid">
            <?php $__empty_1 = true; $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a href="/library/<?php echo e($book->slug); ?>" class="card" style="color:var(--text);">
                <?php if($book->cover_image): ?>
                <img src="/storage/<?php echo e($book->cover_image); ?>" alt="<?php echo e($book->title); ?>">
                <?php else: ?>
                <div style="height:200px;background:linear-gradient(135deg,var(--surface),var(--card));display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-book" style="font-size:3rem;color:var(--gold);opacity:0.5;"></i>
                </div>
                <?php endif; ?>
                <div class="card-body">
                    <h3 class="card-title"><?php echo e($book->title); ?></h3>
                    <div class="card-meta">
                        <?php if($book->author): ?><span><i class="fas fa-pen-fancy"></i> <?php echo e($book->author); ?></span><?php endif; ?>
                        <?php if($book->is_free): ?><span class="badge badge-green">Free</span><?php endif; ?>
                    </div>
                    <?php if($book->category): ?>
                    <div class="card-meta"><span class="badge badge-gold"><?php echo e($book->category); ?></span></div>
                    <?php endif; ?>
                    <p class="card-excerpt"><?php echo e(\Illuminate\Support\Str::limit(strip_tags($book->description), 100)); ?></p>
                    <div class="card-meta" style="margin-top:0.5rem;">
                        <span><i class="fas fa-eye"></i> <?php echo e($book->view_count ?? 0); ?> views</span>
                        <span><i class="fas fa-download"></i> <?php echo e($book->download_count ?? 0); ?> downloads</span>
                    </div>
                </div>
            </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-muted);">
                <i class="fas fa-book" style="font-size:2.5rem;opacity:0.3;margin-bottom:1rem;display:block;"></i>
                <p>No books available at the moment.</p>
            </div>
            <?php endif; ?>
        </div>

        <?php if($books->hasPages()): ?>
        <div class="pagination">
            <?php echo e($books->links('pagination::simple-default')); ?>

        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/siku/Documents/GitHub/church/resources/views/public/library.blade.php ENDPATH**/ ?>