<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    // Nama tabel yang dipakai kalau nggak default plural modelnya
    protected $table = 'sys_devices';

    protected $fillable = [
        'device_name',
        'device_uid',
        'location',
        'ip_address',
        'is_active',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relasi 1 device punya banyak scan
    public function scans(): HasMany
    {
        return $this->hasMany(RfidScan::class, 'device_uid');
    }
}
