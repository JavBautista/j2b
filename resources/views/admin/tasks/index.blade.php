@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">Tareas</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <tasks-component :shop="{{ json_encode($shop) }}"></tasks-component>
            </div>
        </div>
    </div>
@endsection
