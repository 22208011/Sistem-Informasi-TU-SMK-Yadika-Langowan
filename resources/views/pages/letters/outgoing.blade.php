<?php

use App\Models\OutgoingLetter;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Agenda Surat Keluar')] class extends Component {
    use WithPagination, WithFileUploads;

    #[Url]
    public $activeTab = 'aktif';

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
    public $sent_date = '';
    public $recipient = '';
    public $recipient_address = '';
    public $subject = '';
    public $form_classification = 'lainnya';
    public $form_nature = 'biasa';
    public $attachment_count = 0;
    public $attachment_type = '';
    public $content_summary = '';
    public $signed_by = '';
    public $notes = '';
    public $file;

    public function openCreate()
    {
        $this->resetForm();
        $this->letter_date = now()->format('Y-m-d');
        $this->letter_number = OutgoingLetter::generateLetterNumber();
        $this->showModal = true;
    }

    public function openEdit($id)
    {
        $letter = OutgoingLetter::findOrFail($id);
        
        if (!$letter->isEditable()) {
            session()->flash('error', 'Surat tidak dapat diedit karena sudah dikirim atau diarsipkan.');
            return;
        }

        $this->editId = $id;
        $this->letter_number = $letter->letter_number;
        $this->letter_date = $letter->letter_date->format('Y-m-d');
        $this->sent_date = $letter->sent_date?->format('Y-m-d');
        $this->recipient = $letter->recipient;
        $this->recipient_address = $letter->recipient_address;
        $this->subject = $letter->subject;
        $this->form_classification = $letter->classification;
        $this->form_nature = $letter->nature;
        $this->attachment_count = $letter->attachment_count;
        $this->attachment_type = $letter->attachment_type;
        $this->content_summary = $letter->content_summary;
        $this->signed_by = $letter->signed_by;
        $this->notes = $letter->notes;
        $this->showModal = true;
    }

    public function openView($id)
    {
        $this->viewLetter = OutgoingLetter::with(['creator', 'signer'])->findOrFail($id);
        $this->showViewModal = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'letter_number' => 'required|string|max:100',
            'letter_date' => 'required|date',
            'sent_date' => 'nullable|date',
            'recipient' => 'required|string|max:255',
            'recipient_address' => 'nullable|string',
            'subject' => 'required|string|max:500',
            'form_classification' => 'required|string',
            'form_nature' => 'required|string',
            'attachment_count' => 'nullable|integer|min:0',
            'attachment_type' => 'nullable|string|max:255',
            'content_summary' => 'nullable|string',
            'signed_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $data = [
            'letter_number' => $this->letter_number,
            'letter_date' => $this->letter_date,
            'sent_date' => $this->sent_date ?: null,
            'recipient' => $this->recipient,
            'recipient_address' => $this->recipient_address,
            'subject' => $this->subject,
            'classification' => $this->form_classification,
            'nature' => $this->form_nature,
            'attachment_count' => $this->attachment_count ?? 0,
            'attachment_type' => $this->attachment_type,
            'content_summary' => $this->content_summary,
            'signed_by' => $this->signed_by ?: null,
            'notes' => $this->notes,
        ];

        if ($this->file) {
            $data['file_path'] = $this->file->store('outgoing-letters', 'public');
        }

        if ($this->editId) {
            $letter = OutgoingLetter::findOrFail($this->editId);
            $letter->update($data);
            session()->flash('success', 'Surat keluar berhasil diperbarui.');
        } else {
            $data['agenda_number'] = OutgoingLetter::generateAgendaNumber();
            $data['created_by'] = auth()->id();
            OutgoingLetter::create($data);
            session()->flash('success', 'Surat keluar berhasil ditambahkan.');
        }

        $this->closeModal();
    }

    public function updateStatus($id, $status)
    {
        $letter = OutgoingLetter::findOrFail($id);
        
        $updateData = ['status' => $status];
        
        if ($status === 'sent' && !$letter->sent_date) {
            $updateData['sent_date'] = now();
        }
        
        $letter->update($updateData);
        session()->flash('success', 'Status surat berhasil diperbarui.');
    }

    public function delete($id)
    {
        $letter = OutgoingLetter::findOrFail($id);
        
        if ($letter->file_path) {
            \Storage::disk('public')->delete($letter->file_path);
        }
        $letter->delete();
        session()->flash('success', 'Surat keluar berhasil dihapus.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->showViewModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset(['editId', 'letter_number', 'letter_date', 'sent_date', 'recipient', 'recipient_address', 'subject', 'form_classification', 'form_nature', 'attachment_count', 'attachment_type', 'content_summary', 'signed_by', 'notes', 'file', 'viewLetter']);
        $this->form_classification = 'lainnya';
        $this->form_nature = 'biasa';
    }

    public function with(): array
    {
        $query = OutgoingLetter::query()->with(['creator', 'signer']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('agenda_number', 'like', '%' . $this->search . '%')
                    ->orWhere('letter_number', 'like', '%' . $this->search . '%')
                    ->orWhere('recipient', 'like', '%' . $this->search . '%')
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

        if ($this->activeTab === 'aktif') {
            $query->whereIn('status', ['draft', 'sent']);
        } else if ($this->activeTab === 'arsip') {
            $query->where('status', 'archived');
            // Arsip will be sorted by classification then date
            $query->orderBy('classification', 'asc');
        }

        $letters = $query->orderBy('letter_date', 'desc')->orderBy('created_at', 'desc')->paginate(15);

        // Statistics
        $thisMonth = OutgoingLetter::whereMonth('letter_date', now()->month)
            ->whereYear('letter_date', now()->year);

        // Ambil semua pegawai aktif (guru, kepala sekolah, tendik) dari tabel Employee
        $signers = \App\Models\Employee::active()->get();

        return [
            'letters' => $letters,
            'classifications' => OutgoingLetter::CLASSIFICATIONS,
            'natures' => OutgoingLetter::NATURES,
            'statuses' => OutgoingLetter::STATUSES,
            'natureColors' => OutgoingLetter::NATURE_COLORS,
            'statusColors' => OutgoingLetter::STATUS_COLORS,
            'signers' => $signers,
            'stats' => [
                'total' => OutgoingLetter::count(),
                'this_month' => (clone $thisMonth)->count(),
                'draft' => OutgoingLetter::where('status', 'draft')->count(),
                'sent' => OutgoingLetter::where('status', 'sent')->count(),
            ],
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Agenda Surat Keluar</flux:heading>
            <flux:subheading>Pencatatan dan pengelolaan surat keluar sekolah</flux:subheading>
        </div>
        <flux:button icon="plus" wire:click="openCreate">
            Buat Surat Keluar
        </flux:button>
    </div>

    <flux:navlist variant="outline" class="flex gap-4 border-b border-gray-200 dark:border-zinc-700 pb-2">
        <flux:navlist.item icon="document-text" wire:click="$set('activeTab', 'aktif')" current="{{ $activeTab === 'aktif' }}">Semua Surat Aktif</flux:navlist.item>
        <flux:navlist.item icon="archive-box" wire:click="$set('activeTab', 'arsip')" current="{{ $activeTab === 'arsip' }}">Gudang Arsip Khusus</flux:navlist.item>
    </flux:navlist>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <flux:card class="p-4! transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="paper-airplane" class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="text-xs text-gray-500">Total Surat</flux:text>
                    <flux:heading size="lg">{{ $stats['total'] }}</flux:heading>
                </div>
            </div>
        </flux:card>
        <flux:card class="p-4! transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
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
        <flux:card class="p-4! transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-yellow-100 dark:bg-yellow-900/30">
                    <flux:icon name="pencil-square" class="size-5 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <flux:text class="text-xs text-gray-500">Draft</flux:text>
                    <flux:heading size="lg">{{ $stats['draft'] }}</flux:heading>
                </div>
            </div>
        </flux:card>
        <flux:card class="p-4! transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon name="check-badge" class="size-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <flux:text class="text-xs text-gray-500">Terkirim</flux:text>
                    <flux:heading size="lg">{{ $stats['sent'] }}</flux:heading>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Cari surat..." />
            <flux:select wire:model.live="classification">
                <option value="">Semua Tujuan</option>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">No. Surat</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Penerima</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Perihal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Sifat</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @if ($letters->isEmpty())
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <flux:icon name="{{ $activeTab === 'arsip' ? 'archive-box' : 'paper-airplane' }}" class="size-12 mx-auto mb-4 text-gray-300" />
                                <p>{{ $activeTab === 'arsip' ? 'Belum ada arsip surat' : 'Belum ada surat keluar yang tercatat' }}</p>
                            </td>
                        </tr>
                    @else
                        @php
                            $groupedLetters = $activeTab === 'arsip' ? $letters->groupBy('classification') : ['' => $letters];
                        @endphp
                        @foreach ($groupedLetters as $classification => $groupLetters)
                            @if ($activeTab === 'arsip')
                                <tr>
                                    <td colspan="7" class="px-4 py-2 bg-blue-50/50 dark:bg-zinc-800/80 font-bold text-gray-700 dark:text-gray-300 border-y border-gray-200 dark:border-zinc-700">
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="folder-open" class="size-4 text-blue-500" />
                                            <span>{{ $classifications[$classification] ?? $classification }}</span>
                                            <span class="text-xs font-normal text-gray-500">({{ $groupLetters->count() }} Surat)</span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                            @foreach ($groupLetters as $letter)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors duration-200">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $letter->letter_number }}</div>
                                        <div class="text-xs text-gray-500">Agenda: {{ $letter->agenda_number }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ $letter->letter_date->format('d/m/Y') }}</div>
                                        @if ($letter->sent_date)
                                            <div class="text-xs text-gray-500">Terkirim: {{ $letter->sent_date->format('d/m/Y') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ $letter->recipient }}</div>
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
                                            @if ($letter->isEditable())
                                                <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $letter->id }})" title="Edit" />
                                            @endif
                                            <flux:dropdown>
                                                <flux:button size="xs" variant="ghost" icon="ellipsis-vertical" />
                                                <flux:menu>
                                                    @if ($letter->status === 'draft')
                                                        <flux:menu.item wire:click="updateStatus({{ $letter->id }}, 'sent')" icon="paper-airplane">
                                                            Tandai Terkirim
                                                        </flux:menu.item>
                                                    @endif
                                                    @if ($letter->status === 'sent')
                                                        <flux:menu.item wire:click="updateStatus({{ $letter->id }}, 'archived')" icon="archive-box">
                                                            Arsipkan
                                                        </flux:menu.item>
                                                    @endif
                                                    <flux:menu.separator />
                                                    <flux:menu.item wire:click="delete({{ $letter->id }})" wire:confirm="PERINGATAN! Anda yakin ingin menghapus surat ini secara permanen?" icon="trash" variant="danger">
                                                        Hapus
                                                    </flux:menu.item>
                                                </flux:menu>
                                            </flux:dropdown>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endif
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
            <flux:heading size="lg" class="mb-4">{{ $editId ? 'Edit Surat Keluar' : 'Buat Surat Keluar Baru' }}</flux:heading>
            
            <form wire:submit="save" class="space-y-6">
                <!-- Data Administrasi -->
                <div>
                    <flux:heading size="md" class="mb-2">1. Detail Administrasi</flux:heading>
                    <flux:separator class="mb-4" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input wire:model="letter_number" label="Nomor Surat" placeholder="Contoh: 001/SMK-YL/I/2026" required />
                        <flux:input wire:model="letter_date" type="date" label="Tanggal Surat" required />
                    </div>
                </div>

                <!-- Tujuan & Perihal -->
                <div>
                    <flux:heading size="md" class="mb-2">2. Tujuan & Perihal</flux:heading>
                    <flux:separator class="mb-4" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input wire:model="recipient" label="Penerima (Instansi/Orang)" placeholder="Nama penerima..." required />
                        <flux:select wire:model="form_classification" label="Klasifikasi / Jenis">
                            @foreach ($classifications as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="mt-4">
                        <flux:textarea wire:model="recipient_address" label="Alamat Penerima" rows="2" placeholder="Alamat lengkap penerima..." />
                    </div>
                    <div class="mt-4">
                        <flux:input wire:model="subject" label="Perihal / Judul" placeholder="Undangan, Permohonan..." required />
                    </div>
                </div>

                <!-- Detail & Lampiran -->
                <div>
                    <flux:heading size="md" class="mb-2">3. Detail Tambahan & Lampiran</flux:heading>
                    <flux:separator class="mb-4" />
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:select wire:model="form_nature" label="Sifat Surat">
                            @foreach ($natures as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="attachment_count" type="number" label="Jumlah Lampiran" min="0" />
                        <flux:input wire:model="attachment_type" label="Jenis Lampiran" placeholder="Misal: Dokumen, Berkas" />
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <flux:select wire:model="signed_by" label="Ditandatangani Oleh">
                            <option value="">-- Pilih Penandatangan --</option>
                            @foreach ($signers as $signer)
                                <option value="{{ $signer->user_id }}">{{ $signer->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="sent_date" type="date" label="Tanggal Dikirim (Opsional)" />
                    </div>

                    <div class="mt-4">
                        <flux:textarea wire:model="content_summary" label="Ringkasan Isi (Opsional)" rows="2" placeholder="Garis besar isi surat..." />
                    </div>
                    <div class="mt-4">
                        <flux:textarea wire:model="notes" label="Catatan Tambahan (Opsional)" rows="2" placeholder="Catatan internal..." />
                    </div>
                </div>

                <!-- Upload -->
                <div>
                    <flux:heading size="md" class="mb-2">4. Arsip Berkas</flux:heading>
                    <flux:separator class="mb-4" />
                    <flux:input wire:model="file" type="file" label="Upload File Surat Asli" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
                    <flux:text class="text-xs text-gray-500 mt-1">Format: PDF, JPG, PNG, DOC, DOCX. Maksimal 10MB.</flux:text>
                </div>

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
            <flux:heading size="lg" class="mb-4">Detail Surat Keluar</flux:heading>
            
            <div class="space-y-6">
                <!-- Header Status & Nomor -->
                <div class="flex flex-col md:flex-row justify-between items-start gap-4 pb-4 border-b border-gray-100 dark:border-zinc-700">
                    <div>
                        <flux:text class="text-sm font-medium text-blue-600 dark:text-blue-400">Nomor Surat</flux:text>
                        <flux:heading size="xl" class="mt-1 font-mono tracking-tight">{{ $viewLetter->letter_number }}</flux:heading>
                        <flux:text class="text-xs text-gray-500 mt-1">No. Agenda: {{ $viewLetter->agenda_number }}</flux:text>
                    </div>
                    <div class="text-right">
                        <flux:badge size="lg" color="{{ $statusColors[$viewLetter->status] ?? 'zinc' }}">
                            {{ $statuses[$viewLetter->status] ?? $viewLetter->status }}
                        </flux:badge>
                    </div>
                </div>

                <!-- Main Info Card -->
                <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-5 space-y-5 border border-gray-200 dark:border-zinc-700/80 shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-6">
                        <div>
                            <flux:text class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Penerima Tujuan</flux:text>
                            <flux:text class="font-semibold text-gray-900 dark:text-white text-lg">{{ $viewLetter->recipient }}</flux:text>
                            @if ($viewLetter->recipient_address)
                                <flux:text class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-3">{{ $viewLetter->recipient_address }}</flux:text>
                            @endif
                        </div>
                        <div>
                            <flux:text class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Perihal / Subjek</flux:text>
                            <flux:text class="font-semibold text-gray-900 dark:text-white text-lg leading-tight">{{ $viewLetter->subject }}</flux:text>
                        </div>
                        
                        <div>
                            <flux:text class="text-xs font-medium text-gray-500 mb-1">Tanggal Surat</flux:text>
                            <div class="flex items-center gap-2">
                                <flux:icon name="calendar" class="size-4 text-gray-400" />
                                <flux:text class="font-medium text-gray-800 dark:text-gray-200">{{ $viewLetter->letter_date->format('d F Y') }}</flux:text>
                            </div>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium text-gray-500 mb-1">Tanggal Dikirim</flux:text>
                            <div class="flex items-center gap-2">
                                <flux:icon name="paper-airplane" class="size-4 text-{{ $viewLetter->sent_date ? 'green-500' : 'gray-400' }}" />
                                <flux:text class="font-medium {{ $viewLetter->sent_date ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $viewLetter->sent_date?->format('d F Y') ?? 'Belum Terkirim' }}
                                </flux:text>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Meta Data -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 px-2">
                    <div>
                        <flux:text class="text-xs text-gray-500 mb-1">Klasifikasi Surat</flux:text>
                        <flux:text class="font-medium text-gray-900 dark:text-gray-100">{{ $classifications[$viewLetter->classification] ?? $viewLetter->classification }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-gray-500 mb-1">Sifat Surat</flux:text>
                        <flux:badge size="sm" color="{{ $natureColors[$viewLetter->nature] ?? 'zinc' }}">
                            {{ $natures[$viewLetter->nature] ?? $viewLetter->nature }}
                        </flux:badge>
                    </div>
                    @if ($viewLetter->signer)
                    <div>
                        <flux:text class="text-xs text-gray-500 mb-1">Ditandatangani Oleh</flux:text>
                        <flux:text class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <flux:icon name="pencil-square" class="size-4 text-gray-400" />
                            {{ $viewLetter->signer->name }}
                        </flux:text>
                    </div>
                    @endif
                </div>

                <!-- Text Areas -->
                @if ($viewLetter->content_summary || $viewLetter->notes)
                <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-zinc-700">
                    @if ($viewLetter->content_summary)
                    <div>
                        <flux:text class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wider">Ringkasan Isi</flux:text>
                        <div class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-zinc-800/80 p-4 rounded-xl border border-gray-200 dark:border-zinc-700 leading-relaxed shadow-sm">
                            {{ $viewLetter->content_summary }}
                        </div>
                    </div>
                    @endif

                    @if ($viewLetter->notes)
                    <div>
                        <flux:text class="text-xs font-bold text-yellow-700 dark:text-yellow-500 mb-2 uppercase tracking-wider">Catatan Tambahan</flux:text>
                        <div class="text-sm text-yellow-800 dark:text-yellow-200 bg-yellow-50/80 dark:bg-yellow-900/20 p-4 rounded-xl border border-yellow-200 dark:border-yellow-900/30 leading-relaxed">
                            {{ $viewLetter->notes }}
                        </div>
                    </div>
                    @endif
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
                    <flux:text class="text-xs text-gray-500">Dibuat oleh: {{ $viewLetter->creator?->name ?? 'N/A' }} pada {{ $viewLetter->created_at->format('d M Y H:i') }}</flux:text>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <flux:button variant="ghost" wire:click="closeModal">Tutup</flux:button>
                @if ($viewLetter->isEditable())
                    <flux:button icon="pencil-square" wire:click="openEdit({{ $viewLetter->id }})">Edit</flux:button>
                @endif
            </div>
        </flux:card>
        @endif
    </flux:modal>
</div>
