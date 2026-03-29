@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h4 class="mb-3" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-tasks" style="color: var(--j2b-primary);"></i> Tareas
                </h4>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <tasks-component
                    :shop="{{ json_encode($shop) }}"
                    :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"
                ></tasks-component>
            </div>
        </div>
    </div>
@endsection
