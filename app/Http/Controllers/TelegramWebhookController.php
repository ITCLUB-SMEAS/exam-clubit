<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    /**
     * Telegram IP ranges (as per official documentation)
     *
     * @see https://core.telegram.org/bots/webhooks#the-short-version
     */
    protected array $telegramIpRanges = [
        '149.154.160.0/20',
        '91.108.4.0/22',
    ];

    public function handle(Request $request, TelegramService $telegram)
    {
        // 1. Verify IP is from Telegram (production only)
        if (app()->environment('production')) {
            if (! $this->isFromTelegram($request->ip())) {
                Log::warning('Telegram webhook: Invalid source IP', [
                    'ip' => $request->ip(),
                ]);
                abort(403, 'Invalid source IP');
            }
        }

        // 2. Verify secret token from Telegram header
        $secret = config('services.telegram.webhook_secret');
        $headerSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        // In production, secret is required
        if (app()->environment('production')) {
            if (empty($secret)) {
                Log::error('Telegram webhook: Secret not configured');
                abort(500, 'Webhook secret not configured');
            }

            if ($headerSecret !== $secret) {
                Log::warning('Telegram webhook: Invalid secret token');
                abort(403, 'Invalid webhook secret');
            }
        } else {
            // In development, warn but allow if not configured
            if (! empty($secret) && $headerSecret !== $secret) {
                abort(403, 'Invalid webhook secret');
            }
        }

        $update = $request->all();

        // Handle callback query (button clicks)
        if (isset($update['callback_query'])) {
            return $this->handleCallback($update['callback_query'], $telegram);
        }

        $message = $update['message'] ?? null;

        if (! $message || ! isset($message['text'])) {
            return response()->json(['ok' => true]);
        }

        $chatId = $message['chat']['id'];

        // Check if user is authorized admin
        if (! $this->isAuthorizedAdmin($chatId)) {
            $telegram->sendMessage("⛔ Akses ditolak.\nAnda tidak terdaftar sebagai admin.", $chatId);

            return response()->json(['ok' => true]);
        }

        $text = trim($message['text']);

        // Only respond to commands (starting with /)
        if (! str_starts_with($text, '/')) {
            return response()->json(['ok' => true]);
        }

        $parts = explode(' ', $text);
        $command = $parts[0];
        $params = array_slice($parts, 1);

        $response = $telegram->handleCommand($command, $params, (string) $chatId);
        $telegram->sendMessage($response, (string) $chatId);

        return response()->json(['ok' => true]);
    }

    /**
     * Check if the request IP is from Telegram's IP ranges
     */
    protected function isFromTelegram(string $ip): bool
    {
        foreach ($this->telegramIpRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address is within a CIDR range
     */
    protected function ipInRange(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);

        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    protected function handleCallback(array $callback, TelegramService $telegram)
    {
        $chatId = $callback['message']['chat']['id'];
        $data = $callback['data'];
        $callbackId = $callback['id'];

        // Check authorization
        if (! $this->isAuthorizedAdmin($chatId)) {
            $telegram->answerCallback($callbackId, '⛔ Akses ditolak');

            return response()->json(['ok' => true]);
        }

        // Parse callback data
        [$action, $param] = explode('_', $data, 2) + [null, null];

        $response = match ($action) {
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
