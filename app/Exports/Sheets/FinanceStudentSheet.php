<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinanceStudentSheet implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return [
            $row->student?->nis ?? '-',
            $row->student?->name ?? 'Terhapus',
            $row->student?->classroom?->name ?? '-',
            $row->invoice_count,
            $row->total,
            $row->paid,
            $row->total - $row->paid, // Sisa Tagihan
        ];
    }

    public function headings(): array
    {
        return [
            ['LAPORAN KEUANGAN PER SISWA'],
            ['NIS', 'NAMA SISWA', 'KELAS', 'JML TAGIHAN', 'TOTAL TAGIHAN (Rp)', 'TOTAL TERBAYAR (Rp)', 'SISA TAGIHAN (Rp)'],
        ];
    }

    public function title(): string
    {
        return 'Per Siswa';
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
            'D' => 15,
            'E' => 20,
            'F' => 20,
            'G' => 20,
        ];
    }
}
