@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">Equipos</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <equipments-component :shop="{{ json_encode($shop) }}" :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"></equipments-component>
            </div>
        </div>
    </div>
@endsection
