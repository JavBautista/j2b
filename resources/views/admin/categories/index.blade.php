@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">Categorías</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <categories-component :shop="{{ json_encode($shop) }}" :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"></categories-component>
            </div>
        </div>
    </div>
@endsection
