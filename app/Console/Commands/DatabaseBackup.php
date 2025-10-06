<?php

namespace App\Console\Commands;

use App\Models\DatabaseBackup as DatabaseBackupModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\BackupCollection;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;
use Throwable;

class DatabaseBackup extends Command
{
    protected $signature = 'db:backup-custom';
    protected $description = 'Creates a new database backup and records it in the database.';

    public function handle()
    {
        $this->info('Starting database backup...');

        try {
            // Jalankan command backup dari Spatie
            Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);
            $this->info('Spatie backup command executed successfully.');

            // Ambil nama disk dari konfigurasi Spatie
            $diskName = config('backup.backup.destination.disks')[0];
            $path = config('backup.backup.name');

            // Temukan file backup terbaru yang baru saja dibuat
            $backups = Storage::disk($diskName)->files($path);
            $latestBackupPath = collect($backups)->last();

            if (!$latestBackupPath) {
                throw new \Exception('No backup file found after running the backup command.');
            }

            $filename = basename($latestBackupPath);
            $sizeInBytes = Storage::disk($diskName)->size($latestBackupPath);

            // Simpan record ke database
            DatabaseBackupModel::create([
                'date' => now(),
                'filename' => $filename,
                'filename' => $filename,
                'disk' => $diskName,
                'size_in_kb' => $sizeInBytes / 1024,
            ]);

            $this->info("Backup successful. File '{$filename}' has been recorded.");
            return 0;
        } catch (Throwable $e) {
            $this->error('Backup failed!');
            $this->error($e->getMessage());
            Log::error('Custom database backup failed', ['error' => $e->getMessage()]);
            return 1;
        }
    }
}
