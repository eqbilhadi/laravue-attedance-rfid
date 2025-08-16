<?php

namespace App\Http\Controllers\RfidManagement;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\RfidScan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogScanController extends Controller
{
    public function index(Request $request)
    {
        $query = RfidScan::query()
            ->with(['device:device_uid,device_name', 'user:sys_users.id,name'])
            ->when(
                $request->filled('device_uid'),
                fn($query) => $query->where('device_uid', $request->input('device_uid'))
            )
            ->when(
                $request->filled('start_date') && $request->filled('end_date'),
                function ($query) use ($request) {
                    $startDate = $request->input('start_date') . ' 00:00:00';
                    $endDate = $request->input('end_date') . ' 23:59:59';
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            )
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = strtolower($request->input('search'));

                    $query->where(function ($q) use ($search) {
                        $q->whereHas('user', fn($userQuery) => $userQuery->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
                            ->orWhereRaw('LOWER(card_uid) LIKE ?', ["{$search}%"]);
                    });
                }
            )
            ->latest();

        $data = $query->paginate(10)->withQueryString();

        $devices = Device::select('device_uid', 'device_name')
            ->pluck('device_name', 'device_uid') 
            ->map(fn($deviceName, $id) => [
                'label' => $deviceName,
                'value' => $id,
            ])
            ->values();

        return Inertia::render('rfid-management/log-scan/Index', [
            'data' => $data,
            'filters' => $request->only(['device_uid', 'start_date', 'end_date', 'search']),
            'devices' => $devices
        ]);
    }
}
