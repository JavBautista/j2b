@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Configuración de Notas de Venta"
        parent-label="Configuraciones"
        :parent-route="route('admin.configurations')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <receipt-settings-component></receipt-settings-component>
    </div>
@endsection
