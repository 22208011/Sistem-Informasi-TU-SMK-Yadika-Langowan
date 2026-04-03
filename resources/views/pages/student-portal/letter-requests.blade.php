<?php

use App\Models\LetterRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] #[Title('Permohonan Surat Saya')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterType = '';
    public string $filterStatus = '';

    public function mount()
    {
        if (!auth()->user()->isStudent()) {
            return redirect()->route('dashboard');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function cancel(int $id)
    {
        $request = LetterRequest::where('student_id', auth()->user()->student_id)
            ->findOrFail($id);

        if (!$request->canBeCancelled()) {
            session()->flash('error', 'Permohonan ini tidak dapat dibatalkan karena sudah diproses.');
            return;
        }

        $request->delete();
        session()->flash('success', 'Permohonan surat berhasil dibatalkan.');
    }

    public function download(int $id)
    {
        $request = LetterRequest::where('student_id', auth()->user()->student_id)
            ->findOrFail($id);

        if (!$request->canBeDownloaded()) {
            session()->flash('error', 'File surat belum tersedia untuk diunduh.');
            return;
        }

        return Storage::disk('public')->download($request->result_file);
    }

    public function with(): array
    {
        $student = auth()->user()->student;

        $query = LetterRequest::query()
            ->where('student_id', $student?->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('request_number', 'like', '%' . $this->search . '%')
                  ->orWhere('purpose', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('letter_type', $this->filterType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return [
            'requests' => $query->orderBy('created_at', 'desc')->paginate(10),
            'letterTypes' => LetterRequest::TYPES,
            'statuses' => LetterRequest::STATUSES,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Permohonan Surat Saya') }}</flux:heading>
            <flux:subheading>{{ __('Lihat status dan riwayat permohonan surat Anda') }}</flux:subheading>
        </div>
        <flux:button icon="plus" href="{{ route('student-portal.letter-requests.create') }}" wire:navigate>
            {{ __('Ajukan Permohonan') }}
        </flux:button>
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
        <div class="grid gap-4 sm:grid-cols-3 p-1">
            <flux:input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nomor atau keperluan..." icon="magnifying-glass" />
            <flux:select wire:model.live="filterType">
                <option value="">{{ __('Semua Jenis') }}</option>
                @foreach ($letterTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="filterStatus">
                <option value="">{{ __('Semua Status') }}</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <!-- Status Legend -->
    <div class="flex flex-wrap gap-3">
        <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-amber-400"></div>
            <flux:text size="sm">{{ __('Menunggu') }}</flux:text>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
            <flux:text size="sm">{{ __('Diproses') }}</flux:text>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-green-400"></div>
            <flux:text size="sm">{{ __('Selesai') }}</flux:text>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-red-400"></div>
            <flux:text size="sm">{{ __('Ditolak') }}</flux:text>
        </div>
    </div>

    <!-- Requests List -->
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('No. Permohonan') }}</flux:table.column>
                <flux:table.column>{{ __('Jenis Surat') }}</flux:table.column>
                <flux:table.column>{{ __('Keperluan') }}</flux:table.column>
                <flux:table.column>{{ __('Tanggal Pengajuan') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($requests as $request)
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-sm">
                            {{ $request->request_number }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="{{ \App\Models\LetterRequest::TYPE_COLORS[$request->letter_type] ?? 'zinc' }}">
                                {{ \App\Models\LetterRequest::TYPES[$request->letter_type] ?? $request->letter_type }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate">
                            {{ $request->purpose ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">
                            {{ $request->created_at->translatedFormat('d M Y H:i') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="{{ \App\Models\LetterRequest::STATUS_COLORS[$request->status] ?? 'zinc' }}">
                                {{ \App\Models\LetterRequest::STATUSES[$request->status] ?? $request->status }}
                            </flux:badge>
                            @if ($request->status === 'rejected' && $request->admin_notes)
                                <flux:text size="sm" class="text-red-500 mt-1">
                                    Alasan: {{ Str::limit($request->admin_notes, 40) }}
                                </flux:text>
                            @endif
                            @if ($request->completed_at)
                                <flux:text size="sm" class="text-green-600 dark:text-green-400 mt-1">
                                    {{ $request->completed_at->translatedFormat('d M Y') }}
                                </flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                @if ($request->canBeDownloaded())
                                    <flux:button size="sm" variant="primary" icon="arrow-down-tray" wire:click="download({{ $request->id }})">
                                        {{ __('Unduh') }}
                                    </flux:button>
                                @endif
                                @if ($request->canBeCancelled())
                                    <flux:button size="sm" variant="danger" icon="x-mark" wire:click="cancel({{ $request->id }})" wire:confirm="Yakin ingin membatalkan permohonan ini?">
                                        {{ __('Batal') }}
                                    </flux:button>
                                @endif
                                @if ($request->status === 'completed' && !$request->result_file)
                                    <flux:badge size="sm" color="yellow">
                                        {{ __('File belum tersedia') }}
                                    </flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-12">
                            <flux:icon name="document-text" class="mx-auto mb-4 size-12 text-zinc-400" />
                            <p class="text-zinc-500 mb-3">{{ __('Belum ada permohonan surat.') }}</p>
                            <flux:button variant="primary" size="sm" href="{{ route('student-portal.letter-requests.create') }}" wire:navigate>
                                {{ __('Ajukan Permohonan Pertama') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($requests->hasPages())
            <div class="mt-4">
                {{ $requests->links() }}
            </div>
        @endif
    </flux:card>
</div>
