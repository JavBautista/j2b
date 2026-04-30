@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <a href="{{ route('admin.receipts') }}"
                           class="btn btn-light border d-flex align-items-center justify-content-center"
                           style="width: 40px; height: 40px;"
                           title="Volver al listado">
                            <i class="fa fa-arrow-left"></i>
                        </a>
                        <div>
                            <small class="text-muted d-block">
                                <i class="fa fa-list me-1"></i>Notas de Venta
                            </small>
                            <h4 class="mb-0 fw-semibold">
                                <i class="fa fa-edit me-2 text-warning"></i>Editar Nota de Venta
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
