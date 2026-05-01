@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Categorías" icon="fa-tags" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <categories-component :shop="{{ json_encode($shop) }}" :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"></categories-component>
            </div>
        </div>
    </div>
@endsection
