<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\WorkTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class WorkTimeController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkTime::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = strtolower($request->input('search'));
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
            });

        $data = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('master-data/work-time/Index', [
            'data' => $data,
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    public function create()
    {
        return Inertia::render('master-data/work-time/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mst_work_times,name',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'late_tolerance_minutes' => 'required|integer|min:0',
        ]);

        try {
            WorkTime::create($validated);

            return redirect()->route('master-data.work-time.index')->with('success', [
                'title' => 'Work time created!',
                'description' => 'Work time ' . $request->name . ' has been created.'
            ]);
        } catch (Throwable $e) {
            Log::error('Work time create failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('error', [
                'title' => 'Failed to created!',
                'description' => 'Please try again.'
            ]);
        }
    }

    public function edit(WorkTime $workTime)
    {
        return Inertia::render('master-data/work-time/Form', [
            'time' => $workTime,
        ]);
    }

    public function update(Request $request, WorkTime $workTime)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mst_work_times,name,' . $workTime->id,
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'late_tolerance_minutes' => 'required|integer|min:0',
        ]);

        try {
            $workTime->update($validated);

            return redirect()->route('master-data.work-time.index')->with('success', [
                'title' => 'Work time updated!',
                'description' => 'Work time ' . $request->name . ' has been updated.'
            ]);
        } catch (Throwable $e) {
            Log::error('Work time create failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('error', [
                'title' => 'Failed to updated!',
                'description' => 'Please try again.'
            ]);
        }
    }

    public function destroy(WorkTime $workTime)
    {
        try {
            $name = $workTime->name;
            $workTime->delete();
            return redirect()->back()->with('success', [
                'title' => 'Work Time Deleted!',
                'description' => 'Work time for ' . $name . ' has been deleted.'
            ]);
        } catch (Throwable $e) {
            Log::error('Work time delete failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('error', [
                'title' => 'Failed to deleted!',
                'description' => 'Please try again.'
            ]);
        }
    }
}
