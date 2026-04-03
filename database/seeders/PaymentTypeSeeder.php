<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'SPP',
                'name' => 'SPP (Sumbangan Pembinaan Pendidikan)',
                'description' => 'Iuran bulanan siswa',
                'default_amount' => 250000,
                'is_recurring' => true,
            ],
            [
                'code' => 'UG',
                'name' => 'Uang Gedung',
                'description' => 'Biaya pembangunan dan pemeliharaan gedung',
                'default_amount' => 2500000,
                'is_recurring' => false,
            ],
            [
                'code' => 'DSP',
                'name' => 'Dana Sumbangan Pendidikan',
                'description' => 'Biaya awal masuk sekolah',
                'default_amount' => 3000000,
                'is_recurring' => false,
            ],
            [
                'code' => 'SRGM',
                'name' => 'Seragam',
                'description' => 'Biaya seragam sekolah lengkap',
                'default_amount' => 750000,
                'is_recurring' => false,
            ],
            [
                'code' => 'PKL',
                'name' => 'Praktek Kerja Lapangan',
                'description' => 'Biaya kegiatan PKL/Magang',
                'default_amount' => 500000,
                'is_recurring' => false,
            ],
            [
                'code' => 'US',
                'name' => 'Ujian Sekolah',
                'description' => 'Biaya penyelenggaraan Ujian Sekolah',
                'default_amount' => 150000,
                'is_recurring' => false,
            ],
            [
                'code' => 'WISUDA',
                'name' => 'Wisuda',
                'description' => 'Biaya acara wisuda kelulusan',
                'default_amount' => 500000,
                'is_recurring' => false,
            ],
            [
                'code' => 'BUKU',
                'name' => 'Buku & Modul',
                'description' => 'Biaya buku pelajaran dan modul',
                'default_amount' => 350000,
                'is_recurring' => false,
            ],
        ];

        foreach ($types as $type) {
            PaymentType::updateOrCreate(['code' => $type['code']], $type);
        }

        $this->command->info('PaymentType seeder completed: '.count($types).' types created.');
    }
}
