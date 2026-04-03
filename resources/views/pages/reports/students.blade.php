<?php

use App\Models\Student;
use App\Models\Classroom;
use App\Models\Department;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Laporan Siswa')] class extends Component {
    use WithPagination;

    public $department_id = '';
    public $grade = '';
    public $status = 'aktif';
    public $gender = '';

    public function with(): array
    {
        $query = Student::query()->with(['classroom.department', 'guardian']);

        if ($this->department_id) {
            $query->whereHas('classroom', fn($q) => $q->where('department_id', $this->department_id));
        }

        if ($this->grade) {
            $query->whereHas('classroom', fn($q) => $q->where('grade', $this->grade));
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->gender) {
            $query->where('gender', $this->gender);
        }

        // Statistics
        $allStudents = Student::query();
        if ($this->department_id) {
            $allStudents->whereHas('classroom', fn($q) => $q->where('department_id', $this->department_id));
        }
        if ($this->grade) {
            $allStudents->whereHas('classroom', fn($q) => $q->where('grade', $this->grade));
        }

        $totalActive = (clone $allStudents)->where('status', 'aktif')->count();
        $totalInactive = (clone $allStudents)->whereIn('status', ['pindah', 'keluar', 'do'])->count();
        $totalGraduated = (clone $allStudents)->where('status', 'lulus')->count();
        $totalMale = (clone $allStudents)->where('gender', 'L')->where('status', 'aktif')->count();
        $totalFemale = (clone $allStudents)->where('gender', 'P')->where('status', 'aktif')->count();

        // Per Department
        $perDepartment = Department::withCount(['classrooms as student_count' => function ($q) {
            $q->join('students', 'classrooms.id', '=', 'students.classroom_id')
                ->where('students.status', 'aktif');
        }])->get();

        // Per Grade Level
        $perGradeLevel = Classroom::selectRaw('grade, count(students.id) as total')
            ->leftJoin('students', function ($join) {
                $join->on('classrooms.id', '=', 'students.classroom_id')
                    ->where('students.status', 'aktif');
            })
            ->groupBy('grade')
            ->orderBy('grade')
            ->get();

        return [
            'students' => $query->orderBy('name')->paginate(20),
            'departments' => Department::orderBy('name')->get(),
            'totalActive' => $totalActive,
            'totalInactive' => $totalInactive,
            'totalGraduated' => $totalGraduated,
            'totalMale' => $totalMale,
            'totalFemale' => $totalFemale,
            'perDepartment' => $perDepartment,
            'perGradeLevel' => $perGradeLevel,
        ];
    }
}; ?>

<div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <flux:button icon="arrow-left" variant="ghost" :href="route('reports.index')" wire:navigate />
                <div>
                    <flux:heading size="xl">Laporan Siswa</flux:heading>
                    <flux:subheading>Statistik dan data siswa</flux:subheading>
                </div>
            </div>
            @can('reports.export')
            <div class="flex gap-2">
                <flux:dropdown>
                    <flux:button icon="arrow-down-tray" variant="primary">Ekspor Laporan</flux:button>
                    <flux:menu>
                        <flux:menu.item icon="document-text" :href="route('reports.export.students', ['format' => 'pdf', 'department_id' => $department_id, 'status' => $status, 'gender' => $gender])" target="_blank">Laporan PDF</flux:menu.item>
                        <flux:menu.item icon="document" :href="route('reports.export.students', ['format' => 'word', 'department_id' => $department_id, 'status' => $status, 'gender' => $gender])" target="_blank">Microsoft Word (.doc)</flux:menu.item>
                        <flux:menu.item icon="table-cells" :href="route('reports.export.students', ['format' => 'excel', 'department_id' => $department_id, 'status' => $status, 'gender' => $gender])" target="_blank">Microsoft Excel (.xls)</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
            @endcan
        </div>

        <!-- Summary Stats -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <flux:card class="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalActive) }}</p>
                    <p class="text-sm text-blue-700 dark:text-blue-300">Siswa Aktif</p>
                </div>
            </flux:card>

            <flux:card class="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($totalGraduated) }}</p>
                    <p class="text-sm text-green-700 dark:text-green-300">Lulus</p>
                </div>
            </flux:card>

            <flux:card class="border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($totalInactive) }}</p>
                    <p class="text-sm text-red-700 dark:text-red-300">Tidak Aktif</p>
                </div>
            </flux:card>

            <flux:card class="border-cyan-200 bg-cyan-50 dark:border-cyan-800 dark:bg-cyan-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ number_format($totalMale) }}</p>
                    <p class="text-sm text-cyan-700 dark:text-cyan-300">Laki-laki</p>
                </div>
            </flux:card>

            <flux:card class="border-pink-200 bg-pink-50 dark:border-pink-800 dark:bg-pink-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">{{ number_format($totalFemale) }}</p>
                    <p class="text-sm text-pink-700 dark:text-pink-300">Perempuan</p>
                </div>
            </flux:card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Per Department -->
            <flux:card>
                <flux:heading size="sm" class="mb-4">Siswa per Jurusan</flux:heading>
                @if ($perDepartment->count() > 0)
                    <div class="space-y-3">
                        @foreach ($perDepartment as $dept)
                            @php
                                $percentage = $totalActive > 0 ? ($dept->student_count / $totalActive) * 100 : 0;
                            @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $dept->name }}</span>
                                    <span class="text-gray-500">{{ $dept->student_count }} siswa</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div class="h-full rounded-full bg-blue-500" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">Belum ada data jurusan.</p>
                @endif
            </flux:card>

            <!-- Per Grade Level -->
            <flux:card>
                <flux:heading size="sm" class="mb-4">Siswa per Tingkat Kelas</flux:heading>
                @if ($perGradeLevel->count() > 0)
                    <div class="space-y-3">
                        @foreach ($perGradeLevel as $grade)
                            @php
                                $percentage = $totalActive > 0 ? ($grade->total / $totalActive) * 100 : 0;
                            @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-900 dark:text-white">Kelas {{ $grade->grade }}</span>
                                    <span class="text-gray-500">{{ $grade->total }} siswa</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div class="h-full rounded-full bg-green-500" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">Belum ada data kelas.</p>
                @endif
            </flux:card>
        </div>

        <!-- Filters & Data Table -->
        <flux:card>
            <flux:heading size="sm" class="mb-4">Daftar Siswa</flux:heading>

            <div class="mb-4 grid gap-4 sm:grid-cols-4">
                <flux:select wire:model.live="department_id">
                    <option value="">Semua Jurusan</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="grade">
                    <option value="">Semua Tingkat</option>
                    <option value="10">Kelas 10</option>
                    <option value="11">Kelas 11</option>
                    <option value="12">Kelas 12</option>
                </flux:select>
                <flux:select wire:model.live="status">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="pindah">Pindah</option>
                    <option value="lulus">Lulus</option>
                    <option value="keluar">Keluar</option>
                    <option value="do">DO</option>
                </flux:select>
                <flux:select wire:model.live="gender">
                    <option value="">Semua Gender</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </flux:select>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>NIS</flux:table.column>
                    <flux:table.column>Nama</flux:table.column>
                    <flux:table.column>L/P</flux:table.column>
                    <flux:table.column>Kelas</flux:table.column>
                    <flux:table.column>Jurusan</flux:table.column>
                    <flux:table.column>Wali</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($students as $student)
                        <flux:table.row>
                            <flux:table.cell class="font-mono text-sm">{{ $student->nis }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $student->name }}</flux:table.cell>
                            <flux:table.cell>{{ $student->gender === 'male' ? 'L' : 'P' }}</flux:table.cell>
                            <flux:table.cell>{{ $student->classroom?->name ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $student->classroom?->department?->name ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $student->guardian?->name ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $statusColors = ['active' => 'green', 'inactive' => 'red', 'graduated' => 'blue'];
                                    $statusLabels = ['active' => 'Aktif', 'inactive' => 'Tidak Aktif', 'graduated' => 'Lulus'];
                                @endphp
                                <flux:badge :color="$statusColors[$student->status] ?? 'zinc'" size="sm">
                                    {{ $statusLabels[$student->status] ?? $student->status }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-8">
                                <p class="text-gray-500">Tidak ada data siswa.</p>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($students->hasPages())
                <div class="mt-4">
                    {{ $students->links() }}
                </div>
            @endif
    </flux:card>
</div>
