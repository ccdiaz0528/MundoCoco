<?php

namespace App\Filament\Pages;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\InventarioService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Cierre con conteo físico: reemplaza la planilla "Inventario final" de la
 * tienda. Se digita lo contado por producto y el sistema registra los
 * sobrantes/faltantes como ajustes trazables (RF03/RF12). La primera vez se
 * usa en modo "inventario inicial" (RF02).
 */
class ConteoFisico extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Conteo físico';

    protected static UnitEnum|string|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Cierre con conteo físico';

    protected static ?string $slug = 'conteo-fisico';

    protected string $view = 'filament.pages.conteo-fisico';

    /** @var array<int|string, mixed> producto_id => cantidad contada */
    public array $conteos = [];

    public bool $esInicial = false;

    public ?string $fecha = null;

    public ?string $observaciones = null;

    /** @var array<int, array{nombre: string, sistema: int, contado: int, diferencia: int, valor: float}> */
    public array $resultado = [];

    public bool $resultadoInicial = false;

    public function mount(): void
    {
        $this->fecha = Carbon::now()->toDateString();
        // Sin movimientos registrados la tienda aún no tiene inventario inicial.
        $this->esInicial = ! MovimientoInventario::query()->exists();
    }

    public function registrar(): void
    {
        $this->validate([
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $filas = app(InventarioService::class)->registrarConteo($this->conteos, $this->esInicial, $this->observaciones, $this->fecha);

        // En el inventario inicial no hay diferencia: se valoriza lo contado.
        $this->resultadoInicial = $this->esInicial;
        $this->resultado = $filas->map(fn (array $fila) => [
            'nombre' => $fila['producto']->nombre,
            'sistema' => $fila['sistema'],
            'contado' => $fila['contado'],
            'diferencia' => $this->esInicial ? 0 : $fila['diferencia'],
            'valor' => ($this->esInicial ? $fila['contado'] : $fila['diferencia']) * (float) $fila['producto']->precio_venta,
        ])->all();

        $ajustados = $filas->whereNotNull('movimiento')->count();
        Notification::make()
            ->title($this->esInicial ? "Inventario inicial registrado ({$ajustados} productos)" : "Conteo registrado: {$ajustados} productos ajustados")
            ->success()
            ->send();

        $this->dispatch('conteo-registrado');
        $this->conteos = [];
        $this->esInicial = false;
        $this->observaciones = null;
    }

    public function getViewData(): array
    {
        /** @var Collection<string, Collection<int, Producto>> $porCategoria */
        $porCategoria = Producto::query()
            ->with('categoria')
            ->where('activo', true)
            ->orderBy('categoria_id')
            ->orderBy('nombre')
            ->get()
            ->groupBy(fn (Producto $producto) => $producto->categoria?->nombre ?? 'Sin categoría');

        return [
            'porCategoria' => $porCategoria,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('registrar movimientos') ?? false;
    }
}
