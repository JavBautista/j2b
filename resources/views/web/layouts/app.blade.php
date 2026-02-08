<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <title>@yield('title', 'j2biznes - Control Total de tu Negocio desde tu Móvil')</title>
    <meta name="description" content="@yield('description', 'j2biznes es la app que necesitas para manejar inventarios, clientes, ventas y más. Todo desde tu teléfono con la facilidad que tu negocio merece.')">
    <meta name="keywords" content="@yield('keywords', 'gestión empresarial, inventarios, punto de venta, POS móvil, control de clientes, j2biznes')">
    <meta name="author" content="j2biznes">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('og_title', 'j2biznes - Control Total de tu Negocio desde tu Móvil')">
    <meta property="og:description" content="@yield('og_description', 'j2biznes es la aplicación todo-en-uno que necesitas para manejar inventarios, clientes, ventas y más.')">
    <meta property="og:image" content="@yield('og_image', asset('images/web/og-image.jpg'))">
    <meta property="og:url" content="{{ url()->current() }}">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'j2biznes - Control Total de tu Negocio')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Simplifica tu gestión empresarial con la herramienta que cabe en tu bolsillo.')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('images/web/twitter-image.jpg'))">
    
    <!-- Favicon and Icons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#00ff88">
    <meta name="msapplication-TileColor" content="#1a1a2e">
    <meta name="msapplication-config" content="{{ asset('browserconfig.xml') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Load CSS normally (not preload) to ensure it loads -->
    <link href="{{ mix('css/web.css') }}" rel="stylesheet">
    
    <!-- Lazy load Font Awesome after main content -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'" crossorigin="anonymous">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous"></noscript>
    
    @stack('styles')
    
    <!-- DNS Prefetch for external resources -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    
    <!-- Additional Head Content -->
    @stack('head')
</head>
<body class="@yield('body_class')">
    <!-- Header -->
    <header class="header">
        <nav class="nav">
            <div class="logo">
                <a href="{{ url('/') }}" class="logo-link">
                    <img src="{{ asset('img/j2b_60px.png') }}" alt="J2Biznes Logo" class="logo-image">
                    <span class="logo-text">J2Biznes</span>
                </a>
            </div>
            
            <ul class="nav-links">
                <li><a href="#inicio" class="nav-link">Inicio</a></li>
                <li><a href="#caracteristicas" class="nav-link">Características</a></li>
                <li><a href="#precios" class="nav-link">Precios</a></li>
                <li><a href="#testimonios" class="nav-link">Testimonios</a></li>
                <li><a href="#faq" class="nav-link">FAQ</a></li>
                <li><a href="#contacto" class="nav-link">Contacto</a></li>
            </ul>
            
            <div class="header-actions">
                @guest
                    <a href="{{ route('login') }}" class="login-btn">Iniciar Sesión</a>
                    <a href="#descarga" class="cta-button">Prueba Gratis</a>
                @else
                    @if(auth()->user()->hasRole('superadmin'))
                        <a href="{{ url('/superadmin') }}" class="dashboard-btn">Dashboard</a>
                    @elseif(auth()->user()->hasRole('admin'))
                        <a href="{{ url('/client') }}" class="dashboard-btn">Dashboard</a>
                    @endif
                    
                    <div class="user-menu">
                        <button class="user-menu-btn">
                            <i class="fas fa-user-circle"></i>
                            {{ auth()->user()->name }}
                        </button>
                        <div class="user-menu-dropdown">
                            <a href="{{ route('password.reset') }}">Cambiar Contraseña</a>
                            <a href="{{ route('logout') }}" 
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Cerrar Sesión
                            </a>
                        </div>
                    </div>
                    
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                @endguest
            </div>
            
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer id="contacto" class="footer">
        <div class="footer-content">
            <div class="footer-section footer-brand">
                <div class="footer-logo">
                    <img src="{{ asset('img/j2b_60px.png') }}" alt="J2Biznes Logo" class="footer-logo-image">
                    <div class="footer-logo-content">
                        <h3>J2Biznes</h3>
                        <div class="footer-badge">
                            <span>Hecho con ❤️ en México</span>
                        </div>
                    </div>
                </div>
                <p>La solución integral para gestionar tu negocio desde cualquier lugar. Simple, potente y diseñada para emprendedores mexicanos.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/j2biznes/" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Producto</h3>
                <ul>
                    <li><a href="#caracteristicas">Características</a></li>
                    <li><a href="#precios">Precios</a></li>
                    <li><a href="#faq">Preguntas Frecuentes</a></li>
                    <li><a href="#actualizaciones">Novedades</a></li>
                    <li><a href="#seguridad">Seguridad</a></li>
                    <li><a href="#integraciones">Integraciones</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Soporte</h3>
                <ul>
                    <li><a href="#ayuda">Centro de Ayuda</a></li>
                    <li><a href="#contacto">Contacto</a></li>
                    <li><a href="#tutoriales">Tutoriales</a></li>
                    <li><a href="#documentacion">Documentación</a></li>
                    <li><a href="#api">API para Desarrolladores</a></li>
                    <li><a href="#estado">Estado del Servicio</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Empresa</h3>
                <ul>
                    <li><a href="#nosotros">Sobre Nosotros</a></li>
                    <li><a href="#blog">Blog</a></li>
                    <li><a href="#carrera">Trabaja con Nosotros</a></li>
                    <li><a href="#prensa">Prensa</a></li>
                    <li><a href="#partners">Partners</a></li>
                    <li><a href="#afiliados">Programa de Afiliados</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Contacto</h3>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:contacto@j2biznes.com">contacto@j2biznes.com</a>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <a href="tel:+524425592717">+52 442 559 2717</a>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>México</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <span>Lun–Sáb | 9:00–18:00 h (MX)</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <div class="footer-copyright">
                    <p>&copy; {{ date('Y') }} J2Biznes. Todos los derechos reservados.</p>
                </div>
                <div class="footer-links">
                    <a href="{{ route('legal.privacy') }}">Política de Privacidad</a>
                    <a href="{{ route('legal.terms') }}">Términos de Servicio</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts with cache busting -->
    <script src="{{ mix('js/web.js') }}" defer></script>
    @stack('scripts')
    
    <!-- Additional Body Content -->
    @stack('body')
</body>
</html>