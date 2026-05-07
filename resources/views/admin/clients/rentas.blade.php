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
    <snmp-token-manager-component :client-id="{{ $client->id }}"></snmp-token-manager-component>

    <rentas-cliente-component
        :client="{{ json_encode($client) }}"
        :shop="{{ json_encode($shop) }}"
        :is-limited-user="{{ json_encode($isLimitedUser) }}"
    ></rentas-cliente-component>
</div>
@endsection
