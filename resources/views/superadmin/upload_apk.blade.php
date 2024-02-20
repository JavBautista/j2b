@extends('superadmin.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Subir archivo APK</div>

                <div class="card-body">

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('superadmin.store.apk') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="apkFile" class="form-label">Selecciona el archivo APK</label>
                            <input type="file" class="form-control" id="apkFile" name="apkFile" accept=".apk">
                        </div>

                        <button type="submit" class="btn btn-primary">Subir APK</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

