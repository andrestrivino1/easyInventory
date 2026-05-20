@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Nueva Liquidación de Viaje</h1>

    <form method="POST" action="{{ route('liquidaciones.store') }}">
        @include('liquidaciones.partials._form')
    </form>
</div>
@endsection
