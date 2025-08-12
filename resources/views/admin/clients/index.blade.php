@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">Clientes</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <p>Esta es la página de clientes de la tienda: {{ $shop->name }}</p>
                <shop-clients-component
                    :shop="{{ json_encode($shop) }}"></shop-clients-component>
            </div>
        </div>
    </div>
@endsection