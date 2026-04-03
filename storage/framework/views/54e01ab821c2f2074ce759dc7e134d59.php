<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['type' => 'card', 'count' => 1]));

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

foreach (array_filter((['type' => 'card', 'count' => 1]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $types = [
        'card' => 'h-32 rounded-xl',
        'table-row' => 'h-12 rounded-lg',
        'text' => 'h-4 rounded',
        'avatar' => 'h-10 w-10 rounded-full',
        'button' => 'h-10 w-24 rounded-lg',
        'stat' => 'h-24 rounded-xl',
    ];
    $class = $types[$type] ?? $types['card'];
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 0; $i < $count; $i++): ?>
    <div <?php echo e($attributes->merge(['class' => "animate-pulse bg-zinc-200 dark:bg-zinc-700 $class"])); ?>></div>
<?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\laragon\www\tata-usaha-sekolah\resources\views\components\loading-skeleton.blade.php ENDPATH**/ ?>