@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">
                    <i class="fa fa-list-alt me-2"></i>Notas de Venta
                </h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <receipt-list-component
                    :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"
                ></receipt-list-component>
            </div>
        </div>
    </div>
@endsection
