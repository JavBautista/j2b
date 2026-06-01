@extends('web.layouts.app')

@section('title', 'J2Doctor - Soluciones reales de copiadoras para técnicos | Ecosistema J2')
@section('description', 'J2Doctor es la comunidad con IA para técnicos de copiadoras: captura un caso, la IA lo procesa y te muestra cómo otros técnicos ya lo resolvieron. Parte del ecosistema J2.')

@section('content')
    <!-- Hero J2Doctor -->
    <section class="j2d-page-hero">
        <div class="container">
            <span class="ecosystem-eyebrow"><i class="fas fa-layer-group"></i> Ecosistema J2 · Producto hermano de J2Biznes</span>

            <div class="j2d-page-logo">
                <img src="{{ asset('img/j2b_60px.png') }}" alt="J2Doctor" class="j2doctor-logo-img">
                <span class="j2doctor-logo-text">J2<span class="j2d-accent">Doctor</span></span>
            </div>

            <h1>Encuentra soluciones a problemas <span class="j2d-accent">reales</span> de copiadoras</h1>
            <p class="j2d-page-lead">Captura un caso, la IA lo procesa y te muestra cómo otros técnicos ya lo resolvieron. Construye tu reputación validando soluciones.</p>

            <div class="j2doctor-actions j2d-actions-center">
                <a href="https://j2doctor.com" target="_blank" rel="noopener" class="btn-j2d-primary">
                    <i class="fas fa-arrow-up-right-from-square"></i> Entrar a J2Doctor
                </a>
                <a href="{{ url('/') }}#ecosistema" class="btn-j2d-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a J2Biznes
                </a>
            </div>
        </div>
    </section>

    <!-- Para quién es -->
    <section class="j2d-section">
        <div class="container">
            <div class="section-header">
                <h2>¿Para quién es <span class="j2d-accent">J2Doctor</span>?</h2>
                <p>Pensado para <strong>técnicos de copiadoras e impresoras</strong> que quieren resolver fallas más rápido apoyándose en la experiencia de toda la comunidad.</p>
            </div>
        </div>
    </section>

    <!-- Cómo funciona -->
    <section class="j2d-section j2d-section-alt">
        <div class="container">
            <div class="section-header">
                <h2>Cómo funciona</h2>
                <p>De la falla a la solución validada, en tres pasos</p>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number j2d-step-number">1</div>
                    <div class="step-icon j2d-step-icon"><i class="fas fa-clipboard-list"></i></div>
                    <h3>Captura el caso</h3>
                    <p>Describe la falla de la copiadora desde el móvil. Sin formatos complicados.</p>
                </div>
                <div class="step-arrow"><i class="fas fa-arrow-right"></i></div>
                <div class="step-card">
                    <div class="step-number j2d-step-number">2</div>
                    <div class="step-icon j2d-step-icon"><i class="fas fa-robot"></i></div>
                    <h3>La IA lo procesa</h3>
                    <p>Normaliza el caso y lo agrupa con problemas similares ya reportados por otros técnicos.</p>
                </div>
                <div class="step-arrow"><i class="fas fa-arrow-right"></i></div>
                <div class="step-card">
                    <div class="step-number j2d-step-number">3</div>
                    <div class="step-icon j2d-step-icon"><i class="fas fa-check-double"></i></div>
                    <h3>Aplica y valida</h3>
                    <p>Usa la solución mejor rankeada y marca si te funcionó. Tu validación ayuda a los demás.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Beneficios -->
    <section class="j2d-section">
        <div class="container">
            <div class="section-header">
                <h2>Por qué usarlo</h2>
            </div>
            <div class="j2doctor-benefits">
                <div class="j2doctor-benefit">
                    <i class="fas fa-bolt"></i>
                    <h4>Resuelve más rápido</h4>
                    <p>Llega a la solución probada sin reinventar el diagnóstico en cada visita.</p>
                </div>
                <div class="j2doctor-benefit">
                    <i class="fas fa-users"></i>
                    <h4>Conocimiento colaborativo</h4>
                    <p>La experiencia de toda la comunidad de técnicos, en un solo lugar.</p>
                </div>
                <div class="j2doctor-benefit">
                    <i class="fas fa-medal"></i>
                    <h4>Reputación que suma</h4>
                    <p>Cada solución que validas construye tu prestigio como técnico.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA final -->
    <section class="j2d-cta">
        <div class="container">
            <h2>¿List@ para resolver con la comunidad?</h2>
            <p>Crea tu cuenta gratis en J2Doctor y empieza a capturar y resolver casos hoy.</p>
            <div class="j2doctor-actions j2d-actions-center">
                <a href="https://j2doctor.com" target="_blank" rel="noopener" class="btn-j2d-primary">
                    <i class="fas fa-arrow-up-right-from-square"></i> Entrar a J2Doctor
                </a>
            </div>
            <p class="j2d-cta-note">J2Doctor es parte del ecosistema J2, junto con J2Biznes.</p>
        </div>
    </section>
@endsection
