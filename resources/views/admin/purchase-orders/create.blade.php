@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Nueva Orden de Compra"
        parent-label="Órdenes de Compra"
        :parent-route="route('admin.purchase-orders')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <purchase-order-create-component></purchase-order-create-component>
    </div>
@endsection
