<?php

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\AcademicYear;
use App\Livewire\Concerns\WithNotification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Jadwal Pelajaran')] class extends Component {
    use WithPagination;
    use WithNotification;

    public $classroom_id = '';
    public $day = '';
    public $teacher_id = '';

    // Form fields
    public $showForm = false;
    public $editingId = null;
    public $form_classroom_id = '';
    public $form_subject_id = '';
    public $form_teacher_id = '';
    public $form_day = '';
    public $start_time = '';
    public $end_time = '';
    public $room = '';

    protected function rules()
    {
        return [
            'form_classroom_id' => 'required|exists:classrooms,id',
            'form_subject_id' => 'required|exists:subjects,id',
            'form_teacher_id' => 'required|exists:employees,id',
            'form_day' => 'required|in:1,2,3,4,5,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:50',
        ];
    }

    public function mount()
    {
        // For teachers, show only their schedule by default
        if (auth()->user()->isTeacher() && auth()->user()->employee) {
            $this->teacher_id = auth()->user()->employee_id;
        }
    }

    public function openForm($id = null)
    {
        $this->resetForm();
        $this->showForm = true;

        if ($id) {
            $schedule = Schedule::findOrFail($id);
            $this->editingId = $id;
            $this->form_classroom_id = $schedule->classroom_id;
            $this->form_subject_id = $schedule->subject_id;
            $this->form_teacher_id = $schedule->teacher_id;
            $this->form_day = $schedule->day_of_week;
            $this->start_time = $schedule->start_time?->format('H:i');
            $this->end_time = $schedule->end_time?->format('H:i');
            $this->room = $schedule->room;
        }
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->form_classroom_id = '';
        $this->form_subject_id = '';
        $this->form_teacher_id = '';
        $this->form_day = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->room = '';
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        $academicYear = AcademicYear::where('is_active', true)->first();

        $data = [
            'academic_year_id' => $academicYear?->id,
            'semester' => $academicYear?->semester === 'genap' ? 2 : 1,
            'classroom_id' => $this->form_classroom_id,
            'subject_id' => $this->form_subject_id,
            'teacher_id' => $this->form_teacher_id,
            'day_of_week' => $this->form_day,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'room' => $this->room,
        ];

        if ($this->editingId) {
            Schedule::findOrFail($this->editingId)->update($data);
            $this->success('Jadwal berhasil diperbarui.');
        } else {
            Schedule::create($data);
            $this->success('Jadwal berhasil ditambahkan.');
        }

        $this->closeForm();
    }

    public function delete($id)
    {
        try {
            Schedule::findOrFail($id)->delete();
            $this->success('Jadwal berhasil dihapus.');
        } catch (\Exception $e) {
            $this->error('Gagal menghapus jadwal: ' . $e->getMessage());
        }
    }

    public function with(): array
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        $query = Schedule::query()
            ->with(['classroom', 'subject', 'teacher'])
            ->when($academicYear, fn($q) => $q->where('academic_year_id', $academicYear->id));

        // Filter for teachers to only see their own schedule
        if (auth()->user()->isTeacher() && !auth()->user()->hasPermission('schedule.view')) {
            $query->where('teacher_id', auth()->user()->employee_id);
        } else {
            if ($this->teacher_id) {
                $query->where('teacher_id', $this->teacher_id);
            }
        }

        if ($this->classroom_id) {
            $query->where('classroom_id', $this->classroom_id);
        }

        if ($this->day) {
            $query->where('day_of_week', $this->day);
        }

        $schedules = $query->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Group by day for display
        $groupedSchedules = $schedules->groupBy('day_of_week');

        return [
            'groupedSchedules' => $groupedSchedules,
            'schedules' => $schedules,
            'classrooms' => Classroom::with('department')->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'teachers' => Employee::where('employee_type', 'guru')
                ->orWhere('employee_type', 'keduanya')
                ->orderBy('name')
                ->get(),
            'days' => [
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
            ],
            'academicYear' => $academicYear,
            'canManage' => auth()->user()->hasPermission('schedule.create'),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Jadwal Pelajaran') }}</x-slot:title>
        <x-slot:subtitle>
            @if ($academicYear)
                Tahun Ajaran {{ $academicYear->name }}
            @else
                Belum ada tahun ajaran aktif
            @endif
        </x-slot:subtitle>
        <x-slot:actions>
            @if ($canManage)
            <flux:button icon="plus" wire:click="openForm" class="rounded-xl! bg-linear-to-r! from-blue-600! to-indigo-600! hover:from-blue-700! hover:to-indigo-700! shadow-lg! shadow-blue-500!/25">
                Tambah Jadwal
            </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Notification --}}
    <x-notification-alert :type="$notificationType" :message="$notificationMessage" />

        <!-- Filters -->
        <x-elegant-card>
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select wire:model.live="classroom_id" class="rounded-xl!">
                    <option value="">Semua Kelas</option>
                    @foreach ($classrooms as $classroom)
                        <option value="{{ $classroom->id }}">{{ $classroom->name }} - {{ $classroom->department?->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="day" class="rounded-xl!">
                    <option value="">Semua Hari</option>
                    @foreach ($days as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                @if(auth()->user()->hasPermission('schedule.view'))
                <flux:select wire:model.live="teacher_id" class="rounded-xl!">
                    <option value="">Semua Guru</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </flux:select>
                @endif
            </div>
        </x-elegant-card>

        <!-- Schedule Display -->
        @if ($groupedSchedules->isEmpty())
            <x-elegant-card>
                <div class="py-12 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                        <svg class="size-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <flux:heading size="lg">Belum Ada Jadwal</flux:heading>
                    <flux:subheading>Jadwal pelajaran belum tersedia untuk filter yang dipilih.</flux:subheading>
                </div>
            </x-elegant-card>
        @else
            <div class="space-y-6">
                @foreach ($days as $dayKey => $dayLabel)
                    @if ($groupedSchedules->has($dayKey))
                        <x-elegant-card :noPadding="true" class="animate-fade-in-up">
                            <x-slot:header>
                                <div class="flex items-center gap-2">
                                    <div class="w-10 h-10 rounded-xl bg-linear-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                                        <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-zinc-800 dark:text-white">{{ $dayLabel }}</span>
                                    <flux:badge color="blue" class="rounded-lg!">{{ $groupedSchedules[$dayKey]->count() }} Jadwal</flux:badge>
                                </div>
                            </x-slot:header>

                            <flux:table class="table-elegant">
                                <flux:table.columns>
                                    <flux:table.column class="font-semibold!">Waktu</flux:table.column>
                                    <flux:table.column class="font-semibold!">Mata Pelajaran</flux:table.column>
                                    <flux:table.column class="font-semibold!">Kelas</flux:table.column>
                                    <flux:table.column class="font-semibold!">Guru</flux:table.column>
                                    <flux:table.column class="font-semibold!">Ruangan</flux:table.column>
                                    @if ($canManage)
                                    <flux:table.column class="text-right font-semibold!">Aksi</flux:table.column>
                                    @endif
                                </flux:table.columns>
                                <flux:table.rows>
                                    @foreach ($groupedSchedules[$dayKey] as $schedule)
                                        <flux:table.row wire:key="schedule-{{ $schedule->id }}" class="hover:bg-blue-50/50! dark:hover:bg-blue-900/10! transition-colors">
                                            <flux:table.cell>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-sm font-medium">
                                                    {{ $schedule->start_time?->format('H:i') }} - {{ $schedule->end_time?->format('H:i') }}
                                                </span>
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                <span class="font-medium text-zinc-800 dark:text-white">{{ $schedule->subject?->name ?? '-' }}</span>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">({{ $schedule->subject?->code }})</span>
                                            </flux:table.cell>
                                            <flux:table.cell class="text-zinc-600 dark:text-zinc-400">
                                                {{ $schedule->classroom?->name ?? '-' }}
                                            </flux:table.cell>
                                            <flux:table.cell class="text-zinc-600 dark:text-zinc-400">
                                                {{ $schedule->teacher?->name ?? '-' }}
                                            </flux:table.cell>
                                            <flux:table.cell class="text-zinc-600 dark:text-zinc-400">
                                                {{ $schedule->room ?? '-' }}
                                            </flux:table.cell>
                                            @if ($canManage)
                                            <flux:table.cell class="text-right">
                                                <flux:dropdown position="bottom" align="end">
                                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="rounded-lg! hover:bg-zinc-100! dark:hover:bg-zinc-800!" />
                                                    <flux:menu class="rounded-xl! shadow-xl! border-zinc!-200/50 dark:border-zinc!-700/50">
                                                        <flux:menu.item wire:click="openForm({{ $schedule->id }})" icon="pencil" class="rounded-lg!">
                                                            {{ __('Edit') }}
                                                        </flux:menu.item>
                                                        <flux:menu.separator />
                                                        <flux:menu.item
                                                            wire:click="delete({{ $schedule->id }})"
                                                            wire:confirm="Yakin ingin menghapus jadwal ini?"
                                                            icon="trash"
                                                            variant="danger"
                                                            class="rounded-lg!"
                                                        >
                                                            {{ __('Hapus') }}
                                                        </flux:menu.item>
                                                    </flux:menu>
                                                </flux:dropdown>
                                            </flux:table.cell>
                                            @endif
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </x-elegant-card>
                    @endif
                @endforeach
            </div>
        @endif

        <!-- Form Modal -->
        <flux:modal name="schedule-form" wire:model="showForm" class="max-w-xl">
            <form wire:submit="save" class="space-y-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-blue-500 to-indigo-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <flux:heading size="lg">
                        {{ $editingId ? 'Edit Jadwal' : 'Tambah Jadwal' }}
                    </flux:heading>
                </div>

                <flux:select wire:model="form_classroom_id" label="Kelas" required class="rounded-xl!">
                    <option value="">Pilih Kelas</option>
                    @foreach ($classrooms as $classroom)
                        <option value="{{ $classroom->id }}">{{ $classroom->name }} - {{ $classroom->department?->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form_subject_id" label="Mata Pelajaran" required class="rounded-xl!">
                    <option value="">Pilih Mata Pelajaran</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form_teacher_id" label="Guru Pengajar" required class="rounded-xl!">
                    <option value="">Pilih Guru</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form_day" label="Hari" required class="rounded-xl!">
                    <option value="">Pilih Hari</option>
                    @foreach ($days as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input type="time" wire:model="start_time" label="Jam Mulai" required class="rounded-xl!" />
                    <flux:input type="time" wire:model="end_time" label="Jam Selesai" required class="rounded-xl!" />
                </div>

                <flux:input wire:model="room" label="Ruangan (Opsional)" placeholder="Contoh: Lab Komputer, R.201" class="rounded-xl!" />

                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button type="button" variant="ghost" wire:click="closeForm" class="rounded-xl!">Batal</flux:button>
                    <flux:button type="submit" class="rounded-xl! bg-linear-to-r! from-blue-600! to-indigo-600!">
                        <svg class="size-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ $editingId ? 'Perbarui' : 'Simpan' }}
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    </div>
</div>
