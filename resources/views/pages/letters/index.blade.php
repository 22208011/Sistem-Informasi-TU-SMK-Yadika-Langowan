<?php

use App\Models\Letter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Surat Menyurat')] class extends Component {
    use WithPagination;

    public $search = '';
    public $type = '';
    public $status = '';

    public function delete($id)
    {
        $letter = Letter::findOrFail($id);

        // Only allow delete if draft or user is admin
        if ($letter->status !== 'draft' && !auth()->user()->isAdmin()) {
            session()->flash('error', 'Hanya surat berstatus draft yang dapat dihapus.');
            return;
        }

        $letter->delete();
        session()->flash('success', 'Surat berhasil dihapus.');
    }

    public function approve($id)
    {
        $letter = Letter::findOrFail($id);
        $letter->update([
            'status' => 'approved',
            'approver_id' => auth()->id(),
            'approved_at' => now(),
        ]);
        session()->flash('success', 'Surat berhasil disetujui.');
    }

    public function reject($id)
    {
        $letter = Letter::findOrFail($id);
        $letter->update([
            'status' => 'rejected',
            'approver_id' => auth()->id(),
            'approved_at' => now(),
        ]);
        session()->flash('success', 'Surat ditolak.');
    }

    public function with(): array
    {
        $query = Letter::query()->with(['student', 'author', 'approver']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('letter_number', 'like', '%' . $this->search . '%')
                    ->orWhere('subject', 'like', '%' . $this->search . '%')
                    ->orWhereHas('student', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));
            });
        }

        if ($this->type) {
            $query->where('letter_type', $this->type);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $letters = $query->orderBy('created_at', 'desc')->paginate(15);

        return [
            'letters' => $letters,
            'letterTypes' => Letter::TYPES,
            'statusLabels' => Letter::STATUSES,
            'canCreate' => auth()->user()->hasPermission('letters.create'),
            'canApprove' => auth()->user()->hasPermission('letters.approve'),
        ];
    }
}; ?>

<div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">Surat Menyurat</flux:heading>
                <flux:subheading>Kelola surat-surat resmi sekolah</flux:subheading>
            </div>
            @if ($canCreate)
            <flux:button icon="plus" :href="route('letters.create')" wire:navigate>
                Buat Surat
            </flux:button>
            @endif
        </div>

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

        <!-- Filters -->
        <flux:card>
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nomor surat, perihal, atau siswa..." icon="magnifying-glass" />
                <flux:select wire:model.live="type">
                    <option value="">Semua Jenis</option>
                    @foreach ($letterTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="status">
                    <option value="">Semua Status</option>
                    @foreach ($statusLabels as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        <!-- Letters Table -->
        <flux:card>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nomor Surat</flux:table.column>
                    <flux:table.column>Jenis</flux:table.column>
                    <flux:table.column>Perihal</flux:table.column>
                    <flux:table.column>Siswa</flux:table.column>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($letters as $letter)
                        <flux:table.row>
                            <flux:table.cell class="font-mono text-sm">
                                {{ $letter->letter_number ?? '-' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc">
                                    {{ $letterTypes[$letter->letter_type] ?? $letter->letter_type }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate font-medium">
                                {{ $letter->subject }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($letter->student)
                                    {{ $letter->student->name }}
                                    <span class="block text-xs text-gray-500">{{ $letter->student->nis }}</span>
                                @else
                                    -
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-sm text-gray-500">
                                {{ $letter->issued_at ? $letter->issued_at->format('d M Y') : $letter->created_at->format('d M Y') }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $statusColors = [
                                        'draft' => 'zinc',
                                        'pending' => 'yellow',
                                        'approved' => 'green',
                                        'rejected' => 'red',
                                        'sent' => 'blue',
                                    ];
                                @endphp
                                <flux:badge :color="$statusColors[$letter->status] ?? 'zinc'" size="sm">
                                    {{ $statusLabels[$letter->status] ?? $letter->status }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button icon="ellipsis-horizontal" size="sm" variant="ghost" />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" :href="route('letters.show', $letter)" wire:navigate>
                                            Lihat
                                        </flux:menu.item>
                                        @if ($letter->status === 'draft')
                                        <flux:menu.item icon="pencil" :href="route('letters.edit', $letter)" wire:navigate>
                                            Edit
                                        </flux:menu.item>
                                        @endif
                                        @if ($canApprove && $letter->status === 'pending')
                                        <flux:menu.separator />
                                        <flux:menu.item icon="check" wire:click="approve({{ $letter->id }})">
                                            Setujui
                                        </flux:menu.item>
                                        <flux:menu.item icon="x-mark" wire:click="reject({{ $letter->id }})">
                                            Tolak
                                        </flux:menu.item>
                                        @endif
                                        @if ($letter->status === 'draft')
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $letter->id }})" wire:confirm="Yakin ingin menghapus surat ini?">
                                            Hapus
                                        </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-12">
                                <flux:icon.envelope class="mx-auto mb-4 size-12 text-gray-400" />
                                <p class="text-gray-500">Belum ada surat.</p>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($letters->hasPages())
                <div class="mt-4">
                    {{ $letters->links() }}
                </div>
            @endif
    </flux:card>
</div>
