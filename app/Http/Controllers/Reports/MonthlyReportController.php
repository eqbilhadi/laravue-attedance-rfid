<?php

namespace App\Http\Controllers\Reports;

use App\Exports\MonthlyReportExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\UserSchedule;
use App\Models\WorkScheduleDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class MonthlyReportController extends Controller
{
    public function index(Request $request)
    {
        // Tetapkan filter default jika tidak ada input
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $userId = $request->input('user_id');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // --- LANGKAH 1: Buat query untuk PENGGUNA dan terapkan paginasi ---
        $usersQuery = User::query()
            ->where('is_active', true)
            // Filter hanya user yang memiliki data presensi di bulan yang dipilih
            ->whereHas('attendances', function ($q) use ($year, $month) {
                $q->whereYear('date', $year)->whereMonth('date', $month);
            })
            ->when($userId, fn($q) => $q->where('id', $userId));

        $paginatedUsers = $usersQuery->paginate(15)->withQueryString();

        // --- LANGKAH 2: Ambil data relevan HANYA untuk pengguna di halaman saat ini ---
        $reportUserIds = $paginatedUsers->pluck('id');

        // Ambil semua data presensi untuk user di halaman ini dalam satu query
        $attendancesForPage = Attendance::query()
            ->whereIn('user_id', $reportUserIds)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('user_id');

        // Ambil semua jadwal user di halaman ini dalam satu query
        $userSchedulesForPage = UserSchedule::query()
            ->whereIn('user_id', $reportUserIds)
            ->where('start_date', '<=', $endDate)
            ->where(fn($q) => $q->where('end_date', '>=', $startDate)->orWhereNull('end_date'))
            ->with('workSchedule.days.time') // Eager load semua yang dibutuhkan
            ->get()
            ->keyBy('user_id'); // Gunakan keyBy untuk pencarian cepat

        // --- LANGKAH 3: Transformasi data paginasi untuk menambahkan rekapitulasi ---
        $paginatedUsers->through(function ($user) use ($startDate, $endDate, $attendancesForPage, $userSchedulesForPage) {
            $userAttendances = $attendancesForPage->get($user->id, collect());
            $userSchedule = $userSchedulesForPage->get($user->id);
            $totalWorkDays = 0;

            if ($userSchedule) {
                // Buat lookup map agar tidak perlu looping & filter berulang
                $dailyScheduleMap = $userSchedule->workSchedule->days->keyBy('day_of_week');
                for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                    $isWithinScheduleRange = $userSchedule->end_date
                        ? $date->between($userSchedule->start_date, $userSchedule->end_date)
                        : $date->gte($userSchedule->start_date);
                    if ($isWithinScheduleRange && $dailyScheduleMap->has($date->dayOfWeekIso) && $dailyScheduleMap->get($date->dayOfWeekIso)->time) {
                        $totalWorkDays++;
                    }
                }
            }

            $presentCount = $userAttendances->where('status', 'Present')->count();
            $lateCount = $userAttendances->where('status', 'Late')->count();

            // --- LOGIKA BARU: Hitung rasio kehadiran ---
            $totalPresence = $presentCount + $lateCount;
            $presenceRatio = ($totalWorkDays > 0) ? round(($totalPresence / $totalWorkDays) * 100, 1)  : 0;

            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_avatar_url' => $user->avatar_url,
                'total_work_days' => $totalWorkDays,
                'present_count' => $presentCount,
                'late_count' => $lateCount,
                'absent_count' => $userAttendances->where('status', 'Absent')->count(),
                'sick_count' => $userAttendances->where('status', 'Sick')->count(),
                'permit_count' => $userAttendances->where('status', 'Permit')->count(),
                'leave_count' => $userAttendances->where('status', 'Leave')->count(),
                'presence_ratio' => $presenceRatio,
            ];
        });

        return Inertia::render('reports/monthly/Index', [
            'reportData' => $paginatedUsers,
            'filters' => $request->only(['year', 'month', 'user_id']),
            'users' => User::select('id', 'name')->where('is_active', true)->get(),
        ]);
    }

    /**
     * Memulai proses ekspor dengan menyimpan file ke server.
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
            'user_id' => 'nullable|exists:sys_users,id',
        ]);

        try {
            $fileName = "monthly-report-{$validated['year']}-{$validated['month']}-" . uniqid() . ".xlsx";
            $disk = 'public';

            Excel::store(new MonthlyReportExport($validated['year'], $validated['month'], $validated['user_id']), $fileName, $disk);

            return redirect()->back()->with('data', [
                'filename' => $fileName,
            ]);
        } catch (Throwable $e) {
            Log::error('Export failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate the report. Please try again.');
        }
    }

    /**
     * Mengunduh file yang sudah dibuat, lalu menghapusnya.
     */
    public function downloadExport($filename)
    {
        $disk = 'public';
        $path = Storage::disk($disk)->path($filename);
        
        if (!Storage::disk($disk)->exists($filename)) {
            abort(404, 'File not found or has already been downloaded.');
        }

        return response()->download($path, 'Monthly Attendance Report.xlsx')->deleteFileAfterSend(true);
    }
}
