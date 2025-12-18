@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">
                    <i class="fa fa-eye me-2"></i>Detalle de Orden de Compra
                </h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <purchase-order-show-component
                    :order-id="{{ $order->id }}"
                ></purchase-order-show-component>
            </div>
        </div>
    </div>
@endsection
