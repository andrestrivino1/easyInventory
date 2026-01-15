<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'photo' => 'nullable|image|max:2048',
            'vehicle_photo' => 'nullable|image|max:2048',
            'social_security_date' => 'nullable|date',
            'social_security_pdf' => 'nullable|file|mimes:pdf|max:5120',
            'vehicle_owner' => 'nullable|string|max:255',
        ]);
        
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('drivers', 'public');
        }
        
        if ($request->hasFile('vehicle_photo')) {
            $data['vehicle_photo_path'] = $request->file('vehicle_photo')->store('drivers', 'public');
        }
        
        if ($request->hasFile('social_security_pdf')) {
            $data['social_security_pdf'] = $request->file('social_security_pdf')->store('drivers', 'public');
        }
        
        $data['active'] = true;
        unset($data['photo'], $data['vehicle_photo']);
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
            'photo' => 'nullable|image|max:2048',
            'vehicle_photo' => 'nullable|image|max:2048',
            'social_security_date' => 'nullable|date',
            'social_security_pdf' => 'nullable|file|mimes:pdf|max:5120',
            'vehicle_owner' => 'nullable|string|max:255',
        ]);
        
        if ($request->hasFile('photo')) {
            if ($driver->photo_path) {
                Storage::disk('public')->delete($driver->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('drivers', 'public');
        }
        
        if ($request->hasFile('vehicle_photo')) {
            if ($driver->vehicle_photo_path) {
                Storage::disk('public')->delete($driver->vehicle_photo_path);
            }
            $data['vehicle_photo_path'] = $request->file('vehicle_photo')->store('drivers', 'public');
        }
        
        if ($request->hasFile('social_security_pdf')) {
            if ($driver->social_security_pdf) {
                Storage::disk('public')->delete($driver->social_security_pdf);
            }
            $data['social_security_pdf'] = $request->file('social_security_pdf')->store('drivers', 'public');
        }
        
        unset($data['photo'], $data['vehicle_photo']);
        $driver->update($data);
        return redirect()->route('drivers.index')->with('success','Driver updated successfully.');
    }

    public function destroy(Driver $driver)
    {
        $driver->active = false;
        $driver->save();
        return redirect()->route('drivers.index')->with('success','Driver deactivated successfully.');
    }

    // View social security PDF
    public function viewSocialSecurityPdf(Driver $driver)
    {
        if (!$driver->social_security_pdf) {
            abort(404, 'PDF de seguridad social no encontrado');
        }
        
        // Verificar si el archivo existe en el disco público
        if (!Storage::disk('public')->exists($driver->social_security_pdf)) {
            abort(404, 'El archivo PDF no existe en el servidor');
        }
        
        $fullPath = Storage::disk('public')->path($driver->social_security_pdf);
        if (!file_exists($fullPath)) {
            abort(404, 'El archivo no se encontró en la ruta especificada');
        }
        
        $mimeType = Storage::disk('public')->mimeType($driver->social_security_pdf);
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
        ]);
    }

    // View driver photo
    public function viewPhoto(Driver $driver)
    {
        if (!$driver->photo_path) {
            abort(404, 'Foto del conductor no encontrada');
        }
        
        if (!Storage::disk('public')->exists($driver->photo_path)) {
            abort(404, 'El archivo de foto no existe en el servidor');
        }
        
        $fullPath = Storage::disk('public')->path($driver->photo_path);
        if (!file_exists($fullPath)) {
            abort(404);
        }
        
        $mimeType = Storage::disk('public')->mimeType($driver->photo_path);
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
        ]);
    }

    // View vehicle photo
    public function viewVehiclePhoto(Driver $driver)
    {
        if (!$driver->vehicle_photo_path) {
            abort(404, 'Foto del vehículo no encontrada');
        }
        
        if (!Storage::disk('public')->exists($driver->vehicle_photo_path)) {
            abort(404, 'El archivo de foto no existe en el servidor');
        }
        
        $fullPath = Storage::disk('public')->path($driver->vehicle_photo_path);
        if (!file_exists($fullPath)) {
            abort(404);
        }
        
        $mimeType = Storage::disk('public')->mimeType($driver->vehicle_photo_path);
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
        ]);
    }
}
