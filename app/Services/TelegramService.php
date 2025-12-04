<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamViolation;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $token;
    protected string $chatId;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
    }

    public function sendMessage(string $message, ?string $chatId = null, ?int $topicId = null): bool
    {
        if (!$this->token || (!$this->chatId && !$chatId)) {
            return false;
        }

        try {
            $payload = [
                'chat_id' => $chatId ?? $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ];
            
            // Add topic ID if provided
            if ($topicId) {
                $payload['message_thread_id'] = $topicId;
            }
            
            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram notification failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendDocument(string $filePath, string $filename, ?string $chatId = null, ?string $caption = null): bool
    {
        if (!$this->token || !file_exists($filePath)) {
            return false;
        }

        try {
            $response = Http::attach('document', file_get_contents($filePath), $filename)
                ->post("https://api.telegram.org/bot{$this->token}/sendDocument", [
                    'chat_id' => $chatId ?? $this->chatId,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram send document failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendMessageWithKeyboard(string $message, array $buttons, ?string $chatId = null, ?int $topicId = null): bool
    {
        if (!$this->token) {
            return false;
        }

        try {
            $payload = [
                'chat_id' => $chatId ?? $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => $buttons
                ]),
            ];

            if ($topicId) {
                $payload['message_thread_id'] = $topicId;
            }

            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", $payload);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram keyboard message failed: ' . $e->getMessage());
            return false;
        }
    }

    public function answerCallback(string $callbackId, string $text = ''): bool
    {
        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
                'callback_query_id' => $callbackId,
                'text' => $text,
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Send message to all notify IDs (personal + groups)
     */
    public function sendToAll(string $message): void
    {
        $notifyIds = array_filter(explode(',', config('services.telegram.notify_ids', '')));
        $groupTopicId = config('services.telegram.group_topic_id');
        
        foreach ($notifyIds as $chatId) {
            $chatId = trim($chatId);
            // If it's a group (negative ID) and topic is set, send to topic
            $topicId = (str_starts_with($chatId, '-') && $groupTopicId) ? (int) $groupTopicId : null;
            $this->sendMessage($message, $chatId, $topicId);
        }
    }

    // ==================== VIOLATION ALERTS ====================

    public function sendViolationAlert(array $data): bool
    {
        if ($this->isMuted()) {
            return false;
        }
        
        $message = "🚨 <b>PELANGGARAN TERDETEKSI</b>\n\n"
            . "👤 Siswa: <b>{$data['student_name']}</b>\n"
            . "📝 Ujian: {$data['exam_title']}\n"
            . "⚠️ Tipe: <b>{$data['violation_type']}</b>\n"
            . "📋 Detail: {$data['description']}\n"
            . "🔢 Pelanggaran ke: {$data['violation_count']}\n"
            . "🌐 IP: {$data['ip_address']}\n"
            . "🕐 Waktu: " . now()->format('d/m/Y H:i:s');

        // Inline keyboard buttons
        $nisn = $data['student_nisn'] ?? null;
        if ($nisn) {
            $buttons = [
                [
                    ['text' => '🔒 Block Siswa', 'callback_data' => "block_{$nisn}"],
                    ['text' => '🔄 Reset Violation', 'callback_data' => "reset_{$nisn}"],
                ],
                [
                    ['text' => '🚫 Kick dari Ujian', 'callback_data' => "kick_{$nisn}"],
                ]
            ];
            $this->sendToAllWithKeyboard($message, $buttons);
        } else {
            $this->sendToAll($message);
        }
        
        // Check for mass violations
        $this->checkMassViolation();
        
        return true;
    }

    public function sendToAllWithKeyboard(string $message, array $buttons): void
    {
        $notifyIds = array_filter(explode(',', config('services.telegram.notify_ids', '')));
        $groupTopicId = config('services.telegram.group_topic_id');
        
        foreach ($notifyIds as $chatId) {
            $chatId = trim($chatId);
            $topicId = (str_starts_with($chatId, '-') && $groupTopicId) ? (int) $groupTopicId : null;
            $this->sendMessageWithKeyboard($message, $buttons, $chatId, $topicId);
        }
    }

    public function checkMassViolation(): void
    {
        // Count violations in last 5 minutes
        $recentCount = ExamViolation::where('created_at', '>=', now()->subMinutes(5))->count();
        $uniqueStudents = ExamViolation::where('created_at', '>=', now()->subMinutes(5))
            ->distinct('student_id')
            ->count('student_id');

        // Alert if 5+ students got violations in 5 minutes
        if ($uniqueStudents >= 5) {
            $cacheKey = 'mass_violation_alert_' . now()->format('Y-m-d-H');
            
            // Only alert once per hour
            if (!cache()->has($cacheKey)) {
                $topViolations = ExamViolation::where('created_at', '>=', now()->subMinutes(5))
                    ->selectRaw('violation_type, COUNT(*) as count')
                    ->groupBy('violation_type')
                    ->orderByDesc('count')
                    ->limit(3)
                    ->pluck('count', 'violation_type');

                $message = "🚨🚨 <b>MASS VIOLATION ALERT</b> 🚨🚨\n\n"
                    . "⚠️ <b>{$uniqueStudents} siswa</b> melakukan pelanggaran dalam 5 menit terakhir!\n\n"
                    . "📊 Total: {$recentCount} pelanggaran\n\n"
                    . "<b>Tipe terbanyak:</b>\n";

                foreach ($topViolations as $type => $count) {
                    $message .= "• {$type}: {$count}x\n";
                }

                $message .= "\n⚠️ <i>Kemungkinan ada masalah teknis atau soal ujian.</i>\n"
                    . "🕐 " . now()->format('d/m/Y H:i:s');

                $this->sendToAll($message);
                cache()->put($cacheKey, true, now()->addHour());
            }
        }
    }

    public function sendStudentBlockedAlert(Student $student, string $reason): bool
    {
        if ($this->isMuted()) {
            return false;
        }
        
        $className = $student->classroom?->name ?? '-';
        $message = "🔒 <b>SISWA DIBLOKIR</b>\n\n"
            . "👤 Nama: <b>{$student->name}</b>\n"
            . "🆔 NISN: {$student->nisn}\n"
            . "🏫 Kelas: {$className}\n"
            . "📋 Alasan: {$reason}\n"
            . "🕐 Waktu: " . now()->format('d/m/Y H:i:s');

        $this->sendToAll($message);
        return true;
    }

    // ==================== EXAM SESSION ALERTS ====================

    public function sendExamStartedAlert(ExamSession $session): bool
    {
        $exam = $session->exam;
        $enrolledCount = $session->examGroups()->count();
        $lessonName = $exam->lesson?->name ?? '-';

        $message = "🟢 <b>SESI UJIAN DIMULAI</b>\n\n"
            . "📝 Ujian: <b>{$exam->title}</b>\n"
            . "📚 Mapel: {$lessonName}\n"
            . "👥 Peserta: {$enrolledCount} siswa\n"
            . "⏱️ Durasi: {$exam->duration} menit\n"
            . "🕐 Mulai: " . $session->start_time->format('d/m/Y H:i');

        return $this->sendMessage($message);
    }

    public function sendExamEndedAlert(ExamSession $session): bool
    {
        $exam = $session->exam;
        $grades = Grade::where('exam_session_id', $session->id)->get();
        $completed = $grades->whereNotNull('end_time')->count();
        $avgScore = $grades->whereNotNull('grade')->avg('grade');

        $message = "🔴 <b>SESI UJIAN BERAKHIR</b>\n\n"
            . "📝 Ujian: <b>{$exam->title}</b>\n"
            . "✅ Selesai: {$completed} siswa\n"
            . "📊 Rata-rata: " . ($avgScore ? number_format($avgScore, 1) : '-') . "\n"
            . "🕐 Selesai: " . now()->format('d/m/Y H:i');

        return $this->sendMessage($message);
    }

    // ==================== SCORE ALERTS ====================

    public function sendLowScoreAlert(Grade $grade): bool
    {
        $student = $grade->student;
        $exam = $grade->exam;

        $message = "⚠️ <b>NILAI DI BAWAH KKM</b>\n\n"
            . "👤 Siswa: <b>{$student->name}</b>\n"
            . "📝 Ujian: {$exam->title}\n"
            . "📊 Nilai: <b>{$grade->grade}</b>\n"
            . "🎯 KKM: {$exam->passing_grade}\n"
            . "🕐 Waktu: " . now()->format('d/m/Y H:i:s');

        return $this->sendMessage($message);
    }

    public function sendTopPerformersAlert(Exam $exam, $topStudents): bool
    {
        $message = "🏆 <b>TOP PERFORMERS</b>\n\n"
            . "📝 Ujian: <b>{$exam->title}</b>\n\n";

        foreach ($topStudents as $i => $grade) {
            $medal = match($i) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '⭐' };
            $message .= "{$medal} {$grade->student->name}: <b>{$grade->grade}</b>\n";
        }

        return $this->sendMessage($message);
    }

    // ==================== DAILY SUMMARY ====================

    public function sendDailySummary(): bool
    {
        $today = now()->startOfDay();

        $totalExams = ExamSession::whereDate('start_time', $today)->count();
        $totalParticipants = Grade::whereDate('created_at', $today)->distinct('student_id')->count('student_id');
        $completedExams = Grade::whereDate('end_time', $today)->count();
        $avgScore = Grade::whereDate('end_time', $today)->avg('grade');
        $totalViolations = ExamViolation::whereDate('created_at', $today)->count();
        $blockedStudents = Student::whereDate('blocked_at', $today)->count();

        $passedCount = Grade::whereDate('end_time', $today)
            ->whereHas('exam', fn($q) => $q->whereColumn('grades.grade', '>=', 'exams.passing_grade'))
            ->count();

        $message = "📊 <b>REKAP HARIAN</b>\n"
            . "📅 " . now()->format('d/m/Y') . "\n\n"
            . "📝 Sesi Ujian: {$totalExams}\n"
            . "👥 Total Peserta: {$totalParticipants}\n"
            . "✅ Ujian Selesai: {$completedExams}\n"
            . "📈 Rata-rata Nilai: " . ($avgScore ? number_format($avgScore, 1) : '-') . "\n"
            . "🎯 Lulus: {$passedCount}\n"
            . "🚨 Pelanggaran: {$totalViolations}\n"
            . "🔒 Siswa Diblokir: {$blockedStudents}";

        $this->sendToAll($message);
        return true;
    }

    public function sendWeeklyReport(): bool
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $totalExams = ExamSession::whereBetween('start_time', [$startOfWeek, $endOfWeek])->count();
        $totalGrades = Grade::whereBetween('end_time', [$startOfWeek, $endOfWeek])->count();
        $avgScore = Grade::whereBetween('end_time', [$startOfWeek, $endOfWeek])->avg('grade');
        $totalViolations = ExamViolation::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();

        $topExam = Exam::withCount(['grades' => fn($q) => $q->whereBetween('end_time', [$startOfWeek, $endOfWeek])])
            ->orderByDesc('grades_count')
            ->first();

        $message = "📈 <b>LAPORAN MINGGUAN</b>\n"
            . "📅 " . $startOfWeek->format('d/m') . " - " . $endOfWeek->format('d/m/Y') . "\n\n"
            . "📝 Total Sesi: {$totalExams}\n"
            . "✅ Ujian Dikerjakan: {$totalGrades}\n"
            . "📊 Rata-rata Nilai: " . ($avgScore ? number_format($avgScore, 1) : '-') . "\n"
            . "🚨 Total Pelanggaran: {$totalViolations}\n";

        if ($topExam) {
            $message .= "🔥 Ujian Terpopuler: {$topExam->title}";
        }

        $this->sendToAll($message);
        return true;
    }

    // ==================== STUDENT REMINDER ====================

    public function sendExamReminder(Student $student, ExamSession $session, string $timeframe): bool
    {
        if (!$student->telegram_chat_id) {
            return false;
        }

        $exam = $session->exam;
        $lessonName = $exam->lesson?->name ?? '-';
        $startTime = $session->start_time->format('d/m/Y H:i');
        $message = "⏰ <b>PENGINGAT UJIAN</b>\n\n"
            . "Hai <b>{$student->name}</b>!\n\n"
            . "📝 Ujian: <b>{$exam->title}</b>\n"
            . "📚 Mapel: {$lessonName}\n"
            . "🕐 Waktu: {$startTime}\n"
            . "⏱️ Durasi: {$exam->duration} menit\n\n"
            . "⚡ Ujian dimulai {$timeframe}!\n"
            . "Pastikan koneksi internet stabil.";

        return $this->sendMessage($message, $student->telegram_chat_id);
    }

    public function sendScoreToStudent(Student $student, Grade $grade): bool
    {
        if (!$student->telegram_chat_id) {
            return false;
        }

        $exam = $grade->exam;
        $passed = $grade->grade >= $exam->passing_grade;
        $status = $passed ? '✅ LULUS' : '❌ TIDAK LULUS';

        $message = "📊 <b>HASIL UJIAN</b>\n\n"
            . "Hai <b>{$student->name}</b>!\n\n"
            . "📝 Ujian: {$exam->title}\n"
            . "📈 Nilai: <b>{$grade->grade}</b>\n"
            . "🎯 KKM: {$exam->passing_grade}\n"
            . "📋 Status: {$status}";

        return $this->sendMessage($message, $student->telegram_chat_id);
    }

    // ==================== REAL-TIME STATUS ====================

    public function sendActiveExamStatus(): bool
    {
        $activeSessions = ExamSession::where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->with('exam')
            ->get();

        if ($activeSessions->isEmpty()) {
            return $this->sendMessage("📋 <b>STATUS UJIAN</b>\n\nTidak ada ujian yang sedang berlangsung.");
        }

        $message = "📋 <b>UJIAN AKTIF</b>\n\n";

        foreach ($activeSessions as $session) {
            $activeCount = Grade::where('exam_session_id', $session->id)
                ->whereNotNull('start_time')
                ->whereNull('end_time')
                ->count();

            $message .= "📝 {$session->exam->title}\n"
                . "   👥 Sedang mengerjakan: {$activeCount}\n"
                . "   ⏰ Berakhir: {$session->end_time->format('H:i')}\n\n";
        }

        return $this->sendMessage($message);
    }

    public function sendTodayViolations(): bool
    {
        $violations = ExamViolation::with(['student', 'exam'])
            ->whereDate('created_at', now())
            ->latest()
            ->limit(10)
            ->get();

        if ($violations->isEmpty()) {
            return $this->sendMessage("✅ <b>PELANGGARAN HARI INI</b>\n\nTidak ada pelanggaran tercatat.");
        }

        $total = ExamViolation::whereDate('created_at', now())->count();
        $message = "🚨 <b>PELANGGARAN HARI INI</b>\n"
            . "Total: {$total} pelanggaran\n\n";

        foreach ($violations as $v) {
            $time = $v->created_at->format('H:i');
            $message .= "⚠️ [{$time}] {$v->student->name}\n"
                . "   {$v->violation_type}\n";
        }

        return $this->sendMessage($message);
    }

    // ==================== BOT COMMANDS HANDLER ====================

    public function handleCommand(string $command, array $params = [], ?string $chatId = null): string
    {
        return match($command) {
            '/start' => $this->cmdStart(),
            '/help' => $this->cmdHelp(),
            '/status' => $this->cmdStatus(),
            '/violations' => $this->cmdViolations(),
            '/stats' => $this->cmdStats($params[0] ?? null),
            '/students_online' => $this->cmdStudentsOnline(),
            '/block' => $this->cmdBlock($params[0] ?? null),
            '/unblock' => $this->cmdUnblock($params[0] ?? null),
            '/extend' => $this->cmdExtend($params[0] ?? null, $params[1] ?? null),
            '/summary' => $this->cmdSummary(),
            // New commands
            '/search' => $this->cmdSearch(implode(' ', $params)),
            '/score' => $this->cmdScore($params[0] ?? null),
            '/exam_list' => $this->cmdExamList(),
            '/class' => $this->cmdClass(implode(' ', $params)),
            '/pause' => $this->cmdPause($params[0] ?? null),
            '/resume' => $this->cmdResume($params[0] ?? null),
            '/kick' => $this->cmdKick($params[0] ?? null),
            '/reset_violation' => $this->cmdResetViolation($params[0] ?? null),
            '/top' => $this->cmdTop($params[0] ?? null),
            '/failed' => $this->cmdFailed($params[0] ?? null),
            '/broadcast' => $this->cmdBroadcast(implode(' ', $params)),
            '/mute' => $this->cmdMute(),
            '/unmute' => $this->cmdUnmute(),
            '/health' => $this->cmdHealth(),
            '/export' => $this->cmdExport($params[0] ?? null, $chatId),
            default => "❓ Command tidak dikenal. Ketik /help"
        };
    }

    protected function cmdStart(): string
    {
        return "👋 <b>Selamat datang di Bot Ujian Online!</b>\n\n"
            . "Bot ini akan mengirimkan notifikasi:\n"
            . "• 🚨 Pelanggaran anti-cheat\n"
            . "• 🔒 Siswa diblokir\n"
            . "• 📊 Rekap harian & mingguan\n\n"
            . "Ketik /help untuk melihat perintah.";
    }

    protected function cmdHelp(): string
    {
        return "📖 <b>DAFTAR PERINTAH</b>\n\n"
            . "<b>📊 Info & Status</b>\n"
            . "/status - Ujian aktif\n"
            . "/students_online - Siswa sedang ujian\n"
            . "/summary - Rekap hari ini\n"
            . "/violations - Pelanggaran hari ini\n"
            . "/health - Cek server health\n\n"
            . "<b>🔍 Pencarian</b>\n"
            . "/search [nama] - Cari siswa\n"
            . "/score [nisn] - Nilai siswa\n"
            . "/exam_list - Ujian mendatang\n"
            . "/class [nama] - Info kelas\n"
            . "/stats [exam_id] - Statistik ujian\n"
            . "/top [exam_id] - Top 5 nilai\n"
            . "/failed [exam_id] - Siswa tidak lulus\n\n"
            . "<b>⚡ Quick Actions</b>\n"
            . "/block [nisn] - Blokir siswa\n"
            . "/unblock [nisn] - Unblock siswa\n"
            . "/extend [nisn] [menit] - Tambah waktu\n"
            . "/pause [nisn] - Pause ujian\n"
            . "/resume [nisn] - Resume ujian\n"
            . "/kick [nisn] - Force submit\n"
            . "/reset_violation [nisn] - Reset pelanggaran\n\n"
            . "<b>📤 Export</b>\n"
            . "/export [exam_id] - Export PDF hasil ujian\n\n"
            . "<b>📢 Lainnya</b>\n"
            . "/broadcast [pesan] - Kirim ke semua admin\n"
            . "/mute - Matikan notifikasi\n"
            . "/unmute - Nyalakan notifikasi";
    }

    protected function cmdStatus(): string
    {
        $activeSessions = ExamSession::where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->with('exam')
            ->get();

        if ($activeSessions->isEmpty()) {
            return "📋 Tidak ada ujian aktif saat ini.";
        }

        $message = "📋 <b>UJIAN AKTIF</b>\n\n";
        foreach ($activeSessions as $session) {
            $activeCount = Grade::where('exam_session_id', $session->id)
                ->whereNotNull('start_time')
                ->whereNull('end_time')
                ->count();
            $message .= "• {$session->exam->title}: {$activeCount} siswa\n";
        }

        return $message;
    }

    protected function cmdViolations(): string
    {
        $violations = ExamViolation::with('student')
            ->whereDate('created_at', now())
            ->latest()
            ->limit(5)
            ->get();

        if ($violations->isEmpty()) {
            return "✅ Tidak ada pelanggaran hari ini.";
        }

        $message = "🚨 <b>PELANGGARAN HARI INI</b>\n\n";
        foreach ($violations as $v) {
            $message .= "• {$v->student->name}: {$v->violation_type}\n";
        }

        return $message;
    }

    protected function cmdStats(?string $examId): string
    {
        if (!$examId) {
            return "❓ Gunakan: /stats [exam_id]";
        }

        $exam = Exam::find($examId);
        if (!$exam) {
            return "❌ Ujian tidak ditemukan.";
        }

        $grades = $exam->grades()->whereNotNull('grade')->get();
        $avg = $grades->avg('grade');
        $passed = $grades->where('grade', '>=', $exam->passing_grade)->count();

        return "📊 <b>{$exam->title}</b>\n\n"
            . "👥 Peserta: {$grades->count()}\n"
            . "📈 Rata-rata: " . number_format($avg ?? 0, 1) . "\n"
            . "✅ Lulus: {$passed}\n"
            . "❌ Tidak Lulus: " . ($grades->count() - $passed);
    }

    protected function cmdStudentsOnline(): string
    {
        $count = Grade::whereNotNull('start_time')
            ->whereNull('end_time')
            ->count();

        return "👥 <b>{$count}</b> siswa sedang mengerjakan ujian.";
    }

    protected function cmdBlock(?string $nisn): string
    {
        if (!$nisn) {
            return "❓ Gunakan: /block [nisn]";
        }

        $student = Student::where('nisn', $nisn)->first();
        if (!$student) {
            return "❌ Siswa dengan NISN {$nisn} tidak ditemukan.";
        }

        if ($student->is_blocked) {
            return "⚠️ Siswa sudah diblokir sebelumnya.";
        }

        $student->block('Diblokir via Telegram Bot');

        return "🔒 <b>{$student->name}</b> berhasil diblokir.";
    }

    protected function cmdUnblock(?string $nisn): string
    {
        if (!$nisn) {
            return "❓ Gunakan: /unblock [nisn]";
        }

        $student = Student::where('nisn', $nisn)->first();
        if (!$student) {
            return "❌ Siswa dengan NISN {$nisn} tidak ditemukan.";
        }

        if (!$student->is_blocked) {
            return "⚠️ Siswa tidak dalam status diblokir.";
        }

        $student->unblock();

        return "🔓 <b>{$student->name}</b> berhasil di-unblock.";
    }

    protected function cmdExtend(?string $nisn, ?string $minutes): string
    {
        if (!$nisn || !$minutes) {
            return "❓ Gunakan: /extend [nisn] [menit]";
        }

        $student = Student::where('nisn', $nisn)->first();
        if (!$student) {
            return "❌ Siswa tidak ditemukan.";
        }

        $grade = Grade::where('student_id', $student->id)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->first();

        if (!$grade) {
            return "⚠️ Siswa tidak sedang mengerjakan ujian.";
        }

        $grade->increment('duration', (int) $minutes);

        return "⏱️ Waktu <b>{$student->name}</b> ditambah {$minutes} menit.";
    }

    protected function cmdSummary(): string
    {
        $today = now()->startOfDay();
        $totalParticipants = Grade::whereDate('created_at', $today)->count();
        $completed = Grade::whereDate('end_time', $today)->count();
        $violations = ExamViolation::whereDate('created_at', $today)->count();

        return "📊 <b>REKAP HARI INI</b>\n\n"
            . "👥 Peserta: {$totalParticipants}\n"
            . "✅ Selesai: {$completed}\n"
            . "🚨 Pelanggaran: {$violations}";
    }

    // ==================== NEW COMMANDS ====================

    protected function cmdSearch(string $name): string
    {
        if (empty($name)) {
            return "❓ Gunakan: /search [nama]";
        }

        $students = Student::where('name', 'like', "%{$name}%")->limit(10)->get();

        if ($students->isEmpty()) {
            return "❌ Siswa tidak ditemukan.";
        }

        $message = "🔍 <b>HASIL PENCARIAN</b>\n\n";
        foreach ($students as $s) {
            $status = $s->is_blocked ? '🔒' : '✅';
            $message .= "{$status} {$s->name}\n   NISN: <code>{$s->nisn}</code>\n";
        }

        return $message;
    }

    protected function cmdScore(?string $nisn): string
    {
        if (!$nisn) {
            return "❓ Gunakan: /score [nisn]";
        }

        $student = Student::where('nisn', $nisn)->first();
        if (!$student) {
            return "❌ Siswa tidak ditemukan.";
        }

        $grades = Grade::with('exam')
            ->where('student_id', $student->id)
            ->whereNotNull('grade')
            ->latest()
            ->limit(5)
            ->get();

        if ($grades->isEmpty()) {
            return "📊 <b>{$student->name}</b>\n\nBelum ada nilai.";
        }

        $message = "📊 <b>{$student->name}</b>\n\n";
        foreach ($grades as $g) {
            $status = $g->grade >= ($g->exam->passing_grade ?? 0) ? '✅' : '❌';
            $message .= "{$status} {$g->exam->title}: <b>{$g->grade}</b>\n";
        }

        return $message;
    }

    protected function cmdExamList(): string
    {
        $sessions = ExamSession::with('exam')
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        if ($sessions->isEmpty()) {
            return "📅 Tidak ada ujian yang dijadwalkan.";
        }

        $message = "📅 <b>UJIAN MENDATANG</b>\n\n";
        foreach ($sessions as $s) {
            $message .= "📝 {$s->exam->title}\n"
                . "   🕐 {$s->start_time->format('d/m H:i')}\n";
        }

        return $message;
    }

    protected function cmdClass(string $name): string
    {
        if (empty($name)) {
            return "❓ Gunakan: /class [nama_kelas]";
        }

        $classroom = \App\Models\Classroom::where('name', 'like', "%{$name}%")->first();
        if (!$classroom) {
            return "❌ Kelas tidak ditemukan.";
        }

        $total = $classroom->students()->count();
        $blocked = $classroom->students()->where('is_blocked', true)->count();

        return "🏫 <b>{$classroom->name}</b>\n\n"
            . "👥 Total Siswa: {$total}\n"
            . "🔒 Diblokir: {$blocked}";
    }

    protected function cmdPause(?string $nisn): string
    {
        if (!$nisn) {
            return "❓ Gunakan: /pause [nisn]";
        }

        $student = Student::where('nisn', $nisn)->first();
        if (!$student) {
            return "❌ Siswa tidak ditemukan.";
        }

        $grade = Grade::where('student_id', $student->id)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->first();

        if (!$grade) {
            return "⚠️ Siswa tidak sedang ujian.";
        }

        $grade->update(['is_paused' => true, 'paused_at' => now(), 'pause_reason' => 'Paused via Telegram']);

        return "⏸️ Ujian <b>{$student->name}</b> di-pause.";
    }

    protected function cmdResume(?string $nisn): string
    {
        if (!$nisn) {
            return "❓ Gunakan: /resume [nisn]";
        }

        $student = Student::where('nisn', $nisn)->first();
        if (!$student) {
            return "❌ Siswa tidak ditemukan.";
        }

        $grade = Grade::where('student_id', $student->id)->where('is_paused', true)->first();

        if (!$grade) {
            return "⚠️ Tidak ada ujian yang di-pause.";
        }

        $grade->update(['is_paused' => false, 'paused_at' => null]);

        return "▶️ Ujian <b>{$student->name}</b> dilanjutkan.";
    }

    protected function cmdKick(?string $nisn): string
    {
        if (!$nisn) {
            return "❓ Gunakan: /kick [nisn]";
        }

        $student = Student::where('nisn', $nisn)->first();
        if (!$student) {
            return "❌ Siswa tidak ditemukan.";
        }

        $grade = Grade::where('student_id', $student->id)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->first();

        if (!$grade) {
            return "⚠️ Siswa tidak sedang ujian.";
        }

        $grade->update(['end_time' => now(), 'status' => 'force_submitted']);

        return "🚫 Ujian <b>{$student->name}</b> di-force submit.";
    }

    protected function cmdResetViolation(?string $nisn): string
    {
        if (!$nisn) {
            return "❓ Gunakan: /reset_violation [nisn]";
        }

        $student = Student::where('nisn', $nisn)->first();
        if (!$student) {
            return "❌ Siswa tidak ditemukan.";
        }

        $grade = Grade::where('student_id', $student->id)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->first();

        if (!$grade) {
            return "⚠️ Siswa tidak sedang ujian.";
        }

        $grade->update([
            'violation_count' => 0,
            'tab_switch_count' => 0,
            'fullscreen_exit_count' => 0,
            'copy_paste_count' => 0,
            'right_click_count' => 0,
            'blur_count' => 0,
            'is_flagged' => false,
        ]);

        return "🔄 Pelanggaran <b>{$student->name}</b> di-reset.";
    }

    protected function cmdTop(?string $examId): string
    {
        if (!$examId) {
            return "❓ Gunakan: /top [exam_id]";
        }

        $exam = Exam::find($examId);
        if (!$exam) {
            return "❌ Ujian tidak ditemukan.";
        }

        $grades = Grade::with('student')
            ->where('exam_id', $examId)
            ->whereNotNull('grade')
            ->orderByDesc('grade')
            ->limit(5)
            ->get();

        if ($grades->isEmpty()) {
            return "📊 Belum ada nilai untuk ujian ini.";
        }

        $message = "🏆 <b>TOP 5 - {$exam->title}</b>\n\n";
        foreach ($grades as $i => $g) {
            $medal = match($i) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '⭐' };
            $message .= "{$medal} {$g->student->name}: <b>{$g->grade}</b>\n";
        }

        return $message;
    }

    protected function cmdFailed(?string $examId): string
    {
        if (!$examId) {
            return "❓ Gunakan: /failed [exam_id]";
        }

        $exam = Exam::find($examId);
        if (!$exam) {
            return "❌ Ujian tidak ditemukan.";
        }

        $grades = Grade::with('student')
            ->where('exam_id', $examId)
            ->whereNotNull('grade')
            ->where('grade', '<', $exam->passing_grade ?? 0)
            ->orderBy('grade')
            ->limit(10)
            ->get();

        if ($grades->isEmpty()) {
            return "✅ Semua siswa lulus ujian ini!";
        }

        $message = "❌ <b>TIDAK LULUS - {$exam->title}</b>\nKKM: {$exam->passing_grade}\n\n";
        foreach ($grades as $g) {
            $message .= "• {$g->student->name}: {$g->grade}\n";
        }

        return $message;
    }

    protected function cmdBroadcast(string $pesan): string
    {
        if (empty($pesan)) {
            return "❓ Gunakan: /broadcast [pesan]";
        }

        $adminIds = array_filter(explode(',', config('services.telegram.admin_ids', '')));
        $sent = 0;

        foreach ($adminIds as $chatId) {
            if ($this->sendMessage("📢 <b>BROADCAST</b>\n\n{$pesan}", trim($chatId))) {
                $sent++;
            }
        }

        return "✅ Broadcast terkirim ke {$sent} admin.";
    }

    protected function cmdMute(): string
    {
        cache()->put('telegram_muted', true, now()->addHours(24));
        return "🔇 Notifikasi dimatikan selama 24 jam.";
    }

    protected function cmdUnmute(): string
    {
        cache()->forget('telegram_muted');
        return "🔔 Notifikasi dinyalakan kembali.";
    }

    protected function cmdHealth(): string
    {
        $metrics = [];
        $status = "✅";

        // Database
        $dbStart = microtime(true);
        try {
            \DB::select('SELECT 1');
            $metrics['db'] = round((microtime(true) - $dbStart) * 1000) . 'ms';
        } catch (\Exception $e) {
            $metrics['db'] = '❌ ERROR';
            $status = "⚠️";
        }

        // Disk
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskPercent = round((1 - $diskFree / $diskTotal) * 100);
        $metrics['disk'] = $diskPercent . '%';
        if ($diskPercent > 80) $status = "⚠️";

        // Memory
        $memInfo = @file_get_contents('/proc/meminfo');
        if ($memInfo && preg_match('/MemTotal:\s+(\d+)/', $memInfo, $total) && preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $avail)) {
            $memPercent = round((1 - $avail[1] / $total[1]) * 100);
            $metrics['memory'] = $memPercent . '%';
            if ($memPercent > 80) $status = "⚠️";
        } else {
            $metrics['memory'] = 'N/A';
        }

        // Active exams
        $activeExams = Grade::whereNotNull('start_time')->whereNull('end_time')->count();

        return "{$status} <b>SERVER HEALTH</b>\n\n"
            . "🗄️ Database: {$metrics['db']}\n"
            . "💾 Disk: {$metrics['disk']}\n"
            . "🧠 Memory: {$metrics['memory']}\n"
            . "📝 Ujian Aktif: {$activeExams} siswa\n"
            . "🕐 " . now()->format('d/m/Y H:i:s');
    }

    protected function cmdExport(?string $examId, ?string $chatId): string
    {
        if (!$examId) {
            return "❓ Gunakan: /export [exam_id]";
        }

        $exam = Exam::find($examId);
        if (!$exam) {
            return "❌ Ujian tidak ditemukan.";
        }

        // Dispatch to queue for background processing
        \App\Jobs\ExportPdfJob::dispatch((int) $examId, $chatId);

        return "📤 Export sedang diproses... File akan dikirim sebentar lagi.";
    }

    public function isMuted(): bool
    {
        return cache()->get('telegram_muted', false);
    }
}
