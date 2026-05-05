@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-lg-3 col-xs-6">
      <div class="small-box" style="background:#3c8dbc;color:#fff;">
        <div class="inner">
          <h3>{{ $totalProductos }}</h3>
          <p>Productos</p>
        </div>
        <div class="icon"><i class="bi bi-bag"></i></div>
        <a href="{{ route('products.index') }}" class="small-box-footer">Más info <i class="bi bi-arrow-right-circle"></i></a>
      </div>
    </div>
    <div class="col-lg-3 col-xs-6">
      <div class="small-box" style="background:#00a65a;color:#fff;">
        <div class="inner">
          <h3>{{ $totalBodegas }}</h3>
          <p>Bodegas</p>
        </div>
        <div class="icon"><i class="bi bi-building"></i></div>
        <a href="{{ route('warehouses.index') }}" class="small-box-footer">Más info <i class="bi bi-arrow-right-circle"></i></a>
      </div>
    </div>
    <div class="col-lg-3 col-xs-6">
      <div class="small-box" style="background:#f39c12;color:#111;">
        <div class="inner">
          <h3>{{ $bajoStock }}</h3>
          <p>Bajo stock</p>
        </div>
        <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
        <a href="{{ route('stock.index') }}" class="small-box-footer text-dark">Ver detalles <i class="bi bi-arrow-right-circle"></i></a>
      </div>
    </div>
    <div class="col-lg-3 col-xs-6">
      <div class="small-box" style="background:#dd4b39;color:#fff;">
        <div class="inner">
          <h3>{{ $transito }}</h3>
          <p>Órdenes en tránsito</p>
        </div>
        <div class="icon"><i class="bi bi-arrow-left-right"></i></div>
        <a href="{{ route('transfer-orders.index') }}" class="small-box-footer">Más info <i class="bi bi-arrow-right-circle"></i></a>
      </div>
    </div>
  </div>
</div>
@endsection
