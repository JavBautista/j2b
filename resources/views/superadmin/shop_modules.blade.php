@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Módulos de {{ $shopName }}"
        parent-label="Suscripciones"
        :parent-route="route('superadmin.subscription-management')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <shop-modules-component :shop-id="{{ $shopId }}" shop-name="{{ $shopName }}"></shop-modules-component>
    </div>
@endsection
