@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Pre-registros" icon="fa-user-plus" subtitle="Solicitudes pendientes de aprobación" />
@endsection

@section('content')
    <div class="container-fluid">
        <superadmin-pre-register-component></superadmin-pre-register-component>
    </div>
@endsection
