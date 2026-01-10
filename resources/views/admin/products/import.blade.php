@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/admin/products') }}">Productos</a></li>
                        <li class="breadcrumb-item active">Importar</li>
                    </ol>
                </nav>
                <h1 class="page-header">Importar Productos desde Excel</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <product-import-component :shop="{{ json_encode($shop) }}"></product-import-component>
            </div>
        </div>
    </div>
@endsection
