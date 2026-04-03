<?php

namespace Database\Seeders;

use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OutgoingLetterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users for created_by and signed_by
        $adminUser = User::where('email', 'admin@smk.sch.id')->first();
        $taUser = User::where('email', 'tata.usaha@smk.sch.id')->first();
        $kepsekUser = User::where('email', 'kepala.sekolah@smk.sch.id')->first();
        
        $creator = $adminUser ?? $taUser ?? User::first();
        $signer = $kepsekUser ?? $adminUser ?? User::first();

        $outgoingLetters = [
            [
                'agenda_number' => 'SK/03/2026/0001',
                'letter_number' => '001/SMK-YL/III/2026',
                'letter_date' => '2026-03-01',
                'sent_date' => '2026-03-02',
                'recipient' => 'Dinas Pendidikan Provinsi Sulawesi Utara',
                'recipient_address' => 'Jl. Babe Palar No. 5, Manado',
                'subject' => 'Laporan Kegiatan Semester Ganjil Tahun 2025/2026',
                'classification' => OutgoingLetter::CLASS_DINAS_PENDIDIKAN,
                'nature' => OutgoingLetter::NATURE_PENTING,
                'attachment_count' => 5,
                'attachment_type' => 'Laporan Lengkap, Data Siswa, Rekapitulasi',
                'content_summary' => 'Penyampaian laporan kegiatan belajar mengajar semester ganjil tahun ajaran 2025/2026 beserta data pendukung.',
                'notes' => 'Dikirim via pos tercatat',
                'created_by' => $creator?->id,
                'signed_by' => $signer?->id,
                'status' => OutgoingLetter::STATUS_SENT,
            ],
            [
                'agenda_number' => 'SK/03/2026/0002',
                'letter_number' => '002/SMK-YL/III/2026',
                'letter_date' => '2026-03-03',
                'sent_date' => '2026-03-04',
                'recipient' => 'Yayasan Yadika Pusat',
                'recipient_address' => 'Jakarta Pusat',
                'subject' => 'Laporan Keuangan Triwulan I Tahun 2026',
                'classification' => OutgoingLetter::CLASS_YAYASAN,
                'nature' => OutgoingLetter::NATURE_PENTING,
                'attachment_count' => 3,
                'attachment_type' => 'Laporan Keuangan, Bukti Transaksi',
                'content_summary' => 'Penyampaian laporan pertanggungjawaban keuangan triwulan pertama tahun 2026.',
                'notes' => 'Dikirim via email dan hardcopy',
                'created_by' => $creator?->id,
                'signed_by' => $signer?->id,
                'status' => OutgoingLetter::STATUS_SENT,
            ],
            [
                'agenda_number' => 'SK/03/2026/0003',
                'letter_number' => '003/SMK-YL/III/2026',
                'letter_date' => '2026-03-05',
                'sent_date' => null,
                'recipient' => 'PT. Astra Business Center',
                'recipient_address' => 'Jl. Industri Raya No. 88, Manado',
                'subject' => 'Balasan Penawaran Kerjasama Program Magang',
                'classification' => OutgoingLetter::CLASS_PERUSAHAAN,
                'nature' => OutgoingLetter::NATURE_BIASA,
                'attachment_count' => 2,
                'attachment_type' => 'Draft MoU, Daftar Calon Peserta PKL',
                'content_summary' => 'Persetujuan kerjasama program magang dengan mengirimkan 15 siswa kelas XI jurusan TKJ dan RPL.',
                'notes' => 'Menunggu tanda tangan Kepala Sekolah',
                'created_by' => $creator?->id,
                'signed_by' => null,
                'status' => OutgoingLetter::STATUS_PENDING,
            ],
            [
                'agenda_number' => 'SK/03/2026/0004',
                'letter_number' => '004/SMK-YL/III/2026',
                'letter_date' => '2026-03-05',
                'sent_date' => null,
                'recipient' => 'Seluruh Orang Tua/Wali Siswa Kelas XII',
                'recipient_address' => '-',
                'subject' => 'Undangan Rapat Persiapan Ujian Sekolah',
                'classification' => OutgoingLetter::CLASS_ORANG_TUA,
                'nature' => OutgoingLetter::NATURE_PENTING,
                'attachment_count' => 1,
                'attachment_type' => 'Jadwal Ujian',
                'content_summary' => 'Mengundang seluruh orang tua/wali siswa kelas XII untuk menghadiri rapat persiapan ujian sekolah pada tanggal 15 Maret 2026.',
                'notes' => 'Dikirim melalui siswa',
                'created_by' => $creator?->id,
                'signed_by' => $signer?->id,
                'status' => OutgoingLetter::STATUS_APPROVED,
            ],
            [
                'agenda_number' => 'SK/03/2026/0005',
                'letter_number' => '005/SMK-YL/III/2026',
                'letter_date' => '2026-03-06',
                'sent_date' => '2026-03-06',
                'recipient' => 'SMAN 1 Langowan',
                'recipient_address' => 'Jl. Pendidikan No. 1, Langowan',
                'subject' => 'Konfirmasi Keikutsertaan Pertandingan Futsal',
                'classification' => OutgoingLetter::CLASS_INSTANSI_LAIN,
                'nature' => OutgoingLetter::NATURE_BIASA,
                'attachment_count' => 1,
                'attachment_type' => 'Daftar Pemain',
                'content_summary' => 'Konfirmasi keikutsertaan tim futsal SMK Yadika Langowan dalam pertandingan futsal antar SMA/SMK.',
                'notes' => 'Dikirim via WA dan hardcopy',
                'created_by' => $creator?->id,
                'signed_by' => $signer?->id,
                'status' => OutgoingLetter::STATUS_SENT,
            ],
            [
                'agenda_number' => 'SK/03/2026/0006',
                'letter_number' => '006/SMK-YL/III/2026',
                'letter_date' => '2026-03-07',
                'sent_date' => null,
                'recipient' => 'CV. Mitra Informatika Sulut',
                'recipient_address' => 'Jl. Sam Ratulangi No. 45, Manado',
                'subject' => 'Permohonan Penawaran Pengadaan Komputer',
                'classification' => OutgoingLetter::CLASS_PERUSAHAAN,
                'nature' => OutgoingLetter::NATURE_BIASA,
                'attachment_count' => 1,
                'attachment_type' => 'Spesifikasi Kebutuhan',
                'content_summary' => 'Permohonan penawaran harga untuk pengadaan 20 unit komputer untuk laboratorium RPL.',
                'notes' => null,
                'created_by' => $creator?->id,
                'signed_by' => null,
                'status' => OutgoingLetter::STATUS_DRAFT,
            ],
            [
                'agenda_number' => 'SK/03/2026/0007',
                'letter_number' => '007/SMK-YL/III/2026',
                'letter_date' => '2026-03-07',
                'sent_date' => null,
                'recipient' => 'Polres Minahasa',
                'recipient_address' => 'Jl. Raya Tondano, Minahasa',
                'subject' => 'Konfirmasi Kegiatan Sosialisasi Anti Narkoba',
                'classification' => OutgoingLetter::CLASS_INSTANSI_LAIN,
                'nature' => OutgoingLetter::NATURE_BIASA,
                'attachment_count' => 0,
                'attachment_type' => null,
                'content_summary' => 'Konfirmasi kesediaan menerima tim sosialisasi anti narkoba dari Polres Minahasa pada tanggal 20 Maret 2026.',
                'notes' => 'Perlu jadwal ulang karena bentrok dengan kegiatan',
                'created_by' => $creator?->id,
                'signed_by' => null,
                'status' => OutgoingLetter::STATUS_DRAFT,
            ],
            [
                'agenda_number' => 'SK/03/2026/0008',
                'letter_number' => '008/SMK-YL/III/2026',
                'letter_date' => '2026-03-08',
                'sent_date' => '2026-03-08',
                'recipient' => 'Ikatan Alumni SMK Yadika Langowan',
                'recipient_address' => 'Langowan, Minahasa',
                'subject' => 'Undangan Reuni dan Bakti Sosial',
                'classification' => OutgoingLetter::CLASS_ALUMNI,
                'nature' => OutgoingLetter::NATURE_BIASA,
                'attachment_count' => 2,
                'attachment_type' => 'Rundown Acara, Form Pendaftaran',
                'content_summary' => 'Mengundang seluruh alumni untuk menghadiri acara reuni dan bakti sosial dalam rangka HUT Sekolah ke-25.',
                'notes' => 'Disebarkan via media sosial dan email',
                'created_by' => $creator?->id,
                'signed_by' => $signer?->id,
                'status' => OutgoingLetter::STATUS_SENT,
            ],
        ];

        DB::beginTransaction();
        try {
            foreach ($outgoingLetters as $letter) {
                OutgoingLetter::create($letter);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
