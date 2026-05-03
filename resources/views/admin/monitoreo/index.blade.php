@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Monitoreo GPS"
        icon="fa-map-marker-alt"
        subtitle="Seguimiento en tiempo real de colaboradores en campo"
    />
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <monitoreo-component></monitoreo-component>
        </div>
    </div>
</div>

{{-- Config inyectada en runtime (no se incrusta en el bundle compilado) --}}
<script>
    window.j2bConfig = window.j2bConfig || {};
    window.j2bConfig.mapbox = @json([
        'token' => config('services.mapbox.token'),
    ]);
    window.j2bConfig.firebase = @json([
        'apiKey' => config('services.firebase_web.api_key'),
        'authDomain' => config('services.firebase_web.auth_domain'),
        'databaseURL' => config('services.firebase_web.database_url'),
        'projectId' => config('services.firebase_web.project_id'),
        'storageBucket' => config('services.firebase_web.storage_bucket'),
        'messagingSenderId' => config('services.firebase_web.messaging_sender_id'),
        'appId' => config('services.firebase_web.app_id'),
    ]);
</script>
@endsection
