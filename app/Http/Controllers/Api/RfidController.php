<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RfidResource;
use App\Models\Device;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\RawAttendance;
use App\Models\RfidScan;
use App\Models\User;
use App\Models\UserRfid;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RfidController extends Controller
{
    public function check(Request $request)
    {
        try {
            $deviceUid = $request->device_uid;
            $cardUid = $request->uid;
            $currentTime = now();

            // 1. Selalu catat setiap tap ke log audit murni
            $rfidScan = RfidScan::create([
                "device_uid" => $deviceUid,
                "card_uid" => $cardUid,
            ]);

            // 2. Validasi Perangkat & Kartu
            $device = Device::where('device_uid', $deviceUid)->first();
            if (!$device) {
                return new RfidResource(false, "Alat ini belum terdaftar.", "ALAT TIDAK TERDAFTAR", null);
            }

            $userRfid = UserRfid::where('uid', $cardUid)->with('user.userSchedules.workSchedule.days.time')->first();
            if (!$userRfid || !$userRfid->user) {
                return new RfidResource(false, "Daftarkan kartu ini melalui website.\nUID: {$cardUid}", "KARTU TIDAK TERDAFTAR", null);
            }

            $user = $userRfid->user;
            $userName = $user->name;
            $rfidScan->update(['user_id' => $user->id]);

            // 3. Temukan sesi kerja yang relevan (bisa dari kemarin atau hari ini)
            $activeSession = $this->getActiveWorkSessionForTime($user, $currentTime);

            if (!$activeSession) {
                // Tidak ada session aktif → cek libur nasional langsung berdasarkan currentTime
                $holiday = Holiday::whereDate('date', $currentTime->toDateString())->first();
                if ($holiday) {
                    return new RfidResource(false, "Hari ini libur nasional: {$holiday->description}.", "HARI LIBUR NASIONAL", null);
                }

                // Cek apakah user punya assignment jadwal sama sekali atau tidak
                if ($user->userSchedules->isEmpty()) {
                    return new RfidResource(false, "Anda belum memiliki assignment jadwal kerja.", "TIDAK ADA JADWAL", null);
                } else {
                    return new RfidResource(false, "Hari ini adalah jadwal libur Anda.", "HARI LIBUR", null);
                }
            }

            // 3.1 Cek libur nasional berdasarkan tanggal start session (bukan currentTime)
            $holiday = Holiday::whereDate('date', $activeSession['start']->toDateString())->first();
            if ($holiday) {
                return new RfidResource(false, "Tanggal {$activeSession['start']->toDateString()} adalah libur nasional: {$holiday->description}.", "HARI LIBUR NASIONAL", null);
            }

            // 4. Cari data presensi mentah yang mungkin sudah ada untuk sesi ini
            $rawAttendance = RawAttendance::where('user_id', $user->id)
                ->where('date', $activeSession['start']->toDateString())
                ->first();

            // 5. Tentukan niat (masuk atau pulang)
            if (!$rawAttendance) {
                if ($currentTime->lt($activeSession['window_start'])) {
                    return new RfidResource(false, "Jadwal masuk Anda pukul {$activeSession['start']->format('H:i')}.\nAnda bisa scan 2 jam sebelumnya.", "BELUM WAKTUNYA MASUK", null);
                }
                // Ini adalah upaya presensi MASUK
                RawAttendance::create([
                    'user_id' => $user->id,
                    'date' => $activeSession['start']->toDateString(),
                    'clock_in' => $currentTime,
                ]);

                $message = "Selamat Datang\n{$userName}\nAnda masuk pukul {$currentTime->format('H:i')} WIB.";
                $title = "PRESENSI MASUK";
                return new RfidResource(true, $message, $title, $userRfid);
            } else {
                // Ini adalah upaya presensi PULANG
                if ($rawAttendance->clock_out) {
                    return new RfidResource(false, "Anda sudah melakukan presensi pulang pada pukul " . $rawAttendance->clock_out->format('H:i') . ".", "SUDAH PULANG", null);
                }

                if ($currentTime->lt($activeSession['end'])) {
                    return new RfidResource(false, "Jadwal pulang Anda adalah pukul {$activeSession['end']->format('H:i')}.", "BELUM WAKTUNYA PULANG", null);
                }

                $rawAttendance->update(['clock_out' => $currentTime]);

                $message = "Terima Kasih\n{$userName}\nAnda pulang pukul {$currentTime->format('H:i')} WIB.";
                $title = "PRESENSI PULANG";
                return new RfidResource(true, $message, $title, $userRfid);
            }
        } catch (\Throwable $e) {
            Log::error('Tap card failed', ['error' => $e->getMessage()]);
            return new RfidResource(false, "Terjadi kesalahan pada server.", "SERVER ERROR", null);
        }
    }

    /**
     * Fungsi helper untuk mencari dan membangun detail sesi kerja yang relevan.
     */
    private function getActiveWorkSessionForTime(User $user, Carbon $currentTime): ?array
    {
        // Cek sesi hari ini dan kemarin
        $yesterdaySession = $this->buildSessionForDate($user, $currentTime->copy()->subDay());
        $todaySession = $this->buildSessionForDate($user, $currentTime);
        Log::info("Jadwal", [
            "y" => $yesterdaySession,
            "t" => $todaySession
        ]);
        $activeSession = null;

        // Prioritaskan sesi kemarin jika waktu saat ini masih dalam jangkauan checkoutnya
        if ($yesterdaySession && $currentTime->between($yesterdaySession['start'], $yesterdaySession['window_end'])) {
            $activeSession = $yesterdaySession;
        }
        // Jika tidak, gunakan sesi hari ini
        elseif ($todaySession) {
            $activeSession = $todaySession;
        }

        return $activeSession;
    }

    /**
     * Fungsi helper untuk membangun detail sesi kerja untuk tanggal tertentu.
     */
    private function buildSessionForDate(User $user, Carbon $date): ?array
    {
        $userSchedule = $user->userSchedules
            ->where('start_date', '<=', $date)
            ->first(fn($us) => is_null($us->end_date) || $date->lte($us->end_date));

        if (!$userSchedule) return null;

        $dayOfWeek = $date->dayOfWeekIso;
        $workScheduleDay = $userSchedule->workSchedule->days->firstWhere('day_of_week', $dayOfWeek);

        if (!$workScheduleDay || !$workScheduleDay->time) return null;

        $workTime = $workScheduleDay->time;
        list($startHour, $startMinute) = explode(':', $workTime->start_time);
        list($endHour, $endMinute) = explode(':', $workTime->end_time);

        $scheduledStartTime = $date->copy()->setTime((int)$startHour, (int)$startMinute);
        $scheduledEndTime = $date->copy()->setTime((int)$endHour, (int)$endMinute);

        if ($scheduledEndTime->lt($scheduledStartTime)) {
            $scheduledEndTime->addDay();
        }

        return [
            'start' => $scheduledStartTime,
            'end' => $scheduledEndTime,
            'window_start' => $scheduledStartTime->copy()->subHours(2),
            'window_end' => $scheduledEndTime->copy()->addHours(2),
        ];
    }
}
