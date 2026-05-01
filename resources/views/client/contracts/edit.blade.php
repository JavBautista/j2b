@extends('client.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Editar Plantilla: {{ $template->name }}"
        parent-label="Plantillas de Contratos"
        :parent-route="route('contract-templates.index')"
    />
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <shop-template-creator-component 
                        :default-variables="{{ json_encode($defaultVariables) }}"
                        :save-url="'{{ route('contract-templates.update', $template) }}'"
                        :template-data="{{ json_encode($template) }}"
                        :is-editing="true"
                    ></shop-template-creator-component>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection