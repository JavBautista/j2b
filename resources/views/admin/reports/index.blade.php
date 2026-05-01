@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Reportes" icon="fa-chart-line" />
@endsection

@section('content')
    <div class="container-fluid">
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
