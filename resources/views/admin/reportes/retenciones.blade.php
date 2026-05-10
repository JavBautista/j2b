@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Reporte de Retenciones" icon="fa-minus-circle" subtitle="Resumen mensual de ISR e IVA retenidos en facturas vigentes" />
@endsection

@section('content')
    <div class="container-fluid">
        <retenciones-reporte-component></retenciones-reporte-component>
    </div>
@endsection
