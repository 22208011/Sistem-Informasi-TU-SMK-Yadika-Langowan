<?php $__env->startSection('content'); ?>
<table class="data-table">
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th><?php echo e($type === 'student' ? 'NIS' : 'NIP'); ?></th>
            <th>Nama <?php echo e($type === 'student' ? 'Siswa' : 'Pegawai'); ?></th>
            <th><?php echo e($type === 'student' ? 'Kelas' : 'Jabatan'); ?></th>
            <th>Status</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $attendances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
        <tr>
            <td class="text-center"><?php echo e($index + 1); ?></td>
            <td class="text-center"><?php echo e($att->date?->format('d/m/Y') ?? '-'); ?></td>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($type === 'student'): ?>
                <td class="text-center"><?php echo e($att->student?->nis ?? '-'); ?></td>
                <td><?php echo e($att->student?->name ?? '-'); ?></td>
                <td class="text-center"><?php echo e($att->classroom?->name ?? '-'); ?></td>
            <?php else: ?>
                <td class="text-center"><?php echo e($att->employee?->nip ?? '-'); ?></td>
                <td><?php echo e($att->employee?->name ?? '-'); ?></td>
                <td><?php echo e($att->employee?->position?->name ?? '-'); ?></td>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            
            <td class="text-center"><?php echo e(ucfirst($att->status)); ?></td>
            <td><?php echo e($att->notes ?? '-'); ?></td>
        </tr>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </tbody>
</table>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('exports.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\tata-usaha-sekolah\resources\views\exports\attendance.blade.php ENDPATH**/ ?>