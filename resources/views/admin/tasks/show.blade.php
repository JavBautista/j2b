@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <a href="{{ route('admin.tasks') }}" class="j2b-btn j2b-btn-dark mb-3">
                    <i class="fa fa-arrow-left"></i> Volver a Tareas
                </a>
            </div>
        </div>
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
