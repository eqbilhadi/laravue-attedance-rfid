<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSchedule extends Model
{
    use HasFactory;

    protected $table = 'mst_user_schedules';

    protected $fillable = [
        'user_id',
        'work_schedule_id',
        'start_date',
        'end_date',
    ];

    /**
     * Get the user that owns the schedule.
     */
    public function user()
    {
        // Pastikan model User Anda ada dan benar
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the work schedule for the schedule.
     */
    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }
}
