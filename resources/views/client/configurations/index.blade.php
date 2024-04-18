@extends('client.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Configuraciones</div>
                <div class="card-body">

                    <div class="list-group">
                      <a href="{{ route('client.configurations.extra_fields') }}" class="list-group-item list-group-item-action">Campos extra para notas</a>
                     <!-- <a href="#" class="list-group-item list-group-item-action">A third link item</a>
                      <a href="#" class="list-group-item list-group-item-action">A fourth link item</a>
                  -->
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection