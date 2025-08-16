<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RfidResource;
use App\Models\Device;
use App\Models\RfidScan;
use App\Models\UserRfid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RfidController extends Controller
{
    public function check(Request $request)
{
    try {
        $deviceUid = $request->device_uid;

        $device = Device::where('device_uid', $deviceUid)->first();

        if (!$device) {
            return new RfidResource(
                false,
                "Alat ini belum terdaftar, silakan daftarkan di website sebelum digunakan.",
                "ALAT TIDAK TERDAFTAR",
                null,
            );
        }

        RfidScan::create([
            "device_uid" => $deviceUid,
            "card_uid" => $request->uid,
        ]);

        $user = UserRfid::where('uid', $request->uid)
            ->with('user')
            ->first();

        if ($user) {
            return new RfidResource(
                true,
                "Halo\n{$user->user->name}\npresensi berhasil pukul " . now()->format('H:i') . " WIB.",
                "PRESENSI BERHASIL",
                $user,
            );
        }

        return new RfidResource(
            false,
            "Daftarkan terlebih dahulu kartunya melalui website\nUID: {$request->uid}",
            "KARTU TIDAK TERDAFTAR",
            null,
        );
    } catch (\Throwable $e) {
        Log::error('Tap card failed', [
            'error' => $e->getMessage(),
        ]);

        return new RfidResource(
            false,
            "Terjadi kesalahan pada server.",
            "SERVER ERROR",
            null,
        );
    }
}

}
