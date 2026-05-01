@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Facturas Emitidas" icon="fa-file-invoice-dollar" subtitle="Listado de comprobantes CFDI emitidos por la tienda" />
@endsection

@section('content')
    <div class="container-fluid">
        <cfdi-facturas-component></cfdi-facturas-component>
    </div>
@endsection
