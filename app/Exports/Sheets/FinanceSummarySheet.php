<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinanceSummarySheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows = [];
        
        $rows[] = ['Total Tagihan', $this->data['total_amount']];
        $rows[] = ['Total Terbayar', $this->data['total_paid']];
        $rows[] = ['Sisa Belum Bayar', $this->data['total_unpaid']];
        $rows[] = ['Persentase Lunas', $this->data['percentage_paid'] . '%'];
        $rows[] = ['', ''];
        
        $rows[] = ['DISTRIBUSI STATUS', 'JUMLAH'];
        $rows[] = ['Lunas', $this->data['by_status']['lunas']];
        $rows[] = ['Bayar Sebagian', $this->data['by_status']['sebagian']];
        $rows[] = ['Belum Bayar', $this->data['by_status']['belum_bayar']];
        
        return $rows;
    }

    public function headings(): array
    {
        return [
            ['RINGKASAN LAPORAN KEUANGAN'],
            ['METRIK', 'NILAI']
        ];
    }

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            7 => ['font' => ['bold' => true]], // DISTRIBUSI STATUS
            'A' => ['font' => ['bold' => true]],
        ];
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 20,
        ];
    }
}
