<?php

namespace App\Http\Controllers;

use App\Models\RfidScan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // === Kategori 1: Ringkasan & Monitoring Real-time ===

        // --- Statistik Kehadiran Hari Ini ---
        $totalParticipants = User::count();
        $scansToday = RfidScan::whereDate('sys_rfid_scans.created_at', today())
            ->join('sys_user_rfids', 'sys_user_rfids.uid', '=', 'sys_rfid_scans.card_uid')->get();

        $presentCount = $scansToday->unique('user_id')->whereNotNull('user_id')->count();
        // Asumsi terlambat adalah scan setelah jam 07:00
        $lateCount = $scansToday->unique('user_id')->whereNotNull('user_id')->filter(function ($scan) {
            return Carbon::parse($scan->created_at)->format('H:i:s') > '15:50:00';
        })->count();

        $attendanceToday = [
            'total' => $totalParticipants,
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $totalParticipants - $presentCount,
        ];

        // --- Aktivitas Scan Terakhir ---
        $lastScans = RfidScan::with(['user:sys_users.id,name', 'device:device_uid,device_name'])
            ->whereDate('created_at', today())
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($scan) {
                return [
                    'name' => $scan->user->name ?? 'Unregistered Card',
                    'class' => $scan->device->device_name ?? 'Unknown Device', // Ganti dengan data kelas jika ada
                    'time' => Carbon::parse($scan->created_at)->format('H:i:s'),
                    'status' => Carbon::parse($scan->created_at)->format('H:i:s') > '15:50:00' ? 'Late' : 'On Time',
                ];
            });

        // === Kategori 2: Analisis & Tren Kehadiran ===

        // --- Tren Kehadiran Mingguan ---
        $weeklyTrend = RfidScan::whereBetween('sys_rfid_scans.created_at', [today()->subDays(6), today()->endOfDay()])
            ->select(
                DB::raw('DATE(sys_rfid_scans.created_at) as date'),
                DB::raw('COUNT(DISTINCT sys_user_rfids.user_id) as present_count'),
                DB::raw("COUNT(DISTINCT CASE WHEN TO_CHAR(sys_rfid_scans.created_at, 'HH24:MI:SS') > '15:50:00' THEN sys_user_rfids.user_id END) as late_count")
            )
            ->join('sys_user_rfids', 'sys_user_rfids.uid', '=', 'sys_rfid_scans.card_uid')
            ->whereNotNull('sys_user_rfids.user_id')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();


        $weeklyDates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $weeklyDates->push([
                'day' => $date->format('D'),
                'full_date' => $date->format('Y-m-d'),
            ]);
        }

        $weeklyTrendSeries = [
            'present' => $weeklyDates->map(fn ($d) => $weeklyTrend->firstWhere('date', $d['full_date'])?->present_count ?? 0)->values(),
            'late' => $weeklyDates->map(fn ($d) => $weeklyTrend->firstWhere('date', $d['full_date'])?->late_count ?? 0)->values(),
            'absent' => $weeklyDates->map(fn ($d) => $totalParticipants - ($weeklyTrend->firstWhere('date', $d['full_date'])?->present_count ?? 0))->values(),
            'categories' => $weeklyDates->pluck('day'),
        ];


        // === Kategori 3: Kesehatan Sistem ===

        // --- Aktivitas Scan Perangkat Hari Ini ---
        $deviceActivity = RfidScan::with('device:device_uid,device_name')
            ->whereDate('sys_rfid_scans.created_at', today())
            ->leftJoin('sys_user_rfids', 'sys_user_rfids.uid', '=', 'sys_rfid_scans.card_uid')
            ->select('device_uid',
                DB::raw('count(CASE WHEN sys_user_rfids.user_id IS NOT NULL THEN 1 END) as registered_scans'),
                DB::raw('count(CASE WHEN sys_user_rfids.user_id IS NULL THEN 1 END) as unregistered_scans')
            )
            ->groupBy('device_uid')
            ->get();
        
        // Ambil nama device untuk label chart
        $deviceNames = $deviceActivity->map(fn($item) => $item->device->device_name ?? $item->device_uid);

        $deviceActivitySeries = [
            [
                'name' => 'Registered',
                'data' => $deviceActivity->pluck('registered_scans'),
            ],
            [
                'name' => 'Unregistered',
                'data' => $deviceActivity->pluck('unregistered_scans'),
            ]
        ];


        return Inertia::render('Dashboard', [
            'attendanceToday' => $attendanceToday,
            'lastScans' => $lastScans,
            'weeklyTrend' => $weeklyTrendSeries,
            'deviceActivity' => [
                'series' => $deviceActivitySeries,
                'categories' => $deviceNames,
            ],
        ]);
    }
}
