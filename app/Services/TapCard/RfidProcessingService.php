<?php

namespace App\Services\TapCard;

use App\Models\RfidScan;
use App\Services\TapCard\AttendanceService;
use App\Services\TapCard\RfidValidationService;
use App\Services\TapCard\WorkSessionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * RfidProcessingService
 * Kelas ini bertindak sebagai koordinator utama. Ia tidak memiliki banyak logika, tugasnya adalah memanggil service-service lain dalam urutan yang benar.
 */
class RfidProcessingService
{
    protected $validator;
    protected $workSessionService;
    protected $attendanceService;

    public function __construct(
        RfidValidationService $validator,
        WorkSessionService $workSessionService,
        AttendanceService $attendanceService
    ) {
        $this->validator = $validator;
        $this->workSessionService = $workSessionService;
        $this->attendanceService = $attendanceService;
    }

    public function processTap(Request $request): array
    {
        $deviceUid = $request->device_uid;
        $cardUid = $request->uid;
        // Gunakan waktu sekarang atau waktu yang di-hardcode untuk testing
        $currentTime = now();

        // 1. Validasi Perangkat & Kartu
        $this->validator->validateDevice($deviceUid);
        
        // 2. Selalu catat setiap tap
        $rfidScan = RfidScan::create([
            "device_uid" => $deviceUid,
            "card_uid" => $cardUid,
        ]);
        
        $userRfid = $this->validator->validateCard($cardUid);
        $user = $userRfid->user;

        // Update log dengan user_id jika validasi berhasil
        $rfidScan->update(['user_id' => $user->id]);

        // 3. Temukan sesi kerja yang relevan
        $activeSession = $this->workSessionService->findActiveSession($user, $currentTime);

        // 4. Proses presensi (masuk/pulang) dan dapatkan hasilnya
        $result = $this->attendanceService->processAttendance($user, $currentTime, $activeSession);

        // 5. Gabungkan hasil dengan data userRfid untuk response
        return array_merge($result, ['data' => $userRfid]);
    }
}