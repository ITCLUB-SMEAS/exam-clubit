<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function __construct(protected BackupService $backup) {}

    public function index()
    {
        return Inertia::render('Admin/Backup/Index', [
            'backups' => $this->backup->listBackups(),
        ]);
    }

    public function create()
    {
        $path = $this->backup->createDatabaseBackup();

        if (!$path) {
            return back()->with('error', 'Gagal membuat backup.');
        }

        return back()->with('success', 'Backup berhasil dibuat.');
    }

    public function download(string $filename)
    {
        $path = $this->backup->downloadBackup($filename);

        if (!$path) {
            return back()->with('error', 'File backup tidak ditemukan.');
        }

        return response()->download($path);
    }

    public function destroy(string $filename)
    {
        if (!$this->backup->deleteBackup($filename)) {
            return back()->with('error', 'Gagal menghapus backup.');
        }

        return back()->with('success', 'Backup berhasil dihapus.');
    }

    public function cleanup()
    {
        $deleted = $this->backup->cleanOldBackups(7);
        return back()->with('success', "{$deleted} backup lama berhasil dihapus.");
    }
}
