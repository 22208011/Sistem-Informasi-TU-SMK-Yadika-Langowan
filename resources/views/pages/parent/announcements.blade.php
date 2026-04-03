<?php

use App\Models\Announcement;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Pengumuman Sekolah')] class extends Component {
    use WithPagination;

    public $type = '';
    public $search = '';

    public function with(): array
    {
        $query = Announcement::query()
            ->active()
            ->published()
            ->visibleTo(auth()->user());

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'announcements' => $query->orderBy('is_pinned', 'desc')
                ->orderBy('published_at', 'desc')
                ->paginate(10),
                'types' => Announcement::TYPES,
        ];
    }
}; ?>

<div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">Pengumuman Sekolah</flux:heading>
                <flux:subheading>Informasi dan pengumuman dari sekolah</flux:subheading>
            </div>
        </div>

        <!-- Filters -->
        <flux:card>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex-1">
                    <flux:input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari pengumuman..." icon="magnifying-glass" />
                </div>
                <flux:select wire:model.live="type" class="sm:w-48">
                    <option value="">Semua Tipe</option>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        <!-- Announcements List -->
        <div class="space-y-4">
            @forelse ($announcements as $announcement)
                <flux:card class="{{ $announcement->is_pinned ? 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20' : '' }}">
                    <div class="flex items-start gap-4">
                        <!-- Icon -->
                        <div class="shrink-0">
                            @switch($announcement->type)
                                @case('urgent')
                                    <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/30">
                                        <flux:icon.exclamation-triangle class="size-6 text-red-600 dark:text-red-400" />
                                    </div>
                                    @break
                                @case('important')
                                    <div class="rounded-full bg-orange-100 p-3 dark:bg-orange-900/30">
                                        <flux:icon.exclamation-circle class="size-6 text-orange-600 dark:text-orange-400" />
                                    </div>
                                    @break
                                @case('event')
                                    <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/30">
                                        <flux:icon.calendar class="size-6 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    @break
                                @case('academic')
                                    <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/30">
                                        <flux:icon.academic-cap class="size-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    @break
                                @default
                                    <div class="rounded-full bg-gray-100 p-3 dark:bg-gray-800">
                                        <flux:icon.megaphone class="size-6 text-gray-600 dark:text-gray-400" />
                                    </div>
                            @endswitch
                        </div>

                        <!-- Content -->
                        <div class="min-w-0 flex-1">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                @if ($announcement->is_pinned)
                                    <flux:badge color="amber" size="sm">
                                        <flux:icon.bookmark class="mr-1 size-3" />
                                        Disematkan
                                    </flux:badge>
                                @endif
                                <flux:badge size="sm" :color="match($announcement->type) {
                                    'urgent' => 'red',
                                    'important' => 'orange',
                                    'event' => 'purple',
                                    'academic' => 'blue',
                                    default => 'zinc'
                                }">
                                    {{ ucfirst($announcement->type === 'general' ? 'Umum' : ($announcement->type === 'academic' ? 'Akademik' : ($announcement->type === 'event' ? 'Kegiatan' : ($announcement->type === 'important' ? 'Penting' : 'Darurat')))) }}
                                </flux:badge>
                            </div>

                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $announcement->title }}
                            </h3>

                            <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
                                {!! nl2br(e($announcement->content)) !!}
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1">
                                    <flux:icon.clock class="size-4" />
                                    {{ $announcement->published_at?->format('d M Y H:i') ?? $announcement->created_at->format('d M Y H:i') }}
                                </span>
                                @if ($announcement->author)
                                    <span class="flex items-center gap-1">
                                        <flux:icon.user class="size-4" />
                                        {{ $announcement->author->name }}
                                    </span>
                                @endif
                                @if ($announcement->expires_at)
                                    <span class="flex items-center gap-1">
                                        <flux:icon.calendar class="size-4" />
                                        Berlaku hingga: {{ $announcement->expires_at->format('d M Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </flux:card>
            @empty
                <flux:card>
                    <div class="py-12 text-center">
                        <flux:icon.megaphone class="mx-auto mb-4 size-12 text-gray-400" />
                        <flux:heading size="lg">Tidak Ada Pengumuman</flux:heading>
                        <flux:subheading>Belum ada pengumuman yang tersedia saat ini.</flux:subheading>
                    </div>
                </flux:card>
            @endforelse
        </div>

        <!-- Pagination -->
        @if ($announcements->hasPages())
            <div class="mt-4">
                {{ $announcements->links() }}
            </div>
        @endif
    </div>
</div>
