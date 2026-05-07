@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Lecturas SNMP"
        icon="fa-cloud-download"
        subtitle="Datos remotos de copiadoras y multifuncionales recibidos por el agente SNMP"
    />
@endsection

@section('content')
<div class="container-fluid">
    <snmp-readings-component></snmp-readings-component>
</div>
@endsection
