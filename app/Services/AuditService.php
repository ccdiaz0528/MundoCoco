<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public static function log(string $accion, ?Model $modelo = null, ?array $cambios = null, ?array $anteriores = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'accion' => $accion,
            'modelo_type' => $modelo ? get_class($modelo) : null,
            'modelo_id' => $modelo?->getKey(),
            'cambios' => $cambios,
            'valores_anteriores' => $anteriores,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logVentaCreada($venta): void
    {
        self::log('venta_creada', $venta, $venta->toArray());
    }

    public static function logVentaEditada($venta, array $anteriores): void
    {
        self::log('venta_editada', $venta, $venta->toArray(), $anteriores);
    }

    public static function logCajaCerrada($caja): void
    {
        self::log('caja_cerrada', $caja, $caja->toArray());
    }

    public static function logStockAjuste($producto, int $anterior, int $nuevo, string $tipo): void
    {
        self::log('stock_ajuste', $producto, ['stock' => $nuevo, 'tipo' => $tipo], ['stock' => $anterior]);
    }
}
