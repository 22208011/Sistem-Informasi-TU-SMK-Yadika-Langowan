<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Livewire\Concerns\WithNotification;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Kelas')] class extends Component {
    use WithPagination;
    use WithNotification;

    public bool $showModal = false;
    public ?Classroom $editing = null;

    public string $name = '';
    public string $grade = 'X';
    public string|int|null $department_id = null;
    public string|int|null $academic_year_id = null;
    public string|int|null $homeroom_teacher_id = null;
    public int $capacity = 36;
    public string $room = '';
    public bool $is_active = true;

    public string $search = '';
    public string $filterGrade = '';
    public string|int|null $filterDepartment = null;

    public function mount(): void
    {
        // Set default academic year to active one
        $activeYear = AcademicYear::getActive();
        $this->academic_year_id = $activeYear?->id;
    }

    public function rules(): array
    {
        $nameUniqueRule = Rule::unique('classrooms', 'name')
            ->where(fn($query) => $query->where('academic_year_id', $this->academic_year_id));

        if ($this->editing) {
            $nameUniqueRule = $nameUniqueRule->ignore($this->editing->id);
        }

        return [
            'name' => ['required', 'string', 'max:20', $nameUniqueRule],
            'grade' => ['required', 'in:X,XI,XII'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'homeroom_teacher_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    #[Computed]
    public function teachers()
    {
        // Only show employees/guru/pegawai for homeroom teacher selection
        return \App\Models\Employee::where('is_active', true)
            ->where(function($q) {
                $q->where('employee_type', 'guru')->orWhere('employee_type', 'keduanya');
            })
            ->whereNotNull('user_id')
            ->orderBy('name')
            ->get(['id', 'name', 'user_id']);
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama kelas sudah digunakan pada tahun ajaran yang sama.',
        ];
    }

    #[Computed]
    public function classrooms()
    {
        return Classroom::query()
            ->with(['department', 'academicYear', 'homeroomTeacher'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterGrade, fn($q) => $q->where('grade', $this->filterGrade))
            ->when($this->filterDepartment, fn($q) => $q->where('department_id', $this->filterDepartment))
            ->orderBy('grade')
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function departments()
    {
        return Department::active()->orderBy('code')->get();
    }

    #[Computed]
    public function academicYears()
    {
        return AcademicYear::orderByDesc('name')->orderBy('semester')->get();
    }

    public function create(): void
    {
        $this->resetForm();
        // Set default to active academic year
        $activeYear = AcademicYear::getActive();
        $this->academic_year_id = $activeYear?->id;
        $this->showModal = true;
    }

    public function edit(Classroom $classroom): void
    {
        $this->editing = $classroom;
        $this->name = $classroom->name;
        $this->grade = $classroom->grade;
        $this->department_id = $classroom->department_id;
        $this->academic_year_id = $classroom->academic_year_id;
        $this->homeroom_teacher_id = $classroom->homeroom_teacher_id;
        $this->capacity = $classroom->capacity;
        $this->room = $classroom->room ?? '';
        $this->is_active = $classroom->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        // Convert empty strings to null for nullable integer fields
        $this->department_id = $this->department_id === '' ? null : $this->department_id;
        $this->academic_year_id = $this->academic_year_id === '' ? null : $this->academic_year_id;
        $this->homeroom_teacher_id = $this->homeroom_teacher_id === '' ? null : $this->homeroom_teacher_id;

        // Keep classroom name consistent: exactly one grade prefix.
        $this->name = $this->normalizedClassName($this->name, $this->grade);

        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
            $this->success('Kelas berhasil diperbarui.');
        } else {
            Classroom::create($validated);
            $this->success('Kelas berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(Classroom $classroom): void
    {
        // Check if classroom has students
        if ($classroom->students()->exists()) {
            $this->error('Tidak dapat menghapus kelas yang masih memiliki siswa.');
            return;
        }

        $classroom->delete();
        $this->success('Kelas berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->editing = null;
        $this->name = '';
        $this->grade = 'X';
        $this->department_id = null;
        $this->academic_year_id = null;
        $this->homeroom_teacher_id = null;
        $this->capacity = 36;
        $this->room = '';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
    }

    public function updatedDepartmentId(): void
    {
        // Auto-generate class name when department changes
        $this->generateClassName();
    }

    public function updatedGrade(): void
    {
        // Auto-generate class name when grade changes
        $this->generateClassName();
    }

    private function generateClassName(): void
    {
        if ($this->department_id && $this->grade) {
            $department = Department::find($this->department_id);
            if ($department) {
                // Count existing classes for this combination
                $count = Classroom::where('grade', $this->grade)
                    ->where('department_id', $this->department_id)
                    ->when($this->academic_year_id, fn($q) => $q->where('academic_year_id', $this->academic_year_id))
                    ->count();

                $this->name = $this->normalizedClassName($department->code . ' ' . ($count + 1), $this->grade);
            }
        }
    }

    private function normalizedClassName(string $name, string $grade): string
    {
        $cleanName = trim(preg_replace('/\s+/', ' ', $name));
        $cleanName = trim(preg_replace('/^(?:(?:XII|XI|X)\s+)+/i', '', $cleanName));

        if ($cleanName === '') {
            return $grade;
        }

        return trim($grade . ' ' . $cleanName);
    }
}; ?>

<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Kelas') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Kelola data kelas per jurusan dan tahun ajaran.') }}</flux:text>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">
            {{ __('Tambah Kelas') }}
        </flux:button>
    </div>

    {{-- Notification --}}
    <x-notification-alert :type="$notificationType" :message="$notificationMessage" />

    <flux:card>
        <flux:card.header>
            <div class="flex flex-col sm:flex-row gap-4 w-full">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Cari kelas...') }}"
                    icon="magnifying-glass"
                    class="sm:max-w-xs"
                />

                <flux:select wire:model.live="filterGrade" class="sm:max-w-xs">
                    <option value="">Semua Tingkat</option>
                    @foreach (App\Models\Classroom::GRADES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterDepartment" class="sm:max-w-xs">
                    <option value="">Semua Jurusan</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->code }} - {{ $dept->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card.header>

        <flux:card.body class="p-0">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Nama Kelas') }}</flux:table.column>
                    <flux:table.column>{{ __('Tingkat') }}</flux:table.column>
                    <flux:table.column>{{ __('Jurusan') }}</flux:table.column>
                    <flux:table.column>{{ __('Tahun Ajaran') }}</flux:table.column>
                    <flux:table.column>{{ __('Wali Kelas') }}</flux:table.column>
                    <flux:table.column>{{ __('Kapasitas') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Aksi') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->classrooms as $classroom)
                        <flux:table.row wire:key="class-{{ $classroom->id }}">
                            <flux:table.cell class="font-medium">{{ $classroom->name }}</flux:table.cell>
                            <flux:table.cell>{{ App\Models\Classroom::GRADES[$classroom->grade] ?? $classroom->grade }}</flux:table.cell>
                            <flux:table.cell>{{ $classroom->department?->code }}</flux:table.cell>
                            <flux:table.cell>
                                {{ $classroom->academicYear?->name }}
                                <span class="text-xs text-zinc-500">({{ ucfirst($classroom->academicYear?->semester) }})</span>
                            </flux:table.cell>
                            <flux:table.cell>{{ $classroom->homeroomTeacher?->name ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $classroom->capacity }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($classroom->is_active)
                                    <flux:badge color="green">Aktif</flux:badge>
                                @else
                                    <flux:badge color="zinc">Tidak Aktif</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="edit({{ $classroom->id }})" icon="pencil">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            wire:click="delete({{ $classroom->id }})"
                                            wire:confirm="{{ __('Apakah Anda yakin ingin menghapus kelas ini?') }}"
                                            icon="trash"
                                            variant="danger"
                                        >
                                            {{ __('Hapus') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center py-8">
                                <flux:text class="text-zinc-500">{{ __('Belum ada data kelas.') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card.body>

        @if ($this->classrooms->hasPages())
            <flux:card.footer>
                {{ $this->classrooms->links() }}
            </flux:card.footer>
        @endif
    </flux:card>

    <!-- Modal Form -->
    <flux:modal wire:model="showModal" class="max-w-lg">
        <form wire:submit="save">
            <flux:modal.header>
                <flux:heading size="lg">
                    {{ $editing ? __('Edit Kelas') : __('Tambah Kelas') }}
                </flux:heading>
            </flux:modal.header>

            <flux:modal.body class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model.live="grade" label="{{ __('Tingkat') }}" required>
                        @foreach (App\Models\Classroom::GRADES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input
                        wire:model="name"
                        label="{{ __('Nama/Nomor Kelas') }}"
                        placeholder="RPL 1"
                        required
                    />
                </div>

                <flux:select wire:model.live="department_id" label="{{ __('Jurusan') }}" required>
                    <option value="">-- Pilih Jurusan --</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->code }} - {{ $dept->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="academic_year_id" label="{{ __('Tahun Ajaran') }}" required>
                    <option value="">-- Pilih Tahun Ajaran --</option>
                    @foreach ($this->academicYears as $year)
                        <option value="{{ $year->id }}">
                            {{ $year->name }} - {{ ucfirst($year->semester) }}
                            @if ($year->is_active) (Aktif) @endif
                        </option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="homeroom_teacher_id" label="{{ __('Wali Kelas') }}">
                    <option value="">-- Pilih Wali Kelas --</option>
                    @foreach ($this->teachers as $teacher)
                        <option value="{{ $teacher->user_id }}">{{ $teacher->name }}</option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="capacity"
                        label="{{ __('Kapasitas') }}"
                        type="number"
                        min="1"
                        max="50"
                        required
                    />

                    <flux:input
                        wire:model="room"
                        label="{{ __('Ruangan') }}"
                        placeholder="R.101"
                    />
                </div>

                <flux:checkbox
                    wire:model="is_active"
                    label="{{ __('Kelas aktif') }}"
                />
            </flux:modal.body>

            <flux:modal.footer>
                <flux:button type="button" wire:click="closeModal" variant="ghost">
                    {{ __('Batal') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $editing ? __('Perbarui') : __('Simpan') }}
                </flux:button>
            </flux:modal.footer>
        </form>
    </flux:modal>
</div>

@script
<script>
    $wire.on('scroll-to-top', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>
@endscript
