@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Editar gasto mensual</h1>
    <form method="POST" action="{{ route('liquidaciones.gastos.update', $gasto) }}">
        @include('liquidaciones.gastos._form')
    </form>
</div>
@endsection
