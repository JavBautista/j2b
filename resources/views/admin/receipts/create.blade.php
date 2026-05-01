@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Nueva Nota de Venta"
        parent-label="Notas de Venta"
        :parent-route="route('admin.receipts')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <receipt-form-component
            :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"
        ></receipt-form-component>
    </div>
@endsection
