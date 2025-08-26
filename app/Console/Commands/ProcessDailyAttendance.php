<?php

namespace App\Console\Commands;

use App\Enum\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\RawAttendance;
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
        $processingDate = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : Carbon::yesterday()->startOfDay();
        $this->info("Processing attendance for: " . $processingDate->toDateString());

        $isHoliday = Holiday::where('date', $processingDate->toDateString())->exists();
        $activeUsers = User::where('is_active', true)->whereHas('rfidCard')->get();

        foreach ($activeUsers as $user) {
            $this->info("Processing user: {$user->name}");

            $userSchedule = $user->userSchedules()
                ->where('start_date', '<=', $processingDate)
                ->where(fn ($q) => $q->where('end_date', '>=', $processingDate)->orWhereNull('end_date'))
                ->first();

            if (!$userSchedule) {
                $this->warn("No active schedule found for {$user->name}. Skipping.");
                continue;
            }

            $dayOfWeek = $processingDate->dayOfWeekIso;
            $workScheduleDay = $userSchedule->workSchedule->days()->where('day_of_week', $dayOfWeek)->first();

            $status = AttendanceStatus::ABSENT;
            if ($isHoliday) {
                $status = AttendanceStatus::HOLIDAY;
            } elseif (!$workScheduleDay || !$workScheduleDay->time) {
                $status = AttendanceStatus::HOLIDAY;
            }

            // --- LOGIKA BARU YANG LEBIH SEDERHANA ---
            $clockIn = null;
            $clockOut = null;
            $lateMinutes = 0;

            // Ambil data dari tabel log presensi yang bersih
            $rawAttendance = RawAttendance::where('user_id', $user->id)
                ->where('date', $processingDate->toDateString())
                ->first();

            if ($rawAttendance) {
                $clockIn = $rawAttendance->clock_in;
                $clockOut = $rawAttendance->clock_out;

                // Hitung keterlambatan jika ada jam masuk dan bukan hari libur
                if ($clockIn && $status !== AttendanceStatus::HOLIDAY) {
                    $workTime = $workScheduleDay->time;
                    list($startHour, $startMinute) = explode(':', $workTime->start_time);
                    $scheduledStartTime = $processingDate->copy()->setTime((int)$startHour, (int)$startMinute, 0);

                    if ($clockIn->isAfter($scheduledStartTime)) {
                        $lateMinutes = (int) $scheduledStartTime->diffInMinutes($clockIn);
                    }

                    if ($lateMinutes > $workTime->late_tolerance_minutes) {
                        $status = AttendanceStatus::LATE;
                    } else {
                        $status = AttendanceStatus::PRESENT;
                    }
                }
            }
            
            // Cek Izin/Sakit/Cuti jika status masih ABSENT
            if ($status === AttendanceStatus::ABSENT) {
                $approvedLeave = $user->leaveRequests()
                    ->with('leaveType')
                    ->where('status', 'Approved')
                    ->where('start_date', '<=', $processingDate->toDateString())
                    ->where('end_date', '>=', $processingDate->toDateString())
                    ->first();

                if ($approvedLeave) {
                    if ($approvedLeave->leaveType->is_deducting_leave) {
                        $status = AttendanceStatus::LEAVE;
                    } elseif (stripos($approvedLeave->leaveType->name, 'sakit') !== false) {
                        $status = AttendanceStatus::SICK;
                    } else {
                        $status = AttendanceStatus::PERMIT;
                    }
                }
            }

            // Simpan hasil akhir ke tabel data matang
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
