@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">
                    <i class="fa fa-plus-circle me-2"></i>Nueva Orden de Compra
                </h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <purchase-order-create-component></purchase-order-create-component>
            </div>
        </div>
    </div>
@endsection
