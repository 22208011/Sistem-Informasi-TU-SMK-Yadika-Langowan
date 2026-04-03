<?php
    $schoolProfile = \App\Models\SchoolProfile::getProfile();
?>
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <?php echo $__env->make('partials.head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-muted flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-xs">
                        <div class="px-10 py-8"><?php echo e($slot); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php app('livewire')->forceAssetInjection(); ?>
        <script src="<?php echo e(asset('vendor/flux/flux.js')); ?>" data-navigate-once></script>
        <script>
            function startAlpineWhenReady() {
                if (window.Alpine && !window.alpineStarted) {
                    window.alpineStarted = true;
                    window.Alpine.start();
                } else if (!window.Alpine) {
                    setTimeout(startAlpineWhenReady, 10);
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startAlpineWhenReady);
            } else {
                startAlpineWhenReady();
            }
        </script>
    </body>
</html>
<?php /**PATH C:\laragon\www\tata-usaha-sekolah\resources\views\layouts\auth\card.blade.php ENDPATH**/ ?>