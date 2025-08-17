<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use App\Models\WorkTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class WorkScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkSchedule::query()
            ->with(['days.time']) // Eager load relasi untuk ditampilkan
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = strtolower($request->input('search'));
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
            });

        $data = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('master-data/work-schedule/Index', [
            'data' => $data,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('master-data/work-schedule/Form', [
            'times' => WorkTime::select('id', 'name', 'start_time', 'end_time')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mst_work_schedules,name',
            'description' => 'nullable|string',
            'days' => 'required|array|size:7',
            'days.*.day_of_week' => 'required|integer|between:1,7',
            'days.*.work_time_id' => 'nullable|integer|exists:mst_work_times,id',
        ]);

        DB::beginTransaction();
        try {
            $workSchedule = WorkSchedule::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
            ]);

            $workSchedule->days()->createMany($validated['days']);

            DB::commit();

            return redirect()->route('master-data.work-schedule.index')->with('success', [
                'title' => 'Work Schedule Created!',
                'description' => 'Schedule ' . $request->name . ' has been created.'
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Work schedule create failed', ['error' => $e->getMessage()]);

            return redirect()->back()->withInput()->with('error', [
                'title' => 'Failed to Create!',
                'description' => 'Please try again.'
            ]);
        }
    }

    public function edit(WorkSchedule $workSchedule)
    {
        return Inertia::render('master-data/work-schedule/Form', [
            'schedule' => $workSchedule->load('days'),
            'times' => WorkTime::select('id', 'name', 'start_time', 'end_time')->get(),
        ]);
    }

    public function update(Request $request, WorkSchedule $workSchedule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mst_work_schedules,name,' . $workSchedule->id,
            'description' => 'nullable|string',
            'days' => 'required|array|size:7',
            'days.*.day_of_week' => 'required|integer|between:1,7',
            'days.*.work_time_id' => 'nullable|integer|exists:mst_work_times,id',
        ]);

        DB::beginTransaction();
        try {
            $workSchedule->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
            ]);

            // Hapus hari yang lama dan buat yang baru (cara paling simpel)
            $workSchedule->days()->delete();
            $workSchedule->days()->createMany($validated['days']);

            DB::commit();

            return redirect()->route('master-data.work-schedule.index')->with('success', [
                'title' => 'Work Schedule Updated!',
                'description' => 'Schedule ' . $request->name . ' has been updated.'
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Work schedule update failed', ['error' => $e->getMessage()]);

            return redirect()->back()->withInput()->with('error', [
                'title' => 'Failed to Update!',
                'description' => 'Please try again.'
            ]);
        }
    }

    public function destroy(WorkSchedule $workSchedule)
    {
        try {
            $name = $workSchedule->name;
            $workSchedule->delete(); // onDelete('cascade') akan menghapus days
            return redirect()->back()->with('success', [
                'title' => 'Work Schedule Deleted!',
                'description' => 'Schedule ' . $name . ' has been deleted.'
            ]);
        } catch (Throwable $e) {
            Log::error('Work schedule delete failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', [
                'title' => 'Failed to Delete!',
                'description' => 'Please try again.'
            ]);
        }
    }
}
