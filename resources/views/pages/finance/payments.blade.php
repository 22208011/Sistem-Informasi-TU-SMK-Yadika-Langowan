<?php

use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\PaymentTransaction;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Livewire\Concerns\WithNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Pembayaran Siswa')] class extends Component {
    use WithPagination;
    use WithNotification;

    public string $search = '';
    public string $filterType = '';
    public string $filterStatus = '';
    public string $filterMonth = '';
    public ?int $filterAcademicYear = null;
    public string $activeTab = 'all'; // all, unpaid, paid

    // Payment form
    public bool $showModal = false;
    public bool $showPayModal = false;
    public bool $showTypeModal = false;
    public ?int $editingId = null;
    public ?int $editingTypeId = null;
    public ?int $payingId = null;

    // Payment form fields
    public ?int $payment_type_id = null;
    public ?int $student_id = null;
    public ?int $academic_year_id = null;
    public string $amount = '';
    public string $discount = '0';
    public ?string $due_date = null;
    public ?int $month = null;
    public string $notes = '';

    // Transaction form fields
    public string $pay_amount = '';
    public string $payment_method = 'tunai';
    public ?string $payment_date = null;
    public string $reference_number = '';
    public string $pay_notes = '';

    // Payment type form
    public string $typeName = '';
    public string $typeCode = '';
    public string $typeDescription = '';
    public string $typeAmount = '0';
    public bool $typeIsRecurring = false;
    public bool $typeIsActive = true;

    public function mount(): void
    {
        $this->filterAcademicYear = AcademicYear::where('is_active', true)->first()?->id;
        $this->payment_date = now()->format('Y-m-d');
        $this->due_date = now()->addMonth()->format('Y-m-d');
    }

    public function rules(): array
    {
        return [
            'payment_type_id' => ['required', 'exists:payment_types,id'],
            'student_id' => ['required', 'exists:students,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected $messages = [
        'payment_type_id.required' => 'Jenis pembayaran wajib dipilih.',
        'student_id.required' => 'Siswa wajib dipilih.',
        'academic_year_id.required' => 'Tahun ajaran wajib dipilih.',
        'amount.required' => 'Jumlah wajib diisi.',
    ];

    #[Computed]
    public function payments()
    {
        return Payment::query()
            ->with(['paymentType', 'student.classroom', 'academicYear', 'transactions'])
            ->when($this->search, fn($q) => $q->where(function($query) {
                $query->where('invoice_number', 'like', "%{$this->search}%")
                      ->orWhereHas('student', fn($q2) => $q2->where('name', 'like', "%{$this->search}%")->orWhere('nis', 'like', "%{$this->search}%"));
            }))
            ->when($this->filterType, fn($q) => $q->where('payment_type_id', $this->filterType))
            ->when($this->filterStatus, fn($q) => $q->where('payment_status', $this->filterStatus))
            ->when($this->filterMonth, fn($q) => $q->where('month', $this->filterMonth))
            ->when($this->filterAcademicYear, fn($q) => $q->where('academic_year_id', $this->filterAcademicYear))
            ->when($this->activeTab === 'unpaid', fn($q) => $q->whereIn('payment_status', ['belum_bayar', 'sebagian']))
            ->when($this->activeTab === 'paid', fn($q) => $q->where('payment_status', 'lunas'))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    #[Computed]
    public function paymentTypes()
    {
        return PaymentType::active()->orderBy('name')->get();
    }

    #[Computed]
    public function allPaymentTypes()
    {
        return PaymentType::withCount('payments')->orderBy('name')->get();
    }

    #[Computed]
    public function students()
    {
        return Student::active()->with('classroom')->orderBy('name')->get();
    }

    #[Computed]
    public function academicYears()
    {
        return AcademicYear::orderByDesc('start_year')->get();
    }

    #[Computed]
    public function statistics()
    {
        $query = Payment::query()
            ->when($this->filterAcademicYear, fn($q) => $q->where('academic_year_id', $this->filterAcademicYear));

        return [
            'total_invoices' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'total_paid' => $query->sum('paid_amount'),
            'total_unpaid' => $query->clone()->whereIn('payment_status', ['belum_bayar', 'sebagian'])->sum('total_amount') - $query->clone()->whereIn('payment_status', ['belum_bayar', 'sebagian'])->sum('paid_amount'),
            'count_unpaid' => $query->clone()->whereIn('payment_status', ['belum_bayar', 'sebagian'])->count(),
            'count_paid' => $query->clone()->where('payment_status', 'lunas')->count(),
        ];
    }

    public function setTab($tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->academic_year_id = $this->filterAcademicYear;
        $this->showModal = true;
    }

    public function edit(Payment $payment): void
    {
        if ($payment->payment_status === 'lunas') {
            $this->error('Pembayaran yang sudah lunas tidak dapat diedit.');
            return;
        }

        $this->editingId = $payment->id;
        $this->payment_type_id = $payment->payment_type_id;
        $this->student_id = $payment->student_id;
        $this->academic_year_id = $payment->academic_year_id;
        $this->amount = $payment->amount;
        $this->discount = $payment->discount;
        $this->due_date = $payment->due_date?->format('Y-m-d');
        $this->month = $payment->month;
        $this->notes = $payment->notes ?? '';
        $this->showModal = true;
    }

    public function updatedPaymentTypeId(): void
    {
        if ($this->payment_type_id) {
            $type = PaymentType::find($this->payment_type_id);
            if ($type && $type->default_amount > 0) {
                $this->amount = $type->default_amount;
            }
        }
    }

    public function save(): void
    {
        $this->validate();

        $discount = floatval($this->discount) ?: 0;
        $amount = floatval($this->amount);
        $totalAmount = max(0, $amount - $discount);

        $data = [
            'payment_type_id' => $this->payment_type_id,
            'student_id' => $this->student_id,
            'academic_year_id' => $this->academic_year_id,
            'amount' => $amount,
            'discount' => $discount,
            'total_amount' => $totalAmount,
            'due_date' => $this->due_date ?: null,
            'month' => $this->month ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->editingId) {
            $payment = Payment::find($this->editingId);
            $payment->update($data);
            $payment->updateStatus();
            $this->success('Tagihan berhasil diperbarui.');
        } else {
            $data['invoice_number'] = Payment::generateInvoiceNumber();
            $data['created_by'] = auth()->id();
            Payment::create($data);
            $this->success('Tagihan berhasil dibuat.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function openPayModal(Payment $payment): void
    {
        if ($payment->payment_status === 'lunas') {
            $this->error('Pembayaran sudah lunas.');
            return;
        }

        $this->payingId = $payment->id;
        $this->pay_amount = $payment->remaining_amount;
        $this->payment_method = 'tunai';
        $this->payment_date = now()->format('Y-m-d');
        $this->reference_number = '';
        $this->pay_notes = '';
        $this->showPayModal = true;
    }

    public function processPayment(): void
    {
        $this->validate([
            'pay_amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:tunai,transfer,qris,lainnya'],
            'payment_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'pay_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'pay_amount.required' => 'Jumlah bayar wajib diisi.',
            'pay_amount.min' => 'Jumlah bayar minimal Rp 1.',
            'payment_date.required' => 'Tanggal bayar wajib diisi.',
        ]);

        $payment = Payment::find($this->payingId);
        if (!$payment || $payment->payment_status === 'lunas') {
            $this->error('Pembayaran tidak valid.');
            return;
        }

        $payAmount = floatval($this->pay_amount);
        if ($payAmount > $payment->remaining_amount) {
            $payAmount = $payment->remaining_amount;
        }

        PaymentTransaction::create([
            'payment_id' => $payment->id,
            'receipt_number' => PaymentTransaction::generateReceiptNumber(),
            'amount' => $payAmount,
            'payment_date' => $this->payment_date,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number ?: null,
            'notes' => $this->pay_notes ?: null,
            'received_by' => auth()->id(),
        ]);

        $this->showPayModal = false;
        $this->success('Pembayaran berhasil dicatat.');
    }

    public function delete(Payment $payment): void
    {
        // Hapus semua riwayat transaksi terkait sebelum menghapus tagihan utama
        if ($payment->transactions()->count() > 0) {
            $payment->transactions()->delete();
        }

        $payment->delete();
        $this->success('Tagihan beserta riwayat transaksinya berhasil dihapus.');
    }

    // Payment Type management
    public function createType(): void
    {
        $this->resetTypeForm();
        $this->showTypeModal = true;
    }

    public function editType(PaymentType $type): void
    {
        $this->editingTypeId = $type->id;
        $this->typeName = $type->name;
        $this->typeCode = $type->code;
        $this->typeDescription = $type->description ?? '';
        $this->typeAmount = $type->default_amount;
        $this->typeIsRecurring = (bool) $type->is_recurring;
        $this->typeIsActive = (bool) $type->is_active;
        $this->showTypeModal = true;
    }

    public function saveType(): void
    {
        $this->validate([
            'typeName' => ['required', 'string', 'max:255'],
            'typeCode' => ['required', 'string', 'max:20', 'unique:payment_types,code,' . $this->editingTypeId],
            'typeDescription' => ['nullable', 'string', 'max:500'],
            'typeAmount' => ['nullable', 'numeric', 'min:0'],
            'typeIsRecurring' => ['boolean'],
            'typeIsActive' => ['boolean'],
        ], [
            'typeName.required' => 'Nama jenis pembayaran wajib diisi.',
            'typeCode.required' => 'Kode wajib diisi.',
            'typeCode.unique' => 'Kode sudah terdaftar.',
        ]);

        $data = [
            'name' => $this->typeName,
            'code' => strtoupper($this->typeCode),
            'description' => $this->typeDescription ?: null,
            'default_amount' => floatval($this->typeAmount) ?: 0,
            'is_recurring' => $this->typeIsRecurring,
            'is_active' => $this->typeIsActive,
        ];

        if ($this->editingTypeId) {
            PaymentType::find($this->editingTypeId)->update($data);
            $this->success('Jenis pembayaran berhasil diperbarui.');
        } else {
            PaymentType::create($data);
            $this->success('Jenis pembayaran berhasil ditambahkan.');
        }

        $this->showTypeModal = false;
        $this->resetTypeForm();
    }

    public function deleteType(PaymentType $type): void
    {
        if ($type->payments()->count() > 0) {
            $this->error('Tidak dapat menghapus jenis pembayaran yang memiliki tagihan.');
            return;
        }

        $type->delete();
        $this->success('Jenis pembayaran berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->payment_type_id = null;
        $this->student_id = null;
        $this->academic_year_id = $this->filterAcademicYear;
        $this->amount = '';
        $this->discount = '0';
        $this->due_date = now()->addMonth()->format('Y-m-d');
        $this->month = null;
        $this->notes = '';
        $this->resetValidation();
    }

    public function resetTypeForm(): void
    {
        $this->editingTypeId = null;
        $this->typeName = '';
        $this->typeCode = '';
        $this->typeDescription = '';
        $this->typeAmount = '0';
        $this->typeIsRecurring = false;
        $this->typeIsActive = true;
        $this->resetValidation();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterMonth(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAcademicYear(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Pembayaran Siswa</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Kelola tagihan dan pembayaran siswa</flux:text>
        </div>
        <div class="flex gap-2">
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('finance.create'))
            <flux:button variant="ghost" icon="tag" wire:click="createType">
                Jenis Pembayaran
            </flux:button>
            <flux:button variant="primary" icon="plus" wire:click="create">
                Buat Tagihan
            </flux:button>
            @endif
        </div>
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-zinc-800 dark:text-white">{{ number_format($this->statistics['total_invoices']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Total Tagihan</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-lg font-bold text-blue-600">Rp {{ number_format($this->statistics['total_amount'], 0, ',', '.') }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Total Nilai</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-lg font-bold text-green-600">Rp {{ number_format($this->statistics['total_paid'], 0, ',', '.') }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Terbayar</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-lg font-bold text-red-600">Rp {{ number_format($this->statistics['total_unpaid'], 0, ',', '.') }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Belum Bayar</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-green-600">{{ number_format($this->statistics['count_paid']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Lunas</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:text class="text-2xl font-bold text-red-600">{{ number_format($this->statistics['count_unpaid']) }}</flux:text>
            <flux:text class="text-xs text-zinc-500">Belum Lunas</flux:text>
        </flux:card>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700">
        <button wire:click="setTab('all')" class="px-4 py-2 text-sm font-medium {{ $activeTab === 'all' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-zinc-500 hover:text-zinc-700' }}">
            Semua
        </button>
        <button wire:click="setTab('unpaid')" class="px-4 py-2 text-sm font-medium {{ $activeTab === 'unpaid' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-zinc-500 hover:text-zinc-700' }}">
            Belum Lunas
            @if($this->statistics['count_unpaid'] > 0)
            <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-600">{{ $this->statistics['count_unpaid'] }}</span>
            @endif
        </button>
        <button wire:click="setTab('paid')" class="px-4 py-2 text-sm font-medium {{ $activeTab === 'paid' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-zinc-500 hover:text-zinc-700' }}">
            Lunas
        </button>
    </div>

    {{-- Filters --}}
    <flux:card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex-1">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari no. invoice, nama siswa, NIS..." icon="magnifying-glass" />
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:select wire:model.live="filterAcademicYear" class="w-36">
                    <option value="">Semua TA</option>
                    @foreach($this->academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="filterType" class="w-36">
                    <option value="">Semua Jenis</option>
                    @foreach($this->paymentTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="filterMonth" class="w-36">
                    <option value="">Semua Bulan</option>
                    @foreach(\App\Models\Payment::MONTHS as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </flux:card>

    {{-- Payments Table --}}
    <flux:card class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">No. Invoice</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Siswa</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Jenis</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Total</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Terbayar</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Sisa</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Status</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->payments as $payment)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 {{ $payment->isOverdue() ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-sm">
                                {{ $payment->invoice_number }}
                                @if($payment->month)
                                    <div class="text-xs text-zinc-500">{{ $payment->month_label }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $payment->student?->name ?? '-' }}</div>
                                <div class="text-xs text-zinc-500">
                                    {{ $payment->student?->nis }} - {{ $payment->student?->classroom?->name ?? '-' }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $payment->paymentType?->name ?? '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium">{{ $payment->formatted_total }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-green-600">{{ $payment->formatted_paid }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-red-600">{{ $payment->formatted_remaining }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <flux:badge size="sm" :color="$payment->status_color">
                                    {{ $payment->status_label }}
                                </flux:badge>
                                @if($payment->isOverdue())
                                    <div class="mt-1 text-xs text-red-600">Jatuh tempo: {{ $payment->due_date->format('d/m/Y') }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if($payment->payment_status !== 'lunas')
                                        <flux:button size="xs" variant="primary" wire:click="openPayModal({{ $payment->id }})" title="Bayar">
                                            Bayar
                                        </flux:button>
                                        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('finance.edit'))
                                        <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="edit({{ $payment->id }})" title="Edit" />
                                        @endif
                                    @endif
                                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('finance.delete'))
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="delete({{ $payment->id }})" wire:confirm="Yakin ingin menghapus tagihan ini?" class="text-red-600 hover:text-red-700" title="Hapus" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-zinc-500">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon name="banknotes" class="size-8 text-zinc-400" />
                                    <span>Belum ada data pembayaran</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->payments->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->payments->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Payment Modal --}}
    <flux:modal wire:model="showModal" class="max-w-2xl">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit Tagihan' : 'Buat Tagihan Baru' }}</flux:heading>

            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model.live="payment_type_id" label="Jenis Pembayaran *">
                        <option value="">Pilih Jenis</option>
                        @foreach($this->paymentTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="academic_year_id" label="Tahun Ajaran *">
                        <option value="">Pilih Tahun Ajaran</option>
                        @foreach($this->academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:select wire:model="student_id" label="Siswa *">
                    <option value="">Pilih Siswa</option>
                    @foreach($this->students as $student)
                        <option value="{{ $student->id }}">{{ $student->nis }} - {{ $student->name }} ({{ $student->classroom?->name ?? '-' }})</option>
                    @endforeach
                </flux:select>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="amount" type="number" label="Jumlah (Rp) *" placeholder="0" />
                    <flux:input wire:model="discount" type="number" label="Diskon (Rp)" placeholder="0" />
                    <flux:select wire:model="month" label="Bulan (untuk SPP)">
                        <option value="">-- Tidak Ada --</option>
                        @foreach(\App\Models\Payment::MONTHS as $num => $name)
                            <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model="due_date" type="date" label="Jatuh Tempo" />

                <flux:textarea wire:model="notes" label="Catatan" placeholder="Catatan tambahan..." rows="2" />

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">Batal</flux:button>
                    <flux:button type="submit" variant="primary">{{ $editingId ? 'Perbarui' : 'Simpan' }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Pay Modal --}}
    <flux:modal wire:model="showPayModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Proses Pembayaran</flux:heading>

            <form wire:submit="processPayment" class="space-y-4">
                <flux:input wire:model="pay_amount" type="number" label="Jumlah Bayar (Rp) *" placeholder="0" min="1" />

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model="payment_method" label="Metode Pembayaran *">
                        @foreach(\App\Models\PaymentTransaction::PAYMENT_METHODS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="payment_date" type="date" label="Tanggal Bayar *" />
                </div>

                <flux:input wire:model="reference_number" label="No. Referensi" placeholder="No. Transfer / Ref" />

                <flux:textarea wire:model="pay_notes" label="Catatan" placeholder="Catatan pembayaran..." rows="2" />

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button type="button" variant="ghost" wire:click="$set('showPayModal', false)">Batal</flux:button>
                    <flux:button type="submit" variant="primary">Proses Pembayaran</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Payment Type Modal --}}
    <flux:modal wire:model="showTypeModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingTypeId ? 'Edit Jenis Pembayaran' : 'Tambah Jenis Pembayaran' }}</flux:heading>

            <form wire:submit="saveType" class="space-y-4">
                <flux:input wire:model="typeCode" label="Kode *" placeholder="SPP, UG, dll" />
                <flux:input wire:model="typeName" label="Nama Jenis *" placeholder="SPP Bulanan, Uang Gedung..." />
                <flux:input wire:model="typeAmount" type="number" label="Nominal Default (Rp)" placeholder="0" />
                <flux:textarea wire:model="typeDescription" label="Deskripsi" placeholder="Deskripsi jenis pembayaran..." rows="2" />

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <flux:checkbox wire:model="typeIsRecurring" />
                        <flux:text>Pembayaran Rutin (Bulanan)</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:checkbox wire:model="typeIsActive" />
                        <flux:text>Aktif</flux:text>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button type="button" variant="ghost" wire:click="$set('showTypeModal', false)">Batal</flux:button>
                    <flux:button type="submit" variant="primary">{{ $editingTypeId ? 'Perbarui' : 'Simpan' }}</flux:button>
                </div>
            </form>

            {{-- Type List --}}
            @if($this->allPaymentTypes->count() > 0)
            <div class="border-t pt-4 dark:border-zinc-700">
                <flux:text class="mb-2 font-medium">Daftar Jenis Pembayaran</flux:text>
                <div class="max-h-48 space-y-2 overflow-y-auto">
                    @foreach($this->allPaymentTypes as $type)
                        <div class="flex items-center justify-between rounded-lg border p-2 dark:border-zinc-700">
                            <div>
                                <span class="font-mono text-xs text-zinc-500">{{ $type->code }}</span>
                                <span class="mx-1">-</span>
                                <span class="font-medium">{{ $type->name }}</span>
                                <span class="text-xs text-zinc-500">({{ $type->payments_count }} tagihan)</span>
                                @if($type->is_recurring)
                                    <flux:badge size="sm" color="blue">Bulanan</flux:badge>
                                @endif
                            </div>
                            <div class="flex gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil" wire:click="editType({{ $type->id }})" />
                                @if($type->payments_count === 0)
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="deleteType({{ $type->id }})" wire:confirm="Yakin ingin menghapus jenis pembayaran ini?" class="text-red-600" />
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </flux:modal>
</div>
