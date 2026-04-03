<?php

use App\Models\Letter;
use App\Models\Student;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Form Surat')] class extends Component {
    public ?Letter $letter = null;

    public $type = 'summons';
    public $student_id = '';
    public $number = '';
    public $subject = '';
    public $content = '';
    public $date = '';
    public $notes = '';

    protected function rules()
    {
        return [
            'type' => 'required|in:summons,warning,certificate,recommendation,transfer,graduation,other',
            'student_id' => 'nullable|exists:students,id',
            'number' => 'nullable|string|max:100',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function mount(?Letter $letter = null)
    {
        $this->letter = $letter;
        $this->date = now()->format('Y-m-d');

        if ($letter && $letter->exists) {
            $this->type = $letter->type;
            $this->student_id = $letter->student_id;
            $this->number = $letter->number;
            $this->subject = $letter->subject;
            $this->content = $letter->content;
            $this->date = $letter->date;
            $this->notes = $letter->notes;
        } else {
            // Generate letter number
            $this->generateNumber();
        }
    }

    public function generateNumber()
    {
        $year = now()->year;
        $month = str_pad(now()->month, 2, '0', STR_PAD_LEFT);
        $count = Letter::whereYear('created_at', $year)->count() + 1;
        $this->number = sprintf('%03d/SMK-YDK/%s/%d', $count, $month, $year);
    }

    public function save($status = 'draft')
    {
        $this->validate();

        $data = [
            'type' => $this->type,
            'student_id' => $this->student_id ?: null,
            'number' => $this->number,
            'subject' => $this->subject,
            'content' => $this->content,
            'date' => $this->date,
            'notes' => $this->notes,
            'status' => $status,
            'created_by' => auth()->id(),
        ];

        if ($this->letter && $this->letter->exists) {
            $this->letter->update($data);
            session()->flash('success', 'Surat berhasil diperbarui.');
        } else {
            Letter::create($data);
            session()->flash('success', 'Surat berhasil dibuat.');
        }

        return redirect()->route('letters.index');
    }

    public function saveAsDraft()
    {
        $this->save('draft');
    }

    public function saveAndSubmit()
    {
        $this->save('pending');
    }

    public function with(): array
    {
        return [
            'students' => Student::where('status', 'aktif')
                ->orderBy('name')
                ->get(),
            'letterTypes' => [
                'summons' => 'Surat Panggilan',
                'warning' => 'Surat Peringatan',
                'certificate' => 'Surat Keterangan',
                'recommendation' => 'Surat Rekomendasi',
                'transfer' => 'Surat Pindah',
                'graduation' => 'Surat Kelulusan',
                'other' => 'Lainnya',
            ],
            'isEditing' => $this->letter && $this->letter->exists,
        ];
    }
}; ?>

<div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <flux:button icon="arrow-left" variant="ghost" :href="route('letters.index')" wire:navigate />
            <div>
                <flux:heading size="xl">{{ $isEditing ? 'Edit Surat' : 'Buat Surat Baru' }}</flux:heading>
                <flux:subheading>{{ $isEditing ? 'Perbarui data surat' : 'Buat surat resmi sekolah' }}</flux:subheading>
            </div>
        </div>

        <form class="space-y-6">
            <flux:card>
                <flux:heading size="sm" class="mb-4">Informasi Surat</flux:heading>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model="type" label="Jenis Surat" required>
                        @foreach ($letterTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="number" label="Nomor Surat" placeholder="001/SMK-YDK/01/2026" />
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <flux:input type="date" wire:model="date" label="Tanggal Surat" required />

                    <flux:select wire:model="student_id" label="Siswa Terkait (Opsional)">
                        <option value="">Pilih Siswa (jika ada)</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} - {{ $student->nis }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="mt-4">
                    <flux:input wire:model="subject" label="Perihal / Subjek" placeholder="Perihal surat" required />
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="sm" class="mb-4">Isi Surat</flux:heading>

                <flux:textarea wire:model="content" label="Isi Surat" rows="10" placeholder="Tulis isi surat di sini..." required />

                <div class="mt-4">
                    <flux:textarea wire:model="notes" label="Catatan Internal (Opsional)" rows="3" placeholder="Catatan untuk internal, tidak akan tercetak di surat" />
                </div>
            </flux:card>

            <flux:card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <flux:icon.information-circle class="mr-1 inline size-4" />
                        Surat yang disimpan sebagai draft dapat diedit kapan saja. Surat yang diajukan akan menunggu persetujuan Kepala Sekolah.
                    </p>
                    <div class="flex gap-3">
                        <flux:button type="button" variant="ghost" :href="route('letters.index')" wire:navigate>
                            Batal
                        </flux:button>
                        <flux:button type="button" variant="outline" wire:click="saveAsDraft">
                            Simpan Draft
                        </flux:button>
                        <flux:button type="button" wire:click="saveAndSubmit">
                            Simpan & Ajukan
                        </flux:button>
                    </div>
                </div>
            </flux:card>
        </form>
    </div>
</div>
