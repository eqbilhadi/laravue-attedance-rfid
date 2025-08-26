<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class RfidScan extends Model
{
    protected $table = 'sys_rfid_scans';

    protected $fillable = [
        'device_uid',
        'card_uid',
        'user_id',
    ];

    // Relasi scan milik device
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_uid', 'device_uid');
    }

    // rfid_scan.card_uid -> user_rfids.uid dan user_rfids.user_id -> users.id
    // public function user(): HasOneThrough
    // {
    //     return $this->hasOneThrough(
    //         User::class,
    //         UserRfid::class,
    //         'uid',
    //         'id',
    //         'card_uid',
    //         'user_id'
    //     );
    // }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
