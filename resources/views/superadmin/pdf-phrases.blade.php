@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Frases para PDFs" icon="fa-quote-right" subtitle="Frases motivacionales que aparecen en los PDFs generados" />
@endsection

@section('content')
    <div class="container-fluid">
        <pdf-phrases-component></pdf-phrases-component>
    </div>
@endsection
