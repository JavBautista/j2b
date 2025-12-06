 <div class="sidebar">
    <nav class="sidebar-nav">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.index') }}">
                    <i class="fa fa-home"></i> Home
                </a>
            </li>
            
            <li class="nav-item">
                <a href="{{ route('admin.tasks') }}" class="nav-link">
                    <i class="fa fa-tasks"></i> Tareas
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.receipts') }}" class="nav-link">
                    <i class="fa fa-list-alt"></i> Ventas
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.clients') }}" class="nav-link">
                    <i class="fa fa-users"></i> Clientes
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.users') }}" class="nav-link">
                    <i class="fa fa-user-circle"></i> Usuarios App
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.gastos') }}" class="nav-link">
                    <i class="fa fa-money"></i> Gastos
                </a>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-th-list"></i> Catálogos</a>
                <ul class="nav-dropdown-items">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.products') }}"><i class="fa fa-cube"></i> Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.categories') }}"><i class="fa fa-tags"></i> Categorías</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.services') }}"><i class="fa fa-wrench"></i> Servicios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.equipments') }}"><i class="fa fa-print"></i> Equipos</a>
                    </li>
                </ul>
            </li>

            {{-- Reportes: Solo para Admin Full (limited = 0) --}}
            @if(Auth::user()->isFullAdmin())
            <li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="fa fa-bar-chart"></i> Reportes</a>
                <ul class="nav-dropdown-items">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.reports') }}"><i class="fa fa-dashboard"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.reports') }}?tab=ventas"><i class="fa fa-line-chart"></i> Ventas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.reports') }}?tab=utilidad"><i class="fa fa-money"></i> Utilidades</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.reports') }}?tab=inventario"><i class="fa fa-cubes"></i> Inventario</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.reports') }}?tab=adeudos"><i class="fa fa-exclamation-circle"></i> Adeudos</a>
                    </li>
                </ul>
            </li>
            @endif

            <li class="nav-item">
                <a href="{{ route('admin.contracts') }}" class="nav-link">
                    <i class="fa fa-file-text-o"></i> Plantillas Contratos
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.shop') }}" class="nav-link">
                    <i class="fa fa-shopping-cart"></i> Info. Tienda
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.configurations') }}" class="nav-link">
                    <i class="fa fa-cogs"></i> Configuraciones
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.download') }}" class="nav-link">
                    <i class="fa fa-download"></i> Descarga
                </a>
            </li>

            <!--<li class="nav-item nav-dropdown">
                <a class="nav-link nav-dropdown-toggle" href="#"><i class="icon-bag"></i> Tiendas</a>
                <ul class="nav-dropdown-items">
                    <li class="nav-item">
                        <a class="nav-link" href="#"> <i class="fa fa-user"></i>  </a>
                    </li>
                </ul>
            </li>
            -->




        </ul>
    </nav>
    <button class="sidebar-minimizer brand-minimizer" type="button"></button>
</div>