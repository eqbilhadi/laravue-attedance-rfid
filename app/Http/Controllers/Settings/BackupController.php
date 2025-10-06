<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DatabaseBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Throwable;

class BackupController extends Controller
{
    public function index()
    {
        $backups = DatabaseBackup::latest()->paginate(10);
        return Inertia::render('settings/backup/Index', [
            'backups' => $backups,
        ]);
    }

    public function store()
    {
        try {
            Artisan::call('db:backup-custom');
            return redirect()->route('settings.backup.index')->with('success', [
                'title' => 'Backup Created!',
                'description' => 'A new database backup has been generated successfully.'
            ]);
        } catch (Throwable $e) {
            Log::error('Manual backup failed', ['error' => $e->getMessage()]);
            return redirect()->route('settings.backup.index')->with('error', [
                'title' => 'Backup Failed!',
                'description' => 'Could not create a new backup. Please check the logs.'
            ]);
        }
    }

    public function download(DatabaseBackup $backup)
    {
        $filePath = config('backup.backup.name') . '/' . $backup->filename;

        if (!Storage::disk($backup->disk)->exists($filePath)) {
            abort(404, 'Backup file not found.');
        }

        return Storage::disk($backup->disk)->download($filePath);
    }

    public function destroy(DatabaseBackup $backup)
    {
        $filePath = config('backup.backup.name') . '/' . $backup->filename;

        try {
            if (Storage::disk($backup->disk)->exists($filePath)) {
                Storage::disk($backup->disk)->delete($filePath);
            }
            $backup->delete();
            return redirect()->back()->with('success', [
                'title' => 'Backup Deleted!',
                'description' => "Backup file '{$backup->filename}' has been deleted."
            ]);
        } catch (Throwable $e) {
            Log::error('Backup delete failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', [
                'title' => 'Delete Failed!',
                'description' => 'Could not delete the backup file.'
            ]);
        }
    }
}
