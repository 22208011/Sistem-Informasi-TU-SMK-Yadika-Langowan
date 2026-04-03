<?php $__env->startSection('content'); ?>
<table class="data-table">
    <thead>
        <tr>
            <th>No</th>
            <th>NIS</th>
            <th>Nama Siswa</th>
            <th>L/P</th>
            <th>Kelas</th>
            <th>Jurusan</th>
            <th>Status</th>
            <th>Nama Wali</th>
        </tr>
    </thead>
    <tbody>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
        <tr>
            <td class="text-center"><?php echo e($index + 1); ?></td>
            <td class="text-center"><?php echo e($student->nis ?? '-'); ?></td>
            <td><?php echo e($student->name); ?></td>
            <td class="text-center"><?php echo e($student->gender === 'male' ? 'L' : 'P'); ?></td>
            <td class="text-center"><?php echo e($student->classroom?->name ?? '-'); ?></td>
            <td><?php echo e($student->department?->name ?? '-'); ?></td>
            <td class="text-center"><?php echo e(ucfirst($student->status)); ?></td>
            <td><?php echo e($student->guardian?->name ?? '-'); ?></td>
        </tr>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </tbody>
</table>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('exports.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\tata-usaha-sekolah\resources\views\exports\students.blade.php ENDPATH**/ ?>