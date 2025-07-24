<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>j2biznes - Control Total de tu Negocio desde tu Móvil</title>
    <meta name="description" content="j2biznes es la app que necesitas para manejar inventarios, clientes, ventas y más. Todo desde tu teléfono con la facilidad que tu negocio merece.">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #10b981;
            --accent-color: #f59e0b;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-light: #9ca3af;
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-dark: #111827;
            --border-color: #e5e7eb;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-accent: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--bg-primary);
            overflow-x: hidden;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 1000;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: var(--primary-color); /* Fallback */
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            margin: 0;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .nav-links a.active {
            color: var(--primary-color);
        }

        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-color);
            border-radius: 1px;
        }

        .cta-button {
            background: var(--gradient-primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            display: inline-block;
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-primary);
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            background: var(--gradient-primary);
            color: white;
            padding: 8rem 2rem 6rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: white;
            color: var(--primary-color);
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-lg);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            padding: 1rem 2rem;
            border: 2px solid white;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        /* Features Section */
        .features {
            padding: 6rem 2rem;
            background: var(--bg-secondary);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-header h2 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            margin-bottom: 1rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: var(--primary-color); /* Fallback */
        }

        .section-header p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
        }

        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .feature-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .feature-card p {
            color: var(--text-secondary);
            line-height: 1.7;
        }

        /* Stats Section */
        .stats {
            padding: 4rem 2rem;
            background: var(--bg-dark);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item h3 {
            font-size: 3rem;
            font-weight: 800;
            background: var(--gradient-accent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            color: #4facfe; /* Fallback */
        }

        .stat-item p {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        /* Testimonials */
        .testimonials {
            padding: 6rem 2rem;
            background: var(--bg-primary);
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
        }

        .testimonial-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .testimonial-text {
            font-style: italic;
            margin-bottom: 1.5rem;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .author-info h4 {
            font-weight: 600;
            color: var(--text-primary);
        }

        .author-info p {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* CTA Section */
        .cta-section {
            padding: 6rem 2rem;
            background: var(--gradient-secondary);
            color: white;
            text-align: center;
        }

        .cta-content h2 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-content p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Footer */
        .footer {
            background: var(--bg-dark);
            color: white;
            padding: 3rem 2rem 1rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .footer-section h3 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: white;
        }

        .footer-section ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid #374151;
            margin-top: 2rem;
            padding-top: 1rem;
            text-align: center;
            color: #9ca3af;
        }

        .footer-bottom a {
            color: #9ca3af;
            text-decoration: none;
        }

        .footer-bottom a:hover {
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 1rem;
                box-shadow: var(--shadow-lg);
                border-top: 1px solid var(--border-color);
            }

            .nav-links.active {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero {
                padding: 6rem 2rem 4rem;
                min-height: 80vh;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .feature-card {
                padding: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .testimonial-grid {
                grid-template-columns: 1fr;
            }

            .nav {
                padding: 1rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Scroll animations */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.6s ease;
        }

        .scroll-reveal.revealed {
            opacity: 1;
            transform: translateY(0);
        }

        /* Loading state */
        body.loading * {
            animation-play-state: paused;
        }

        body.loaded .fade-in-up {
            animation-delay: 0.2s;
        }
        
        body.loaded .scroll-reveal {
            transition-delay: 0.1s;
        }
    </style>
</head>
<body class="loading">
    <!-- Header -->
    <header class="header">
        <nav class="nav">
            <div class="logo">j2biznes</div>
            <ul class="nav-links">
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#caracteristicas">Características</a></li>
                <li><a href="#testimonios">Testimonios</a></li>
                <li><a href="#contacto">Contacto</a></li>
            </ul>
            <a href="#descarga" class="cta-button">Descargar App</a>
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </nav>
    </header>

    <!-- Hero Section -->
    <section id="inicio" class="hero">
        <div class="hero-content fade-in-up">
            <h1>Control Total de tu Negocio desde tu Móvil</h1>
            <p>j2biznes es la aplicación todo-en-uno que necesitas para manejar inventarios, clientes, ventas y más. Simplifica tu gestión empresarial con la herramienta que cabe en tu bolsillo.</p>
            <div class="hero-buttons">
                <a href="#descarga" class="btn-primary">
                    <i class="fas fa-download"></i> Descargar Gratis
                </a>
                <a href="#demo" class="btn-secondary">
                    <i class="fas fa-play"></i> Ver Demo
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="caracteristicas" class="features">
        <div class="container">
            <div class="section-header scroll-reveal">
                <h2>Todo lo que necesitas en una sola app</h2>
                <p>Desde inventarios hasta ventas, j2biznes integra todas las herramientas esenciales para que puedas enfocarte en hacer crecer tu negocio.</p>
            </div>

            <div class="features-grid">
                <div class="feature-card scroll-reveal">
                    <div class="feature-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3>Gestión de Inventarios</h3>
                    <p>Controla tu stock en tiempo real, recibe alertas de productos agotados y gestiona múltiples almacenes desde cualquier lugar.</p>
                </div>

                <div class="feature-card scroll-reveal">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Control de Clientes</h3>
                    <p>Mantén una base de datos completa de tus clientes, historial de compras y preferencias para brindar un servicio personalizado.</p>
                </div>

                <div class="feature-card scroll-reveal">
                    <div class="feature-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3>Gestión de Proveedores</h3>
                    <p>Administra tus proveedores, órdenes de compra y mantén un control detallado de tus costos y tiempos de entrega.</p>
                </div>

                <div class="feature-card scroll-reveal">
                    <div class="feature-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3>Punto de Venta Móvil</h3>
                    <p>Genera facturas, notas de venta y cotizaciones al instante. Acepta diferentes métodos de pago y envía comprobantes por email.</p>
                </div>

                <div class="feature-card scroll-reveal">
                    <div class="feature-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Control de Personal</h3>
                    <p>Administra tu equipo, asigna roles y permisos, y mantén un registro de actividades para optimizar la productividad.</p>
                </div>

                <div class="feature-card scroll-reveal">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Soporte al Cliente</h3>
                    <p>Ofrece atención personalizada a tus clientes, gestiona tickets de soporte y mantén la satisfacción al máximo nivel.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item scroll-reveal">
                    <h3 data-target="10000">0</h3>
                    <p>Negocios confían en nosotros</p>
                </div>
                <div class="stat-item scroll-reveal">
                    <h3 data-target="99">0</h3>
                    <p>% de tiempo de disponibilidad</p>
                </div>
                <div class="stat-item scroll-reveal">
                    <h3 data-target="24">0</h3>
                    <p>Horas de soporte técnico</p>
                </div>
                <div class="stat-item scroll-reveal">
                    <h3 data-target="50">0</h3>
                    <p>Países utilizando j2biznes</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonios" class="testimonials">
        <div class="container">
            <div class="section-header scroll-reveal">
                <h2>Lo que dicen nuestros clientes</h2>
                <p>Miles de emprendedores y empresarios ya transformaron su forma de trabajar con j2biznes.</p>
            </div>

            <div class="testimonial-grid">
                <div class="testimonial-card scroll-reveal">
                    <p class="testimonial-text">"j2biznes revolucionó mi negocio. Ahora puedo manejar todo desde mi teléfono y mis ventas aumentaron 40% en los primeros 3 meses."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">MC</div>
                        <div class="author-info">
                            <h4>María Carmen López</h4>
                            <p>Propietaria, Boutique Fashion</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card scroll-reveal">
                    <p class="testimonial-text">"La facilidad de uso es increíble. En minutos pude configurar todo mi inventario y ahora mis empleados pueden trabajar más eficientemente."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">JS</div>
                        <div class="author-info">
                            <h4>Juan Sebastián Mora</h4>
                            <p>Gerente, TechStore Plus</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card scroll-reveal">
                    <p class="testimonial-text">"El soporte al cliente es excepcional. Siempre están disponibles para ayudarme y las actualizaciones constantes mejoran cada vez más la app."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">AR</div>
                        <div class="author-info">
                            <h4>Ana Rosa Martínez</h4>
                            <p>Fundadora, Delicias Caseras</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="descarga" class="cta-section">
        <div class="container">
            <div class="cta-content scroll-reveal">
                <h2>¿Listo para transformar tu negocio?</h2>
                <p>Únete a miles de emprendedores que ya están creciendo con j2biznes. Descarga la app y comienza tu prueba gratuita hoy mismo.</p>
                <div class="hero-buttons">
                    <a href="#" class="btn-primary">
                        <i class="fab fa-apple"></i> App Store
                    </a>
                    <a href="#" class="btn-primary">
                        <i class="fab fa-google-play"></i> Google Play
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contacto" class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>j2biznes</h3>
                <p>La solución integral para gestionar tu negocio desde cualquier lugar. Simple, potente y diseñada para emprendedores como tú.</p>
            </div>
            <div class="footer-section">
                <h3>Producto</h3>
                <ul>
                    <li><a href="#caracteristicas">Características</a></li>
                    <li><a href="#precios">Precios</a></li>
                    <li><a href="#actualizaciones">Actualizaciones</a></li>
                    <li><a href="#seguridad">Seguridad</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Soporte</h3>
                <ul>
                    <li><a href="#ayuda">Centro de Ayuda</a></li>
                    <li><a href="#contacto">Contacto</a></li>
                    <li><a href="#tutoriales">Tutoriales</a></li>
                    <li><a href="#api">API</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Empresa</h3>
                <ul>
                    <li><a href="#nosotros">Sobre Nosotros</a></li>
                    <li><a href="#blog">Blog</a></li>
                    <li><a href="#carrera">Carrera</a></li>
                    <li><a href="#prensa">Prensa</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 j2biznes. Todos los derechos reservados. | <a href="#privacidad">Política de Privacidad</a> | <a href="#terminos">Términos de Servicio</a></p>
        </div>
    </footer>

    <script>
        // Remove loading class when page loads
        window.addEventListener('load', () => {
            document.body.classList.remove('loading');
            document.body.classList.add('loaded');
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const headerHeight = document.querySelector('.header').offsetHeight;
                    const targetPosition = target.offsetTop - headerHeight;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Header scroll effect
        let lastScrollTop = 0;
        window.addEventListener('scroll', () => {
            const header = document.querySelector('.header');
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = 'none';
            }
            
            lastScrollTop = scrollTop;
        });

        // Scroll reveal animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.scroll-reveal').forEach(el => {
            observer.observe(el);
        });

        // Mobile menu functionality
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');
        let isMenuOpen = false;

        mobileMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            isMenuOpen = !isMenuOpen;
            
            if (isMenuOpen) {
                navLinks.classList.add('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-times"></i>';
            } else {
                navLinks.classList.remove('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (isMenuOpen && !e.target.closest('.nav')) {
                navLinks.classList.remove('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                isMenuOpen = false;
            }
        });

        // Close mobile menu when clicking a link
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                isMenuOpen = false;
            });
        });

        // Counter animation for stats
        const animateCounters = () => {
            const counters = document.querySelectorAll('.stat-item h3[data-target]');
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                let current = 0;
                const increment = target / 100;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        if (target === 10000) {
                            counter.textContent = '10,000+';
                        } else if (target === 99) {
                            counter.textContent = '99.9%';
                        } else if (target === 24) {
                            counter.textContent = '24/7';
                        } else if (target === 50) {
                            counter.textContent = '50+';
                        }
                        clearInterval(timer);
                    } else {
                        if (target === 10000) {
                            counter.textContent = Math.floor(current).toLocaleString() + '+';
                        } else if (target === 99) {
                            counter.textContent = Math.floor(current) + '%';
                        } else if (target === 24) {
                            counter.textContent = Math.floor(current);
                        } else if (target === 50) {
                            counter.textContent = Math.floor(current) + '+';
                        }
                    }
                }, 50);
            });
        };

        // Trigger counter animation when stats section is visible
        const statsSection = document.querySelector('.stats');
        let statsAnimated = false;
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !statsAnimated) {
                    animateCounters();
                    statsAnimated = true;
                }
            });
        }, { threshold: 0.5 });

        if (statsSection) {
            statsObserver.observe(statsSection);
        }

        // Add intersection observer for navbar highlighting
        const sections = document.querySelectorAll('section[id]');
        const navItems = document.querySelectorAll('.nav-links a');

        const highlightNavigation = () => {
            let current = '';
            const scrollPosition = window.pageYOffset + 200;
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    current = section.getAttribute('id');
                }
            });

            navItems.forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('href') === `#${current}`) {
                    item.classList.add('active');
                }
            });
        };

        window.addEventListener('scroll', highlightNavigation);

        // Parallax effect for hero section (subtle)
        let ticking = false;
        const updateParallax = () => {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            
            if (hero && scrolled < hero.offsetHeight) {
                const heroContent = hero.querySelector('.hero-content');
                if (heroContent) {
                    heroContent.style.transform = `translateY(${scrolled * 0.1}px)`;
                }
            }
            
            ticking = false;
        };

        const requestParallaxUpdate = () => {
            if (!ticking) {
                requestAnimationFrame(updateParallax);
                ticking = true;
            }
        };

        window.addEventListener('scroll', requestParallaxUpdate);

        // Enhanced hover effects for feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-12px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Enhanced hover effects for testimonial cards
        document.querySelectorAll('.testimonial-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
                this.style.borderLeftWidth = '6px';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.borderLeftWidth = '4px';
            });
        });

        // Improved loading experience
        const preloadResources = () => {
            // Preload critical fonts
            const fontLink = document.createElement('link');
            fontLink.rel = 'preload';
            fontLink.as = 'font';
            fontLink.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap';
            fontLink.crossOrigin = 'anonymous';
            document.head.appendChild(fontLink);

            // Preload Font Awesome
            const iconLink = document.createElement('link');
            iconLink.rel = 'preload';
            iconLink.as = 'style';
            iconLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
            iconLink.crossOrigin = 'anonymous';
            document.head.appendChild(iconLink);
        };

        // Initialize preloading
        preloadResources();

        // Smooth reveal animation stagger
        document.querySelectorAll('.features-grid .feature-card').forEach((card, index) => {
            card.style.transitionDelay = `${index * 0.1}s`;
        });

        document.querySelectorAll('.testimonial-grid .testimonial-card').forEach((card, index) => {
            card.style.transitionDelay = `${index * 0.15}s`;
        });

        // Error handling for external resources
        const handleResourceError = (resource, fallback) => {
            resource.addEventListener('error', () => {
                console.warn(`Failed to load ${resource.href || resource.src}, using fallback`);
                if (fallback) fallback();
            });
        };

        // Add error handling for font loading
        document.fonts.ready.then(() => {
            document.body.classList.add('fonts-loaded');
        }).catch(() => {
            console.warn('Font loading failed, using system fonts');
            document.body.style.fontFamily = 'system-ui, -apple-system, sans-serif';
        });

        // Performance optimization: debounced scroll handler
        const debounce = (func, wait) => {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        };

        // Apply debouncing to scroll-heavy functions
        const debouncedHighlight = debounce(highlightNavigation, 10);
        window.removeEventListener('scroll', highlightNavigation);
        window.addEventListener('scroll', debouncedHighlight);

        // Accessibility improvements
        document.addEventListener('keydown', (e) => {
            // ESC key closes mobile menu
            if (e.key === 'Escape' && isMenuOpen) {
                navLinks.classList.remove('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                isMenuOpen = false;
            }
        });

        // Focus management for mobile menu
        mobileMenuBtn.addEventListener('focus', () => {
            mobileMenuBtn.style.outline = '2px solid var(--primary-color)';
        });

        mobileMenuBtn.addEventListener('blur', () => {
            mobileMenuBtn.style.outline = 'none';
        });

        // Console log for debugging
        console.log('j2biznes landing page loaded successfully');
        console.log('All animations and interactions initialized');
    </script>
</body>
</html>