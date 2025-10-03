<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceCorrectionController extends Controller
{
    /**
     * Menampilkan halaman utama untuk koreksi presensi.
     */
    public function index()
    {
        return Inertia::render('attendance/correction/Index', [
            'users' => User::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Mengambil data presensi yang ada untuk user dan tanggal tertentu.
     * Juga akan menyertakan jadwal kerja harian jika ditemukan.
     */
    public function fetch(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:sys_users,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $user = User::find($validated['user_id']);
        $date = Carbon::parse($validated['date']);

        // Cek dulu apakah ada assignment jadwal aktif untuk user ini
        $userSchedule = $user->userSchedules()
            ->where('start_date', '<=', $date)
            ->where(fn ($q) => $q->where('end_date', '>=', $date)->orWhereNull('end_date'))
            ->with('workSchedule.days.time') // Eager load relasi
            ->first();

        // Jika tidak ada assignment sama sekali, ini error
        if (!$userSchedule) {
            return response()->json([
                'schedule_exists' => false,
                'is_day_off' => false,
                'attendance' => null,
                'daily_schedule' => null,
            ]);
        }

        // Ada assignment, sekarang cek apakah hari ini adalah hari kerja atau libur terjadwal
        $workScheduleDay = $userSchedule->workSchedule->days->firstWhere('day_of_week', $date->dayOfWeekIso);
        
        $isDayOff = !$workScheduleDay || !$workScheduleDay->time;

        // Cari data presensi yang sudah ada
        $attendance = Attendance::where('user_id', $user->id)
            ->with('workSchedule')
            ->where('date', $date->toDateString())
            ->first();

        return response()->json([
            'schedule_exists' => true,
            'is_day_off' => $isDayOff,
            'attendance' => $attendance,
            'daily_schedule' => $workScheduleDay?->time,
        ]);
    }

    /**
     * Menyimpan atau memperbarui data presensi.
     * Menggunakan updateOrCreate untuk menangani logika create/update secara otomatis.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:Present,Late,Absent,Sick,Permit,Leave,Holiday',
            'notes' => 'nullable|string|max:255',
            'user_id' => [
                'required',
                'exists:sys_users,id',
                function ($attribute, $value, $fail) use ($request) {
                    if (!$request->filled('date')) {
                        return;
                    }
                    $user = User::find($value);
                    $date = $request->input('date');
                    $userSchedule = $user->userSchedules()
                        ->where('start_date', '<=', $date)
                        ->where(fn($q) => $q->where('end_date', '>=', $date)->orWhereNull('end_date'))
                        ->first();
                    if (!$userSchedule) {
                        $fail('No active schedule found for this user on the selected date.');
                    }
                },
            ],
        ]);

        $user = User::find($validated['user_id']);
        $date = Carbon::parse($validated['date']);
        
        $userSchedule = $user->userSchedules()->where('start_date', '<=', $date)
            ->where(fn ($q) => $q->where('end_date', '>=', $date)->orWhereNull('end_date'))
            ->with('workSchedule.days.time')->first();

        $workScheduleDay = $userSchedule->workSchedule->days->firstWhere('day_of_week', $date->dayOfWeekIso);
        $workTime = $workScheduleDay?->time;

        $lateMinutes = 0;
        if ($validated['clock_in'] && $workTime) {
            $scheduledStartTime = $date->copy()->setTimeFromTimeString($workTime->start_time);
            $clockInTime = $date->copy()->setTimeFromTimeString($validated['clock_in']);

            if ($clockInTime->isAfter($scheduledStartTime)) {
                $lateMinutes = (int) $scheduledStartTime->diffInMinutes($clockInTime);
            }
        }
        
        $clockInTimestamp = $validated['clock_in'] ? $date->copy()->setTimeFromTimeString($validated['clock_in']) : null;
        $clockOutTimestamp = $validated['clock_out'] ? $date->copy()->setTimeFromTimeString($validated['clock_out']) : null;

        Attendance::updateOrCreate(
            [
                'user_id' => $validated['user_id'],
                'date' => $validated['date'],
            ],
            [
                'work_schedule_id' => $userSchedule->work_schedule_id,
                'clock_in' => $clockInTimestamp,
                'clock_out' => $clockOutTimestamp,
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                'late_minutes' => $lateMinutes, // <-- Menyimpan hasil kalkulasi
            ]
        );

        return redirect()->route('attendance.correction.index')->with('success', 'Attendance data has been saved successfully.');
    }
}
