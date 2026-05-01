@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Gestión de Suscripciones" icon="fa-credit-card" subtitle="Administración global de suscripciones activas" />
@endsection

@section('content')
    <div class="container-fluid">
        <subscription-management-component></subscription-management-component>
    </div>
@endsection
