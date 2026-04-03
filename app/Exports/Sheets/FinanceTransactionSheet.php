<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinanceTransactionSheet implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
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
            $row->receipt_number,
            $row->payment_date->format('d/m/Y'),
            $row->payment?->student?->name ?? '-',
            $row->payment?->student?->nis ?? '-',
            $row->payment?->paymentType?->name ?? '-',
            \App\Models\PaymentTransaction::PAYMENT_METHODS[$row->payment_method] ?? $row->payment_method,
            $row->amount,
            $row->receiver?->name ?? '-',
            $row->notes ?? '-',
        ];
    }

    public function headings(): array
    {
        return [
            ['LAPORAN TRANSAKSI PEMBAYARAN'],
            ['NO. KWITANSI', 'TANGGAL', 'NAMA SISWA', 'NIS', 'JENIS PEMBAYARAN', 'METODE', 'JUMLAH (Rp)', 'PENERIMA (ADMIN)', 'CATATAN'],
        ];
    }

    public function title(): string
    {
        return 'Transaksi';
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
            'A' => 20,
            'B' => 15,
            'C' => 30,
            'D' => 15,
            'E' => 25,
            'F' => 15,
            'G' => 15,
            'H' => 25,
            'I' => 30,
        ];
    }
}
