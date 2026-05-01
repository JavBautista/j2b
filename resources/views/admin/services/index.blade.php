@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Servicios" icon="fa-cogs" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <services-component :shop="{{ json_encode($shop) }}" :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"></services-component>
            </div>
        </div>
    </div>
@endsection
