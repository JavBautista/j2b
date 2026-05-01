@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Mensajes de Contacto" icon="fa-envelope" subtitle="Mensajes recibidos desde el sitio público" />
@endsection

@section('content')
    <div class="container-fluid">
        <contact-messages-component></contact-messages-component>
    </div>
@endsection
