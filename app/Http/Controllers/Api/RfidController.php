<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RfidResource;
use App\Models\UserRfid;
use Illuminate\Http\Request;

class RfidController extends Controller
{
    public function check(Request $request)
    {
        $user = UserRfid::where('uid', $request->uid)->with('user')->first();

        if ($user) {
            return new RfidResource(true, 'Registered RFID Card', $user);
        }

        return new RfidResource(false, "Kartu dengan UID\n" . $request->uid . "\nbelum terdaftar", $user);
    }
}
