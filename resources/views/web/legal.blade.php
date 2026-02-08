@extends('web.layouts.app')

@php
    $isTerms = $type === 'terms';
    $pageTitle = $isTerms ? 'Términos y Condiciones' : 'Aviso de Privacidad';
@endphp

@section('title', $pageTitle . ' - J2Biznes')
@section('description', $pageTitle . ' de la plataforma J2Biznes.')

@section('content')
    <section class="legal-section">
        <div class="container">
            <div class="legal-wrapper">
                @if($document)
                    <div class="legal-header">
                        <h1>{{ $document->title }}</h1>
                        <div class="legal-meta">
                            @if($document->version)
                                <span><i class="fas fa-tag"></i> Versión {{ $document->version }}</span>
                            @endif
                            @if($document->effective_date)
                                <span><i class="fas fa-calendar-alt"></i> Vigente desde: {{ $document->effective_date->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="legal-content">
                        {!! $document->content !!}
                    </div>
                @else
                    <div class="legal-empty">
                        <i class="fas fa-file-alt"></i>
                        <h2>Documento no disponible</h2>
                        <p>Este documento aún no ha sido publicado. Por favor, vuelve más tarde.</p>
                        <a href="{{ url('/') }}" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Volver al inicio
                        </a>
                    </div>
                @endif

                @if($document)
                    <div class="legal-footer">
                        <a href="{{ url('/') }}" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Volver al inicio
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
.legal-section {
    min-height: calc(100vh - 80px);
    padding: 120px 0 60px;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
}

.legal-wrapper {
    max-width: 860px;
    margin: 0 auto;
}

.legal-header {
    margin-bottom: 40px;
    text-align: center;
}

.legal-header h1 {
    font-size: 2.2rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 15px;
}

.legal-meta {
    display: flex;
    justify-content: center;
    gap: 25px;
    flex-wrap: wrap;
}

.legal-meta span {
    color: rgba(255,255,255,0.6);
    font-size: 0.9rem;
}

.legal-meta span i {
    color: #00ff88;
    margin-right: 6px;
}

.legal-content {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 40px;
    color: rgba(255,255,255,0.85);
    line-height: 1.8;
    font-size: 0.95rem;
}

.legal-content h1,
.legal-content h2,
.legal-content h3 {
    color: #fff;
    margin-top: 30px;
    margin-bottom: 15px;
}

.legal-content h1 { font-size: 1.6rem; }
.legal-content h2 { font-size: 1.3rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
.legal-content h3 { font-size: 1.1rem; }

.legal-content p {
    margin-bottom: 15px;
}

.legal-content ul,
.legal-content ol {
    margin-bottom: 15px;
    padding-left: 25px;
}

.legal-content li {
    margin-bottom: 8px;
}

.legal-content strong {
    color: #fff;
}

.legal-content a {
    color: #00ff88;
    text-decoration: underline;
}

.legal-empty {
    text-align: center;
    padding: 80px 20px;
}

.legal-empty i {
    font-size: 4rem;
    color: rgba(255,255,255,0.2);
    margin-bottom: 20px;
}

.legal-empty h2 {
    color: #fff;
    font-size: 1.5rem;
    margin-bottom: 10px;
}

.legal-empty p {
    color: rgba(255,255,255,0.6);
    margin-bottom: 30px;
}

.legal-footer {
    margin-top: 40px;
    text-align: center;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #00ff88;
    text-decoration: none;
    font-weight: 500;
    padding: 10px 24px;
    border: 1px solid rgba(0,255,136,0.3);
    border-radius: 8px;
    transition: all 0.3s;
}

.btn-back:hover {
    background: rgba(0,255,136,0.1);
    border-color: #00ff88;
    color: #00ff88;
}

@media (max-width: 768px) {
    .legal-section {
        padding: 100px 0 40px;
    }

    .legal-header h1 {
        font-size: 1.6rem;
    }

    .legal-content {
        padding: 25px 20px;
        font-size: 0.9rem;
    }
}
</style>
@endpush
