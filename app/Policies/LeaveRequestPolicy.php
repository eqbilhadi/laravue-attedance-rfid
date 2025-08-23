<?php

namespace App\Policies;

use App\Enum\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeaveRequestPolicy
{
    /**
     * Tentukan apakah user boleh mengedit pengajuan.
     * Ini yang akan dipanggil oleh $this->authorize('update', $leaveRequest).
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        // User boleh mengedit HANYA JIKA:
        // 1. Dia adalah pemilik pengajuan tersebut.
        // 2. DAN status pengajuannya masih "Pending".
        return $leaveRequest->status === LeaveRequestStatus::PENDING && ($user->id === $leaveRequest->user_id || $user->can('create for others leave request'));
    }

    /**
     * Tentukan apakah user boleh menghapus (membatalkan) pengajuan.
     * Ini yang akan dipanggil oleh $this->authorize('delete', $leaveRequest).
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        // Aturannya sama dengan update.
        return $this->update($user, $leaveRequest);
    }
}
