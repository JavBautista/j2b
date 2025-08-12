@extends('admin.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <img src="{{ asset('img/j2b_1200px.png') }}" width="25%" alt="J2Biznes Logo" class="img-fluid">
                    <h1 class="card-title">Descargas</h1>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Descargar APK</h5>
                                    <p class="card-text">Haz clic en el siguiente enlace para descargar la aplicación Android de J2B.</p>
                                    <a href="{{ route('download.apk', ['filename' => 'j2b.apk']) }}" class="btn btn-primary">Descargar APK</a>
                                </div>
                            </div>
                        </div>
                        <!-- Agrega más tarjetas aquí si necesitas más descargas -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
