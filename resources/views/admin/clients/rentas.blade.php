@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Rentas de {{ $client->name }}"
        parent-label="Clientes"
        :parent-route="route('admin.clients')"
    />
@endsection

@section('content')
<div class="container-fluid">
    <j2-monitor-license-card :client-id="{{ $client->id }}"></j2-monitor-license-card>

    <rentas-cliente-component
        :client="{{ json_encode($client) }}"
        :shop="{{ json_encode($shop) }}"
        :is-limited-user="{{ json_encode($isLimitedUser) }}"
    ></rentas-cliente-component>
</div>
@endsection
