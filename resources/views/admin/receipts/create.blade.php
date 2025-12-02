@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">
                    <i class="fa fa-file-invoice-dollar me-2"></i>Nueva Nota de Venta
                </h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <receipt-create-component></receipt-create-component>
            </div>
        </div>
    </div>
@endsection
