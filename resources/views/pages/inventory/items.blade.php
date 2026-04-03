<?php

use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Livewire\Concerns\WithNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Inventaris Barang')] class extends Component {
    use WithPagination;
    use WithNotification;
    use WithFileUploads;

    public string $search = '';
    public string $filterCategory = '';
    public string $filterCondition = '';
    public string $filterStatus = '';

    // Form fields
    public bool $showModal = false;
    public bool $showCategoryModal = false;
    public ?int $editingId = null;
    public ?int $editingCategoryId = null;

    // Item form
    public ?int $category_id = null;
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public string $brand = '';
    public string $model = '';
    public string $serial_number = '';
    public int $quantity = 1;
    public string $condition = 'baik';
    public string $location = '';
    public ?string $purchase_date = null;
    public ?string $purchase_price = null;
    public string $supplier = '';
    public ?string $warranty_until = null;
    public $photo = null;
    public string $notes = '';
    public bool $is_active = true;

    // Category form
    public string $categoryName = '';
    public string $categoryCode = '';
    public string $categoryDescription = '';
    public bool $categoryIsActive = true;

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:item_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:inventory_items,code,' . $this->editingId],
            'description' => ['nullable', 'string', 'max:1000'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
            'condition' => ['required', 'in:baik,rusak_ringan,rusak_berat,hilang'],
            'location' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'warranty_until' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    protected $messages = [
        'category_id.required' => 'Kategori wajib dipilih.',
        'name.required' => 'Nama barang wajib diisi.',
        'code.required' => 'Kode barang wajib diisi.',
        'code.unique' => 'Kode barang sudah terdaftar.',
        'quantity.required' => 'Jumlah wajib diisi.',
        'quantity.min' => 'Jumlah minimal 1.',
    ];

    #[Computed]
    public function items()
    {
        return InventoryItem::query()
            ->with(['category', 'creator'])
            ->withCount('activeBorrowings')
            ->when($this->search, fn($q) => $q->where(function($query) {
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhere('code', 'like', "%{$this->search}%")
                      ->orWhere('brand', 'like', "%{$this->search}%")
                      ->orWhere('serial_number', 'like', "%{$this->search}%");
            }))
            ->when($this->filterCategory, fn($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterCondition, fn($q) => $q->where('condition', $this->filterCondition))
            ->when($this->filterStatus !== '', fn($q) => $q->where('is_active', $this->filterStatus === '1'))
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function categories()
    {
        return ItemCategory::active()->orderBy('name')->get();
    }

    #[Computed]
    public function allCategories()
    {
        return ItemCategory::withCount('items')->orderBy('name')->get();
    }

    #[Computed]
    public function statistics()
    {
        return [
            'total' => InventoryItem::count(),
            'active' => InventoryItem::where('is_active', true)->count(),
            'total_quantity' => InventoryItem::sum('quantity'),
            'available' => InventoryItem::sum('available_quantity'),
            'good_condition' => InventoryItem::where('condition', 'baik')->count(),
            'damaged' => InventoryItem::whereIn('condition', ['rusak_ringan', 'rusak_berat'])->count(),
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(InventoryItem $item): void
    {
        $this->editingId = $item->id;
        $this->category_id = $item->category_id;
        $this->name = $item->name;
        $this->code = $item->code;
        $this->description = $item->description ?? '';
        $this->brand = $item->brand ?? '';
        $this->model = $item->model ?? '';
        $this->serial_number = $item->serial_number ?? '';
        $this->quantity = $item->quantity;
        $this->condition = $item->condition;
        $this->location = $item->location ?? '';
        $this->purchase_date = $item->purchase_date?->format('Y-m-d');
        $this->purchase_price = $item->purchase_price;
        $this->supplier = $item->supplier ?? '';
        $this->warranty_until = $item->warranty_until?->format('Y-m-d');
        $this->notes = $item->notes ?? '';
        $this->is_active = (bool) $item->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'category_id' => $this->category_id,
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'description' => $this->description ?: null,
            'brand' => $this->brand ?: null,
            'model' => $this->model ?: null,
            'serial_number' => $this->serial_number ?: null,
            'quantity' => $this->quantity,
            'condition' => $this->condition,
            'location' => $this->location ?: null,
            'purchase_date' => $this->purchase_date ?: null,
            'purchase_price' => $this->purchase_price ?: null,
            'supplier' => $this->supplier ?: null,
            'warranty_until' => $this->warranty_until ?: null,
            'notes' => $this->notes ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->photo) {
            $data['photo'] = $this->photo->store('inventory', 'public');
        }

        if ($this->editingId) {
            $item = InventoryItem::find($this->editingId);
            $item->update($data);
            $this->success('Barang berhasil diperbarui.');
        } else {
            $data['created_by'] = auth()->id();
            $data['available_quantity'] = $this->quantity;
            InventoryItem::create($data);
            $this->success('Barang berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(InventoryItem $item): void
    {
        if ($item->activeBorrowings()->count() > 0) {
            $this->notifyError('Barang tidak dapat dihapus karena masih ada peminjaman aktif.');
            return;
        }

        $item->delete();
        $this->success('Barang berhasil dihapus.');
    }

    // Category management
    public function createCategory(): void
    {
        $this->resetCategoryForm();
        $this->showCategoryModal = true;
    }

    public function editCategory(ItemCategory $category): void
    {
        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryCode = $category->code;
        $this->categoryDescription = $category->description ?? '';
        $this->categoryIsActive = (bool) $category->is_active;
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        $this->validate([
            'categoryName' => ['required', 'string', 'max:255'],
            'categoryCode' => ['required', 'string', 'max:20', 'unique:item_categories,code,' . $this->editingCategoryId],
            'categoryDescription' => ['nullable', 'string', 'max:500'],
            'categoryIsActive' => ['boolean'],
        ], [
            'categoryName.required' => 'Nama kategori wajib diisi.',
            'categoryCode.required' => 'Kode kategori wajib diisi.',
            'categoryCode.unique' => 'Kode kategori sudah terdaftar.',
        ]);

        $data = [
            'name' => $this->categoryName,
            'code' => strtoupper($this->categoryCode),
            'description' => $this->categoryDescription ?: null,
            'is_active' => $this->categoryIsActive,
        ];

        if ($this->editingCategoryId) {
            ItemCategory::find($this->editingCategoryId)->update($data);
            $this->success('Kategori berhasil diperbarui.');
        } else {
            ItemCategory::create($data);
            $this->success('Kategori berhasil ditambahkan.');
        }

        $this->showCategoryModal = false;
        $this->resetCategoryForm();
    }

    public function deleteCategory(ItemCategory $category): void
    {
        if ($category->items()->count() > 0) {
            $this->notifyError('Kategori tidak dapat dihapus karena masih memiliki barang.');
            return;
        }

        $category->delete();
        $this->success('Kategori berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->category_id = null;
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->brand = '';
        $this->model = '';
        $this->serial_number = '';
        $this->quantity = 1;
        $this->condition = 'baik';
        $this->location = '';
        $this->purchase_date = null;
        $this->purchase_price = null;
        $this->supplier = '';
        $this->warranty_until = null;
        $this->photo = null;
        $this->notes = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function resetCategoryForm(): void
    {
        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->categoryCode = '';
        $this->categoryDescription = '';
        $this->categoryIsActive = true;
        $this->resetValidation();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCondition(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Inventaris Barang</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Kelola data barang inventaris sekolah</flux:text>
        </div>
        <div class="flex gap-2">
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('inventory.create'))
            <flux:button variant="ghost" icon="tag" wire:click="createCategory">
                Kategori
            </flux:button>
            <flux:button variant="primary" icon="plus" wire:click="create">
                Tambah Barang
            </flux:button>
            @endif
        </div>
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-zinc-800 dark:text-white">{{ number_format($this->statistics['total']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Total Jenis</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-green-600">{{ number_format($this->statistics['active']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Aktif</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-blue-600">{{ number_format($this->statistics['total_quantity']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Total Unit</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-emerald-600">{{ number_format($this->statistics['available']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Tersedia</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-green-600">{{ number_format($this->statistics['good_condition']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Kondisi Baik</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-red-600">{{ number_format($this->statistics['damaged']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Rusak</flux:text>
        </flux:card>
    </div>

    {{-- Filters --}}
    <flux:card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex-1">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama, kode, merek, serial..." icon="magnifying-glass" />
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:select wire:model.live="filterCategory" class="w-40">
                    <option value="">Semua Kategori</option>
                    @foreach($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="filterCondition" class="w-36">
                    <option value="">Semua Kondisi</option>
                    @foreach(\App\Models\InventoryItem::CONDITIONS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="filterStatus" class="w-32">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    {{-- Items Table --}}
    <flux:card class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Kode</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Nama Barang</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Kategori</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium text-center">Jumlah</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium text-center">Tersedia</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Kondisi</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Lokasi</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium text-center">Status</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->items as $item)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-sm">{{ $item->code }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $item->name }}</div>
                                @if($item->brand || $item->model)
                                    <div class="text-xs text-zinc-500">
                                        {{ $item->brand }}{{ $item->brand && $item->model ? ' - ' : '' }}{{ $item->model }}
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $item->category?->name ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">{{ $item->quantity }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                <span class="{{ $item->available_quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $item->available_quantity }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <flux:badge size="sm" :color="$item->condition_color">
                                    {{ $item->condition_label }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $item->location ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                @if($item->is_active)
                                    <flux:badge size="sm" color="green">Aktif</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">Nonaktif</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('inventory.edit'))
                                    <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="edit({{ $item->id }})" title="Edit" />
                                    @endif
                                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('inventory.delete'))
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="delete({{ $item->id }})" wire:confirm="Yakin ingin menghapus barang ini?" class="text-red-600 hover:text-red-700" title="Hapus" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-zinc-500">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon name="cube" class="size-8 text-zinc-400" />
                                    <span>Belum ada data barang</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->items->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->items->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Item Modal --}}
    <flux:modal wire:model="showModal" class="max-w-3xl">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit Barang' : 'Tambah Barang Baru' }}</flux:heading>

            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model="category_id" label="Kategori *">
                        <option value="">Pilih Kategori</option>
                        @foreach($this->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="code" label="Kode Barang *" placeholder="INV-001" />
                </div>

                <flux:input wire:model="name" label="Nama Barang *" placeholder="Masukkan nama barang" />

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="brand" label="Merek" placeholder="Merek" />
                    <flux:input wire:model="model" label="Model" placeholder="Model/Tipe" />
                    <flux:input wire:model="serial_number" label="Serial Number" placeholder="S/N" />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="quantity" type="number" label="Jumlah *" min="1" />
                    <flux:select wire:model="condition" label="Kondisi *">
                        @foreach(\App\Models\InventoryItem::CONDITIONS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="location" label="Lokasi" placeholder="Ruang Lab, Gedung A" />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="purchase_date" type="date" label="Tanggal Pembelian" />
                    <flux:input wire:model="purchase_price" type="number" label="Harga Pembelian" placeholder="0" />
                    <flux:input wire:model="supplier" label="Supplier" placeholder="Nama supplier" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="warranty_until" type="date" label="Garansi Sampai" />
                    <flux:input wire:model="photo" type="file" label="Foto Barang" accept="image/*" />
                </div>

                <flux:textarea wire:model="description" label="Deskripsi" placeholder="Deskripsi barang..." rows="2" />

                <flux:textarea wire:model="notes" label="Catatan" placeholder="Catatan tambahan..." rows="2" />

                <div class="flex items-center gap-2">
                    <flux:checkbox wire:model="is_active" />
                    <flux:text>Barang aktif</flux:text>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">Batal</flux:button>
                    <flux:button type="submit" variant="primary">{{ $editingId ? 'Perbarui' : 'Simpan' }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Category Modal --}}
    <flux:modal wire:model="showCategoryModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingCategoryId ? 'Edit Kategori' : 'Tambah Kategori Baru' }}</flux:heading>

            <form wire:submit="saveCategory" class="space-y-4">
                <flux:input wire:model="categoryCode" label="Kode Kategori *" placeholder="KAT-001" />
                <flux:input wire:model="categoryName" label="Nama Kategori *" placeholder="Elektronik, Furniture, dll" />
                <flux:textarea wire:model="categoryDescription" label="Deskripsi" placeholder="Deskripsi kategori..." rows="2" />

                <div class="flex items-center gap-2">
                    <flux:checkbox wire:model="categoryIsActive" />
                    <flux:text>Kategori aktif</flux:text>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button type="button" variant="ghost" wire:click="$set('showCategoryModal', false)">Batal</flux:button>
                    <flux:button type="submit" variant="primary">{{ $editingCategoryId ? 'Perbarui' : 'Simpan' }}</flux:button>
                </div>
            </form>

            {{-- Category List --}}
            @if($this->allCategories->count() > 0)
            <div class="border-t pt-4 dark:border-zinc-700">
                <flux:text class="mb-2 font-medium">Daftar Kategori</flux:text>
                <div class="max-h-48 space-y-2 overflow-y-auto">
                    @foreach($this->allCategories as $cat)
                        <div class="flex items-center justify-between rounded-lg border p-2 dark:border-zinc-700">
                            <div>
                                <span class="font-mono text-xs text-zinc-500">{{ $cat->code }}</span>
                                <span class="mx-1">-</span>
                                <span class="font-medium">{{ $cat->name }}</span>
                                <span class="text-xs text-zinc-500">({{ $cat->items_count }} barang)</span>
                            </div>
                            <div class="flex gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil" wire:click="editCategory({{ $cat->id }})" />
                                @if($cat->items_count === 0)
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="deleteCategory({{ $cat->id }})" wire:confirm="Yakin ingin menghapus kategori ini?" class="text-red-600" />
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </flux:modal>
</div>
