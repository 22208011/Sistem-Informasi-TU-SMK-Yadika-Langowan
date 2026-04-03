<?php

use App\Models\SchoolProfile;
use App\Livewire\Concerns\WithNotification;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

new #[Layout('layouts.app')] #[Title('Profil Sekolah')] class extends Component {
    use WithFileUploads;
    use WithNotification;

    public ?SchoolProfile $profile = null;
    public bool $isEditing = false;

    public string $npsn = '';
    public string $name = '';
    public string $status = 'negeri';
    public string $accreditation = '';
    public string $address = '';
    public string $village = '';
    public string $district = '';
    public string $city = '';
    public string $province = '';
    public string $postal_code = '';
    public string $phone = '';
    public string $fax = '';
    public string $email = '';
    public string $website = '';
    public string $principal_name = '';
    public string $principal_nip = '';
    
    #[Validate('nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048')]
    public $logo = null;
    
    public ?string $existing_logo = null;

    public function mount(): void
    {
        $this->profile = SchoolProfile::first();
        $this->loadProfileData();
        
        // If no profile exists, start in edit mode
        if (!$this->profile) {
            $this->isEditing = true;
        }
    }
    
    public function updatedLogo(): void
    {
        $this->validateOnly('logo');
    }

    public function rules(): array
    {
        return [
            'npsn' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:negeri,swasta'],
            'accreditation' => ['nullable', 'string', 'max:10'],
            'address' => ['required', 'string'],
            'village' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'sometimes', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'principal_nip' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'npsn.required' => 'NPSN wajib diisi.',
            'name.required' => 'Nama sekolah wajib diisi.',
            'address.required' => 'Alamat wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'logo.image' => 'File harus berupa gambar.',
            'logo.mimes' => 'Format gambar harus JPG, JPEG, PNG, GIF, atau WEBP.',
            'logo.max' => 'Ukuran file maksimal 2MB.',
        ];
    }

    public function startEditing(): void
    {
        $this->isEditing = true;
    }

    public function cancelEditing(): void
    {
        $this->isEditing = false;
        $this->logo = null;
        $this->loadProfileData();
        $this->resetValidation();
    }

    public function save(): void
    {
        try {
            $validated = $this->validate();
            
            // Remove logo from validated data (will handle separately)
            unset($validated['logo']);
            
            // Convert empty strings to null for nullable fields
            $nullableFields = ['accreditation', 'village', 'district', 'city', 'province', 
                               'postal_code', 'phone', 'fax', 'email', 'website', 
                               'principal_name', 'principal_nip'];
            foreach ($nullableFields as $field) {
                if (isset($validated[$field]) && $validated[$field] === '') {
                    $validated[$field] = null;
                }
            }

            // Handle logo upload
            if ($this->logo) {
                // Delete old logo if exists
                if ($this->existing_logo) {
                    Storage::disk('public')->delete($this->existing_logo);
                }
                $validated['logo'] = $this->logo->store('school', 'public');
            }

            if ($this->profile) {
                $this->profile->update($validated);
                $this->profile->refresh();
            } else {
                $this->profile = SchoolProfile::create($validated);
            }
            
            // Update existing_logo with the new value from database
            $this->existing_logo = $this->profile->logo;
            
            // Reset the logo input after successful save
            $this->logo = null;
            
            // Reload data from database to ensure form shows current values
            $this->loadProfileData();
            
            // Switch to view mode
            $this->isEditing = false;

            // Set success notification
            $this->success('Profil sekolah berhasil disimpan.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('School Profile Save Error: ' . $e->getMessage());
            $this->error('Gagal menyimpan: ' . $e->getMessage());
        }
    }
    
    protected function loadProfileData(): void
    {
        if ($this->profile) {
            $this->npsn = $this->profile->npsn ?? '';
            $this->name = $this->profile->name ?? '';
            $this->status = $this->profile->status ?? 'negeri';
            $this->accreditation = $this->profile->accreditation ?? '';
            $this->address = $this->profile->address ?? '';
            $this->village = $this->profile->village ?? '';
            $this->district = $this->profile->district ?? '';
            $this->city = $this->profile->city ?? '';
            $this->province = $this->profile->province ?? '';
            $this->postal_code = $this->profile->postal_code ?? '';
            $this->phone = $this->profile->phone ?? '';
            $this->fax = $this->profile->fax ?? '';
            $this->email = $this->profile->email ?? '';
            $this->website = $this->profile->website ?? '';
            $this->principal_name = $this->profile->principal_name ?? '';
            $this->principal_nip = $this->profile->principal_nip ?? '';
            $this->existing_logo = $this->profile->logo;
        }
    }

    public function removeLogo(): void
    {
        if ($this->existing_logo) {
            Storage::disk('public')->delete($this->existing_logo);
            $this->profile?->update(['logo' => null]);
            $this->existing_logo = null;
        }
        $this->logo = null;
    }
}; ?>

<div>
    <!-- Page Header with Animation -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Profil Sekolah') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Kelola identitas, alamat, dan informasi kontak sekolah Anda.') }}</x-slot:subtitle>
        <x-slot:actions>
            @if (!$isEditing && $profile)
                <flux:button wire:click="startEditing" icon="pencil" class="rounded-xl! bg-linear-to-r! from-blue-600! to-indigo-600!">
                    Edit Profil
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Notification --}}
    <x-notification-alert :type="$notificationType" :message="$notificationMessage" />

    {{-- Show validation errors --}}
    @if ($errors->any())
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-red-900/50 dark:text-red-300">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($isEditing)
        {{-- EDIT MODE --}}
        <form wire:submit.prevent="save" class="space-y-6">
            <!-- Identity Card -->
            <x-elegant-card class="animate-fade-in-up">
                <x-slot:header>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-linear-to-br from-blue-500 to-indigo-600 text-white">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Identitas Sekolah') }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Informasi dasar dan identifikasi sekolah</p>
                        </div>
                    </div>
                </x-slot:header>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input wire:model="npsn" label="{{ __('NPSN') }}" placeholder="Nomor Pokok Sekolah Nasional" required class="rounded-xl!" />
                        <flux:input wire:model="name" label="{{ __('Nama Sekolah') }}" placeholder="SMK Negeri 1 ..." required class="rounded-xl!" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:select wire:model="status" label="{{ __('Status') }}" class="rounded-xl!">
                            <option value="negeri">Negeri</option>
                            <option value="swasta">Swasta</option>
                        </flux:select>
                        <flux:input wire:model="accreditation" label="{{ __('Akreditasi') }}" placeholder="A / B / C" class="rounded-xl!" />
                    </div>
                </div>
            </x-elegant-card>

            <!-- Address Card -->
            <x-elegant-card class="animate-fade-in-up delay-100">
                <x-slot:header>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-linear-to-br from-green-500 to-emerald-600 text-white">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Alamat') }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Lokasi dan alamat lengkap sekolah</p>
                        </div>
                    </div>
                </x-slot:header>

                <div class="space-y-4">
                    <flux:textarea wire:model="address" label="{{ __('Alamat Lengkap') }}" placeholder="Jalan, Nomor, RT/RW" rows="3" required class="rounded-xl!" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input wire:model="village" label="{{ __('Kelurahan/Desa') }}" class="rounded-xl!" />
                        <flux:input wire:model="district" label="{{ __('Kecamatan') }}" class="rounded-xl!" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:input wire:model="city" label="{{ __('Kabupaten/Kota') }}" class="rounded-xl!" />
                        <flux:input wire:model="province" label="{{ __('Provinsi') }}" class="rounded-xl!" />
                        <flux:input wire:model="postal_code" label="{{ __('Kode Pos') }}" class="rounded-xl!" />
                    </div>
                </div>
            </x-elegant-card>

            <!-- Contact Card -->
            <x-elegant-card class="animate-fade-in-up delay-200">
                <x-slot:header>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-linear-to-br from-purple-500 to-indigo-600 text-white">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Kontak') }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Informasi kontak dan media sosial</p>
                        </div>
                    </div>
                </x-slot:header>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input wire:model="phone" label="{{ __('Telepon') }}" type="tel" placeholder="(021) 123-4567" class="rounded-xl!" />
                        <flux:input wire:model="fax" label="{{ __('Fax') }}" type="tel" class="rounded-xl!" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input wire:model="email" label="{{ __('Email') }}" type="email" placeholder="info@smk.sch.id" class="rounded-xl!" />
                        <flux:input wire:model="website" label="{{ __('Website') }}" type="url" placeholder="https://smk.sch.id" class="rounded-xl!" />
                    </div>
                </div>
            </x-elegant-card>

            <!-- Principal Card -->
            <x-elegant-card class="animate-fade-in-up delay-300">
                <x-slot:header>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-linear-to-br from-amber-500 to-orange-600 text-white">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Kepala Sekolah') }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Data kepala sekolah saat ini</p>
                        </div>
                    </div>
                </x-slot:header>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model="principal_name" label="{{ __('Nama Kepala Sekolah') }}" class="rounded-xl!" />
                    <flux:input wire:model="principal_nip" label="{{ __('NIP') }}" class="rounded-xl!" />
                </div>
            </x-elegant-card>

            <!-- Logo Card -->
            <x-elegant-card class="animate-fade-in-up delay-400">
                <x-slot:header>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-linear-to-br from-pink-500 to-rose-600 text-white">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Logo Sekolah') }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Upload logo resmi sekolah</p>
                        </div>
                    </div>
                </x-slot:header>

                <div class="space-y-4">
                    @if ($existing_logo || $logo)
                        <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                            @if ($logo && !$errors->has('logo'))
                                @php
                                    try {
                                        $previewUrl = $logo->temporaryUrl();
                                    } catch (\Exception $e) {
                                        $previewUrl = null;
                                    }
                                @endphp
                                @if ($previewUrl)
                                    <img src="{{ $previewUrl }}" alt="Logo Preview" class="w-24 h-24 object-contain rounded-xl border-2 border-white dark:border-zinc-700 shadow-lg" />
                                @else
                                    <div class="w-24 h-24 flex items-center justify-center rounded-xl border-2 border-white dark:border-zinc-700 shadow-lg bg-zinc-200 dark:bg-zinc-700">
                                        <svg class="w-8 h-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                            @elseif ($existing_logo)
                                <img src="{{ asset('storage/' . $existing_logo) }}" alt="Logo Sekolah" class="w-24 h-24 object-contain rounded-xl border-2 border-white dark:border-zinc-700 shadow-lg" />
                            @endif
                            <div>
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    {{ $logo && !$errors->has('logo') ? 'Preview logo baru' : 'Logo saat ini' }}
                                </p>
                                <flux:button type="button" wire:click="removeLogo" variant="danger" size="sm" class="rounded-lg!">
                                    <svg class="size-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    {{ __('Hapus Logo') }}
                                </flux:button>
                            </div>
                        </div>
                    @endif

                    <div class="p-4 border-2 border-dashed border-zinc-300 dark:border-zinc-700 rounded-xl hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Upload Logo Baru') }}</label>
                            <input
                                type="file"
                                wire:model="logo"
                                id="logo-upload"
                                accept="image/jpeg,image/png,image/gif,image/webp"
                                class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                                       file:mr-4 file:py-2 file:px-4
                                       file:rounded-lg file:border-0
                                       file:text-sm file:font-medium
                                       file:bg-blue-50 file:text-blue-700
                                       hover:file:bg-blue-100
                                       dark:file:bg-blue-900/30 dark:file:text-blue-400
                                       cursor-pointer"
                            />
                            @error('logo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            <div wire:loading wire:target="logo" class="text-sm text-blue-600 dark:text-blue-400">
                                <svg class="animate-spin inline-block w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Mengupload...') }}
                            </div>
                        </div>
                        <p class="text-xs text-zinc-500 mt-2">{{ __('Format: JPG, PNG, GIF, WEBP. Maksimal 2MB.') }}</p>
                    </div>
                </div>
            </x-elegant-card>

            <!-- Submit Button -->
            <div class="flex justify-end gap-3 animate-fade-in-up delay-500">
                @if ($profile)
                    <flux:button type="button" wire:click="cancelEditing" variant="ghost" class="rounded-xl!">
                        Batal
                    </flux:button>
                @endif
                <flux:button 
                    type="submit" 
                    wire:loading.attr="disabled"
                    class="rounded-xl! px-!8 py-!3 bg-linear-to-r! from-blue-600! to-indigo-600! hover:from-blue-700! hover:to-indigo-700! shadow-lg! shadow-blue-500!/25"
                >
                    <span wire:loading.remove wire:target="save">
                        <svg class="size-5 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ __('Simpan Profil') }}
                    </span>
                    <span wire:loading wire:target="save">
                        <svg class="animate-spin size-5 mr-2 inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Menyimpan...') }}
                    </span>
                </flux:button>
            </div>
        </form>
    @else
        {{-- VIEW MODE --}}
        <div class="space-y-6">
            <!-- School Header Card with Logo -->
            <x-elegant-card class="animate-fade-in-up">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    <!-- Logo -->
                    <div class="shrink-0">
                        @if ($existing_logo)
                            <img src="{{ asset('storage/' . $existing_logo) }}" alt="Logo {{ $name }}" class="w-32 h-32 object-contain rounded-2xl border-4 border-white dark:border-zinc-700 shadow-xl" />
                        @else
                            <div class="w-32 h-32 flex items-center justify-center rounded-2xl border-4 border-white dark:border-zinc-700 shadow-xl bg-linear-to-br from-blue-500 to-indigo-600">
                                <svg class="w-16 h-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                        @endif
                    </div>
                    
                    <!-- School Name & Basic Info -->
                    <div class="flex-1 text-center md:text-left">
                        <h1 class="text-2xl md:text-3xl font-bold text-zinc-800 dark:text-white">{{ $name }}</h1>
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 mt-2">
                            <flux:badge color="blue" class="rounded-lg!">NPSN: {{ $npsn }}</flux:badge>
                            <flux:badge color="{{ $status === 'negeri' ? 'green' : 'purple' }}" class="rounded-lg!">{{ ucfirst($status) }}</flux:badge>
                            @if ($accreditation)
                                <flux:badge color="amber" class="rounded-lg!">Akreditasi {{ $accreditation }}</flux:badge>
                            @endif
                        </div>
                        <p class="text-zinc-500 dark:text-zinc-400 mt-3">
                            <svg class="inline-block size-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            {{ $address }}{{ $village ? ', ' . $village : '' }}{{ $district ? ', ' . $district : '' }}{{ $city ? ', ' . $city : '' }}{{ $province ? ', ' . $province : '' }}{{ $postal_code ? ' ' . $postal_code : '' }}
                        </p>
                    </div>
                </div>
            </x-elegant-card>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Contact Information -->
                <x-elegant-card class="animate-fade-in-up delay-100">
                    <x-slot:header>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-linear-to-br from-purple-500 to-indigo-600 text-white">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">Informasi Kontak</h3>
                        </div>
                    </x-slot:header>

                    <div class="space-y-4">
                        @if ($phone)
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                    <svg class="size-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Telepon</p>
                                    <p class="font-medium text-zinc-800 dark:text-white">{{ $phone }}</p>
                                </div>
                            </div>
                        @endif

                        @if ($fax)
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20">
                                    <svg class="size-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Fax</p>
                                    <p class="font-medium text-zinc-800 dark:text-white">{{ $fax }}</p>
                                </div>
                            </div>
                        @endif

                        @if ($email)
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-900/20">
                                    <svg class="size-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Email</p>
                                    <a href="mailto:{{ $email }}" class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">{{ $email }}</a>
                                </div>
                            </div>
                        @endif

                        @if ($website)
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/20">
                                    <svg class="size-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Website</p>
                                    <a href="{{ $website }}" target="_blank" class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">{{ $website }}</a>
                                </div>
                            </div>
                        @endif

                        @if (!$phone && !$fax && !$email && !$website)
                            <p class="text-zinc-500 dark:text-zinc-400 text-center py-4">Belum ada informasi kontak.</p>
                        @endif
                    </div>
                </x-elegant-card>

                <!-- Principal Information -->
                <x-elegant-card class="animate-fade-in-up delay-200">
                    <x-slot:header>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-linear-to-br from-amber-500 to-orange-600 text-white">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">Kepala Sekolah</h3>
                        </div>
                    </x-slot:header>

                    @if ($principal_name)
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-linear-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30">
                                <svg class="size-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ $principal_name }}</h4>
                                @if ($principal_nip)
                                    <p class="text-zinc-500 dark:text-zinc-400">NIP: {{ $principal_nip }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-zinc-500 dark:text-zinc-400 text-center py-4">Belum ada data kepala sekolah.</p>
                    @endif
                </x-elegant-card>
            </div>
        </div>
    @endif
</div>
