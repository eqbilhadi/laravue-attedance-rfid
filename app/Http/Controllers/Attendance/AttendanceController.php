<?php

namespace App\Http\Controllers\Attendance;

use App\Enum\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::query()
            ->with([
                'user:id,name,email,avatar,gender', 
                'workSchedule' => function ($query) {
                    $query->with(['days.time']);
                }
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('user', function ($subq) use ($request) {
                    $search = strtolower($request->input('search'));
                    $subq->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
                $q->whereBetween('date', [$request->input('start_date'), $request->input('end_date')]);
            });

        $data = $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('attendance/data/Index', [
            'data' => $data,
            'filters' => $request->only(['search', 'status', 'start_date', 'end_date']),
            'users' => User::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            // Mengirim daftar status dari Enum ke frontend
            'statuses' => collect(AttendanceStatus::cases())->map(fn ($status) => [
                'label' => $status->value,
                'value' => $status->value,
            ]),
        ]);
    }

    /**
     * Menjalankan command untuk memproses data presensi untuk tanggal tertentu.
     */
    public function processAttendance(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        try {
            $command = 'attendance:process';
            $parameters = [];

            // Jika ada tanggal yang dikirim dari form, gunakan sebagai flag --date
            if ($request->filled('date')) {
                $parameters['--date'] = $request->input('date');
                $description = 'Attendance data for ' . $request->input('date') . ' is being processed.';
            } else {
                // Jika tidak, command akan otomatis menggunakan hari kemarin
                $description = 'Attendance data for the previous day is being processed.';
            }

            Artisan::call($command, $parameters);

            return redirect()->route('attendance.data.index')->with('success', [
                'title' => 'Processing Started!',
                'description' => $description
            ]);

        } catch (Throwable $e) {
            Log::error('Manual attendance processing failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', [
                'title' => 'Processing Failed!',
                'description' => 'An error occurred while trying to process attendance data.'
            ]);
        }
    }
}
