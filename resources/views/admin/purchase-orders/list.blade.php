@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Órdenes de Compra" icon="fa-truck" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <purchase-order-list-component
                    :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"
                ></purchase-order-list-component>
            </div>
        </div>
    </div>
@endsection
