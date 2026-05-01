@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Importar Clientes desde Excel"
        parent-label="Clientes"
        :parent-route="route('admin.clients')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <client-import-component :shop="{{ json_encode($shop) }}"></client-import-component>
            </div>
        </div>
    </div>
@endsection
