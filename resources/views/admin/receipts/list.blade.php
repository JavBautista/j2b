@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Notas de Venta"
        icon="fa-list-alt"
        subtitle="Gestiona tus ventas, cotizaciones y rentas"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                @php
                    $shop = auth()->user()->shop;
                    $cfdiActivo = $shop && $shop->cfdi_enabled && $shop->cfdiEmisor && $shop->cfdiEmisor->is_registered;
                @endphp
                <receipt-list-component
                    :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"
                    :cfdi-activo="{{ $cfdiActivo ? 'true' : 'false' }}"
                ></receipt-list-component>
            </div>
        </div>
    </div>
@endsection
