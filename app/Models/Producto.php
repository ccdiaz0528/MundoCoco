<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'precio_venta',
        'precio_costo',
        'stock_actual',
        'stock_minimo',
        'activo',
    ];

    // Un producto pertenece a una categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    // Un producto aparece en muchos detalles de venta
    public function ventaDetalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    // Método útil: saber si el stock está bajo
    public function stockBajo(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }
}
