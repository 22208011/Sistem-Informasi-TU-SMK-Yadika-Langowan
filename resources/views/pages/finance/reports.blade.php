<?php

use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\PaymentTransaction;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Classroom;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.app')] #[Title('Laporan Keuangan')] class extends Component {
    use WithPagination;

    public string $reportType = 'summary'; // summary, per_student, per_type, transactions
    public ?int $filterAcademicYear = null;
    public string $filterMonth = '';
    public string $filterClassroom = '';
    public string $filterType = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(): void
    {
        $this->filterAcademicYear = AcademicYear::where('is_active', true)->first()?->id;
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    #[Computed]
    public function academicYears()
    {
        return AcademicYear::orderByDesc('start_year')->get();
    }

    #[Computed]
    public function paymentTypes()
    {
        return PaymentType::active()->orderBy('name')->get();
    }

    #[Computed]
    public function classrooms()
    {
        return Classroom::with('department')->orderBy('grade')->orderBy('name')->get();
    }

    #[Computed]
    public function summaryData()
    {
        $query = Payment::query()
            ->when($this->filterAcademicYear, fn($q) => $q->where('academic_year_id', $this->filterAcademicYear));

        $totalAmount = $query->sum('total_amount');
        $totalPaid = $query->sum('paid_amount');
        $totalUnpaid = $totalAmount - $totalPaid;

        $byStatus = [
            'lunas' => $query->clone()->where('payment_status', 'lunas')->count(),
            'sebagian' => $query->clone()->where('payment_status', 'sebagian')->count(),
            'belum_bayar' => $query->clone()->where('payment_status', 'belum_bayar')->count(),
        ];

        // By payment type
        $byType = Payment::query()
            ->select('payment_type_id', DB::raw('SUM(total_amount) as total'), DB::raw('SUM(paid_amount) as paid'), DB::raw('COUNT(*) as count'))
            ->when($this->filterAcademicYear, fn($q) => $q->where('academic_year_id', $this->filterAcademicYear))
            ->groupBy('payment_type_id')
            ->with('paymentType')
            ->get();

        // By month (for current academic year)
        $byMonth = Payment::query()
            ->select('month', DB::raw('SUM(total_amount) as total'), DB::raw('SUM(paid_amount) as paid'))
            ->when($this->filterAcademicYear, fn($q) => $q->where('academic_year_id', $this->filterAcademicYear))
            ->whereNotNull('month')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_unpaid' => $totalUnpaid,
            'percentage_paid' => $totalAmount > 0 ? round(($totalPaid / $totalAmount) * 100, 1) : 0,
            'by_status' => $byStatus,
            'by_type' => $byType,
            'by_month' => $byMonth,
        ];
    }

    #[Computed]
    public function studentReport()
    {
        return Payment::query()
            ->select('student_id', DB::raw('SUM(total_amount) as total'), DB::raw('SUM(paid_amount) as paid'), DB::raw('COUNT(*) as invoice_count'))
            ->with(['student.classroom'])
            ->when($this->filterAcademicYear, fn($q) => $q->where('academic_year_id', $this->filterAcademicYear))
            ->when($this->filterClassroom, fn($q) => $q->whereHas('student', fn($s) => $s->where('classroom_id', $this->filterClassroom)))
            ->when($this->filterType, fn($q) => $q->where('payment_type_id', $this->filterType))
            ->groupBy('student_id')
            ->orderByRaw('(SUM(total_amount) - SUM(paid_amount)) DESC')
            ->paginate(20);
    }

    #[Computed]
    public function typeReport()
    {
        return PaymentType::query()
            ->withCount(['payments' => function($q) {
                $q->when($this->filterAcademicYear, fn($q) => $q->where('academic_year_id', $this->filterAcademicYear));
            }])
            ->withSum(['payments' => function($q) {
                $q->when($this->filterAcademicYear, fn($q) => $q->where('academic_year_id', $this->filterAcademicYear));
            }], 'total_amount')
            ->withSum(['payments' => function($q) {
                $q->when($this->filterAcademicYear, fn($q) => $q->where('academic_year_id', $this->filterAcademicYear));
            }], 'paid_amount')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function transactionReport()
    {
        return PaymentTransaction::query()
            ->with(['payment.student', 'payment.paymentType', 'receiver'])
            ->when($this->dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
            ->when($this->filterType, fn($q) => $q->whereHas('payment', fn($p) => $p->where('payment_type_id', $this->filterType)))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(20);
    }

    #[Computed]
    public function transactionSummary()
    {
        $query = PaymentTransaction::query()
            ->when($this->dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
            ->when($this->filterType, fn($q) => $q->whereHas('payment', fn($p) => $p->where('payment_type_id', $this->filterType)));

        return [
            'total' => $query->sum('amount'),
            'count' => $query->count(),
            'by_method' => PaymentTransaction::query()
                ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->when($this->dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
                ->when($this->dateTo, fn($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
                ->when($this->filterType, fn($q) => $q->whereHas('payment', fn($p) => $p->where('payment_type_id', $this->filterType)))
                ->groupBy('payment_method')
                ->get(),
        ];
    }

    public function setReportType($type): void
    {
        $this->reportType = $type;
        $this->resetPage();
    }

    public function updatingFilterAcademicYear(): void
    {
        $this->resetPage();
    }

    public function updatingFilterMonth(): void
    {
        $this->resetPage();
    }

    public function updatingFilterClassroom(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Laporan Keuangan</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Laporan pembayaran dan transaksi keuangan</flux:text>
        </div>
        
        <div class="flex items-center gap-2">
            <flux:button 
                as="a" 
                href="{{ route('finance.reports.export.excel', [
                    'academic_year_id' => $filterAcademicYear,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'classroom_id' => $filterClassroom,
                    'type_id' => $filterType
                ]) }}" 
                target="_blank" 
                icon="document-text" 
                variant="outline"
            >
                Export Excel
            </flux:button>
            <flux:button 
                as="a" 
                href="{{ route('finance.reports.export.pdf', [
                    'academic_year_id' => $filterAcademicYear,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'classroom_id' => $filterClassroom,
                    'type_id' => $filterType
                ]) }}" 
                target="_blank" 
                icon="document-arrow-down" 
                variant="primary"
            >
                Export PDF
            </flux:button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700">
        <button wire:click="setReportType('summary')" class="whitespace-nowrap px-4 py-2 text-sm font-medium {{ $reportType === 'summary' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-zinc-500 hover:text-zinc-700' }}">
            Ringkasan
        </button>
        <button wire:click="setReportType('per_student')" class="whitespace-nowrap px-4 py-2 text-sm font-medium {{ $reportType === 'per_student' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-zinc-500 hover:text-zinc-700' }}">
            Per Siswa
        </button>
        <button wire:click="setReportType('per_type')" class="whitespace-nowrap px-4 py-2 text-sm font-medium {{ $reportType === 'per_type' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-zinc-500 hover:text-zinc-700' }}">
            Per Jenis
        </button>
        <button wire:click="setReportType('transactions')" class="whitespace-nowrap px-4 py-2 text-sm font-medium {{ $reportType === 'transactions' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-zinc-500 hover:text-zinc-700' }}">
            Transaksi
        </button>
    </div>

    {{-- Summary Report --}}
    @if($reportType === 'summary')
    <div class="space-y-6">
        {{-- Filters --}}
        <flux:card>
            <div class="flex flex-wrap gap-4">
                <flux:select wire:model.live="filterAcademicYear" label="Tahun Ajaran" class="w-40">
                    <option value="">Semua</option>
                    @foreach($this->academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        {{-- Overall Stats --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:card>
                <flux:text class="text-sm text-zinc-500">Total Tagihan</flux:text>
                <flux:text class="text-2xl font-bold text-blue-600">Rp {{ number_format($this->summaryData['total_amount'], 0, ',', '.') }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm text-zinc-500">Total Terbayar</flux:text>
                <flux:text class="text-2xl font-bold text-green-600">Rp {{ number_format($this->summaryData['total_paid'], 0, ',', '.') }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm text-zinc-500">Total Belum Bayar</flux:text>
                <flux:text class="text-2xl font-bold text-red-600">Rp {{ number_format($this->summaryData['total_unpaid'], 0, ',', '.') }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text class="text-sm text-zinc-500">Persentase Terbayar</flux:text>
                <flux:text class="text-2xl font-bold {{ $this->summaryData['percentage_paid'] >= 75 ? 'text-green-600' : ($this->summaryData['percentage_paid'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $this->summaryData['percentage_paid'] }}%
                </flux:text>
            </flux:card>
        </div>

        {{-- Status Distribution --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Distribusi Status</flux:heading>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="flex items-center justify-between rounded-lg border p-4 dark:border-zinc-700">
                    <div>
                        <flux:text class="text-sm text-zinc-500">Lunas</flux:text>
                        <flux:text class="text-2xl font-bold text-green-600">{{ number_format($this->summaryData['by_status']['lunas']) }}</flux:text>
                    </div>
                    <flux:icon name="check-circle" class="size-10 text-green-200" />
                </div>
                <div class="flex items-center justify-between rounded-lg border p-4 dark:border-zinc-700">
                    <div>
                        <flux:text class="text-sm text-zinc-500">Bayar Sebagian</flux:text>
                        <flux:text class="text-2xl font-bold text-yellow-600">{{ number_format($this->summaryData['by_status']['sebagian']) }}</flux:text>
                    </div>
                    <flux:icon name="minus-circle" class="size-10 text-yellow-200" />
                </div>
                <div class="flex items-center justify-between rounded-lg border p-4 dark:border-zinc-700">
                    <div>
                        <flux:text class="text-sm text-zinc-500">Belum Bayar</flux:text>
                        <flux:text class="text-2xl font-bold text-red-600">{{ number_format($this->summaryData['by_status']['belum_bayar']) }}</flux:text>
                    </div>
                    <flux:icon name="x-circle" class="size-10 text-red-200" />
                </div>
            </div>
        </flux:card>

        {{-- By Payment Type --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Per Jenis Pembayaran</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <tr>
                            <th class="px-4 py-3 font-medium">Jenis</th>
                            <th class="px-4 py-3 font-medium text-center">Jumlah</th>
                            <th class="px-4 py-3 font-medium text-right">Total Tagihan</th>
                            <th class="px-4 py-3 font-medium text-right">Terbayar</th>
                            <th class="px-4 py-3 font-medium text-right">Sisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->summaryData['by_type'] as $item)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3 font-medium">{{ $item->paymentType?->name ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($item->count) }}</td>
                                <td class="px-4 py-3 text-right">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-green-600">Rp {{ number_format($item->paid, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-red-600">Rp {{ number_format($item->total - $item->paid, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>

        {{-- By Month --}}
        @if($this->summaryData['by_month']->count() > 0)
        <flux:card>
            <flux:heading size="lg" class="mb-4">Per Bulan (Pembayaran Rutin)</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <tr>
                            <th class="px-4 py-3 font-medium">Bulan</th>
                            <th class="px-4 py-3 font-medium text-right">Total Tagihan</th>
                            <th class="px-4 py-3 font-medium text-right">Terbayar</th>
                            <th class="px-4 py-3 font-medium text-right">Sisa</th>
                            <th class="px-4 py-3 font-medium text-right">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->summaryData['by_month'] as $item)
                            @php
                                $percentage = $item->total > 0 ? round(($item->paid / $item->total) * 100, 1) : 0;
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3 font-medium">{{ \App\Models\Payment::MONTHS[$item->month] ?? $item->month }}</td>
                                <td class="px-4 py-3 text-right">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-green-600">Rp {{ number_format($item->paid, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-red-600">Rp {{ number_format($item->total - $item->paid, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="{{ $percentage >= 75 ? 'text-green-600' : ($percentage >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $percentage }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
        @endif
    </div>
    @endif

    {{-- Per Student Report --}}
    @if($reportType === 'per_student')
    <div class="space-y-6">
        {{-- Filters --}}
        <flux:card>
            <div class="flex flex-wrap gap-4">
                <flux:select wire:model.live="filterAcademicYear" label="Tahun Ajaran" class="w-40">
                    <option value="">Semua</option>
                    @foreach($this->academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="filterClassroom" label="Kelas" class="w-40">
                    <option value="">Semua Kelas</option>
                    @foreach($this->classrooms as $classroom)
                        <option value="{{ $classroom->id }}">{{ $classroom->full_name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="filterType" label="Jenis" class="w-40">
                    <option value="">Semua Jenis</option>
                    @foreach($this->paymentTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        {{-- Student Table --}}
        <flux:card class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">Siswa</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">Kelas</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium text-center">Tagihan</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Total</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Terbayar</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Sisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($this->studentReport as $item)
                            @php
                                $remaining = $item->total - $item->paid;
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 {{ $remaining > 0 ? 'bg-red-50/50 dark:bg-red-900/10' : '' }}">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $item->student?->name ?? '-' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $item->student?->nis }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $item->student?->classroom?->name ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">{{ $item->invoice_count }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-green-600">Rp {{ number_format($item->paid, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right {{ $remaining > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                                    Rp {{ number_format($remaining, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-zinc-500">
                                    Tidak ada data
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($this->studentReport->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $this->studentReport->links() }}
                </div>
            @endif
        </flux:card>
    </div>
    @endif

    {{-- Per Type Report --}}
    @if($reportType === 'per_type')
    <div class="space-y-6">
        {{-- Filters --}}
        <flux:card>
            <div class="flex flex-wrap gap-4">
                <flux:select wire:model.live="filterAcademicYear" label="Tahun Ajaran" class="w-40">
                    <option value="">Semua</option>
                    @foreach($this->academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        {{-- Type Table --}}
        <flux:card class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">Kode</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">Jenis Pembayaran</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium text-center">Tagihan</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Total</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Terbayar</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Sisa</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium text-right">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($this->typeReport as $type)
                            @php
                                $total = $type->payments_sum_total_amount ?? 0;
                                $paid = $type->payments_sum_paid_amount ?? 0;
                                $remaining = $total - $paid;
                                $percentage = $total > 0 ? round(($paid / $total) * 100, 1) : 0;
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-sm">{{ $type->code }}</td>
                                <td class="px-4 py-3 font-medium">
                                    {{ $type->name }}
                                    @if($type->is_recurring)
                                        <flux:badge size="sm" color="blue">Bulanan</flux:badge>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">{{ $type->payments_count ?? 0 }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format($total, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-green-600">Rp {{ number_format($paid, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-red-600">Rp {{ number_format($remaining, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <span class="{{ $percentage >= 75 ? 'text-green-600' : ($percentage >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $percentage }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-zinc-500">
                                    Tidak ada data
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    </div>
    @endif

    {{-- Transactions Report --}}
    @if($reportType === 'transactions')
    <div class="space-y-6">
        {{-- Filters --}}
        <flux:card>
            <div class="flex flex-wrap gap-4">
                <flux:input wire:model.live="dateFrom" type="date" label="Dari Tanggal" class="w-40" />
                <flux:input wire:model.live="dateTo" type="date" label="Sampai Tanggal" class="w-40" />
                <flux:select wire:model.live="filterType" label="Jenis" class="w-40">
                    <option value="">Semua Jenis</option>
                    @foreach($this->paymentTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        {{-- Transaction Summary --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:card>
                <flux:text class="text-sm text-zinc-500">Total di Periode Ini</flux:text>
                <flux:text class="text-2xl font-bold text-green-600">Rp {{ number_format($this->transactionSummary['total'], 0, ',', '.') }}</flux:text>
                <flux:text class="text-xs text-zinc-500">{{ number_format($this->transactionSummary['count']) }} transaksi</flux:text>
            </flux:card>
            @foreach($this->transactionSummary['by_method'] as $method)
                <flux:card>
                    <flux:text class="text-sm text-zinc-500">{{ \App\Models\PaymentTransaction::PAYMENT_METHODS[$method->payment_method] ?? $method->payment_method }}</flux:text>
                    <flux:text class="text-xl font-bold">Rp {{ number_format($method->total, 0, ',', '.') }}</flux:text>
                    <flux:text class="text-xs text-zinc-500">{{ number_format($method->count) }} transaksi</flux:text>
                </flux:card>
            @endforeach
        </div>

        {{-- Transaction Table --}}
        <flux:card class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">No. Kwitansi</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">Tanggal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">Siswa</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">Jenis</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">Metode</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium text-right">Jumlah</th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">Penerima</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($this->transactionReport as $tx)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-sm">{{ $tx->receipt_number }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $tx->payment_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $tx->payment?->student?->name ?? '-' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $tx->payment?->invoice_number }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $tx->payment?->paymentType?->name ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <flux:badge size="sm" color="zinc">{{ $tx->method_label }}</flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-green-600">{{ $tx->formatted_amount }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-zinc-500">{{ $tx->receiver?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-zinc-500">
                                    Tidak ada transaksi
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($this->transactionReport->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $this->transactionReport->links() }}
                </div>
            @endif
        </flux:card>
    </div>
    @endif
</div>
