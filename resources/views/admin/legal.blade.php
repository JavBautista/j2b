@extends('admin.layouts.app')

@php
    $isTerms = $type === 'terms';
    $pageTitle = $isTerms ? 'Términos y Condiciones' : 'Aviso de Privacidad';
    $pageIcon = $isTerms ? 'fa-file-contract' : 'fa-shield-alt';
@endphp

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center">
                <div>
                    <h3 class="mb-1"><i class="fas {{ $pageIcon }} me-2 text-primary"></i>{{ $pageTitle }}</h3>
                    <p class="text-muted mb-0">Documento legal de la plataforma J2Biznes</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @if($document)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>{{ $document->title }}</strong>
                        <div>
                            @if($document->version)
                                <span class="badge badge-info">v{{ $document->version }}</span>
                            @endif
                            @if($document->effective_date)
                                <span class="text-muted ms-2" style="font-size: 0.85rem;">
                                    <i class="fas fa-calendar-alt"></i> Vigente desde: {{ $document->effective_date->format('d/m/Y') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body legal-document-content">
                        {!! $document->content !!}
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Documento no disponible</h5>
                        <p class="text-muted">Este documento aún no ha sido publicado por el administrador de la plataforma.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.legal-document-content {
    line-height: 1.8;
    font-size: 0.95rem;
}
.legal-document-content h1 { font-size: 1.5rem; margin-top: 25px; margin-bottom: 12px; }
.legal-document-content h2 { font-size: 1.25rem; margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 8px; }
.legal-document-content h3 { font-size: 1.1rem; margin-top: 15px; margin-bottom: 8px; }
.legal-document-content ul, .legal-document-content ol { padding-left: 25px; margin-bottom: 15px; }
.legal-document-content li { margin-bottom: 6px; }
.legal-document-content p { margin-bottom: 12px; }
</style>
@endsection
