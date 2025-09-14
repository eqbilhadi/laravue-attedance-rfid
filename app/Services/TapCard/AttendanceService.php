<?php

namespace App\Services\TapCard;

use App\Models\Holiday;
use App\Models\RawAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * AttendanceService
 * Kelas ini menangani logika bisnis inti: pengecekan libur, cuti, dan proses clock-in atau clock-out.
 */
class AttendanceService
{
    /**
     * Memproses tap presensi, menentukan apakah itu masuk atau pulang.
     */
    public function processAttendance(User $user, Carbon $currentTime, ?array $activeSession): array
    {
        if (!$activeSession) {
            $this->handleNoActiveSession($user, $currentTime);
        }

        $this->checkForHolidayOrLeave($user, $activeSession['start']);


        $rawAttendance = RawAttendance::where('user_id', $user->id)
            ->where('date', $activeSession['start']->toDateString())
            ->first();

        if (!$rawAttendance) {
            return $this->handleClockIn($user, $currentTime, $activeSession);
        } else {
            return $this->handleClockOut($rawAttendance, $currentTime, $activeSession);
        }
    }

    /**
     * Menangani logika saat tidak ada sesi kerja aktif yang ditemukan.
     * @throws ValidationException
     */
    private function handleNoActiveSession(User $user, Carbon $currentTime): void
    {
        // Cek libur nasional langsung berdasarkan currentTime
        $holiday = Holiday::whereDate('date', $currentTime->toDateString())->first();
        if ($holiday) {
            throw ValidationException::withMessages([
                'title' => 'HARI LIBUR NASIONAL',
                'message' => "Hari ini libur nasional: {$holiday->description}."
            ]);
        }

        // Cek cuti/izin
        $this->checkForHolidayOrLeave($user, $currentTime);

        // Cek jadwal
        if ($user->userSchedules->isEmpty()) {
            throw ValidationException::withMessages([
                'title' => 'TIDAK ADA JADWAL',
                'message' => 'Anda belum memiliki assignment jadwal kerja.'
            ]);
        }

        throw ValidationException::withMessages([
            'title' => 'HARI LIBUR',
            'message' => 'Hari ini adalah jadwal libur Anda.'
        ]);
    }

    /**
     * Menangani logika presensi masuk.
     */
    private function handleClockIn(User $user, Carbon $currentTime, array $activeSession): array
    {
        if ($currentTime->lt($activeSession['window_start'])) {
            throw ValidationException::withMessages([
                'title' => 'BELUM WAKTUNYA MASUK',
                'message' => "Jadwal masuk Anda pukul {$activeSession['start']->format('H:i')}.\nAnda bisa scan 2 jam sebelumnya."
            ]);
        }

        if ($currentTime->gte($activeSession['end'])) {
            throw ValidationException::withMessages([
                'title' => 'SESI KERJA BERAKHIR',
                'message' => "Sesi kerja Anda berakhir pada pukul {$activeSession['end']->format('H:i')}.\nAnda absen."
            ]);
        }

        RawAttendance::create([
            'user_id' => $user->id,
            'date' => $activeSession['start']->toDateString(),
            'clock_in' => $currentTime,
        ]);

        return [
            'success' => true,
            'title' => 'PRESENSI MASUK',
            'message' => "Selamat Datang\n{$user->name}\nAnda masuk pukul {$currentTime->format('H:i')} WIB.",
        ];
    }

    /**
     * Menangani logika presensi pulang.
     */
    private function handleClockOut(RawAttendance $rawAttendance, Carbon $currentTime, array $activeSession): array
    {
        if ($rawAttendance->clock_out) {
            throw ValidationException::withMessages([
                'title' => 'SUDAH PULANG',
                'message' => "Anda sudah melakukan presensi pulang pada pukul " . $rawAttendance->clock_out->format('H:i') . "."
            ]);
        }

        if ($currentTime->lt($activeSession['end'])) {
             throw ValidationException::withMessages([
                'title' => 'BELUM WAKTUNYA PULANG',
                'message' => "Jadwal pulang Anda adalah pukul {$activeSession['end']->format('H:i')}."
            ]);
        }

        $rawAttendance->update(['clock_out' => $currentTime]);

        return [
            'success' => true,
            'title' => 'PRESENSI PULANG',
            'message' => "Terima Kasih\n{$rawAttendance->user->name}\nAnda pulang pukul {$currentTime->format('H:i')} WIB.",
        ];
    }

    /**
     * Pengecekan terpusat untuk hari libur atau cuti.
     * @throws ValidationException
     */
    private function checkForHolidayOrLeave(User $user, Carbon $date): void
    {
        $holiday = Holiday::whereDate('date', $date->toDateString())->first();
        if ($holiday) {
            throw ValidationException::withMessages([
                'title' => 'HARI LIBUR NASIONAL',
                'message' => "Tanggal {$date->toDateString()} adalah libur nasional: {$holiday->description}."
            ]);
        }

        $approvedLeave = $user->leaveRequests()
            ->with('leaveType')
            ->where('status', 'Approved')
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->first();

        if ($approvedLeave) {
            throw ValidationException::withMessages([
                'title' => 'CUTI / IZIN',
                'message' => "Hari ini anda cuti atau izin: {$approvedLeave->leaveType->name}."
            ]);
        }
    }
}