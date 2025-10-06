<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatabaseBackup extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'database_backups';

    protected $fillable = [
        'date',
        'filename',
        'disk',
        'size_in_kb',
    ];

    protected $casts = [
        'size_in_kb' => 'float',
    ];
}
