@extends('client.layouts.app')

@section('page-header')
    <x-admin.page-header title="Detalles de la Tienda" icon="fa-store" subtitle="Información de tu negocio">
        <x-slot:actions>
            <a href="{{ route('client.shop.edit') }}" class="btn btn-primary">
                <i class="fa fa-edit me-1"></i> Editar
            </a>
        </x-slot:actions>
    </x-admin.page-header>
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif



                    <div class="mb-3">
                        <h5>Información general:</h5>
                        <p><strong>Nombre:</strong> {{ $shop->name }}</p>
                        <p><strong>Descripción:</strong> {{ $shop->description }}</p>
                        <p><strong>Código Postal:</strong> {{ $shop->zip_code }}</p>
                        <p><strong>Dirección:</strong> {{ $shop->address }}</p>
                        <p><strong>Número exterior:</strong> {{ $shop->number_out }}</p>
                        <p><strong>Número interior:</strong> {{ $shop->number_int }}</p>
                        <p><strong>Distrito:</strong> {{ $shop->district }}</p>
                        <p><strong>Ciudad:</strong> {{ $shop->city }}</p>
                        <p><strong>Estado:</strong> {{ $shop->state }}</p>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <h5>Información de contacto:</h5>
                        <p><strong>WhatsApp:</strong> {{ $shop->whatsapp }}</p>
                        <p><strong>Teléfono:</strong> {{ $shop->phone }}</p>
                        <p><strong>Email:</strong> {{ $shop->email }}</p>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <h5>Redes sociales:</h5>
                        <p><strong>Facebook:</strong> {{ $shop->facebook }}</p>
                        <p><strong>Twitter:</strong> {{ $shop->twitter }}</p>
                        <p><strong>Instagram:</strong> {{ $shop->instagram }}</p>
                        <p><strong>Pinterest:</strong> {{ $shop->pinterest }}</p>
                        <p><strong>Canal de video:</strong> {{ $shop->video_channel }}</p>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <h5>Información adicional:</h5>
                        <p><strong>Eslogan:</strong> {{ $shop->slogan }}</p>
                        <p><strong>Presentación:</strong> {{ $shop->presentation }}</p>
                        <p><strong>Misión:</strong> {{ $shop->mission }}</p>
                        <p><strong>Visión:</strong> {{ $shop->vision }}</p>
                        <p><strong>Valores:</strong> {{ $shop->values }}</p>
                        <p><strong>Número de cuenta bancaria:</strong> {{ $shop->bank_number }}</p>
                        <p><strong>Banco:</strong> {{ $shop->bank_name }}</p>
                        <p><strong>Página web:</strong> {{ $shop->web }}</p>
                    </div>

                    {{-- Agrega más secciones según sea necesario para mostrar otros campos --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
