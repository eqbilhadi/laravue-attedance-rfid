<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSchedule;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class ScheduleAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = UserSchedule::query()
            ->with(['user:id,name,email,avatar,gender', 'workSchedule.days.time'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('user', fn($qUser) => $qUser->where('name', 'like', '%' . $request->input('search') . '%'));
            })
            ->when($request->filled('work_schedule_id'), function ($q) use ($request) {
                $q->where('work_schedule_id', $request->input('work_schedule_id'));
            });

        $data = $query->latest('start_date')->paginate(10)->withQueryString();

        return Inertia::render('master-data/schedule-assignment/Index', [
            'data' => $data,
            'filters' => $request->only(['search', 'work_schedule_id']),
            'users' => User::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'workSchedules' => WorkSchedule::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('master-data/schedule-assignment/Form', [
            'workSchedules' => WorkSchedule::with(['days.time'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateSchedule($request);

        DB::beginTransaction();
        try {
            foreach ($validated['user_ids'] as $userId) {
                UserSchedule::create([
                    'user_id' => $userId,
                    'work_schedule_id' => $validated['work_schedule_id'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                ]);
            }
            DB::commit();

            return redirect()->route('master-data.schedule-assignment.index')->with('success', [
                'title' => 'Schedule Assigned!',
                'description' => 'Schedule has been successfully assigned to ' . count($validated['user_ids']) . ' user(s).'
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Schedule assignment create failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', [
                'title' => 'Assignment Failed!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }

    public function edit(UserSchedule $scheduleAssignment)
    {
        return Inertia::render('master-data/schedule-assignment/Form', [
            'assignment' => $scheduleAssignment->load(['user', 'workSchedule']),
            'workSchedules' => WorkSchedule::with(['days.time'])->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, UserSchedule $scheduleAssignment)
    {
        $validated = $this->validateSchedule($request, $scheduleAssignment->id);

        try {
            $scheduleAssignment->update($validated);
            $userName = $scheduleAssignment->user->name;

            return redirect()->route('master-data.schedule-assignment.index')->with('success', [
                'title' => 'Assignment Updated!',
                'description' => 'Schedule assignment for ' . $userName . ' has been updated.'
            ]);
        } catch (Throwable $e) {
            Log::error('Schedule assignment update failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', [
                'title' => 'Update Failed!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }

    public function destroy(UserSchedule $scheduleAssignment)
    {
        try {
            $userName = $scheduleAssignment->user->name;
            $scheduleAssignment->delete();

            return redirect()->back()->with('success', [
                'title' => 'Assignment Deleted!',
                'description' => 'Schedule assignment for ' . $userName . ' has been deleted.'
            ]);
        } catch (Throwable $e) {
            Log::error('Schedule assignment delete failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', [
                'title' => 'Delete Failed!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }

    private function validateSchedule(Request $request, $ignoreId = null)
    {
        // --- VALIDASI UNTUK UPDATE (USER TUNGGAL) ---
        if ($ignoreId) {
            return $request->validate([
                'user_id' => 'required|exists:sys_users,id',
                'work_schedule_id' => 'required|exists:mst_work_schedules,id',
                'start_date' => 'required|date',
                'end_date' => [
                    'nullable',
                    'date',
                    'after_or_equal:start_date',
                    function ($attribute, $value, $fail) use ($request, $ignoreId) {
                        $query = UserSchedule::where('user_id', $request->input('user_id'))
                            ->where('id', '!=', $ignoreId)
                            ->where(function ($q) use ($request, $value) {
                                $startDate = $request->input('start_date');
                                $endDate = $value ?? '9999-12-31';
                                $q->where('start_date', '<=', $endDate)
                                    ->where(fn($sub) => $sub->where('end_date', '>=', $startDate)->orWhereNull('end_date'));
                            });

                        if ($query->exists()) {
                            $fail('The schedule overlaps with an existing assignment for this user.');
                        }
                    },
                ],
            ]);
        }

        // --- VALIDASI UNTUK CREATE (BULK/BANYAK USER) ---
        return $request->validate([
            'work_schedule_id' => 'required|exists:mst_work_schedules,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_ids' => [
                'required',
                'array',
                'min:1',
                // Validasi custom untuk mengecek overlap pada setiap user yang dipilih
                function ($attribute, $value, $fail) use ($request) {
                    $userIds = $value; // $value adalah array user_ids yang dikirim
                    $startDate = $request->input('start_date');
                    $endDate = $request->input('end_date') ?? '9999-12-31';

                    // Cari user yang sudah punya jadwal tumpang tindih di rentang tanggal ini
                    $overlappingUsers = User::whereIn('id', $userIds)
                        ->whereHas('userSchedules', function ($query) use ($startDate, $endDate) {
                            $query->where('start_date', '<=', $endDate)
                                ->where(fn($sub) => $sub->where('end_date', '>=', $startDate)->orWhereNull('end_date'));
                        })
                        ->pluck('name'); // Ambil nama user yang bermasalah

                    if ($overlappingUsers->isNotEmpty()) {
                        // Jika ada, gagalkan validasi dan berikan pesan error yang jelas
                        $fail('Schedule overlaps for the following user(s): ' . $overlappingUsers->implode(', '));
                    }
                },
            ],
            'user_ids.*' => 'exists:sys_users,id',
        ]);
    }
}
