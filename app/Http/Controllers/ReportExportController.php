<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Grade;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportExportController extends Controller
{
    /**
     * Helper to download view to desired format
     */
    private function downloadFormattedView($view, $baseFilename, $format)
    {
        if ($format === 'pdf') {
            $pdf = Pdf::loadHtml($view->render())->setPaper('a4', 'portrait');
            return $pdf->download($baseFilename . '.pdf');
        } elseif ($format === 'word') {
            return Response::make($view->render(), 200, [
                'Content-Type' => 'application/msword',
                'Content-Disposition' => 'attachment; filename="' . $baseFilename . '.doc"',
            ]);
        } elseif ($format === 'excel') {
            return Response::make($view->render(), 200, [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="' . $baseFilename . '.xls"',
            ]);
        }
    }
    /**
     * Export Academic Report to CSV
     */
    public function academicCsv(Request $request)
    {
        $this->authorize('reports.academic');

        $academicYear = AcademicYear::where('is_active', true)->first();

        $query = Grade::query()
            ->with(['student', 'subject', 'teacher'])
            ->when($academicYear, fn($q) => $q->where('academic_year_id', $academicYear->id))
            ->when($request->classroom_id, function($q) use ($request) {
                $q->whereHas('student', fn($sq) => $sq->where('classroom_id', $request->classroom_id));
            })
            ->when($request->subject_id, fn($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->semester, fn($q) => $q->where('semester', $request->semester));

        $grades = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan_akademik_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($grades, $academicYear) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header info
            fputcsv($file, ['LAPORAN AKADEMIK']);
            fputcsv($file, ['Tahun Ajaran: ' . ($academicYear?->name ?? '-')]);
            fputcsv($file, ['Tanggal Export: ' . now()->format('d/m/Y H:i')]);
            fputcsv($file, []);

            // Column headers
            fputcsv($file, ['No', 'NIS', 'Nama Siswa', 'Kelas', 'Mata Pelajaran', 'Jenis', 'Nilai', 'Semester', 'Tanggal', 'Guru']);

            // Data rows
            foreach ($grades as $index => $grade) {
                fputcsv($file, [
                    $index + 1,
                    $grade->student?->nis ?? '-',
                    $grade->student?->name ?? '-',
                    $grade->student?->classroom?->name ?? '-',
                    $grade->subject?->name ?? '-',
                    ucfirst($grade->type),
                    $grade->score,
                    $grade->semester,
                    $grade->date?->format('d/m/Y') ?? '-',
                    $grade->teacher?->name ?? '-',
                ]);
            }

            // Summary
            fputcsv($file, []);
            fputcsv($file, ['RINGKASAN']);
            fputcsv($file, ['Total Data', $grades->count()]);
            fputcsv($file, ['Rata-rata Nilai', number_format($grades->avg('score'), 2)]);
            fputcsv($file, ['Nilai Tertinggi', $grades->max('score')]);
            fputcsv($file, ['Nilai Terendah', $grades->min('score')]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export Students Report to CSV
     */
    public function studentsCsv(Request $request)
    {
        $this->authorize('reports.students');

        $query = Student::query()
            ->with(['classroom', 'department', 'guardian'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->classroom_id, fn($q) => $q->where('classroom_id', $request->classroom_id))
            ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->gender, fn($q) => $q->where('gender', $request->gender));

        $students = $query->orderBy('name')->get();

        $format = $request->get('format', 'csv');
        $fileName = 'laporan_siswa_' . now()->format('Y-m-d');

        if (in_array($format, ['pdf', 'word', 'excel'])) {
            return $this->downloadFormattedView(view('exports.students', [
                'students' => $students,
                'title' => 'LAPORAN DATA SISWA',
                'subtitle' => 'Tanggal Export: ' . now()->format('d/m/Y H:i'),
                'signerTitle' => 'Wali Kelas',
            ]), $fileName, $format);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan_siswa_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($students) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header info
            fputcsv($file, ['LAPORAN DATA SISWA']);
            fputcsv($file, ['Tanggal Export: ' . now()->format('d/m/Y H:i')]);
            fputcsv($file, []);

            // Column headers
            fputcsv($file, ['No', 'NIS', 'NISN', 'Nama', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'Agama', 'Kelas', 'Jurusan', 'Status', 'Nama Wali', 'No. HP Wali']);

            // Data rows
            foreach ($students as $index => $student) {
                fputcsv($file, [
                    $index + 1,
                    $student->nis ?? '-',
                    $student->nisn ?? '-',
                    $student->name,
                    $student->gender === 'male' ? 'Laki-laki' : 'Perempuan',
                    $student->birth_place ?? '-',
                    $student->birth_date?->format('d/m/Y') ?? '-',
                    $student->address ?? '-',
                    ucfirst($student->religion ?? '-'),
                    $student->classroom?->name ?? '-',
                    $student->department?->name ?? '-',
                    ucfirst($student->status),
                    $student->guardian?->name ?? '-',
                    $student->guardian?->phone ?? '-',
                ]);
            }

            // Summary
            fputcsv($file, []);
            fputcsv($file, ['RINGKASAN']);
            fputcsv($file, ['Total Siswa', $students->count()]);
            fputcsv($file, ['Laki-laki', $students->where('gender', 'male')->count()]);
            fputcsv($file, ['Perempuan', $students->where('gender', 'female')->count()]);
            fputcsv($file, ['Aktif', $students->where('status', 'aktif')->count()]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export Employees Report to CSV
     */
    public function employeesCsv(Request $request)
    {
        $this->authorize('reports.employees');

        $query = Employee::query()
            ->with('position')
            ->when($request->has('status') && $request->status !== '', fn($q) => $q->where('is_active', $request->status))
            ->when($request->type, fn($q) => $q->where('employee_type', $request->type))
            ->when($request->position_id, fn($q) => $q->where('position_id', $request->position_id));

        $employees = $query->orderBy('name')->get();

        $format = $request->get('format', 'csv');
        $fileName = 'laporan_pegawai_' . now()->format('Y-m-d');

        if (in_array($format, ['pdf', 'word', 'excel'])) {
            return $this->downloadFormattedView(view('exports.employees', [
                'employees' => $employees,
                'title' => 'LAPORAN DATA PEGAWAI',
                'subtitle' => 'Tanggal Export: ' . now()->format('d/m/Y H:i'),
                'signerTitle' => 'Kepala Sekolah',
            ]), $fileName, $format);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan_pegawai_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($employees) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header info
            fputcsv($file, ['LAPORAN DATA PEGAWAI']);
            fputcsv($file, ['Tanggal Export: ' . now()->format('d/m/Y H:i')]);
            fputcsv($file, []);

            // Column headers
            fputcsv($file, ['No', 'NIP', 'Nama', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'No. HP', 'Email', 'Jabatan', 'Tipe', 'Status Kepegawaian', 'Tanggal Masuk', 'Status']);

            // Data rows
            foreach ($employees as $index => $employee) {
                fputcsv($file, [
                    $index + 1,
                    $employee->nip ?? '-',
                    $employee->name,
                    $employee->gender === 'male' ? 'Laki-laki' : 'Perempuan',
                    $employee->birth_place ?? '-',
                    $employee->birth_date?->format('d/m/Y') ?? '-',
                    $employee->address ?? '-',
                    $employee->phone ?? '-',
                    $employee->email ?? '-',
                    $employee->position?->name ?? '-',
                    ucfirst($employee->employee_type ?? '-'),
                    ucfirst($employee->employment_status ?? '-'),
                    $employee->join_date?->format('d/m/Y') ?? '-',
                    $employee->is_active ? 'Aktif' : 'Tidak Aktif',
                ]);
            }

            // Summary
            fputcsv($file, []);
            fputcsv($file, ['RINGKASAN']);
            fputcsv($file, ['Total Pegawai', $employees->count()]);
            fputcsv($file, ['Guru', $employees->where('employee_type', 'teacher')->count()]);
            fputcsv($file, ['Staff', $employees->where('employee_type', 'staff')->count()]);
            fputcsv($file, ['Aktif', $employees->where('is_active', true)->count()]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export Attendance Report to CSV
     */
    public function attendanceCsv(Request $request)
    {
        $this->authorize('attendance.view_summary');

        $academicYear = AcademicYear::where('is_active', true)->first();
        $reportType = $request->get('type', 'student');
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        if ($reportType === 'student') {
            return $this->exportStudentAttendance($request, $startDate, $endDate);
        } else {
            return $this->exportEmployeeAttendance($request, $startDate, $endDate);
        }
    }

    private function exportStudentAttendance($request, $startDate, $endDate)
    {
        $query = StudentAttendance::query()
            ->with(['student', 'classroom'])
            ->whereBetween('date', [$startDate, $endDate])
            ->when($request->classroom_id, function($q) use ($request) {
                $q->whereHas('student', fn($sq) => $sq->where('classroom_id', $request->classroom_id));
            });

        $attendances = $query->orderBy('date')->orderBy('student_id')->get();

        $format = $request->get('format', 'csv');
        $fileName = 'laporan_kehadiran_siswa_' . $startDate->format('Y-m');

        if (in_array($format, ['pdf', 'word', 'excel'])) {
            return $this->downloadFormattedView(view('exports.attendance', [
                'attendances' => $attendances,
                'type' => 'student',
                'title' => 'LAPORAN KEHADIRAN SISWA',
                'subtitle' => 'Periode: ' . $startDate->translatedFormat('F Y'),
                'signerTitle' => 'Wali Kelas',
            ]), $fileName, $format);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '.csv"',
        ];

        $callback = function() use ($attendances, $startDate, $endDate) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header info
            fputcsv($file, ['LAPORAN KEHADIRAN SISWA']);
            fputcsv($file, ['Periode: ' . $startDate->translatedFormat('F Y')]);
            fputcsv($file, ['Tanggal Export: ' . now()->format('d/m/Y H:i')]);
            fputcsv($file, []);

            // Column headers
            fputcsv($file, ['No', 'Tanggal', 'NIS', 'Nama Siswa', 'Kelas', 'Status', 'Keterangan']);

            // Data rows
            foreach ($attendances as $index => $att) {
                fputcsv($file, [
                    $index + 1,
                    $att->date?->format('d/m/Y') ?? '-',
                    $att->student?->nis ?? '-',
                    $att->student?->name ?? '-',
                    $att->classroom?->name ?? '-',
                    ucfirst($att->status),
                    $att->notes ?? '-',
                ]);
            }

            // Summary
            $summary = $attendances->groupBy('status')->map->count();
            fputcsv($file, []);
            fputcsv($file, ['RINGKASAN']);
            fputcsv($file, ['Total Data', $attendances->count()]);
            fputcsv($file, ['Hadir', $summary->get('hadir', 0)]);
            fputcsv($file, ['Sakit', $summary->get('sakit', 0)]);
            fputcsv($file, ['Izin', $summary->get('izin', 0)]);
            fputcsv($file, ['Alpha', $summary->get('alpha', 0)]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function exportEmployeeAttendance($request, $startDate, $endDate)
    {
        $query = EmployeeAttendance::query()
            ->with('employee')
            ->whereBetween('date', [$startDate, $endDate]);

        $attendances = $query->orderBy('date')->orderBy('employee_id')->get();

        $format = $request->get('format', 'csv');
        $fileName = 'laporan_kehadiran_pegawai_' . $startDate->format('Y-m');

        if (in_array($format, ['pdf', 'word', 'excel'])) {
            return $this->downloadFormattedView(view('exports.attendance', [
                'attendances' => $attendances,
                'type' => 'employee',
                'title' => 'LAPORAN KEHADIRAN PEGAWAI',
                'subtitle' => 'Periode: ' . $startDate->translatedFormat('F Y'),
                'signerTitle' => 'Kepala Sekolah',
            ]), $fileName, $format);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '.csv"',
        ];

        $callback = function() use ($attendances, $startDate, $endDate) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header info
            fputcsv($file, ['LAPORAN KEHADIRAN PEGAWAI']);
            fputcsv($file, ['Periode: ' . $startDate->translatedFormat('F Y')]);
            fputcsv($file, ['Tanggal Export: ' . now()->format('d/m/Y H:i')]);
            fputcsv($file, []);

            // Column headers
            fputcsv($file, ['No', 'Tanggal', 'NIP', 'Nama Pegawai', 'Jabatan', 'Jam Masuk', 'Jam Keluar', 'Status', 'Keterangan']);

            // Data rows
            foreach ($attendances as $index => $att) {
                fputcsv($file, [
                    $index + 1,
                    $att->date?->format('d/m/Y') ?? '-',
                    $att->employee?->nip ?? '-',
                    $att->employee?->name ?? '-',
                    $att->employee?->position?->name ?? '-',
                    $att->check_in ?? '-',
                    $att->check_out ?? '-',
                    ucfirst($att->status),
                    $att->notes ?? '-',
                ]);
            }

            // Summary
            $summary = $attendances->groupBy('status')->map->count();
            fputcsv($file, []);
            fputcsv($file, ['RINGKASAN']);
            fputcsv($file, ['Total Data', $attendances->count()]);
            fputcsv($file, ['Hadir', $summary->get('hadir', 0)]);
            fputcsv($file, ['Sakit', $summary->get('sakit', 0)]);
            fputcsv($file, ['Izin', $summary->get('izin', 0)]);
            fputcsv($file, ['Alpha', $summary->get('alpha', 0)]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export Summary Report to CSV
     */
    public function summaryCsv(Request $request)
    {
        $this->authorize('reports.view');

        $academicYear = AcademicYear::where('is_active', true)->first();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan_ringkasan_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($academicYear) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header info
            fputcsv($file, ['LAPORAN RINGKASAN SEKOLAH']);
            fputcsv($file, ['Tahun Ajaran: ' . ($academicYear?->name ?? '-')]);
            fputcsv($file, ['Tanggal Export: ' . now()->format('d/m/Y H:i')]);
            fputcsv($file, []);

            // Student Stats
            fputcsv($file, ['DATA SISWA']);
            fputcsv($file, ['Total Siswa', Student::count()]);
            fputcsv($file, ['Siswa Aktif', Student::where('status', 'aktif')->count()]);
            fputcsv($file, ['Laki-laki', Student::where('gender', 'male')->count()]);
            fputcsv($file, ['Perempuan', Student::where('gender', 'female')->count()]);
            fputcsv($file, []);

            // Employee Stats
            fputcsv($file, ['DATA PEGAWAI']);
            fputcsv($file, ['Total Pegawai', Employee::count()]);
            fputcsv($file, ['Pegawai Aktif', Employee::where('is_active', true)->count()]);
            fputcsv($file, ['Guru', Employee::where('employee_type', 'teacher')->count()]);
            fputcsv($file, ['Staff', Employee::where('employee_type', 'staff')->count()]);
            fputcsv($file, []);

            // Classroom Stats
            fputcsv($file, ['DATA KELAS']);
            fputcsv($file, ['Total Kelas', Classroom::count()]);
            fputcsv($file, ['Kelas Aktif', Classroom::where('is_active', true)->count()]);
            fputcsv($file, []);

            // Academic Stats
            if ($academicYear) {
                $avgGrade = Grade::where('academic_year_id', $academicYear->id)->avg('score');
                fputcsv($file, ['DATA AKADEMIK']);
                fputcsv($file, ['Rata-rata Nilai', number_format($avgGrade ?? 0, 2)]);
                fputcsv($file, ['Total Data Nilai', Grade::where('academic_year_id', $academicYear->id)->count()]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Check permission using Gate
     */
    private function authorize($permission)
    {
        $user = auth()->user();
        if (!$user->hasPermission($permission) && !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
