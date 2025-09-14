<?php

namespace App\Services\TapCard;

use App\Models\User;
use Carbon\Carbon;

/**
 * WorkSessionService
 * Kelas ini mengambil semua logika yang berhubungan dengan pencarian dan pembentukan sesi/jadwal kerja.
 */
class WorkSessionService
{
    /**
     * Mencari sesi kerja yang aktif untuk user pada waktu tertentu,
     * mempertimbangkan shift malam (kemarin).
     */
    public function findActiveSession(User $user, Carbon $currentTime): ?array
    {
        // Cek sesi hari ini dan kemarin
        $yesterdaySession = $this->buildSessionForDate($user, $currentTime->copy()->subDay());
        $todaySession = $this->buildSessionForDate($user, $currentTime);

        // Prioritaskan sesi kemarin jika waktu saat ini masih dalam jangkauan checkoutnya
        if ($yesterdaySession && $currentTime->between($yesterdaySession['start'], $yesterdaySession['window_end'])) {
            return $yesterdaySession;
        }

        // Jika tidak, gunakan sesi hari ini jika valid
        if ($todaySession) {
            return $todaySession;
        }

        return null;
    }

    /**
     * Membangun detail sesi kerja untuk tanggal tertentu.
     */
    private function buildSessionForDate(User $user, Carbon $date): ?array
    {
        // Temukan jadwal user yang berlaku pada tanggal ini
        $userSchedule = $user->userSchedules
            ->where('start_date', '<=', $date->toDateString())
            ->first(fn($us) => is_null($us->end_date) || $date->toDateString() <= $us->end_date);

        if (!$userSchedule) return null;

        $dayOfWeek = $date->dayOfWeekIso; // 1 for Monday, 7 for Sunday
        $workScheduleDay = $userSchedule->workSchedule->days->firstWhere('day_of_week', $dayOfWeek);

        if (!$workScheduleDay || !$workScheduleDay->time) return null;

        $workTime = $workScheduleDay->time;
        list($startHour, $startMinute) = explode(':', $workTime->start_time);
        list($endHour, $endMinute) = explode(':', $workTime->end_time);

        $scheduledStartTime = $date->copy()->setTime((int)$startHour, (int)$startMinute);
        $scheduledEndTime = $date->copy()->setTime((int)$endHour, (int)$endMinute);

        // Handle overnight shifts
        if ($scheduledEndTime->lt($scheduledStartTime)) {
            $scheduledEndTime->addDay();
        }

        return [
            'start' => $scheduledStartTime,
            'end' => $scheduledEndTime,
            'window_start' => $scheduledStartTime->copy()->subHours(2), // Jendela waktu presensi masuk
            'window_end' => $scheduledEndTime->copy()->addHours(2),   // Jendela waktu presensi pulang
        ];
    }
}