@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Tiendas" icon="fa-store" subtitle="Administración de tiendas registradas en la plataforma" />
@endsection

@section('content')
    <div class="container-fluid">
        <superadmin-shops-component></superadmin-shops-component>
    </div>
@endsection
