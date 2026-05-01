@extends('superadmin.layouts.app')

@section('page-header')
    <x-admin.page-header title="Documentos Legales" icon="fa-gavel" subtitle="Términos, privacidad y demás documentos legales" />
@endsection

@section('content')
    <div class="container-fluid">
        <legal-documents-component></legal-documents-component>
    </div>
@endsection
