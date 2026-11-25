<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
@page { margin: 18mm 12mm 16mm 12mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; }
header { border-bottom: 3px solid #ff0a41; padding-bottom: 8px; margin-bottom: 10px; }
.brand { font-size: 18px; font-weight: bold; }
.subtitle { color: #64748b; }
.summary { width: 100%; border-collapse: collapse; margin: 10px 0 12px; }
.summary td { width: 25%; border: 1px solid #d1d5db; padding: 7px; background: #f8fafc; }
.summary strong { display: block; font-size: 12px; margin-top: 3px; }
table { width: 100%; border-collapse: collapse; }
th { background: #111827; color: #fff; padding: 6px 4px; border: 1px solid #111827; }
td { border: 1px solid #d1d5db; padding: 5px 4px; }
tbody tr:nth-child(even) td { background: #f8fafc; }
.right { text-align: right; }
.center { text-align: center; }
.filters { margin-bottom: 8px; }
.filters span { display: inline-block; margin-right: 16px; }
.section-title { background: #ff0a41; color: #fff; padding: 6px 8px; margin: 14px 0 6px; font-size: 11px; }
footer { position: fixed; bottom: -10mm; left: 0; right: 0; text-align: center; color: #64748b; border-top: 1px solid #d1d5db; padding-top: 4px; }
.page-number:before { content: "Página " counter(page) " de " counter(pages); }
</style>
</head>
<body>
<header>
  <div class="brand">FARM SJB</div>
  <div class="subtitle">Sistema de Venta de Medicamentos</div>
  <h2 style="margin:6px 0 0">Reporte de ventas y medicamentos</h2>
  <div class="subtitle">Generado: {{ $generatedAt->format('d/m/Y H:i') }}</div>
</header>

<div class="filters">
  <span><strong>Periodo:</strong> {{ $filters['period'] }}</span>
  <span><strong>Desde:</strong> {{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') : 'Todos' }}</span>
  <span><strong>Hasta:</strong> {{ $filters['end_date'] ? \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') : 'Todos' }}</span>
  @if($filters['seller_name'])<span><strong>Vendedor:</strong> {{ $filters['seller_name'] }}</span>@endif
  @if($filters['customer_name'])<span><strong>Cliente:</strong> {{ $filters['customer_name'] }}</span>@endif
</div>

<table class="summary">
<tr>
<td>Cantidad de operaciones<strong>{{ $summary['operations'] }}</strong></td>
<td>Ingresos acumulados<strong>S/ {{ number_format($summary['total'],2) }}</strong></td>
<td>IGV acumulado<strong>S/ {{ number_format($summary['igv'],2) }}</strong></td>
<td>Ticket promedio<strong>S/ {{ number_format($summary['average'],2) }}</strong></td>
</tr>
</table>

<div class="section-title">Reporte de ventas</div>
<table>
<thead><tr><th>Código</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th><th class="right">Subtotal</th><th class="right">IGV</th><th class="right">Total</th><th>Estado</th></tr></thead>
<tbody>
@forelse($sales as $sale)
<tr><td>{{ str_pad($sale->id,6,'0',STR_PAD_LEFT) }}</td><td>{{ $sale->date->format('d/m/Y H:i') }}</td><td>{{ $sale->customer->name }}</td><td>{{ $sale->user->name }}</td><td class="right">S/ {{ number_format($sale->total-$sale->igv,2) }}</td><td class="right">S/ {{ number_format($sale->igv,2) }}</td><td class="right">S/ {{ number_format($sale->total,2) }}</td><td class="center">Completada</td></tr>
@empty
<tr><td colspan="8" class="center">No existen ventas para los filtros seleccionados</td></tr>
@endforelse
</tbody>
</table>

@if($user->hasRole('Administrador','Químico farmacéutico'))
<div class="section-title">Reporte de medicamentos</div>
<table>
<thead><tr><th>Medicamento</th><th>Categoría</th><th class="right">Precio venta</th><th class="right">Stock</th><th>Estado</th></tr></thead>
<tbody>
@forelse($medicines as $medicine)
<tr><td>{{ $medicine->name }}</td><td>{{ $medicine->category->name }}</td><td class="right">S/ {{ number_format($medicine->price,2) }}</td><td class="right">{{ $medicine->stock }}</td><td>@if($medicine->stock===0)Agotado@elseif($medicine->stock<=10)Stock bajo@elseDisponible@endif</td></tr>
@empty<tr><td colspan="5" class="center">No hay medicamentos registrados</td></tr>@endforelse
</tbody>
</table>
@endif

@if($user->hasRole('Administrador'))
<div class="section-title">Reporte de vendedores</div>
<table>
<thead><tr><th>Vendedor</th><th class="right">Ventas</th><th class="right">Total vendido</th><th class="right">Promedio</th></tr></thead>
<tbody>@foreach($sellers as $seller)<tr><td>{{ $seller->name }}</td><td class="right">{{ $seller->count }}</td><td class="right">S/ {{ number_format($seller->total,2) }}</td><td class="right">S/ {{ number_format($seller->average,2) }}</td></tr>@endforeach</tbody>
</table>
@endif

<footer>FARM SJB · Sistema de Venta de Medicamentos · Generado {{ $generatedAt->format('d/m/Y H:i') }} · <span class="page-number"></span></footer>
</body>
</html>
