@extends('superadmin.layouts.app')
@section('content')
    <shop-payments-component :shop-id="{{ $shopId }}" shop-name="{{ $shopName }}"></shop-payments-component>
@endsection
