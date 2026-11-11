<!doctype html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="San Juan – Sistema de Venta de Medicamentos">
  <title>FARM SJB | @yield('title')</title>
<link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}">
<link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>

<body>

<div id="app-wrapper" class="d-flex">

<!-- SIDEBAR -->
  @include('partials.sidebar')
  <div class="main-wrapper d-flex flex-column flex-grow-1 overflow-hidden">
<!-- header -->
 @include('partials.header')




    <main class="app-content flex-grow-1 overflow-y-auto p-3 p-lg-4">
      <div class="container-fluid p-0">
        <div class="d-flex align-items-center justify-content-between flex-column flex-sm-row gap-2 mb-4">

          <p class="text-secondary mb-0">@yield('description')</p>

          <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);" aria-label="Ruta de navegación">
            <ol class="breadcrumb mb-0 small">
              <li class="breadcrumb-item">
                  <span>@yield('item-breadcrumb')</span>
              </li>
               <li class="breadcrumb-item text-muted fw-medium" aria-current="page">
                <a href="" class="text-decoration-none text-secondary d-flex align-items-center gap-1">
          @yield('section-breadcrumb')
                </a>
              </li>
              <li class="breadcrumb-item">
                <span class="text-secondary">@yield('module-breadcrumb')</span>
              </li>
            </ol>
          </nav>
        </div>


        <!-- 
            CONTENIDO DE LA VISTA
            Aquí va el contenido específico de cada página
            (KPIs, gráficos, tablas, formularios, etc.)
            En Laravel: 
        -->
            @yield('content')

      </div>
    </main>

  </div>
</div>

<!-- MODAL — Confirmar Cierre de Sesión -->
  @include('partials.modal-logout')
<div class="modal fade" id="logoutModal" tabindex="-1"
     aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">

      <div class="modal-body text-center p-4">
        <div class="rounded-circle bg-danger-subtle d-inline-flex align-items-center justify-content-center mb-3"
             style="width:62px;height:62px">
          <i class="bi bi-box-arrow-right text-danger fs-3"></i>
        </div>
        <h6 class="fw-bold mb-2" id="logoutModalLabel">¿Cerrar sesión?</h6>
        <p class="text-secondary small mb-0">¿Estás seguro de que deseas salir del sistema?</p>
      </div>

      <div class="modal-footer border-top justify-content-center py-3 gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary px-4"
                data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-sm btn-danger px-4" id="confirmLogoutBtn">
          <i class="bi bi-box-arrow-right me-1"></i>Salir
        </button>
      </div>

    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>


</body>
</html>
