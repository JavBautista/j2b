@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Proveedores" icon="fa-handshake-o" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <suppliers-component
                    :shop="{{ json_encode($shop) }}"
                    :is-limited-user="{{ json_encode($isLimitedUser) }}"></suppliers-component>
            </div>
        </div>
    </div>
@endsection
