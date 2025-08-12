@extends('client.layouts.app')

@section('content')
<div id="template-creator-page" class="template-creator-page">
    <shop-template-creator-component 
        :default-variables="{{ json_encode($defaultVariables) }}"
        :save-url="'{{ route('contract-templates.store') }}'"
    ></shop-template-creator-component>
</div>
@endsection

@section('styles')
<style>
/* Ocultar el sidebar y usar toda la pantalla */
.template-creator-page {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    background: #f8f9fa;
}

/* Ocultar elementos de navegación para esta página */
.template-creator-page ~ * {
    display: none !important;
}
</style>
@endsection