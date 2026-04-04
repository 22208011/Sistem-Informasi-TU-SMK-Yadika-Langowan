<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Laporan Resmi' }}</title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
        }
        .kop-surat {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .kop-surat h1 {
            font-size: 16pt;
            margin: 0;
            padding: 0;
            text-transform: uppercase;
        }
        .kop-surat h2 {
            font-size: 18pt;
            font-weight: bold;
            margin: 5px 0;
            padding: 0;
            text-transform: uppercase;
        }
        .kop-surat p {
            font-size: 11pt;
            margin: 0;
            padding: 0;
        }
        .report-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: left;
            font-size: 11pt;
        }
        table.data-table th {
            font-weight: bold;
            text-align: center;
            background-color: #f2f2f2;
        }
        .signature-block {
            width: 100%;
            margin-top: 40px;
        }
        .signature-table {
            width: 100%;
            border: none;
        }
        .signature-table td {
            border: none;
            text-align: center;
            vertical-align: top;
            width: 33%;
        }
        .signature-box {
            margin-top: 70px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <!-- Format ini telah disesuaikan agar rapi di baca pada Ms. Word dan PDF -->
    @php
        $logoYadika = '';
        $pathsYadika = [
            public_path('images/logo-yadika.png'),
        ];
        foreach ($pathsYadika as $path) {
            if(file_exists($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'jpeg' : 'png';
                $logoYadika = 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                break;
            }
        }

        $logoTutwuri = '';
        $pathsTutwuri = [
            public_path('images/tutwuri handayani.png'),
            public_path('images/tutwuri.png'),
            public_path('images/tut wuri.png')
        ];
        foreach ($pathsTutwuri as $path) {
            if(file_exists($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'jpeg' : 'png';
                $logoTutwuri = 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                break;
            }
        }
    @endphp

    <table style="width: 100%; border-bottom: 3px solid #000; margin-bottom: 20px;">
        <tr>
            <td style="width: 15%; text-align: left; vertical-align: middle; border: none; padding-bottom: 10px;">
                @if($logoYadika)
                    <img src="{{ $logoYadika }}" style="width: 90px; height: auto;">
                @endif
            </td>
            <td style="width: 70%; text-align: center; vertical-align: middle; border: none; padding-bottom: 10px;">
                <h1 style="font-size: 14pt; margin: 0; padding: 0; text-transform: uppercase;">YAYASAN PENDIDIKAN ABDI KARYA JAKARTA (YADIKA)</h1>
                <h2 style="font-size: 18pt; font-weight: bold; margin: 6px 0; padding: 0; text-transform: uppercase; color: #1e3a8a;">SMK YADIKA LANGOWAN</h2>
                <p style="font-size: 11pt; margin: 0; padding: 0;">Jl.Desa Waleure Kecamatan Langowan Kabupaten Minahasa, Sulawesi Utara.</p>
                <p style="font-size: 11pt; margin: 0; padding: 0; font-style: italic;">Telp.0431373346; Fax.0431373347 Email : yadika.langowan@gmail.com KodePos : 95694</p>
            </td>
            <td style="width: 15%; text-align: right; vertical-align: middle; border: none; padding-bottom: 10px;">
                @if($logoTutwuri)
                    <img src="{{ $logoTutwuri }}" style="width: 90px; height: auto;">
                @endif
            </td>
        </tr>
    </table>

    <div class="report-title">
        {{ $title ?? 'LAPORAN' }}
        @if(isset($subtitle))
            <br><span style="font-size: 11pt; font-weight: normal;">{{ $subtitle }}</span>
        @endif
    </div>

    @yield('content')

    <div class="signature-block">
        <table class="signature-table">
            <tr>
                <td></td>
                <td></td>
                <td>
                    Langowan, {{ now()->translatedFormat('d F Y') }}<br>
                    {{ $signerTitle ?? 'Kepala Sekolah' }}
                    <div class="signature-box">
                        <strong>( ......................................... )</strong><br>
                        @if(isset($signerNip))
                            NIP: {{ $signerNip }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
