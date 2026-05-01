@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Gastos" icon="fa-money-bill-wave" />
@endsection

@section('content')
<div class="container-fluid">
    <gastos-component :shop="{{ json_encode($shop) }}"></gastos-component>
</div>
@endsection
