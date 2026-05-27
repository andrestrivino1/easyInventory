@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Nuevo gasto mensual</h1>
    <form method="POST" action="{{ route('liquidaciones.gastos.store') }}">
        @include('liquidaciones.gastos._form')
    </form>
</div>
@endsection
