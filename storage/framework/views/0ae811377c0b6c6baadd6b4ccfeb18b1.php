<div class="space-y-4">
    
    <div class="flex items-center justify-between">
        <div class="space-y-2">
            <div class="h-6 w-48 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
            <div class="h-4 w-32 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
        </div>
        <div class="h-10 w-32 animate-pulse rounded-lg bg-zinc-200 dark:bg-zinc-700"></div>
    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 0; $i < 4; $i++): ?>
            <div class="h-24 animate-pulse rounded-xl bg-zinc-200 dark:bg-zinc-700"></div>
        <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="h-12 animate-pulse bg-zinc-100 dark:bg-zinc-800"></div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 0; $i < 5; $i++): ?>
            <div class="h-16 animate-pulse border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900"></div>
        <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php /**PATH C:\laragon\www\tata-usaha-sekolah\resources\views\components\page-skeleton.blade.php ENDPATH**/ ?>