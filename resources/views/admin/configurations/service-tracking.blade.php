@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Seguimiento de Servicios"
        parent-label="Configuraciones"
        :parent-route="route('admin.configurations')"
    />
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <service-tracking-config-component></service-tracking-config-component>
        </div>
    </div>
</div>
@endsection
