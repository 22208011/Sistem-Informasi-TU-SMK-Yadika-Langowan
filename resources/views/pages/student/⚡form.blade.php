<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Form Siswa')] class extends Component {
    use WithFileUploads;

    public ?Student $student = null;

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
    public string $status = 'aktif';
    public ?int $classroom_id = null;
    public ?int $department_id = null;
    public ?int $academic_year_id = null;

    // Photo
    public $photo = null;
    public ?string $existing_photo = null;

    // Guardians
    public array $guardians = [];

    public function mount(?Student $student = null): void
    {
        $this->entry_year = now()->year;

        if ($student && $student->exists) {
            $this->student = $student;
            $this->nis = $student->nis;
            $this->nisn = $student->nisn ?? '';
            $this->name = $student->name;
            $this->gender = $student->gender;
            $this->place_of_birth = $student->place_of_birth ?? '';
            $this->date_of_birth = $student->date_of_birth?->format('Y-m-d') ?? '';
            $this->religion = $student->religion ?? '';
            $this->address = $student->address ?? '';
            $this->phone = $student->phone ?? '';
            $this->email = $student->email ?? '';
            $this->previous_school = $student->previous_school ?? '';
            $this->entry_year = $student->entry_year;
            $this->status = $student->status;
            $this->classroom_id = $student->classroom_id;
            $this->department_id = $student->department_id;
            $this->academic_year_id = $student->academic_year_id;
            $this->existing_photo = $student->photo;

            // Load guardians
            foreach ($student->guardians as $guardian) {
                $this->guardians[] = [
                    'id' => $guardian->id,
                    'relationship' => $guardian->relationship,
                    'name' => $guardian->name,
                    'nik' => $guardian->nik ?? '',
                    'phone' => $guardian->phone ?? '',
                    'email' => $guardian->email ?? '',
                    'occupation' => $guardian->occupation ?? '',
                    'address' => $guardian->address ?? '',
                    'is_primary' => $guardian->is_primary,
                ];
            }
        }

        // Ensure at least one guardian form
        if (empty($this->guardians)) {
            $this->addGuardian();
        }
    }

    public function rules(): array
    {
        $nisRule = $this->student
            ? 'required|string|max:20|unique:students,nis,' . $this->student->id
            : 'required|string|max:20|unique:students,nis';

        $nisnRule = $this->student
            ? 'nullable|string|max:20|unique:students,nisn,' . $this->student->id
            : 'nullable|string|max:20|unique:students,nisn';

        return [
            'nis' => $nisRule,
            'nisn' => $nisnRule,
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:L,P'],
            'place_of_birth' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'religion' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'entry_year' => ['required', 'digits:4'],
            'status' => ['required', 'in:aktif,lulus,pindah,keluar,do'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'guardians' => ['array', 'min:1'],
            'guardians.*.relationship' => ['required', 'in:ayah,ibu,wali'],
            'guardians.*.name' => ['required', 'string', 'max:255'],
            'guardians.*.nik' => ['nullable', 'string', 'max:20'],
            'guardians.*.phone' => ['nullable', 'string', 'max:20'],
            'guardians.*.email' => ['required', 'email:rfc,dns', 'max:100'],
            'guardians.*.occupation' => ['nullable', 'string', 'max:255'],
            'guardians.*.address' => ['nullable', 'string'],
        ];
    }

    #[Computed]
    public function classrooms()
    {
        return Classroom::active()->orderBy('name')->get();
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

    public function addGuardian(): void
    {
        $this->guardians[] = [
            'id' => null,
            'relationship' => 'ayah',
            'name' => '',
            'nik' => '',
            'phone' => '',
            'email' => '',
            'occupation' => '',
            'address' => '',
            'is_primary' => count($this->guardians) === 0,
        ];
    }

    public function removeGuardian(int $index): void
    {
        if (count($this->guardians) > 1) {
            // If removing primary, set first remaining as primary
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

    public function save(): void
    {
        $validated = $this->validate();

        // Handle photo upload
        if ($this->photo) {
            if ($this->existing_photo) {
                Storage::disk('public')->delete($this->existing_photo);
            }
            $validated['photo'] = $this->photo->store('students', 'public');
        }

        // Remove empty strings and guardians from student data
        $studentData = collect($validated)
            ->except(['guardians', 'photo'])
            ->map(fn($v) => $v === '' ? null : $v)
            ->toArray();

        if (isset($validated['photo'])) {
            $studentData['photo'] = $validated['photo'];
        }

        if ($this->student) {
            $this->student->update($studentData);
            $student = $this->student;
            $message = 'Data siswa berhasil diperbarui.';
        } else {
            $student = Student::create($studentData);
            $message = 'Siswa berhasil ditambahkan.';
        }

        // Save guardians
        $createdParentAccountCount = 0;
        $existingIds = [];
        foreach ($this->guardians as $guardianData) {
            if (!empty($guardianData['name'])) {
                $guardianData = array_map(fn($v) => $v === '' ? null : $v, $guardianData);
                $guardianData['student_id'] = $student->id;

                if (isset($guardianData['id']) && $guardianData['id']) {
                    $guardian = Guardian::find($guardianData['id']);
                    if ($guardian) {
                        $guardian->fill($guardianData);
                        if ($this->syncParentAccount($guardian)) {
                            $createdParentAccountCount++;
                        }
                        $guardian->save();
                        $existingIds[] = $guardian->id;
                    }
                } else {
                    $guardian = new Guardian($guardianData);
                    if ($this->syncParentAccount($guardian)) {
                        $createdParentAccountCount++;
                    }
                    $guardian->save();
                    $existingIds[] = $guardian->id;
                }
            }
        }

        // Delete removed guardians
        $student->guardians()->whereNotIn('id', $existingIds)->delete();

        if ($createdParentAccountCount > 0) {
            $message .= ' ' . $createdParentAccountCount . ' akun orang tua otomatis dibuat (password awal: password).';
        }

        session()->flash('success', $message);
        $this->redirect(route('students.index'), navigate: true);
    }

    private function syncParentAccount(Guardian $guardian): bool
    {
        $email = strtolower(trim((string) $guardian->email));

        if ($email === '') {
            throw ValidationException::withMessages([
                'guardians' => 'Email wali wajib diisi untuk login orang tua.',
            ]);
        }

        $this->ensureEmailLooksReal($email);

        $guardian->email = $email;

        $parentRoleId = Role::where('name', Role::ORANG_TUA)->value('id');
        if (! $parentRoleId) {
            throw ValidationException::withMessages([
                'guardians' => 'Role orang tua belum tersedia. Jalankan seeder role terlebih dahulu.',
            ]);
        }

        if ($guardian->user_id) {
            $linkedUser = User::find($guardian->user_id);

            if ($linkedUser) {
                $sameEmailUser = User::where('email', $email)
                    ->where('id', '!=', $linkedUser->id)
                    ->first();

                if ($sameEmailUser) {
                    if ($sameEmailUser->role?->name !== Role::ORANG_TUA) {
                        throw ValidationException::withMessages([
                            'guardians' => 'Salah satu email wali dipakai akun non orang tua.',
                        ]);
                    }

                    $guardian->user_id = $sameEmailUser->id;
                    return false;
                }

                $linkedUser->update([
                    'name' => $guardian->name,
                    'email' => $email,
                    'role_id' => $parentRoleId,
                    'is_active' => true,
                ]);

                $guardian->user_id = $linkedUser->id;
                return false;
            }
        }

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            if ($existingUser->role?->name !== Role::ORANG_TUA) {
                throw ValidationException::withMessages([
                    'guardians' => 'Salah satu email wali dipakai akun non orang tua.',
                ]);
            }

            $existingUser->update([
                'name' => $guardian->name,
                'is_active' => true,
            ]);

            $guardian->user_id = $existingUser->id;
            return false;
        }

        $newUser = User::create([
            'name' => $guardian->name,
            'email' => $email,
            'password' => Hash::make('password'),
            'role_id' => $parentRoleId,
            'is_active' => true,
        ]);

        $guardian->user_id = $newUser->id;

        return true;
    }

    private function ensureEmailLooksReal(string $email): void
    {
        $domain = strtolower((string) substr(strrchr($email, '@') ?: '', 1));

        if ($domain === '') {
            throw ValidationException::withMessages([
                'guardians' => 'Domain email wali tidak valid.',
            ]);
        }

        $blockedDomains = [
            'example.com',
            'example.org',
            'example.net',
            'test.com',
            'invalid',
            'localhost',
        ];

        if (in_array($domain, $blockedDomains, true)) {
            throw ValidationException::withMessages([
                'guardians' => 'Gunakan email wali yang asli, bukan domain contoh/test.',
            ]);
        }
    }

    public function removePhoto(): void
    {
        if ($this->existing_photo) {
            Storage::disk('public')->delete($this->existing_photo);
            $this->student?->update(['photo' => null]);
            $this->existing_photo = null;
        }
        $this->photo = null;
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:button :href="route('students.index')" variant="ghost" icon="arrow-left" wire:navigate class="mb-4">
            {{ __('Kembali') }}
        </flux:button>

        <flux:heading size="xl">
            {{ $student ? __('Edit Siswa') : __('Tambah Siswa Baru') }}
        </flux:heading>
        <flux:text class="mt-2">
            {{ $student ? __('Perbarui data siswa.') : __('Isi data siswa baru.') }}
        </flux:text>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Basic Information -->
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">{{ __('Informasi Dasar') }}</flux:heading>
            </flux:card.header>

            <flux:card.body class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="nis"
                        label="{{ __('NIS') }}"
                        placeholder="Nomor Induk Siswa"
                        required
                    />

                    <flux:input
                        wire:model="nisn"
                        label="{{ __('NISN') }}"
                        placeholder="Nomor Induk Siswa Nasional"
                    />
                </div>

                <flux:input
                    wire:model="name"
                    label="{{ __('Nama Lengkap') }}"
                    placeholder="Nama lengkap sesuai identitas"
                    required
                />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="gender" label="{{ __('Jenis Kelamin') }}" required>
                        @foreach (App\Models\Student::GENDERS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input
                        wire:model="place_of_birth"
                        label="{{ __('Tempat Lahir') }}"
                    />

                    <flux:input
                        wire:model="date_of_birth"
                        label="{{ __('Tanggal Lahir') }}"
                        type="date"
                    />
                </div>

                <flux:select wire:model="religion" label="{{ __('Agama') }}">
                    <option value="">-- Pilih Agama --</option>
                    @foreach (App\Models\Student::RELIGIONS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:textarea
                    wire:model="address"
                    label="{{ __('Alamat') }}"
                    rows="3"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="phone"
                        label="{{ __('Nomor Telepon') }}"
                        type="tel"
                    />

                    <flux:input
                        wire:model="email"
                        label="{{ __('Email') }}"
                        type="email"
                    />
                </div>
            </flux:card.body>
        </flux:card>

        <!-- Academic Information -->
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">{{ __('Informasi Akademik') }}</flux:heading>
            </flux:card.header>

            <flux:card.body class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="previous_school"
                        label="{{ __('Asal Sekolah') }}"
                        placeholder="Nama SMP/MTs asal"
                    />

                    <flux:input
                        wire:model="entry_year"
                        label="{{ __('Tahun Masuk') }}"
                        type="number"
                        min="2000"
                        max="2100"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="department_id" label="{{ __('Jurusan') }}">
                        <option value="">-- Pilih Jurusan --</option>
                        @foreach ($this->departments as $department)
                            <option value="{{ $department->id }}">{{ $department->code }} - {{ $department->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="classroom_id" label="{{ __('Kelas') }}">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach ($this->classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="academic_year_id" label="{{ __('Tahun Ajaran Masuk') }}">
                        <option value="">-- Pilih Tahun Ajaran --</option>
                        @foreach ($this->academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:select wire:model="status" label="{{ __('Status') }}" required>
                    @foreach (App\Models\Student::STATUSES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            </flux:card.body>
        </flux:card>

        <!-- Guardians -->
        <flux:card>
            <flux:card.header class="flex justify-between items-center">
                <flux:heading size="lg">{{ __('Data Orang Tua/Wali') }}</flux:heading>
                <flux:button type="button" wire:click="addGuardian" variant="ghost" icon="plus" size="sm">
                    {{ __('Tambah Wali') }}
                </flux:button>
            </flux:card.header>

            <flux:card.body class="space-y-6">
                <div class="rounded-xl border border-sky-200 bg-sky-50 p-3 text-xs text-sky-700 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-300">
                    Email wali wajib diisi karena dipakai untuk login orang tua. Jika akun belum ada, sistem otomatis membuat akun dengan password awal: <strong>password</strong>.
                </div>

                @foreach ($guardians as $index => $guardian)
                    <div wire:key="guardian-{{ $index }}" class="p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg space-y-4">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <flux:heading size="sm">{{ __('Wali') }} #{{ $index + 1 }}</flux:heading>
                                @if ($guardian['is_primary'])
                                    <flux:badge color="green" size="sm">Utama</flux:badge>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                @if (!$guardian['is_primary'])
                                    <flux:button type="button" wire:click="setPrimaryGuardian({{ $index }})" variant="ghost" size="sm">
                                        {{ __('Jadikan Utama') }}
                                    </flux:button>
                                @endif
                                @if (count($guardians) > 1)
                                    <flux:button type="button" wire:click="removeGuardian({{ $index }})" variant="ghost" size="sm" icon="trash" />
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:select wire:model="guardians.{{ $index }}.relationship" label="{{ __('Hubungan') }}" required>
                                @foreach (App\Models\Guardian::RELATIONSHIPS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>

                            <div class="md:col-span-2">
                                <flux:input
                                    wire:model="guardians.{{ $index }}.name"
                                    label="{{ __('Nama Lengkap') }}"
                                    required
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:input
                                wire:model="guardians.{{ $index }}.nik"
                                label="{{ __('NIK') }}"
                            />

                            <flux:input
                                wire:model="guardians.{{ $index }}.phone"
                                label="{{ __('Nomor Telepon') }}"
                            />

                            <flux:input
                                wire:model="guardians.{{ $index }}.email"
                                label="{{ __('Email Login') }}"
                                type="email"
                                required
                            />

                            <flux:input
                                wire:model="guardians.{{ $index }}.occupation"
                                label="{{ __('Pekerjaan') }}"
                            />
                        </div>

                        <flux:textarea
                            wire:model="guardians.{{ $index }}.address"
                            label="{{ __('Alamat') }}"
                            rows="2"
                        />
                    </div>
                @endforeach
            </flux:card.body>
        </flux:card>

        <!-- Photo -->
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">{{ __('Foto') }}</flux:heading>
            </flux:card.header>

            <flux:card.body class="space-y-4">
                @if ($existing_photo || $photo)
                    <div class="flex items-center gap-4">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="w-24 h-24 object-cover rounded-lg border" />
                        @elseif ($existing_photo)
                            <img src="{{ asset('storage/' . $existing_photo) }}" alt="Foto" class="w-24 h-24 object-cover rounded-lg border" />
                        @endif
                        <flux:button type="button" wire:click="removePhoto" variant="danger" size="sm">
                            {{ __('Hapus Foto') }}
                        </flux:button>
                    </div>
                @endif

                <div>
                    <flux:label>{{ __('Upload Foto') }}</flux:label>
                    <input 
                        type="file" 
                        wire:model="photo" 
                        accept="image/*"
                        class="block w-full text-sm text-zinc-700 dark:text-zinc-300
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-lg file:border-0
                            file:text-sm file:font-medium
                            file:bg-zinc-100 file:text-zinc-700
                            dark:file:bg-zinc-700 dark:file:text-zinc-300
                            hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600
                            cursor-pointer"
                    />
                    <div wire:loading wire:target="photo" class="mt-2 text-sm text-blue-600">
                        <flux:icon.loading class="inline w-4 h-4 mr-1" /> Mengupload...
                    </div>
                    @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Format: JPG, PNG. Maksimal 2MB.') }}</flux:text>
            </flux:card.body>
        </flux:card>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
            <flux:button :href="route('students.index')" variant="ghost" wire:navigate>
                {{ __('Batal') }}
            </flux:button>
            <flux:button type="submit" variant="primary">
                {{ $student ? __('Perbarui') : __('Simpan') }}
            </flux:button>
        </div>
    </form>
</div>
