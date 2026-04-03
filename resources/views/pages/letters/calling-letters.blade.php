<?php

use App\Models\CallingLetter;
use App\Models\CallingLetterStudent;
use App\Models\AcademicYear;
use App\Models\Student;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Surat Panggilan')] class extends Component {
    use WithPagination;

    public $search = '';
    public $type = '';
    public $status = '';
    public $academic_year_id = '';

    // Form fields
    public $showForm = false;
    public $showDetailModal = false;
    public $editingId = null;
    public $letter_number = '';
    public $form_type = 'SP1';
    public $subject = '';
    public $content = '';
    public $letter_date = '';
    public $meeting_date = '';
    public $meeting_time = '';
    public $meeting_place = '';
    public $form_academic_year_id = '';
    public $form_status = 'draft';
    public $notes = '';
    public $selectedStudents = [];

    // Detail view
    public $selectedLetter = null;

    protected function rules()
    {
        return [
            'form_type' => 'required|in:SP1,SP2,SP3',
            'subject' => 'required|string|max:255',
            'content' => 'nullable|string|max:5000',
            'letter_date' => 'required|date',
            'meeting_date' => 'required|date',
            'meeting_time' => 'required',
            'meeting_place' => 'required|string|max:255',
            'form_academic_year_id' => 'required|exists:academic_years,id',
            'form_status' => 'required|in:draft,sent,completed',
            'notes' => 'nullable|string|max:1000',
            'selectedStudents' => 'required|array|min:1',
        ];
    }

    public function mount()
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
            $this->form_academic_year_id = $activeYear->id;
        }
        $this->letter_date = now()->format('Y-m-d');
        $this->meeting_date = now()->addDays(3)->format('Y-m-d');
    }

    public function openForm($id = null)
    {
        abort_unless(auth()->user()?->can($id ? 'calling-letters.update' : 'calling-letters.create'), 403);

        $this->resetForm();
        $this->showForm = true;

        if ($id) {
            $letter = CallingLetter::with('students')->findOrFail($id);
            $this->editingId = $id;
            $this->letter_number = $letter->letter_number;
            $this->form_type = $letter->type;
            $this->subject = $letter->subject;
            $this->content = $letter->content;
            $this->letter_date = $letter->letter_date->format('Y-m-d');
            $this->meeting_date = $letter->meeting_date->format('Y-m-d');
            $this->meeting_time = $letter->meeting_time?->format('H:i');
            $this->meeting_place = $letter->meeting_place;
            $this->form_academic_year_id = $letter->academic_year_id;
            $this->form_status = $letter->status;
            $this->notes = $letter->notes;
            $this->selectedStudents = $letter->students->pluck('id')->toArray();
        } else {
            $this->letter_number = CallingLetter::generateLetterNumber('SP1');
        }
    }

    public function updatedFormType($value)
    {
        if (!$this->editingId) {
            $this->letter_number = CallingLetter::generateLetterNumber($value);
            $this->subject = "Surat Panggilan Orang Tua/Wali ({$value})";
        }
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->letter_number = CallingLetter::generateLetterNumber('SP1');
        $this->form_type = 'SP1';
        $this->subject = '';
        $this->content = '';
        $this->letter_date = now()->format('Y-m-d');
        $this->meeting_date = now()->addDays(3)->format('Y-m-d');
        $this->meeting_time = '';
        $this->meeting_place = '';
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->form_academic_year_id = $activeYear?->id ?? '';
        $this->form_status = 'draft';
        $this->notes = '';
        $this->selectedStudents = [];
        $this->resetValidation();
    }

    public function save()
    {
        abort_unless(auth()->user()?->can($this->editingId ? 'calling-letters.update' : 'calling-letters.create'), 403);

        $this->validate();

        $data = [
            'letter_number' => $this->letter_number,
            'type' => $this->form_type,
            'subject' => $this->subject,
            'content' => $this->content,
            'letter_date' => $this->letter_date,
            'meeting_date' => $this->meeting_date,
            'meeting_time' => $this->meeting_time,
            'meeting_place' => $this->meeting_place,
            'academic_year_id' => $this->form_academic_year_id,
            'status' => $this->form_status,
            'notes' => $this->notes,
            'created_by' => auth()->id(),
        ];

        if ($this->editingId) {
            $letter = CallingLetter::findOrFail($this->editingId);
            $letter->update($data);
            
            // Sync students
            $letter->callingLetterStudents()->delete();
            foreach ($this->selectedStudents as $studentId) {
                $letter->callingLetterStudents()->create([
                    'student_id' => $studentId,
                    'attendance_status' => 'pending',
                ]);
            }
            
            session()->flash('success', 'Surat panggilan berhasil diperbarui.');
        } else {
            $letter = CallingLetter::create($data);
            
            foreach ($this->selectedStudents as $studentId) {
                $letter->callingLetterStudents()->create([
                    'student_id' => $studentId,
                    'attendance_status' => 'pending',
                ]);
            }
            
            session()->flash('success', 'Surat panggilan berhasil ditambahkan.');
        }

        $this->closeForm();
    }

    public function delete($id)
    {
        abort_unless(auth()->user()?->can('calling-letters.delete'), 403);

        $letter = CallingLetter::findOrFail($id);
        $letter->callingLetterStudents()->delete();
        $letter->delete();
        session()->flash('success', 'Surat panggilan berhasil dihapus.');
    }

    public function openDetailModal($id)
    {
        $this->selectedLetter = CallingLetter::with(['callingLetterStudents.student', 'academicYear', 'creator'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedLetter = null;
    }

    public function updateAttendanceStatus($callingLetterStudentId, $status)
    {
        abort_unless(auth()->user()?->can('calling-letters.update'), 403);

        CallingLetterStudent::where('id', $callingLetterStudentId)->update([
            'attendance_status' => $status,
        ]);
        
        $this->selectedLetter = CallingLetter::with(['callingLetterStudents.student', 'academicYear', 'creator'])->findOrFail($this->selectedLetter->id);
        session()->flash('success', 'Status kehadiran berhasil diperbarui.');
    }

    public function markAsSent($id)
    {
        abort_unless(auth()->user()?->can('calling-letters.update'), 403);

        CallingLetter::where('id', $id)->update(['status' => 'sent']);
        session()->flash('success', 'Status surat berhasil diubah menjadi Terkirim.');
    }

    public function markAsCompleted($id)
    {
        abort_unless(auth()->user()?->can('calling-letters.update'), 403);

        CallingLetter::where('id', $id)->update(['status' => 'completed']);
        session()->flash('success', 'Status surat berhasil diubah menjadi Selesai.');
    }

    public function with(): array
    {
        $query = CallingLetter::query()->with(['callingLetterStudents.student', 'academicYear', 'creator']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('letter_number', 'like', '%' . $this->search . '%')
                    ->orWhere('subject', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->academic_year_id) {
            $query->where('academic_year_id', $this->academic_year_id);
        }

        return [
            'letters' => $query->latest()->paginate(15),
            'types' => CallingLetter::TYPES,
            'statuses' => CallingLetter::STATUSES,
            'academicYears' => AcademicYear::orderBy('start_date', 'desc')->get(),
            'students' => Student::where('status', 'aktif')->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">Surat Panggilan Orang Tua</flux:heading>
                <flux:subheading>Kelola surat panggilan SP1, SP2, dan SP3</flux:subheading>
            </div>
            @can('calling-letters.create')
            <flux:button icon="plus" wire:click="openForm">
                Buat Surat Panggilan
            </flux:button>
            @endcan
        </div>

        <!-- Alert Messages -->
        @if (session()->has('success'))
        <flux:callout variant="success" icon="check-circle" heading="Berhasil" dismissible>
            {{ session('success') }}
        </flux:callout>
        @endif

        @if (session()->has('error'))
        <flux:callout variant="danger" icon="x-circle" heading="Gagal" dismissible>
            {{ session('error') }}
        </flux:callout>
        @endif

        <!-- Filters -->
        <flux:card>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari nomor surat atau perihal..."
                    icon="magnifying-glass"
                />
                <flux:select wire:model.live="type">
                    <flux:select.option value="">Semua Jenis</flux:select.option>
                    @foreach ($types as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="status">
                    <flux:select.option value="">Semua Status</flux:select.option>
                    @foreach ($statuses as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="academic_year_id">
                    <flux:select.option value="">Semua Tahun</flux:select.option>
                    @foreach ($academicYears as $year)
                        <flux:select.option value="{{ $year->id }}">{{ $year->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        <!-- Data Table -->
        <flux:card>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nomor Surat</flux:table.column>
                    <flux:table.column>Jenis</flux:table.column>
                    <flux:table.column>Perihal</flux:table.column>
                    <flux:table.column>Tanggal Panggilan</flux:table.column>
                    <flux:table.column>Siswa</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($letters as $letter)
                        <flux:table.row>
                            <flux:table.cell>
                                <span class="font-mono text-sm">{{ $letter->letter_number }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $typeColors = ['SP1' => 'yellow', 'SP2' => 'orange', 'SP3' => 'red'];
                                @endphp
                                <flux:badge color="{{ $typeColors[$letter->type] ?? 'zinc' }}" size="sm">
                                    {{ $letter->type }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ Str::limit($letter->subject, 40) }}</flux:table.cell>
                            <flux:table.cell>
                                <div>
                                    <div>{{ $letter->meeting_date->format('d/m/Y') }}</div>
                                    <div class="text-xs text-zinc-500">{{ $letter->meeting_time?->format('H:i') }}</div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" variant="outline">
                                    {{ $letter->callingLetterStudents->count() }} siswa
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $statusColors = ['draft' => 'zinc', 'sent' => 'blue', 'completed' => 'green'];
                                @endphp
                                <flux:badge color="{{ $statusColors[$letter->status] ?? 'zinc' }}" size="sm">
                                    {{ $statuses[$letter->status] ?? $letter->status }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button size="xs" variant="ghost" icon="eye" wire:click="openDetailModal({{ $letter->id }})" title="Lihat Detail" />
                                    @can('calling-letters.update')
                                    @if ($letter->status === 'draft')
                                    <flux:button size="xs" variant="ghost" icon="pencil" wire:click="openForm({{ $letter->id }})" />
                                    <flux:button size="xs" variant="ghost" icon="paper-airplane" wire:click="markAsSent({{ $letter->id }})" title="Tandai Terkirim" />
                                    @elseif ($letter->status === 'sent')
                                    <flux:button size="xs" variant="ghost" icon="check" wire:click="markAsCompleted({{ $letter->id }})" title="Tandai Selesai" />
                                    @endif
                                    @endcan
                                    @can('calling-letters.delete')
                                    @if ($letter->status === 'draft')
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="delete({{ $letter->id }})" wire:confirm="Yakin ingin menghapus surat panggilan ini?" />
                                    @endif
                                    @endcan
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-8">
                                <div class="text-zinc-500">Belum ada surat panggilan</div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $letters->links() }}
            </div>
        </flux:card>

        <!-- Form Modal -->
        <flux:modal wire:model="showForm" class="w-full max-w-3xl">
            <div class="space-y-6">
                <flux:heading size="lg">{{ $editingId ? 'Edit' : 'Buat' }} Surat Panggilan</flux:heading>

                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:input 
                            wire:model="letter_number" 
                            label="Nomor Surat" 
                            readonly
                        />
                        <flux:select wire:model.live="form_type" label="Jenis Surat" required>
                            @foreach ($types as $key => $label)
                                <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <flux:input 
                        wire:model="subject" 
                        label="Perihal" 
                        placeholder="Perihal surat panggilan..."
                        required
                    />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:input 
                            wire:model="letter_date" 
                            label="Tanggal Surat" 
                            type="date"
                            required
                        />
                        <flux:select wire:model="form_academic_year_id" label="Tahun Pelajaran" required>
                            <flux:select.option value="">Pilih Tahun</flux:select.option>
                            @foreach ($academicYears as $year)
                                <flux:select.option value="{{ $year->id }}">{{ $year->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <flux:input 
                            wire:model="meeting_date" 
                            label="Tanggal Pertemuan" 
                            type="date"
                            required
                        />
                        <flux:input 
                            wire:model="meeting_time" 
                            label="Waktu Pertemuan" 
                            type="time"
                            required
                        />
                        <flux:input 
                            wire:model="meeting_place" 
                            label="Tempat Pertemuan" 
                            placeholder="Ruang Kepala Sekolah"
                            required
                        />
                    </div>

                    <flux:select wire:model="selectedStudents" label="Siswa yang Dipanggil" multiple required>
                        @foreach ($students as $student)
                            <flux:select.option value="{{ $student->id }}">{{ $student->name }} ({{ $student->nis }})</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:textarea 
                        wire:model="content" 
                        label="Isi Surat" 
                        rows="4"
                        placeholder="Isi/konten surat panggilan..."
                    />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:select wire:model="form_status" label="Status" required>
                            @foreach ($statuses as $key => $label)
                                <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:textarea 
                            wire:model="notes" 
                            label="Catatan" 
                            rows="2"
                            placeholder="Catatan tambahan..."
                        />
                    </div>

                    <div class="flex justify-end gap-3">
                        <flux:button type="button" variant="ghost" wire:click="closeForm">Batal</flux:button>
                        <flux:button type="submit" variant="primary">Simpan</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        <!-- Detail Modal -->
        <flux:modal wire:model="showDetailModal" class="w-full max-w-3xl">
            @if ($selectedLetter)
            <div class="space-y-6">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">Detail Surat Panggilan</flux:heading>
                        <flux:subheading>{{ $selectedLetter->letter_number }}</flux:subheading>
                    </div>
                    @php
                        $typeColors = ['SP1' => 'yellow', 'SP2' => 'orange', 'SP3' => 'red'];
                    @endphp
                    <flux:badge color="{{ $typeColors[$selectedLetter->type] ?? 'zinc' }}" size="lg">
                        {{ $selectedLetter->type }}
                    </flux:badge>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-zinc-500">Perihal</div>
                        <div class="font-medium">{{ $selectedLetter->subject }}</div>
                    </div>
                    <div>
                        <div class="text-zinc-500">Status</div>
                        @php
                            $statusColors = ['draft' => 'zinc', 'sent' => 'blue', 'completed' => 'green'];
                        @endphp
                        <flux:badge color="{{ $statusColors[$selectedLetter->status] ?? 'zinc' }}">
                            {{ $statuses[$selectedLetter->status] ?? $selectedLetter->status }}
                        </flux:badge>
                    </div>
                    <div>
                        <div class="text-zinc-500">Tanggal Pertemuan</div>
                        <div class="font-medium">{{ $selectedLetter->meeting_date->format('d F Y') }}</div>
                    </div>
                    <div>
                        <div class="text-zinc-500">Waktu & Tempat</div>
                        <div class="font-medium">{{ $selectedLetter->meeting_time?->format('H:i') }} - {{ $selectedLetter->meeting_place }}</div>
                    </div>
                </div>

                @if ($selectedLetter->content)
                <div>
                    <div class="text-zinc-500 text-sm mb-1">Isi Surat</div>
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg text-sm">
                        {!! nl2br(e($selectedLetter->content)) !!}
                    </div>
                </div>
                @endif

                <div>
                    <flux:heading size="sm" class="mb-3">Daftar Siswa yang Dipanggil</flux:heading>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>NIS</flux:table.column>
                            <flux:table.column>Nama Siswa</flux:table.column>
                            <flux:table.column>Status Kehadiran</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($selectedLetter->callingLetterStudents as $cls)
                                <flux:table.row>
                                    <flux:table.cell>{{ $cls->student?->nis }}</flux:table.cell>
                                    <flux:table.cell>{{ $cls->student?->name }}</flux:table.cell>
                                    <flux:table.cell>
                                        @php
                                            $attColors = ['pending' => 'zinc', 'attended' => 'green', 'not_attended' => 'red'];
                                            $attLabels = ['pending' => 'Menunggu', 'attended' => 'Hadir', 'not_attended' => 'Tidak Hadir'];
                                        @endphp
                                        <flux:badge color="{{ $attColors[$cls->attendance_status] ?? 'zinc' }}" size="sm">
                                            {{ $attLabels[$cls->attendance_status] ?? $cls->attendance_status }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if ($selectedLetter->status !== 'draft')
                                        <div class="flex gap-1">
                                            <flux:button size="xs" variant="ghost" wire:click="updateAttendanceStatus({{ $cls->id }}, 'attended')">
                                                Hadir
                                            </flux:button>
                                            <flux:button size="xs" variant="ghost" wire:click="updateAttendanceStatus({{ $cls->id }}, 'not_attended')">
                                                Tidak Hadir
                                            </flux:button>
                                        </div>
                                        @else
                                        <span class="text-zinc-400 text-xs">Kirim surat terlebih dahulu</span>
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                <div class="flex justify-end">
                    <flux:button variant="ghost" wire:click="closeDetailModal">Tutup</flux:button>
                </div>
            </div>
            @endif
        </flux:modal>
    </div>
</div>
