<?php

use App\Models\AuditLog;
use App\Models\UserActivityLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $search = '';
    public $user_id = '';
    public $event = '';
    public $model_type = '';
    public $date_from = '';
    public $date_to = '';
    public $tab = 'audit';

    // Detail view
    public $showDetailModal = false;
    public $selectedLog = null;

    public function openDetailModal($id)
    {
        if ($this->tab === 'audit') {
            $this->selectedLog = AuditLog::with(['user', 'auditable'])->findOrFail($id);
        } else {
            $this->selectedLog = UserActivityLog::with('user')->findOrFail($id);
        }
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedLog = null;
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function with(): array
    {
        // Audit Logs
        $auditQuery = AuditLog::query()->with('user');

        if ($this->search) {
            $auditQuery->where(function ($q) {
                $q->where('model_type', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('url', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->user_id) {
            $auditQuery->where('user_id', $this->user_id);
        }

        if ($this->event) {
            $auditQuery->where('action', $this->event);
        }

        if ($this->model_type) {
            $auditQuery->where('model_type', 'like', '%' . $this->model_type . '%');
        }

        if ($this->date_from) {
            $auditQuery->whereDate('created_at', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $auditQuery->whereDate('created_at', '<=', $this->date_to);
        }

        // Activity Logs
        $activityQuery = UserActivityLog::query()->with('user');

        if ($this->search) {
            $activityQuery->where(function ($q) {
                $q->where('notes', 'like', '%' . $this->search . '%')
                    ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                    ->orWhere('user_agent', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->user_id) {
            $activityQuery->where('user_id', $this->user_id);
        }

        if ($this->event) {
            $activityQuery->where('activity', $this->event);
        }

        if ($this->date_from) {
            $activityQuery->whereDate('created_at', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $activityQuery->whereDate('created_at', '<=', $this->date_to);
        }

        // Model types for filter
        $modelTypes = AuditLog::distinct()->pluck('model_type')
            ->map(function ($type) {
                return $type ? class_basename($type) : null;
            })
            ->filter()
            ->unique()
            ->values();

        return [
            'auditLogs' => $auditQuery->latest()->paginate(20, ['*'], 'audit_page'),
            'activityLogs' => $activityQuery->latest()->paginate(20, ['*'], 'activity_page'),
            'users' => User::orderBy('name')->get(),
            'actions' => AuditLog::ACTIONS,
            'activityActions' => UserActivityLog::ACTIONS,
            'modelTypes' => $modelTypes,
        ];
    }
}; ?>

<div>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">Audit Log</flux:heading>
                <flux:subheading>Lacak semua perubahan dan aktivitas pengguna</flux:subheading>
            </div>
        </div>

        <!-- Tabs -->
        <flux:card class="p-0!">
            <div class="flex border-b border-zinc-200 dark:border-zinc-700">
                <button 
                    wire:click="setTab('audit')" 
                    class="px-6 py-3 text-sm font-medium transition-colors {{ $tab === 'audit' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-zinc-500 hover:text-zinc-700' }}"
                >
                    Audit Log
                </button>
                <button 
                    wire:click="setTab('activity')" 
                    class="px-6 py-3 text-sm font-medium transition-colors {{ $tab === 'activity' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-zinc-500 hover:text-zinc-700' }}"
                >
                    Activity Log
                </button>
            </div>
        </flux:card>

        <!-- Filters -->
        <flux:card>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari..."
                    icon="magnifying-glass"
                />
                <flux:select wire:model.live="user_id">
                    <flux:select.option value="">Semua User</flux:select.option>
                    @foreach ($users as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="event">
                    <flux:select.option value="">Semua Event</flux:select.option>
                    @if ($tab === 'audit')
                        @foreach ($actions as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    @else
                        @foreach ($activityActions as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    @endif
                </flux:select>
                @if ($tab === 'audit')
                <flux:select wire:model.live="model_type">
                    <flux:select.option value="">Semua Model</flux:select.option>
                    @foreach ($modelTypes as $type)
                        <flux:select.option value="{{ $type }}">{{ $type }}</flux:select.option>
                    @endforeach
                </flux:select>
                @endif
                <flux:input 
                    wire:model.live="date_from" 
                    type="date"
                    placeholder="Dari Tanggal"
                />
                <flux:input 
                    wire:model.live="date_to" 
                    type="date"
                    placeholder="Sampai Tanggal"
                />
            </div>
        </flux:card>

        <!-- Audit Logs Table -->
        @if ($tab === 'audit')
        <flux:card>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Waktu</flux:table.column>
                    <flux:table.column>User</flux:table.column>
                    <flux:table.column>Event</flux:table.column>
                    <flux:table.column>Model</flux:table.column>
                    <flux:table.column>IP Address</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($auditLogs as $log)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="text-sm">
                                    <div>{{ $log->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-zinc-500">{{ $log->created_at->format('H:i:s') }}</div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $log->user?->name ?? $log->user_name ?? 'System' }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $actionColors = [
                                        'create' => 'green',
                                        'update' => 'blue',
                                        'delete' => 'red',
                                        'login' => 'cyan',
                                        'logout' => 'zinc',
                                        'approve' => 'green',
                                        'reject' => 'red',
                                        'export' => 'purple',
                                        'print' => 'indigo',
                                    ];
                                @endphp
                                <flux:badge color="{{ $actionColors[$log->action] ?? 'zinc' }}" size="sm">
                                    {{ $actions[$log->action] ?? $log->action }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div>
                                    <div class="font-medium">{{ $log->model_name }}</div>
                                    @if ($log->model_id)
                                        <div class="text-xs text-zinc-500">ID: {{ $log->model_id }}</div>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="font-mono text-xs">{{ $log->ip_address }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="xs" variant="ghost" icon="eye" wire:click="openDetailModal({{ $log->id }})" title="Detail" />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-8">
                                <div class="text-zinc-500">Belum ada audit log</div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $auditLogs->links() }}
            </div>
        </flux:card>
        @endif

        <!-- Activity Logs Table -->
        @if ($tab === 'activity')
        <flux:card>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Waktu</flux:table.column>
                    <flux:table.column>User</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                    <flux:table.column>Deskripsi</flux:table.column>
                    <flux:table.column>IP / Browser</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($activityLogs as $log)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="text-sm">
                                    <div>{{ $log->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-zinc-500">{{ $log->created_at->format('H:i:s') }}</div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $log->user?->name ?? 'Guest' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" variant="outline">
                                    {{ $activityActions[$log->action] ?? $log->action }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="max-w-xs truncate">{{ $log->description ?: '-' }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-sm">
                                    <div class="font-mono text-xs">{{ $log->ip_address }}</div>
                                    <div class="text-xs text-zinc-500">{{ $log->browser }} / {{ $log->os }}</div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="xs" variant="ghost" icon="eye" wire:click="openDetailModal({{ $log->id }})" title="Detail" />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-8">
                                <div class="text-zinc-500">Belum ada activity log</div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $activityLogs->links() }}
            </div>
        </flux:card>
        @endif

        <!-- Detail Modal -->
        <flux:modal wire:model="showDetailModal" class="w-full max-w-2xl">
            @if ($selectedLog)
            <div class="space-y-6">
                <flux:heading size="lg">Detail Log</flux:heading>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="text-zinc-500">Waktu</div>
                            <div class="font-medium">{{ $selectedLog->created_at->format('d F Y H:i:s') }}</div>
                        </div>
                        <div>
                            <div class="text-zinc-500">User</div>
                            <div class="font-medium">{{ $selectedLog->user?->name ?? 'System' }}</div>
                        </div>
                        <div>
                            <div class="text-zinc-500">IP Address</div>
                            <div class="font-mono">{{ $selectedLog->ip_address }}</div>
                        </div>
                        <div>
                            <div class="text-zinc-500">URL</div>
                            <div class="truncate">{{ $selectedLog->url }}</div>
                        </div>
                    </div>

                    @if ($tab === 'audit' && $selectedLog instanceof \App\Models\AuditLog)
                        <div>
                            <div class="text-zinc-500 text-sm mb-1">Aksi</div>
                            @php
                                $actionColors = [
                                    'create' => 'green',
                                    'update' => 'blue',
                                    'delete' => 'red',
                                    'login' => 'cyan',
                                    'logout' => 'zinc',
                                    'approve' => 'green',
                                    'reject' => 'red',
                                    'export' => 'purple',
                                    'print' => 'indigo',
                                ];
                            @endphp
                            <flux:badge color="{{ $actionColors[$selectedLog->action] ?? 'zinc' }}">
                                {{ $actions[$selectedLog->action] ?? $selectedLog->action }}
                            </flux:badge>
                        </div>

                        @if ($selectedLog->description)
                        <div>
                            <div class="text-zinc-500 text-sm mb-1">Deskripsi</div>
                            <div class="font-medium">{{ $selectedLog->description }}</div>
                        </div>
                        @endif

                        <div>
                            <div class="text-zinc-500 text-sm mb-1">Model</div>
                            <div class="font-medium">{{ $selectedLog->model_name }} @if($selectedLog->model_id)(ID: {{ $selectedLog->model_id }})@endif</div>
                        </div>

                        @if ($selectedLog->old_values)
                        <div>
                            <div class="text-zinc-500 text-sm mb-1">Data Lama</div>
                            <pre class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg text-xs overflow-auto max-h-48">{{ json_encode($selectedLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                        @endif

                        @if ($selectedLog->new_values)
                        <div>
                            <div class="text-zinc-500 text-sm mb-1">Data Baru</div>
                            <pre class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg text-xs overflow-auto max-h-48">{{ json_encode($selectedLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                        @endif

                        @if ($selectedLog->changed_fields && count($selectedLog->changed_fields) > 0)
                        <div>
                            <div class="text-zinc-500 text-sm mb-1">Field yang Berubah</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($selectedLog->changed_fields as $field)
                                    <flux:badge size="sm" variant="outline">{{ $field }}</flux:badge>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @else
                        <div>
                            <div class="text-zinc-500 text-sm mb-1">Aksi</div>
                            <flux:badge size="sm" variant="outline">
                                {{ $activityActions[$selectedLog->action] ?? $selectedLog->action }}
                            </flux:badge>
                        </div>

                        <div>
                            <div class="text-zinc-500 text-sm mb-1">Deskripsi</div>
                            <div class="font-medium">{{ $selectedLog->description ?: '-' }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-zinc-500">Browser</div>
                                <div class="font-medium">{{ $selectedLog->browser }}</div>
                            </div>
                            <div>
                                <div class="text-zinc-500">OS</div>
                                <div class="font-medium">{{ $selectedLog->os }}</div>
                            </div>
                        </div>

                        @if ($selectedLog->request_data)
                        <div>
                            <div class="text-zinc-500 text-sm mb-1">Request Data</div>
                            <pre class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg text-xs overflow-auto max-h-48">{{ json_encode($selectedLog->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                        @endif
                    @endif

                    <div>
                        <div class="text-zinc-500 text-sm mb-1">User Agent</div>
                        <div class="text-xs text-zinc-600 dark:text-zinc-400 break-all">{{ $selectedLog->user_agent }}</div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <flux:button variant="ghost" wire:click="closeDetailModal">Tutup</flux:button>
                </div>
            </div>
            @endif
        </flux:modal>
    </div>
</div>
