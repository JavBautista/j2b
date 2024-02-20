@extends('client.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Editar información de la tienda</div>

                <div class="card-body">
                    <form action="{{ route('client.shop.update', $shop->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" value="{{ $shop->id }}">
                        {{-- Información general --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $shop->name }}">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ $shop->description }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="zip_code" class="form-label">Código Postal</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" value="{{ $shop->zip_code }}">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="address" name="address" value="{{ $shop->address }}">
                        </div>

                        <div class="mb-3">
                            <label for="number_out" class="form-label">Número Exterior</label>
                            <input type="text" class="form-control" id="number_out" name="number_out" value="{{ $shop->number_out }}">
                        </div>

                        <div class="mb-3">
                            <label for="number_int" class="form-label">Número Interior</label>
                            <input type="text" class="form-control" id="number_int" name="number_int" value="{{ $shop->number_int }}">
                        </div>

                        <div class="mb-3">
                            <label for="district" class="form-label">Colonia</label>
                            <input type="text" class="form-control" id="district" name="district" value="{{ $shop->district }}">
                        </div>

                        <div class="mb-3">
                            <label for="city" class="form-label">Ciudad</label>
                            <input type="text" class="form-control" id="city" name="city" value="{{ $shop->city }}">
                        </div>

                        <div class="mb-3">
                            <label for="state" class="form-label">Estado</label>
                            <input type="text" class="form-control" id="state" name="state" value="{{ $shop->state }}">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="whatsapp" name="whatsapp" {{ $shop->whatsapp ? 'checked' : '' }}>
                                <label class="form-check-label" for="whatsapp">
                                    ¿Es número de WhatsApp?
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ $shop->phone }}">
                        </div>

                        {{-- Agrega más campos según sea necesario --}}

                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
