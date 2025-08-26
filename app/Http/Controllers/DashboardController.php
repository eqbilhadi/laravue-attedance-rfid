<?php

namespace App\Http\Controllers;

use App\Enum\AttendanceStatus;
use App\Enum\LeaveRequestStatus;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\RawAttendance;
use App\Models\RfidScan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman utama Dashboard (tanpa data awal).
     * Data akan diambil oleh frontend melalui endpoint API.
     */
    public function index()
    {
        return Inertia::render('Dashboard');
    }

    // --- API ENDPOINTS ---

    /**
     * Menyediakan data untuk kartu ringkasan (summary cards).
     */
    public function getSummaryCards()
    {
        $today = Carbon::today();
        $liveAttendance = $this->getLiveAttendanceSummaryData($today);
        $liveAttendanceCollection = collect($liveAttendance);

        // Hitung metrik dari data live
        $presentCount = $liveAttendanceCollection->whereIn('status', ['Present', 'Checked Out'])->count();
        $lateCount = $liveAttendanceCollection->where('status', 'Late')->count();
        $notYetArrivedCount = $liveAttendanceCollection->where('status', 'Not Yet Arrived')->count();
        $absentCount = $liveAttendanceCollection->where('status', 'Absent')->count();
        
        $totalActiveUsers = User::where('is_active', true)->count();
        $scheduledTodayCount = $liveAttendanceCollection->count();
        $onDayOffCount = $totalActiveUsers - $scheduledTodayCount;

        return response()->json([
            'scheduled_today' => $scheduledTodayCount,
            'present_today' => $presentCount + $lateCount,
            'late_today' => $lateCount,
            'absent_today' => $absentCount,
            'not_yet_arrived' => $notYetArrivedCount,
            'on_day_off_today' => $onDayOffCount,
            'on_leave_today' => LeaveRequest::where('status', LeaveRequestStatus::APPROVED)
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->count(),
            'pending_requests' => LeaveRequest::where('status', LeaveRequestStatus::PENDING)->count(),
        ]);
    }

    /**
     * Menyediakan data untuk tabel live attendance.
     */
    public function getLiveAttendanceSummary()
    {
        return response()->json($this->getLiveAttendanceSummaryData(Carbon::today()));
    }

    /**
     * Menyediakan data untuk semua diagram.
     */
    public function getChartData()
    {
        $today = Carbon::today();
        $startOfWeek = $today->copy()->startOfWeek();
        $endOfWeek = $today->copy()->endOfWeek();
        $startOfMonth = $today->copy()->startOfMonth();

        return response()->json([
            'weekly_attendance' => $this->getWeeklyAttendanceChartData($startOfWeek, $endOfWeek),
            'monthly_status_distribution' => $this->getMonthlyStatusDistribution($startOfMonth, $today),
        ]);
    }

    /**
     * Menyediakan data untuk statistik cepat.
     */
    public function getQuickStats()
    {
        $startOfMonth = Carbon::today()->startOfMonth();
        $endOfMonth = Carbon::today()->endOfMonth();

        $employeeOfTheMonth = Attendance::query()
            ->select('user_id', 
                DB::raw('COUNT(CASE WHEN status = \'Present\' THEN 1 END) as present_count'),
                DB::raw('SUM(late_minutes) as total_late_minutes')
            )
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->groupBy('user_id')
            ->orderBy('present_count', 'desc')
            ->orderBy('total_late_minutes', 'asc')
            ->with('user:id,name,avatar,gender')
            ->first();

        $mostLateEmployees = Attendance::query()
            ->select('user_id', DB::raw('COUNT(*) as late_count'))
            ->where('status', AttendanceStatus::LATE)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->groupBy('user_id')
            ->orderBy('late_count', 'desc')
            ->with('user:id,name,avatar,gender')
            ->limit(3)
            ->get();

        return response()->json([
            'employee_of_the_month' => $employeeOfTheMonth,
            'most_late_employees' => $mostLateEmployees,
        ]);
    }


    // --- HELPER FUNCTIONS (Private) ---

    private function getLiveAttendanceSummaryData(Carbon $today): array
    {
        $liveSummary = [];
        $currentTime = now();

        $scheduledUsers = User::where('is_active', true)
            ->whereHas('userSchedules', function ($query) use ($today) {
                $query->where('start_date', '<=', $today)
                      ->where(fn ($q) => $q->where('end_date', '>=', $today)->orWhereNull('end_date'));
            })
            ->with(['userSchedules.workSchedule.days.time'])
            ->get();

        foreach ($scheduledUsers as $user) {
            $userSchedule = $user->userSchedules->first();
            $workScheduleDay = $userSchedule->workSchedule->days->firstWhere('day_of_week', $today->dayOfWeekIso);

            if (!$workScheduleDay || !$workScheduleDay->time) {
                continue;
            }

            $rawAttendance = RawAttendance::where('user_id', $user->id)
                ->where('date', $today->toDateString())
                ->first();

            $workTime = $workScheduleDay->time;
            list($startHour, $startMinute) = explode(':', $workTime->start_time);
            list($endHour, $endMinute) = explode(':', $workTime->end_time);
            
            $scheduledStartTime = $today->copy()->setTime((int)$startHour, (int)$startMinute);
            $scheduledEndTime = $today->copy()->setTime((int)$endHour, (int)$endMinute);

            if ($scheduledEndTime->lt($scheduledStartTime)) {
                $scheduledEndTime->addDay();
            }

            $status = 'Not Yet Arrived';

            if ($rawAttendance?->clock_in) {
                $status = $rawAttendance->clock_in->isAfter($scheduledStartTime->copy()->addMinutes($workTime->late_tolerance_minutes)) ? 'Late' : 'Present';
                if ($rawAttendance->clock_out) {
                    $status = 'Checked Out';
                }
            } else {
                if ($currentTime->isAfter($scheduledEndTime)) {
                    $status = 'Absent';
                }
            }

            $liveSummary[] = [
                'user_name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'clock_in' => $rawAttendance?->clock_in?->format('H:i') ?? '-',
                'clock_out' => $rawAttendance?->clock_out?->format('H:i') ?? '-',
                'status' => $status,
                'work_time_start' => $workTime->start_time,
                'work_time_end' => $workTime->end_time,
            ];
        }
        return $liveSummary;
    }

    /**
     * Helper: Mengambil data untuk diagram tren kehadiran mingguan.
     */
    private function getWeeklyAttendanceChartData(Carbon $start, Carbon $end): array
    {
        $dates = collect(Carbon::parse($start)->daysUntil($end));

        $weeklyData = Attendance::whereBetween('date', [$start, $end])
            ->selectRaw('date, status, count(*) as total')
            ->groupBy('date', 'status')
            ->toBase() // supaya keluar stdClass, lebih ringan
            ->get();

        return [
            'labels' => $dates->map(fn($date) => $date->format('D, d M')),
            'datasets' => [
                [
                    'label' => 'Present',
                    'data' => $dates->map(
                        fn($date) =>
                        $weeklyData
                            ->first(fn($item) => $item->date === $date->toDateString() && $item->status === 'Present')
                            ->total ?? 0
                    ),
                    'backgroundColor' => '#10B981',
                ],
                [
                    'label' => 'Late',
                    'data' => $dates->map(
                        fn($date) =>
                        $weeklyData
                            ->first(fn($item) => $item->date === $date->toDateString() && $item->status === 'Late')
                            ->total ?? 0
                    ),
                    'backgroundColor' => '#EF4444',
                ],
                [
                    'label' => 'Absent',
                    'data' => $dates->map(
                        fn($date) =>
                        $weeklyData
                            ->first(fn($item) => $item->date === $date->toDateString() && $item->status === 'Absent')
                            ->total ?? 0
                    ),
                    'backgroundColor' => '#6B7280',
                ],
            ]
        ];
    }


     private function getMonthlyStatusDistribution(Carbon $start, Carbon $end): array
    {
        $monthlyData = Attendance::whereBetween('date', [$start, $end])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'labels' => $monthlyData->keys()->all(),
            'datasets' => [[
                'data' => $monthlyData->values()->all(),
                'backgroundColor' => $monthlyData->keys()->map(function ($status) {
                    return match ($status) {
                        'Present' => '#10B981',
                        'Late' => '#EF4444',
                        'Absent' => '#6B7280',
                        'Sick', 'Permit' => '#F59E0B',
                        'Leave' => '#3B82F6',
                        'Holiday' => '#E5E7EB',
                        default => '#9CA3AF',
                    };
                })->all(),
            ]],
        ];
    }
}
