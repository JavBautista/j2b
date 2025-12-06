@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Usuarios de la Aplicación</h1>
    <users-component :shop="{{ json_encode($shop) }}"></users-component>
</div>
@endsection
