@extends('template.administrador')

@section('title', 'Reportes')
@section('name_section', 'Reportes')
@section('description', 'Consultas operativas y estadísticas de ventas e inventario')
@section('item_section', 'Principal')
@section('route_section', route('reports.index'))
@section('section', 'Reportes')
@section('module', 'Principal')

@section('content')
<div class="card bg-white border-0 shadow-sm border-5 border-top border-secondary mb-3">
    <div class="card-header bg-white"><h6 class="fw-bold mb-0">Filtros del reporte</h6></div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.index') }}" class="row g-3">
            <div class="col-12 col-md-3"><label class="form-label small">Periodo</label><select name="period" class="form-select"><option value="day" @selected($filters['period']==='day')>Hoy</option><option value="week" @selected($filters['period']==='week')>Esta semana</option><option value="month" @selected($filters['period']==='month')>Este mes</option><option value="custom" @selected($filters['period']==='custom')>Personalizado</option></select></div>
            <div class="col-6 col-md-2"><label class="form-label small">Fecha inicial</label><input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="form-control"></div>
            <div class="col-6 col-md-2"><label class="form-label small">Fecha final</label><input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="form-control"></div>
            @if (auth()->user()->hasRole('Administrador'))
            <div class="col-12 col-md-2"><label class="form-label small">Vendedor</label><select name="seller_id" class="form-select"><option value="">Todos</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($filters['seller_id'] === $user->id)>{{ $user->name }}</option>@endforeach</select></div>
            @endif
            <div class="col-12 col-md-3"><label class="form-label small">Cliente</label><select name="customer_id" class="form-select"><option value="">Todos</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected($filters['customer_id'] === $customer->id)>{{ $customer->name }}</option>@endforeach</select></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Consultar</button><a href="{{ route('reports.export.xlsx', request()->query()) }}" class="btn btn-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel</a><a href="{{ route('reports.export.pdf', request()->query()) }}" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF</a></div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-secondary">Operaciones</div><div class="fs-4 fw-bold">{{ $summary['operations'] }}</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-secondary">Ingresos acumulados</div><div class="fs-5 fw-bold">S/ {{ number_format($summary['total'],2) }}</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-secondary">IGV acumulado</div><div class="fs-5 fw-bold">S/ {{ number_format($summary['igv'],2) }}</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-secondary">Ticket promedio</div><div class="fs-5 fw-bold">S/ {{ number_format($summary['average'],2) }}</div></div></div></div>
</div>

<div class="card bg-white border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between"><h6 class="fw-bold mb-0">Reporte de ventas</h6><span class="small text-secondary">Generado {{ now()->format('d/m/Y H:i') }}</span></div>
    <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead><tr class="small text-uppercase"><th>Código</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th><th class="text-end">Subtotal</th><th class="text-end">IGV</th><th class="text-end">Total</th><th>Estado</th></tr></thead><tbody>
    @forelse($sales as $sale)<tr><td>#{{ str_pad($sale->id,6,'0',STR_PAD_LEFT) }}</td><td>{{ $sale->date->format('d/m/Y H:i') }}</td><td>{{ $sale->customer->name }}</td><td>{{ $sale->user->name }}</td><td class="text-end">S/ {{ number_format($sale->total-$sale->igv,2) }}</td><td class="text-end">S/ {{ number_format($sale->igv,2) }}</td><td class="text-end fw-semibold">S/ {{ number_format($sale->total,2) }}</td><td><span class="badge text-bg-success">Completada</span></td></tr>
    @empty<tr><td colspan="8" class="text-center text-secondary py-4">No existen ventas para los filtros seleccionados</td></tr>@endforelse
    </tbody></table></div>
</div>

@if (auth()->user()->hasRole('Administrador','Químico farmacéutico'))
<div class="card bg-white border-0 shadow-sm mb-3">
    <div class="card-header bg-white"><h6 class="fw-bold mb-0">Reporte de medicamentos</h6></div>
    <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead><tr class="small text-uppercase"><th>Medicamento</th><th>Categoría</th><th class="text-end">Precio venta</th><th class="text-end">Stock</th><th>Estado</th></tr></thead><tbody>
    @forelse($medicines as $medicine)<tr><td>{{ $medicine->name }}</td><td>{{ $medicine->category->name }}</td><td class="text-end">S/ {{ number_format($medicine->price,2) }}</td><td class="text-end">{{ $medicine->stock }}</td><td>@if($medicine->stock===0)<span class="badge text-bg-danger">Agotado</span>@elseif($medicine->stock<=10)<span class="badge text-bg-warning">Stock bajo</span>@else<span class="badge text-bg-success">Disponible</span>@endif</td></tr>
    @empty<tr><td colspan="5" class="text-center text-secondary py-3">No hay medicamentos registrados</td></tr>@endforelse
    </tbody></table></div>
</div>
@endif

@if (auth()->user()->hasRole('Administrador'))
<div class="card bg-white border-0 shadow-sm">
    <div class="card-header bg-white"><h6 class="fw-bold mb-0">Reporte de vendedores</h6></div>
    <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead><tr class="small text-uppercase"><th>Vendedor</th><th class="text-end">Ventas</th><th class="text-end">Total vendido</th><th class="text-end">Promedio</th></tr></thead><tbody>
    @foreach($sellers as $seller)<tr><td>{{ $seller->name }}</td><td class="text-end">{{ $seller->count }}</td><td class="text-end">S/ {{ number_format($seller->total,2) }}</td><td class="text-end">S/ {{ number_format($seller->average,2) }}</td></tr>@endforeach
    </tbody></table></div>
</div>
@endif
@endsection

@push('scripts')
<style>@media print{.sidebar-nav,#sidebar,.app-content>div>.card:first-child,.btn,.breadcrumb{display:none!important}.app-content{overflow:visible!important}.main-wrapper{width:100%!important}.card{break-inside:avoid;box-shadow:none!important}}</style>
@endpush
