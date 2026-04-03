<?php $__env->startSection('content'); ?>
<table class="data-table">
    <thead>
        <tr>
            <th>No</th>
            <th>NIP</th>
            <th>Nama Pegawai</th>
            <th>L/P</th>
            <th>Jabatan</th>
            <th>Jenis</th>
            <th>No HP</th>
            <th>Status Aktif</th>
        </tr>
    </thead>
    <tbody>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
        <tr>
            <td class="text-center"><?php echo e($index + 1); ?></td>
            <td class="text-center"><?php echo e($employee->nip ?? '-'); ?></td>
            <td><?php echo e($employee->name); ?></td>
            <td class="text-center"><?php echo e($employee->gender === 'male' ? 'L' : 'P'); ?></td>
            <td><?php echo e($employee->position?->name ?? '-'); ?></td>
            <td class="text-center"><?php echo e(ucfirst($employee->employee_type ?? '-')); ?></td>
            <td class="text-center"><?php echo e($employee->phone ?? '-'); ?></td>
            <td class="text-center"><?php echo e($employee->is_active ? 'Aktif' : 'Tidak'); ?></td>
        </tr>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </tbody>
</table>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('exports.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\tata-usaha-sekolah\resources\views\exports\employees.blade.php ENDPATH**/ ?>