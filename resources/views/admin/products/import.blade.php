@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Importar Productos desde Excel"
        parent-label="Productos"
        :parent-route="route('admin.products')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <product-import-component :shop="{{ json_encode($shop) }}"></product-import-component>
            </div>
        </div>
    </div>
@endsection
