@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Tasas de Impuesto"
        parent-label="Configuraciones"
        :parent-route="route('admin.configurations')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <shop-tax-rates-component></shop-tax-rates-component>
    </div>
@endsection
