@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Contexto de Tienda (Prompt IA)"
        parent-label="Configuraciones IA"
        :parent-route="route('admin.configurations.ai-settings')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <ai-settings-component></ai-settings-component>
    </div>
@endsection
