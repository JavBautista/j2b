@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Planes de Suscripción" icon="fa-tags" subtitle="Configuración de planes y precios" />
@endsection

@section('content')
    <div class="container-fluid">
        <superadmin-plans-component></superadmin-plans-component>
    </div>
@endsection
