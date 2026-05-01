@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Editar Nota de Venta"
        parent-label="Notas de Venta"
        :parent-route="route('admin.receipts')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <receipt-form-component
                    :receipt-id="{{ $receiptId }}"
                    :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"
                ></receipt-form-component>
            </div>
        </div>
    </div>
@endsection
