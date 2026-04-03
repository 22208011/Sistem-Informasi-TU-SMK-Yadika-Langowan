<?php

use App\Models\Announcement;
use App\Models\Department;
use App\Models\Classroom;
use App\Livewire\Concerns\WithNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.app')] #[Title('Pengumuman')] class extends Component {
    use WithPagination;
    use WithNotification;

    public string $search = '';
    public string $filterType = '';
    public string $filterPriority = '';
    public string $filterStatus = '';

    // Form fields
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $title = '';
    public string $content = '';
    public string $type = 'general';
    public string $priority = 'normal';
    public string $target_audience = 'all';
    public ?int $target_department_id = null;
    public ?int $target_classroom_id = null;
    public ?string $published_at = null;
    public ?string $expires_at = null;
    public bool $is_pinned = false;
    public bool $is_active = true;

    // Detail view
    public bool $showDetail = false;
    public ?Announcement $selectedAnnouncement = null;

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', 'in:general,academic,event,urgent,holiday'],
            'priority' => ['required', 'in:low,normal,high'],
            'target_audience' => ['required', 'in:all,students,teachers,parents,staff,specific_class,specific_department'],
            'target_department_id' => ['nullable', 'exists:departments,id'],
            'target_classroom_id' => ['nullable', 'exists:classrooms,id'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
            'is_pinned' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    protected $messages = [
        'title.required' => 'Judul pengumuman wajib diisi.',
        'content.required' => 'Isi pengumuman wajib diisi.',
        'type.required' => 'Tipe pengumuman wajib dipilih.',
        'expires_at.after' => 'Tanggal kedaluwarsa harus setelah tanggal terbit.',
    ];

    #[Computed]
    public function announcements()
    {
        return Announcement::query()
            ->with(['author', 'targetDepartment', 'targetClassroom'])
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterPriority, fn($q) => $q->where('priority', $this->filterPriority))
            ->when($this->filterStatus !== '', fn($q) => $q->where('is_active', $this->filterStatus === '1'))
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    #[Computed]
    public function departments()
    {
        return Department::active()->orderBy('name')->get();
    }

    #[Computed]
    public function classrooms()
    {
        return Classroom::active()->orderBy('name')->get();
    }

    #[Computed]
    public function statistics()
    {
        return [
            'total' => Announcement::count(),
            'active' => Announcement::where('is_active', true)->count(),
            'pinned' => Announcement::where('is_pinned', true)->count(),
            'urgent' => Announcement::where('type', 'urgent')->where('is_active', true)->count(),
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->published_at = now()->format('Y-m-d\TH:i');
        $this->showModal = true;
    }

    public function edit(Announcement $announcement): void
    {
        $this->editingId = $announcement->id;
        $this->title = $announcement->title;
        $this->content = $announcement->content;
        $this->type = $announcement->type;
        $this->priority = $announcement->priority;
        $this->target_audience = $announcement->target_audience;
        $this->target_department_id = $announcement->target_department_id;
        $this->target_classroom_id = $announcement->target_classroom_id;
        $this->published_at = $announcement->published_at?->format('Y-m-d\TH:i');
        $this->expires_at = $announcement->expires_at?->format('Y-m-d\TH:i');
        $this->is_pinned = (bool) $announcement->is_pinned;
        $this->is_active = (bool) $announcement->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        try {
            $data = [
                'title' => $this->title,
                'content' => $this->content,
                'type' => $this->type,
                'priority' => $this->priority,
                'target_audience' => $this->target_audience,
                'target_department_id' => $this->target_audience === 'specific_department' ? $this->target_department_id : null,
                'target_classroom_id' => $this->target_audience === 'specific_class' ? $this->target_classroom_id : null,
                'published_at' => $this->published_at ?: now(),
                'expires_at' => $this->expires_at ?: null,
                'is_pinned' => (bool) $this->is_pinned,
                'is_active' => (bool) $this->is_active,
            ];

            if ($this->editingId) {
                $announcement = Announcement::findOrFail($this->editingId);
                $announcement->update($data);
                $this->success('Pengumuman berhasil diperbarui.');
            } else {
                $data['author_id'] = Auth::id();
                Announcement::create($data);
                $this->success('Pengumuman berhasil ditambahkan.');
            }

            $this->closeModal();
        } catch (\Exception $e) {
            $this->error('Gagal menyimpan pengumuman: ' . $e->getMessage());
        }
    }

    public function toggleActive(Announcement $announcement): void
    {
        $announcement->update(['is_active' => !$announcement->is_active]);
        $status = $announcement->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->success("Pengumuman berhasil {$status}.");
    }

    public function togglePinned(Announcement $announcement): void
    {
        $announcement->update(['is_pinned' => !$announcement->is_pinned]);
        $status = $announcement->is_pinned ? 'disematkan' : 'dilepas sematannya';
        $this->success("Pengumuman berhasil {$status}.");
    }

    public function delete(Announcement $announcement): void
    {
        try {
            $title = $announcement->title;
            $announcement->delete();
            $this->success("Pengumuman '{$title}' berhasil dihapus.");
        } catch (\Exception $e) {
            $this->error('Gagal menghapus pengumuman: ' . $e->getMessage());
        }
    }

    public function viewDetail(Announcement $announcement): void
    {
        $this->selectedAnnouncement = $announcement;
        $this->showDetail = true;
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->content = '';
        $this->type = 'general';
        $this->priority = 'normal';
        $this->target_audience = 'all';
        $this->target_department_id = null;
        $this->target_classroom_id = null;
        $this->published_at = null;
        $this->expires_at = null;
        $this->is_pinned = false;
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
    }

    public function toggleActiveForm(): void
    {
        $this->is_active = !$this->is_active;
    }

    public function togglePinnedForm(): void
    {
        $this->is_pinned = !$this->is_pinned;
    }

    public function getTypeColor(string $type): string
    {
        return Announcement::TYPE_COLORS[$type] ?? 'zinc';
    }

    public function getPriorityBadge(string $priority): array
    {
        return match($priority) {
            'high' => ['color' => 'red', 'icon' => 'arrow-up'],
            'low' => ['color' => 'zinc', 'icon' => 'arrow-down'],
            default => ['color' => 'blue', 'icon' => 'minus'],
        };
    }
}; ?>

<div>
    <!-- Page Header -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Pengumuman') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Kelola pengumuman sekolah untuk siswa, guru, dan orang tua.') }}</x-slot:subtitle>
        <x-slot:actions>
            @can('announcements.create')
            <flux:button wire:click="create" icon="plus" class="rounded-xl! bg-linear-to-r! from-purple-600! to-indigo-600! hover:from-purple-700! hover:to-indigo-700! shadow-lg! shadow-purple-500!/25">
                {{ __('Buat Pengumuman') }}
            </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Notification --}}
    <x-notification-alert :type="$notificationType" :message="$notificationMessage" />

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
        <x-stat-card title="Total Pengumuman" :value="$this->statistics['total']" color="purple" class="animate-fade-in-up">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Aktif" :value="$this->statistics['active']" color="green" class="animate-fade-in-up delay-100">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Disematkan" :value="$this->statistics['pinned']" color="blue" class="animate-fade-in-up delay-200">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Mendesak" :value="$this->statistics['urgent']" color="red" class="animate-fade-in-up delay-300">
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Data Table Card -->
    <x-elegant-card :noPadding="true" class="animate-fade-in-up delay-400">
        <x-slot:header>
            <div class="flex flex-col lg:flex-row gap-4 w-full">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Cari pengumuman...') }}"
                    icon="magnifying-glass"
                    class="flex-1 max-w-sm rounded-xl! bg-zinc-50! dark:bg-zinc-800/50!"
                />

                <div class="flex flex-wrap gap-3">
                    <flux:select wire:model.live="filterType" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-35">
                        <option value="">Semua Tipe</option>
                        @foreach (App\Models\Announcement::TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="filterPriority" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-35">
                        <option value="">Semua Prioritas</option>
                        @foreach (App\Models\Announcement::PRIORITIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="filterStatus" class="rounded-xl! bg-zinc-50! dark:bg-zinc-800/50! min-w-30">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </flux:select>
                </div>
            </div>
        </x-slot:header>

        <flux:table class="table-elegant">
            <flux:table.columns>
                <flux:table.column class="font-semibold!">{{ __('Pengumuman') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Tipe') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Prioritas') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Target') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Terbit') }}</flux:table.column>
                <flux:table.column class="font-semibold!">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="text-right font-semibold!">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->announcements as $announcement)
                    <flux:table.row wire:key="ann-{{ $announcement->id }}" class="hover:bg-purple-50/50! dark:hover:bg-purple-900/10! transition-colors {{ $announcement->is_pinned ? 'bg-purple-50/30 dark:bg-purple-900/5' : '' }}">
                        <flux:table.cell>
                            <div class="flex items-start gap-3">
                                @if ($announcement->is_pinned)
                                    <div class="shrink-0 mt-1">
                                        <svg class="size-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-semibold text-zinc-800 dark:text-white">{{ Str::limit($announcement->title, 40) }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $announcement->excerpt }}</p>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $this->getTypeColor($announcement->type) }}" class="rounded-lg!">
                                {{ App\Models\Announcement::TYPES[$announcement->type] ?? $announcement->type }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @php $badge = $this->getPriorityBadge($announcement->priority); @endphp
                            <flux:badge color="{{ $badge['color'] }}" class="rounded-lg!">
                                {{ App\Models\Announcement::PRIORITIES[$announcement->priority] ?? $announcement->priority }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ App\Models\Announcement::AUDIENCES[$announcement->target_audience] ?? $announcement->target_audience }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $announcement->published_at?->format('d M Y') ?? '-' }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <button
                                type="button"
                                wire:click="toggleActive({{ $announcement->id }})"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $announcement->is_active ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                                role="switch"
                                aria-checked="{{ $announcement->is_active ? 'true' : 'false' }}"
                            >
                                <span class="{{ $announcement->is_active ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="rounded-lg! hover:bg-zinc-100! dark:hover:bg-zinc-800!" />
                                <flux:menu class="rounded-xl! shadow-xl! border-zinc!-200/50 dark:border-zinc!-700/50">
                                    <flux:menu.item wire:click="viewDetail({{ $announcement->id }})" icon="eye" class="rounded-lg!">
                                        {{ __('Lihat Detail') }}
                                    </flux:menu.item>
                                    @can('announcements.edit')
                                    <flux:menu.item wire:click="edit({{ $announcement->id }})" icon="pencil" class="rounded-lg!">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    @endcan
                                    <flux:menu.item wire:click="togglePinned({{ $announcement->id }})" icon="{{ $announcement->is_pinned ? 'bookmark-slash' : 'bookmark' }}" class="rounded-lg!">
                                        {{ $announcement->is_pinned ? __('Lepas Sematan') : __('Sematkan') }}
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="toggleActive({{ $announcement->id }})" icon="{{ $announcement->is_active ? 'x-circle' : 'check-circle' }}" class="rounded-lg!">
                                        {{ $announcement->is_active ? __('Nonaktifkan') : __('Aktifkan') }}
                                    </flux:menu.item>
                                    @can('announcements.delete')
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        wire:click="delete({{ $announcement->id }})"
                                        wire:confirm="{{ __('Apakah Anda yakin ingin menghapus pengumuman ini?') }}"
                                        icon="trash"
                                        variant="danger"
                                        class="rounded-lg!"
                                    >
                                        {{ __('Hapus') }}
                                    </flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-12">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                    <svg class="size-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                    </svg>
                                </div>
                                <p class="text-zinc-500 dark:text-zinc-400">{{ __('Belum ada pengumuman.') }}</p>
                                @can('announcements.create')
                                <flux:button wire:click="create" variant="ghost" icon="plus" size="sm" class="rounded-lg!">
                                    {{ __('Buat Pengumuman Pertama') }}
                                </flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->announcements->hasPages())
            <x-slot:footer>
                {{ $this->announcements->links() }}
            </x-slot:footer>
        @endif
    </x-elegant-card>

    <!-- Modal Form -->
    <flux:modal wire:model="showModal" class="max-w-2xl">
        <form wire:submit="save">
            <flux:modal.header class="border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-linear-to-br from-purple-500 to-indigo-600 text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                    </div>
                    <flux:heading size="lg">
                        {{ $editingId ? __('Edit Pengumuman') : __('Buat Pengumuman') }}
                    </flux:heading>
                </div>
            </flux:modal.header>

            <flux:modal.body class="space-y-4 py-6 max-h-[60vh] overflow-y-auto">
                <flux:input wire:model="title" label="{{ __('Judul Pengumuman') }}" placeholder="Masukkan judul pengumuman" required class="rounded-xl!" />

                <flux:textarea wire:model="content" label="{{ __('Isi Pengumuman') }}" rows="5" required class="rounded-xl!" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="type" label="{{ __('Tipe') }}" required class="rounded-xl!">
                        @foreach (App\Models\Announcement::TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="priority" label="{{ __('Prioritas') }}" required class="rounded-xl!">
                        @foreach (App\Models\Announcement::PRIORITIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:select wire:model.live="target_audience" label="{{ __('Target Audiens') }}" required class="rounded-xl!">
                    @foreach (App\Models\Announcement::AUDIENCES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                @if ($target_audience === 'specific_department')
                    <flux:select wire:model="target_department_id" label="{{ __('Pilih Jurusan') }}" class="rounded-xl!">
                        <option value="">-- Pilih Jurusan --</option>
                        @foreach ($this->departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </flux:select>
                @endif

                @if ($target_audience === 'specific_class')
                    <flux:select wire:model="target_classroom_id" label="{{ __('Pilih Kelas') }}" class="rounded-xl!">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach ($this->classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </flux:select>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <flux:input type="datetime-local" wire:model="published_at" label="{{ __('Tanggal Terbit') }}" class="rounded-xl!" />
                    <flux:input type="datetime-local" wire:model="expires_at" label="{{ __('Tanggal Kedaluwarsa') }}" class="rounded-xl!" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Sematkan') }}</p>
                                <p class="text-xs text-zinc-500">{{ __('Tampilkan di atas') }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="togglePinnedForm"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 {{ $is_pinned ? 'bg-purple-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                                role="switch"
                            >
                                <span class="{{ $is_pinned ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                    </div>

                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Aktif') }}</p>
                                <p class="text-xs text-zinc-500">{{ __('Tampilkan pengumuman') }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="toggleActiveForm"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $is_active ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                                role="switch"
                            >
                                <span class="{{ $is_active ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </flux:modal.body>

            <flux:modal.footer class="border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/50">
                <flux:button type="button" wire:click="closeModal" variant="ghost" class="rounded-xl!">{{ __('Batal') }}</flux:button>
                <flux:button type="submit" class="rounded-xl! bg-linear-to-r! from-purple-600! to-indigo-600!">
                    <svg class="size-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ $editingId ? __('Perbarui') : __('Publikasikan') }}
                </flux:button>
            </flux:modal.footer>
        </form>
    </flux:modal>

    <!-- Detail Modal -->
    <flux:modal wire:model="showDetail" class="max-w-2xl">
        @if ($selectedAnnouncement)
            <flux:modal.header class="border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-start gap-3">
                    @if ($selectedAnnouncement->is_pinned)
                        <svg class="size-5 text-purple-500 shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    @endif
                    <div class="flex-1">
                        <flux:heading size="lg">{{ $selectedAnnouncement->title }}</flux:heading>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <flux:badge color="{{ $this->getTypeColor($selectedAnnouncement->type) }}" class="rounded-lg!">
                                {{ App\Models\Announcement::TYPES[$selectedAnnouncement->type] ?? $selectedAnnouncement->type }}
                            </flux:badge>
                            <flux:badge color="{{ $this->getPriorityBadge($selectedAnnouncement->priority)['color'] }}" class="rounded-lg!">
                                {{ App\Models\Announcement::PRIORITIES[$selectedAnnouncement->priority] ?? $selectedAnnouncement->priority }}
                            </flux:badge>
                        </div>
                    </div>
                </div>
            </flux:modal.header>

            <flux:modal.body class="py-6">
                <div class="prose prose-zinc dark:prose-invert max-w-none">
                    {!! nl2br(e($selectedAnnouncement->content)) !!}
                </div>

                <div class="mt-6 pt-6 border-t border-zinc-100 dark:border-zinc-800 space-y-2 text-sm text-zinc-500 dark:text-zinc-400">
                    <p><strong>Target:</strong> {{ App\Models\Announcement::AUDIENCES[$selectedAnnouncement->target_audience] ?? $selectedAnnouncement->target_audience }}</p>
                    <p><strong>Diterbitkan:</strong> {{ $selectedAnnouncement->published_at?->format('d M Y H:i') ?? '-' }}</p>
                    @if ($selectedAnnouncement->expires_at)
                        <p><strong>Kedaluwarsa:</strong> {{ $selectedAnnouncement->expires_at->format('d M Y H:i') }}</p>
                    @endif
                    <p><strong>Penulis:</strong> {{ $selectedAnnouncement->author?->name ?? '-' }}</p>
                </div>
            </flux:modal.body>

            <flux:modal.footer class="border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/50">
                <flux:button type="button" wire:click="$set('showDetail', false)" variant="ghost" class="rounded-xl!">{{ __('Tutup') }}</flux:button>
                @can('announcements.edit')
                <flux:button wire:click="edit({{ $selectedAnnouncement->id }})" class="rounded-xl! bg-linear-to-r! from-purple-600! to-indigo-600!">
                    {{ __('Edit Pengumuman') }}
                </flux:button>
                @endcan
            </flux:modal.footer>
        @endif
    </flux:modal>
</div>

@script
<script>
    $wire.on('scroll-to-top', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>
@endscript
