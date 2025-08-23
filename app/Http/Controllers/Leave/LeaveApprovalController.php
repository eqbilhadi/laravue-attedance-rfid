<?php

namespace App\Http\Controllers\Leave;

use App\Enum\LeaveRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class LeaveApprovalController extends Controller
{
    /**
     * Menampilkan daftar pengajuan cuti yang menunggu persetujuan.
     */
    public function index(Request $request)
    {
        $query = LeaveRequest::query()
            ->with(['user:id,name,avatar,gender', 'leaveType:id,name'])
            ->where('status', LeaveRequestStatus::PENDING) // Hanya tampilkan yang Pending
            ->latest();
        
        // TODO: Tambahkan logika di sini jika manajer hanya boleh melihat timnya
        // if ($user->isManager()) {
        //     $query->whereIn('user_id', $user->subordinates()->pluck('id'));
        // }

        $data = $query->paginate(10)->withQueryString();

        return Inertia::render('leave/approval/Index', [
            'data' => $data,
        ]);
    }

    /**
     * Memperbarui status pengajuan (Approve/Reject).
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:1000',
        ]);

        try {
            if ($validated['action'] === 'approve') {
                $leaveRequest->status = LeaveRequestStatus::APPROVED;
                $leaveRequest->rejection_reason = null;
                $message = 'Leave request has been approved.';
            } else {
                $leaveRequest->status = LeaveRequestStatus::REJECTED;
                $leaveRequest->rejection_reason = $validated['rejection_reason'];
                $message = 'Leave request has been rejected.';
            }

            $leaveRequest->approved_by = Auth::id();
            $leaveRequest->approved_at = now();
            $leaveRequest->save();

            return redirect()->back()->with('success', [
                'title' => 'Action Successful!',
                'description' => $message
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to update leave request status', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', [
                'title' => 'Action Failed!',
                'description' => 'An error occurred, please try again.'
            ]);
        }
    }
}
