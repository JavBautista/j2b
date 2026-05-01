@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Configuración CFDI" icon="fa-file-invoice" subtitle="Datos del emisor y certificados de timbrado" />
@endsection

@section('content')
    <div class="container-fluid">
        <cfdi-config-component></cfdi-config-component>
    </div>
@endsection
