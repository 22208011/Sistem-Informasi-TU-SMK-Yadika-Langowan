<?php

use App\Models\Graduate;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Classroom;
use App\Livewire\Concerns\WithNotification;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Data Lulusan')] class extends Component {
    use WithPagination, WithNotification;

    public $search = '';
    public $graduation_status = '';
    public $academic_year_id = '';
    public $classroom_id = '';

    // Form fields
    public $showForm = false;
    public $editingId = null;
    public $student_id = '';
    public $form_academic_year_id = '';
    public $graduation_date = '';
    public $form_graduation_status = 'pending';
    public $final_score = '';
    public $predicate = '';
    public $achievements = '';
    public $notes = '';

    protected function rules()
    {
        return [
            'student_id' => 'required|exists:students,id|unique:graduates,student_id,' . $this->editingId . ',id,academic_year_id,' . $this->form_academic_year_id,
            'form_academic_year_id' => 'required|exists:academic_years,id',
            'graduation_date' => 'required|date',
            'form_graduation_status' => 'required|in:lulus,tidak_lulus,pending',
            'final_score' => 'nullable|numeric|min:0|max:100',
            'predicate' => 'nullable|string|max:50',
            'achievements' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function mount()
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
            $this->form_academic_year_id = $activeYear->id;
        }
        $this->graduation_date = now()->format('Y-m-d');
    }

    public function openForm($id = null)
    {
        $this->resetForm();
        $this->showForm = true;

        if ($id) {
            $graduate = Graduate::findOrFail($id);
            $this->editingId = $id;
            $this->student_id = $graduate->student_id;
            $this->form_academic_year_id = $graduate->academic_year_id;
            $this->graduation_date = $graduate->graduation_date->format('Y-m-d');
            $this->form_graduation_status = $graduate->graduation_status;
            $this->final_score = $graduate->final_score;
            $this->predicate = $graduate->predicate;
            $this->achievements = $graduate->achievements;
            $this->notes = $graduate->notes;
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
        $this->student_id = '';
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->form_academic_year_id = $activeYear?->id ?? '';
        $this->graduation_date = now()->format('Y-m-d');
        $this->form_graduation_status = 'pending';
        $this->final_score = '';
        $this->predicate = '';
        $this->achievements = '';
        $this->notes = '';
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'student_id' => $this->student_id,
            'academic_year_id' => $this->form_academic_year_id,
            'graduation_date' => $this->graduation_date,
            'graduation_status' => $this->form_graduation_status,
            'final_score' => $this->final_score ?: null,
            'predicate' => $this->predicate ?: null,
            'achievements' => $this->achievements ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->editingId) {
            $graduate = Graduate::findOrFail($this->editingId);
            $graduate->update($data);
            $this->success('Data lulusan berhasil diperbarui.');
        } else {
            Graduate::create($data);
            $this->success('Data lulusan berhasil ditambahkan.');
        }

        $this->closeForm();
    }

    public function delete($id)
    {
        $graduate = Graduate::findOrFail($id);
        $graduate->delete();
        $this->success('Data lulusan berhasil dihapus.');
    }

    public function markAsPassed($id)
    {
        Graduate::where('id', $id)->update([
            'graduation_status' => 'lulus',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        $this->success('Status siswa berhasil diubah menjadi Lulus.');
    }

    public function markAsNotPassed($id)
    {
        Graduate::where('id', $id)->update([
            'graduation_status' => 'tidak_lulus',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        $this->success('Status siswa berhasil diubah menjadi Tidak Lulus.');
    }

    public function with(): array
    {
        $query = Graduate::query()->with(['student.classroom', 'academicYear']);

        if ($this->search) {
            $query->whereHas('student', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('nis', 'like', '%' . $this->search . '%')
                    ->orWhere('nisn', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->graduation_status) {
            $query->where('graduation_status', $this->graduation_status);
        }

        if ($this->academic_year_id) {
            $query->where('academic_year_id', $this->academic_year_id);
        }

        if ($this->classroom_id) {
            $query->whereHas('student', function ($q) {
                $q->where('classroom_id', $this->classroom_id);
            });
        }

        // Get students eligible for graduation (grade 12, not yet in graduates table)
        $existingGraduateStudentIds = Graduate::pluck('student_id')->toArray();
        $eligibleStudents = Student::where('status', 'aktif')
            ->whereNotIn('id', $existingGraduateStudentIds)
            ->whereHas('classroom', function ($q) {
                $q->where('grade', Classroom::GRADE_XII);
            })
            ->with('classroom')
            ->orderBy('classroom_id')
            ->orderBy('name')
            ->get()
            ->groupBy(fn($s) => $s->classroom?->name ?? 'Tanpa Kelas');

        $baseQuery = Graduate::query()->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id));

        return [
            'graduates' => $query->latest()->paginate(15),
            'statuses' => Graduate::STATUSES,
            'academicYears' => AcademicYear::orderBy('start_date', 'desc')->get(),
            'classrooms' => Classroom::where('grade', Classroom::GRADE_XII)->orderBy('name')->get(),
            'eligibleStudents' => $eligibleStudents,
            'totalGraduates' => (clone $baseQuery)->count(),
            'passedCount' => (clone $baseQuery)->where('graduation_status', 'lulus')->count(),
            'notPassedCount' => (clone $baseQuery)->where('graduation_status', 'tidak_lulus')->count(),
            'pendingCount' => (clone $baseQuery)->where('graduation_status', 'pending')->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Data Lulusan') }}</x-slot:title>
        <x-slot:subtitle>Kelola data kelulusan siswa</x-slot:subtitle>
        <x-slot:actions>
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('graduates.create'))
            <flux:button icon="plus" wire:click="openForm" class="rounded-xl! bg-linear-to-r! from-blue-600! to-indigo-600! hover:from-blue-700! hover:to-indigo-700! shadow-lg! shadow-blue-500!/25">
                Tambah Data Lulusan
            </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Notification --}}
    <x-notification-alert :type="$notificationType" :message="$notificationMessage" />

    <!-- Statistics -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <x-stat-card title="Total Siswa" :value="$totalGraduates" color="blue">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Lulus" :value="$passedCount" color="green">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Tidak Lulus" :value="$notPassedCount" color="red">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Menunggu" :value="$pendingCount" color="amber">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filters -->
    <x-elegant-card>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama/NIS/NISN..."
                icon="magnifying-glass"
                class="rounded-xl!"
            />
            <flux:select wire:model.live="graduation_status" class="rounded-xl!">
                <option value="">Semua Status</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="academic_year_id" class="rounded-xl!">
                <option value="">Semua Tahun</option>
                @foreach ($academicYears as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="classroom_id" class="rounded-xl!">
                <option value="">Semua Kelas</option>
                @foreach ($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </flux:select>
        </div>
    </x-elegant-card>

    <!-- Data Table -->
    <x-elegant-card :noPadding="true">
        <flux:table class="table-elegant">
            <flux:table.columns>
                <flux:table.column class="font-semibold!">NIS/NISN</flux:table.column>
                <flux:table.column class="font-semibold!">Nama Siswa</flux:table.column>
                <flux:table.column class="font-semibold!">Kelas</flux:table.column>
                <flux:table.column class="font-semibold!">Nilai Akhir</flux:table.column>
                <flux:table.column class="font-semibold!">Predikat</flux:table.column>
                <flux:table.column class="font-semibold!">Status</flux:table.column>
                <flux:table.column class="text-right font-semibold!">Aksi</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($graduates as $graduate)
                    <flux:table.row wire:key="graduate-{{ $graduate->id }}" class="hover:bg-blue-50/50! dark:hover:bg-blue-900/10! transition-colors">
                        <flux:table.cell>
                            <div>
                                <div class="font-medium text-zinc-800 dark:text-white">{{ $graduate->student?->nis }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $graduate->student?->nisn }}</div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="font-medium text-zinc-800 dark:text-white">{{ $graduate->student?->name }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-600 dark:text-zinc-400">
                            {{ $graduate->student?->classroom?->name ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($graduate->final_score)
                                <span class="font-mono font-medium text-zinc-800 dark:text-white">{{ number_format($graduate->final_score, 2) }}</span>
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-600 dark:text-zinc-400">
                            {{ $graduate->predicate ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $statusColors = ['lulus' => 'green', 'tidak_lulus' => 'red', 'pending' => 'yellow'];
                            @endphp
                            <flux:badge color="{{ $statusColors[$graduate->graduation_status] ?? 'zinc' }}" size="sm" class="rounded-lg!">
                                {{ $statuses[$graduate->graduation_status] ?? $graduate->graduation_status }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="rounded-lg! hover:bg-zinc-100! dark:hover:bg-zinc-800!" />
                                <flux:menu class="rounded-xl! shadow-xl! border-zinc-200/50! dark:border-zinc-700/50!">
                                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('graduates.update'))
                                    <flux:menu.item wire:click="openForm({{ $graduate->id }})" icon="pencil" class="rounded-lg!">
                                        Edit
                                    </flux:menu.item>
                                    @if ($graduate->graduation_status === 'pending')
                                    <flux:menu.item wire:click="markAsPassed({{ $graduate->id }})" icon="check" class="rounded-lg!">
                                        Tandai Lulus
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="markAsNotPassed({{ $graduate->id }})" icon="x-mark" class="rounded-lg!">
                                        Tandai Tidak Lulus
                                    </flux:menu.item>
                                    @endif
                                    @endif
                                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('graduates.delete'))
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        wire:click="delete({{ $graduate->id }})"
                                        wire:confirm="Yakin ingin menghapus data lulusan ini?"
                                        icon="trash"
                                        variant="danger"
                                        class="rounded-lg!"
                                    >
                                        Hapus
                                    </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <div class="py-12 text-center">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                    <svg class="size-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                    </svg>
                                </div>
                                <flux:heading size="lg">Belum Ada Data Lulusan</flux:heading>
                                <flux:subheading>Data lulusan belum tersedia untuk filter yang dipilih.</flux:subheading>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($graduates->hasPages())
        <div class="p-4 border-t border-zinc-200/60 dark:border-zinc-700/60">
            {{ $graduates->links() }}
        </div>
        @endif
    </x-elegant-card>

    <!-- Form Modal -->
    <flux:modal name="graduate-form" wire:model="showForm" class="max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-blue-500 to-indigo-600 text-white">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347" />
                    </svg>
                </div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Data Lulusan' : 'Tambah Data Lulusan' }}
                </flux:heading>
            </div>

            <flux:select wire:model="student_id" label="Siswa" required class="rounded-xl!">
                <option value="">Pilih Siswa</option>
                @if ($editingId)
                    @php $currentStudent = \App\Models\Student::find($student_id); @endphp
                    @if ($currentStudent)
                        <option value="{{ $currentStudent->id }}" selected>{{ $currentStudent->name }} ({{ $currentStudent->nis }})</option>
                    @endif
                @else
                    @foreach ($eligibleStudents as $classroomName => $students)
                        <optgroup label="{{ $classroomName }}">
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->nis }})</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                @endif
            </flux:select>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:select wire:model="form_academic_year_id" label="Tahun Pelajaran" required class="rounded-xl!">
                    <option value="">Pilih Tahun</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input
                    wire:model="graduation_date"
                    label="Tanggal Kelulusan"
                    type="date"
                    required
                    class="rounded-xl!"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <flux:input
                    wire:model="final_score"
                    label="Nilai Akhir Rata-rata"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    class="rounded-xl!"
                />
                <flux:select wire:model="predicate" label="Predikat" class="rounded-xl!">
                    <option value="">Pilih Predikat</option>
                    @foreach (Graduate::PREDICATES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="form_graduation_status" label="Status Kelulusan" required class="rounded-xl!">
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea
                wire:model="achievements"
                label="Prestasi"
                rows="2"
                placeholder="Prestasi selama sekolah..."
                class="rounded-xl!"
            />

            <flux:textarea
                wire:model="notes"
                label="Catatan"
                rows="2"
                placeholder="Catatan tambahan..."
                class="rounded-xl!"
            />

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" variant="ghost" wire:click="closeForm" class="rounded-xl!">Batal</flux:button>
                <flux:button type="submit" class="rounded-xl! bg-linear-to-r! from-blue-600! to-indigo-600! hover:from-blue-700! hover:to-indigo-700!">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
