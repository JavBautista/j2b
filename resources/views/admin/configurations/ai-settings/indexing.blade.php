@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Indexación de IA"
        parent-label="Configuraciones IA"
        :parent-route="route('admin.configurations.ai-settings')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <ai-indexing-component></ai-indexing-component>
    </div>
@endsection
