<?php

use App\Models\Permission;
use App\Models\Role;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Manajemen Role')] class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public bool $showPermissionModal = false;
    public ?Role $editingRole = null;
    public ?Role $selectedRole = null;

    // Form fields
    public string $name = '';
    public string $display_name = '';
    public string $description = '';
    public bool $is_active = true;
    public array $selectedPermissions = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'unique:roles,name,' . $this->editingRole?->id],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->withCount(['users', 'permissions'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('display_name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function permissions()
    {
        return Permission::all()->groupBy('module');
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(Role $role): void
    {
        $this->editingRole = $role;
        $this->name = $role->name;
        $this->display_name = $role->display_name;
        $this->description = $role->description ?? '';
        $this->is_active = $role->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingRole) {
            // Prevent editing admin role name
            if ($this->editingRole->name === Role::ADMIN && $this->name !== Role::ADMIN) {
                $this->addError('name', 'Nama role admin tidak dapat diubah.');
                return;
            }
            $this->editingRole->update($data);
            session()->flash('success', 'Role berhasil diperbarui.');
        } else {
            Role::create($data);
            session()->flash('success', 'Role berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(Role $role): void
    {
        if ($role->name === Role::ADMIN) {
            session()->flash('error', 'Role admin tidak dapat dihapus.');
            return;
        }

        if ($role->users()->count() > 0) {
            session()->flash('error', 'Role masih memiliki user. Pindahkan user terlebih dahulu.');
            return;
        }

        $role->permissions()->detach();
        $role->delete();
        session()->flash('success', 'Role berhasil dihapus.');
    }

    public function managePermissions(Role $role): void
    {
        $this->selectedRole = $role;
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        $this->showPermissionModal = true;
    }

    public function savePermissions(): void
    {
        if ($this->selectedRole) {
            $this->selectedRole->permissions()->sync($this->selectedPermissions);
            session()->flash('success', 'Permission berhasil diperbarui untuk role ' . $this->selectedRole->display_name);
        }
        $this->showPermissionModal = false;
        $this->selectedRole = null;
        $this->selectedPermissions = [];
    }

    public function resetForm(): void
    {
        $this->editingRole = null;
        $this->name = '';
        $this->display_name = '';
        $this->description = '';
        $this->is_active = true;
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manajemen Role') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Kelola role dan akses pengguna sistem.') }}</flux:text>
        </div>
        <flux:button wire:click="create" icon="plus">
            {{ __('Tambah Role') }}
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

    <!-- Search -->
    <flux:card>
        <flux:card.body>
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Cari role...') }}"
                icon="magnifying-glass"
            />
        </flux:card.body>
    </flux:card>

    <!-- Table -->
    <flux:card>
        <flux:card.body class="p-0">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Role') }}</flux:table.column>
                    <flux:table.column>{{ __('Nama Tampilan') }}</flux:table.column>
                    <flux:table.column>{{ __('Jumlah User') }}</flux:table.column>
                    <flux:table.column>{{ __('Jumlah Permission') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Aksi') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->roles as $role)
                        <flux:table.row wire:key="role-{{ $role->id }}">
                            <flux:table.cell class="font-medium">
                                <flux:badge color="{{ $role->name === 'admin' ? 'red' : 'blue' }}">
                                    {{ $role->name }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $role->display_name }}</flux:table.cell>
                            <flux:table.cell>{{ $role->users_count }} user</flux:table.cell>
                            <flux:table.cell>{{ $role->permissions_count }} permission</flux:table.cell>
                            <flux:table.cell>
                                @if ($role->is_active)
                                    <flux:badge color="green">Aktif</flux:badge>
                                @else
                                    <flux:badge color="zinc">Tidak Aktif</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                    <flux:menu>
                                        <flux:menu.item wire:click="managePermissions({{ $role->id }})" icon="key">
                                            {{ __('Kelola Permission') }}
                                        </flux:menu.item>
                                        <flux:menu.item wire:click="edit({{ $role->id }})" icon="pencil">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                        @if ($role->name !== 'admin')
                                            <flux:menu.separator />
                                            <flux:menu.item
                                                wire:click="delete({{ $role->id }})"
                                                wire:confirm="{{ __('Apakah Anda yakin ingin menghapus role ini?') }}"
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
                                <flux:text class="text-zinc-500">{{ __('Belum ada data role.') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card.body>

        @if ($this->roles->hasPages())
            <flux:card.footer>
                {{ $this->roles->links() }}
            </flux:card.footer>
        @endif
    </flux:card>

    <!-- Modal Form Role -->
    <flux:modal wire:model="showModal" class="max-w-lg">
        <form wire:submit="save">
            <flux:modal.header>
                <flux:heading size="lg">
                    {{ $editingRole ? __('Edit Role') : __('Tambah Role') }}
                </flux:heading>
            </flux:modal.header>

            <flux:modal.body class="space-y-4">
                <flux:input
                    wire:model="name"
                    label="{{ __('Nama Role') }}"
                    placeholder="siswa"
                    :disabled="$editingRole?->name === 'admin'"
                    required
                />

                <flux:input
                    wire:model="display_name"
                    label="{{ __('Nama Tampilan') }}"
                    placeholder="Siswa"
                    required
                />

                <flux:textarea
                    wire:model="description"
                    label="{{ __('Deskripsi') }}"
                    placeholder="Deskripsi role..."
                    rows="3"
                />

                <flux:switch
                    wire:model="is_active"
                    label="{{ __('Status Aktif') }}"
                    description="{{ __('Role aktif dapat digunakan oleh pengguna') }}"
                />
            </flux:modal.body>

            <flux:modal.footer>
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">
                    {{ __('Batal') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $editingRole ? __('Simpan Perubahan') : __('Tambah Role') }}
                </flux:button>
            </flux:modal.footer>
        </form>
    </flux:modal>

    <!-- Modal Permission -->
    <flux:modal wire:model="showPermissionModal" class="max-w-4xl">
        <form wire:submit="savePermissions">
            <flux:modal.header>
                <flux:heading size="lg">
                    {{ __('Kelola Permission untuk :role', ['role' => $selectedRole?->display_name]) }}
                </flux:heading>
            </flux:modal.header>

            <flux:modal.body class="space-y-6 max-h-96 overflow-y-auto">
                @foreach ($this->permissions as $module => $perms)
                    <div class="border rounded-lg p-4 dark:border-zinc-700">
                        <flux:heading size="sm" class="mb-3 capitalize">{{ str_replace('_', ' ', $module) }}</flux:heading>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach ($perms as $perm)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model="selectedPermissions"
                                        value="{{ $perm['id'] }}"
                                        class="rounded border-zinc-300 dark:border-zinc-600"
                                    >
                                    <span class="text-sm">{{ $perm['display_name'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </flux:modal.body>

            <flux:modal.footer>
                <flux:button type="button" variant="ghost" wire:click="$set('showPermissionModal', false)">
                    {{ __('Batal') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ __('Simpan Permission') }}
                </flux:button>
            </flux:modal.footer>
        </form>
    </flux:modal>
</div>
