@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h1>Editar Liquidación #{{ $liq->id }}</h1>
        <span class="badge bg-secondary fs-6 align-self-center">{{ strtoupper($liq->estado) }}</span>
    </div>

    <form method="POST" action="{{ route('liquidaciones.update', $liq) }}">
        @include('liquidaciones.partials._form')
    </form>
</div>
@endsection
