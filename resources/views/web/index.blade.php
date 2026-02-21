@extends('web.layouts.app')

@section('title', 'J2Biznes - Control Total de tu Negocio desde tu Móvil')
@section('description', 'J2Biznes es la aplicación todo-en-uno que necesitas para manejar inventarios, clientes, ventas y más. Simplifica tu gestión empresarial con la herramienta que cabe en tu bolsillo.')

@section('content')
    <!-- Hero Section -->
    <section id="inicio" class="hero">
        <div class="hero-content fade-in-up">
            <div class="hero-badges">
                <span class="hero-badge"><i class="fas fa-shield-alt"></i> Sin costos ocultos</span>
                <span class="hero-badge"><i class="fas fa-sync"></i> Sincronización automática</span>
                <span class="hero-badge"><i class="fas fa-heart"></i> Hecho en México</span>
            </div>
            
            <h1>El punto de venta móvil que hace crecer tu negocio</h1>
            <p>1 año ayudando a pequeños y medianos negocios en México. Genera notas de venta, controla inventarios y gestiona clientes desde cualquier lugar con sincronización automática.</p>
            
            <div class="hero-buttons">
                <a href="#descarga" class="btn-primary btn-large">
                    <i class="fas fa-rocket"></i> Prueba Gratis {{ $trialDays }} días
                </a>
                <a href="#demo" class="btn-secondary">
                    <i class="fas fa-play"></i> Ver Demo
                </a>
            </div>

            <!-- App Mockup -->
            <div class="hero-mockup">
                <div class="phone-mockup">
                    <div class="phone-frame">
                        <div class="phone-screen">
                            <img 
                                src="{{ asset('img/web/j2b_screenshot.jpg') }}" 
                                alt="Captura de pantalla de J2Biznes App" 
                                class="app-screenshot"
                            >
                        </div>
                        <div class="phone-button"></div>
                    </div>
                    <div class="phone-shadow"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Problem/Solution Section -->
    <section class="problem-solution">
        <div class="container">
            <div class="section-header scroll-reveal">
                <h2>¿Cansado de perder ventas por no tener un sistema confiable?</h2>
                <p>J2Biznes resuelve los principales problemas que enfrentan los negocios mexicanos</p>
            </div>
            
            <div class="solution-grid">
                <div class="solution-card scroll-reveal">
                    <div class="solution-icon">
                        <i class="fas fa-sync"></i>
                    </div>
                    <h3>Siempre Conectado</h3>
                    <p>Mantén tu información sincronizada en tiempo real. Accede a tus datos desde cualquier dispositivo con conexión segura.</p>
                </div>
                
                <div class="solution-card scroll-reveal">
                    <div class="solution-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h3>Todo en Uno</h3>
                    <p>Punto de venta, inventarios, clientes, proveedores, reportes y más. Una sola app para todo tu negocio.</p>
                </div>
                
                <div class="solution-card scroll-reveal">
                    <div class="solution-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Desde Cualquier Lugar</h3>
                    <p>Administra tu negocio desde tu teléfono. Atiende clientes en ferias, domicilio o donde sea necesario.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <div class="section-header scroll-reveal">
                <h2>Cómo funciona J2Biznes</h2>
                <p>En solo 3 pasos puedes tener tu negocio funcionando con J2Biznes</p>
            </div>
            
            <div class="steps-grid">
                <div class="step-card scroll-reveal">
                    <div class="step-number">1</div>
                    <div class="step-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3>Descarga</h3>
                    <p>Instala J2Biznes desde Google Play o App Store. Es gratis y no requiere registro inicial.</p>
                </div>
                
                <div class="step-arrow scroll-reveal">
                    <i class="fas fa-arrow-right"></i>
                </div>
                
                <div class="step-card scroll-reveal">
                    <div class="step-number">2</div>
                    <div class="step-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <h3>Configura</h3>
                    <p>Agrega tus productos, clientes y configura tu negocio en menos de 15 minutos.</p>
                </div>
                
                <div class="step-arrow scroll-reveal">
                    <i class="fas fa-arrow-right"></i>
                </div>
                
                <div class="step-card scroll-reveal">
                    <div class="step-number">3</div>
                    <div class="step-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Vende</h3>
                    <p>Comienza a generar ventas, controlar inventarios y hacer crecer tu negocio desde el primer día.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="caracteristicas" class="features">
        <div class="container">
            <div class="section-header scroll-reveal">
                <h2>Todo lo que necesitas en una sola app</h2>
                <p>Desde inventarios hasta ventas, J2Biznes integra todas las herramientas esenciales para que puedas enfocarte en hacer crecer tu negocio.</p>
            </div>

            <div class="features-grid">
                <div class="feature-card scroll-reveal" data-feature="inventory">
                    <div class="feature-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3>Gestión de Inventarios</h3>
                    <p>Controla tu stock en tiempo real, recibe alertas de productos agotados y gestiona múltiples almacenes.</p>
                    <div class="feature-benefits">
                        <span class="benefit-tag">Control en tiempo real</span>
                        <span class="benefit-tag">Alertas automáticas</span>
                    </div>
                </div>

                <div class="feature-card scroll-reveal" data-feature="customers">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Control de Clientes</h3>
                    <p>Base de datos completa de clientes, historial de compras y preferencias para un servicio personalizado.</p>
                    <div class="feature-benefits">
                        <span class="benefit-tag">Historial completo</span>
                        <span class="benefit-tag">Servicio personalizado</span>
                    </div>
                </div>

                <div class="feature-card scroll-reveal" data-feature="suppliers">
                    <div class="feature-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3>Gestión de Proveedores</h3>
                    <p>Administra proveedores, órdenes de compra y mantén control detallado de costos y tiempos de entrega.</p>
                    <div class="feature-benefits">
                        <span class="benefit-tag">Órdenes automáticas</span>
                        <span class="benefit-tag">Control de costos</span>
                    </div>
                </div>

                <div class="feature-card scroll-reveal" data-feature="pos">
                    <div class="feature-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3>Punto de Venta Móvil</h3>
                    <p>Genera facturas, notas de venta y cotizaciones al instante. Acepta múltiples métodos de pago.</p>
                    <div class="feature-benefits">
                        <span class="benefit-tag">Facturación instantánea</span>
                        <span class="benefit-tag">Múltiples pagos</span>
                    </div>
                </div>

                <div class="feature-card scroll-reveal" data-feature="staff">
                    <div class="feature-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Control de Personal</h3>
                    <p>Administra tu equipo, asigna roles y permisos, y mantén registro de actividades para optimizar productividad.</p>
                    <div class="feature-benefits">
                        <span class="benefit-tag">Roles personalizados</span>
                        <span class="benefit-tag">Seguimiento de actividad</span>
                    </div>
                </div>

                <div class="feature-card scroll-reveal" data-feature="support">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Soporte en Español</h3>
                    <p>Atención personalizada en horario laboral mexicano, gestión de tickets y soporte técnico especializado.</p>
                    <div class="feature-benefits">
                        <span class="benefit-tag">Horario México</span>
                        <span class="benefit-tag">Respuesta rápida</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="precios" class="pricing">
        <div class="container">
            <div class="section-header scroll-reveal">
                <h2>Planes diseñados para cada tipo de negocio</h2>
                <p>Comienza con nuestra prueba gratuita y crece con nosotros. Sin compromisos, cancela cuando quieras.</p>
            </div>
            
            <div class="pricing-grid">

                <!-- Plan Básico -->
                <div class="pricing-card scroll-reveal">
                    <div class="pricing-header">
                        <h3>BÁSICO</h3>
                        <div class="price">
                            <span class="currency">MX$</span>
                            <span class="amount">499</span>
                            <span class="period">/mes</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> Productos ilimitados</li>
                        <li><i class="fas fa-check"></i> Hasta 200 clientes</li>
                        <li><i class="fas fa-check"></i> 2 usuarios admin</li>
                        <li><i class="fas fa-check"></i> Notas de venta y cotizaciones</li>
                        <li><i class="fas fa-check"></i> Control de inventario con alertas</li>
                        <li><i class="fas fa-check"></i> Gestión de proveedores y compras</li>
                        <li><i class="fas fa-check"></i> Reportes básicos</li>
                        <li><i class="fas fa-check"></i> App móvil completa</li>
                        <li><i class="fas fa-check"></i> Soporte por email</li>
                    </ul>
                    <a href="#descarga" class="pricing-btn btn-outline">Elegir Plan</a>
                </div>

                <!-- Plan PRO -->
                <div class="pricing-card pricing-card-featured scroll-reveal">
                    <div class="pricing-badge">Más Popular</div>
                    <div class="pricing-header">
                        <h3>PRO</h3>
                        <div class="price">
                            <span class="currency">MX$</span>
                            <span class="amount">999</span>
                            <span class="period">/mes</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> Todo lo del plan Básico</li>
                        <li><i class="fas fa-check"></i> Clientes ilimitados</li>
                        <li><i class="fas fa-check"></i> 5 usuarios admin + colaboradores</li>
                        <li><i class="fas fa-check"></i> Reportes avanzados (8 módulos)</li>
                        <li><i class="fas fa-check"></i> Contratos digitales con firma</li>
                        <li><i class="fas fa-check"></i> Renta de equipos</li>
                        <li><i class="fas fa-check"></i> Pagos parciales y crédito</li>
                        <li><i class="fas fa-check"></i> Plataforma WEB completa</li>
                        <li><i class="fas fa-check"></i> Exportación Excel y PDF</li>
                        <li><i class="fas fa-check"></i> Soporte prioritario</li>
                    </ul>
                    <a href="#descarga" class="pricing-btn btn-primary">Elegir Plan</a>
                </div>

                <!-- Plan Enterprise -->
                <div class="pricing-card scroll-reveal">
                    <div class="pricing-header">
                        <h3>ENTERPRISE</h3>
                        <div class="price">
                            <span class="currency">MX$</span>
                            <span class="amount">1,999</span>
                            <span class="period">/mes</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> Todo lo del plan PRO</li>
                        <li><i class="fas fa-check"></i> Usuarios ilimitados</li>
                        <li><i class="fas fa-star"></i> <strong>Asistente de Inteligencia Artificial</strong></li>
                        <li><i class="fas fa-star"></i> <strong>Facturación CFDI (timbrado SAT)</strong></li>
                        <li><i class="fas fa-star"></i> <strong>GPS tiempo real de técnicos</strong></li>
                        <li><i class="fas fa-check"></i> Órdenes de trabajo con fotos y firma</li>
                        <li><i class="fas fa-check"></i> Portal de cliente en la app</li>
                        <li><i class="fas fa-check"></i> Multi-moneda</li>
                        <li><i class="fas fa-check"></i> Soporte dedicado</li>
                    </ul>
                    <a href="#descarga" class="pricing-btn btn-outline">Elegir Plan</a>
                </div>

            </div>
        </div>
    </section>

    <!-- Stats Section with Lazy Loading -->
    <section class="stats" data-lazy-component="stats-counter">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item scroll-reveal">
                    <h3 data-target="1" data-format="years" data-duration="2000">0</h3>
                    <p>Año en el mercado</p>
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
                    <h3 data-target="1" data-format="country" data-duration="1200">0</h3>
                    <p>País utilizando J2Biznes</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonios" class="testimonials">
        <div class="container">
            <div class="section-header scroll-reveal">
                <h2>Lo que dicen nuestros clientes</h2>
                <p>Emprendedores y empresarios mexicanos ya están mejorando su forma de trabajar con J2Biznes.</p>
            </div>

            <div class="testimonial-grid">
                <div class="testimonial-card scroll-reveal">
                    <p class="testimonial-text">"J2Biznes simplificó mi negocio. Ahora puedo manejar todo desde mi teléfono y mis procesos son más eficientes."</p>
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

    <!-- FAQ Section -->
    <section id="faq" class="faq">
        <div class="container">
            <div class="section-header scroll-reveal">
                <h2>Preguntas Frecuentes</h2>
                <p>Resolvemos las dudas más comunes sobre J2Biznes</p>
            </div>
            
            <div class="faq-list">
                <div class="faq-item scroll-reveal">
                    <div class="faq-question">
                        <h3>¿J2Biznes requiere conexión a internet?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Sí, J2Biznes requiere conexión a internet para funcionar correctamente. Esto nos permite mantener tu información sincronizada en tiempo real entre todos tus dispositivos y garantizar la seguridad de tus datos.</p>
                    </div>
                </div>
                
                <div class="faq-item scroll-reveal">
                    <div class="faq-question">
                        <h3>¿En qué dispositivos puedo usar J2Biznes?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>J2Biznes está disponible para Android (versión 5.0 o superior) e iOS (versión 10.0 o superior). También funciona en tablets y próximamente estará disponible para computadoras de escritorio.</p>
                    </div>
                </div>
                
                <div class="faq-item scroll-reveal">
                    <div class="faq-question">
                        <h3>¿Qué tipo de soporte ofrecen?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Ofrecemos soporte en español por email y chat. El plan Profesional incluye soporte prioritario con tiempos de respuesta más rápidos. También tenemos tutoriales en video y documentación completa.</p>
                    </div>
                </div>
                
                <div class="faq-item scroll-reveal">
                    <div class="faq-question">
                        <h3>¿Puedo migrar mis datos de otro sistema?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Sí, ofrecemos migración gratuita de datos desde Excel, otros sistemas POS, y bases de datos. Nuestro equipo te ayuda con el proceso para que no pierdas información y puedas comenzar a usar J2Biznes inmediatamente.</p>
                    </div>
                </div>
                
                <div class="faq-item scroll-reveal">
                    <div class="faq-question">
                        <h3>¿Qué tan segura es mi información?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Tu información está protegida con encriptación SSL, respaldos automáticos regulares, y medidas de seguridad estrictas. Cumplimos con las regulaciones de privacidad mexicanas y nunca compartimos tu información con terceros.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contacto" class="contact-section">
        <div class="container">
            <div class="contact-wrapper">
                <div class="contact-info-side scroll-reveal">
                    <div class="contact-header">
                        <h2>¿Tienes preguntas?</h2>
                        <p>Estamos aquí para ayudarte. Escríbenos y te contactaremos lo antes posible.</p>
                    </div>

                    <div class="contact-features">
                        <div class="contact-feature">
                            <div class="contact-feature-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-feature-content">
                                <h4>Respuesta rápida</h4>
                                <p>Te respondemos en menos de 24 horas</p>
                            </div>
                        </div>

                        <div class="contact-feature">
                            <div class="contact-feature-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="contact-feature-content">
                                <h4>Atención personalizada</h4>
                                <p>Un asesor real te ayudará con tus dudas</p>
                            </div>
                        </div>

                        <div class="contact-feature">
                            <div class="contact-feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="contact-feature-content">
                                <h4>Sin compromiso</h4>
                                <p>Solo información, sin presión de venta</p>
                            </div>
                        </div>
                    </div>

                    <div class="contact-direct">
                        <p class="contact-direct-label">¿Prefieres contacto directo?</p>
                        <a href="https://wa.me/5214425592717" target="_blank" class="whatsapp-btn">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>

                <div class="contact-form-side scroll-reveal">
                    <form id="contactForm" class="contact-form">
                        @csrf
                        <div class="form-group">
                            <label for="contact_name">Nombre completo <span class="required">*</span></label>
                            <input type="text" id="contact_name" name="name" placeholder="Ej: Juan Pérez" required maxlength="100" minlength="3">
                            <span class="field-error" id="error_name"></span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_email">Email <span class="required">*</span></label>
                                <input type="email" id="contact_email" name="email" placeholder="tu@email.com" required maxlength="100">
                                <span class="field-error" id="error_email"></span>
                            </div>

                            <div class="form-group">
                                <label for="contact_phone">Teléfono <span class="required">*</span></label>
                                <input type="tel" id="contact_phone" name="phone" placeholder="10 dígitos" required maxlength="10" pattern="[0-9]{10}">
                                <span class="field-error" id="error_phone"></span>
                            </div>
                        </div>

                        <div class="form-group-inline">
                            <label class="checkbox-container">
                                <input type="checkbox" id="contact_is_whatsapp" name="is_whatsapp" value="1">
                                <span class="checkmark"></span>
                                <span class="checkbox-label"><i class="fab fa-whatsapp"></i> Este número tiene WhatsApp</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="contact_company">Empresa/Negocio <span class="optional">(opcional)</span></label>
                            <input type="text" id="contact_company" name="company" placeholder="Nombre de tu negocio" maxlength="100">
                            <span class="field-error" id="error_company"></span>
                        </div>

                        <div class="form-group">
                            <label for="contact_message">Mensaje <span class="required">*</span></label>
                            <textarea id="contact_message" name="message" rows="4" placeholder="¿En qué podemos ayudarte?" required minlength="10" maxlength="1000"></textarea>
                            <span class="field-error" id="error_message"></span>
                        </div>

                        <div class="form-alert form-alert-error" id="form_error" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="form_error_text">Por favor corrige los errores antes de enviar.</span>
                        </div>

                        <div class="form-alert form-alert-success" id="form_success" style="display: none;">
                            <i class="fas fa-check-circle"></i>
                            <span>¡Mensaje enviado! Te contactaremos pronto.</span>
                        </div>

                        <button type="submit" class="contact-submit-btn" id="contact_submit_btn">
                            <span class="btn-text">Enviar mensaje</span>
                            <span class="btn-icon"><i class="fas fa-paper-plane"></i></span>
                            <span class="btn-loading" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Enviando...</span>
                        </button>

                        <p class="form-disclaimer">
                            <i class="fas fa-lock"></i> Tu información está segura y no será compartida con terceros.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="descarga" class="cta-section">
        <div class="container">
            <div class="cta-content scroll-reveal">
                <h2>¿Listo para transformar tu negocio?</h2>
                <p>Únete a los emprendedores mexicanos que ya están optimizando sus negocios con J2Biznes. Descarga la app y comienza tu prueba gratuita hoy mismo.</p>
                <div class="hero-buttons">
                    {{-- Botones de descarga temporalmente ocultos hasta publicación en stores
                    <a href="#" class="btn-primary btn-large">
                        <i class="fab fa-apple"></i> Descargar para iOS
                    </a>
                    <a href="#" class="btn-primary btn-large">
                        <i class="fab fa-google-play"></i> Descargar para Android
                    </a>
                    --}}
                    <a href="{{ route('download.form') }}" class="btn-primary btn-large">
                        <i class="fas fa-download"></i> Descargar App Gratis
                    </a>
                </div>
                <p class="cta-note"><i class="fas fa-gift"></i> <strong>Prueba gratuita por {{ $trialDays }} días</strong> - Descarga la app y comienza ahora</p>
            </div>
        </div>
    </section>
@endsection