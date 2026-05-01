@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Clientes de la tienda" icon="fa-users" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <shop-clients-component
                    :shop="{{ json_encode($shop) }}"
                    :is-limited-user="{{ json_encode($isLimitedUser) }}"></shop-clients-component>
            </div>
        </div>
    </div>
@endsection
