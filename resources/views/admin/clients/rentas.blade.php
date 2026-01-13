@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <rentas-cliente-component
        :client="{{ json_encode($client) }}"
        :shop="{{ json_encode($shop) }}"
        :is-limited-user="{{ json_encode($isLimitedUser) }}"
    ></rentas-cliente-component>
</div>
@endsection
