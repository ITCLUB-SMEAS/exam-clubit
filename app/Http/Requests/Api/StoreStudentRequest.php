<?php

namespace App\Http\Requests\Api;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nisn' => ['required', 'string', 'max:20', 'unique:students,nisn', 'regex:/^[0-9]+$/'],
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'password' => ['required', new StrongPassword(8, false, false, true, false)],
            'gender' => ['required', 'in:L,P'],
            'photo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nisn.regex' => 'NISN hanya boleh berisi angka.',
            'name.regex' => 'Nama hanya boleh berisi huruf dan spasi.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422));
    }
}
