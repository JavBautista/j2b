@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Equipos" icon="fa-cube" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <equipments-component :shop="{{ json_encode($shop) }}" :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"></equipments-component>
            </div>
        </div>
    </div>
@endsection
