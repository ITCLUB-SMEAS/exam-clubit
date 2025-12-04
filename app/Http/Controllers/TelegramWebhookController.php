<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramService $telegram)
    {
        // Verify secret token from Telegram
        $secret = config('services.telegram.webhook_secret');
        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            abort(403);
        }

        $update = $request->all();

        // Handle callback query (button clicks)
        if (isset($update['callback_query'])) {
            return $this->handleCallback($update['callback_query'], $telegram);
        }

        $message = $update['message'] ?? null;

        if (!$message || !isset($message['text'])) {
            return response()->json(['ok' => true]);
        }

        $chatId = $message['chat']['id'];
        
        // Check if user is authorized admin
        if (!$this->isAuthorizedAdmin($chatId)) {
            $telegram->sendMessage("⛔ Akses ditolak.\nAnda tidak terdaftar sebagai admin.", $chatId);
            return response()->json(['ok' => true]);
        }

        $text = trim($message['text']);
        
        // Only respond to commands (starting with /)
        if (!str_starts_with($text, '/')) {
            return response()->json(['ok' => true]);
        }
        
        $parts = explode(' ', $text);
        $command = $parts[0];
        $params = array_slice($parts, 1);

        $response = $telegram->handleCommand($command, $params, (string) $chatId);
        $telegram->sendMessage($response, (string) $chatId);

        return response()->json(['ok' => true]);
    }

    protected function handleCallback(array $callback, TelegramService $telegram)
    {
        $chatId = $callback['message']['chat']['id'];
        $data = $callback['data'];
        $callbackId = $callback['id'];

        // Check authorization
        if (!$this->isAuthorizedAdmin($chatId)) {
            $telegram->answerCallback($callbackId, '⛔ Akses ditolak');
            return response()->json(['ok' => true]);
        }

        // Parse callback data
        [$action, $param] = explode('_', $data, 2) + [null, null];

        $response = match($action) {
            'block' => $telegram->handleCommand('/block', [$param], (string) $chatId),
            'reset' => $telegram->handleCommand('/reset_violation', [$param], (string) $chatId),
            'kick' => $telegram->handleCommand('/kick', [$param], (string) $chatId),
            'newtoken' => $telegram->handleCommand('/new_token', [$param], (string) $chatId),
            'token' => $telegram->handleCommand('/token', [$param], (string) $chatId),
            default => '❓ Action tidak dikenal',
        };

        // Answer callback and send response
        $telegram->answerCallback($callbackId, '✅ Diproses');
        $telegram->sendMessage($response, (string) $chatId);

        return response()->json(['ok' => true]);
    }

    protected function isAuthorizedAdmin(int $chatId): bool
    {
        $adminIds = config('services.telegram.admin_ids', '');
        $allowedIds = array_filter(array_map('trim', explode(',', $adminIds)));
        
        return in_array((string) $chatId, $allowedIds);
    }
}
