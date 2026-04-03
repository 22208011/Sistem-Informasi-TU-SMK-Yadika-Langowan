<?php

use App\Models\Employee;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Detail Pegawai')] class extends Component {
    public Employee $employee;

    public function mount(Employee $employee): void
    {
        $this->employee = $employee->load(['position', 'department', 'user']);
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:button :href="route('employees.index')" variant="ghost" icon="arrow-left" wire:navigate class="mb-4">
            {{ __('Kembali') }}
        </flux:button>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $employee->name }}</flux:heading>
                <flux:text class="mt-1">
                    {{ App\Models\Employee::TYPES[$employee->employee_type] ?? $employee->employee_type }}
                    @if ($employee->position)
                        - {{ $employee->position->name }}
                    @endif
                </flux:text>
            </div>
            <flux:button :href="route('employees.edit', $employee)" variant="primary" icon="pencil" wire:navigate>
                {{ __('Edit Data') }}
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Photo & Basic Info -->
        <div class="lg:col-span-1">
            <flux:card>
                <flux:card.body class="text-center">
                    @if ($employee->photo)
                        <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" class="w-32 h-32 rounded-full object-cover mx-auto mb-4" />
                    @else
                        <div class="w-32 h-32 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-4">
                            <span class="text-4xl font-medium">{{ substr($employee->name, 0, 1) }}</span>
                        </div>
                    @endif

                    <flux:heading size="lg">{{ $employee->name }}</flux:heading>

                    <div class="mt-2 space-y-1">
                        @if ($employee->nip)
                            <flux:text size="sm">NIP: {{ $employee->nip }}</flux:text>
                        @endif
                        @if ($employee->nuptk)
                            <flux:text size="sm" class="text-zinc-500">NUPTK: {{ $employee->nuptk }}</flux:text>
                        @endif
                    </div>

                    <div class="mt-4 flex justify-center gap-2">
                        <flux:badge color="{{ $employee->employee_type === 'guru' ? 'blue' : 'purple' }}">
                            {{ App\Models\Employee::TYPES[$employee->employee_type] }}
                        </flux:badge>
                        <flux:badge color="{{ $employee->employee_status === 'pns' ? 'green' : 'zinc' }}">
                            {{ App\Models\Employee::STATUSES[$employee->employee_status] }}
                        </flux:badge>
                    </div>

                    @if (!$employee->is_active)
                        <div class="mt-4">
                            <flux:badge color="red">Tidak Aktif</flux:badge>
                        </div>
                    @endif
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
                            <dd class="font-medium">{{ App\Models\Employee::GENDERS[$employee->gender] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Tempat, Tanggal Lahir') }}</dt>
                            <dd class="font-medium">
                                {{ $employee->place_of_birth ?? '-' }}
                                @if ($employee->date_of_birth)
                                    , {{ $employee->date_of_birth->format('d F Y') }}
                                    ({{ $employee->age }} tahun)
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Agama') }}</dt>
                            <dd class="font-medium">{{ App\Models\Employee::RELIGIONS[$employee->religion] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Alamat') }}</dt>
                            <dd class="font-medium">{{ $employee->address ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Telepon') }}</dt>
                            <dd class="font-medium">{{ $employee->phone ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Email') }}</dt>
                            <dd class="font-medium">{{ $employee->email ?? '-' }}</dd>
                        </div>
                    </dl>
                </flux:card.body>
            </flux:card>

            <!-- Employment Info -->
            <flux:card>
                <flux:card.header>
                    <flux:heading size="lg">{{ __('Informasi Kepegawaian') }}</flux:heading>
                </flux:card.header>

                <flux:card.body>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Tipe Pegawai') }}</dt>
                            <dd class="font-medium">{{ App\Models\Employee::TYPES[$employee->employee_type] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Status Kepegawaian') }}</dt>
                            <dd class="font-medium">{{ App\Models\Employee::STATUSES[$employee->employee_status] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Jabatan') }}</dt>
                            <dd class="font-medium">{{ $employee->position?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Jurusan') }}</dt>
                            <dd class="font-medium">{{ $employee->department?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Tanggal Mulai Bekerja') }}</dt>
                            <dd class="font-medium">{{ $employee->join_date?->format('d F Y') ?? '-' }}</dd>
                        </div>
                    </dl>
                </flux:card.body>
            </flux:card>

            <!-- Education Info -->
            <flux:card>
                <flux:card.header>
                    <flux:heading size="lg">{{ __('Pendidikan') }}</flux:heading>
                </flux:card.header>

                <flux:card.body>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Jenjang Pendidikan') }}</dt>
                            <dd class="font-medium">{{ App\Models\Employee::EDUCATION_LEVELS[$employee->education_level] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">{{ __('Jurusan/Program Studi') }}</dt>
                            <dd class="font-medium">{{ $employee->education_major ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm text-zinc-500">{{ __('Institusi Pendidikan') }}</dt>
                            <dd class="font-medium">{{ $employee->education_institution ?? '-' }}</dd>
                        </div>
                    </dl>
                </flux:card.body>
            </flux:card>
        </div>
    </div>
</div>
