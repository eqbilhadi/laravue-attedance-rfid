<?php

namespace App\Services\TapCard;

use App\Models\Device;
use App\Models\UserRfid;
use Illuminate\Validation\ValidationException;

/**
 * RfidValidationService
 * Kelas ini bertanggung jawab hanya untuk validasi perangkat (device) dan kartu (card).
 */
class RfidValidationService
{
    /**
     * Memvalidasi apakah device terdaftar.
     * @throws ValidationException
     */
    public function validateDevice(string $deviceUid): Device
    {
        $device = Device::where('device_uid', $deviceUid)->first();
        if (!$device) {
            throw ValidationException::withMessages([
                'title' => 'ALAT TIDAK TERDAFTAR',
                'message' => 'Alat ini belum terdaftar.',
            ]);
        }
        return $device;
    }

    /**
     * Memvalidasi apakah kartu RFID terdaftar dan memiliki user.
     * @throws ValidationException
     */
    public function validateCard(string $cardUid): UserRfid
    {
        $userRfid = UserRfid::where('uid', $cardUid)->with('user')->first();
        if (!$userRfid || !$userRfid->user) {
            throw ValidationException::withMessages([
                'title' => 'KARTU TIDAK TERDAFTAR',
                'message' => "Daftarkan kartu ini melalui website.\nUID: {$cardUid}",
            ]);
        }
        return $userRfid;
    }
}