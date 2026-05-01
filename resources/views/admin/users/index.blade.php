@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Usuarios de la Aplicación" icon="fa-user" />
@endsection

@section('content')
<div class="container-fluid">
    <users-component :shop="{{ json_encode($shop) }}"></users-component>
</div>
@endsection
