<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enum\AttendanceStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    // Properti tabel diubah menjadi trx_attendances
    protected $table = 'trx_attendances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'work_schedule_id',
        'date',
        'clock_in',
        'clock_out',
        'late_minutes',
        'overtime_minutes',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'status' => AttendanceStatus::class,
    ];

    /**
     * Get the user that owns the attendance record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the work schedule associated with the attendance record.
     */
    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }

    protected function dateString(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date->toDateString(),
        );
    }

    protected function clockInTime(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->clock_in?->format('H:i'),
        );
    }

    protected function clockOutTime(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->clock_out?->format('H:i'),
        );
    }
}
