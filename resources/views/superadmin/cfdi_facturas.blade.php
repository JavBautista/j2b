@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Facturas Globales CFDI" icon="fa-file-invoice-dollar" subtitle="Listado consolidado de comprobantes timbrados" />
@endsection

@section('content')
    <div class="container-fluid">
        <superadmin-cfdi-facturas-component></superadmin-cfdi-facturas-component>
    </div>
@endsection
