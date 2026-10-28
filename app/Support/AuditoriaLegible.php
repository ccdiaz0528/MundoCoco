<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Gasto;
use App\Models\MetodoPago;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * RNF04: convierte el registro de auditoría técnico (acción en clave y JSON de
 * cambios) en frases y etiquetas que cualquier usuario del panel entiende.
 * La tabla muestra un resumen corto; el detalle completo va en un modal.
 */
class AuditoriaLegible
{
    /** Nombre legible de cada acción auditada. */
    public const ACCIONES = [
        'venta_creada' => 'Venta registrada',
        'venta_editada' => 'Venta modificada',
        'venta_anulada' => 'Venta anulada',
        'venta_precio_modificado' => 'Precio ajustado en venta',
        'caja_cerrada' => 'Caja cerrada',
        'stock_ajuste' => 'Cambio de stock',
        'producto_actualizado' => 'Producto modificado',
        'token_api_emitido' => 'Token de API emitido',
        'politica_datos_aceptada' => 'Política de datos aceptada',
    ];

    /** Color del badge de cada acción (paleta de Filament). */
    private const COLORES = [
        'venta_creada' => 'success',
        'venta_editada' => 'warning',
        'venta_anulada' => 'danger',
        'venta_precio_modificado' => 'warning',
        'caja_cerrada' => 'info',
        'stock_ajuste' => 'warning',
        'producto_actualizado' => 'info',
        'token_api_emitido' => 'gray',
        'politica_datos_aceptada' => 'gray',
    ];

    /** Nombre legible de cada modelo auditado. */
    private const MODELOS = [
        'Venta' => 'Venta',
        'Producto' => 'Producto',
        'Caja' => 'Caja',
        'Gasto' => 'Gasto',
        'User' => 'Usuario',
        'Categoria' => 'Categoría',
        'MetodoPago' => 'Método de pago',
    ];

    /** Etiqueta en español de cada campo guardado en el detalle. */
    private const CAMPOS = [
        'stock' => 'Stock',
        'tipo' => 'Tipo de movimiento',
        'nombre' => 'Nombre',
        'codigo' => 'Código',
        'descripcion' => 'Descripción',
        'precio_venta' => 'Precio de venta',
        'precio_costo' => 'Precio de costo',
        'precio_base' => 'Precio de lista',
        'precio_unitario' => 'Precio aplicado',
        'categoria_id' => 'Categoría',
        'stock_minimo' => 'Stock mínimo',
        'activo' => 'Activo',
        'motivo' => 'Motivo',
        'categoria' => 'Causa de la anulación',
        'descartado' => 'Producto descartado',
        'total' => 'Total',
        'subtotal' => 'Subtotal',
        'fecha_venta' => 'Fecha de la venta',
        'metodo_pago_id' => 'Método de pago',
        'observaciones' => 'Observaciones',
        'anulada_at' => 'Anulada el',
        'anulada_por' => 'Anulada por',
        'fecha' => 'Fecha de la caja',
        'estado' => 'Estado',
        'saldo_inicial' => 'Saldo inicial',
        'total_ventas' => 'Ventas',
        'total_efectivo' => 'Ventas en efectivo',
        'total_nequi' => 'Ventas por Nequi',
        'total_transferencias' => 'Ventas por transferencia',
        'total_tarjetas' => 'Ventas con tarjeta',
        'total_gastos' => 'Gastos',
        'total_retiros' => 'Retiros',
        'saldo_teorico' => 'Saldo esperado',
        'saldo_real' => 'Saldo contado',
        'diferencia' => 'Diferencia',
        'fecha_cierre' => 'Cerrada el',
        'observaciones_cierre' => 'Observaciones de cierre',
        'politica_aceptada_at' => 'Política aceptada el',
        'monto' => 'Monto',
        'cantidad' => 'Cantidad',
        'email' => 'Correo',
    ];

    /** Campos que se muestran como dinero. */
    private const CAMPOS_DINERO = [
        'total', 'subtotal', 'precio_venta', 'precio_costo', 'precio_base', 'precio_unitario',
        'saldo_inicial', 'saldo_real', 'saldo_teorico', 'diferencia',
        'total_ventas', 'total_gastos', 'total_retiros',
        'total_efectivo', 'total_nequi', 'total_transferencias', 'total_tarjetas', 'monto',
    ];

    /** Campos de fecha y el formato con el que se muestran. */
    private const CAMPOS_FECHA = [
        'fecha' => 'd/m/Y',
        'fecha_venta' => 'd/m/Y H:i',
        'fecha_cierre' => 'd/m/Y H:i',
        'anulada_at' => 'd/m/Y H:i',
        'politica_aceptada_at' => 'd/m/Y H:i',
        'fecha_movimiento' => 'd/m/Y',
    ];

    /** Campos internos que no le dicen nada al usuario. */
    private const CAMPOS_OCULTOS = [
        'id', 'created_at', 'updated_at', 'deleted_at', 'sucursal_id', 'user_id',
        'modelo_type', 'modelo_id', 'ip_address', 'user_agent', 'email_verified_at',
        'remember_token', 'password', 'referencia_type', 'referencia_id',
    ];

    private const TIPOS_MOVIMIENTO = [
        MovimientoInventario::TIPO_INICIAL => 'Conteo inicial',
        MovimientoInventario::TIPO_COMPRA => 'Compra',
        MovimientoInventario::TIPO_DEVOLUCION => 'Devolución',
        MovimientoInventario::TIPO_AJUSTE_POSITIVO => 'Ajuste positivo',
        MovimientoInventario::TIPO_VENTA => 'Venta',
        MovimientoInventario::TIPO_AJUSTE_NEGATIVO => 'Ajuste negativo',
        MovimientoInventario::TIPO_MERMA => 'Merma',
    ];

    public static function accion(?string $accion): string
    {
        if ($accion === null || $accion === '') {
            return 'Sin acción';
        }

        return self::ACCIONES[$accion] ?? Str::headline($accion);
    }

    public static function color(?string $accion): string
    {
        return self::COLORES[$accion] ?? 'gray';
    }

    /** Tipo de registro auditado ("Venta", "Producto"…) o null si no aplica. */
    public static function tipoRegistro(AuditLog $log): ?string
    {
        if (! $log->modelo_type) {
            return null;
        }

        $clase = class_basename($log->modelo_type);

        return self::MODELOS[$clase] ?? $clase;
    }

    /** Nombre reconocible del registro afectado ("Aceite de coco", "Venta #12"…). */
    public static function registro(AuditLog $log): string
    {
        $tipo = self::tipoRegistro($log);

        if ($tipo === null) {
            return 'Sin registro';
        }

        $nombre = self::nombreModelo($log->modelo);

        return $nombre ?? $tipo.' #'.$log->modelo_id;
    }

    /** Resumen de una línea para la columna "Detalle". */
    public static function resumen(AuditLog $log): string
    {
        $cambios = is_array($log->cambios) ? $log->cambios : [];
        $anteriores = is_array($log->valores_anteriores) ? $log->valores_anteriores : [];

        $partes = match ($log->accion) {
            'venta_creada' => [
                self::valor('total', $cambios['total'] ?? null),
                self::valor('metodo_pago_id', $cambios['metodo_pago_id'] ?? null),
            ],
            'venta_editada' => [self::camposCambiados(self::camposReales($anteriores, $cambios))],
            'venta_anulada' => [
                isset($cambios['motivo']) && trim((string) $cambios['motivo']) !== ''
                    ? 'Motivo: '.$cambios['motivo']
                    : null,
                self::valor('total', $cambios['total'] ?? null),
            ],
            'venta_precio_modificado' => [
                count($cambios['lineas'] ?? []).' producto(s) con precio ajustado',
            ],
            'caja_cerrada' => [
                'Saldo esperado '.self::valor('saldo_teorico', $cambios['saldo_teorico'] ?? null),
                'Diferencia '.self::valor('diferencia', $cambios['diferencia'] ?? null),
            ],
            'stock_ajuste' => [
                'Stock '.($anteriores['stock'] ?? '?').' → '.($cambios['stock'] ?? '?'),
                self::valor('tipo', $cambios['tipo'] ?? null),
            ],
            'producto_actualizado' => [self::cambioPrincipal($anteriores, $cambios)],
            'token_api_emitido' => [
                isset($cambios['nombre']) && trim((string) $cambios['nombre']) !== ''
                    ? 'Token «'.$cambios['nombre'].'»'
                    : 'Token generado',
            ],
            'politica_datos_aceptada' => [
                'Aceptada el '.self::valor('politica_aceptada_at', $cambios['politica_aceptada_at'] ?? null),
            ],
            default => [self::camposCambiados(array_keys($cambios))],
        };

        return implode(' · ', array_filter(
            [self::registro($log), ...$partes],
            fn (?string $parte): bool => $parte !== null && $parte !== '',
        ));
    }

    /**
     * Detalle completo del registro, ya traducido a etiquetas y valores
     * legibles. Se usa en el modal "Ver detalle".
     *
     * @return array<int, array{campo: string, antes: string, despues: string}>
     */
    public static function detalles(AuditLog $log): array
    {
        $cambios = is_array($log->cambios) ? $log->cambios : [];
        $anteriores = is_array($log->valores_anteriores) ? $log->valores_anteriores : [];

        if ($log->accion === 'venta_precio_modificado') {
            return self::detallePrecios($cambios['lineas'] ?? []);
        }

        // Con valores anteriores se listan solo los campos que de verdad
        // cambiaron; sin ellos, el log es una foto completa del registro.
        if ($anteriores !== []) {
            $campos = self::camposReales($anteriores, $cambios);

            // Datos nuevos que explican el cambio (p. ej. el tipo de
            // movimiento de un ajuste de stock).
            foreach ($cambios as $campo => $valor) {
                $campo = (string) $campo;

                if (array_key_exists($campo, $anteriores)
                    || in_array($campo, self::CAMPOS_OCULTOS, true)
                    || is_array($valor)) {
                    continue;
                }

                if (self::valor($campo, $valor) !== '—') {
                    $campos[] = $campo;
                }
            }

            return array_map(fn (string $campo): array => [
                'campo' => self::etiqueta($campo),
                'antes' => array_key_exists($campo, $anteriores)
                    ? self::valor($campo, $anteriores[$campo])
                    : '—',
                'despues' => self::valor($campo, $cambios[$campo] ?? null),
            ], self::ordenarCampos($campos));
        }

        $detalles = [];

        foreach (self::ordenarCampos(array_keys($cambios)) as $campo) {
            $valor = $cambios[$campo];

            if (in_array($campo, self::CAMPOS_OCULTOS, true)) {
                continue;
            }

            $despues = self::valor($campo, $valor);

            // En una foto completa, los campos vacíos solo hacen ruido.
            if ($despues === '—') {
                continue;
            }

            $detalles[] = [
                'campo' => self::etiqueta($campo),
                'antes' => '—',
                'despues' => $despues,
            ];
        }

        return $detalles;
    }

    /**
     * Ordena los campos en el orden en que se leen mejor (saldo inicial,
     * ventas, gastos… en una caja) y deja al final los desconocidos.
     *
     * @param  array<int, int|string>  $campos
     * @return array<int, string>
     */
    private static function ordenarCampos(array $campos): array
    {
        $orden = array_flip(array_keys(self::CAMPOS));
        $campos = array_map(strval(...), array_values($campos));

        usort($campos, fn (string $a, string $b): int => ($orden[$a] ?? PHP_INT_MAX) <=> ($orden[$b] ?? PHP_INT_MAX));

        return $campos;
    }

    /**
     * Campos que realmente cambiaron entre la foto anterior y la nueva.
     * Así el detalle no repite datos que se guardaron igual.
     *
     * @return array<int, string>
     */
    private static function camposReales(array $anteriores, array $cambios): array
    {
        $campos = [];

        foreach ($anteriores as $campo => $antes) {
            // Sin valor nuevo comparable (relaciones, campos que no se envían).
            if (! array_key_exists($campo, $cambios)) {
                continue;
            }

            if ($antes != $cambios[$campo]) {
                $campos[] = (string) $campo;
            }
        }

        return array_values(array_diff($campos, self::CAMPOS_OCULTOS));
    }

    /** Valor de un campo ya formateado para leerlo (dinero, fechas, sí/no…). */
    public static function valor(string $campo, mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        if (in_array($campo, self::CAMPOS_DINERO, true)) {
            return self::dinero($valor);
        }

        if (isset(self::CAMPOS_FECHA[$campo])) {
            return self::fecha($valor, self::CAMPOS_FECHA[$campo]);
        }

        return match ($campo) {
            'metodo_pago_id' => MetodoPago::query()->whereKey($valor)->value('nombre') ?? '#'.$valor,
            'categoria_id' => Categoria::query()->whereKey($valor)->value('nombre') ?? '#'.$valor,
            'anulada_por' => User::query()->whereKey($valor)->value('name') ?? '#'.$valor,
            'tipo' => self::TIPOS_MOVIMIENTO[(string) $valor] ?? Str::headline((string) $valor),
            'categoria' => self::etiquetaCategoria($valor),
            'estado' => Str::headline((string) $valor),
            'activo', 'descartado' => (bool) $valor ? 'Sí' : 'No',
            default => self::texto($valor),
        };
    }

    /** La "categoría" del log puede ser una causa de anulación o un gasto. */
    private static function etiquetaCategoria(mixed $valor): string
    {
        if (! is_string($valor)) {
            return self::texto($valor);
        }

        return config('mundococo.motivos_anulacion.'.$valor)
            ?? Gasto::CATEGORIAS[$valor]
            ?? Str::headline($valor);
    }

    /** Etiqueta en español de un campo del detalle. */
    public static function etiqueta(string $campo): string
    {
        return self::CAMPOS[$campo] ?? Str::headline(str_replace('_', ' ', $campo));
    }

    private static function nombreModelo(?Model $modelo): ?string
    {
        return match (true) {
            $modelo instanceof Producto => $modelo->nombre,
            $modelo instanceof Venta => 'Venta #'.$modelo->getKey(),
            $modelo instanceof Caja => $modelo->fecha
                ? 'Caja del '.$modelo->fecha->format('d/m/Y')
                : 'Caja #'.$modelo->getKey(),
            $modelo instanceof Gasto => 'Gasto #'.$modelo->getKey(),
            $modelo instanceof User => $modelo->name,
            $modelo instanceof Categoria => $modelo->nombre,
            $modelo instanceof MetodoPago => $modelo->nombre,
            $modelo !== null => class_basename($modelo).' #'.$modelo->getKey(),
            default => null,
        };
    }

    /** "Cambió: Total, Observaciones" con un tope para no hacer una novela. */
    private static function camposCambiados(array $claves): ?string
    {
        $claves = array_values(array_diff($claves, self::CAMPOS_OCULTOS));

        if ($claves === []) {
            return null;
        }

        $etiquetas = array_map(self::etiqueta(...), $claves);

        if (count($etiquetas) > 3) {
            $restantes = count($etiquetas) - 3;

            return 'Cambió: '.implode(', ', array_slice($etiquetas, 0, 3)).' y '.$restantes.' campo(s) más';
        }

        return 'Cambió: '.implode(', ', $etiquetas);
    }

    /** Primer cambio encontrado y, si hay más, cuántos quedaron. */
    private static function cambioPrincipal(array $anteriores, array $cambios): ?string
    {
        $claves = self::camposReales($anteriores, $cambios);

        if ($claves === []) {
            return null;
        }

        $campo = $claves[0];
        $texto = self::etiqueta($campo).': '
            .self::valor($campo, $anteriores[$campo])
            .' → '
            .self::valor($campo, $cambios[$campo] ?? null);

        $restantes = count($claves) - 1;

        return $restantes > 0 ? $texto.' (+'.$restantes.' más)' : $texto;
    }

    /**
     * Precios ajustados de una venta: nombre del producto y salto de valor.
     *
     * @return array<int, array{campo: string, antes: string, despues: string}>
     */
    private static function detallePrecios(array $lineas): array
    {
        $detalles = [];

        foreach ($lineas as $linea) {
            $producto = Producto::withTrashed()->find($linea['producto_id'] ?? null);
            $nombre = $producto?->nombre ?? 'Producto #'.($linea['producto_id'] ?? '?');

            $detalles[] = [
                'campo' => $nombre.': precio de lista vs. precio aplicado',
                'antes' => self::dinero($linea['precio_base'] ?? null),
                'despues' => self::dinero($linea['precio_unitario'] ?? null),
            ];
        }

        return $detalles;
    }

    private static function dinero(mixed $valor): string
    {
        if (! is_numeric($valor)) {
            return '—';
        }

        $numero = (float) $valor;
        $decimales = abs($numero - round($numero)) > 0.004 ? 2 : 0;

        return '$ '.number_format($numero, $decimales, ',', '.');
    }

    private static function fecha(mixed $valor, string $formato): string
    {
        try {
            return CarbonImmutable::parse($valor)->timezone(config('app.timezone'))->format($formato);
        } catch (Throwable) {
            return self::texto($valor);
        }
    }

    private static function texto(mixed $valor): string
    {
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }

        if (is_array($valor)) {
            $plano = array_filter($valor, fn (mixed $item): bool => is_scalar($item));

            return $plano === [] ? '—' : implode(', ', array_map(strval(...), $plano));
        }

        return (string) $valor;
    }
}
