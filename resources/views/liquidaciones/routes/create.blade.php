@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Nueva Ruta</h1>
    <form method="POST" action="{{ route('liquidaciones.routes.store') }}">
        @include('liquidaciones.routes.partials._form')
    </form>
</div>
@endsection
