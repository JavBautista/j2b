@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Bitácora de Timbrado"
        icon="fa-history"
        subtitle="Registro detallado de timbrados, cancelaciones y complementos. Útil para reportar incidencias al soporte."
    />
@endsection

@section('content')
    <div class="container-fluid">
        <cfdi-bitacora-component></cfdi-bitacora-component>
    </div>
@endsection
