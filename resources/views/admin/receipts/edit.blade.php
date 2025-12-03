@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-12 d-flex justify-content-between align-items-center">
                <h1 class="page-header mb-0">
                    <i class="fa fa-edit me-2"></i>Editar Nota de Venta
                </h1>
                <a href="{{ route('admin.receipts') }}" class="btn btn-primary">
                    <i class="fa fa-arrow-left me-1"></i>Volver a la lista
                </a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <receipt-form-component :receipt-id="{{ $receiptId }}"></receipt-form-component>
            </div>
        </div>
    </div>
@endsection
