<?php

use App\Models\Guardian;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Data Wali Murid')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterRelationship = '';
    public ?int $selectedStudentId = null;

    // Form properties
    public bool $showModal = false;
    public ?int $editingId = null;
    public int $student_id = 0;
    public string $relationship = '';
    public string $name = '';
    public string $nik = '';
    public string $place_of_birth = '';
    public ?string $date_of_birth = null;
    public string $religion = '';
    public string $education = '';
    public string $occupation = '';
    public string $income = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public bool $is_primary = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterRelationship(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function guardians()
    {
        return Guardian::query()
            ->with('student')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('nik', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhereHas('student', function ($sq) {
                            $sq->where('name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->filterRelationship, function ($query) {
                $query->where('relationship', $this->filterRelationship);
            })
            ->when($this->selectedStudentId, function ($query) {
                $query->where('student_id', $this->selectedStudentId);
            })
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function students()
    {
        return Student::query()
            ->active()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function relationships(): array
    {
        return Guardian::RELATIONSHIPS;
    }

    #[Computed]
    public function educations(): array
    {
        return Guardian::EDUCATIONS;
    }

    #[Computed]
    public function incomes(): array
    {
        return Guardian::INCOMES;
    }

    #[Computed]
    public function totalGuardians(): int
    {
        return Guardian::count();
    }

    #[Computed]
    public function totalParentAccounts(): int
    {
        return Guardian::whereNotNull('user_id')->count();
    }

    #[Computed]
    public function guardiansWithoutAccount(): int
    {
        return Guardian::whereNull('user_id')->count();
    }

    #[Computed]
    public function religions(): array
    {
        return [
            'islam' => 'Islam',
            'kristen' => 'Kristen Protestan',
            'katolik' => 'Katolik',
            'hindu' => 'Hindu',
            'buddha' => 'Buddha',
            'konghucu' => 'Konghucu',
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(Guardian $guardian): void
    {
        $this->editingId = $guardian->id;
        $this->student_id = $guardian->student_id;
        $this->relationship = $guardian->relationship;
        $this->name = $guardian->name;
        $this->nik = $guardian->nik ?? '';
        $this->place_of_birth = $guardian->place_of_birth ?? '';
        $this->date_of_birth = $guardian->date_of_birth?->format('Y-m-d');
        $this->religion = $guardian->religion ?? '';
        $this->education = $guardian->education ?? '';
        $this->occupation = $guardian->occupation ?? '';
        $this->income = $guardian->income ?? '';
        $this->address = $guardian->address ?? '';
        $this->phone = $guardian->phone ?? '';
        $this->email = $guardian->email ?? '';
        $this->is_primary = $guardian->is_primary;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'student_id' => 'required|exists:students,id',
            'relationship' => 'required|in:' . implode(',', array_keys(Guardian::RELATIONSHIPS)),
            'name' => 'required|string|max:255',
            'nik' => 'nullable|string|size:16',
            'place_of_birth' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'religion' => 'nullable|string|max:50',
            'education' => 'nullable|string|max:50',
            'occupation' => 'nullable|string|max:100',
            'income' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email:rfc,dns|max:100',
            'is_primary' => 'boolean',
        ], [
            'email.email' => 'Email tidak valid atau domain email tidak terdeteksi.',
        ]);

        // If is_primary is true, set others to false
        if ($validated['is_primary']) {
            Guardian::where('student_id', $validated['student_id'])
                ->when($this->editingId, function ($query) {
                    $query->where('id', '!=', $this->editingId);
                })
                ->update(['is_primary' => false]);
        }

        $createdNewAccount = false;

        if ($this->editingId) {
            $guardian = Guardian::findOrFail($this->editingId);
            $guardian->fill($validated);
            $createdNewAccount = $this->syncParentAccount($guardian);
            $guardian->save();

            $message = 'Data wali murid berhasil diperbarui.';
        } else {
            $guardian = new Guardian($validated);
            $createdNewAccount = $this->syncParentAccount($guardian);
            $guardian->save();

            $message = 'Data wali murid berhasil ditambahkan.';
        }

        if ($createdNewAccount) {
            $message .= ' Akun login orang tua otomatis dibuat (password awal: password).';
        }

        session()->flash('success', $message);

        $this->showModal = false;
        $this->resetForm();
    }

    private function syncParentAccount(Guardian $guardian): bool
    {
        $email = strtolower(trim((string) $guardian->email));

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'Email wajib diisi.',
            ]);
        }

        $this->ensureEmailLooksReal($email, 'email');

        $guardian->email = $email;

        $parentRoleId = Role::where('name', Role::ORANG_TUA)->value('id');
        if (! $parentRoleId) {
            throw ValidationException::withMessages([
                'email' => 'Role orang tua belum tersedia. Jalankan seeder role terlebih dahulu.',
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
                            'email' => 'Email sudah dipakai oleh akun non orang tua.',
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
                    'email' => 'Email sudah dipakai oleh akun non orang tua.',
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

    private function ensureEmailLooksReal(string $email, string $field): void
    {
        $domain = strtolower((string) substr(strrchr($email, '@') ?: '', 1));

        if ($domain === '') {
            throw ValidationException::withMessages([
                $field => 'Domain email tidak valid.',
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
                $field => 'Gunakan email asli, bukan domain contoh/test.',
            ]);
        }
    }

    public function delete(Guardian $guardian): void
    {
        $guardian->delete();
        session()->flash('success', 'Data wali murid berhasil dihapus');
    }

    public function setPrimary(Guardian $guardian): void
    {
        // Set all other guardians of this student to not primary
        Guardian::where('student_id', $guardian->student_id)
            ->where('id', '!=', $guardian->id)
            ->update(['is_primary' => false]);

        $guardian->update(['is_primary' => true]);
        session()->flash('success', 'Wali utama berhasil diubah');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->student_id = 0;
        $this->relationship = '';
        $this->name = '';
        $this->nik = '';
        $this->place_of_birth = '';
        $this->date_of_birth = null;
        $this->religion = '';
        $this->education = '';
        $this->occupation = '';
        $this->income = '';
        $this->address = '';
        $this->phone = '';
        $this->email = '';
        $this->is_primary = false;
        $this->resetValidation();
    }
}; ?>

<div>
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V9a1 1 0 00-1-1h-4m0 12H7m10 0v-6a1 1 0 00-1-1H8a1 1 0 00-1 1v6m0 0H2v-9a1 1 0 011-1h4m0 0V4a1 1 0 011-1h8a1 1 0 011 1v4m-10 0h10" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Data Wali Murid') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Kelola data wali murid/orang tua siswa dengan lebih terstruktur.') }}</x-slot:subtitle>
        <x-slot:actions>
            @can('students.create')
            <flux:button wire:click="create" icon="plus" class="rounded-xl! bg-linear-to-r! from-sky-600! to-indigo-600! hover:from-sky-700! hover:to-indigo-700! shadow-lg! shadow-sky-500!/20">
                {{ __('Tambah Wali') }}
            </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <flux:card class="border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-emerald-100 p-2 dark:bg-emerald-900/40">
                    <flux:icon name="users" class="size-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <p class="text-xs text-emerald-700 dark:text-emerald-400">Total Data Wali</p>
                    <p class="text-2xl font-semibold text-emerald-800 dark:text-emerald-300">{{ number_format($this->totalGuardians) }}</p>
                </div>
            </div>
        </flux:card>

        <flux:card class="border-sky-200 bg-sky-50 dark:border-sky-800 dark:bg-sky-900/20">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-sky-100 p-2 dark:bg-sky-900/40">
                    <flux:icon name="key" class="size-5 text-sky-600 dark:text-sky-400" />
                </div>
                <div>
                    <p class="text-xs text-sky-700 dark:text-sky-400">Akun Orang Tua Aktif</p>
                    <p class="text-2xl font-semibold text-sky-800 dark:text-sky-300">{{ number_format($this->totalParentAccounts) }}</p>
                </div>
            </div>
        </flux:card>

        <flux:card class="border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-900/40">
                    <flux:icon name="exclamation-triangle" class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-xs text-amber-700 dark:text-amber-400">Belum Taut Akun</p>
                    <p class="text-2xl font-semibold text-amber-800 dark:text-amber-300">{{ number_format($this->guardiansWithoutAccount) }}</p>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <x-elegant-card class="mb-6" :hover="false">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center lg:max-w-2xl">
                <div class="relative w-full sm:max-w-sm">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-zinc-400 dark:text-zinc-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama, NIK, no. HP..."
                        class="h-11 w-full rounded-xl border border-zinc-300 bg-zinc-50 pl-10 pr-4 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-200 dark:placeholder:text-zinc-500 dark:focus:border-sky-400 dark:focus:ring-sky-900/40"
                    />
                </div>

                <flux:select wire:model.live="filterRelationship" class="w-full sm:w-48 rounded-xl! bg-zinc-50! dark:bg-zinc-800/50!">
                    <flux:select.option value="">Semua Hubungan</flux:select.option>
                    @foreach($this->relationships as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                Menampilkan {{ $this->guardians->count() }} dari {{ $this->guardians->total() }} data
            </div>
        </div>
    </x-elegant-card>

    <!-- Data Table -->
    <x-elegant-card :noPadding="true" class="overflow-hidden" :hover="false">
        <div class="overflow-x-auto">
            <flux:table class="table-elegant">
                <flux:table.columns>
                    <flux:table.column class="font-semibold!">{{ __('Nama Siswa') }}</flux:table.column>
                    <flux:table.column class="font-semibold!">{{ __('Hubungan') }}</flux:table.column>
                    <flux:table.column class="font-semibold!">{{ __('Nama Wali') }}</flux:table.column>
                    <flux:table.column class="font-semibold!">{{ __('Email Login') }}</flux:table.column>
                    <flux:table.column class="font-semibold!">{{ __('No. HP') }}</flux:table.column>
                    <flux:table.column class="font-semibold!">{{ __('Pekerjaan') }}</flux:table.column>
                    <flux:table.column class="font-semibold!">{{ __('Status Akun') }}</flux:table.column>
                    <flux:table.column class="text-right font-semibold!">{{ __('Aksi') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->guardians as $guardian)
                        <flux:table.row wire:key="guardian-{{ $guardian->id }}" class="hover:bg-sky-50/50! dark:hover:bg-sky-900/10! transition-colors">
                            <flux:table.cell>
                                <a href="{{ route('students.show', $guardian->student) }}" class="text-blue-600 hover:underline" wire:navigate>
                                    {{ $guardian->student->name }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="{{ $guardian->relationship === 'ayah' ? 'blue' : ($guardian->relationship === 'ibu' ? 'pink' : 'gray') }}">
                                    {{ Guardian::RELATIONSHIPS[$guardian->relationship] ?? $guardian->relationship }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $guardian->name }}</flux:table.cell>
                            <flux:table.cell>{{ $guardian->email ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $guardian->phone ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $guardian->occupation ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                @if($guardian->user_id)
                                    <flux:badge size="sm" color="green">Login Aktif</flux:badge>
                                @else
                                    <flux:badge size="sm" color="amber">Belum Aktif</flux:badge>
                                @endif
                                @if($guardian->is_primary)
                                    <flux:badge size="sm" color="blue" class="mt-1">Wali Utama</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="rounded-lg! hover:bg-zinc-100! dark:hover:bg-zinc-800!" />
                                    <flux:menu class="rounded-xl! shadow-xl! border-zinc!-200/50 dark:border-zinc!-700/50">
                                        @can('students.edit')
                                        <flux:menu.item wire:click="edit({{ $guardian->id }})" icon="pencil">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                        @if(!$guardian->is_primary)
                                        <flux:menu.item wire:click="setPrimary({{ $guardian->id }})" icon="star">
                                            {{ __('Jadikan Wali Utama') }}
                                        </flux:menu.item>
                                        @endif
                                        @endcan
                                        @can('students.delete')
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="delete({{ $guardian->id }})" wire:confirm="Yakin ingin menghapus data wali ini?" icon="trash" variant="danger">
                                            {{ __('Hapus') }}
                                        </flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center py-8">
                                <flux:icon name="users" class="mx-auto h-12 w-12 text-zinc-400" />
                                <flux:text class="mt-2">{{ __('Belum ada data wali murid') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800/60 bg-zinc-50/50 dark:bg-zinc-800/30">
            {{ $this->guardians->links() }}
        </div>
    </x-elegant-card>

    <!-- Modal Form -->
    <flux:modal wire:model="showModal" name="guardian-form" class="md:w-150">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Edit Wali Murid') : __('Tambah Wali Murid') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Lengkapi data wali murid di bawah ini. Email wajib karena dipakai untuk login orang tua.') }}</flux:text>
            </div>

            <form wire:submit="save" class="space-y-4">
                <flux:select wire:model="student_id" label="Siswa" required>
                    <flux:select.option value="">-- Pilih Siswa --</flux:select.option>
                    @foreach($this->students as $student)
                        <flux:select.option value="{{ $student->id }}">{{ $student->name }} ({{ $student->nis }})</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="relationship" label="Hubungan" required>
                        <flux:select.option value="">-- Pilih --</flux:select.option>
                        @foreach($this->relationships as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="name" label="Nama Lengkap" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="nik" label="NIK" maxlength="16" placeholder="16 digit NIK" />
                    <flux:input wire:model="phone" label="No. HP" placeholder="08xxxxxxxxxx" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="place_of_birth" label="Tempat Lahir" />
                    <flux:input wire:model="date_of_birth" type="date" label="Tanggal Lahir" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="religion" label="Agama">
                        <flux:select.option value="">-- Pilih --</flux:select.option>
                        @foreach($this->religions as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="education" label="Pendidikan Terakhir">
                        <flux:select.option value="">-- Pilih --</flux:select.option>
                        @foreach($this->educations as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="occupation" label="Pekerjaan" />
                    <flux:select wire:model="income" label="Penghasilan">
                        <flux:select.option value="">-- Pilih --</flux:select.option>
                        @foreach($this->incomes as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="rounded-xl border border-sky-200 bg-sky-50 p-3 text-xs text-sky-700 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-300">
                    Email ini akan digunakan sebagai username login orang tua. Jika akun belum ada, sistem otomatis membuat akun dengan password awal: <strong>password</strong>.
                </div>

                <flux:input wire:model="email" type="email" label="Email Login Orang Tua" required />

                <flux:textarea wire:model="address" label="Alamat" rows="2" />

                <flux:checkbox wire:model="is_primary" label="Jadikan sebagai wali utama" />

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button variant="ghost" wire:click="$set('showModal', false)">
                        {{ __('Batal') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ $editingId ? __('Simpan Perubahan') : __('Tambah') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
