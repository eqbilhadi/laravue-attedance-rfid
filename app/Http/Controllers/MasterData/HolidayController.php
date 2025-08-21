<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $query = Holiday::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = strtolower($request->input('search'));
                $q->whereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
            })
            ->when($request->filled('year'), function ($q) use ($request) {
                $q->whereYear('date', $request->input('year'));
            });

        $data = $query->orderBy('date', 'asc')->paginate(10)->withQueryString();

        return Inertia::render('master-data/holiday/Index', [
            'data' => $data,
            'filters' => $request->only(['search', 'year']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|unique:mst_holidays,date',
            'description' => 'required|string|max:255',
        ]);

        try {
            Holiday::create($validated);

            return redirect()->route('master-data.holiday.index')->with('success', [
                'title' => 'Holiday Created!',
                'description' => 'Holiday on ' . $request->date . ' has been created.'
            ]);
        } catch (Throwable $e) {
            Log::error('Holiday create failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', [
                'title' => 'Failed to Create!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }

    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'date' => 'required|date|unique:mst_holidays,date,' . $holiday->id,
            'description' => 'required|string|max:255',
        ]);

        try {
            $holiday->update($validated);

            return redirect()->route('master-data.holiday.index')->with('success', [
                'title' => 'Holiday Updated!',
                'description' => 'Holiday ' . $request->description . ' has been updated.'
            ]);
        } catch (Throwable $e) {
            Log::error('Holiday update failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', [
                'title' => 'Failed to Update!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }

    public function destroy(Holiday $holiday)
    {
        try {
            $description = $holiday->description;
            $holiday->delete();
            return redirect()->back()->with('success', [
                'title' => 'Holiday Deleted!',
                'description' => 'Holiday ' . $description . ' has been deleted.'
            ]);
        } catch (Throwable $e) {
            Log::error('Holiday delete failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', [
                'title' => 'Failed to Delete!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }
}
