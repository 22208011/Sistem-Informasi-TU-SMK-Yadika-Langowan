<?php

namespace Database\Seeders;

use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IncomingLetterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user for received_by
        $adminUser = User::where('email', 'admin@smk.sch.id')->first();
        $taUser = User::where('email', 'tata.usaha@smk.sch.id')->first();
        $receiver = $adminUser ?? $taUser ?? User::first();

        $incomingLetters = [
            [
                'agenda_number' => 'SM/03/2026/0001',
                'letter_number' => '421.3/DP-SULUT/2025/1234',
                'letter_date' => '2026-02-20',
                'received_date' => '2026-02-22',
                'sender' => 'Dinas Pendidikan Provinsi Sulawesi Utara',
                'sender_address' => 'Jl. Babe Palar No. 5, Manado',
                'subject' => 'Undangan Rapat Koordinasi Kepala SMK Se-Sulut',
                'classification' => IncomingLetter::CLASS_DINAS_PENDIDIKAN,
                'nature' => IncomingLetter::NATURE_PENTING,
                'attachment_count' => 1,
                'attachment_type' => 'Jadwal Acara',
                'disposition' => 'Untuk dihadiri oleh Kepala Sekolah',
                'disposition_to' => 'Kepala Sekolah',
                'notes' => 'Rapat tanggal 1 Maret 2026',
                'received_by' => $receiver?->id,
                'status' => IncomingLetter::STATUS_DISPOSITIONED,
            ],
            [
                'agenda_number' => 'SM/03/2026/0002',
                'letter_number' => 'YYS/001/II/2026',
                'letter_date' => '2026-02-18',
                'received_date' => '2026-02-20',
                'sender' => 'Yayasan Yadika',
                'sender_address' => 'Jakarta Pusat',
                'subject' => 'Pemberitahuan Kunjungan Pengawas Yayasan',
                'classification' => IncomingLetter::CLASS_YAYASAN,
                'nature' => IncomingLetter::NATURE_PENTING,
                'attachment_count' => 2,
                'attachment_type' => 'Daftar Tim & Jadwal',
                'disposition' => 'Siapkan laporan dan dokumentasi',
                'disposition_to' => 'Wakil Kepala Sekolah',
                'notes' => 'Kunjungan tanggal 10-12 Maret 2026',
                'received_by' => $receiver?->id,
                'status' => IncomingLetter::STATUS_PROCESSING,
            ],
            [
                'agenda_number' => 'SM/03/2026/0003',
                'letter_number' => 'PT.ABC/HRD/003/2026',
                'letter_date' => '2026-02-15',
                'received_date' => '2026-02-18',
                'sender' => 'PT. Astra Business Center',
                'sender_address' => 'Jl. Industri Raya No. 88, Manado',
                'subject' => 'Penawaran Kerjasama Program Magang SMK',
                'classification' => IncomingLetter::CLASS_PERUSAHAAN,
                'nature' => IncomingLetter::NATURE_BIASA,
                'attachment_count' => 3,
                'attachment_type' => 'Proposal MoU, Daftar Posisi, Company Profile',
                'disposition' => 'Tindak lanjuti dengan Kabid Humas',
                'disposition_to' => 'Humas',
                'notes' => 'Kuota maksimal 20 siswa',
                'received_by' => $receiver?->id,
                'status' => IncomingLetter::STATUS_COMPLETED,
            ],
            [
                'agenda_number' => 'SM/03/2026/0004',
                'letter_number' => '-',
                'letter_date' => '2026-02-25',
                'received_date' => '2026-02-26',
                'sender' => 'Orang Tua/Wali Ahmad Fauzan',
                'sender_address' => 'Langowan, Minahasa',
                'subject' => 'Permohonan Izin Tidak Masuk Sekolah',
                'classification' => IncomingLetter::CLASS_ORANG_TUA,
                'nature' => IncomingLetter::NATURE_BIASA,
                'attachment_count' => 1,
                'attachment_type' => 'Surat Keterangan Dokter',
                'disposition' => 'Arsipkan dan catat kehadiran',
                'disposition_to' => 'Wali Kelas',
                'notes' => 'Izin sakit 3 hari',
                'received_by' => $receiver?->id,
                'status' => IncomingLetter::STATUS_ARCHIVED,
            ],
            [
                'agenda_number' => 'SM/03/2026/0005',
                'letter_number' => 'SMAN-1-LNG/100/II/2026',
                'letter_date' => '2026-02-23',
                'received_date' => '2026-02-25',
                'sender' => 'SMAN 1 Langowan',
                'sender_address' => 'Jl. Pendidikan No. 1, Langowan',
                'subject' => 'Undangan Pertandingan Futsal antar SMA/SMK',
                'classification' => IncomingLetter::CLASS_INSTANSI_LAIN,
                'nature' => IncomingLetter::NATURE_BIASA,
                'attachment_count' => 1,
                'attachment_type' => 'Peraturan Pertandingan',
                'disposition' => 'Koordinasikan dengan pembina ekskul',
                'disposition_to' => 'Wakil Kesiswaan',
                'notes' => 'Pertandingan tanggal 15 Maret 2026',
                'received_by' => $receiver?->id,
                'status' => IncomingLetter::STATUS_DISPOSITIONED,
            ],
            [
                'agenda_number' => 'SM/03/2026/0006',
                'letter_number' => '421.4/DP-SULUT/2026/0567',
                'letter_date' => '2026-03-01',
                'received_date' => '2026-03-03',
                'sender' => 'Dinas Pendidikan Provinsi Sulawesi Utara',
                'sender_address' => 'Jl. Babe Palar No. 5, Manado',
                'subject' => 'Surat Edaran Dana BOS Triwulan 1 Tahun 2026',
                'classification' => IncomingLetter::CLASS_DINAS_PENDIDIKAN,
                'nature' => IncomingLetter::NATURE_PENTING,
                'attachment_count' => 2,
                'attachment_type' => 'Petunjuk Teknis, Form Pelaporan',
                'disposition' => 'Segera proses sesuai Juknis',
                'disposition_to' => 'Bendahara Sekolah',
                'notes' => 'Deadline pelaporan 31 Maret 2026',
                'received_by' => $receiver?->id,
                'status' => IncomingLetter::STATUS_PROCESSING,
            ],
            [
                'agenda_number' => 'SM/03/2026/0007',
                'letter_number' => 'CV.MIS/MKT/021/2026',
                'letter_date' => '2026-02-28',
                'received_date' => '2026-03-02',
                'sender' => 'CV. Mitra Informatika Sulut',
                'sender_address' => 'Jl. Sam Ratulangi No. 45, Manado',
                'subject' => 'Penawaran Pengadaan Komputer Lab',
                'classification' => IncomingLetter::CLASS_PERUSAHAAN,
                'nature' => IncomingLetter::NATURE_BIASA,
                'attachment_count' => 1,
                'attachment_type' => 'Daftar Harga & Spesifikasi',
                'disposition' => null,
                'disposition_to' => null,
                'notes' => 'Untuk pertimbangan pengadaan',
                'received_by' => $receiver?->id,
                'status' => IncomingLetter::STATUS_RECEIVED,
            ],
            [
                'agenda_number' => 'SM/03/2026/0008',
                'letter_number' => 'POLRES-MNH/PAM/078/2026',
                'letter_date' => '2026-03-04',
                'received_date' => '2026-03-05',
                'sender' => 'Polres Minahasa',
                'sender_address' => 'Jl. Raya Tondano, Minahasa',
                'subject' => 'Pemberitahuan Sosialisasi Anti Narkoba',
                'classification' => IncomingLetter::CLASS_INSTANSI_LAIN,
                'nature' => IncomingLetter::NATURE_PENTING,
                'attachment_count' => 1,
                'attachment_type' => 'Materi Sosialisasi',
                'disposition' => 'Siapkan tempat dan undang seluruh siswa',
                'disposition_to' => 'Wakil Kesiswaan',
                'notes' => 'Sosialisasi tanggal 20 Maret 2026',
                'received_by' => $receiver?->id,
                'status' => IncomingLetter::STATUS_DISPOSITIONED,
            ],
        ];

        DB::beginTransaction();
        try {
            foreach ($incomingLetters as $letter) {
                IncomingLetter::create($letter);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
