<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || auth()->user()->rol !== 'admin') {
                return redirect('/')->with('error', 'Acceso no autorizado.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $usuarios = User::with('almacen', 'almacenes')->orderBy('nombre_completo')->get();
        return view('users.index', compact('usuarios'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Solo admin puede crear usuarios
        if (auth()->user()->rol !== 'admin') {
            return redirect()->route('users.index')->with('error', 'Solo los administradores pueden crear usuarios.');
        }
        $almacenes = Warehouse::orderBy('nombre')->get();
        return view('users.create', ['almacenes' => $almacenes]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Solo admin puede crear usuarios
        if (auth()->user()->rol !== 'admin') {
            return redirect()->route('users.index')->with('error', 'Solo los administradores pueden crear usuarios.');
        }
        
        $rules = [
            'nombre_completo' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'telefono' => 'nullable|string|max:20',
            'rol' => 'required|in:admin,clientes,funcionario,importer,import_viewer',
            'password' => 'required|string|min:6|confirmed',
        ];
        
        // Validaciones según el rol
        if ($request->rol === 'funcionario') {
            $rules['almacenes'] = 'required|array|min:1';
            $rules['almacenes.*'] = 'exists:warehouses,id';
        } elseif ($request->rol === 'clientes') {
            $rules['almacenes'] = 'required|array|min:1';
            $rules['almacenes.*'] = 'exists:warehouses,id';
        }
        // admin no requiere almacen_id (puede ver todos)
        
        $data = $request->validate($rules);
        
        // Validar que funcionarios solo seleccionen bodegas de Buenaventura
        if ($request->rol === 'funcionario' && isset($request->almacenes)) {
            $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
            $almacenesSeleccionados = $request->almacenes;
            
            foreach ($almacenesSeleccionados as $almacenId) {
                if (!in_array($almacenId, $bodegasBuenaventuraIds)) {
                    return back()->withErrors(['almacenes' => 'Los funcionarios solo pueden seleccionar bodegas de Buenaventura.'])->withInput();
                }
            }
        }
        
        $data['password'] = Hash::make($data['password']);
        $data['name'] = $data['email']; // Generar name automáticamente desde email
        
        // Guardar almacenes según el rol
        $almacenes = null;
        if ($request->rol === 'funcionario' || $request->rol === 'clientes') {
            $almacenes = $request->almacenes;
            unset($data['almacenes']);
            $data['almacen_id'] = null; // Funcionarios y clientes usan relación many-to-many
        } elseif ($request->rol !== 'clientes') {
            $data['almacen_id'] = null; // admin no tiene almacen_id
        }
        
        $usuario = User::create($data);
        
        // Sincronizar almacenes para funcionarios y clientes
        if (($request->rol === 'funcionario' || $request->rol === 'clientes') && $almacenes) {
            $usuario->almacenes()->sync($almacenes);
        }
        
        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $usuario = User::with('almacenes')->findOrFail($id);
        $almacenes = Warehouse::orderBy('nombre')->get();
        return view('users.edit', compact('usuario','almacenes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);
        
        $rules = [
            'nombre_completo' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,'.$usuario->id,
            'telefono' => 'nullable|string|max:20',
            'rol' => 'required|in:admin,clientes,funcionario,importer,import_viewer',
            'password' => 'nullable|string|min:6|confirmed',
        ];
        
        // Validaciones según el rol
        if ($request->rol === 'funcionario') {
            $rules['almacenes'] = 'required|array|min:1';
            $rules['almacenes.*'] = 'exists:warehouses,id';
        } elseif ($request->rol === 'clientes') {
            $rules['almacenes'] = 'required|array|min:1';
            $rules['almacenes.*'] = 'exists:warehouses,id';
        }
        // admin no requiere almacen_id
        
        $data = $request->validate($rules);
        
        // Validar que funcionarios solo seleccionen bodegas de Buenaventura
        if ($request->rol === 'funcionario' && isset($request->almacenes)) {
            $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
            $almacenesSeleccionados = $request->almacenes;
            
            foreach ($almacenesSeleccionados as $almacenId) {
                if (!in_array($almacenId, $bodegasBuenaventuraIds)) {
                    return back()->withErrors(['almacenes' => 'Los funcionarios solo pueden seleccionar bodegas de Buenaventura.'])->withInput();
                }
            }
        }
        
        $data['name'] = $data['email']; // Generar name automáticamente desde email
        
        // Manejar contraseña
        if (isset($data['password']) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
        unset($data['password']);
        }
        
        // Guardar almacenes según el rol
        $almacenes = null;
        if ($request->rol === 'funcionario' || $request->rol === 'clientes') {
            $almacenes = $request->almacenes;
            unset($data['almacenes']);
            $data['almacen_id'] = null; // Funcionarios y clientes usan relación many-to-many
        } elseif ($request->rol !== 'clientes') {
            $data['almacen_id'] = null; // admin no tiene almacen_id
            // Limpiar relación pivot si cambiaron de funcionario/cliente a otro rol
            $usuario->almacenes()->detach();
        }
        
        $usuario->update($data);
        
        // Sincronizar almacenes para funcionarios y clientes
        if (($request->rol === 'funcionario' || $request->rol === 'clientes') && $almacenes) {
            $usuario->almacenes()->sync($almacenes);
        }
        
        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Solo admin puede eliminar usuarios
        if(auth()->user()->rol !== 'admin') {
            return back()->with('error','Solo los administradores pueden eliminar usuarios.');
        }
        $usuario = User::findOrFail($id);
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }
        $usuario->delete();
        return back()->with('success', 'Usuario eliminado correctamente.');
    }
}
