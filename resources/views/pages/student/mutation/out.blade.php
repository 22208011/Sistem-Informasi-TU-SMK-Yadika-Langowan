<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentMutation;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Mutasi Keluar Siswa')] class extends Component {
    
    public ?int $student_id = null;
    public string $exit_type = 'keluar'; // keluar, lulus, do
    public string $mutation_date = '';
    public string $effective_date = '';
    public string $destination_school = '';
    public string $reason = '';
    public string $document_number = '';
    public string $notes = '';
    public ?int $academic_year_id = null;
    
    public string $searchStudent = '';

    public function mount(): void
    {
        $this->mutation_date = now()->format('Y-m-d');
        $this->effective_date = now()->format('Y-m-d');
        
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->academic_year_id = $activeYear?->id;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'exit_type' => 'required|in:keluar,lulus,do',
            'mutation_date' => 'required|date',
            'effective_date' => 'required|date',
            'destination_school' => 'nullable|string|max:255',
            'reason' => 'required|string|max:255',
            'document_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'academic_year_id' => 'required|exists:academic_years,id',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Siswa wajib dipilih.',
            'reason.required' => 'Alasan mutasi wajib diisi.',
            'mutation_date.required' => 'Tanggal mutasi wajib diisi.',
        ];
    }

    #[Computed]
    public function students()
    {
        return Student::query()
            ->where('status', 'aktif')
            ->with(['classroom', 'department'])
            ->when($this->searchStudent, fn($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->searchStudent}%")
                    ->orWhere('nis', 'like', "%{$this->searchStudent}%");
            }))
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function selectedStudent()
    {
        if ($this->student_id) {
            return Student::with(['classroom', 'department', 'guardians'])->find($this->student_id);
        }
        return null;
    }

    #[Computed]
    public function academicYears()
    {
        return AcademicYear::orderByDesc('start_date')->get();
    }

    #[Computed]
    public function exitReasons()
    {
        return [
            'keluar' => [
                'pindah_sekolah' => 'Pindah ke Sekolah Lain',
                'permintaan_ortu' => 'Permintaan Orang Tua',
                'alasan_kesehatan' => 'Alasan Kesehatan',
                'alasan_ekonomi' => 'Alasan Ekonomi',
                'lainnya' => 'Alasan Lainnya',
            ],
            'lulus' => [
                'lulus_reguler' => 'Lulus Reguler',
            ],
            'do' => [
                'pelanggaran_berat' => 'Pelanggaran Berat',
                'tidak_naik_kelas' => 'Tidak Naik Kelas Berturut-turut',
                'mangkir_lama' => 'Mangkir Terlalu Lama',
                'lainnya' => 'Alasan Lainnya',
            ],
        ];
    }

    public function selectStudent(int $id): void
    {
        $this->student_id = $id;
    }

    public function clearStudent(): void
    {
        $this->student_id = null;
        $this->searchStudent = '';
    }

    public function save(): void
    {
        $this->validate();

        $student = Student::findOrFail($this->student_id);

        DB::transaction(function () use ($student) {
            // Determine mutation type
            $type = match($this->exit_type) {
                'keluar' => StudentMutation::TYPE_KELUAR,
                'lulus' => StudentMutation::TYPE_LULUS,
                'do' => StudentMutation::TYPE_DO,
                default => StudentMutation::TYPE_KELUAR,
            };

            // Create mutation record
            StudentMutation::create([
                'student_id' => $student->id,
                'type' => $type,
                'mutation_date' => $this->mutation_date,
                'effective_date' => $this->effective_date,
                'reason' => $this->reason,
                'destination_school' => $this->destination_school ?: null,
                'previous_classroom_id' => $student->classroom_id,
                'document_number' => $this->document_number ?: null,
                'notes' => $this->notes ?: null,
                'status' => StudentMutation::STATUS_PENDING,
                'academic_year_id' => $this->academic_year_id,
            ]);
        });

        session()->flash('success', 'Mutasi keluar berhasil diajukan dan menunggu persetujuan.');
        $this->redirect(route('students.mutations.index'), navigate: true);
    }
}; ?>

<div>
    <!-- Page Header -->
    <x-page-header>
        <x-slot:icon>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </x-slot:icon>
        <x-slot:title>{{ __('Mutasi Keluar Siswa') }}</x-slot:title>
        <x-slot:subtitle>{{ __('Proses siswa yang akan keluar, pindah sekolah, lulus, atau dikeluarkan.') }}</x-slot:subtitle>
        <x-slot:actions>
            <flux:button :href="route('students.mutations.index')" variant="ghost" icon="arrow-left" wire:navigate>
                {{ __('Kembali') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="save" class="space-y-6">
        <!-- Select Student -->
        <x-card>
            <x-slot:header>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Pilih Siswa</h3>
            </x-slot:header>
            
            @if(!$this->selectedStudent)
                <div class="space-y-4">
                    <flux:input wire:model.live.debounce.300ms="searchStudent" placeholder="Cari siswa berdasarkan nama atau NIS..." icon="magnifying-glass" />
                    
                    @if(count($this->students) > 0)
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden max-h-64 overflow-y-auto">
                            @foreach($this->students as $student)
                                <button type="button" wire:click="selectStudent({{ $student->id }})" class="w-full px-4 py-3 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800 border-b border-zinc-100 dark:border-zinc-800 last:border-b-0 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-zinc-900 dark:text-white">{{ $student->name }}</div>
                                            <div class="text-sm text-zinc-500">NIS: {{ $student->nis }} • {{ $student->classroom?->name }} • {{ $student->department?->name }}</div>
                                        </div>
                                        <svg class="size-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @elseif($searchStudent)
                        <div class="text-center py-8 text-zinc-500">
                            <svg class="size-12 mx-auto text-zinc-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p>Tidak ditemukan siswa dengan kata kunci "{{ $searchStudent }}"</p>
                        </div>
                    @else
                        <div class="text-center py-8 text-zinc-500">
                            <p>Ketik nama atau NIS siswa untuk mencari</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold">
                            {{ strtoupper(substr($this->selectedStudent->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="font-semibold text-zinc-900 dark:text-white">{{ $this->selectedStudent->name }}</div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                NIS: {{ $this->selectedStudent->nis }} • {{ $this->selectedStudent->classroom?->name }} • {{ $this->selectedStudent->department?->name }}
                            </div>
                        </div>
                    </div>
                    <flux:button type="button" size="sm" variant="ghost" icon="x-mark" wire:click="clearStudent">
                        Ganti
                    </flux:button>
                </div>
            @endif
        </x-card>

        @if($this->selectedStudent)
            <!-- Exit Type -->
            <x-card>
                <x-slot:header>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Jenis Mutasi Keluar</h3>
                </x-slot:header>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="relative flex items-center p-4 border rounded-xl cursor-pointer transition-all {{ $exit_type === 'keluar' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                        <input type="radio" wire:model.live="exit_type" value="keluar" class="sr-only">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg {{ $exit_type === 'keluar' ? 'bg-blue-100 text-blue-600' : 'bg-zinc-100 text-zinc-600' }}">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">Pindah Sekolah</div>
                                <div class="text-xs text-zinc-500">Siswa pindah ke sekolah lain</div>
                            </div>
                        </div>
                    </label>

                    <label class="relative flex items-center p-4 border rounded-xl cursor-pointer transition-all {{ $exit_type === 'lulus' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                        <input type="radio" wire:model.live="exit_type" value="lulus" class="sr-only">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg {{ $exit_type === 'lulus' ? 'bg-green-100 text-green-600' : 'bg-zinc-100 text-zinc-600' }}">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M12 14l9-5-9-5-9 5 9 5z" />
                                    <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">Lulus</div>
                                <div class="text-xs text-zinc-500">Siswa telah menyelesaikan pendidikan</div>
                            </div>
                        </div>
                    </label>

                    <label class="relative flex items-center p-4 border rounded-xl cursor-pointer transition-all {{ $exit_type === 'do' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                        <input type="radio" wire:model.live="exit_type" value="do" class="sr-only">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg {{ $exit_type === 'do' ? 'bg-red-100 text-red-600' : 'bg-zinc-100 text-zinc-600' }}">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">Dikeluarkan (DO)</div>
                                <div class="text-xs text-zinc-500">Siswa dikeluarkan dari sekolah</div>
                            </div>
                        </div>
                    </label>
                </div>
            </x-card>

            <!-- Mutation Details -->
            <x-card>
                <x-slot:header>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Detail Mutasi</h3>
                </x-slot:header>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input wire:model="mutation_date" type="date" label="Tanggal Mutasi" required />
                    <flux:input wire:model="effective_date" type="date" label="Tanggal Efektif" required />
                    <flux:select wire:model="academic_year_id" label="Tahun Ajaran" required>
                        <option value="">Pilih Tahun Ajaran</option>
                        @foreach($this->academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    @if($exit_type === 'keluar')
                        <flux:input wire:model="destination_school" label="Sekolah Tujuan" placeholder="Nama sekolah tujuan" />
                    @endif
                    
                    <flux:select wire:model="reason" label="Alasan" required class="{{ $exit_type === 'keluar' ? '' : 'md:col-span-2' }}">
                        <option value="">Pilih Alasan</option>
                        @foreach($this->exitReasons[$exit_type] ?? [] as $value => $label)
                            <option value="{{ $label }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    
                    <flux:input wire:model="document_number" label="No. Surat Keterangan" placeholder="Nomor surat (opsional)" />
                </div>
            </x-card>

            <!-- Notes -->
            <x-card>
                <x-slot:header>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Catatan Tambahan</h3>
                </x-slot:header>
                <flux:textarea wire:model="notes" rows="3" placeholder="Catatan tambahan mengenai mutasi keluar siswa ini..." />
            </x-card>

            <!-- Warning -->
            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
                <div class="flex gap-3">
                    <svg class="size-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h4 class="font-medium text-amber-800 dark:text-amber-200">Perhatian</h4>
                        <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                            Pengajuan mutasi keluar akan memerlukan persetujuan dari admin/kepala sekolah. 
                            Status siswa akan berubah setelah mutasi disetujui.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <flux:button type="button" :href="route('students.mutations.index')" variant="ghost" wire:navigate>
                    Batal
                </flux:button>
                <flux:button type="submit" variant="primary" icon="paper-airplane" class="bg-linear-to-r! from-amber-600! to-orange-600!">
                    Ajukan Mutasi Keluar
                </flux:button>
            </div>
        @endif
    </form>
</div>
