<?php

namespace App\Console\Commands;

use App\Models\Answer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DecryptAnswerText extends Command
{
    protected $signature = 'answers:decrypt';
    protected $description = 'Decrypt all encrypted answer_text fields';

    public function handle()
    {
        $this->info('Decrypting answer_text fields...');
        
        $answers = DB::table('answers')
            ->whereNotNull('answer_text')
            ->where('answer_text', '!=', '')
            ->get();

        $decrypted = 0;
        $skipped = 0;

        foreach ($answers as $answer) {
            try {
                // Try to decrypt - if it fails, it's already plain text
                $decryptedText = Crypt::decryptString($answer->answer_text);
                
                DB::table('answers')
                    ->where('id', $answer->id)
                    ->update(['answer_text' => $decryptedText]);
                
                $decrypted++;
            } catch (\Exception $e) {
                // Already plain text, skip
                $skipped++;
            }
        }

        $this->info("Done! Decrypted: {$decrypted}, Skipped (already plain): {$skipped}");
    }
}
