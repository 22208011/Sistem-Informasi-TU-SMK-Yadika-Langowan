<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Http\Controllers\FinanceExportController;

class FinanceReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $controller = new FinanceExportController();
        $data = $controller->getReportData($this->filters);

        $sheets = [];

        $sheets[] = new Sheets\FinanceSummarySheet($data['summary']);
        $sheets[] = new Sheets\FinanceStudentSheet($data['per_student']);
        $sheets[] = new Sheets\FinanceTypeSheet($data['per_type']);
        $sheets[] = new Sheets\FinanceTransactionSheet($data['transactions']);

        return $sheets;
    }
}
