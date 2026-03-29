@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('admin.configurations') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver a Configuraciones
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <service-tracking-config-component></service-tracking-config-component>
        </div>
    </div>
</div>
@endsection
