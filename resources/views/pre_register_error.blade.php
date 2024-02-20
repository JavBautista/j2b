@extends('auth.contenido')

@section('login')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Error de confirmación</div>
                    <div class="card-body">
                        @if(isset($error))
                            <div class="alert alert-danger">
                                  {{ $error }}
                            </div>
                        @endif
                        <p>
                            <a class="btn btn-secondary" href="{{ route('solicitud') }}">Regresar al pre-registro</a>
                        </p>
                    </div>
            </div>
        </div>
    </div>
</div>
@endsection
