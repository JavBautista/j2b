@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Cuentas Bancarias"
        parent-label="Configuraciones"
        :parent-route="route('admin.configurations.index')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <shop-bank-accounts-component></shop-bank-accounts-component>
    </div>
@endsection
