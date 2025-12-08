<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $studentId = $this->route('student')->id ?? $this->route('student');

        return [
            'name' => ['required', 'string', 'max:255', 'min:3'],
            'nisn' => ['required', 'string', 'max:20', Rule::unique('students')->ignore($studentId)->whereNull('deleted_at')],
            'gender' => ['required', 'in:L,P'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama siswa wajib diisi',
            'name.min' => 'Nama minimal 3 karakter',
            'nisn.required' => 'NISN wajib diisi',
            'nisn.unique' => 'NISN sudah terdaftar',
            'gender.required' => 'Jenis kelamin wajib dipilih',
            'password.min' => 'Password minimal 6 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'classroom_id.required' => 'Kelas wajib dipilih',
        ];
    }
}
