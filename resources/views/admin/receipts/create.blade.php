@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0" style="color: var(--j2b-dark); font-weight: 600;">
                <i class="fa fa-file-invoice-dollar me-2" style="color: var(--j2b-primary);"></i>Nueva Nota de Venta
            </h5>
            <a href="{{ route('admin.receipts') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left me-1"></i>Volver
            </a>
        </div>
        <receipt-form-component
            :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"
        ></receipt-form-component>
    </div>
@endsection
