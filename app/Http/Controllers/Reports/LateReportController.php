<?php

namespace App\Http\Controllers\Reports;

use App\Exports\LateReportExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class LateReportController extends Controller
{
    public function index(Request $request)
    {
        // --- Query Utama untuk Tabel ---
        $query = Attendance::query()
            ->with(['user:id,name,avatar,gender', 'workSchedule' => function ($query) {
                $query->with(['days.time']);
            }])
            ->where('status', 'Late') // Hanya ambil data yang terlambat
            ->latest('date');

        // Filter berdasarkan pencarian nama user
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('search') . '%');
            });
        }

        // Filter berdasarkan rentang tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->input('start_date'), $request->input('end_date')]);
        }

        $data = $query->paginate(15)->withQueryString();

        // --- Query untuk Statistik Ringkasan ---
        $statsQuery = Attendance::query()->where('status', 'Late');
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $statsQuery->whereBetween('date', [$request->input('start_date'), $request->input('end_date')]);
        }

        $mostFrequentLateUser = $statsQuery->clone()
            ->select('user_id', DB::raw('count(*) as late_count'))
            ->groupBy('user_id')
            ->orderByDesc('late_count')
            ->with('user:id,name,avatar,gender')
            ->first();

        $summaryStatistics = [
            'total_late_records' => $statsQuery->clone()->count(),
            'average_late_time' => round($statsQuery->clone()->avg('late_minutes')),
            'most_frequent_late_user' => $mostFrequentLateUser,
        ];

        return Inertia::render('reports/late/Index', [
            'data' => $data,
            'summaryStatistics' => $summaryStatistics,
            'filters' => $request->only(['search', 'start_date', 'end_date']),
        ]);
    }

    /**
     * Memulai proses ekspor dengan menyimpan file ke server.
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $fileName = "late-report-" . uniqid() . ".xlsx";
            $disk = 'public';

            Excel::store(new LateReportExport(
                $validated['search'] ?? null,
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null
            ), $fileName, $disk);

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
        
        return response()->download($path, 'Late Attendance Report.xlsx')->deleteFileAfterSend(true);
    }
}
