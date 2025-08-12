@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Editar Plantilla: {{ $template->name }}</h4>
                    <a href="{{ route('contract-templates.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
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