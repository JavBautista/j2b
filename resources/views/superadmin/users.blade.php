@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Usuarios" icon="fa-user-shield" subtitle="Usuarios del sistema con permisos administrativos" />
@endsection

@section('content')
    <div class="container-fluid">
        <superadmin-users-component></superadmin-users-component>
    </div>
@endsection
