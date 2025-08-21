<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveType::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = strtolower($request->input('search'));
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
            });

        $data = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('master-data/leave-type/Index', [
            'data' => $data,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mst_leave_types,name',
            'is_deducting_leave' => 'required|boolean',
        ]);

        try {
            LeaveType::create($validated);

            return redirect()->route('master-data.leave-type.index')->with('success', [
                'title' => 'Leave Type Created!',
                'description' => 'Leave type ' . $request->name . ' has been created.'
            ]);
        } catch (Throwable $e) {
            Log::error('Leave type create failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', [
                'title' => 'Failed to Create!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mst_leave_types,name,' . $leaveType->id,
            'is_deducting_leave' => 'required|boolean',
        ]);

        try {
            $leaveType->update($validated);

            return redirect()->route('master-data.leave-type.index')->with('success', [
                'title' => 'Leave Type Updated!',
                'description' => 'Leave type ' . $request->name . ' has been updated.'
            ]);
        } catch (Throwable $e) {
            Log::error('Leave type update failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', [
                'title' => 'Failed to Update!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }

    public function destroy(LeaveType $leaveType)
    {
        try {
            $name = $leaveType->name;
            $leaveType->delete();
            return redirect()->back()->with('success', [
                'title' => 'Leave Type Deleted!',
                'description' => 'Leave type ' . $name . ' has been deleted.'
            ]);
        } catch (Throwable $e) {
            Log::error('Leave type delete failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', [
                'title' => 'Failed to Delete!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }
}
