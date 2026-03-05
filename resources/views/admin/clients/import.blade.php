@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/admin/clients') }}">Clientes</a></li>
                        <li class="breadcrumb-item active">Importar</li>
                    </ol>
                </nav>
                <h1 class="page-header">Importar Clientes desde Excel</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <client-import-component :shop="{{ json_encode($shop) }}"></client-import-component>
            </div>
        </div>
    </div>
@endsection
