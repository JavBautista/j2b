@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Documentos" icon="fa-file-contract" subtitle="Contratos y plantillas en Markdown con generación de PDF" />
@endsection

@section('content')
    <div class="container-fluid">
        <superadmin-documents-component></superadmin-documents-component>
    </div>
@endsection
