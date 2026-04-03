<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentMutation;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Mutasi Masuk - Siswa Pindahan')] class extends Component {
    use WithFileUploads;

    // Basic Info
    public string $nis = '';
    public string $nisn = '';
    public string $name = '';
    public string $gender = 'L';
    public string $place_of_birth = '';
    public string $date_of_birth = '';
    public string $religion = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';

    // Academic Info
    public string $previous_school = '';
    public string $entry_year = '';
    public ?int $classroom_id = null;
    public ?int $department_id = null;
    public ?int $academic_year_id = null;

    // Photo
    public $photo = null;

    // Guardians
    public array $guardians = [];

    // Mutation Info
    public string $mutation_date = '';
    public string $effective_date = '';
    public string $document_number = '';
    public string $transfer_reason = '';
    public string $notes = '';

    public function mount(): void
    {
        $this->entry_year = now()->year;
        $this->mutation_date = now()->format('Y-m-d');
        $this->effective_date = now()->format('Y-m-d');
        
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->academic_year_id = $activeYear?->id;
        
        $this->addGuardian();
        $this->generateNIS();
    }

    public function generateNIS(): void
    {
        $lastStudent = Student::where('entry_year', $this->entry_year)
            ->orderByDesc('nis')
            ->first();
        
        if ($lastStudent) {
            $lastNumber = (int) substr($lastStudent->nis, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        $this->nis = $this->entry_year . $newNumber;
    }

    public function rules(): array
    {
        return [
            'nis' => 'required|string|max:20|unique:students,nis',
            'nisn' => 'nullable|string|max:20|unique:students,nisn',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'place_of_birth' => 'required|string|max:100',
            'date_of_birth' => 'required|date|before:today',
            'religion' => 'required|string',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'previous_school' => 'required|string|max:255',
            'entry_year' => 'required|integer|min:2000|max:2100',
            'classroom_id' => 'required|exists:classrooms,id',
            'department_id' => 'required|exists:departments,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'photo' => 'nullable|image|max:2048',
            'mutation_date' => 'required|date',
            'effective_date' => 'required|date',
            'document_number' => 'nullable|string|max:50',
            'transfer_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'guardians' => 'required|array|min:1',
            'guardians.*.relationship' => 'required|string',
            'guardians.*.name' => 'required|string|max:255',
            'guardians.*.nik' => 'nullable|string|max:20',
            'guardians.*.phone' => 'nullable|string|max:20',
            'guardians.*.occupation' => 'nullable|string|max:100',
            'guardians.*.address' => 'nullable|string',
            'guardians.*.is_primary' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'previous_school.required' => 'Asal sekolah wajib diisi untuk siswa pindahan.',
            'nis.required' => 'NIS wajib diisi.',
            'nis.unique' => 'NIS sudah digunakan.',
            'name.required' => 'Nama siswa wajib diisi.',
            'classroom_id.required' => 'Kelas tujuan wajib dipilih.',
            'department_id.required' => 'Jurusan wajib dipilih.',
            'guardians.required' => 'Minimal satu data orang tua/wali wajib diisi.',
            'guardians.*.name.required' => 'Nama orang tua/wali wajib diisi.',
        ];
    }

    public function addGuardian(): void
    {
        $isPrimary = empty($this->guardians);
        $this->guardians[] = [
            'relationship' => 'ayah',
            'name' => '',
            'nik' => '',
            'phone' => '',
            'occupation' => '',
            'address' => '',
            'is_primary' => $isPrimary,
        ];
    }

    public function removeGuardian(int $index): void
    {
        if (count($this->guardians) > 1) {
            $wasPrimary = $this->guardians[$index]['is_primary'] ?? false;
            unset($this->guardians[$index]);
            $this->guardians = array_values($this->guardians);
            
            if ($wasPrimary && !empty($this->guardians)) {
                $this->guardians[0]['is_primary'] = true;
            }
        }
    }

    public function setPrimaryGuardian(int $index): void
    {
        foreach ($this->guardians as $i => $guardian) {
            $this->guardians[$i]['is_primary'] = ($i === $index);
        }
    }

    #[Computed]
    public function classrooms()
    {
        return Classroom::query()
            ->active()
            ->when($this->department_id, fn($q) => $q->where('department_id', $this->department_id))
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

    public function updatedDepartmentId(): void
    {
        $this->classroom_id = null;
    }

    public function updatedEntryYear(): void
    {
        $this->generateNIS();
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            $photoPath = null;
            if ($this->photo) {
                $photoPath = $this->photo->store('students', 'public');
            }

            // Create student
            $student = Student::create([
                'nis' => $this->nis,
                'nisn' => $this->nisn ?: null,
                'name' => $this->name,
                'gender' => $this->gender,
                'place_of_birth' => $this->place_of_birth,
                'date_of_birth' => $this->date_of_birth,
                'religion' => $this->religion,
                'address' => $this->address,
                'phone' => $this->phone ?: null,
                'email' => $this->email ?: null,
                'previous_school' => $this->previous_school,
                'entry_year' => $this->entry_year,
                'status' => 'aktif',
                'classroom_id' => $this->classroom_id,
                'department_id' => $this->department_id,
                'academic_year_id' => $this->academic_year_id,
                'photo' => $photoPath,
            ]);

            // Create guardians
            foreach ($this->guardians as $guardianData) {
                Guardian::create([
                    'student_id' => $student->id,
                    'relationship' => $guardianData['relationship'],
                    'name' => $guardianData['name'],
                    'nik' => $guardianData['nik'] ?: null,
                    'phone' => $guardianData['phone'] ?: null,
                    'occupation' => $guardianData['occupation'] ?: null,
                    'address' => $guardianData['address'] ?: null,
                    'is_primary' => $guardianData['is_primary'] ?? false,
                ]);
            }

            // Create mutation record
            StudentMutation::create([
                'student_id' => $student->id,
                'type' => StudentMutation::TYPE_MASUK,
                'mutation_date' => $this->mutation_date,
                'effective_date' => $this->effective_date,
                'reason' => $this->transfer_reason ?: 'Pindahan dari ' . $this->previous_school,
                'previous_school' => $this->previous_school,
                'new_classroom_id' => $this->classroom_id,
                'document_number' => $this->document_number ?: null,
                'notes' => $this->notes ?: null,
                'status' => StudentMutation::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'academic_year_id' => $this->academic_year_id,
            ]);
        });

        session()->flash('success', 'Data siswa pindahan berhasil disimpan.');
        $this->redirect(route('students.mutations.index'), navigate: true);
    }
}; ?>

<div>
    <!-- Page Header -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Mutasi Masuk - Siswa Pindahan') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Daftarkan siswa pindahan dari sekolah lain.') }}</x-slot:subtitle>
        <x-slot:actions>
            <flux:button :href="route('students.mutations.index')" variant="ghost" icon="arrow-left" wire:navigate>
                {{ __('Kembali') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="save" class="space-y-6">
        <!-- Transfer Info -->
        <x-card>
            <x-slot:header>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Informasi Pindahan</h3>
            </x-slot:header>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input wire:model="previous_school" label="Asal Sekolah" required placeholder="Nama sekolah asal" />
                <flux:input wire:model="mutation_date" type="date" label="Tanggal Mutasi" required />
                <flux:input wire:model="effective_date" type="date" label="Tanggal Efektif" required />
                <flux:input wire:model="document_number" label="No. Surat Pindah" placeholder="Nomor surat keterangan pindah" />
                <div class="md:col-span-2">
                    <flux:input wire:model="transfer_reason" label="Alasan Pindah" placeholder="Alasan siswa pindah sekolah" />
                </div>
            </div>
        </x-card>

        <!-- Basic Info -->
        <x-card>
            <x-slot:header>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Data Pribadi Siswa</h3>
            </x-slot:header>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input wire:model="nis" label="NIS (Baru)" required readonly class="bg-zinc-100 dark:bg-zinc-800" />
                <flux:input wire:model="nisn" label="NISN" placeholder="10 digit NISN" />
                <flux:input wire:model="name" label="Nama Lengkap" required />
                
                <flux:select wire:model="gender" label="Jenis Kelamin" required>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </flux:select>
                <flux:input wire:model="place_of_birth" label="Tempat Lahir" required />
                <flux:input wire:model="date_of_birth" type="date" label="Tanggal Lahir" required />
                
                <flux:select wire:model="religion" label="Agama" required>
                    <option value="">Pilih Agama</option>
                    @foreach(\App\Models\Student::RELIGIONS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="phone" label="No. HP" />
                <flux:input wire:model="email" type="email" label="Email" />
                
                <div class="md:col-span-3">
                    <flux:textarea wire:model="address" label="Alamat Lengkap" required rows="2" />
                </div>
            </div>
        </x-card>

        <!-- Academic Info -->
        <x-card>
            <x-slot:header>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Penempatan Akademik</h3>
            </x-slot:header>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input wire:model.live="entry_year" type="number" label="Tahun Masuk" required min="2000" max="2100" />
                <flux:select wire:model="academic_year_id" label="Tahun Ajaran" required>
                    <option value="">Pilih Tahun Ajaran</option>
                    @foreach($this->academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </flux:select>
                <div></div>
                
                <flux:select wire:model.live="department_id" label="Jurusan Tujuan" required>
                    <option value="">Pilih Jurusan</option>
                    @foreach($this->departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="classroom_id" label="Kelas Tujuan" required>
                    <option value="">Pilih Kelas</option>
                    @foreach($this->classrooms as $classroom)
                        <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                    @endforeach
                </flux:select>
                
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Foto Siswa</label>
                    <input type="file" wire:model="photo" accept="image/*" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                    @if($photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="mt-2 w-24 h-24 object-cover rounded-lg" />
                    @endif
                </div>
            </div>
        </x-card>

        <!-- Guardians -->
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Data Orang Tua/Wali</h3>
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addGuardian">
                        Tambah Wali
                    </flux:button>
                </div>
            </x-slot:header>
            <div class="space-y-6">
                @foreach($guardians as $index => $guardian)
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Wali {{ $index + 1 }}</span>
                                @if($guardian['is_primary'] ?? false)
                                    <flux:badge size="sm" color="green">Utama</flux:badge>
                                @else
                                    <flux:button type="button" size="xs" variant="ghost" wire:click="setPrimaryGuardian({{ $index }})">
                                        Jadikan Utama
                                    </flux:button>
                                @endif
                            </div>
                            @if(count($guardians) > 1)
                                <flux:button type="button" size="xs" variant="ghost" icon="trash" wire:click="removeGuardian({{ $index }})" class="text-red-600" />
                            @endif
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:select wire:model="guardians.{{ $index }}.relationship" label="Hubungan" required>
                                <option value="ayah">Ayah</option>
                                <option value="ibu">Ibu</option>
                                <option value="wali">Wali</option>
                            </flux:select>
                            <flux:input wire:model="guardians.{{ $index }}.name" label="Nama Lengkap" required />
                            <flux:input wire:model="guardians.{{ $index }}.nik" label="NIK" />
                            <flux:input wire:model="guardians.{{ $index }}.phone" label="No. HP" />
                            <flux:input wire:model="guardians.{{ $index }}.occupation" label="Pekerjaan" />
                            <flux:input wire:model="guardians.{{ $index }}.address" label="Alamat" />
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <!-- Notes -->
        <x-card>
            <x-slot:header>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Catatan Tambahan</h3>
            </x-slot:header>
            <flux:textarea wire:model="notes" rows="3" placeholder="Catatan khusus mengenai siswa pindahan ini..." />
        </x-card>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
            <flux:button type="button" :href="route('students.mutations.index')" variant="ghost" wire:navigate>
                Batal
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check" class="bg-linear-to-r! from-green-600! to-emerald-600!">
                Simpan Mutasi Masuk
            </flux:button>
        </div>
    </form>
</div>
