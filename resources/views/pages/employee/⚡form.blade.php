<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Livewire\Concerns\WithNotification;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Form Pegawai')] class extends Component {
    use WithFileUploads;
    use WithNotification;

    public ?Employee $employee = null;

    // Basic Info
    public string $nip = '';
    public string $nuptk = '';
    public string $name = '';
    public string $gender = 'L';
    public string $place_of_birth = '';
    public string $date_of_birth = '';
    public string $religion = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';

    // Employment Info
    public string $employee_status = 'honorer';
    public string $employee_type = 'guru';
    public string $join_date = '';
    public ?int $position_id = null;
    public ?int $department_id = null;

    // Education
    public string $education_level = '';
    public string $education_major = '';
    public string $education_institution = '';

    // Photo
    public $photo = null;
    public ?string $existing_photo = null;
    public bool $is_active = true;

    public function mount(?Employee $employee = null): void
    {
        if ($employee && $employee->exists) {
            $this->employee = $employee;
            $this->nip = $employee->nip ?? '';
            $this->nuptk = $employee->nuptk ?? '';
            $this->name = $employee->name;
            $this->gender = $employee->gender;
            $this->place_of_birth = $employee->place_of_birth ?? '';
            $this->date_of_birth = $employee->date_of_birth?->format('Y-m-d') ?? '';
            $this->religion = $employee->religion ?? '';
            $this->address = $employee->address ?? '';
            $this->phone = $employee->phone ?? '';
            $this->email = $employee->email ?? '';
            $this->employee_status = $employee->employee_status;
            $this->employee_type = $employee->employee_type;
            $this->join_date = $employee->join_date?->format('Y-m-d') ?? '';
            $this->position_id = $employee->position_id;
            $this->department_id = $employee->department_id;
            $this->education_level = $employee->education_level ?? '';
            $this->education_major = $employee->education_major ?? '';
            $this->education_institution = $employee->education_institution ?? '';
            $this->existing_photo = $employee->photo;
            $this->is_active = (bool) $employee->is_active;
        }
    }

    public function rules(): array
    {
        $nipRule = $this->employee
            ? 'nullable|string|max:50|unique:employees,nip,' . $this->employee->id
            : 'nullable|string|max:50|unique:employees,nip';

        $nuptkRule = $this->employee
            ? 'nullable|string|max:50|unique:employees,nuptk,' . $this->employee->id
            : 'nullable|string|max:50|unique:employees,nuptk';

        return [
            'nip' => $nipRule,
            'nuptk' => $nuptkRule,
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:L,P'],
            'place_of_birth' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'religion' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'employee_status' => ['required', 'in:pns,pppk,honorer,kontrak'],
            'employee_type' => ['required', 'in:guru,tendik'],
            'join_date' => ['nullable', 'date'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'education_level' => ['nullable', 'string', 'max:20'],
            'education_major' => ['nullable', 'string', 'max:255'],
            'education_institution' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['boolean'],
        ];
    }

    protected $messages = [
        'name.required' => 'Nama lengkap wajib diisi.',
        'name.max' => 'Nama maksimal 255 karakter.',
        'gender.required' => 'Jenis kelamin wajib dipilih.',
        'employee_status.required' => 'Status kepegawaian wajib dipilih.',
        'employee_type.required' => 'Tipe pegawai wajib dipilih.',
        'nip.unique' => 'NIP sudah terdaftar.',
        'nuptk.unique' => 'NUPTK sudah terdaftar.',
        'email.email' => 'Format email tidak valid.',
        'photo.image' => 'File harus berupa gambar.',
        'photo.max' => 'Ukuran foto maksimal 2MB.',
    ];

    #[Computed]
    public function positions()
    {
        return Position::active()->orderBy('name')->get();
    }

    #[Computed]
    public function departments()
    {
        return Department::active()->orderBy('name')->get();
    }

    public function save(): void
    {
        // Debug log
        \Log::info('Employee save called', [
            'name' => $this->name,
            'is_active' => $this->is_active,
            'employee_exists' => $this->employee?->exists,
        ]);

        $this->validate();

        try {
            $data = [
                'nip' => $this->nip ?: null,
                'nuptk' => $this->nuptk ?: null,
                'name' => $this->name,
                'gender' => $this->gender,
                'place_of_birth' => $this->place_of_birth ?: null,
                'date_of_birth' => $this->date_of_birth ?: null,
                'religion' => $this->religion ?: null,
                'address' => $this->address ?: null,
                'phone' => $this->phone ?: null,
                'email' => $this->email ?: null,
                'employee_status' => $this->employee_status,
                'employee_type' => $this->employee_type,
                'join_date' => $this->join_date ?: null,
                'position_id' => $this->position_id ?: null,
                'department_id' => $this->department_id ?: null,
                'education_level' => $this->education_level ?: null,
                'education_major' => $this->education_major ?: null,
                'education_institution' => $this->education_institution ?: null,
                'is_active' => (bool) $this->is_active,
            ];

            \Log::info('Employee data to save', $data);

            // Handle photo upload
            if ($this->photo) {
                if ($this->existing_photo) {
                    Storage::disk('public')->delete($this->existing_photo);
                }
                $data['photo'] = $this->photo->store('employees', 'public');
            }

            if ($this->employee && $this->employee->exists) {
                $this->employee->update($data);
                \Log::info('Employee updated', ['id' => $this->employee->id]);
                session()->flash('success', 'Data pegawai berhasil diperbarui.');
            } else {
                $employee = Employee::create($data);
                \Log::info('Employee created', ['id' => $employee->id]);
                session()->flash('success', 'Pegawai berhasil ditambahkan.');
            }

            $this->redirect(route('employees.index'), navigate: true);
        } catch (\Exception $e) {
            \Log::error('Employee save failed', ['error' => $e->getMessage()]);
            $this->error('Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function removePhoto(): void
    {
        if ($this->existing_photo) {
            Storage::disk('public')->delete($this->existing_photo);
            $this->employee?->update(['photo' => null]);
            $this->existing_photo = null;
        }
        $this->photo = null;
        $this->success('Foto berhasil dihapus.');
    }

    public function toggleActive(): void
    {
        $this->is_active = !$this->is_active;
    }
}; ?>

<div>
    <!-- Page Header -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ $employee ? __('Edit Pegawai') : __('Tambah Pegawai Baru') }}</x-slot:title>
        <x-slot:subtitle>{{ $employee ? __('Perbarui data pegawai.') : __('Isi data pegawai baru.') }}</x-slot:subtitle>
        <x-slot:actions>
            <flux:button :href="route('employees.index')" variant="ghost" icon="arrow-left" wire:navigate class="rounded-xl!">
                {{ __('Kembali') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    {{-- Notification --}}
    <x-notification-alert :type="$notificationType" :message="$notificationMessage" />

    <form wire:submit="save" class="space-y-6">
        <!-- Basic Information -->
        <x-elegant-card class="animate-fade-in-up">
            <x-slot:header>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-green-500 to-emerald-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <flux:heading size="lg">{{ __('Informasi Dasar') }}</flux:heading>
                </div>
            </x-slot:header>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="nip"
                        label="{{ __('NIP') }}"
                        placeholder="Nomor Induk Pegawai (untuk PNS/PPPK)"
                        class="rounded-xl!"
                    />

                    <flux:input
                        wire:model="nuptk"
                        label="{{ __('NUPTK') }}"
                        placeholder="Nomor Unik Pendidik dan Tenaga Kependidikan"
                        class="rounded-xl!"
                    />
                </div>

                <flux:input
                    wire:model="name"
                    label="{{ __('Nama Lengkap') }}"
                    placeholder="Nama lengkap sesuai identitas"
                    required
                    class="rounded-xl!"
                />
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="gender" label="{{ __('Jenis Kelamin') }}" required class="rounded-xl!">
                        @foreach (App\Models\Employee::GENDERS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input
                        wire:model="place_of_birth"
                        label="{{ __('Tempat Lahir') }}"
                        class="rounded-xl!"
                    />

                    <flux:input
                        wire:model="date_of_birth"
                        label="{{ __('Tanggal Lahir') }}"
                        type="date"
                        class="rounded-xl!"
                    />
                </div>

                <flux:select wire:model="religion" label="{{ __('Agama') }}" class="rounded-xl!">
                    <option value="">-- Pilih Agama --</option>
                    @foreach (App\Models\Employee::RELIGIONS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:textarea
                    wire:model="address"
                    label="{{ __('Alamat') }}"
                    rows="3"
                    class="rounded-xl!"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="phone"
                        label="{{ __('Nomor Telepon') }}"
                        type="tel"
                        class="rounded-xl!"
                    />

                    <flux:input
                        wire:model="email"
                        label="{{ __('Email') }}"
                        type="email"
                        class="rounded-xl!"
                    />
                </div>
            </div>
        </x-elegant-card>

        <!-- Employment Information -->
        <x-elegant-card class="animate-fade-in-up delay-100">
            <x-slot:header>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-blue-500 to-indigo-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <flux:heading size="lg">{{ __('Informasi Kepegawaian') }}</flux:heading>
                </div>
            </x-slot:header>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="employee_type" label="{{ __('Tipe Pegawai') }}" required class="rounded-xl!">
                        @foreach (App\Models\Employee::TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="employee_status" label="{{ __('Status Kepegawaian') }}" required class="rounded-xl!">
                        @foreach (App\Models\Employee::STATUSES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select wire:model="position_id" label="{{ __('Jabatan') }}" class="rounded-xl!">
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach ($this->positions as $position)
                            <option value="{{ $position->id }}">{{ $position->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="department_id" label="{{ __('Jurusan (untuk Guru Produktif)') }}" class="rounded-xl!">
                        <option value="">-- Pilih Jurusan --</option>
                        @foreach ($this->departments as $department)
                            <option value="{{ $department->id }}">{{ $department->code }} - {{ $department->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input
                    wire:model="join_date"
                    label="{{ __('Tanggal Mulai Bekerja') }}"
                    type="date"
                    class="rounded-xl!"
                />
            </div>
        </x-elegant-card>

        <!-- Education -->
        <x-elegant-card class="animate-fade-in-up delay-200">
            <x-slot:header>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-purple-500 to-indigo-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <flux:heading size="lg">{{ __('Pendidikan') }}</flux:heading>
                </div>
            </x-slot:header>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="education_level" label="{{ __('Jenjang Pendidikan') }}" class="rounded-xl!">
                        <option value="">-- Pilih Jenjang --</option>
                        @foreach (App\Models\Employee::EDUCATION_LEVELS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input
                        wire:model="education_major"
                        label="{{ __('Jurusan/Program Studi') }}"
                        class="rounded-xl!"
                    />

                    <flux:input
                        wire:model="education_institution"
                        label="{{ __('Nama Institusi') }}"
                        placeholder="Universitas/Perguruan Tinggi"
                        class="rounded-xl!"
                    />
                </div>
            </div>
        </x-elegant-card>

        <!-- Photo -->
        <x-elegant-card class="animate-fade-in-up delay-300">
            <x-slot:header>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-amber-500 to-orange-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <flux:heading size="lg">{{ __('Foto') }}</flux:heading>
                </div>
            </x-slot:header>

            <div class="space-y-4">
                @if ($existing_photo || $photo)
                    <div class="flex items-center gap-4">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="w-24 h-24 object-cover rounded-xl border-2 border-zinc-200 dark:border-zinc-700 shadow-md" />
                        @elseif ($existing_photo)
                            <img src="{{ asset('storage/' . $existing_photo) }}" alt="Foto" class="w-24 h-24 object-cover rounded-xl border-2 border-zinc-200 dark:border-zinc-700 shadow-md" />
                        @endif
                        <flux:button type="button" wire:click="removePhoto" variant="danger" size="sm" icon="trash" class="rounded-xl!">
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
                            file:rounded-xl file:border-0
                            file:text-sm file:font-medium
                            file:bg-zinc-100 file:text-zinc-700
                            dark:file:bg-zinc-700 dark:file:text-zinc-300
                            hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600
                            cursor-pointer"
                    />
                    <div wire:loading wire:target="photo" class="mt-2 text-sm text-blue-600">
                        <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Mengupload...
                    </div>
                    @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Format: JPG, PNG. Maksimal 2MB.') }}</flux:text>
            </div>
        </x-elegant-card>

        <!-- Status Active -->
        <x-elegant-card class="animate-fade-in-up delay-400">
            <x-slot:header>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-teal-500 to-cyan-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <flux:heading size="lg">{{ __('Status Keaktifan') }}</flux:heading>
                </div>
            </x-slot:header>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Pegawai Aktif') }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Nonaktifkan jika pegawai sudah tidak bekerja di sekolah.') }}</p>
                    </div>
                    <button
                        type="button"
                        wire:click="toggleActive"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $is_active ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                        role="switch"
                        aria-checked="{{ $is_active ? 'true' : 'false' }}"
                    >
                        <span class="sr-only">Toggle active status</span>
                        <span class="{{ $is_active ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                </div>
            </div>
        </x-elegant-card>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row justify-end gap-3 animate-fade-in-up delay-500">
            <flux:button :href="route('employees.index')" variant="ghost" wire:navigate class="rounded-xl!">
                {{ __('Batal') }}
            </flux:button>
            <flux:button type="submit" class="rounded-xl! bg-linear-to-r! from-green-600! to-emerald-600! hover:from-green-700! hover:to-emerald-700! shadow-lg! shadow-green-500!/25">
                <svg class="size-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ $employee ? __('Perbarui Data') : __('Simpan Data') }}
            </flux:button>
        </div>
    </form>
</div>

@script
<script>
    $wire.on('scroll-to-top', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>
@endscript
