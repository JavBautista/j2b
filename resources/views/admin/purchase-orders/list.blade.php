@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">
                    <i class="fa fa-truck me-2"></i>Órdenes de Compra
                </h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <purchase-order-list-component></purchase-order-list-component>
            </div>
        </div>
    </div>
@endsection
