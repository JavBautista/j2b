@extends('client.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <img src="{{ asset('img/j2b_1200px.png') }}" width="50%" alt="J2Biznes Logo" class="img-fluid">
                    <h1 class="card-title">¡Bienvenido a J2Biznes!</h1>
                </div>
                <div class="card-body">
                    <p class="card-text">J2Biznes es una plataforma diseñada para ayudarte a gestionar tu negocio de manera eficiente y efectiva. Con nuestras herramientas, podrás administrar tus ventas, inventario, clientes y más, todo desde una sola plataforma integrada.</p>
                    <p class="card-text">Explora nuestras características y descubre cómo J2Biznes puede llevar tu negocio al siguiente nivel.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
