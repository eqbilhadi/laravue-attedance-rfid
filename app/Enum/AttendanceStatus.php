<?php

namespace App\Enum;

enum AttendanceStatus: string
{
    case PRESENT = 'Present';
    case LATE = 'Late';
    case ABSENT = 'Absent';
    case SICK = 'Sick';
    case PERMIT = 'Permit';
    case LEAVE = 'Leave';
    case HOLIDAY = 'Holiday';
}
