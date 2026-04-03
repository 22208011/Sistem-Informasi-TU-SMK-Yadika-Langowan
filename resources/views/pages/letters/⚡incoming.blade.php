<?php

use App\Models\IncomingLetter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Agenda Surat Masuk')] class extends Component {
    use WithPagination, WithFileUploads;

    #[Url]
    public $search = '';

    #[Url]
    public $classification = '';

    #[Url]
    public $status = '';

    #[Url]
    public $nature = '';

    public $showModal = false;
    public $showViewModal = false;
    public $editId = null;
    public $viewLetter = null;

    // Form fields
    public $letter_number = '';
    public $letter_date = '';
    public $received_date = '';
    public $sender = '';
    public $sender_address = '';
    public $subject = '';
    public $form_classification = 'lainnya';
    public $form_nature = 'biasa';
    public $attachment_count = 0;
    public $attachment_type = '';
    public $disposition = '';
    public $disposition_to = '';
    public $notes = '';
    public $file;

    public function openCreate()
    {
        $this->resetForm();
        $this->received_date = now()->format('Y-m-d');
        $this->letter_date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function openEdit($id)
    {
        $letter = IncomingLetter::findOrFail($id);
        $this->editId = $id;
        $this->letter_number = $letter->letter_number;
        $this->letter_date = $letter->letter_date->format('Y-m-d');
        $this->received_date = $letter->received_date->format('Y-m-d');
        $this->sender = $letter->sender;
        $this->sender_address = $letter->sender_address;
        $this->subject = $letter->subject;
        $this->form_classification = $letter->classification;
        $this->form_nature = $letter->nature;
        $this->attachment_count = $letter->attachment_count;
        $this->attachment_type = $letter->attachment_type;
        $this->disposition = $letter->disposition;
        $this->disposition_to = $letter->disposition_to;
        $this->notes = $letter->notes;
        $this->showModal = true;
    }

    public function openView($id)
    {
        $this->viewLetter = IncomingLetter::with('receiver')->findOrFail($id);
        $this->showViewModal = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'letter_number' => 'required|string|max:100',
            'letter_date' => 'required|date',
            'received_date' => 'required|date',
            'sender' => 'required|string|max:255',
            'sender_address' => 'nullable|string',
            'subject' => 'required|string|max:500',
            'form_classification' => 'required|string',
            'form_nature' => 'required|string',
            'attachment_count' => 'nullable|integer|min:0',
            'attachment_type' => 'nullable|string|max:255',
            'disposition' => 'nullable|string',
            'disposition_to' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $data = [
            'letter_number' => $this->letter_number,
            'letter_date' => $this->letter_date,
            'received_date' => $this->received_date,
            'sender' => $this->sender,
            'sender_address' => $this->sender_address,
            'subject' => $this->subject,
            'classification' => $this->form_classification,
            'nature' => $this->form_nature,
            'attachment_count' => $this->attachment_count ?? 0,
            'attachment_type' => $this->attachment_type,
            'disposition' => $this->disposition,
            'disposition_to' => $this->disposition_to,
            'notes' => $this->notes,
            'received_by' => auth()->id(),
        ];

        if ($this->file) {
            $data['file_path'] = $this->file->store('incoming-letters', 'public');
        }

        if ($this->editId) {
            $letter = IncomingLetter::findOrFail($this->editId);
            $letter->update($data);
            session()->flash('success', 'Surat masuk berhasil diperbarui.');
        } else {
            $data['agenda_number'] = IncomingLetter::generateAgendaNumber();
            IncomingLetter::create($data);
            session()->flash('success', 'Surat masuk berhasil ditambahkan.');
        }

        $this->closeModal();
    }

    public function updateStatus($id, $status)
    {
        $letter = IncomingLetter::findOrFail($id);
        $letter->update(['status' => $status]);
        session()->flash('success', 'Status surat berhasil diperbarui.');
    }

    public function delete($id)
    {
        $letter = IncomingLetter::findOrFail($id);
        if ($letter->file_path) {
            \Storage::disk('public')->delete($letter->file_path);
        }
        $letter->delete();
        session()->flash('success', 'Surat masuk berhasil dihapus.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->showViewModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset(['editId', 'letter_number', 'letter_date', 'received_date', 'sender', 'sender_address', 'subject', 'form_classification', 'form_nature', 'attachment_count', 'attachment_type', 'disposition', 'disposition_to', 'notes', 'file', 'viewLetter']);
        $this->form_classification = 'lainnya';
        $this->form_nature = 'biasa';
    }

    public function with(): array
    {
        $query = IncomingLetter::query()->with('receiver');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('agenda_number', 'like', '%' . $this->search . '%')
                    ->orWhere('letter_number', 'like', '%' . $this->search . '%')
                    ->orWhere('sender', 'like', '%' . $this->search . '%')
                    ->orWhere('subject', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->classification) {
            $query->where('classification', $this->classification);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->nature) {
            $query->where('nature', $this->nature);
        }

        $letters = $query->orderBy('received_date', 'desc')->orderBy('created_at', 'desc')->paginate(15);

        // Statistics
        $thisMonth = IncomingLetter::whereMonth('received_date', now()->month)
            ->whereYear('received_date', now()->year);

        return [
            'letters' => $letters,
            'classifications' => IncomingLetter::CLASSIFICATIONS,
            'natures' => IncomingLetter::NATURES,
            'statuses' => IncomingLetter::STATUSES,
            'natureColors' => IncomingLetter::NATURE_COLORS,
            'statusColors' => IncomingLetter::STATUS_COLORS,
            'stats' => [
                'total' => IncomingLetter::count(),
                'this_month' => (clone $thisMonth)->count(),
                'pending' => IncomingLetter::whereIn('status', ['received', 'processing'])->count(),
                'completed' => IncomingLetter::where('status', 'completed')->count(),
            ],
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Agenda Surat Masuk</flux:heading>
            <flux:subheading>Pencatatan dan pengelolaan surat masuk dari luar</flux:subheading>
        </div>
        <flux:button icon="plus" wire:click="openCreate">
            Catat Surat Masuk
        </flux:button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <flux:card class="p-4!">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="envelope" class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="text-xs text-gray-500">Total Surat</flux:text>
                    <flux:heading size="lg">{{ $stats['total'] }}</flux:heading>
                </div>
            </div>
        </flux:card>
        <flux:card class="p-4!">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/30">
                    <flux:icon name="calendar" class="size-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="text-xs text-gray-500">Bulan Ini</flux:text>
                    <flux:heading size="lg">{{ $stats['this_month'] }}</flux:heading>
                </div>
            </div>
        </flux:card>
        <flux:card class="p-4!">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-yellow-100 dark:bg-yellow-900/30">
                    <flux:icon name="clock" class="size-5 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <flux:text class="text-xs text-gray-500">Proses</flux:text>
                    <flux:heading size="lg">{{ $stats['pending'] }}</flux:heading>
                </div>
            </div>
        </flux:card>
        <flux:card class="p-4!">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon name="check-circle" class="size-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <flux:text class="text-xs text-gray-500">Selesai</flux:text>
                    <flux:heading size="lg">{{ $stats['completed'] }}</flux:heading>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Cari surat..." />
            <flux:select wire:model.live="classification">
                <option value="">Semua Klasifikasi</option>
                @foreach ($classifications as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="nature">
                <option value="">Semua Sifat</option>
                @foreach ($natures as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="status">
                <option value="">Semua Status</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:button variant="ghost" wire:click="$set('search', ''); $set('classification', ''); $set('status', ''); $set('nature', '')">
                Reset Filter
            </flux:button>
        </div>
    </flux:card>

    <!-- Table -->
    <flux:card class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">No. Agenda</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Pengirim</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Perihal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Sifat</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse ($letters as $letter)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $letter->agenda_number }}</div>
                                <div class="text-xs text-gray-500">{{ $letter->letter_number }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $letter->received_date->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">Tgl Surat: {{ $letter->letter_date->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $letter->sender }}</div>
                                <div class="text-xs text-gray-500">{{ $classifications[$letter->classification] ?? $letter->classification }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900 dark:text-white line-clamp-2">{{ $letter->subject }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" color="{{ $natureColors[$letter->nature] ?? 'zinc' }}">
                                    {{ $natures[$letter->nature] ?? $letter->nature }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" color="{{ $statusColors[$letter->status] ?? 'zinc' }}">
                                    {{ $statuses[$letter->status] ?? $letter->status }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <flux:button size="xs" variant="ghost" icon="eye" wire:click="openView({{ $letter->id }})" title="Lihat" />
                                    <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $letter->id }})" title="Edit" />
                                    <flux:dropdown>
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-vertical" />
                                        <flux:menu>
                                            @if ($letter->status !== 'completed')
                                                <flux:menu.item wire:click="updateStatus({{ $letter->id }}, 'completed')" icon="check-circle">
                                                    Tandai Selesai
                                                </flux:menu.item>
                                            @endif
                                            @if ($letter->status !== 'archived')
                                                <flux:menu.item wire:click="updateStatus({{ $letter->id }}, 'archived')" icon="archive-box">
                                                    Arsipkan
                                                </flux:menu.item>
                                            @endif
                                            <flux:menu.item wire:click="delete({{ $letter->id }})" wire:confirm="Yakin ingin menghapus surat ini?" icon="trash" variant="danger">
                                                Hapus
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <flux:icon name="inbox" class="size-12 mx-auto mb-4 text-gray-300" />
                                <p>Belum ada surat masuk yang tercatat</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-zinc-700">
            {{ $letters->links() }}
        </div>
    </flux:card>

    <!-- Create/Edit Modal -->
    <flux:modal wire:model="showModal" name="letter-form" class="max-w-3xl">
        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ $editId ? 'Edit Surat Masuk' : 'Catat Surat Masuk Baru' }}</flux:heading>
            
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model="letter_number" label="Nomor Surat" placeholder="Masukkan nomor surat" required />
                    <flux:input wire:model="letter_date" type="date" label="Tanggal Surat" required />
                </div>

                <flux:input wire:model="received_date" type="date" label="Tanggal Diterima" required />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model="sender" label="Pengirim" placeholder="Nama instansi/pengirim" required />
                    <flux:select wire:model="form_classification" label="Klasifikasi">
                        @foreach ($classifications as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="sender_address" label="Alamat Pengirim" rows="2" placeholder="Alamat lengkap pengirim" />

                <flux:input wire:model="subject" label="Perihal" placeholder="Perihal/subjek surat" required />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select wire:model="form_nature" label="Sifat Surat">
                        @foreach ($natures as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="attachment_count" type="number" label="Jumlah Lampiran" min="0" />
                    <flux:input wire:model="attachment_type" label="Jenis Lampiran" placeholder="Contoh: Dokumen" />
                </div>

                <flux:textarea wire:model="disposition" label="Disposisi" rows="2" placeholder="Catatan disposisi" />

                <flux:input wire:model="disposition_to" label="Disposisi Kepada" placeholder="Nama penerima disposisi" />

                <flux:textarea wire:model="notes" label="Catatan" rows="2" placeholder="Catatan tambahan" />

                <flux:input wire:model="file" type="file" label="Upload File Surat" accept=".pdf,.jpg,.jpeg,.png" />
                <flux:text class="text-xs text-gray-500">Format: PDF, JPG, PNG. Maksimal 10MB</flux:text>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button type="button" variant="ghost" wire:click="closeModal">Batal</flux:button>
                    <flux:button type="submit" variant="primary">{{ $editId ? 'Simpan Perubahan' : 'Simpan' }}</flux:button>
                </div>
            </form>
        </flux:card>
    </flux:modal>

    <!-- View Modal -->
    <flux:modal wire:model="showViewModal" name="letter-view" class="max-w-2xl">
        @if ($viewLetter)
        <flux:card>
            <flux:heading size="lg" class="mb-4">Detail Surat Masuk</flux:heading>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs text-gray-500">No. Agenda</flux:text>
                        <flux:text class="font-medium">{{ $viewLetter->agenda_number }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-gray-500">No. Surat</flux:text>
                        <flux:text class="font-medium">{{ $viewLetter->letter_number }}</flux:text>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs text-gray-500">Tanggal Surat</flux:text>
                        <flux:text class="font-medium">{{ $viewLetter->letter_date->format('d F Y') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-gray-500">Tanggal Diterima</flux:text>
                        <flux:text class="font-medium">{{ $viewLetter->received_date->format('d F Y') }}</flux:text>
                    </div>
                </div>

                <div>
                    <flux:text class="text-xs text-gray-500">Pengirim</flux:text>
                    <flux:text class="font-medium">{{ $viewLetter->sender }}</flux:text>
                    @if ($viewLetter->sender_address)
                        <flux:text class="text-sm text-gray-500">{{ $viewLetter->sender_address }}</flux:text>
                    @endif
                </div>

                <div>
                    <flux:text class="text-xs text-gray-500">Perihal</flux:text>
                    <flux:text class="font-medium">{{ $viewLetter->subject }}</flux:text>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <flux:text class="text-xs text-gray-500">Klasifikasi</flux:text>
                        <flux:text class="font-medium">{{ $classifications[$viewLetter->classification] ?? $viewLetter->classification }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-gray-500">Sifat</flux:text>
                        <flux:badge size="sm" color="{{ $natureColors[$viewLetter->nature] ?? 'zinc' }}">
                            {{ $natures[$viewLetter->nature] ?? $viewLetter->nature }}
                        </flux:badge>
                    </div>
                    <div>
                        <flux:text class="text-xs text-gray-500">Status</flux:text>
                        <flux:badge size="sm" color="{{ $statusColors[$viewLetter->status] ?? 'zinc' }}">
                            {{ $statuses[$viewLetter->status] ?? $viewLetter->status }}
                        </flux:badge>
                    </div>
                </div>

                @if ($viewLetter->disposition)
                <div>
                    <flux:text class="text-xs text-gray-500">Disposisi</flux:text>
                    <flux:text class="font-medium">{{ $viewLetter->disposition }}</flux:text>
                    @if ($viewLetter->disposition_to)
                        <flux:text class="text-sm text-gray-500">Kepada: {{ $viewLetter->disposition_to }}</flux:text>
                    @endif
                </div>
                @endif

                @if ($viewLetter->notes)
                <div>
                    <flux:text class="text-xs text-gray-500">Catatan</flux:text>
                    <flux:text>{{ $viewLetter->notes }}</flux:text>
                </div>
                @endif

                @if ($viewLetter->file_path)
                <div>
                    <flux:text class="text-xs text-gray-500 mb-2">File Lampiran</flux:text>
                    <flux:button size="sm" icon="document-arrow-down" href="{{ Storage::url($viewLetter->file_path) }}" target="_blank">
                        Download File
                    </flux:button>
                </div>
                @endif

                <div class="pt-4 border-t">
                    <flux:text class="text-xs text-gray-500">Diterima oleh: {{ $viewLetter->receiver?->name ?? 'N/A' }} pada {{ $viewLetter->created_at->format('d M Y H:i') }}</flux:text>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <flux:button variant="ghost" wire:click="closeModal">Tutup</flux:button>
                <flux:button icon="pencil-square" wire:click="openEdit({{ $viewLetter->id }})">Edit</flux:button>
            </div>
        </flux:card>
        @endif
    </flux:modal>
</div>
