@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Tarifas J2 Monitor"
        icon="fa-tags"
        subtitle="Catálogo de cobro por rango de equipos del servicio de monitoreo SNMP" />
@endsection

@section('content')
    <div class="container-fluid">
        <superadmin-monitor-pricing-tiers></superadmin-monitor-pricing-tiers>
    </div>
@endsection
