<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfidScan extends Model
{
    protected $table = 'sys_rfid_scans';

    protected $fillable = [
        'device_uid',
        'card_uid',
    ];

    // Relasi scan milik device
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_uid');
    }
}
