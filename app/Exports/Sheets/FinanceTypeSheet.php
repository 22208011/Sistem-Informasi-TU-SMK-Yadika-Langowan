<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinanceTypeSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    private $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data;
    }

    public function map($row): array
    {
        $total = $row->payments_sum_total_amount ?? 0;
        $paid = $row->payments_sum_paid_amount ?? 0;
        $remaining = $total - $paid;
        $percentage = $total > 0 ? round(($paid / $total) * 100, 1) : 0;

        return [
            $row->code,
            $row->name . ($row->is_recurring ? ' (Bulanan)' : ''),
            $row->payments_count ?? 0,
            $total,
            $paid,
            $remaining,
            $percentage . '%',
        ];
    }

    public function headings(): array
    {
        return [
            ['LAPORAN KEUANGAN PER JENIS'],
            ['KODE', 'JENIS PEMBAYARAN', 'JML TAGIHAN', 'TOTAL TAGIHAN (Rp)', 'TOTAL TERBAYAR (Rp)', 'SISA TAGIHAN (Rp)', 'PERSENTASE TERBAYAR']
        ];
    }

    public function title(): string
    {
        return 'Per Jenis';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
        ];
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 35,
            'C' => 15,
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 25,
        ];
    }
}
