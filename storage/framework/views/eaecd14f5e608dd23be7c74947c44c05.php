<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title',
    'value',
    'icon' => null,
    'color' => 'blue', // blue, green, purple, amber, red, indigo
    'trend' => null,
    'trendLabel' => null,
    'suffix' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'title',
    'value',
    'icon' => null,
    'color' => 'blue', // blue, green, purple, amber, red, indigo
    'trend' => null,
    'trendLabel' => null,
    'suffix' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $colorClasses = [
        'blue' => [
            'stat' => 'stat-card blue',
            'icon' => 'icon-container blue',
            'trend-up' => 'text-green-500',
            'trend-down' => 'text-red-500',
        ],
        'green' => [
            'stat' => 'stat-card green',
            'icon' => 'icon-container green',
            'trend-up' => 'text-green-500',
            'trend-down' => 'text-red-500',
        ],
        'purple' => [
            'stat' => 'stat-card purple',
            'icon' => 'icon-container purple',
            'trend-up' => 'text-green-500',
            'trend-down' => 'text-red-500',
        ],
        'amber' => [
            'stat' => 'stat-card amber',
            'icon' => 'icon-container amber',
            'trend-up' => 'text-green-500',
            'trend-down' => 'text-red-500',
        ],
        'red' => [
            'stat' => 'stat-card red',
            'icon' => 'icon-container red',
            'trend-up' => 'text-green-500',
            'trend-down' => 'text-red-500',
        ],
        'indigo' => [
            'stat' => 'stat-card indigo',
            'icon' => 'icon-container indigo',
            'trend-up' => 'text-green-500',
            'trend-down' => 'text-red-500',
        ],
    ];
    
    $classes = $colorClasses[$color] ?? $colorClasses['blue'];
?>

<div <?php echo e($attributes->merge(['class' => $classes['stat'] . ' p-5 shadow-sm hover:shadow-lg transition-all duration-300'])); ?>>
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 mb-2 uppercase tracking-wider"><?php echo e($title); ?></p>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 animate-count-up"><?php echo e($value); ?></span>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($suffix): ?>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400"><?php echo e($suffix); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($trend !== null && $trendLabel): ?>
                <div class="flex items-center gap-1 mt-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($trend > 0): ?>
                        <svg class="size-4 <?php echo e($classes['trend-up']); ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                        </svg>
                        <span class="text-sm <?php echo e($classes['trend-up']); ?>">+<?php echo e($trend); ?>%</span>
                    <?php else: ?>
                        <svg class="size-4 <?php echo e($classes['trend-down']); ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                        </svg>
                        <span class="text-sm <?php echo e($classes['trend-down']); ?>"><?php echo e($trend); ?>%</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <span class="text-xs text-zinc-400"><?php echo e($trendLabel); ?></span>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?>
            <div class="<?php echo e($classes['icon']); ?> size-12">
                <?php echo e($icon); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($slot) && !$slot->isEmpty()): ?>
        <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
            <?php echo e($slot); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\laragon\www\tata-usaha-sekolah\resources\views/components/stat-card.blade.php ENDPATH**/ ?>