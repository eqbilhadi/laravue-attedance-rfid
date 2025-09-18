<?php

namespace App\Http\Controllers\Reports;

use App\Exports\TimesheetExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class TimesheetController extends Controller
{
    public function index(Request $request)
    {
        // Tetapkan filter default jika tidak ada input
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $search = $request->input('search');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // 1. Buat daftar hari dalam sebulan untuk header tabel di frontend
        $period = CarbonPeriod::create($startDate, $endDate);
        $daysInMonth = collect($period)->map(fn($date) => [
            'day' => $date->day,
            'date_string' => $date->toDateString(),
            'is_weekend' => $date->isWeekend(),
        ]);

        // 2. Query utama untuk mengambil pengguna yang relevan (dengan paginasi)
        $usersQuery = User::query()
            ->where('is_active', true)
            // Optimasi: Hanya ambil user yang punya jadwal di bulan ini, tidak harus punya data presensi
            ->whereHas('userSchedules', function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                    ->where(fn($sub) => $sub->where('end_date', '>=', $startDate)->orWhereNull('end_date'));
            })
            ->when($search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            });

        $paginatedUsers = $usersQuery->paginate(20)->withQueryString();

        // 3. Ambil semua data presensi untuk pengguna di halaman ini dalam satu query
        $userIdsOnPage = $paginatedUsers->pluck('id');

        $attendances = Attendance::whereIn('user_id', $userIdsOnPage)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // 4. Tambahkan accessor untuk mempermudah akses di frontend
        $attendances->each(function ($attendance) {
            $attendance->append(['clock_in_time', 'clock_out_time', 'date_string']);
        });

        // 5. "Pivot" atau ubah data presensi menjadi format matriks
        // Hasilnya akan seperti: [ 'user_id' => [ '2025-08-01' => AttendanceObject, ... ] ]
        $attendanceMatrix = $attendances->groupBy('user_id')->map(function ($userAttendances) {
            return $userAttendances->keyBy('date_string');
        });

        return Inertia::render('reports/timesheet/Index', [
            'users' => $paginatedUsers,
            'daysInMonth' => $daysInMonth,
            'attendanceMatrix' => $attendanceMatrix,
            'filters' => $request->only(['year', 'month', 'search']),
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
            'search' => 'nullable|string',
        ]);

        try {
            $fileName = "timesheet-report-{$validated['year']}-{$validated['month']}-" . uniqid() . ".xlsx";
            $disk = 'public';

            Excel::store(new TimesheetExport(
                $validated['year'],
                $validated['month'],
                $validated['search'] ?? null
            ), $fileName, $disk);

            return redirect()->back()->with('data', [
                'filename' => $fileName,
            ]);
        } catch (Throwable $e) {
            Log::error('Timesheet export failed', ['error' => $e->getMessage(), 'tracing' => $e->getTrace()]);
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

        return response()->download($path, 'Timesheet Report.xlsx')->deleteFileAfterSend(true);
    }
}
