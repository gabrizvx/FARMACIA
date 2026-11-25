@extends('template.administrador')

@section('title', 'Dashboard')
@section('name_section', 'Dashboard')
@section('description', 'Resumen operativo y estadísticas en tiempo real')
@section('item_section', 'Inicio')
@section('route_section', route('dashboard.index'))
@section('section', 'Dashboard')
@section('module', 'Principal')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <span class="text-secondary small">Información actualizada al {{ now()->format('d/m/Y H:i') }}</span>
    </div>
    <form method="GET" class="d-flex align-items-center gap-2">
        <label for="days" class="small text-secondary">Ventas por día</label>
        <select name="days" id="days" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="7" @selected($dias === 7)>Últimos 7 días</option>
            <option value="30" @selected($dias === 30)>Últimos 30 días</option>
        </select>
    </form>
</div>

<div class="row g-3 mb-4">
    @php
        $cards = [
            ['Ventas de hoy', 'S/ ' . number_format($totalVentasHoy, 2), 'count' => $cantidadVentasHoy . ' operaciones', 'icon' => 'cart-check', 'color' => 'primary'],
            ['Ingresos del mes', 'S/ ' . number_format($ingresosMes, 2), 'count' => $comparacionMes === null ? 'Sin periodo anterior' : (($comparacionMes >= 0 ? '+' : '') . number_format($comparacionMes, 1) . '% vs mes anterior'), 'icon' => 'cash-stack', 'color' => 'success'],
            ['Clientes registrados', $clientes, 'count' => 'Base de clientes', 'icon' => 'people', 'color' => 'info'],
            ['Medicamentos', $medicamentos, 'count' => $stockBajo . ' con stock bajo · ' . $agotados . ' agotados', 'icon' => 'capsule', 'color' => 'warning'],
        ];
    @endphp
    @foreach ($cards as $card)
        @if (auth()->user()->hasRole('Administrador') || !auth()->user()->hasRole('Químico farmacéutico') || in_array($card[0], ['Medicamentos']))
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card bg-white border-0 shadow-sm border-5 border-top border-{{ $card['color'] }} h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small text-uppercase fw-semibold">{{ $card[0] }}</div>
                        <div class="fs-4 fw-bold text-dark">{{ $card[1] }}</div>
                        <div class="small text-secondary">{{ $card['count'] }}</div>
                    </div>
                    <div class="rounded-circle bg-{{ $card['color'] }}-subtle text-{{ $card['color'] }} d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                        <i class="bi bi-{{ $card['icon'] }} fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endforeach
</div>

<div class="row g-3 mb-4">
    @if (!auth()->user()->hasRole('Químico farmacéutico'))
    <div class="col-12 col-xl-7">
        <div class="card bg-white border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold mb-0">Ingresos por día</h6></div>
            <div class="card-body"><canvas id="dailySalesChart" height="230"></canvas></div>
        </div>
    </div>
    <div class="col-12 col-xl-5">
        <div class="card bg-white border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold mb-0">Ventas por categoría</h6></div>
            <div class="card-body"><canvas id="categoryChart" height="230"></canvas></div>
        </div>
    </div>
    @endif
</div>

<div class="row g-3 mb-4">
    @if (!auth()->user()->hasRole('Químico farmacéutico'))
    <div class="col-12 col-xl-7">
        <div class="card bg-white border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold mb-0">Ingresos mensuales</h6></div>
            <div class="card-body"><canvas id="monthlySalesChart" height="220"></canvas></div>
        </div>
    </div>
    @endif
    <div class="col-12 {{ auth()->user()->hasRole('Químico farmacéutico') ? 'col-xl-12' : 'col-xl-5' }}">
        <div class="card bg-white border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold mb-0">Productos más vendidos</h6></div>
            <div class="card-body"><canvas id="topProductsChart" height="220"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card bg-white border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center"><h6 class="fw-bold mb-0">Actividad reciente</h6><a href="{{ route('sales.index') }}" class="small text-decoration-none">Ver ventas</a></div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr class="small text-uppercase text-secondary"><th>Código</th><th>Cliente</th><th>Vendedor</th><th>Fecha</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                @forelse ($ultimasVentas as $sale)
                    <tr><td><a href="{{ route('sales.show', $sale) }}" class="text-decoration-none fw-semibold">#{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</a></td><td>{{ $sale->customer->name }}</td><td>{{ $sale->user->name }}</td><td>{{ $sale->date->format('d/m/Y H:i') }}</td><td class="text-end fw-semibold">S/ {{ number_format($sale->total, 2) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">No hay ventas registradas</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="col-12 col-xl-5">
        <div class="card bg-white border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center"><h6 class="fw-bold mb-0">Alertas de inventario</h6><a href="{{ route('medicines.index', ['low_stock' => 1]) }}" class="small text-decoration-none">Gestionar</a></div>
            <div class="list-group list-group-flush">
            @forelse ($alertasStock as $medicine)
                <a href="{{ route('medicines.edit', $medicine) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div><div class="fw-semibold small">{{ $medicine->name }}</div><div class="text-secondary small">{{ $medicine->category->name }}</div></div>
                    @if ($medicine->stock === 0)<span class="badge text-bg-danger">Agotado</span>@else<span class="badge text-bg-warning">{{ $medicine->stock }} unidades</span>@endif
                </a>
            @empty
                <div class="list-group-item text-center text-secondary py-4">Sin alertas de stock</div>
            @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { boxWidth: 12 } } } };

@if (!auth()->user()->hasRole('Químico farmacéutico'))
new Chart(document.getElementById('dailySalesChart'), { type: 'line', data: { labels: @json($lineLabels), datasets: [{ label: 'Ingresos (S/)', data: @json($lineData), borderColor: '#ff0a41', backgroundColor: 'rgba(255,10,65,.12)', fill: true, tension: .35 }] }, options: chartOptions });
new Chart(document.getElementById('categoryChart'), { type: 'doughnut', data: { labels: @json($porCategoria->pluck('name')->all()), datasets: [{ data: @json($porCategoria->pluck('total')->map(fn($v)=>(float)$v)->all()), backgroundColor: ['#ff0a41','#111827','#3b82f6','#10b981','#f59e0b','#8b5cf6','#64748b','#ec4899'] }] }, options: chartOptions });
new Chart(document.getElementById('monthlySalesChart'), { type: 'bar', data: { labels: @json($monthLabels), datasets: [{ label: 'Ingresos (S/)', data: @json($monthData), backgroundColor: '#ff0a41', borderRadius: 6 }] }, options: chartOptions });
@endif
new Chart(document.getElementById('topProductsChart'), { type: 'bar', data: { labels: @json($masVendidos->pluck('name')->all()), datasets: [{ label: 'Unidades vendidas', data: @json($masVendidos->pluck('amount')->map(fn($v)=>(int)$v)->all()), backgroundColor: '#3b82f6', borderRadius: 6 }] }, options: { ...chartOptions, indexAxis: 'y' } });
</script>
@endpush