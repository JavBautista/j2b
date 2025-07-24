@extends('web.layouts.app')

@section('title', 'j2biznes - Control Total de tu Negocio desde tu Móvil')
@section('description', 'j2biznes es la aplicación todo-en-uno que necesitas para manejar inventarios, clientes, ventas y más. Simplifica tu gestión empresarial con la herramienta que cabe en tu bolsillo.')

@section('content')
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

            <!-- Hero Screenshot with Lazy Loading -->
            <div class="hero-screenshot" style="margin-top: 3rem;">
                <img 
                    data-src="https://via.placeholder.com/600x400/667eea/ffffff?text=j2biznes+App+Screenshot" 
                    data-critical="true"
                    alt="Captura de pantalla de j2biznes App" 
                    class="lazy-placeholder"
                    style="border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); max-width: 100%; height: auto;"
                >
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
                    <img 
                        data-src="https://via.placeholder.com/300x200/10b981/ffffff?text=Inventarios" 
                        alt="Gestión de Inventarios"
                        class="lazy-placeholder feature-image"
                        style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-top: 1rem;"
                    >
                </div>

                <div class="feature-card scroll-reveal">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Control de Clientes</h3>
                    <p>Mantén una base de datos completa de tus clientes, historial de compras y preferencias para brindar un servicio personalizado.</p>
                    <img 
                        data-src="https://via.placeholder.com/300x200/6366f1/ffffff?text=Clientes" 
                        alt="Control de Clientes"
                        class="lazy-placeholder feature-image"
                        style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-top: 1rem;"
                    >
                </div>

                <div class="feature-card scroll-reveal">
                    <div class="feature-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3>Gestión de Proveedores</h3>
                    <p>Administra tus proveedores, órdenes de compra y mantén un control detallado de tus costos y tiempos de entrega.</p>
                    <img 
                        data-src="https://via.placeholder.com/300x200/f59e0b/ffffff?text=Proveedores" 
                        alt="Gestión de Proveedores"
                        class="lazy-placeholder feature-image"
                        style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-top: 1rem;"
                    >
                </div>

                <div class="feature-card scroll-reveal">
                    <div class="feature-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3>Punto de Venta Móvil</h3>
                    <p>Genera facturas, notas de venta y cotizaciones al instante. Acepta diferentes métodos de pago y envía comprobantes por email.</p>
                    <img 
                        data-src="https://via.placeholder.com/300x200/ef4444/ffffff?text=POS+M%C3%B3vil" 
                        alt="Punto de Venta Móvil"
                        class="lazy-placeholder feature-image"
                        style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-top: 1rem;"
                    >
                </div>

                <div class="feature-card scroll-reveal">
                    <div class="feature-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Control de Personal</h3>
                    <p>Administra tu equipo, asigna roles y permisos, y mantén un registro de actividades para optimizar la productividad.</p>
                    <img 
                        data-src="https://via.placeholder.com/300x200/8b5cf6/ffffff?text=Personal" 
                        alt="Control de Personal"
                        class="lazy-placeholder feature-image"
                        style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-top: 1rem;"
                    >
                </div>

                <div class="feature-card scroll-reveal">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Soporte al Cliente</h3>
                    <p>Ofrece atención personalizada a tus clientes, gestiona tickets de soporte y mantén la satisfacción al máximo nivel.</p>
                    <img 
                        data-src="https://via.placeholder.com/300x200/06b6d4/ffffff?text=Soporte" 
                        alt="Soporte al Cliente"
                        class="lazy-placeholder feature-image"
                        style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-top: 1rem;"
                    >
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section with Lazy Loading -->
    <section class="stats" data-lazy-component="stats-counter">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item scroll-reveal">
                    <h3 data-target="10000" data-format="thousands" data-duration="2000">0</h3>
                    <p>Negocios confían en nosotros</p>
                </div>
                <div class="stat-item scroll-reveal">
                    <h3 data-target="99" data-format="percentage" data-duration="1800">0</h3>
                    <p>% de tiempo de disponibilidad</p>
                </div>
                <div class="stat-item scroll-reveal">
                    <h3 data-target="24" data-format="time" data-duration="1500">0</h3>
                    <p>Horas de soporte técnico</p>
                </div>
                <div class="stat-item scroll-reveal">
                    <h3 data-target="50" data-format="countries" data-duration="1200">0</h3>
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
@endsection