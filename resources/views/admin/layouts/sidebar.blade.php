 <div class="sidebar">
    <nav class="sidebar-nav">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.index') }}">
                    <i class="fa fa-home"></i> Home
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.shop') }}" class="nav-link">
                    <i class="fa fa-shopping-cart"></i> Mi Tienda
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
            
            <li class="nav-item">
                <a href="{{ route('admin.clients') }}" class="nav-link">
                    <i class="fa fa-users"></i> Clientes
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.contracts') }}" class="nav-link">
                    <i class="fa fa-file-text-o"></i> Plantillas Contratos
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