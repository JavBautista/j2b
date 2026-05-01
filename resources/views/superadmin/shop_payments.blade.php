@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Pagos de {{ $shopName }}"
        parent-label="Tiendas"
        :parent-route="route('superadmin.shops')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <shop-payments-component :shop-id="{{ $shopId }}" shop-name="{{ $shopName }}"></shop-payments-component>
    </div>
@endsection
