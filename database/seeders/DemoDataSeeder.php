<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Guardian;
use App\Models\Position;
use App\Models\Role;
use App\Models\SchoolProfile;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. School Profile - Data Real SMK Yadika Langowan
        $school = SchoolProfile::firstOrCreate(
            ['npsn' => '69858680'],
            [
                'name' => 'SMK YADIKA LANGOWAN',
                'status' => 'Swasta',
                'accreditation' => 'A',
                'address' => 'Jl. Pasar Baru, Jaga VI',
                'village' => 'Desa Waleure',
                'district' => 'Langowan Timur',
                'city' => 'Kabupaten Minahasa',
                'province' => 'Sulawesi Utara',
                'postal_code' => '95694',
                'latitude' => 1.162700,
                'longitude' => 124.846100,
                'maps_url' => 'https://sekolah.data.kemendikdasmen.go.id/peta-sekolah?lintang=1.162700&bujur=124.846100',
                'phone' => '(0431) 373346',
                'whatsapp_1' => '0831-1446-0626',
                'whatsapp_1_name' => 'Sir Maikel',
                'whatsapp_2' => '0823-3686-0982',
                'whatsapp_2_name' => 'Sir Doddy',
                'email' => 'dodyfardiansyahali@gmail.com',
                'website' => null,
                'facebook' => 'SMA / SMK Yadika Langowan',
                'instagram' => '@smkyadika_langowan',
                'youtube' => null,
                'tiktok' => null,
                'operational_days' => 'Senin - Jumat, Minggu',
                'operational_start' => '07:00',
                'operational_end' => '15:00',
                'timezone' => 'WITA',
                'principal_name' => 'Yusdian Christiani Meybita Imron, S.Pd, Gr.',
                'principal_nip' => null,
            ]
        );

        // 2. Academic Years
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2025/2026'],
            [
                'start_date' => '2025-07-14',
                'end_date' => '2026-06-30',
                'is_active' => true,
            ]
        );

        AcademicYear::firstOrCreate(
            ['name' => '2024/2025'],
            [
                'start_date' => '2024-07-15',
                'end_date' => '2025-06-30',
                'is_active' => false,
            ]
        );

        // 3. Departments (Kompetensi Keahlian)
        $departments = [
            ['code' => 'ASKEP', 'name' => 'Asisten Keperawatan (Layanan Kesehatan)', 'description' => 'Kompetensi keahlian bidang layanan kesehatan dan keperawatan'],
            ['code' => 'TKRO', 'name' => 'Teknik Kendaraan Ringan Otomotif', 'description' => 'Kompetensi keahlian bidang otomotif kendaraan ringan'],
            ['code' => 'TJKT', 'name' => 'Teknik Jaringan Komputer dan Telekomunikasi', 'description' => 'Kompetensi keahlian bidang jaringan komputer dan telekomunikasi'],
            ['code' => 'UPW', 'name' => 'Usaha Perjalanan Wisata', 'description' => 'Kompetensi keahlian bidang pariwisata dan perjalanan'],
        ];

        $deptModels = [];
        foreach ($departments as $dept) {
            $deptModels[$dept['code']] = Department::firstOrCreate(
                ['code' => $dept['code']],
                $dept
            );
        }

        // 4. Classrooms
        $classrooms = [];
        $grades = ['X', 'XI', 'XII'];
        foreach ($deptModels as $code => $dept) {
            foreach ($grades as $grade) {
                $classroom = Classroom::firstOrCreate(
                    ['name' => $grade.' '.$code, 'academic_year_id' => $academicYear->id],
                    [
                        'grade' => $grade,
                        'department_id' => $dept->id,
                        'capacity' => 36,
                        'is_active' => true,
                    ]
                );
                $classrooms[] = $classroom;
            }
        }

        // 5. Positions - always create if not exist
        $positionsData = [
            ['code' => 'KASEK', 'name' => 'Kepala Sekolah', 'category' => 'struktural', 'description' => 'Pimpinan sekolah'],
            ['code' => 'WAKASEK', 'name' => 'Wakil Kepala Sekolah', 'category' => 'struktural', 'description' => 'Wakil pimpinan sekolah'],
            ['code' => 'KATU', 'name' => 'Kepala Tata Usaha', 'category' => 'struktural', 'description' => 'Kepala bagian administrasi'],
            ['code' => 'TU', 'name' => 'Staff Tata Usaha', 'category' => 'fungsional', 'description' => 'Staff administrasi umum'],
            ['code' => 'GURU', 'name' => 'Guru', 'category' => 'fungsional', 'description' => 'Tenaga pendidik'],
            ['code' => 'WAKEL', 'name' => 'Wali Kelas', 'category' => 'fungsional', 'description' => 'Guru yang menjadi wali kelas'],
            ['code' => 'KAPROG', 'name' => 'Kepala Program Keahlian', 'category' => 'struktural', 'description' => 'Koordinator program keahlian'],
            ['code' => 'BEND', 'name' => 'Bendahara', 'category' => 'fungsional', 'description' => 'Pengelola keuangan sekolah'],
            ['code' => 'PUSTAKA', 'name' => 'Pustakawan', 'category' => 'fungsional', 'description' => 'Pengelola perpustakaan'],
            ['code' => 'LAB', 'name' => 'Laboran', 'category' => 'fungsional', 'description' => 'Pengelola laboratorium'],
        ];

        foreach ($positionsData as $pos) {
            Position::firstOrCreate(['code' => $pos['code']], $pos);
        }

        // 6. Employees (Pegawai/Guru)
        $guruPosition = Position::where('code', 'GURU')->orWhere('name', 'like', '%Guru%')->first();
        $wakelPosition = Position::where('code', 'WAKEL')->orWhere('code', 'WKLS')->first();
        $kasekPosition = Position::where('code', 'KASEK')->orWhere('code', 'KS')->first();
        $tuPosition = Position::where('code', 'TU')->orWhere('code', 'STAFF')->first();

        // Get existing users
        $adminUser = User::where('email', 'admin@smk.sch.id')->first();
        $kasekUser = User::where('email', 'kepala.sekolah@smk.sch.id')->first();
        $tuUser = User::where('email', 'tata.usaha@smk.sch.id')->first();
        $guruUser = User::where('email', 'guru@smk.sch.id')->first();
        $waliKelasUser = User::where('email', 'wali.kelas@smk.sch.id')->first();

        // Kepala Sekolah
        $kasekEmployee = Employee::firstOrCreate(
            ['nip' => '196512121990011001'],
            [
                'nuptk' => '1234567890123456',
                'name' => 'Drs. Johannes Pangemanan, M.Pd',
                'gender' => 'L',
                'place_of_birth' => 'Langowan',
                'date_of_birth' => '1965-12-12',
                'religion' => 'Kristen Protestan',
                'address' => 'Jl. Raya Langowan No. 10',
                'phone' => '081234567890',
                'email' => 'kepala.sekolah@smk.sch.id',
                'position_id' => $kasekPosition->id,
                'employee_status' => 'pns',
                'employee_type' => 'guru',
                'join_date' => '1990-01-01',
                'education_level' => 'S2',
                'education_major' => 'Pendidikan',
                'is_active' => true,
            ]
        );
        if ($kasekUser && ! $kasekUser->employee_id) {
            $kasekUser->update(['employee_id' => $kasekEmployee->id]);
        }

        // Staff TU
        $tuEmployee = Employee::firstOrCreate(
            ['email' => 'tata.usaha@smk.sch.id'],
            [
                'nip' => '198005152010011001',
                'name' => 'Marsel Wongkar, S.Kom',
                'gender' => 'L',
                'place_of_birth' => 'Tondano',
                'date_of_birth' => '1980-05-15',
                'religion' => 'Kristen Protestan',
                'address' => 'Jl. Tondano No. 5',
                'phone' => '082345678901',
                'position_id' => $tuPosition->id,
                'employee_status' => 'pns',
                'employee_type' => 'tendik',
                'join_date' => '2010-01-01',
                'education_level' => 'S1',
                'education_major' => 'Sistem Informasi',
                'is_active' => true,
            ]
        );
        if ($tuUser && ! $tuUser->employee_id) {
            $tuUser->update(['employee_id' => $tuEmployee->id]);
        }

        // Guru-guru
        $teachers = [
            [
                'nip' => '198506201012011001',
                'nuptk' => '2345678901234567',
                'name' => 'Ir. Frangky Lumenta, M.T',
                'gender' => 'L',
                'place_of_birth' => 'Manado',
                'date_of_birth' => '1985-06-20',
                'religion' => 'Kristen Protestan',
                'address' => 'Jl. Manado No. 15',
                'phone' => '083456789012',
                'email' => 'guru@smk.sch.id',
                'position_id' => $guruPosition->id,
                'employee_status' => 'pns',
                'employee_type' => 'guru',
                'join_date' => '2012-01-01',
                'education_level' => 'S2',
                'education_major' => 'Teknik Informatika',
                'is_active' => true,
            ],
            [
                'nip' => '199001102015012001',
                'nuptk' => '3456789012345678',
                'name' => 'Grace Tumewu, S.Pd',
                'gender' => 'P',
                'place_of_birth' => 'Tomohon',
                'date_of_birth' => '1990-01-10',
                'religion' => 'Kristen Protestan',
                'address' => 'Jl. Tomohon No. 8',
                'phone' => '084567890123',
                'email' => 'wali.kelas@smk.sch.id',
                'position_id' => $wakelPosition->id,
                'employee_status' => 'pns',
                'employee_type' => 'guru',
                'join_date' => '2015-01-01',
                'education_level' => 'S1',
                'education_major' => 'Pendidikan Bahasa Inggris',
                'is_active' => true,
            ],
            [
                'nip' => '198803152013011001',
                'name' => 'Denny Rawung, S.Kom',
                'gender' => 'L',
                'place_of_birth' => 'Bitung',
                'date_of_birth' => '1988-03-15',
                'religion' => 'Kristen Protestan',
                'address' => 'Jl. Bitung No. 20',
                'phone' => '085678901234',
                'email' => 'denny.rawung@smk.sch.id',
                'position_id' => $guruPosition->id,
                'employee_status' => 'honorer',
                'employee_type' => 'guru',
                'join_date' => '2013-01-01',
                'education_level' => 'S1',
                'education_major' => 'Teknik Komputer',
                'is_active' => true,
            ],
            [
                'name' => 'Siska Maramis, S.E',
                'gender' => 'P',
                'place_of_birth' => 'Langowan',
                'date_of_birth' => '1992-07-22',
                'religion' => 'Kristen Protestan',
                'address' => 'Jl. Langowan No. 12',
                'phone' => '086789012345',
                'email' => 'siska.maramis@smk.sch.id',
                'position_id' => $guruPosition->id,
                'employee_status' => 'honorer',
                'employee_type' => 'guru',
                'join_date' => '2018-07-01',
                'education_level' => 'S1',
                'education_major' => 'Ekonomi Akuntansi',
                'is_active' => true,
            ],
        ];

        $employeeModels = [];
        foreach ($teachers as $index => $teacher) {
            $emp = Employee::firstOrCreate(
                ['email' => $teacher['email']],
                $teacher
            );
            $employeeModels[] = $emp;

            // Link to existing users
            if ($index === 0 && $guruUser && ! $guruUser->employee_id) {
                $guruUser->update(['employee_id' => $emp->id]);
            }
            if ($index === 1 && $waliKelasUser && ! $waliKelasUser->employee_id) {
                $waliKelasUser->update(['employee_id' => $emp->id]);
            }
        }

        // 7. Subjects (Mata Pelajaran)
        $subjects = [
            // Muatan Nasional
            ['code' => 'PAI', 'name' => 'Pendidikan Agama dan Budi Pekerti', 'description' => 'Mata pelajaran wajib nasional', 'grade_level' => 'all'],
            ['code' => 'PKN', 'name' => 'Pendidikan Pancasila dan Kewarganegaraan', 'description' => 'Mata pelajaran wajib nasional', 'grade_level' => 'all'],
            ['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'description' => 'Mata pelajaran wajib nasional', 'grade_level' => 'all'],
            ['code' => 'MTK', 'name' => 'Matematika', 'description' => 'Mata pelajaran wajib nasional', 'grade_level' => 'all'],
            ['code' => 'SJI', 'name' => 'Sejarah Indonesia', 'description' => 'Mata pelajaran wajib nasional', 'grade_level' => 'all'],
            ['code' => 'BIG', 'name' => 'Bahasa Inggris', 'description' => 'Mata pelajaran wajib nasional', 'grade_level' => 'all'],

            // Muatan Kewilayahan
            ['code' => 'SBK', 'name' => 'Seni Budaya', 'description' => 'Mata pelajaran muatan kewilayahan', 'grade_level' => 'all'],
            ['code' => 'PJK', 'name' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan', 'description' => 'Mata pelajaran muatan kewilayahan', 'grade_level' => 'all'],

            // Muatan Peminatan Kejuruan
            ['code' => 'SIM', 'name' => 'Simulasi dan Komunikasi Digital', 'description' => 'Dasar bidang keahlian', 'grade_level' => 'X'],
            ['code' => 'FIS', 'name' => 'Fisika', 'description' => 'Dasar bidang keahlian', 'grade_level' => 'X'],
            ['code' => 'KIM', 'name' => 'Kimia', 'description' => 'Dasar bidang keahlian', 'grade_level' => 'X'],

            // Kompetensi Keahlian ASKEP (Asisten Keperawatan)
            ['code' => 'ANT', 'name' => 'Anatomi dan Fisiologi', 'description' => 'Mata pelajaran kejuruan ASKEP', 'grade_level' => 'X', 'department_id' => $deptModels['ASKEP']->id ?? null],
            ['code' => 'KDK', 'name' => 'Konsep Dasar Keperawatan', 'description' => 'Mata pelajaran kejuruan ASKEP', 'grade_level' => 'X', 'department_id' => $deptModels['ASKEP']->id ?? null],
            ['code' => 'KDP', 'name' => 'Kebutuhan Dasar Manusia', 'description' => 'Mata pelajaran kejuruan ASKEP', 'grade_level' => 'XI', 'department_id' => $deptModels['ASKEP']->id ?? null],
            ['code' => 'KKL', 'name' => 'Keterampilan Klinis', 'description' => 'Mata pelajaran kejuruan ASKEP', 'grade_level' => 'XI', 'department_id' => $deptModels['ASKEP']->id ?? null],
            ['code' => 'IKM', 'name' => 'Ilmu Kesehatan Masyarakat', 'description' => 'Mata pelajaran kejuruan ASKEP', 'grade_level' => 'XII', 'department_id' => $deptModels['ASKEP']->id ?? null],

            // Kompetensi Keahlian TKRO (Teknik Kendaraan Ringan Otomotif)
            ['code' => 'TDO', 'name' => 'Teknologi Dasar Otomotif', 'description' => 'Mata pelajaran kejuruan TKRO', 'grade_level' => 'X', 'department_id' => $deptModels['TKRO']->id ?? null],
            ['code' => 'PKR', 'name' => 'Pemeliharaan Kelistrikan Kendaraan Ringan', 'description' => 'Mata pelajaran kejuruan TKRO', 'grade_level' => 'XI', 'department_id' => $deptModels['TKRO']->id ?? null],
            ['code' => 'PMK', 'name' => 'Pemeliharaan Mesin Kendaraan Ringan', 'description' => 'Mata pelajaran kejuruan TKRO', 'grade_level' => 'XI', 'department_id' => $deptModels['TKRO']->id ?? null],
            ['code' => 'PSK', 'name' => 'Pemeliharaan Sasis dan Pemindah Tenaga', 'description' => 'Mata pelajaran kejuruan TKRO', 'grade_level' => 'XII', 'department_id' => $deptModels['TKRO']->id ?? null],

            // Kompetensi Keahlian TJKT (Teknik Jaringan Komputer dan Telekomunikasi)
            ['code' => 'SKJ', 'name' => 'Sistem Komputer', 'description' => 'Mata pelajaran kejuruan TJKT', 'grade_level' => 'X', 'department_id' => $deptModels['TJKT']->id ?? null],
            ['code' => 'KJD', 'name' => 'Komputer dan Jaringan Dasar', 'description' => 'Mata pelajaran kejuruan TJKT', 'grade_level' => 'X', 'department_id' => $deptModels['TJKT']->id ?? null],
            ['code' => 'TLJ', 'name' => 'Teknologi Layanan Jaringan', 'description' => 'Mata pelajaran kejuruan TJKT', 'grade_level' => 'XI', 'department_id' => $deptModels['TJKT']->id ?? null],
            ['code' => 'AIJ', 'name' => 'Administrasi Infrastruktur Jaringan', 'description' => 'Mata pelajaran kejuruan TJKT', 'grade_level' => 'XI', 'department_id' => $deptModels['TJKT']->id ?? null],
            ['code' => 'ASJ', 'name' => 'Administrasi Sistem Jaringan', 'description' => 'Mata pelajaran kejuruan TJKT', 'grade_level' => 'XII', 'department_id' => $deptModels['TJKT']->id ?? null],

            // Kompetensi Keahlian UPW (Usaha Perjalanan Wisata)
            ['code' => 'DPW', 'name' => 'Dasar-Dasar Pariwisata', 'description' => 'Mata pelajaran kejuruan UPW', 'grade_level' => 'X', 'department_id' => $deptModels['UPW']->id ?? null],
            ['code' => 'PWT', 'name' => 'Pengelolaan Wisata', 'description' => 'Mata pelajaran kejuruan UPW', 'grade_level' => 'XI', 'department_id' => $deptModels['UPW']->id ?? null],
            ['code' => 'TKT', 'name' => 'Ticketing dan Reservasi', 'description' => 'Mata pelajaran kejuruan UPW', 'grade_level' => 'XI', 'department_id' => $deptModels['UPW']->id ?? null],
            ['code' => 'GPW', 'name' => 'Guiding dan Pemanduan Wisata', 'description' => 'Mata pelajaran kejuruan UPW', 'grade_level' => 'XII', 'department_id' => $deptModels['UPW']->id ?? null],

            // Produk Kreatif dan Kewirausahaan (untuk semua jurusan)
            ['code' => 'PPL', 'name' => 'Produk Kreatif dan Kewirausahaan', 'description' => 'Mata pelajaran kejuruan', 'grade_level' => 'XI'],
        ];

        foreach ($subjects as $subject) {
            Subject::firstOrCreate(
                ['code' => $subject['code']],
                $subject
            );
        }

        // 8. Students
        $orangTuaRole = Role::where('name', Role::ORANG_TUA)->first();
        $orangTuaUser = User::where('email', 'orang.tua@smk.sch.id')->first();

        // Create sample students for each class
        $studentData = [
            ['nis' => '2024001', 'nisn' => '0045678901', 'name' => 'Alexander Mamahit', 'gender' => 'Laki-laki'],
            ['nis' => '2024002', 'nisn' => '0045678902', 'name' => 'Brigita Tumewu', 'gender' => 'Perempuan'],
            ['nis' => '2024003', 'nisn' => '0045678903', 'name' => 'Christian Wongkar', 'gender' => 'Laki-laki'],
            ['nis' => '2024004', 'nisn' => '0045678904', 'name' => 'Diana Rawung', 'gender' => 'Perempuan'],
            ['nis' => '2024005', 'nisn' => '0045678905', 'name' => 'Edward Lumenta', 'gender' => 'Laki-laki'],
            ['nis' => '2024006', 'nisn' => '0045678906', 'name' => 'Fiona Maramis', 'gender' => 'Perempuan'],
            ['nis' => '2024007', 'nisn' => '0045678907', 'name' => 'George Pangemanan', 'gender' => 'Laki-laki'],
            ['nis' => '2024008', 'nisn' => '0045678908', 'name' => 'Helena Senduk', 'gender' => 'Perempuan'],
        ];

        $counter = 0;
        foreach ($classrooms as $classroom) {
            // Add 2-3 students per classroom for demo
            $studentsPerClass = rand(2, 3);
            for ($i = 0; $i < $studentsPerClass && $counter < count($studentData); $i++) {
                $data = $studentData[$counter];
                $student = Student::firstOrCreate(
                    ['nis' => $data['nis']],
                    [
                        'nisn' => $data['nisn'],
                        'name' => $data['name'],
                        'gender' => $data['gender'] === 'Laki-laki' ? 'L' : 'P',
                        'place_of_birth' => 'Langowan',
                        'date_of_birth' => fake()->dateTimeBetween('-18 years', '-15 years')->format('Y-m-d'),
                        'religion' => 'Kristen Protestan',
                        'address' => 'Jl. '.fake()->streetName.' No. '.fake()->numberBetween(1, 100),
                        'phone' => '08'.fake()->numerify('##########'),
                        'email' => strtolower(str_replace(' ', '.', $data['name'])).'@student.smk.sch.id',
                        'classroom_id' => $classroom->id,
                        'department_id' => $classroom->department_id,
                        'academic_year_id' => $academicYear->id,
                        'entry_year' => 2024,
                        'status' => 'aktif',
                    ]
                );

                // Create guardian for first student and link to orang tua user
                if ($counter === 0 && $orangTuaUser && ! $student->guardians()->exists()) {
                    Guardian::firstOrCreate(
                        ['student_id' => $student->id, 'user_id' => $orangTuaUser->id],
                        [
                            'name' => 'Bpk. Robert Mamahit',
                            'relationship' => 'Ayah',
                            'phone' => '081234567899',
                            'email' => 'orang.tua@smk.sch.id',
                            'address' => $student->address,
                            'occupation' => 'Wiraswasta',
                        ]
                    );
                } elseif (! $student->guardians()->exists()) {
                    Guardian::create([
                        'student_id' => $student->id,
                        'name' => 'Orang Tua '.$data['name'],
                        'relationship' => $data['gender'] === 'Laki-laki' ? 'Ayah' : 'Ibu',
                        'phone' => '08'.fake()->numerify('##########'),
                        'address' => $student->address,
                        'occupation' => fake()->randomElement(['PNS', 'Wiraswasta', 'Petani', 'Guru', 'Karyawan Swasta']),
                    ]);
                }

                $counter++;
            }
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Users: '.User::count());
        $this->command->info('Employees: '.Employee::count());
        $this->command->info('Students: '.Student::count());
        $this->command->info('Classrooms: '.Classroom::count());
        $this->command->info('Departments: '.Department::count());
        $this->command->info('Subjects: '.Subject::count());
    }
}
