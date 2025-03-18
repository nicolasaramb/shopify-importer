<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CronSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_name',
        'last_run',
        'next_run',
        'cron_expression'
    ];

    protected $casts = [
        'last_run' => 'datetime',
        'next_run' => 'datetime',
    ];
}