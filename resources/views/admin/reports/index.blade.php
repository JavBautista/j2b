@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">Reportes</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <reports-component
                    :shop="{{ json_encode($shop) }}"
                    :user-limited="{{ auth()->user()->limited ? 'true' : 'false' }}"
                    initial-tab="{{ request('tab', 'dashboard') }}"
                ></reports-component>
            </div>
        </div>
    </div>
@endsection
