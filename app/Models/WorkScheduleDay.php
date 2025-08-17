<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkScheduleDay extends Model
{
    use HasFactory;

    protected $table = 'mst_work_schedule_days';

    public $timestamps = false; // Tabel ini tidak punya kolom timestamps

    protected $fillable = [
        'work_schedule_id',
        'day_of_week',
        'work_time_id',
    ];

    /**
     * Get the schedule that owns the day.
     */
    public function schedule()
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }

    /**
     * Get the work time for the day.
     */
    public function time()
    {
        return $this->belongsTo(WorkTime::class, 'work_time_id');
    }
}
