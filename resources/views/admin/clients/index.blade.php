@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">Clientes de la tienda</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <shop-clients-component
                    :shop="{{ json_encode($shop) }}"
                    :is-limited-user="{{ json_encode($isLimitedUser) }}"></shop-clients-component>
            </div>
        </div>
    </div>
@endsection