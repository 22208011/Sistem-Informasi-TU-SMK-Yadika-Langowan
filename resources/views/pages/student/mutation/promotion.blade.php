<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Student;
use App\Models\StudentMutation;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Kenaikan Kelas')] class extends Component {
    use WithPagination;

    // Filter for student selection
    public ?int $filterClassroom = null;
    public ?int $filterDepartment = null;
    public string $searchStudent = '';
    
    // Promotion details
    public array $selectedStudents = [];
    public ?int $target_classroom_id = null;
    public ?int $academic_year_id = null;
    public string $mutation_date = '';
    public string $effective_date = '';
    public string $notes = '';
    
    // Mode: single or bulk
    public string $mode = 'bulk';

    public function mount(): void
    {
        $this->mutation_date = now()->format('Y-m-d');
        $this->effective_date = now()->format('Y-m-d');
        
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->academic_year_id = $activeYear?->id;
    }

    #[Computed]
    public function students()
    {
        return Student::query()
            ->where('status', 'aktif')
            ->with(['classroom', 'department'])
            ->when($this->filterClassroom, fn($q) => $q->where('classroom_id', $this->filterClassroom))
            ->when($this->filterDepartment, fn($q) => $q->where('department_id', $this->filterDepartment))
            ->when($this->searchStudent, fn($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->searchStudent}%")
                    ->orWhere('nis', 'like', "%{$this->searchStudent}%");
            }))
            ->orderBy('name')
            ->paginate(20);
    }

    #[Computed]
    public function sourceClassrooms()
    {
        return Classroom::query()
            ->active()
            ->when($this->filterDepartment, fn($q) => $q->where('department_id', $this->filterDepartment))
            ->orderBy('grade')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function targetClassrooms()
    {
        // Get higher grade classrooms than selected source
        $sourceClassroom = $this->filterClassroom ? Classroom::find($this->filterClassroom) : null;
        
        return Classroom::query()
            ->active()
            ->when($this->filterDepartment, fn($q) => $q->where('department_id', $this->filterDepartment))
            ->when($sourceClassroom, fn($q) => $q->where('grade', '>', $sourceClassroom->grade))
            ->orderBy('grade')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function departments()
    {
        return Department::active()->orderBy('name')->get();
    }

    #[Computed]
    public function academicYears()
    {
        return AcademicYear::orderByDesc('start_date')->get();
    }

    #[Computed]
    public function selectedStudentsData()
    {
        if (empty($this->selectedStudents)) {
            return collect();
        }
        return Student::with(['classroom', 'department'])->whereIn('id', $this->selectedStudents)->get();
    }

    public function updatedFilterDepartment(): void
    {
        $this->filterClassroom = null;
        $this->target_classroom_id = null;
        $this->selectedStudents = [];
    }

    public function updatedFilterClassroom(): void
    {
        $this->target_classroom_id = null;
        $this->selectedStudents = [];
    }

    public function toggleStudent(int $studentId): void
    {
        if (in_array($studentId, $this->selectedStudents)) {
            $this->selectedStudents = array_values(array_diff($this->selectedStudents, [$studentId]));
        } else {
            $this->selectedStudents[] = $studentId;
        }
    }

    public function selectAll(): void
    {
        $this->selectedStudents = $this->students->pluck('id')->toArray();
    }

    public function deselectAll(): void
    {
        $this->selectedStudents = [];
    }

    public function save(): void
    {
        $this->validate([
            'selectedStudents' => 'required|array|min:1',
            'target_classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'mutation_date' => 'required|date',
            'effective_date' => 'required|date',
        ], [
            'selectedStudents.required' => 'Pilih minimal satu siswa.',
            'selectedStudents.min' => 'Pilih minimal satu siswa.',
            'target_classroom_id.required' => 'Kelas tujuan wajib dipilih.',
        ]);

        $targetClassroom = Classroom::find($this->target_classroom_id);

        DB::transaction(function () use ($targetClassroom) {
            foreach ($this->selectedStudents as $studentId) {
                $student = Student::find($studentId);
                if (!$student) continue;

                // Create mutation record
                StudentMutation::create([
                    'student_id' => $student->id,
                    'type' => StudentMutation::TYPE_NAIK_KELAS,
                    'mutation_date' => $this->mutation_date,
                    'effective_date' => $this->effective_date,
                    'reason' => 'Naik kelas ke ' . $targetClassroom->name,
                    'previous_classroom_id' => $student->classroom_id,
                    'new_classroom_id' => $this->target_classroom_id,
                    'notes' => $this->notes ?: null,
                    'status' => StudentMutation::STATUS_APPROVED,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'academic_year_id' => $this->academic_year_id,
                ]);

                // Update student classroom
                $student->update(['classroom_id' => $this->target_classroom_id]);
            }
        });

        $count = count($this->selectedStudents);
        session()->flash('success', "{$count} siswa berhasil dinaikkan ke kelas {$targetClassroom->name}.");
        $this->redirect(route('students.mutations.index'), navigate: true);
    }
}; ?>

<div>
    <!-- Page Header -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path d="M12 14l9-5-9-5-9 5 9 5z" />
                <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Kenaikan Kelas') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Proses kenaikan kelas siswa secara massal atau individual.') }}</x-slot:subtitle>
        <x-slot:actions>
            <flux:button :href="route('students.mutations.index')" variant="ghost" icon="arrow-left" wire:navigate>
                {{ __('Kembali') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="save" class="space-y-6">
        <!-- Step 1: Filter & Select Source -->
        <x-card>
            <x-slot:header>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">1. Pilih Kelas Asal</h3>
            </x-slot:header>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:select wire:model.live="filterDepartment" label="Jurusan">
                    <option value="">Semua Jurusan</option>
                    @foreach($this->departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="filterClassroom" label="Kelas Asal">
                    <option value="">Pilih Kelas</option>
                    @foreach($this->sourceClassrooms as $classroom)
                        <option value="{{ $classroom->id }}">{{ $classroom->name }} ({{ $classroom->students_count ?? 0 }} siswa)</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="academic_year_id" label="Tahun Ajaran" required>
                    <option value="">Pilih Tahun Ajaran</option>
                    @foreach($this->academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model.live.debounce.300ms="searchStudent" label="Cari Siswa" placeholder="Nama/NIS..." icon="magnifying-glass" />
            </div>
        </x-card>

        <!-- Step 2: Select Students -->
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">2. Pilih Siswa</h3>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-500">{{ count($selectedStudents) }} siswa dipilih</span>
                        <flux:button type="button" size="sm" variant="ghost" wire:click="selectAll">
                            Pilih Semua
                        </flux:button>
                        <flux:button type="button" size="sm" variant="ghost" wire:click="deselectAll">
                            Batal Pilih
                        </flux:button>
                    </div>
                </div>
            </x-slot:header>
            
            @if($filterClassroom)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="text-left py-3 px-4 w-12">
                                    <input type="checkbox" 
                                        class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                        @checked(count($selectedStudents) === $this->students->count() && $this->students->count() > 0)
                                        wire:click="{{ count($selectedStudents) === $this->students->count() ? 'deselectAll' : 'selectAll' }}">
                                </th>
                                <th class="text-left py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">NIS</th>
                                <th class="text-left py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">Nama</th>
                                <th class="text-left py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">Kelas</th>
                                <th class="text-left py-3 px-4 font-medium text-zinc-600 dark:text-zinc-400">Jurusan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse($this->students as $student)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer" wire:click="toggleStudent({{ $student->id }})">
                                    <td class="py-3 px-4">
                                        <input type="checkbox" 
                                            class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                            @checked(in_array($student->id, $selectedStudents))
                                            wire:click.stop="toggleStudent({{ $student->id }})">
                                    </td>
                                    <td class="py-3 px-4 text-zinc-900 dark:text-white">{{ $student->nis }}</td>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-zinc-900 dark:text-white">{{ $student->name }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-zinc-600 dark:text-zinc-400">{{ $student->classroom?->name }}</td>
                                    <td class="py-3 px-4 text-zinc-600 dark:text-zinc-400">{{ $student->department?->name }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-zinc-500">
                                        Tidak ada siswa di kelas ini
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($this->students->hasPages())
                    <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3">
                        {{ $this->students->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12 text-zinc-500">
                    <svg class="size-12 mx-auto text-zinc-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <p>Pilih kelas asal terlebih dahulu</p>
                </div>
            @endif
        </x-card>

        <!-- Step 3: Select Target Classroom -->
        @if(count($selectedStudents) > 0)
            <x-card>
                <x-slot:header>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">3. Kelas Tujuan</h3>
                </x-slot:header>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="target_classroom_id" label="Kelas Tujuan" required>
                        <option value="">Pilih Kelas Tujuan</option>
                        @foreach($this->targetClassrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="mutation_date" type="date" label="Tanggal Kenaikan" required />
                    <flux:input wire:model="effective_date" type="date" label="Tanggal Efektif" required />
                </div>
                <div class="mt-4">
                    <flux:textarea wire:model="notes" label="Catatan" rows="2" placeholder="Catatan tambahan..." />
                </div>
            </x-card>

            <!-- Summary -->
            <x-card>
                <x-slot:header>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Ringkasan</h3>
                </x-slot:header>
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                            <svg class="size-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-zinc-900 dark:text-white">{{ count($selectedStudents) }} siswa akan dinaikkan</div>
                            @if($target_classroom_id)
                                @php $targetClass = \App\Models\Classroom::find($target_classroom_id); @endphp
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">Ke kelas: {{ $targetClass?->name }}</div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="space-y-1 max-h-32 overflow-y-auto">
                        @foreach($this->selectedStudentsData as $student)
                            <div class="flex items-center justify-between text-sm py-1">
                                <span class="text-zinc-700 dark:text-zinc-300">{{ $student->name }}</span>
                                <span class="text-zinc-500">{{ $student->classroom?->name }} → {{ $targetClass?->name ?? '?' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-card>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <flux:button type="button" :href="route('students.mutations.index')" variant="ghost" wire:navigate>
                    Batal
                </flux:button>
                <flux:button type="submit" variant="primary" icon="check" class="bg-linear-to-r! from-green-600! to-emerald-600!">
                    Proses Kenaikan Kelas
                </flux:button>
            </div>
        @endif
    </form>
</div>
