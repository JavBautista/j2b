@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Editar Orden de Compra #{{ $order->folio }}"
        parent-label="Órdenes de Compra"
        :parent-route="route('admin.purchase-orders')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <purchase-order-create-component
            :order-data='@json($order)'
        ></purchase-order-create-component>
    </div>
@endsection
