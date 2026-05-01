@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Detalle de Orden de Compra"
        parent-label="Órdenes de Compra"
        :parent-route="route('admin.purchase-orders')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <purchase-order-show-component
                    :order-id="{{ $order->id }}"
                ></purchase-order-show-component>
            </div>
        </div>
    </div>
@endsection
