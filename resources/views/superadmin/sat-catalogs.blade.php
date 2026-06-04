@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Catálogos SAT" icon="fa-list"
        subtitle="Régimen fiscal, uso CFDI y formas/métodos de pago — fuente única para web e Ionic" />
@endsection

@section('content')
    <div class="container-fluid">
        <superadmin-sat-catalogs-component></superadmin-sat-catalogs-component>
    </div>
@endsection
