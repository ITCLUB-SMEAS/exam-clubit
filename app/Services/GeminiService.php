<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    public function generateQuestions(string $topic, string $type, int $count = 5, string $difficulty = 'medium'): ?array
    {
        $typeInstructions = $this->getTypeInstructions($type);
        
        $prompt = <<<PROMPT
Buatkan {$count} soal ujian tentang "{$topic}" dengan tingkat kesulitan {$difficulty}.

Tipe soal: {$typeInstructions}

PENTING: Jawab HANYA dalam format JSON array tanpa markdown, tanpa ```json, langsung array saja.
Format yang diharapkan:
[
  {
    "question": "Teks pertanyaan",
    "options": ["Pilihan A", "Pilihan B", "Pilihan C", "Pilihan D"],
    "answer": 1,
    "explanation": "Penjelasan singkat jawaban"
  }
]

Untuk "answer": gunakan angka 1-4 sesuai index pilihan yang benar (1=A, 2=B, 3=C, 4=D).
Untuk true_false: options harus ["Benar", "Salah"], answer 1=Benar, 2=Salah.
Untuk essay: options kosong [], answer berisi contoh jawaban.
Untuk short_answer: options kosong [], answer berisi jawaban singkat.

Pastikan soal bervariasi dan sesuai konteks pendidikan Indonesia.
PROMPT;

        try {
            $response = Http::timeout(60)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 4096,
                ],
            ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                $content = trim($content);
                $content = preg_replace('/^```json\s*/', '', $content);
                $content = preg_replace('/\s*```$/', '', $content);
                
                $questions = json_decode($content, true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($questions)) {
                    return $questions;
                }
                
                Log::warning('Gemini response not valid JSON', ['content' => $content]);
            }
            
            Log::error('Gemini API error', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Gemini API exception: ' . $e->getMessage());
        }

        return null;
    }

    protected function getTypeInstructions(string $type): string
    {
        return match ($type) {
            'multiple_choice_single' => 'Pilihan ganda dengan 4 opsi (A, B, C, D), hanya 1 jawaban benar',
            'true_false' => 'Benar/Salah - pernyataan yang harus dijawab Benar atau Salah',
            'essay' => 'Essay - pertanyaan terbuka yang membutuhkan jawaban panjang',
            'short_answer' => 'Jawaban singkat - pertanyaan dengan jawaban 1-3 kata',
            default => 'Pilihan ganda dengan 4 opsi',
        };
    }

    public function gradeEssay(string $question, string $answer, ?string $rubric = null, int $maxPoints = 10): ?array
    {
        $rubricText = $rubric ? "Rubrik penilaian: {$rubric}" : "Nilai berdasarkan kelengkapan, kebenaran, dan kejelasan jawaban.";

        $prompt = <<<PROMPT
Kamu adalah guru yang menilai jawaban essay siswa. Berikan penilaian objektif.

Pertanyaan: {$question}

Jawaban Siswa: {$answer}

{$rubricText}

Nilai maksimal: {$maxPoints} poin

PENTING: Jawab HANYA dalam format JSON tanpa markdown:
{
  "score": <angka 0-{$maxPoints}>,
  "feedback": "<feedback konstruktif dalam bahasa Indonesia, maksimal 200 kata>",
  "strengths": ["<kelebihan 1>", "<kelebihan 2>"],
  "improvements": ["<saran perbaikan 1>", "<saran perbaikan 2>"]
}
PROMPT;

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 1024,
                ],
            ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                $content = trim($content);
                $content = preg_replace('/^```json\s*/', '', $content);
                $content = preg_replace('/\s*```$/', '', $content);

                $result = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($result['score'])) {
                    $result['score'] = max(0, min($maxPoints, (float) $result['score']));
                    return $result;
                }

                Log::warning('Gemini essay grading response not valid', ['content' => $content]);
            }

            Log::error('Gemini essay grading API error', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Gemini essay grading exception: ' . $e->getMessage());
        }

        return null;
    }
}
