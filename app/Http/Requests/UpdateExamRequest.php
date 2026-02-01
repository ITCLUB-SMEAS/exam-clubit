<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'classroom_id' => 'required|integer|exists:classrooms,id',
            'duration' => 'required|integer|min:1|max:600',
            'description' => 'required|string',
            'random_question' => 'required|in:Y,N',
            'random_answer' => 'required|in:Y,N',
            'show_answer' => 'required|in:Y,N',
            'passing_grade' => 'nullable|numeric|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1|max:100',
            'question_limit' => 'nullable|integer|min:1',
            'time_per_question' => 'nullable|integer|min:1|max:3600',
            'adaptive_mode' => 'nullable|boolean',
            'block_multiple_monitors' => 'nullable|boolean',
            'block_virtual_machine' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul ujian',
            'lesson_id' => 'mata pelajaran',
            'classroom_id' => 'kelas',
            'duration' => 'durasi',
            'description' => 'deskripsi',
            'random_question' => 'acak soal',
            'random_answer' => 'acak jawaban',
            'show_answer' => 'tampilkan jawaban',
            'passing_grade' => 'nilai kelulusan',
            'max_attempts' => 'maksimal percobaan',
            'question_limit' => 'batas soal',
            'time_per_question' => 'waktu per soal',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'passing_grade' => $this->passing_grade ?? 0,
            'max_attempts' => $this->max_attempts ?? 1,
            'block_multiple_monitors' => $this->block_multiple_monitors ?? true,
            'block_virtual_machine' => $this->block_virtual_machine ?? true,
            'adaptive_mode' => $this->adaptive_mode ?? false,
        ]);
    }

    /**
     * Get validated data with proper defaults for exam update.
     */
    public function getExamData(): array
    {
        $data = $this->validated();

        // If adaptive mode is enabled, disable random questions
        if ($data['adaptive_mode'] ?? false) {
            $data['random_question'] = 'N';
        }

        return $data;
    }
}
