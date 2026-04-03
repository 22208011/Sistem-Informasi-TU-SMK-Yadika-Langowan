<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'nim' => ['required', 'string', 'max:50'],
            'password' => $this->passwordRules(),
        ])->validate();

        $student = Student::query()
            ->where('nis', trim((string) $input['nim']))
            ->orWhere('nisn', trim((string) $input['nim']))
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'nim' => 'NIM tidak ditemukan. Gunakan NIM siswa yang sudah terdaftar di sistem.',
            ]);
        }

        if (User::query()->where('student_id', $student->id)->exists()) {
            throw ValidationException::withMessages([
                'nim' => 'NIM ini sudah terhubung dengan akun lain. Silakan login atau hubungi admin.',
            ]);
        }

        $studentRole = Role::query()->where('name', Role::SISWA)->first();

        if (! $studentRole) {
            throw ValidationException::withMessages([
                'nim' => 'Role siswa belum tersedia. Silakan hubungi admin sistem.',
            ]);
        }

        $email = $student->email ?: "siswa.{$student->nis}@school.local";
        if (User::query()->where('email', $email)->exists()) {
            $email = "siswa.{$student->nis}.{$student->id}@school.local";
        }

        return User::create([
            'name' => $student->name,
            'email' => $email,
            'password' => $input['password'],
            'role_id' => $studentRole->id,
            'student_id' => $student->id,
            'is_active' => true,
        ]);
    }
}
