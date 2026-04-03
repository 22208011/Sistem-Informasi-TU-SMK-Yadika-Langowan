<?php

use App\Models\ItemBorrowing;
use App\Models\InventoryItem;
use App\Models\Employee;
use App\Models\Student;
use App\Livewire\Concerns\WithNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Peminjaman Barang')] class extends Component {
    use WithPagination;
    use WithNotification;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterBorrowerType = '';
    public string $activeTab = 'active'; // active, history, overdue

    // Form fields
    public bool $showModal = false;
    public bool $showReturnModal = false;
    public ?int $editingId = null;
    public ?int $returningId = null;

    // Borrow form
    public ?int $inventory_item_id = null;
    public string $borrower_type = 'employee';
    public ?int $borrower_id = null;
    public string $borrower_name = '';
    public string $borrower_contact = '';
    public int $quantity_borrowed = 1;
    public ?string $borrow_date = null;
    public ?string $expected_return_date = null;
    public string $purpose = '';
    public string $notes = '';

    // Return form
    public string $return_condition = 'baik';
    public string $return_notes = '';

    public function mount(): void
    {
        $this->borrow_date = now()->format('Y-m-d');
        $this->expected_return_date = now()->addDays(7)->format('Y-m-d');
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'borrower_type' => ['required', 'in:employee,student,external'],
            'borrower_id' => ['nullable', 'integer'],
            'borrower_name' => ['required', 'string', 'max:255'],
            'borrower_contact' => ['nullable', 'string', 'max:100'],
            'quantity_borrowed' => ['required', 'integer', 'min:1'],
            'borrow_date' => ['required', 'date'],
            'expected_return_date' => ['required', 'date', 'after_or_equal:borrow_date'],
            'purpose' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected $messages = [
        'inventory_item_id.required' => 'Barang wajib dipilih.',
        'borrower_name.required' => 'Nama peminjam wajib diisi.',
        'quantity_borrowed.required' => 'Jumlah wajib diisi.',
        'borrow_date.required' => 'Tanggal pinjam wajib diisi.',
        'expected_return_date.required' => 'Tanggal pengembalian wajib diisi.',
        'expected_return_date.after_or_equal' => 'Tanggal pengembalian harus setelah tanggal pinjam.',
    ];

    #[Computed]
    public function borrowings()
    {
        return ItemBorrowing::query()
            ->with(['item.category', 'approver', 'receiver'])
            ->when($this->search, fn($q) => $q->where(function($query) {
                $query->where('borrower_name', 'like', "%{$this->search}%")
                      ->orWhereHas('item', fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"));
            }))
            ->when($this->activeTab === 'active', fn($q) => $q->where('status', 'dipinjam'))
            ->when($this->activeTab === 'overdue', fn($q) => $q->overdue())
            ->when($this->activeTab === 'history', fn($q) => $q->whereIn('status', ['dikembalikan', 'hilang']))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterBorrowerType, fn($q) => $q->where('borrower_type', $this->filterBorrowerType))
            ->orderByDesc('borrow_date')
            ->orderByDesc('id')
            ->paginate(15);
    }

    #[Computed]
    public function availableItems()
    {
        return InventoryItem::available()->with('category')->orderBy('name')->get();
    }

    #[Computed]
    public function employees()
    {
        return Employee::active()->orderBy('name')->get();
    }

    #[Computed]
    public function students()
    {
        return Student::active()->orderBy('name')->get();
    }

    #[Computed]
    public function statistics()
    {
        return [
            'total_active' => ItemBorrowing::where('status', 'dipinjam')->count(),
            'overdue' => ItemBorrowing::overdue()->count(),
            'returned_today' => ItemBorrowing::where('status', 'dikembalikan')
                ->whereDate('actual_return_date', today())->count(),
            'total_borrowed' => ItemBorrowing::where('status', 'dipinjam')->sum('quantity_borrowed'),
        ];
    }

    public function setTab($tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function updatedBorrowerType(): void
    {
        $this->borrower_id = null;
        $this->borrower_name = '';
    }

    public function updatedBorrowerId(): void
    {
        if ($this->borrower_type === 'employee' && $this->borrower_id) {
            $employee = Employee::find($this->borrower_id);
            $this->borrower_name = $employee?->name ?? '';
            $this->borrower_contact = $employee?->phone ?? '';
        } elseif ($this->borrower_type === 'student' && $this->borrower_id) {
            $student = Student::find($this->borrower_id);
            $this->borrower_name = $student?->name ?? '';
            $this->borrower_contact = $student?->phone ?? '';
        }
    }

    public function updatedInventoryItemId(): void
    {
        // Reset quantity when item changes
        $this->quantity_borrowed = 1;
    }

    public function save(): void
    {
        $this->validate();

        // Check available quantity
        $item = InventoryItem::find($this->inventory_item_id);
        if (!$item || $item->available_quantity < $this->quantity_borrowed) {
            $this->notifyError('Jumlah barang tidak mencukupi. Tersedia: ' . ($item->available_quantity ?? 0));
            return;
        }

        $data = [
            'inventory_item_id' => $this->inventory_item_id,
            'borrower_type' => $this->borrower_type,
            'borrower_id' => $this->borrower_id,
            'borrower_name' => $this->borrower_name,
            'borrower_contact' => $this->borrower_contact ?: null,
            'quantity_borrowed' => $this->quantity_borrowed,
            'borrow_date' => $this->borrow_date,
            'expected_return_date' => $this->expected_return_date,
            'purpose' => $this->purpose ?: null,
            'notes' => $this->notes ?: null,
            'status' => 'dipinjam',
            'approved_by' => auth()->id(),
        ];

        if ($this->editingId) {
            $borrowing = ItemBorrowing::find($this->editingId);
            // Restore old quantity first
            $borrowing->item->increment('available_quantity', $borrowing->quantity_borrowed);
            $borrowing->update($data);
            // Deduct new quantity
            $item->decrement('available_quantity', $this->quantity_borrowed);
            $this->success('Peminjaman berhasil diperbarui.');
        } else {
            ItemBorrowing::create($data);
            // Deduct available quantity
            $item->decrement('available_quantity', $this->quantity_borrowed);
            $this->success('Peminjaman berhasil dicatat.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function edit(ItemBorrowing $borrowing): void
    {
        if ($borrowing->status !== 'dipinjam') {
            $this->notifyError('Hanya peminjaman aktif yang dapat diedit.');
            return;
        }

        $this->editingId = $borrowing->id;
        $this->inventory_item_id = $borrowing->inventory_item_id;
        $this->borrower_type = $borrowing->borrower_type;
        $this->borrower_id = $borrowing->borrower_id;
        $this->borrower_name = $borrowing->borrower_name;
        $this->borrower_contact = $borrowing->borrower_contact ?? '';
        $this->quantity_borrowed = $borrowing->quantity_borrowed;
        $this->borrow_date = $borrowing->borrow_date->format('Y-m-d');
        $this->expected_return_date = $borrowing->expected_return_date->format('Y-m-d');
        $this->purpose = $borrowing->purpose ?? '';
        $this->notes = $borrowing->notes ?? '';
        $this->showModal = true;
    }

    public function openReturnModal(ItemBorrowing $borrowing): void
    {
        $this->returningId = $borrowing->id;
        $this->return_condition = 'baik';
        $this->return_notes = '';
        $this->showReturnModal = true;
    }

    public function processReturn(): void
    {
        $this->validate([
            'return_condition' => ['required', 'in:baik,rusak_ringan,rusak_berat,hilang'],
            'return_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'return_condition.required' => 'Kondisi pengembalian wajib dipilih.',
        ]);

        $borrowing = ItemBorrowing::find($this->returningId);
        if (!$borrowing || $borrowing->status !== 'dipinjam') {
            $this->notifyError('Peminjaman tidak ditemukan atau sudah dikembalikan.');
            return;
        }

        $status = $this->return_condition === 'hilang' ? 'hilang' : 'dikembalikan';

        $borrowing->update([
            'status' => $status,
            'actual_return_date' => now()->toDateString(),
            'return_condition' => $this->return_condition,
            'received_by' => auth()->id(),
            'notes' => $this->return_notes ?: $borrowing->notes,
        ]);

        // Update item available quantity (only if not lost)
        if ($status === 'dikembalikan') {
            $borrowing->item->increment('available_quantity', $borrowing->quantity_borrowed);
        }

        // Update item condition if damaged
        if (in_array($this->return_condition, ['rusak_ringan', 'rusak_berat'])) {
            $borrowing->item->update(['condition' => $this->return_condition]);
        }

        $this->showReturnModal = false;
        $this->success($status === 'hilang' ? 'Barang dicatat hilang.' : 'Pengembalian berhasil dicatat.');
    }

    public function markAsLost(ItemBorrowing $borrowing): void
    {
        if ($borrowing->status !== 'dipinjam') {
            $this->notifyError('Hanya peminjaman aktif yang dapat diubah statusnya.');
            return;
        }

        $borrowing->update([
            'status' => 'hilang',
            'return_condition' => 'hilang',
            'received_by' => auth()->id(),
        ]);

        // Do not restore available quantity for lost items
        $this->success('Status peminjaman diubah menjadi hilang.');
    }

    public function delete(ItemBorrowing $borrowing): void
    {
        if ($borrowing->status === 'dipinjam') {
            // Restore available quantity
            $borrowing->item->increment('available_quantity', $borrowing->quantity_borrowed);
        }

        $borrowing->delete();
        $this->success('Data peminjaman berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->inventory_item_id = null;
        $this->borrower_type = 'employee';
        $this->borrower_id = null;
        $this->borrower_name = '';
        $this->borrower_contact = '';
        $this->quantity_borrowed = 1;
        $this->borrow_date = now()->format('Y-m-d');
        $this->expected_return_date = now()->addDays(7)->format('Y-m-d');
        $this->purpose = '';
        $this->notes = '';
        $this->resetValidation();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterBorrowerType(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Peminjaman Barang</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Kelola peminjaman barang inventaris</flux:text>
        </div>
        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('inventory.create'))
        <flux:button variant="primary" icon="plus" wire:click="create">
            Tambah Peminjaman
        </flux:button>
        @endif
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-blue-600">{{ number_format($this->statistics['total_active']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Sedang Dipinjam</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-red-600">{{ number_format($this->statistics['overdue']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Terlambat</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-green-600">{{ number_format($this->statistics['returned_today']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Kembali Hari Ini</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-zinc-800 dark:text-white">{{ number_format($this->statistics['total_borrowed']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Total Unit Dipinjam</flux:text>
        </flux:card>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700">
        <button wire:click="setTab('active')" class="px-4 py-2 text-sm font-medium {{ $activeTab === 'active' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-zinc-500 hover:text-zinc-700' }}">
            Aktif
            @if($this->statistics['total_active'] > 0)
            <span class="ml-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-600">{{ $this->statistics['total_active'] }}</span>
            @endif
        </button>
        <button wire:click="setTab('overdue')" class="px-4 py-2 text-sm font-medium {{ $activeTab === 'overdue' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-zinc-500 hover:text-zinc-700' }}">
            Terlambat
            @if($this->statistics['overdue'] > 0)
            <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-600">{{ $this->statistics['overdue'] }}</span>
            @endif
        </button>
        <button wire:click="setTab('history')" class="px-4 py-2 text-sm font-medium {{ $activeTab === 'history' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-zinc-500 hover:text-zinc-700' }}">
            Riwayat
        </button>
    </div>

    {{-- Filters --}}
    <flux:card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex-1">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama peminjam, barang..." icon="magnifying-glass" />
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:select wire:model.live="filterBorrowerType" class="w-36">
                    <option value="">Semua Tipe</option>
                    @foreach(\App\Models\ItemBorrowing::BORROWER_TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                @if($activeTab === 'history')
                <flux:select wire:model.live="filterStatus" class="w-36">
                    <option value="">Semua Status</option>
                    <option value="dikembalikan">Dikembalikan</option>
                    <option value="hilang">Hilang</option>
                </flux:select>
                @endif
            </div>
        </div>
    </flux:card>

    {{-- Borrowings Table --}}
    <flux:card class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Barang</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Peminjam</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium text-center">Jumlah</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Tanggal Pinjam</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Tenggat</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Status</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->borrowings as $borrowing)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 {{ $borrowing->isOverdue() ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $borrowing->item?->name ?? '-' }}</div>
                                <div class="text-xs text-zinc-500">{{ $borrowing->item?->code ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $borrowing->borrower_name }}</div>
                                <div class="text-xs text-zinc-500">
                                    <flux:badge size="sm" color="zinc">{{ $borrowing->borrower_type_label }}</flux:badge>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">{{ $borrowing->quantity_borrowed }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $borrowing->borrow_date->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div>{{ $borrowing->expected_return_date->format('d/m/Y') }}</div>
                                @if($borrowing->status === 'dipinjam')
                                    @if($borrowing->isOverdue())
                                        <div class="text-xs text-red-600">Terlambat {{ $borrowing->days_overdue }} hari</div>
                                    @elseif($borrowing->days_remaining <= 1)
                                        <div class="text-xs text-yellow-600">{{ $borrowing->days_remaining }} hari lagi</div>
                                    @endif
                                @elseif($borrowing->actual_return_date)
                                    <div class="text-xs text-zinc-500">Kembali: {{ $borrowing->actual_return_date->format('d/m/Y') }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <flux:badge size="sm" :color="$borrowing->status_color">
                                    {{ $borrowing->status_label }}
                                </flux:badge>
                                @if($borrowing->return_condition && $borrowing->status !== 'dipinjam')
                                    <div class="mt-1">
                                        <flux:badge size="sm" color="zinc">{{ \App\Models\ItemBorrowing::RETURN_CONDITIONS[$borrowing->return_condition] ?? '' }}</flux:badge>
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if($borrowing->status === 'dipinjam')
                                        <flux:button size="xs" variant="primary" wire:click="openReturnModal({{ $borrowing->id }})" title="Kembalikan">
                                            Kembalikan
                                        </flux:button>
                                        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('inventory.edit'))
                                        <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="edit({{ $borrowing->id }})" title="Edit" />
                                        @endif
                                    @endif
                                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('inventory.delete'))
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="delete({{ $borrowing->id }})" wire:confirm="Yakin ingin menghapus data peminjaman ini?" class="text-red-600 hover:text-red-700" title="Hapus" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon name="clipboard-document-list" class="size-8 text-zinc-400" />
                                    <span>Belum ada data peminjaman</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->borrowings->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->borrowings->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Borrow Modal --}}
    <flux:modal wire:model="showModal" class="max-w-2xl">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit Peminjaman' : 'Tambah Peminjaman Baru' }}</flux:heading>

            <form wire:submit="save" class="space-y-4">
                <flux:select wire:model.live="inventory_item_id" label="Barang *">
                    <option value="">Pilih Barang</option>
                    @foreach($this->availableItems as $item)
                        <option value="{{ $item->id }}">
                            {{ $item->code }} - {{ $item->name }} (Tersedia: {{ $item->available_quantity }})
                        </option>
                    @endforeach
                </flux:select>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model.live="borrower_type" label="Tipe Peminjam *">
                        @foreach(\App\Models\ItemBorrowing::BORROWER_TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    @if($borrower_type === 'employee')
                    <flux:select wire:model.live="borrower_id" label="Pilih Pegawai">
                        <option value="">-- Pilih atau isi manual --</option>
                        @foreach($this->employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->nip }} - {{ $employee->name }}</option>
                        @endforeach
                    </flux:select>
                    @elseif($borrower_type === 'student')
                    <flux:select wire:model.live="borrower_id" label="Pilih Siswa">
                        <option value="">-- Pilih atau isi manual --</option>
                        @foreach($this->students as $student)
                            <option value="{{ $student->id }}">{{ $student->nis }} - {{ $student->name }}</option>
                        @endforeach
                    </flux:select>
                    @else
                    <div></div>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="borrower_name" label="Nama Peminjam *" placeholder="Nama lengkap" />
                    <flux:input wire:model="borrower_contact" label="Kontak" placeholder="No. HP / Email" />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="quantity_borrowed" type="number" label="Jumlah Pinjam *" min="1" />
                    <flux:input wire:model="borrow_date" type="date" label="Tanggal Pinjam *" />
                    <flux:input wire:model="expected_return_date" type="date" label="Tanggal Kembali *" />
                </div>

                <flux:textarea wire:model="purpose" label="Tujuan Peminjaman" placeholder="Untuk keperluan..." rows="2" />
                <flux:textarea wire:model="notes" label="Catatan" placeholder="Catatan tambahan..." rows="2" />

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">Batal</flux:button>
                    <flux:button type="submit" variant="primary">{{ $editingId ? 'Perbarui' : 'Simpan' }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Return Modal --}}
    <flux:modal wire:model="showReturnModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Proses Pengembalian</flux:heading>

            <form wire:submit="processReturn" class="space-y-4">
                <flux:select wire:model="return_condition" label="Kondisi Barang *">
                    @foreach(\App\Models\ItemBorrowing::RETURN_CONDITIONS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="return_notes" label="Catatan Pengembalian" placeholder="Catatan kondisi barang..." rows="3" />

                @if($return_condition === 'hilang')
                <flux:callout variant="warning" icon="exclamation-triangle">
                    Barang yang dicatat hilang tidak akan dikembalikan ke stok tersedia.
                </flux:callout>
                @endif

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button type="button" variant="ghost" wire:click="$set('showReturnModal', false)">Batal</flux:button>
                    <flux:button type="submit" variant="primary">Proses Pengembalian</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
