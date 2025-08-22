<?php

namespace App\Console\Commands;

use App\Enum\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\RfidScan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessDailyAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:process {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process daily attendance records from RFID scans';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Tentukan tanggal yang akan diproses.
        // Jika tidak ada flag --date, proses untuk hari kemarin.
        $processingDate = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : Carbon::yesterday()->startOfDay();
        $this->info("Processing attendance for: " . $processingDate->toDateString());

        // 1. Cek apakah hari ini adalah hari libur nasional
        $isHoliday = Holiday::where('date', $processingDate->toDateString())->exists();

        // 2. Ambil semua user yang aktif DAN memiliki kartu RFID terdaftar.
        // Ini adalah optimisasi agar tidak memproses user yang tidak relevan.
        // Pastikan Anda sudah menambahkan relasi hasOne('rfidCard') di model User.
        $activeUsers = User::where('is_active', true)->whereHas('rfidCard')->get();

        foreach ($activeUsers as $user) {
            $this->info("Processing user: {$user->name}");

            // 3. Cari jadwal yang berlaku untuk user ini pada tanggal yang diproses
            $userSchedule = $user->userSchedules()
                ->where('start_date', '<=', $processingDate)
                ->where(fn($q) => $q->where('end_date', '>=', $processingDate)->orWhereNull('end_date'))
                ->first();

            if (!$userSchedule) {
                $this->warn("No active schedule found for {$user->name}. Skipping.");
                continue; // Lanjut ke user berikutnya jika tidak ada jadwal
            }

            // 4. Ambil detail jadwal harian dari Work Schedule
            $dayOfWeek = $processingDate->dayOfWeekIso; // Senin = 1, Minggu = 7
            $workScheduleDay = $userSchedule->workSchedule->days()->where('day_of_week', $dayOfWeek)->first();

            // Tentukan status awal berdasarkan jadwal dan hari libur
            $status = AttendanceStatus::ABSENT;
            if ($isHoliday) {
                $status = AttendanceStatus::HOLIDAY;
            } elseif (!$workScheduleDay || !$workScheduleDay->time) {
                // Jika tidak ada jadwal harian atau jadwalnya adalah Off Day
                $status = AttendanceStatus::HOLIDAY;
            }

            // 5. Ambil semua log scan user pada hari itu
            // Kita mencari berdasarkan card_uid dari relasi rfidCard, bukan user_id.
            $scans = RfidScan::where('card_uid', $user->rfidCard->uid)
                ->whereDate('created_at', $processingDate)
                ->orderBy('created_at', 'asc')
                ->get();

            $clockIn = $scans->first()?->created_at;
            $clockOut = $scans->count() > 1 ? $scans->last()?->created_at : null;
            $lateMinutes = 0;

            // 6. Jika ada scan masuk, tentukan status dan hitung keterlambatan
            if ($clockIn && $status !== AttendanceStatus::HOLIDAY) {
                $workTime = $workScheduleDay->time;

                // --- PERBAIKAN DI SINI ---
                // Buat waktu terjadwal dengan cara yang lebih aman, hindari parsing string.
                list($hour, $minute) = explode(':', $workTime->start_time);
                // Cast string to integer to satisfy the setTime() method signature.
                $scheduledStartTime = $processingDate->copy()->setTime((int)$hour, (int)$minute, 0);

                // Tambahkan toleransi ke jadwal masuk
                $deadlineTime = $scheduledStartTime->copy()->addMinutes($workTime->late_tolerance_minutes);
                
                // Hitung selisih menit dari waktu terjadwal ke waktu clock-in.
                if ($clockIn->isAfter($scheduledStartTime)) {
                    $lateMinutes = (int) $scheduledStartTime->diffInMinutes($clockIn);
                } else {
                    $lateMinutes = 0;
                }

                // Tentukan status berdasarkan apakah keterlambatan melebihi toleransi.
                if ($lateMinutes > $workTime->late_tolerance_minutes) {
                    $status = AttendanceStatus::LATE;
                } else {
                    $status = AttendanceStatus::PRESENT;
                }
            }

            // --- LOGIKA BARU: Cek Izin/Sakit/Cuti jika status masih ABSENT ---
            // if ($status === AttendanceStatus::ABSENT) {
            //     // Asumsi Anda memiliki model LeaveRequest dan relasi leaveRequests() di model User
            //     $approvedLeave = $user->leaveRequests()
            //         ->with('leaveType') // Muat relasi ke LeaveType
            //         ->where('status', 'Approved') // Hanya yang sudah disetujui
            //         ->where('start_date', '<=', $processingDate->toDateString())
            //         ->where('end_date', '>=', $processingDate->toDateString())
            //         ->first();

            //     if ($approvedLeave) {
            //         // Tentukan status berdasarkan properti di LeaveType
            //         if ($approvedLeave->leaveType->is_deducting_leave) {
            //             $status = AttendanceStatus::LEAVE; // Cuti Tahunan, Cuti Bersama
            //         } elseif (stripos($approvedLeave->leaveType->name, 'sakit') !== false) {
            //             $status = AttendanceStatus::SICK; // Izin Sakit
            //         } else {
            //             $status = AttendanceStatus::PERMIT; // Izin lain (Dinas Luar, dll)
            //         }
            //     }
            // }

            // 7. Simpan atau update data ke tabel trx_attendances
            Attendance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $processingDate->toDateString(),
                ],
                [
                    'work_schedule_id' => $userSchedule->work_schedule_id,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'late_minutes' => $lateMinutes,
                    'status' => $status,
                    'notes' => 'Processed automatically.',
                ]
            );
        }

        $this->info("Attendance processing finished successfully.");
        return 0;
    }
}
