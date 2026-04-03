<?php

use App\Models\Announcement;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Pengumuman')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterType = '';

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

    public function with(): array
    {
        $query = Announcement::query()
            ->active()
            ->published()
            ->visibleTo(auth()->user());

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        return [
            'announcements' => $query
                ->orderBy('is_pinned', 'desc')
                ->orderBy('published_at', 'desc')
                ->paginate(10),
            'types' => Announcement::TYPES,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <flux:heading size="xl">{{ __('Pengumuman Sekolah') }}</flux:heading>
        <flux:subheading>{{ __('Informasi dan pengumuman terbaru dari sekolah') }}</flux:subheading>
    </div>

    <!-- Filters -->
    <flux:card>
        <div class="grid gap-4 sm:grid-cols-2 p-1">
            <flux:input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari pengumuman..." icon="magnifying-glass" />
            <flux:select wire:model.live="filterType">
                <option value="">{{ __('Semua Jenis') }}</option>
                @foreach ($types as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <!-- Announcements List -->
    <div class="space-y-4">
        @forelse ($announcements as $announcement)
            <flux:card class="{{ $announcement->is_pinned ? 'border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/10' : '' }}">
                <div class="p-2">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                @if ($announcement->is_pinned)
                                    <flux:icon name="pin" class="size-4 text-amber-500" />
                                @endif
                                <flux:badge size="sm" color="{{ \App\Models\Announcement::TYPE_COLORS[$announcement->type] ?? 'zinc' }}">
                                    {{ \App\Models\Announcement::TYPES[$announcement->type] ?? $announcement->type }}
                                </flux:badge>
                                @if ($announcement->priority === 'high')
                                    <flux:badge size="sm" color="red">{{ __('Penting') }}</flux:badge>
                                @endif
                            </div>
                            <flux:heading size="lg">{{ $announcement->title }}</flux:heading>
                            <div class="mt-3 prose prose-sm dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400">
                                {!! nl2br(e($announcement->content)) !!}
                            </div>
                            @if ($announcement->attachment)
                                <div class="mt-3">
                                    <flux:badge size="sm" color="zinc" icon="paper-clip">
                                        {{ __('Lampiran tersedia') }}
                                    </flux:badge>
                                </div>
                            @endif
                        </div>
                        <flux:text size="sm" class="text-zinc-500 whitespace-nowrap shrink-0">
                            {{ $announcement->published_at?->translatedFormat('d M Y') ?? $announcement->created_at->translatedFormat('d M Y') }}
                        </flux:text>
                    </div>
                    <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                        <flux:text size="sm" class="text-zinc-400">
                            {{ __('Oleh: :name', ['name' => $announcement->author?->name ?? 'Sekolah']) }}
                            &bull; {{ $announcement->published_at?->diffForHumans() ?? $announcement->created_at->diffForHumans() }}
                        </flux:text>
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card>
                <div class="py-12 text-center">
                    <flux:icon name="megaphone" class="mx-auto mb-4 size-12 text-zinc-400" />
                    <flux:heading size="lg" class="text-zinc-500">{{ __('Tidak ada pengumuman') }}</flux:heading>
                    <flux:text class="text-zinc-400 mt-1">{{ __('Belum ada pengumuman yang tersedia saat ini.') }}</flux:text>
                </div>
            </flux:card>
        @endforelse
    </div>

    @if ($announcements->hasPages())
        <div class="mt-4">
            {{ $announcements->links() }}
        </div>
    @endif
</div>
