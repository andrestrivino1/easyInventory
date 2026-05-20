@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Editar Ruta: {{ $route->name }}</h1>
    <form method="POST" action="{{ route('liquidaciones.routes.update', $route) }}">
        @include('liquidaciones.routes.partials._form')
    </form>
</div>
@endsection
