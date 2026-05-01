@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Detalle de Tarea"
        parent-label="Tareas"
        :parent-route="route('admin.tasks')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <task-detail-component
                    :task-initial="{{ json_encode($task) }}"
                    :shop="{{ json_encode($shop) }}"
                    :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"
                    :service-steps-initial="{{ json_encode($serviceSteps) }}"
                ></task-detail-component>
            </div>
        </div>
    </div>
@endsection
