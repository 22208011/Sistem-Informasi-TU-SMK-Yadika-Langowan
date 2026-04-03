<?php

use App\Models\Letter;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Detail Surat')] class extends Component {
    public Letter $letter;

    public function mount(Letter $letter)
    {
        $this->letter = $letter->load(['student', 'createdBy', 'approvedBy']);
    }

    public function markAsSent()
    {
        $this->letter->update(['status' => 'sent']);
        session()->flash('success', 'Status surat diubah menjadi terkirim.');
    }

    public function with(): array
    {
        return [
            'letterTypes' => [
                'summons' => 'Surat Panggilan',
                'warning' => 'Surat Peringatan',
                'certificate' => 'Surat Keterangan',
                'recommendation' => 'Surat Rekomendasi',
                'transfer' => 'Surat Pindah',
                'graduation' => 'Surat Kelulusan',
                'other' => 'Lainnya',
            ],
            'statusLabels' => [
                'draft' => 'Draft',
                'pending' => 'Menunggu Persetujuan',
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
                'sent' => 'Terkirim',
            ],
            'statusColors' => [
                'draft' => 'zinc',
                'pending' => 'yellow',
                'approved' => 'green',
                'rejected' => 'red',
                'sent' => 'blue',
            ],
        ];
    }
}; ?>

<div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <flux:button icon="arrow-left" variant="ghost" :href="route('letters.index')" wire:navigate />
                <div>
                    <flux:heading size="xl">Detail Surat</flux:heading>
                    <flux:subheading>{{ $letter->number ?? 'Belum ada nomor' }}</flux:subheading>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <flux:badge :color="$statusColors[$letter->status] ?? 'zinc'" size="lg">
                    {{ $statusLabels[$letter->status] ?? $letter->status }}
                </flux:badge>
                @if ($letter->status === 'approved')
                <flux:button icon="paper-airplane" wire:click="markAsSent" wire:confirm="Tandai surat ini sebagai terkirim?">
                    Tandai Terkirim
                </flux:button>
                @endif
                @if ($letter->status === 'draft')
                <flux:button icon="pencil" :href="route('letters.edit', $letter)" wire:navigate>
                    Edit
                </flux:button>
                @endif
            </div>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle" dismissible>
                {{ session('success') }}
            </flux:callout>
        @endif

        <!-- Letter Info -->
        <flux:card>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jenis Surat</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $letterTypes[$letter->type] ?? $letter->type }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $letter->date ? \Carbon\Carbon::parse($letter->date)->format('d M Y') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Dibuat Oleh</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $letter->createdBy?->name ?? '-' }}</p>
                </div>
                @if ($letter->approvedBy)
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $letter->status === 'approved' ? 'Disetujui Oleh' : 'Ditolak Oleh' }}
                    </p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $letter->approvedBy->name }}
                        <span class="block text-xs text-gray-500">
                            {{ $letter->approved_at ? \Carbon\Carbon::parse($letter->approved_at)->format('d M Y H:i') : '' }}
                        </span>
                    </p>
                </div>
                @endif
            </div>
        </flux:card>

        @if ($letter->student)
        <flux:card>
            <flux:heading size="sm" class="mb-4">Siswa Terkait</flux:heading>
            <div class="flex items-center gap-4">
                <div class="flex size-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon.user class="size-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $letter->student->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        NIS: {{ $letter->student->nis }} | Kelas: {{ $letter->student->classroom?->name ?? '-' }}
                    </p>
                </div>
            </div>
        </flux:card>
        @endif

        <!-- Letter Content -->
        <flux:card>
            <flux:heading size="sm" class="mb-4">Perihal</flux:heading>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $letter->subject }}</p>
        </flux:card>

        <flux:card>
            <flux:heading size="sm" class="mb-4">Isi Surat</flux:heading>
            <div class="prose prose-sm dark:prose-invert max-w-none rounded-lg bg-gray-50 p-6 dark:bg-gray-800">
                {!! nl2br(e($letter->content)) !!}
            </div>
        </flux:card>

        @if ($letter->notes)
        <flux:card>
            <flux:heading size="sm" class="mb-4">
                <flux:icon.lock-closed class="mr-2 inline size-4" />
                Catatan Internal
            </flux:heading>
            <p class="text-gray-600 dark:text-gray-400">{{ $letter->notes }}</p>
        </flux:card>
        @endif

        <!-- Actions -->
        <flux:card>
            <div class="flex flex-wrap gap-3">
                <flux:button icon="printer" variant="outline" onclick="window.print()">
                    Cetak
                </flux:button>
                <flux:button icon="arrow-down-tray" variant="outline">
                    Download PDF
                </flux:button>
            </div>
        </flux:card>
    </div>
</div>
