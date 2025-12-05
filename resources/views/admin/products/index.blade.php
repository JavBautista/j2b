@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">Productos</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <products-component :shop="{{ json_encode($shop) }}" :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"></products-component>
            </div>
        </div>
    </div>
@endsection
