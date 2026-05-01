@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Recibos de {{ $client->name }}"
        parent-label="Clientes"
        :parent-route="route('admin.clients')"
    />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <!-- Información del cliente -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="card-title">
                                    <i class="fa fa-user text-primary"></i> Información del Cliente
                                </h5>
                                <p class="mb-1"><strong>Nombre:</strong> {{ $client->name }}</p>
                                <p class="mb-1"><strong>Email:</strong> {{ $client->email ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Teléfono:</strong> {{ $client->movil ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Empresa:</strong> {{ $client->company ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Dirección:</strong> {{ $client->address ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Estado:</strong> 
                                    @if($client->active)
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-danger">Inactivo</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <receipts-component 
                    :client="{{ json_encode($client) }}">
                </receipts-component>
            </div>
        </div>
    </div>
@endsection