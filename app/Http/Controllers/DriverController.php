<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index()
    {
        $drivers = \App\Models\Driver::all();
        return view('drivers.index', compact('drivers'));
    }

    public function create()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        // Solo admin y funcionarios pueden crear conductores
        if (!in_array($user->rol, ['admin', 'funcionario'])) {
            return redirect()->route('drivers.index')->with('error', 'No tienes permiso para realizar esta acción.');
        }
        return view('drivers.create');
    }

    public function store(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        // Solo admin y funcionarios pueden crear conductores
        if (!in_array($user->rol, ['admin', 'funcionario'])) {
            return redirect()->route('drivers.index')->with('error', 'No tienes permiso para realizar esta acción.');
        }
        
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'identity' => 'required|string|max:20|unique:drivers,identity',
            'phone' => 'nullable|string|max:20',
            'vehicle_plate' => 'required|string|max:20|unique:drivers,vehicle_plate',
        ]);
        $data['active'] = true;
        Driver::create($data);
        return redirect()->route('drivers.index')->with('success','Driver created successfully.');
    }

    public function edit(Driver $driver)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        // Solo admin y funcionarios pueden editar conductores
        if (!in_array($user->rol, ['admin', 'funcionario'])) {
            return redirect()->route('drivers.index')->with('error', 'No tienes permiso para realizar esta acción.');
        }
        return view('drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        // Solo admin y funcionarios pueden editar conductores
        if (!in_array($user->rol, ['admin', 'funcionario'])) {
            return redirect()->route('drivers.index')->with('error', 'No tienes permiso para realizar esta acción.');
        }
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'identity' => 'required|string|max:20|unique:drivers,identity,' . $driver->id,
            'phone' => 'nullable|string|max:20',
            'vehicle_plate' => 'required|string|max:20|unique:drivers,vehicle_plate,' . $driver->id,
            'active' => 'required|boolean',
        ]);
        $driver->update($data);
        return redirect()->route('drivers.index')->with('success','Driver updated successfully.');
    }

    public function destroy(Driver $driver)
    {
        $driver->active = false;
        $driver->save();
        return redirect()->route('drivers.index')->with('success','Driver deactivated successfully.');
    }
}
