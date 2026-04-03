<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Manajemen User')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterRole = '';
    public string $filterStatus = '';
    public bool $showModal = false;
    public ?User $editingUser = null;

    // Form fields
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?int $role_id = null;
    public bool $is_active = true;

    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $this->editingUser?->id],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['boolean'],
        ];

        if (!$this->editingUser) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('role')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->when($this->filterRole, fn($q) => $q->where('role_id', $this->filterRole))
            ->when($this->filterStatus !== '', fn($q) => $q->where('is_active', $this->filterStatus === '1'))
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function roles()
    {
        return Role::active()->orderBy('display_name')->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(User $user): void
    {
        $this->editingUser = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role_id = $user->role_id;
        $this->is_active = $user->is_active;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUser) {
            // Prevent deactivating own account
            if ($this->editingUser->id === auth()->id() && !$this->is_active) {
                $this->addError('is_active', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
                return;
            }
            $this->editingUser->update($data);
            session()->flash('success', 'User berhasil diperbarui.');
        } else {
            User::create($data);
            session()->flash('success', 'User berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(User $user): void
    {
        if ($user->id === auth()->id()) {
            session()->flash('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            return;
        }

        $user->update(['is_active' => !$user->is_active]);
        session()->flash('success', 'Status user berhasil diubah.');
    }

    public function delete(User $user): void
    {
        if ($user->id === auth()->id()) {
            session()->flash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            return;
        }

        $user->delete();
        session()->flash('success', 'User berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->editingUser = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->role_id = null;
        $this->is_active = true;
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manajemen User') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Kelola pengguna sistem dan akses mereka.') }}</flux:text>
        </div>
        <flux:button wire:click="create" icon="plus">
            {{ __('Tambah User') }}
        </flux:button>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle">
            {{ session('error') }}
        </flux:callout>
    @endif

    <!-- Filters -->
    <flux:card>
        <flux:card.body>
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Cari user...') }}"
                        icon="magnifying-glass"
                    />
                </div>
                <div class="w-full md:w-48">
                    <flux:select wire:model.live="filterRole">
                        <option value="">{{ __('Semua Role') }}</option>
                        @foreach ($this->roles as $role)
                            <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="w-full md:w-48">
                    <flux:select wire:model.live="filterStatus">
                        <option value="">{{ __('Semua Status') }}</option>
                        <option value="1">{{ __('Aktif') }}</option>
                        <option value="0">{{ __('Tidak Aktif') }}</option>
                    </flux:select>
                </div>
            </div>
        </flux:card.body>
    </flux:card>

    <!-- Table -->
    <flux:card wire:poll.15s>
        <flux:card.body class="p-0">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Nama') }}</flux:table.column>
                    <flux:table.column>{{ __('Email') }}</flux:table.column>
                    <flux:table.column>{{ __('Role') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Terakhir Login') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Aksi') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->users as $user)
                        <flux:table.row wire:key="user-{{ $user->id }}">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar :initials="$user->initials()" size="sm" />
                                    <div>
                                        <flux:text class="font-medium">{{ $user->name }}</flux:text>
                                        @if ($user->id === auth()->id())
                                            <flux:badge size="sm" color="blue">Anda</flux:badge>
                                        @endif
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $user->email }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $user->role?->name === 'admin' ? 'red' : 'zinc' }}">
                                    {{ $user->role?->display_name ?? '-' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($user->is_active)
                                    <flux:badge color="green">Aktif</flux:badge>
                                @else
                                    <flux:badge color="zinc">Tidak Aktif</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($user->last_login_at)
                                    <div class="text-sm">
                                        <div>{{ $user->last_login_at->translatedFormat('d M Y H:i:s') }}</div>
                                        <div class="text-xs text-zinc-500">{{ $user->last_login_at->diffForHumans() }}</div>
                                    </div>
                                @else
                                    <flux:text class="text-zinc-400">-</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                    <flux:menu>
                                        <flux:menu.item wire:click="edit({{ $user->id }})" icon="pencil">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                        @if ($user->id !== auth()->id())
                                            <flux:menu.item wire:click="toggleActive({{ $user->id }})" icon="{{ $user->is_active ? 'x-circle' : 'check-circle' }}">
                                                {{ $user->is_active ? __('Nonaktifkan') : __('Aktifkan') }}
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item
                                                wire:click="delete({{ $user->id }})"
                                                wire:confirm="{{ __('Apakah Anda yakin ingin menghapus user ini?') }}"
                                                icon="trash"
                                                variant="danger"
                                            >
                                                {{ __('Hapus') }}
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-8">
                                <flux:text class="text-zinc-500">{{ __('Belum ada data user.') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card.body>

        @if ($this->users->hasPages())
            <flux:card.footer>
                {{ $this->users->links() }}
            </flux:card.footer>
        @endif
    </flux:card>

    <!-- Modal Form -->
    <flux:modal wire:model="showModal" class="max-w-lg">
        <form wire:submit="save">
            <flux:modal.header>
                <flux:heading size="lg">
                    {{ $editingUser ? __('Edit User') : __('Tambah User') }}
                </flux:heading>
            </flux:modal.header>

            <flux:modal.body class="space-y-4">
                <flux:input
                    wire:model="name"
                    label="{{ __('Nama Lengkap') }}"
                    placeholder="John Doe"
                    required
                />

                <flux:input
                    wire:model="email"
                    type="email"
                    label="{{ __('Email') }}"
                    placeholder="john@example.com"
                    required
                />

                <flux:select
                    wire:model="role_id"
                    label="{{ __('Role') }}"
                    required
                >
                    <option value="">{{ __('Pilih Role') }}</option>
                    @foreach ($this->roles as $role)
                        <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="password"
                    type="password"
                    label="{{ __('Password') }}"
                    placeholder="{{ $editingUser ? __('Kosongkan jika tidak ingin mengubah') : '********' }}"
                    :required="!$editingUser"
                />

                <flux:input
                    wire:model="password_confirmation"
                    type="password"
                    label="{{ __('Konfirmasi Password') }}"
                    placeholder="********"
                    :required="!$editingUser"
                />

                <flux:switch
                    wire:model="is_active"
                    label="{{ __('Status Aktif') }}"
                    description="{{ __('User aktif dapat login ke sistem') }}"
                />
            </flux:modal.body>

            <flux:modal.footer>
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">
                    {{ __('Batal') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $editingUser ? __('Simpan Perubahan') : __('Tambah User') }}
                </flux:button>
            </flux:modal.footer>
        </form>
    </flux:modal>
</div>
