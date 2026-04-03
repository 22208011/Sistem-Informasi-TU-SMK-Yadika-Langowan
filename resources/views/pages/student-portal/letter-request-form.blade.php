<?php

use App\Models\LetterRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Ajukan Permohonan Surat')] class extends Component {
    use WithFileUploads;

    public string $letter_type = '';
    public string $purpose = '';
    public string $notes = '';

    // Dynamic fields based on selected letter type
    public string $destination_institution = '';
    public string $internship_company = '';
    public string $internship_address = '';
    public string $internship_start_date = '';
    public string $internship_end_date = '';
    public string $homeroom_teacher_name = '';
    public string $parent_name = '';
    public string $transfer_school_name = '';
    public string $transfer_school_city = '';
    public string $transfer_reason = '';

    public bool $requirements_confirmed = false;
    public array $attachments = [];

    public function updatedLetterType(): void
    {
        $this->requirements_confirmed = false;
        $this->attachments = [];
        $this->resetTypeSpecificFields();
    }

    private function resetTypeSpecificFields(): void
    {
        $this->destination_institution = '';
        $this->internship_company = '';
        $this->internship_address = '';
        $this->internship_start_date = '';
        $this->internship_end_date = '';
        $this->homeroom_teacher_name = '';
        $this->parent_name = '';
        $this->transfer_school_name = '';
        $this->transfer_school_city = '';
        $this->transfer_reason = '';
    }

    public function mount()
    {
        if (!auth()->user()->isStudent()) {
            return redirect()->route('dashboard');
        }

        // Pre-select type from query parameter
        $type = request()->query('type');
        if ($type && array_key_exists($type, LetterRequest::TYPES)) {
            $this->letter_type = $type;
        }
    }

    protected function rules(): array
    {
        $requiredAttachmentCount = $this->letter_type
            ? (LetterRequest::TYPE_REQUIRED_ATTACHMENT_COUNT[$this->letter_type] ?? 0)
            : 0;

        $baseRules = [
            'letter_type' => 'required|in:' . implode(',', array_keys(LetterRequest::TYPES)),
            'purpose' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'requirements_confirmed' => $this->letter_type ? 'accepted' : 'nullable',
            'attachments' => 'required|array|size:' . $requiredAttachmentCount,
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];

        $detailRules = match ($this->letter_type) {
            LetterRequest::TYPE_ACTIVE_STUDENT => [
                'destination_institution' => 'required|string|max:255',
            ],
            LetterRequest::TYPE_INTERNSHIP => [
                'internship_company' => 'required|string|max:255',
                'internship_address' => 'required|string|max:255',
                'internship_start_date' => 'required|date',
                'internship_end_date' => 'required|date|after_or_equal:internship_start_date',
            ],
            LetterRequest::TYPE_GOOD_BEHAVIOR => [
                'homeroom_teacher_name' => 'required|string|max:255',
            ],
            LetterRequest::TYPE_TRANSFER => [
                'parent_name' => 'required|string|max:255',
                'transfer_school_name' => 'required|string|max:255',
                'transfer_school_city' => 'required|string|max:120',
                'transfer_reason' => 'required|string|max:500',
            ],
            default => [],
        };

        return array_merge($baseRules, $detailRules);
    }

    protected function messages(): array
    {
        return [
            'letter_type.required' => 'Jenis surat wajib dipilih.',
            'purpose.required' => 'Keperluan/tujuan permohonan wajib diisi.',
            'purpose.max' => 'Keperluan maksimal 500 karakter.',
            'requirements_confirmed.accepted' => 'Konfirmasi persyaratan wajib dicentang sebelum mengajukan.',
            'attachments.required' => 'Dokumen pendukung wajib diunggah.',
            'attachments.array' => 'Format upload dokumen tidak valid.',
            'attachments.size' => 'Jumlah berkas harus sesuai persyaratan jenis surat yang dipilih.',
            'attachments.*.mimes' => 'Setiap file pendukung harus berformat PDF, JPG, atau PNG.',
            'attachments.*.max' => 'Ukuran setiap file maksimal 2MB.',

            'destination_institution.required' => 'Instansi tujuan wajib diisi.',
            'internship_company.required' => 'Nama perusahaan/industri wajib diisi.',
            'internship_address.required' => 'Alamat perusahaan/industri wajib diisi.',
            'internship_start_date.required' => 'Tanggal mulai PKL wajib diisi.',
            'internship_end_date.required' => 'Tanggal selesai PKL wajib diisi.',
            'internship_end_date.after_or_equal' => 'Tanggal selesai PKL harus sama atau setelah tanggal mulai.',
            'homeroom_teacher_name.required' => 'Nama wali kelas wajib diisi.',
            'parent_name.required' => 'Nama orang tua/wali wajib diisi.',
            'transfer_school_name.required' => 'Nama sekolah tujuan wajib diisi.',
            'transfer_school_city.required' => 'Kota/Kabupaten sekolah tujuan wajib diisi.',
            'transfer_reason.required' => 'Alasan mutasi wajib diisi.',
        ];
    }

    private function buildTypeSpecificNotes(): string
    {
        $details = [];

        match ($this->letter_type) {
            LetterRequest::TYPE_ACTIVE_STUDENT => $details = [
                'Instansi Tujuan: ' . $this->destination_institution,
            ],
            LetterRequest::TYPE_INTERNSHIP => $details = [
                'Perusahaan/Industri: ' . $this->internship_company,
                'Alamat PKL: ' . $this->internship_address,
                'Periode PKL: ' . $this->internship_start_date . ' s/d ' . $this->internship_end_date,
            ],
            LetterRequest::TYPE_GOOD_BEHAVIOR => $details = [
                'Nama Wali Kelas: ' . $this->homeroom_teacher_name,
            ],
            LetterRequest::TYPE_TRANSFER => $details = [
                'Nama Orang Tua/Wali: ' . $this->parent_name,
                'Sekolah Tujuan: ' . $this->transfer_school_name,
                'Kota/Kabupaten Sekolah Tujuan: ' . $this->transfer_school_city,
                'Alasan Mutasi: ' . $this->transfer_reason,
            ],
            default => $details = [],
        };

        if (filled($this->notes)) {
            $details[] = 'Catatan Tambahan: ' . $this->notes;
        }

        return implode("\n", $details);
    }

    public function submit()
    {
        $this->validate();

        $student = auth()->user()->student;

        if (!$student) {
            session()->flash('error', 'Data siswa belum terhubung dengan akun Anda.');
            return;
        }

        $attachmentPaths = [];
        foreach ($this->attachments as $file) {
            $attachmentPaths[] = $file->store('letter-requests', 'public');
        }

        LetterRequest::create([
            'request_number' => LetterRequest::generateRequestNumber(),
            'letter_type' => $this->letter_type,
            'student_id' => $student->id,
            'requested_by' => auth()->id(),
            'purpose' => $this->purpose,
            'notes' => $this->buildTypeSpecificNotes(),
            'attachment' => $attachmentPaths,
            'status' => LetterRequest::STATUS_PENDING,
        ]);

        session()->flash('success', 'Permohonan surat berhasil diajukan! Silakan tunggu proses dari pihak sekolah.');

        return redirect()->route('student-portal.letter-requests');
    }

    public function with(): array
    {
        return [
            'letterTypes' => LetterRequest::TYPES,
            'typeDescriptions' => LetterRequest::TYPE_DESCRIPTIONS,
            'requirements' => LetterRequest::TYPE_REQUIREMENTS,
            'attachmentRequiredMap' => LetterRequest::TYPE_ATTACHMENT_REQUIRED,
            'requiredAttachmentCountMap' => LetterRequest::TYPE_REQUIRED_ATTACHMENT_COUNT,
            'typeIcons' => LetterRequest::TYPE_ICONS,
            'typeColors' => LetterRequest::TYPE_COLORS,
            'student' => auth()->user()->student,
        ];
    }
}; ?>

<div class="relative space-y-6">
    <div class="pointer-events-none absolute -top-8 -left-10 h-40 w-40 rounded-full bg-sky-300/25 blur-3xl"></div>
    <div class="pointer-events-none absolute top-20 -right-10 h-48 w-48 rounded-full bg-emerald-300/25 blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-amber-300/20 blur-3xl"></div>

    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl border border-sky-200/70 dark:border-sky-800/60 bg-linear-to-br from-sky-500 via-cyan-500 to-teal-500 p-5 md:p-6 shadow-lg">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.35),transparent_45%)]"></div>
        <div class="flex items-center gap-4">
            <flux:button icon="arrow-left" variant="filled" href="{{ route('student-portal.letter-requests') }}" wire:navigate class="bg-white/90 text-sky-700 hover:bg-white" />
            <div>
                <flux:heading size="xl" class="text-white">{{ __('Ajukan Permohonan Surat') }}</flux:heading>
                <flux:subheading class="text-cyan-50">{{ __('Pilih jenis surat, lengkapi data yang dibutuhkan, lalu kirim permohonan Anda.') }}</flux:subheading>
            </div>
        </div>
    </div>

    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    @if (!$student)
        <flux:card class="bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800">
            <div class="flex items-start gap-4 p-4">
                <flux:icon name="exclamation-triangle" class="size-8 text-yellow-500" />
                <div>
                    <flux:heading size="lg">{{ __('Data Siswa Belum Terhubung') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Anda tidak dapat mengajukan permohonan surat karena data siswa belum terhubung.') }}</flux:text>
                </div>
            </div>
        </flux:card>
    @else
        <form wire:submit="submit" class="space-y-6">
            <!-- Persyaratan Umum -->
            <flux:card class="border border-teal-200/80 dark:border-teal-800/60 shadow-sm">
                <flux:card.header>
                    <flux:heading size="lg">{{ __('Persyaratan Umum Pembuatan Surat di SMK') }}</flux:heading>
                    <flux:text size="sm" class="text-zinc-500 mt-1">{{ __('Pastikan dokumen persyaratan disiapkan sesuai jenis surat yang dipilih.') }}</flux:text>
                </flux:card.header>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700 text-left">
                                <th class="py-2 pr-3 font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Jenis Surat') }}</th>
                                <th class="py-2 font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Persyaratan Umum') }}</th>
                                <th class="py-2 pl-3 font-semibold text-zinc-700 dark:text-zinc-200 whitespace-nowrap">{{ __('Upload Wajib') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($letterTypes as $key => $label)
                                <tr class="border-b border-zinc-100 dark:border-zinc-800 align-top {{ $letter_type === $key ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}">
                                    <td class="py-3 pr-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $label }}</td>
                                    <td class="py-3 text-zinc-600 dark:text-zinc-300">
                                        <ul class="list-disc pl-5 space-y-1">
                                            @foreach ($requirements[$key] ?? [] as $requirement)
                                                <li>{{ $requirement }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="py-3 pl-3">
                                        <flux:badge size="sm" color="red">
                                            {{ __('Wajib') }}
                                        </flux:badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </flux:card>

            <!-- Step 1: Pilih Jenis Surat -->
            <flux:card class="border border-cyan-200/80 dark:border-cyan-800/60 shadow-sm">
                <flux:card.header>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 font-bold text-sm">1</div>
                        <flux:heading size="lg">{{ __('Pilih Jenis Surat') }}</flux:heading>
                    </div>
                </flux:card.header>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4">
                    @foreach ($letterTypes as $key => $label)
                        <button
                            type="button"
                            wire:click="$set('letter_type', '{{ $key }}')"
                            @class([
                                'group w-full text-left rounded-xl border-2 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-hidden focus:ring-2 focus:ring-cyan-300 dark:focus:ring-cyan-700',
                                'border-cyan-500 bg-linear-to-br from-cyan-50 to-sky-50 dark:from-cyan-900/30 dark:to-sky-900/20 shadow-sm' => $letter_type === $key,
                                'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900/40' => $letter_type !== $key,
                            ])
                        >
                            <div class="flex items-start gap-4">
                                <div class="p-3 rounded-full bg-white/80 dark:bg-zinc-800 ring-1 ring-cyan-100 dark:ring-cyan-900 transition group-hover:scale-105">
                                    <flux:icon :name="$typeIcons[$key]" class="size-6 text-cyan-700 dark:text-cyan-300" />
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <flux:text class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $label }}</flux:text>
                                        @if ($letter_type === $key)
                                            <flux:badge size="sm" color="cyan">{{ __('Dipilih') }}</flux:badge>
                                        @endif
                                    </div>
                                    <flux:text size="sm" class="text-zinc-600 dark:text-zinc-300">{{ $typeDescriptions[$key] ?? '-' }}</flux:text>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
                @error('letter_type')
                    <div class="px-4 pb-4">
                        <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                    </div>
                @enderror
            </flux:card>

            <!-- Step 2: Isi Formulir -->
            <flux:card class="border border-sky-200/80 dark:border-sky-800/60 shadow-sm">
                <flux:card.header>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300 font-bold text-sm">2</div>
                        <flux:heading size="lg">{{ __('Isi Formulir') }}</flux:heading>
                    </div>
                </flux:card.header>
                <div class="space-y-4 p-4">
                    <!-- Data Siswa (Read-only) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 rounded-lg bg-linear-to-r from-sky-50 to-teal-50 dark:from-sky-900/20 dark:to-teal-900/20 border border-sky-100 dark:border-sky-900/40">
                        <div>
                            <flux:text class="text-sm text-zinc-500">{{ __('Nama Siswa') }}</flux:text>
                            <flux:text class="font-medium">{{ $student->name }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm text-zinc-500">{{ __('NIS') }}</flux:text>
                            <flux:text class="font-medium">{{ $student->nis }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm text-zinc-500">{{ __('Kelas') }}</flux:text>
                            <flux:text class="font-medium">{{ $student->classroom?->name ?? '-' }}</flux:text>
                        </div>
                    </div>

                    <!-- Keperluan / Tujuan -->
                    <flux:textarea
                        wire:model="purpose"
                        label="{{ __('Keperluan / Tujuan Permohonan') }}"
                        placeholder="{{ __('Jelaskan keperluan atau tujuan Anda mengajukan surat ini...') }}"
                        rows="3"
                        required
                    />

                    <!-- Dynamic Detail Form -->
                    @if ($letter_type)
                        <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/60 p-4 space-y-4 bg-linear-to-br from-emerald-50 to-cyan-50 dark:from-emerald-900/20 dark:to-cyan-900/15">
                            <flux:heading size="md">{{ __('Detail Tambahan Sesuai Jenis Surat') }}</flux:heading>

                            @if ($letter_type === \App\Models\LetterRequest::TYPE_ACTIVE_STUDENT)
                                <flux:input
                                    wire:model="destination_institution"
                                    label="{{ __('Instansi Tujuan') }}"
                                    placeholder="{{ __('Contoh: Universitas A, Bank B, atau Lembaga C') }}"
                                    required
                                />
                                @error('destination_institution')
                                    <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                                @enderror
                            @endif

                            @if ($letter_type === \App\Models\LetterRequest::TYPE_INTERNSHIP)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <flux:input
                                            wire:model="internship_company"
                                            label="{{ __('Nama Perusahaan/Industri') }}"
                                            placeholder="{{ __('PT / CV / Instansi tujuan PKL') }}"
                                            required
                                        />
                                        @error('internship_company')
                                            <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input
                                            wire:model="internship_address"
                                            label="{{ __('Alamat Perusahaan/Industri') }}"
                                            placeholder="{{ __('Alamat lengkap tempat PKL') }}"
                                            required
                                        />
                                        @error('internship_address')
                                            <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <flux:input wire:model="internship_start_date" type="date" label="{{ __('Tanggal Mulai PKL') }}" required />
                                        @error('internship_start_date')
                                            <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input wire:model="internship_end_date" type="date" label="{{ __('Tanggal Selesai PKL') }}" required />
                                        @error('internship_end_date')
                                            <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            @if ($letter_type === \App\Models\LetterRequest::TYPE_GOOD_BEHAVIOR)
                                <flux:input
                                    wire:model="homeroom_teacher_name"
                                    label="{{ __('Nama Wali Kelas') }}"
                                    placeholder="{{ __('Masukkan nama wali kelas Anda') }}"
                                    required
                                />
                                @error('homeroom_teacher_name')
                                    <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                                @enderror
                            @endif

                            @if ($letter_type === \App\Models\LetterRequest::TYPE_TRANSFER)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <flux:input
                                            wire:model="parent_name"
                                            label="{{ __('Nama Orang Tua/Wali') }}"
                                            placeholder="{{ __('Nama orang tua/wali yang mengajukan') }}"
                                            required
                                        />
                                        @error('parent_name')
                                            <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input
                                            wire:model="transfer_school_name"
                                            label="{{ __('Nama Sekolah Tujuan') }}"
                                            placeholder="{{ __('Nama sekolah yang dituju') }}"
                                            required
                                        />
                                        @error('transfer_school_name')
                                            <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                </div>
                                <div>
                                    <flux:input
                                        wire:model="transfer_school_city"
                                        label="{{ __('Kota/Kabupaten Sekolah Tujuan') }}"
                                        placeholder="{{ __('Contoh: Minahasa, Manado, Tomohon') }}"
                                        required
                                    />
                                    @error('transfer_school_city')
                                        <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text>
                                    @enderror
                                </div>
                                <div>
                                    <flux:textarea
                                        wire:model="transfer_reason"
                                        label="{{ __('Alasan Mutasi') }}"
                                        placeholder="{{ __('Jelaskan alasan pindah/mutasi sekolah') }}"
                                        rows="3"
                                        required
                                    />
                                    @error('transfer_reason')
                                        <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text>
                                    @enderror
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Catatan Tambahan -->
                    <flux:textarea
                        wire:model="notes"
                        label="{{ __('Catatan Tambahan (Opsional)') }}"
                        placeholder="{{ __('Catatan tambahan jika ada...') }}"
                        rows="2"
                    />
                </div>
            </flux:card>

            <!-- Step 3: Upload Dokumen -->
            <flux:card class="border border-amber-200/80 dark:border-amber-800/60 shadow-sm">
                <flux:card.header>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 font-bold text-sm">3</div>
                        <flux:heading size="lg">{{ __('Upload Dokumen Pendukung') }}</flux:heading>
                    </div>
                    @if ($letter_type)
                        <flux:text size="sm" class="text-zinc-600 mt-1">
                            {{ __('Wajib untuk jenis surat ini. Jumlah berkas harus sesuai persyaratan: :count file. Format PDF/JPG/PNG, maksimal 2MB per file.', ['count' => $requiredAttachmentCountMap[$letter_type] ?? 0]) }}
                        </flux:text>
                    @else
                        <flux:text size="sm" class="text-zinc-600 mt-1">{{ __('Pilih jenis surat terlebih dahulu, lalu upload dokumen pendukung sesuai jumlah persyaratan.') }}</flux:text>
                    @endif
                </flux:card.header>
                <div class="p-4">
                    <flux:checkbox
                        wire:model="requirements_confirmed"
                        :disabled="!$letter_type"
                        :label="__('Saya sudah menyiapkan persyaratan sesuai jenis surat yang dipilih')"
                        class="mb-4"
                    />
                    @error('requirements_confirmed')
                        <flux:text class="text-red-500 text-sm mb-2">{{ $message }}</flux:text>
                    @enderror

                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed rounded-xl cursor-pointer border-amber-300 dark:border-amber-700 bg-amber-50/40 dark:bg-amber-900/10 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors">
                            <div class="flex flex-col items-center justify-center py-4">
                                @if (count($attachments) > 0)
                                    <flux:icon name="document-check" class="size-8 text-green-500 mb-2" />
                                    <flux:text class="text-sm font-medium text-green-600">{{ __(':count berkas dipilih', ['count' => count($attachments)]) }}</flux:text>
                                    <flux:text size="sm" class="text-zinc-500">{{ __('Wajib: :required berkas', ['required' => $requiredAttachmentCountMap[$letter_type] ?? 0]) }}</flux:text>
                                @else
                                    <flux:icon name="cloud-arrow-up" class="size-8 text-zinc-400 mb-2" />
                                    <flux:text class="text-sm text-zinc-500">{{ __('Klik untuk upload dokumen') }}</flux:text>
                                    <flux:text size="sm" class="text-zinc-400">{{ __('PDF, JPG, PNG (Maks 2MB / file)') }}</flux:text>
                                @endif
                            </div>
                            <input type="file" wire:model="attachments" class="hidden" accept=".pdf,.jpg,.jpeg,.png" multiple />
                        </label>
                    </div>
                    @if (count($attachments) > 0)
                        <div class="mt-3 space-y-1">
                            @foreach ($attachments as $file)
                                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-300">- {{ $file->getClientOriginalName() }}</flux:text>
                            @endforeach
                        </div>
                    @endif
                    @error('attachments')
                        <flux:text class="text-red-500 text-sm mt-2">{{ $message }}</flux:text>
                    @enderror
                    @error('attachments.*')
                        <flux:text class="text-red-500 text-sm mt-2">{{ $message }}</flux:text>
                    @enderror
                </div>
            </flux:card>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3">
                <flux:button variant="ghost" href="{{ route('student-portal.letter-requests') }}" wire:navigate>
                    {{ __('Batal') }}
                </flux:button>
                <flux:button type="submit" variant="primary" icon="paper-airplane" class="bg-linear-to-r from-cyan-600 to-teal-600 hover:from-cyan-700 hover:to-teal-700 shadow-md shadow-cyan-500/20">
                    {{ __('Ajukan Permohonan') }}
                </flux:button>
            </div>
        </form>
    @endif
</div>
