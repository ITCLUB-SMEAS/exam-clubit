<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HandlesTransactions
{
    /**
     * Execute callback within database transaction with error handling
     */
    protected function executeInTransaction(callable $callback, ?string $errorMessage = null)
    {
        try {
            return DB::transaction($callback);
        } catch (\Throwable $e) {
            Log::error('Transaction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = $errorMessage ?? 'Terjadi kesalahan. Silakan coba lagi.';
            
            if (request()->wantsJson()) {
                return response()->json(['error' => $message], 500);
            }

            return back()->withErrors(['error' => $message])->withInput();
        }
    }

    /**
     * Execute callback with try-catch and return appropriate response
     */
    protected function safeExecute(callable $callback, ?string $errorMessage = null)
    {
        try {
            return $callback();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $message = 'Data tidak ditemukan.';
            return $this->handleError($message, 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle validation errors
        } catch (\Throwable $e) {
            Log::error('Operation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $message = $errorMessage ?? 'Terjadi kesalahan. Silakan coba lagi.';
            return $this->handleError($message, 500);
        }
    }

    /**
     * Handle error response based on request type
     */
    private function handleError(string $message, int $status = 500)
    {
        if (request()->wantsJson()) {
            return response()->json(['error' => $message], $status);
        }

        if ($status === 404) {
            abort(404, $message);
        }

        return back()->withErrors(['error' => $message])->withInput();
    }
}
