@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Productos" icon="fa-box" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <products-component :shop="{{ json_encode($shop) }}" :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"></products-component>
            </div>
        </div>
    </div>
@endsection
