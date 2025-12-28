@extends('layouts.app')

@section('content')
<style>
.table-users { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.08); }
.table-users th { background:#0d6efd; color:white; text-align:left; padding:12px; font-size:15px; }
.table-users td { padding:12px; border-bottom:1px solid #ebebeb; }
.table-users tr:hover { background:#f2f8ff; }
.actions button,.actions a{ padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-size:13px; margin-right:6px; text-decoration:none; }
.btn-edit{background:#198754;color:white;}
.btn-delete{background:#ffc107;color:#b30000;}
</style>
<div class="container-fluid" style="padding-top:32px;min-height:88vh;">
    <div class="mx-auto" style="max-width:1050px;">
    @if(auth()->user()->rol === 'admin')
    <div class="d-flex justify-content-end align-items-center mb-3"><a href="{{ route('users.create') }}" class="btn btn-primary rounded-pill px-4" style="font-weight:500;"><i class="bi bi-person-plus me-2"></i>Nuevo usuario</a></div>
    @endif
    <h2 class="mb-4 text-center" style="color:#333;font-weight:bold">Gestión de Usuarios</h2>
    <table class="table-users">
        <thead>
            <tr>
                <th>Nombre completo</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Almacén</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        @forelse($usuarios as $user)
            <tr>
                <td>{{ $user->nombre_completo }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->telefono ?? '-' }}</td>
                <td>{{ $user->almacen->nombre ?? '-' }}</td>
                <td>{{ ucfirst($user->rol) }}</td>
                <td class="actions">
                    <a href="{{ route('users.edit', $user) }}" class="btn-edit">Editar</a>
                    @if(auth()->user()->rol === 'admin' && auth()->id() !== $user->id)
                    <form action="{{ route('users.destroy', $user) }}" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-delete">Eliminar</button>
                    </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-5"><i class="bi bi-people text-secondary" style="font-size:2.2em;"></i><br><div class="mt-2">No hay usuarios registrados.</div></td>
            </tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
