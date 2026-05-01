@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Gestión CFDI" icon="fa-file-invoice" subtitle="Administración global de timbrado fiscal" />
@endsection

@section('content')
    <div class="container-fluid">
        <cfdi-management-component></cfdi-management-component>
    </div>
@endsection
