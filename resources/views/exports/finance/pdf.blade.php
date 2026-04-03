<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .school-name { font-size: 20px; font-weight: bold; margin: 0; }
        .school-address { font-size: 12px; margin: 5px 0 0 0; }
        .title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .success { color: #16a34a; }
        .danger { color: #dc2626; }
        .warning { color: #ca8a04; }
        .section-title { font-size: 14px; font-weight: bold; border-bottom: 1px solid #eee; margin-top: 30px; padding-bottom: 5px; margin-bottom: 15px; }
        
        /* Summary Cards styling for PDF */
        .summary-grid { width: 100%; margin-bottom: 20px; display: table; }
        .summary-card { display: table-cell; width: 25%; padding: 10px; border: 1px solid #ddd; text-align: center; }
        .summary-label { font-size: 10px; color: #666; text-transform: uppercase; }
        .summary-value { font-size: 16px; font-weight: bold; margin-top: 5px; }
        .page-break { page-break-after: always; }
        .footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 30px; font-size: 10px; text-align: center; color: #777; border-top: 1px solid #ddd; padding-top: 5px; }
        .logo-placeholder { float: left; width: 60px; height: 60px; /* fallback */ }
    </style>
</head>
<body>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }} - Sistem Admin Tata Usaha Sekolah
    </div>

    <!-- KOP SURAT -->
    <div class="header">
        <div style="float: left; width: 20%; text-align: center;">
            @php
                // Try to find if user has logo path or use basic SVG
            @endphp
            <div style="display:inline-block; border: 1px solid #ccc; border-radius: 8px; padding: 10px;  width: 50px; height: 50px; line-height: 50px; text-align:center;">
                LOGO
            </div>
        </div>
        <div style="float: right; width: 80%;">
            <h1 class="school-name">{{ config('app.name', 'Tata Usaha SMK') }}</h1>
            <p class="school-address">Alamat: Jl. Raya Pendidikan No. 1, Kota Pelajar<br>Email: info@sekolah.sch.id | Telp: (021) 1234567</p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="title">LAPORAN KEUANGAN SEKOLAH</div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">Total Tagihan</div>
            <div class="summary-value" style="color: #2563eb;">Rp {{ number_format($data['summary']['total_amount'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total Terbayar</div>
            <div class="summary-value success">Rp {{ number_format($data['summary']['total_paid'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Sisa Belum Bayar</div>
            <div class="summary-value danger">Rp {{ number_format($data['summary']['total_unpaid'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Persentase Terbayar</div>
            <div class="summary-value {{ $data['summary']['percentage_paid'] >= 75 ? 'success' : ($data['summary']['percentage_paid'] >= 50 ? 'warning' : 'danger') }}">
                {{ $data['summary']['percentage_paid'] }}%
            </div>
        </div>
    </div>

    <!-- Bagian 1: Ringkasan Status -->
    <div class="section-title">1. RINGKASAN DISTRIBUSI STATUS</div>
    <table>
        <thead>
            <tr>
                <th>Status Pembayaran</th>
                <th class="text-center">Jumlah Kasus</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Lunas</td>
                <td class="text-center">{{ number_format($data['summary']['by_status']['lunas']) }}</td>
            </tr>
            <tr>
                <td>Bayar Sebagian</td>
                <td class="text-center">{{ number_format($data['summary']['by_status']['sebagian']) }}</td>
            </tr>
            <tr>
                <td>Belum Bayar</td>
                <td class="text-center">{{ number_format($data['summary']['by_status']['belum_bayar']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- Bagian 2: Laporan Per Jenis -->
    <div class="section-title">2. LAPORAN PER JENIS PEMBAYARAN</div>
    <table>
        <thead>
            <tr>
                <th>Jenis Pembayaran</th>
                <th class="text-center">Total Item</th>
                <th class="text-right">Total Tagihan</th>
                <th class="text-right">Terbayar</th>
                <th class="text-right">Sisa Tagihan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['per_type'] as $type)
                @php
                    $total = $type->payments_sum_total_amount ?? 0;
                    $paid = $type->payments_sum_paid_amount ?? 0;
                @endphp
                <tr>
                    <td>{{ $type->name }} {{ $type->is_recurring ? '(Bln)' : '' }}</td>
                    <td class="text-center">{{ $type->payments_count ?? 0 }}</td>
                    <td class="text-right">Rp{{ number_format($total, 0, ',', '.') }}</td>
                    <td class="text-right success">Rp{{ number_format($paid, 0, ',', '.') }}</td>
                    <td class="text-right danger">Rp{{ number_format($total - $paid, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">Tidak ada data pembayaran.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- Bagian 3: Laporan TRANSAKSI -->
    <div class="section-title">3. DAFTAR TRANSAKSI PEMBAYARAN (Terbaru)</div>
    <table>
        <thead>
            <tr>
                <th>No. Kwitansi</th>
                <th>Tanggal</th>
                <th>Siswa</th>
                <th>Siswa NIS</th>
                <th>Jumlah</th>
                <th>Metode</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['transactions']->take(50) as $tx) <!-- Limit to 50 for PDF so it doesn't break server easily -->
                <tr>
                    <td>{{ $tx->receipt_number }}</td>
                    <td>{{ $tx->payment_date->format('d/m/Y') }}</td>
                    <td>{{ $tx->payment?->student?->name ?? '-' }}</td>
                    <td>{{ $tx->payment?->student?->nis ?? '-' }}</td>
                    <td class="text-right success">Rp{{ number_format($tx->amount, 0, ',', '.') }}</td>
                    <td class="text-center">{{ \App\Models\PaymentTransaction::PAYMENT_METHODS[$tx->payment_method] ?? $tx->payment_method }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if($data['transactions']->count() > 50)
        <p style="font-size: 10px; color: #666; text-align: center;">*Hanya menampilkan 50 transaksi terbaru. Untuk data selengkapnya silahkan unduh laporan Excel.</p>
    @endif
    
    <!-- Signatures -->
    <div style="margin-top: 50px; text-align: right; margin-right: 50px;">
        <p>Mengetahui,</p>
        <p style="margin-bottom: 70px;">Kepala Tata Usaha</p>
        <p style="font-weight: bold; text-decoration: underline;">( .................................................. )</p>
        <p>NIP. .........................................</p>
    </div>

</body>
</html>
