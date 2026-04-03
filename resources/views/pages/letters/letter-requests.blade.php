<?php

use App\Models\LetterRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] #[Title('Kelola Permohonan Surat')] class extends Component {
    use WithPagination, WithFileUploads;

    public string $search = '';
    public string $filterType = '';
    public string $filterStatus = '';

    // Modal properties
    public bool $showProcessModal = false;
    public ?int $processingRequestId = null;
    public string $adminNotes = '';
    public string $processAction = '';
    public $resultFile = null;

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

    public function openProcessModal(int $id, string $action)
    {
        $this->processingRequestId = $id;
        $this->processAction = $action;
        $this->adminNotes = '';
        $this->resultFile = null;
        $this->showProcessModal = true;
    }

    public function closeProcessModal()
    {
        $this->showProcessModal = false;
        $this->processingRequestId = null;
        $this->adminNotes = '';
        $this->processAction = '';
        $this->resultFile = null;
    }

    public function processRequest()
    {
        $request = LetterRequest::findOrFail($this->processingRequestId);

        if ($this->processAction === 'approve') {
            $this->validate([
                'adminNotes' => 'nullable|string|max:1000',
                'resultFile' => 'nullable|file|mimes:pdf|max:5120',
            ]);

            $resultFilePath = null;
            if ($this->resultFile) {
                $resultFilePath = $this->resultFile->store('letter-results', 'public');
            }

            $request->update([
                'status' => LetterRequest::STATUS_COMPLETED,
                'processed_by' => auth()->id(),
                'admin_notes' => $this->adminNotes,
                'result_file' => $resultFilePath,
                'processed_at' => now(),
                'completed_at' => now(),
            ]);

            session()->flash('success', 'Permohonan surat telah diselesaikan.');
        } elseif ($this->processAction === 'process') {
            $request->update([
                'status' => LetterRequest::STATUS_PROCESSING,
                'processed_by' => auth()->id(),
                'admin_notes' => $this->adminNotes,
                'processed_at' => now(),
            ]);

            session()->flash('success', 'Permohonan surat sedang diproses.');
        } elseif ($this->processAction === 'reject') {
            $this->validate([
                'adminNotes' => 'required|string|max:1000',
            ], [
                'adminNotes.required' => 'Alasan penolakan wajib diisi.',
            ]);

            $request->update([
                'status' => LetterRequest::STATUS_REJECTED,
                'processed_by' => auth()->id(),
                'admin_notes' => $this->adminNotes,
                'processed_at' => now(),
            ]);

            session()->flash('success', 'Permohonan surat telah ditolak.');
        }

        $this->closeProcessModal();
    }

    public function uploadResult(int $id)
    {
        $this->processingRequestId = $id;
        $this->processAction = 'upload';
        $this->resultFile = null;
        $this->showProcessModal = true;
    }

    public function saveResultFile()
    {
        $this->validate([
            'resultFile' => 'required|file|mimes:pdf|max:5120',
        ], [
            'resultFile.required' => 'File surat wajib diupload.',
            'resultFile.mimes' => 'File surat harus berformat PDF.',
            'resultFile.max' => 'Ukuran file maksimal 5MB.',
        ]);

        $request = LetterRequest::findOrFail($this->processingRequestId);
        $resultFilePath = $this->resultFile->store('letter-results', 'public');

        $request->update([
            'result_file' => $resultFilePath,
            'status' => LetterRequest::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        session()->flash('success', 'File surat berhasil diupload.');
        $this->closeProcessModal();
    }

    public function downloadAttachment(int $id, int $index = 0)
    {
        $request = LetterRequest::findOrFail($id);

        $files = $request->attachmentFiles();
        if (empty($files)) {
            session()->flash('error', 'File lampiran tidak ditemukan.');
            return;
        }

        $file = $files[$index] ?? $files[0];
        if (!Storage::disk('public')->exists($file)) {
            session()->flash('error', 'File lampiran tidak ditemukan di penyimpanan.');
            return;
        }

        return Storage::disk('public')->download($file);
    }

    public function with(): array
    {
        $query = LetterRequest::query()
            ->with(['student.classroom', 'student.department', 'requester', 'processor']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('request_number', 'like', '%' . $this->search . '%')
                  ->orWhere('purpose', 'like', '%' . $this->search . '%')
                  ->orWhereHas('student', fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('nis', 'like', '%' . $this->search . '%'));
            });
        }

        if ($this->filterType) {
            $query->where('letter_type', $this->filterType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $stats = [
            'total' => LetterRequest::count(),
            'pending' => LetterRequest::where('status', 'pending')->count(),
            'processing' => LetterRequest::where('status', 'processing')->count(),
            'completed' => LetterRequest::where('status', 'completed')->count(),
            'rejected' => LetterRequest::where('status', 'rejected')->count(),
        ];

        return [
            'requests' => $query->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'processing' THEN 1 WHEN 'completed' THEN 2 WHEN 'rejected' THEN 3 END")->orderBy('created_at', 'desc')->paginate(15),
            'letterTypes' => LetterRequest::TYPES,
            'statuses' => LetterRequest::STATUSES,
            'stats' => $stats,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <flux:heading size="xl">{{ __('Kelola Permohonan Surat Siswa') }}</flux:heading>
        <flux:subheading>{{ __('Proses permohonan surat yang diajukan oleh siswa') }}</flux:subheading>
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

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <flux:card>
            <div class="text-center p-1">
                <flux:text class="text-sm text-zinc-500">{{ __('Total') }}</flux:text>
                <flux:heading size="2xl">{{ $stats['total'] }}</flux:heading>
            </div>
        </flux:card>
        <flux:card class="border-yellow-200 dark:border-yellow-800">
            <div class="text-center p-1">
                <flux:text class="text-sm text-yellow-600">{{ __('Menunggu') }}</flux:text>
                <flux:heading size="2xl" class="text-yellow-600">{{ $stats['pending'] }}</flux:heading>
            </div>
        </flux:card>
        <flux:card class="border-blue-200 dark:border-blue-800">
            <div class="text-center p-1">
                <flux:text class="text-sm text-blue-600">{{ __('Diproses') }}</flux:text>
                <flux:heading size="2xl" class="text-blue-600">{{ $stats['processing'] }}</flux:heading>
            </div>
        </flux:card>
        <flux:card class="border-green-200 dark:border-green-800">
            <div class="text-center p-1">
                <flux:text class="text-sm text-green-600">{{ __('Selesai') }}</flux:text>
                <flux:heading size="2xl" class="text-green-600">{{ $stats['completed'] }}</flux:heading>
            </div>
        </flux:card>
        <flux:card class="border-red-200 dark:border-red-800">
            <div class="text-center p-1">
                <flux:text class="text-sm text-red-600">{{ __('Ditolak') }}</flux:text>
                <flux:heading size="2xl" class="text-red-600">{{ $stats['rejected'] }}</flux:heading>
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card>
        <div class="grid gap-4 sm:grid-cols-3 p-1">
            <flux:input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nomor, nama siswa, NIS..." icon="magnifying-glass" />
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

    <!-- Table -->
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('No. Permohonan') }}</flux:table.column>
                <flux:table.column>{{ __('Siswa') }}</flux:table.column>
                <flux:table.column>{{ __('Jenis Surat') }}</flux:table.column>
                <flux:table.column>{{ __('Keperluan') }}</flux:table.column>
                <flux:table.column>{{ __('Tanggal') }}</flux:table.column>
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
                            @if ($request->student)
                                <div>
                                    <flux:text class="font-medium">{{ $request->student->name }}</flux:text>
                                    <flux:text size="sm" class="text-zinc-500">{{ $request->student->nis }} &bull; {{ $request->student->classroom?->name ?? '-' }}</flux:text>
                                </div>
                            @else
                                <flux:text class="text-zinc-400">-</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="{{ \App\Models\LetterRequest::TYPE_COLORS[$request->letter_type] ?? 'zinc' }}">
                                {{ \App\Models\LetterRequest::TYPES[$request->letter_type] ?? $request->letter_type }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate text-sm">
                            {{ $request->purpose ?? '-' }}
                            @php($attachmentFiles = $request->attachmentFiles())
                            @if (!empty($attachmentFiles))
                                <div class="mt-1">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($attachmentFiles as $index => $file)
                                            <flux:button size="sm" variant="ghost" icon="paper-clip" wire:click="downloadAttachment({{ $request->id }}, {{ $index }})">
                                                {{ __('Lampiran') }} {{ $index + 1 }}
                                            </flux:button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">
                            {{ $request->created_at->translatedFormat('d M Y') }}
                            <br>
                            <span class="text-xs">{{ $request->created_at->translatedFormat('H:i') }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="{{ \App\Models\LetterRequest::STATUS_COLORS[$request->status] ?? 'zinc' }}">
                                {{ \App\Models\LetterRequest::STATUSES[$request->status] ?? $request->status }}
                            </flux:badge>
                            @if ($request->processor)
                                <flux:text size="sm" class="text-zinc-400 mt-1">
                                    oleh: {{ $request->processor->name }}
                                </flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button icon="ellipsis-horizontal" size="sm" variant="ghost" />
                                <flux:menu>
                                    @if ($request->status === 'pending')
                                        <flux:menu.item icon="arrow-path" wire:click="openProcessModal({{ $request->id }}, 'process')">
                                            {{ __('Proses') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="check-circle" wire:click="openProcessModal({{ $request->id }}, 'approve')">
                                            {{ __('Selesaikan') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="x-circle" variant="danger" wire:click="openProcessModal({{ $request->id }}, 'reject')">
                                            {{ __('Tolak') }}
                                        </flux:menu.item>
                                    @elseif ($request->status === 'processing')
                                        <flux:menu.item icon="check-circle" wire:click="openProcessModal({{ $request->id }}, 'approve')">
                                            {{ __('Selesaikan') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="arrow-up-tray" wire:click="uploadResult({{ $request->id }})">
                                            {{ __('Upload File Surat') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="x-circle" variant="danger" wire:click="openProcessModal({{ $request->id }}, 'reject')">
                                            {{ __('Tolak') }}
                                        </flux:menu.item>
                                    @elseif ($request->status === 'completed' && !$request->result_file)
                                        <flux:menu.item icon="arrow-up-tray" wire:click="uploadResult({{ $request->id }})">
                                            {{ __('Upload File Surat') }}
                                        </flux:menu.item>
                                    @endif
                                    @if ($request->admin_notes)
                                        <flux:menu.item icon="chat-bubble-left-ellipsis" disabled>
                                            {{ __('Catatan: ') }} {{ Str::limit($request->admin_notes, 30) }}
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-12">
                            <flux:icon name="document-text" class="mx-auto mb-4 size-12 text-zinc-400" />
                            <p class="text-zinc-500">{{ __('Belum ada permohonan surat dari siswa.') }}</p>
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

    <!-- Process Modal -->
    <flux:modal wire:model="showProcessModal" class="max-w-lg">
        <div class="space-y-4">
            @if ($processAction === 'approve')
                <flux:heading size="lg">{{ __('Selesaikan Permohonan') }}</flux:heading>
                <flux:text>{{ __('Tandai permohonan ini sebagai selesai. Anda dapat mengupload file surat jadi.') }}</flux:text>
            @elseif ($processAction === 'process')
                <flux:heading size="lg">{{ __('Proses Permohonan') }}</flux:heading>
                <flux:text>{{ __('Tandai permohonan ini sebagai sedang diproses.') }}</flux:text>
            @elseif ($processAction === 'reject')
                <flux:heading size="lg">{{ __('Tolak Permohonan') }}</flux:heading>
                <flux:text>{{ __('Berikan alasan penolakan permohonan ini.') }}</flux:text>
            @elseif ($processAction === 'upload')
                <flux:heading size="lg">{{ __('Upload File Surat') }}</flux:heading>
                <flux:text>{{ __('Upload file surat hasil yang sudah selesai (format PDF).') }}</flux:text>
            @endif

            @if ($processAction === 'upload')
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('File Surat (PDF)') }} *</label>
                    <input type="file" wire:model="resultFile" accept=".pdf" class="block w-full text-sm text-zinc-900 border border-zinc-300 rounded-lg cursor-pointer bg-white dark:text-zinc-400 dark:bg-zinc-800 dark:border-zinc-600" />
                    @error('resultFile') <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="closeProcessModal">{{ __('Batal') }}</flux:button>
                    <flux:button variant="primary" wire:click="saveResultFile">{{ __('Upload') }}</flux:button>
                </div>
            @else
                <flux:textarea
                    wire:model="adminNotes"
                    label="{{ $processAction === 'reject' ? __('Alasan Penolakan') . ' *' : __('Catatan (Opsional)') }}"
                    placeholder="{{ $processAction === 'reject' ? __('Jelaskan alasan penolakan...') : __('Catatan dari admin...') }}"
                    rows="3"
                />
                @error('adminNotes') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror

                @if ($processAction === 'approve')
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('Upload File Surat (PDF, Opsional)') }}</label>
                        <input type="file" wire:model="resultFile" accept=".pdf" class="block w-full text-sm text-zinc-900 border border-zinc-300 rounded-lg cursor-pointer bg-white dark:text-zinc-400 dark:bg-zinc-800 dark:border-zinc-600" />
                        @error('resultFile') <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text> @enderror
                    </div>
                @endif

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="closeProcessModal">{{ __('Batal') }}</flux:button>
                    @if ($processAction === 'reject')
                        <flux:button variant="danger" wire:click="processRequest">{{ __('Tolak Permohonan') }}</flux:button>
                    @elseif ($processAction === 'approve')
                        <flux:button variant="primary" wire:click="processRequest">{{ __('Selesaikan') }}</flux:button>
                    @else
                        <flux:button variant="primary" wire:click="processRequest">{{ __('Proses') }}</flux:button>
                    @endif
                </div>
            @endif
        </div>
    </flux:modal>
</div>
