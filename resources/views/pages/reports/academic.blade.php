<?php

use App\Models\Grade;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\AcademicYear;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Laporan Akademik')] class extends Component {
    use WithPagination;

    public $classroom_id = '';
    public $subject_id = '';
    public $semester = '';

    public function with(): array
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        // Overall statistics
        $query = Grade::query()
            ->when($academicYear, fn($q) => $q->where('academic_year_id', $academicYear->id));

        if ($this->classroom_id) {
            $query->whereHas('student', fn($q) => $q->where('classroom_id', $this->classroom_id));
        }

        if ($this->subject_id) {
            $query->where('subject_id', $this->subject_id);
        }

        if ($this->semester) {
            $query->where('semester', $this->semester);
        }

        $gradesData = $query->get();

        // Calculate statistics
        $averageScore = $gradesData->avg('score');
        $highestScore = $gradesData->max('score');
        $lowestScore = $gradesData->min('score');
        $totalGrades = $gradesData->count();

        // Distribution
        $excellent = $gradesData->where('score', '>=', 90)->count();
        $good = $gradesData->where('score', '>=', 80)->where('score', '<', 90)->count();
        $average = $gradesData->where('score', '>=', 70)->where('score', '<', 80)->count();
        $poor = $gradesData->where('score', '>=', 60)->where('score', '<', 70)->count();
        $veryPoor = $gradesData->where('score', '<', 60)->count();

        // Top performers per subject
        $semester = $this->semester;
        $topPerSubject = Subject::with(['grades' => function ($q) use ($academicYear, $semester) {
            $q->when($academicYear, fn($q) => $q->where('academic_year_id', $academicYear->id))
                ->when($semester, fn($q) => $q->where('semester', $semester))
                ->with('student')
                ->orderBy('score', 'desc')
                ->limit(5);
        }])->get();

        // Class rankings
        $classRankings = Classroom::withAvg(['students as avg_grade' => function ($q) use ($academicYear) {
            $q->join('grades', 'students.id', '=', 'grades.student_id')
                ->when($academicYear, fn($q) => $q->where('grades.academic_year_id', $academicYear->id));
        }], 'grades.score')
            ->having('avg_grade', '>', 0)
            ->orderByDesc('avg_grade')
            ->get();

        return [
            'academicYear' => $academicYear,
            'classrooms' => Classroom::with('department')->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'averageScore' => $averageScore,
            'highestScore' => $highestScore,
            'lowestScore' => $lowestScore,
            'totalGrades' => $totalGrades,
            'excellent' => $excellent,
            'good' => $good,
            'average' => $average,
            'poor' => $poor,
            'veryPoor' => $veryPoor,
            'topPerSubject' => $topPerSubject,
            'classRankings' => $classRankings,
        ];
    }
}; ?>

<div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <flux:button icon="arrow-left" variant="ghost" :href="route('reports.index')" wire:navigate />
                <div>
                    <flux:heading size="xl">Laporan Akademik</flux:heading>
                    <flux:subheading>
                        @if ($academicYear)
                            Tahun Ajaran {{ $academicYear->name }}
                        @else
                            Belum ada tahun ajaran aktif
                        @endif
                    </flux:subheading>
                </div>
            </div>
            @can('reports.export')
            <div class="flex gap-2">
                <flux:button icon="arrow-down-tray" variant="outline" :href="route('reports.export.academic', ['classroom_id' => $classroom_id, 'subject_id' => $subject_id, 'semester' => $semester])" target="_blank">
                    Export CSV
                </flux:button>
                <flux:button icon="printer" variant="outline" onclick="window.print()">
                    Print
                </flux:button>
            </div>
            @endcan
        </div>

        <!-- Filters -->
        <flux:card>
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select wire:model.live="classroom_id">
                    <option value="">Semua Kelas</option>
                    @foreach ($classrooms as $classroom)
                        <option value="{{ $classroom->id }}">{{ $classroom->name }} - {{ $classroom->department?->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="subject_id">
                    <option value="">Semua Mata Pelajaran</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="semester">
                    <option value="">Semua Semester</option>
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                </flux:select>
            </div>
        </flux:card>

        <!-- Summary Stats -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:card class="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $averageScore ? number_format($averageScore, 1) : '-' }}</p>
                    <p class="text-sm text-blue-700 dark:text-blue-300">Rata-rata Nilai</p>
                </div>
            </flux:card>

            <flux:card class="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $highestScore ?? '-' }}</p>
                    <p class="text-sm text-green-700 dark:text-green-300">Nilai Tertinggi</p>
                </div>
            </flux:card>

            <flux:card class="border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $lowestScore ?? '-' }}</p>
                    <p class="text-sm text-red-700 dark:text-red-300">Nilai Terendah</p>
                </div>
            </flux:card>

            <flux:card class="border-purple-200 bg-purple-50 dark:border-purple-800 dark:bg-purple-900/20">
                <div class="text-center">
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($totalGrades) }}</p>
                    <p class="text-sm text-purple-700 dark:text-purple-300">Total Data Nilai</p>
                </div>
            </flux:card>
        </div>

        <!-- Grade Distribution -->
        <flux:card>
            <flux:heading size="sm" class="mb-4">Distribusi Nilai</flux:heading>
            @if ($totalGrades > 0)
                <div class="space-y-4">
                    @php
                        $categories = [
                            ['label' => 'Sangat Baik (90-100)', 'count' => $excellent, 'color' => 'green'],
                            ['label' => 'Baik (80-89)', 'count' => $good, 'color' => 'blue'],
                            ['label' => 'Cukup (70-79)', 'count' => $average, 'color' => 'yellow'],
                            ['label' => 'Kurang (60-69)', 'count' => $poor, 'color' => 'orange'],
                            ['label' => 'Sangat Kurang (<60)', 'count' => $veryPoor, 'color' => 'red'],
                        ];
                    @endphp
                    @foreach ($categories as $cat)
                        @php
                            $percentage = $totalGrades > 0 ? ($cat['count'] / $totalGrades) * 100 : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $cat['label'] }}</span>
                                <span class="text-gray-500">{{ $cat['count'] }} ({{ number_format($percentage, 1) }}%)</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-full rounded-full bg-{{ $cat['color'] }}-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">Belum ada data nilai.</p>
            @endif
        </flux:card>

        <!-- Class Rankings -->
        @if ($classRankings->count() > 0)
        <flux:card>
            <flux:heading size="sm" class="mb-4">Peringkat Kelas Berdasarkan Rata-rata Nilai</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Peringkat</flux:table.column>
                    <flux:table.column>Kelas</flux:table.column>
                    <flux:table.column>Rata-rata Nilai</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($classRankings->take(10) as $index => $class)
                        <flux:table.row>
                            <flux:table.cell>
                                @if ($index < 3)
                                    <flux:badge :color="match($index) { 0 => 'amber', 1 => 'zinc', 2 => 'orange' }" size="sm">
                                        #{{ $index + 1 }}
                                    </flux:badge>
                                @else
                                    <span class="text-gray-500">{{ $index + 1 }}</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-medium">
                                {{ $class->name }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $avgGrade = $class->avg_grade;
                                    $gradeColor = $avgGrade >= 80 ? 'green' : ($avgGrade >= 70 ? 'yellow' : 'red');
                                @endphp
                                <flux:badge :color="$gradeColor" size="sm">
                                    {{ number_format($avgGrade, 1) }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif
</div>
