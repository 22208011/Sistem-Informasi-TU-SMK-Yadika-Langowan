<?php

use App\Models\Student;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Detail Siswa')] class extends Component {
    public Student $student;

    public function mount(Student $student): void
    {
        $this->student = $student->load(['classroom', 'department', 'academicYear', 'guardians']);
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:button :href="route('students.index')" variant="ghost" icon="arrow-left" wire:navigate class="mb-4">
            {{ __('Kembali') }}
        </flux:button>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $student->name }}</flux:heading>
                <flux:text class="mt-1">
                    NIS: {{ $student->nis }}
                    @if ($student->nisn)
                        | NISN: {{ $student->nisn }}
                    @endif
                </flux:text>
            </div>
            <flux:button :href="route('students.edit', $student)" variant="primary" icon="pencil" wire:navigate>
                {{ __('Edit Data') }}
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Photo & Basic Info -->
        <div class="lg:col-span-1">
            <flux:card>
                <flux:card.body class="text-center">
                    @if ($student->photo)
                        <img src="{{ $student->photo_url }}" alt="{{ $student->name }}" class="w-32 h-32 rounded-full object-cover mx-auto mb-4" />
                    @else
                        <div class="w-32 h-32 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-4">
                            <span class="text-4xl font-medium">{{ substr($student->name, 0, 1) }}</span>
                        </div>
                    @endif

                    <flux:heading size="lg">{{ $student->name }}</flux:heading>

                    <div class="mt-2 space-y-1">
                        <flux:text size="sm">NIS: {{ $student->nis }}</flux:text>
                        @if ($student->nisn)
                            <flux:text size="sm" class="text-zinc-500">NISN: {{ $student->nisn }}</flux:text>
                        @endif
                    </div>

                    <div class="mt-4 flex justify-center gap-2 flex-wrap">
                        <flux:badge color="{{ App\Models\Student::STATUS_COLORS[$student->status] ?? 'zinc' }}">
                            {{ App\Models\Student::STATUSES[$student->status] }}
                        </flux:badge>
                        @if ($student->classroom)
                            <flux:badge color="blue">{{ $student->classroom->name }}</flux:badge>
                        @endif
                        @if ($student->department)
                            <flux:badge color="purple">{{ $student->department->code }}</flux:badge>
                        @endif
                    </div>
                </flux:card.body>
            </flux:card>
        </div>

        <!-- Detail Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal Info -->
            <flux:card>
                <flux:card.header>
                    <flux:heading size="lg">{{ __('Informasi Pribadi') }}</flux:heading>
                </flux:card.header>

                <flux:card.body>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Jenis Kelamin') }}</dt>
                            <dd class="font-medium">{{ App\Models\Student::GENDERS[$student->gender] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Tempat, Tanggal Lahir') }}</dt>
                            <dd class="font-medium">
                                {{ $student->place_of_birth ?? '-' }}
                                @if ($student->date_of_birth)
                                    , {{ $student->date_of_birth->format('d F Y') }}
                                    ({{ $student->age }} tahun)
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Agama') }}</dt>
                            <dd class="font-medium">{{ App\Models\Student::RELIGIONS[$student->religion] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Telepon') }}</dt>
                            <dd class="font-medium">{{ $student->phone ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Email') }}</dt>
                            <dd class="font-medium">{{ $student->email ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm text-zinc-500">{{ __('Alamat') }}</dt>
                            <dd class="font-medium">{{ $student->address ?? '-' }}</dd>
                        </div>
                    </dl>
                </flux:card.body>
            </flux:card>

            <!-- Academic Info -->
            <flux:card>
                <flux:card.header>
                    <flux:heading size="lg">{{ __('Informasi Akademik') }}</flux:heading>
                </flux:card.header>

                <flux:card.body>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Asal Sekolah') }}</dt>
                            <dd class="font-medium">{{ $student->previous_school ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Tahun Masuk') }}</dt>
                            <dd class="font-medium">{{ $student->entry_year }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Jurusan') }}</dt>
                            <dd class="font-medium">{{ $student->department?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Kelas') }}</dt>
                            <dd class="font-medium">{{ $student->classroom?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Tahun Ajaran Masuk') }}</dt>
                            <dd class="font-medium">{{ $student->academicYear?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Status') }}</dt>
                            <dd>
                                <flux:badge color="{{ App\Models\Student::STATUS_COLORS[$student->status] ?? 'zinc' }}">
                                    {{ App\Models\Student::STATUSES[$student->status] }}
                                </flux:badge>
                            </dd>
                        </div>
                    </dl>
                </flux:card.body>
            </flux:card>

            <!-- Guardians Info -->
            <flux:card>
                <flux:card.header>
                    <flux:heading size="lg">{{ __('Data Orang Tua/Wali') }}</flux:heading>
                </flux:card.header>

                <flux:card.body>
                    @forelse ($student->guardians as $guardian)
                        <div class="@if(!$loop->last) mb-6 pb-6 border-b border-zinc-200 dark:border-zinc-700 @endif">
                            <div class="flex items-center gap-2 mb-3">
                                <flux:heading size="sm">
                                    {{ App\Models\Guardian::RELATIONSHIPS[$guardian->relationship] ?? $guardian->relationship }}
                                </flux:heading>
                                @if ($guardian->is_primary)
                                    <flux:badge color="green" size="sm">Wali Utama</flux:badge>
                                @endif
                            </div>

                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm text-zinc-500">{{ __('Nama') }}</dt>
                                    <dd class="font-medium">{{ $guardian->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-zinc-500">{{ __('NIK') }}</dt>
                                    <dd class="font-medium">{{ $guardian->nik ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-zinc-500">{{ __('Telepon') }}</dt>
                                    <dd class="font-medium">{{ $guardian->phone ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-zinc-500">{{ __('Pekerjaan') }}</dt>
                                    <dd class="font-medium">{{ $guardian->occupation ?? '-' }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-sm text-zinc-500">{{ __('Alamat') }}</dt>
                                    <dd class="font-medium">{{ $guardian->address ?? '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    @empty
                        <flux:text class="text-zinc-500">{{ __('Belum ada data orang tua/wali.') }}</flux:text>
                    @endforelse
                </flux:card.body>
            </flux:card>
        </div>
    </div>
</div>
