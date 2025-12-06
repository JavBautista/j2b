@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Gastos</h1>
    <gastos-component :shop="{{ json_encode($shop) }}"></gastos-component>
</div>
@endsection
