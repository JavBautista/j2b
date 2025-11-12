 <div class="sidebar">
    <nav class="sidebar-nav">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('superadmin.index') }}"><i class="icon-home"></i> Home</a>
            </li>
            <li class="nav-item">
                <a href=" {{ route('superadmin.shops') }} " class="nav-link"> Tiendas </a>
            </li>
            <li class="nav-item">
                <a href=" {{ route('superadmin.users') }} " class="nav-link"> Usuario </a>
            </li>
            <li class="nav-item">
                <a href=" {{ route('superadmin.plans') }} " class="nav-link"> Planes </a>
            </li>
            <li class="nav-item">
                <a href=" {{ route('superadmin.subscription-settings') }} " class="nav-link">
                    <i class="fa fa-cog"></i> Configuración Suscripciones
                </a>
            </li>
            <li class="nav-item">
                <a href=" {{ route('superadmin.subscription-management') }} " class="nav-link">
                    <i class="fa fa-credit-card"></i> Gestión Suscripciones
                </a>
            </li>
            <li class="nav-item">
                <a href=" {{ route('superadmin.upload_apk') }} " class="nav-link"> Upload APK </a>
            </li>

            <li class="nav-item">
                <a href=" {{ route('superadmin.pre-registers') }} " class="nav-link"> Pre Registros </a>
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