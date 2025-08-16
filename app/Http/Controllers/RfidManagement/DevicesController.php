<?php

namespace App\Http\Controllers\RfidManagement;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class DevicesController extends Controller
{
    public function index(Request $request)
    {
        $query = Device::query()
            ->when($request->filled('is_active'), fn($query) => $query->where('is_active', $request->input('is_active')));

        $data = $query->paginate(10)->withQueryString();

        return Inertia::render('rfid-management/devices/Index', [
            'data' => $data,
            'filters' => $request->only(['is_active']),
        ]);
    }

    public function checkExists(Request $request)
    {
        $uids = $request->input('uids', []);
        $existsDevices = Device::whereIn('device_uid', $uids)->pluck('device_uid')->toArray();

        // Return daftar device dengan status registered/unregistered
        $result = [];
        foreach ($uids as $uid) {
            $result[] = [
                'uid' => $uid,
                'registered' => in_array($uid, $existsDevices),
            ];
        }

        return response()->json($result);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'devices' => 'required|array',
            'devices.*.device_uid' => 'required|string|unique:sys_devices,device_uid',
            'devices.*.device_name' => 'required|string',
            'devices.*.location' => 'nullable|string',
            'devices.*.ip_address' => 'nullable|string',
        ], [], [
            'devices.*.device_uid' => 'device uid',
            'devices.*.device_name' => 'device name',
            'devices.*.location' => 'device location',
            'devices.*.ip_address' => 'ip address',
        ]);

        $devices = $data['devices'];

        foreach ($devices as $deviceData) {
            Device::create([
                'device_name' => $deviceData['device_name'],
                'device_uid' => $deviceData['device_uid'],
                'location' => $deviceData['location'],
                'ip_address' => $deviceData['ip_address'],
            ]);
        }

        return redirect()->route('rfid-management.devices.index')->with('success', 'New device saved successfully.');
    }

    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'device_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        try {
            $device->update($validated);

            return redirect()->route('rfid-management.devices.index')->with('success', 'Device updated successfully.');
        } catch (Throwable $e) {
            Log::error('Device update failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('error', 'Failed to update device. Please try again.');
        }
    }

    public function destroy(Device $device)
    {
        try {
            $device->delete();

            return redirect()->route('rfid-management.devices.index')->with('success', 'Device deleted successfully.');
        } catch (Throwable $e) {
            Log::error('Device delete failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('error', 'Failed to delete device. Please try again.');
        }
    }
}
