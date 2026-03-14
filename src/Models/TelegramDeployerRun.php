<?php

namespace Enessvg\LaravelTelegramDeployer\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramDeployerRun extends Model
{
    protected $table = 'telegram_deployer_runs';

    protected $fillable = [
        'action',
        'status',
        'chat_id',
        'user_id',
        'username',
        'request_text',
        'started_at',
        'finished_at',
        'steps',
        'summary',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'steps' => 'array',
    ];
}
