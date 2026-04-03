<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentMutation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Pendaftaran Siswa Baru')] class extends Component {
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
    public string $entry_type = 'baru'; // baru or pindahan
    public string $mutation_date = '';
    public string $document_number = '';
    public string $notes = '';

    public function mount(): void
    {
        $this->entry_year = now()->year;
        $this->mutation_date = now()->format('Y-m-d');
        
        // Set active academic year
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->academic_year_id = $activeYear?->id;
        
        // Initialize with one guardian form
        $this->addGuardian();
        
        // Generate NIS
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
            'previous_school' => 'nullable|string|max:255',
            'entry_year' => 'required|integer|min:2000|max:2100',
            'classroom_id' => 'required|exists:classrooms,id',
            'department_id' => 'required|exists:departments,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'photo' => 'nullable|image|max:2048',
            'entry_type' => 'required|in:baru,pindahan',
            'mutation_date' => 'required|date',
            'document_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            // Guardian validation
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
            'nis.required' => 'NIS wajib diisi.',
            'nis.unique' => 'NIS sudah digunakan.',
            'name.required' => 'Nama siswa wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'place_of_birth.required' => 'Tempat lahir wajib diisi.',
            'date_of_birth.required' => 'Tanggal lahir wajib diisi.',
            'date_of_birth.before' => 'Tanggal lahir tidak valid.',
            'religion.required' => 'Agama wajib dipilih.',
            'address.required' => 'Alamat wajib diisi.',
            'classroom_id.required' => 'Kelas wajib dipilih.',
            'department_id.required' => 'Jurusan wajib dipilih.',
            'academic_year_id.required' => 'Tahun ajaran wajib dipilih.',
            'guardians.required' => 'Minimal satu data orang tua/wali wajib diisi.',
            'guardians.*.relationship.required' => 'Hubungan wajib dipilih.',
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
            // Handle photo upload
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
                'previous_school' => $this->previous_school ?: null,
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
                'effective_date' => $this->mutation_date,
                'reason' => $this->entry_type === 'pindahan' ? 'Pindahan dari ' . $this->previous_school : 'Siswa Baru',
                'previous_school' => $this->entry_type === 'pindahan' ? $this->previous_school : null,
                'new_classroom_id' => $this->classroom_id,
                'document_number' => $this->document_number ?: null,
                'notes' => $this->notes ?: null,
                'status' => StudentMutation::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'academic_year_id' => $this->academic_year_id,
            ]);
        });

        session()->flash('success', 'Pendaftaran siswa baru berhasil disimpan.');
        $this->redirect(route('students.mutations.index'), navigate: true);
    }
}; ?>

<div>
    <!-- Page Header -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Pendaftaran Siswa Baru') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Daftarkan siswa baru atau siswa pindahan ke sekolah.') }}</x-slot:subtitle>
        <x-slot:actions>
            <flux:button :href="route('students.mutations.index')" variant="ghost" icon="arrow-left" wire:navigate>
                {{ __('Kembali') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="save" class="space-y-6">
        <!-- Entry Type -->
        <x-card>
            <x-slot:header>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Tipe Pendaftaran</h3>
            </x-slot:header>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:select wire:model.live="entry_type" label="Tipe Siswa" required>
                    <option value="baru">Siswa Baru</option>
                    <option value="pindahan">Siswa Pindahan</option>
                </flux:select>
                <flux:input wire:model="mutation_date" type="date" label="Tanggal Pendaftaran" required />
                <flux:input wire:model="document_number" label="No. Dokumen/Formulir" placeholder="Opsional" />
            </div>
        </x-card>

        <!-- Basic Info -->
        <x-card>
            <x-slot:header>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Data Pribadi Siswa</h3>
            </x-slot:header>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input wire:model="nis" label="NIS" required readonly class="bg-zinc-100 dark:bg-zinc-800" />
                <flux:input wire:model="nisn" label="NISN" placeholder="10 digit NISN" />
                <flux:input wire:model="name" label="Nama Lengkap" required placeholder="Nama sesuai akta kelahiran" />
                
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
                <flux:input wire:model="phone" label="No. HP" placeholder="08xxxxxxxxxx" />
                <flux:input wire:model="email" type="email" label="Email" placeholder="email@example.com" />
                
                <div class="md:col-span-3">
                    <flux:textarea wire:model="address" label="Alamat Lengkap" required rows="2" placeholder="Alamat tempat tinggal saat ini" />
                </div>
            </div>
        </x-card>

        <!-- Academic Info -->
        <x-card>
            <x-slot:header>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Data Akademik</h3>
            </x-slot:header>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input wire:model.live="entry_year" type="number" label="Tahun Masuk" required min="2000" max="2100" />
                <flux:select wire:model="academic_year_id" label="Tahun Ajaran" required>
                    <option value="">Pilih Tahun Ajaran</option>
                    @foreach($this->academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="previous_school" label="{{ $entry_type === 'pindahan' ? 'Asal Sekolah' : 'Asal Sekolah (SMP/MTs)' }}" placeholder="Nama sekolah sebelumnya" />
                
                <flux:select wire:model.live="department_id" label="Jurusan/Kompetensi Keahlian" required>
                    <option value="">Pilih Jurusan</option>
                    @foreach($this->departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="classroom_id" label="Kelas" required>
                    <option value="">Pilih Kelas</option>
                    @foreach($this->classrooms as $classroom)
                        <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                    @endforeach
                </flux:select>
                
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Foto Siswa</label>
                    <input type="file" wire:model="photo" accept="image/*" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                    @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    Wali {{ $index + 1 }}
                                </span>
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
                            <flux:input wire:model="guardians.{{ $index }}.nik" label="NIK" placeholder="16 digit NIK" />
                            <flux:input wire:model="guardians.{{ $index }}.phone" label="No. HP" placeholder="08xxxxxxxxxx" />
                            <flux:input wire:model="guardians.{{ $index }}.occupation" label="Pekerjaan" />
                            <flux:input wire:model="guardians.{{ $index }}.address" label="Alamat" placeholder="Kosongkan jika sama dengan siswa" />
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
            <flux:textarea wire:model="notes" rows="3" placeholder="Catatan khusus mengenai pendaftaran siswa ini..." />
        </x-card>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
            <flux:button type="button" :href="route('students.mutations.index')" variant="ghost" wire:navigate>
                Batal
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check" class="bg-linear-to-r! from-blue-600! to-indigo-600!">
                Simpan Pendaftaran
            </flux:button>
        </div>
    </form>
</div>
