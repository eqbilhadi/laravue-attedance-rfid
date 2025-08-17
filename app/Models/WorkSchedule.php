<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $table = 'mst_work_schedules';

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the days for the work schedule.
     */
    public function days()
    {
        return $this->hasMany(WorkScheduleDay::class, 'work_schedule_id');
    }

    /**
     * Get the user schedules that use this schedule.
     */
    public function userSchedules()
    {
        return $this->hasMany(UserSchedule::class, 'work_schedule_id');
    }
}
