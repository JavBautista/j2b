@extends('client.layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Agregar Nuevo Campo Extra</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('client.configurations.extra-fields.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="field_name" class="form-label">Nombre del Campo</label>
                    <input type="text" class="form-control" id="field_name" name="field_name" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" checked="checked">
                    <label class="form-check-label" for="active">Activo (Mostrar)</label>
                </div>
                <button type="submit" class="btn btn-primary">Agregar Campo</button>
            </form>
        </div>
    </div>
</div>
@endsection
