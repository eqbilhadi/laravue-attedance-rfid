<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceCorrectionController extends Controller
{
    /**
     * Menampilkan halaman utama untuk koreksi presensi.
     */
    public function index()
    {
        return Inertia::render('attendance/correction/Index', [
            'users' => User::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Mengambil data presensi yang ada berdasarkan user dan tanggal.
     * Ini adalah endpoint API-like yang dipanggil oleh Vue.
     */
    public function fetch(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:sys_users,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $attendance = Attendance::with('workSchedule:id,name')
            ->where('user_id', $request->input('user_id'))
            ->where('date', $request->input('date'))
            ->first();

        return response()->json($attendance);
    }

    /**
     * Menyimpan atau memperbarui data presensi.
     * Menggunakan updateOrCreate untuk menangani logika create/update secara otomatis.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:Present,Late,Absent,Sick,Permit,Leave,Holiday',
            'notes' => 'required|string|max:255',
            'user_id' => [
                'required',
                'exists:sys_users,id',
                // Validasi custom untuk memastikan user punya jadwal aktif
                function ($attribute, $value, $fail) use ($request) {
                    // Hanya jalankan jika tanggal juga ada
                    if (!$request->filled('date')) {
                        return;
                    }

                    $user = User::find($value);
                    $date = $request->input('date');

                    $userSchedule = $user->userSchedules()
                        ->where('start_date', '<=', $date)
                        ->where(fn($q) => $q->where('end_date', '>=', $date)->orWhereNull('end_date'))
                        ->first();

                    if (!$userSchedule) {
                        $fail('No active schedule found for this user on the selected date.');
                    }
                },
            ],
        ]);

        // Ambil jadwal kerja yang seharusnya berlaku pada hari itu
        $user = User::find($validated['user_id']);
        $userSchedule = $user->userSchedules()
            ->where('start_date', '<=', $validated['date'])
            ->where(fn($q) => $q->where('end_date', '>=', $validated['date'])->orWhereNull('end_date'))
            ->first();

        if (!$userSchedule) {
            return redirect()->back()->withInput()->with('error', 'No active schedule found for this user on the selected date.');
        }

        // Gabungkan tanggal dan waktu
        $clockInTimestamp = $validated['clock_in'] ? $validated['date'] . ' ' . $validated['clock_in'] . ':00' : null;
        $clockOutTimestamp = $validated['clock_out'] ? $validated['date'] . ' ' . $validated['clock_out'] . ':00' : null;

        Attendance::updateOrCreate(
            [
                'user_id' => $validated['user_id'],
                'date' => $validated['date'],
            ],
            [
                'work_schedule_id' => $userSchedule->work_schedule_id,
                'clock_in' => $clockInTimestamp,
                'clock_out' => $clockOutTimestamp,
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                // Anda bisa tambahkan kalkulasi 'late_minutes' di sini jika diperlukan
            ]
        );

        return redirect()->route('attendance.correction.index')->with('success', 'Attendance data has been saved successfully.');
    }
}
