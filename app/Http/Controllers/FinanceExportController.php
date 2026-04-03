<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\PaymentTransaction;
use App\Models\AcademicYear;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FinanceReportExport;

class FinanceExportController extends Controller
{
    public function exportPdf(Request $request)
    {
        $filters = $this->getFilters($request);
        $data = $this->getReportData($filters);
        
        $pdf = Pdf::loadView('exports.finance.pdf', [
            'data' => $data,
            'filters' => $filters,
        ]);
        
        return $pdf->download('Laporan_Keuangan_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->getFilters($request);
        
        return Excel::download(
            new FinanceReportExport($filters),
            'Laporan_Keuangan_' . date('Ymd_His') . '.xlsx'
        );
    }

    private function getFilters(Request $request)
    {
        return [
            'academic_year_id' => $request->input('academic_year_id', AcademicYear::where('is_active', true)->first()?->id),
            'date_from' => $request->input('date_from', now()->startOfMonth()->format('Y-m-d')),
            'date_to' => $request->input('date_to', now()->format('Y-m-d')),
            'classroom_id' => $request->input('classroom_id'),
            'type_id' => $request->input('type_id'),
        ];
    }

    public function getReportData($filters)
    {
        return [
            'summary' => $this->getSummaryData($filters['academic_year_id']),
            'per_student' => $this->getStudentReport($filters),
            'per_type' => $this->getTypeReport($filters['academic_year_id']),
            'transactions' => $this->getTransactionReport($filters),
            'transaction_summary' => $this->getTransactionSummary($filters),
        ];
    }

    private function getSummaryData($academicYearId)
    {
        $query = Payment::query()
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId));

        $totalAmount = $query->sum('total_amount');
        $totalPaid = $query->sum('paid_amount');
        $totalUnpaid = $totalAmount - $totalPaid;

        $byStatus = [
            'lunas' => $query->clone()->where('payment_status', 'lunas')->count(),
            'sebagian' => $query->clone()->where('payment_status', 'sebagian')->count(),
            'belum_bayar' => $query->clone()->where('payment_status', 'belum_bayar')->count(),
        ];

        $byType = Payment::query()
            ->select('payment_type_id', DB::raw('SUM(total_amount) as total'), DB::raw('SUM(paid_amount) as paid'), DB::raw('COUNT(*) as count'))
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->groupBy('payment_type_id')
            ->with('paymentType')
            ->get();

        $byMonth = Payment::query()
            ->select('month', DB::raw('SUM(total_amount) as total'), DB::raw('SUM(paid_amount) as paid'))
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
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

    private function getStudentReport($filters)
    {
        return Payment::query()
            ->select('student_id', DB::raw('SUM(total_amount) as total'), DB::raw('SUM(paid_amount) as paid'), DB::raw('COUNT(*) as invoice_count'))
            ->with(['student.classroom'])
            ->when($filters['academic_year_id'], fn($q) => $q->where('academic_year_id', $filters['academic_year_id']))
            ->when($filters['classroom_id'], fn($q) => $q->whereHas('student', fn($s) => $s->where('classroom_id', $filters['classroom_id'])))
            ->when($filters['type_id'], fn($q) => $q->where('payment_type_id', $filters['type_id']))
            ->groupBy('student_id')
            ->orderByRaw('(SUM(total_amount) - SUM(paid_amount)) DESC')
            ->get(); // Using get() instead of paginate for export
    }

    private function getTypeReport($academicYearId)
    {
        return PaymentType::query()
            ->withCount(['payments' => function($q) use ($academicYearId) {
                $q->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId));
            }])
            ->withSum(['payments' => function($q) use ($academicYearId) {
                $q->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId));
            }], 'total_amount')
            ->withSum(['payments' => function($q) use ($academicYearId) {
                $q->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId));
            }], 'paid_amount')
            ->orderBy('name')
            ->get();
    }

    private function getTransactionReport($filters)
    {
        return PaymentTransaction::query()
            ->with(['payment.student', 'payment.paymentType', 'receiver'])
            ->when($filters['date_from'], fn($q) => $q->whereDate('payment_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn($q) => $q->whereDate('payment_date', '<=', $filters['date_to']))
            ->when($filters['type_id'], fn($q) => $q->whereHas('payment', fn($p) => $p->where('payment_type_id', $filters['type_id'])))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get(); // Using get() instead of paginate for export
    }

    private function getTransactionSummary($filters)
    {
        $query = PaymentTransaction::query()
            ->when($filters['date_from'], fn($q) => $q->whereDate('payment_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn($q) => $q->whereDate('payment_date', '<=', $filters['date_to']))
            ->when($filters['type_id'], fn($q) => $q->whereHas('payment', fn($p) => $p->where('payment_type_id', $filters['type_id'])));

        return [
            'total' => $query->sum('amount'),
            'count' => $query->count(),
            'by_method' => PaymentTransaction::query()
                ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->when($filters['date_from'], fn($q) => $q->whereDate('payment_date', '>=', $filters['date_from']))
                ->when($filters['date_to'], fn($q) => $q->whereDate('payment_date', '<=', $filters['date_to']))
                ->when($filters['type_id'], fn($q) => $q->whereHas('payment', fn($p) => $p->where('payment_type_id', $filters['type_id'])))
                ->groupBy('payment_method')
                ->get(),
        ];
    }
}
