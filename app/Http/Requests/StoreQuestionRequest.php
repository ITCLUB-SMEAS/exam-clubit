<?php

namespace App\Http\Requests;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => 'required|string|min:5',
            'question_type' => 'nullable|in:multiple_choice_single,multiple_choice_multiple,short_answer,essay,true_false,matching',
            'points' => 'nullable|numeric|min:0|max:1000',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'option_1' => 'nullable|string|max:5000',
            'option_2' => 'nullable|string|max:5000',
            'option_3' => 'nullable|string|max:5000',
            'option_4' => 'nullable|string|max:5000',
            'option_5' => 'nullable|string|max:5000',
            'answer' => 'nullable|string',
            'correct_answers' => 'nullable|array',
            'correct_answers.*' => 'string',
            'matching_pairs' => 'nullable|array',
            'matching_pairs.*.left' => 'required_with:matching_pairs|string',
            'matching_pairs.*.right' => 'required_with:matching_pairs|string',
            'skip_duplicate_check' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'question' => 'soal',
            'question_type' => 'tipe soal',
            'points' => 'poin',
            'difficulty' => 'tingkat kesulitan',
            'option_1' => 'opsi 1',
            'option_2' => 'opsi 2',
            'option_3' => 'opsi 3',
            'option_4' => 'opsi 4',
            'option_5' => 'opsi 5',
            'answer' => 'jawaban',
            'correct_answers' => 'jawaban benar',
            'matching_pairs' => 'pasangan',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'question.required' => 'Soal wajib diisi.',
            'question.min' => 'Soal minimal 5 karakter.',
            'question_type.in' => 'Tipe soal tidak valid.',
            'points.min' => 'Poin tidak boleh negatif.',
            'points.max' => 'Poin maksimal 1000.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'question_type' => $this->question_type ?? Question::TYPE_MULTIPLE_CHOICE_SINGLE,
            'points' => $this->points ?? 1,
        ]);
    }

    /**
     * Get the processed correct answers.
     */
    public function getCorrectAnswers(): ?array
    {
        $correctAnswers = $this->input('correct_answers');

        if (is_string($correctAnswers)) {
            return array_filter(array_map('trim', explode(',', $correctAnswers)));
        }

        return $correctAnswers;
    }

    /**
     * Get question data for creation.
     */
    public function getQuestionData(int $examId): array
    {
        return [
            'exam_id' => $examId,
            'question' => $this->question,
            'question_type' => $this->question_type,
            'points' => $this->points,
            'difficulty' => $this->difficulty,
            'option_1' => $this->option_1,
            'option_2' => $this->option_2,
            'option_3' => $this->option_3,
            'option_4' => $this->option_4,
            'option_5' => $this->option_5,
            'answer' => $this->answer,
            'correct_answers' => $this->getCorrectAnswers(),
            'matching_pairs' => $this->matching_pairs,
        ];
    }
}
