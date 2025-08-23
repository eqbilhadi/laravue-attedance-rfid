<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Enum\LeaveRequestStatus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class LeaveRequestController extends Controller
{
    use AuthorizesRequests;
    
    public function index(Request $request)
    {
        $query = LeaveRequest::query()
            ->with(['user:id,name,avatar,gender', 'leaveType:id,name', 'approver:id,name'])
            ->latest();

        if (Gate::denies('all leave request')) {
            $query->where('user_id', Auth::id());
        } else {
            $query->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('user', function ($subq) use ($request) {
                    $search = strtolower($request->input('search'));
                    $subq->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                });
            });
        }

        // Filter berdasarkan status
        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->input('status'));
        });

        // Filter berdasarkan jenis cuti (leave type)
        $query->when($request->filled('leave_type_id'), function ($q) use ($request) {
            $q->where('leave_type_id', $request->input('leave_type_id'));
        });

        // Filter berdasarkan rentang tanggal
        $query->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            // Mencari pengajuan yang rentang waktunya bersinggungan dengan filter
            $q->where(function ($subq) use ($startDate, $endDate) {
                $subq->where('start_date', '<=', $endDate)
                     ->where('end_date', '>=', $startDate);
            });
        });


        $data = $query->paginate(10)->withQueryString();

        return Inertia::render('leave/request/Index', [
            'data' => $data,
            'filters' => $request->only(['status', 'search', 'leave_type_id', 'start_date', 'end_date']),
            'statuses' => collect(LeaveRequestStatus::cases())->map(fn ($status) => [
                'label' => $status->value,
                'value' => $status->value,
            ]),
            // Kirim data leave types untuk filter
            'leaveTypes' => LeaveType::select('id', 'name')->get(),
        ]);
    }

    public function create()
    {
        $canCreateForOthers = Gate::allows('create for others leave request');

        return Inertia::render('leave/request/Form', [
            'canCreateForOthers' => $canCreateForOthers,
            'leaveTypes' => LeaveType::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:sys_users,id',
            'leave_type_id' => 'required|exists:mst_leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
        ]);

        if ($validated['user_id'] != Auth::id() && Gate::denies('create for others leave request')) {
            abort(403);
        }

        try {
            LeaveRequest::create([
                'user_id' => $validated['user_id'],
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'reason' => $validated['reason'],
                'status' => Gate::allows('approve leave request') ? LeaveRequestStatus::APPROVED : LeaveRequestStatus::PENDING,
                'approved_by' => Gate::allows('approve leave request') ? Auth::id() : null,
                'approved_at' => Gate::allows('approve leave request') ? now() : null,
            ]);

            return redirect()->route('leave.request.index')->with('success', [
                'title' => 'Request Submitted!',
                'description' => 'Your leave request has been submitted successfully.'
            ]);

        } catch (Throwable $e) {
            Log::error('Leave request creation failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', [
                'title' => 'Submission Failed!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }

    /**
     * Menampilkan form untuk mengedit pengajuan.
     */
    public function edit(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load('user');
        // Pastikan user berhak mengedit dan statusnya masih Pending
        $this->authorize('update', $leaveRequest);

        return Inertia::render('leave/request/Form', [
            'request' => $leaveRequest, // Kirim data request ke form
            'canCreateForOthers' => Gate::allows('create for others leave request'),
            'leaveTypes' => LeaveType::select('id', 'name')->get(),
        ]);
    }

    /**
     * Memperbarui pengajuan yang ada.
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorize('update', $leaveRequest);

        $validated = $request->validate([
            'user_id' => 'required|exists:sys_users,id',
            'leave_type_id' => 'required|exists:mst_leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $leaveRequest->update($validated);
            return redirect()->route('leave.request.index')->with('success', [
                'title' => 'Request Updated!',
                'description' => 'The leave request has been successfully updated.'
            ]);
        } catch (Throwable $e) {
            Log::error('Leave request update failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', [
                'title' => 'Update Failed!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }

    /**
     * Menghapus pengajuan.
     */
    public function destroy(LeaveRequest $leaveRequest)
    {
        $this->authorize('delete', $leaveRequest);

        try {
            $leaveRequest->delete();
            return redirect()->back()->with('success', [
                'title' => 'Request Cancelled!',
                'description' => 'The leave request has been successfully cancelled.'
            ]);
        } catch (Throwable $e) {
            Log::error('Leave request cancellation failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', [
                'title' => 'Cancellation Failed!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }
}
