<?php

namespace App\Models;

use App\Models\Concerns\PerteneceASucursal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    use HasFactory;

    // RNF07: ligado a una sucursal (la principal por defecto).
    use PerteneceASucursal;

    protected $table = 'gastos';

    protected $fillable = [
        'fecha',
        'descripcion',
        'categoria',
        'monto',
        'user_id',
        'caja_id',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public const CATEGORIAS = [
        'materia_prima' => 'Materia Prima',
        'servicios' => 'Servicios',
        'transporte' => 'Transporte',
        'otros' => 'Otros',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }
}
