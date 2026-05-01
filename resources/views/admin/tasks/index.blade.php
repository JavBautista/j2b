@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Tareas" icon="fa-tasks" />
@endsection

@section('content')
    <div class="container-fluid">
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
