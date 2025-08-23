<?php

namespace App\Enum;

/**
 * Enum for Leave Request Status.
 */
enum LeaveRequestStatus: string
{
    case PENDING = 'Pending';
    case APPROVED = 'Approved';
    case REJECTED = 'Rejected';
}

