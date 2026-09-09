<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'accion',
        'modelo_type',
        'modelo_id',
        'cambios',
        'valores_anteriores',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'cambios' => 'array',
            'valores_anteriores' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function modelo(): MorphTo
    {
        return $this->morphTo('modelo');
    }
}
