<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            // Jabatan Struktural
            [
                'code' => 'KS',
                'name' => 'Kepala Sekolah',
                'category' => Position::CATEGORY_STRUKTURAL,
                'description' => 'Pimpinan tertinggi sekolah',
            ],
            [
                'code' => 'WKS1',
                'name' => 'Wakil Kepala Sekolah Bidang Kurikulum',
                'category' => Position::CATEGORY_STRUKTURAL,
                'description' => 'Membidangi kurikulum dan pembelajaran',
            ],
            [
                'code' => 'WKS2',
                'name' => 'Wakil Kepala Sekolah Bidang Kesiswaan',
                'category' => Position::CATEGORY_STRUKTURAL,
                'description' => 'Membidangi kesiswaan dan kegiatan ekstrakurikuler',
            ],
            [
                'code' => 'WKS3',
                'name' => 'Wakil Kepala Sekolah Bidang Sarana Prasarana',
                'category' => Position::CATEGORY_STRUKTURAL,
                'description' => 'Membidangi sarana dan prasarana sekolah',
            ],
            [
                'code' => 'WKS4',
                'name' => 'Wakil Kepala Sekolah Bidang Hubungan Industri',
                'category' => Position::CATEGORY_STRUKTURAL,
                'description' => 'Membidangi hubungan industri dan kerjasama',
            ],
            [
                'code' => 'KTU',
                'name' => 'Kepala Tata Usaha',
                'category' => Position::CATEGORY_STRUKTURAL,
                'description' => 'Pimpinan bagian tata usaha',
            ],
            [
                'code' => 'KAJUR',
                'name' => 'Ketua Jurusan/Kompetensi Keahlian',
                'category' => Position::CATEGORY_STRUKTURAL,
                'description' => 'Pimpinan program keahlian',
            ],
            [
                'code' => 'KAPROG',
                'name' => 'Kepala Program',
                'category' => Position::CATEGORY_STRUKTURAL,
                'description' => 'Koordinator program keahlian',
            ],
            [
                'code' => 'KORBP',
                'name' => 'Koordinator Bimbingan Konseling',
                'category' => Position::CATEGORY_STRUKTURAL,
                'description' => 'Koordinator layanan bimbingan dan konseling',
            ],
            [
                'code' => 'KORPUS',
                'name' => 'Koordinator Perpustakaan',
                'category' => Position::CATEGORY_STRUKTURAL,
                'description' => 'Koordinator perpustakaan sekolah',
            ],

            // Jabatan Fungsional
            [
                'code' => 'GURU',
                'name' => 'Guru Mata Pelajaran',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Guru pengajar mata pelajaran umum',
            ],
            [
                'code' => 'GPROD',
                'name' => 'Guru Produktif',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Guru pengajar mata pelajaran produktif/kejuruan',
            ],
            [
                'code' => 'GPKN',
                'name' => 'Guru Pendidikan Kewarganegaraan',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Guru pengajar PKn',
            ],
            [
                'code' => 'GBK',
                'name' => 'Guru Bimbingan Konseling',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Guru pembimbing/konselor',
            ],
            [
                'code' => 'WKLS',
                'name' => 'Wali Kelas',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Guru yang membimbing satu kelas',
            ],
            [
                'code' => 'STAFF',
                'name' => 'Staf Tata Usaha',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Staf administrasi tata usaha',
            ],
            [
                'code' => 'BEND',
                'name' => 'Bendahara',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Pengelola keuangan sekolah',
            ],
            [
                'code' => 'PUST',
                'name' => 'Pustakawan',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Pengelola perpustakaan',
            ],
            [
                'code' => 'LAB',
                'name' => 'Laboran',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Pengelola laboratorium',
            ],
            [
                'code' => 'TOOL',
                'name' => 'Toolman',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Pengelola alat dan bengkel',
            ],
            [
                'code' => 'OB',
                'name' => 'Office Boy/Girl',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Petugas kebersihan',
            ],
            [
                'code' => 'SAT',
                'name' => 'Satpam',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Petugas keamanan',
            ],
            [
                'code' => 'SUPIR',
                'name' => 'Pengemudi',
                'category' => Position::CATEGORY_FUNGSIONAL,
                'description' => 'Pengemudi kendaraan dinas',
            ],
        ];

        foreach ($positions as $position) {
            Position::firstOrCreate(
                ['code' => $position['code']],
                $position
            );
        }
    }
}
