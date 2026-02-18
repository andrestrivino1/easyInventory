<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\TransferOrder;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use iio\libmergepdf\Merger;

class TransferOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();

        // Cargar relación almacenes si es funcionario o cliente
        if (in_array($user->rol, ['funcionario', 'clientes']) && !$user->relationLoaded('almacenes')) {
            $user->load('almacenes');
        }

        // Admin y funcionario ven todas las transferencias
        if (in_array($user->rol, ['admin', 'funcionario'])) {
            $transferOrders = TransferOrder::with([
                'from',
                'to',
                'products' => function ($query) {
                    $query->withPivot('quantity', 'container_id', 'good_sheets', 'bad_sheets', 'receive_by');
                },
                'driver'
            ])
                ->orderByDesc('date')
                ->paginate(10);
        } elseif ($user->rol === 'clientes') {
            // Clientes ven transferencias desde o hacia sus bodegas asignadas
            $bodegasAsignadasIds = $user->almacenes->pluck('id')->toArray();
            if (empty($bodegasAsignadasIds)) {
                $bodegasAsignadasIds = [];
            }

            if (!empty($bodegasAsignadasIds)) {
                $transferOrders = TransferOrder::with([
                    'from',
                    'to',
                    'products' => function ($query) {
                        $query->withPivot('quantity', 'container_id', 'good_sheets', 'bad_sheets', 'receive_by');
                    },
                    'driver'
                ])
                    ->where(function ($query) use ($bodegasAsignadasIds) {
                        $query->whereIn('warehouse_from_id', $bodegasAsignadasIds)
                            ->orWhereIn('warehouse_to_id', $bodegasAsignadasIds);
                    })
                    ->orderByDesc('date')
                    ->paginate(10);
            } else {
                // Si no tiene bodegas asignadas, devolver un paginador vacío
                $transferOrders = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            }
        } else {
            // Otros roles filtran por su bodega
            $transferOrders = TransferOrder::with([
                'from',
                'to',
                'products' => function ($query) {
                    $query->withPivot('quantity', 'container_id', 'good_sheets', 'bad_sheets', 'receive_by');
                },
                'driver'
            ])
                ->where(function ($query) use ($user) {
                    $query->where('warehouse_from_id', $user->almacen_id)
                        ->orWhere('warehouse_to_id', $user->almacen_id);
                })
                ->orderByDesc('date')
                ->paginate(10);
        }

        $canCreateTransfer = in_array($user->rol, ['admin', 'funcionario']) ||
            ($user->almacen_id && Warehouse::bodegaRecibeContenedores($user->almacen_id));

        // Pasar el usuario con las relaciones cargadas a la vista
        return view('transfer-orders.index', compact('transferOrders', 'canCreateTransfer', 'user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = Auth::user();

        // Admin, funcionario, clientes o usuarios de bodegas que reciben contenedores pueden crear transferencias
        if (
            !in_array($user->rol, ['admin', 'funcionario', 'clientes']) &&
            !($user->almacen_id && Warehouse::bodegaRecibeContenedores($user->almacen_id))
        ) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para crear transferencias.');
        }

        // Filtrar bodegas según el rol del usuario
        if ($user->rol === 'funcionario') {
            // Funcionarios: solo bodegas de Buenaventura
            $warehousesFrom = Warehouse::getBodegasBuenaventura();
            $warehousesTo = Warehouse::orderBy('nombre')->get();
        } elseif ($user->rol === 'clientes') {
            // Clientes: solo sus bodegas asignadas
            $user->load('almacenes');
            $warehousesFrom = $user->almacenes->sortBy('nombre');
            $warehousesTo = $user->almacenes->sortBy('nombre');
        } else {
            // Admin y otros: todas las bodegas
            $warehousesFrom = Warehouse::orderBy('nombre')->get();
            $warehousesTo = Warehouse::orderBy('nombre')->get();
        }

        $products = Product::with('containers')->orderBy('nombre')->get();
        $drivers = \App\Models\Driver::activeWithValidSocialSecurity()->orderBy('name')->get();
        return view('transfer-orders.create', compact('warehousesFrom', 'warehousesTo', 'products', 'drivers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Admin, funcionario, clientes o usuarios de bodegas que reciben contenedores pueden crear transferencias
        if (
            !in_array($user->rol, ['admin', 'funcionario', 'clientes']) &&
            !($user->almacen_id && Warehouse::bodegaRecibeContenedores($user->almacen_id))
        ) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para crear transferencias.');
        }

        $data = $request->validate([
            'warehouse_from_id' => 'required|different:warehouse_to_id|exists:warehouses,id',
            'warehouse_to_id' => 'required|exists:warehouses,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.container_id' => 'required|exists:containers,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.sheets_per_box' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:255',
            'use_external_driver' => 'nullable|boolean',
            'driver_id' => 'nullable|required_without:use_external_driver|exists:drivers,id',
            'external_driver_name' => 'nullable|required_if:use_external_driver,1|string|max:255',
            'external_driver_identity' => 'nullable|required_if:use_external_driver,1|string|max:50',
            'external_driver_plate' => 'nullable|required_if:use_external_driver,1|string|max:50',
            'external_driver_phone' => 'nullable|string|max:20',
            'aprobo' => 'nullable|string|max:255',
            'ciudad_destino' => 'nullable|string|max:255',
        ]);

        // Validar que clientes solo puedan crear transferencias entre sus bodegas asignadas
        if ($user->rol === 'clientes') {
            $user->load('almacenes');
            $bodegasPermitidas = $user->almacenes->pluck('id')->toArray();

            if (
                !in_array($data['warehouse_from_id'], $bodegasPermitidas) ||
                !in_array($data['warehouse_to_id'], $bodegasPermitidas)
            ) {
                return redirect()->back()
                    ->with('error', 'Solo puedes crear transferencias entre tus bodegas asignadas.')
                    ->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Validar y preparar productos
            $productsToAttach = [];
            foreach ($data['products'] as $index => $productData) {
                // Obtener producto de la bodega de origen
                // Los productos son globales, buscar solo por ID
                $product = Product::where('id', $productData['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El producto no existe.")->withInput();
                }

                // Validar contenedor
                $container = \App\Models\Container::find($productData['container_id']);
                if (!$container) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El contenedor no existe.")->withInput();
                }

                // Validar que el producto esté en el contenedor
                $sheetsPerBox = $productData['sheets_per_box'] ?? 0;
                $productInContainerQuery = $container->products()->where('products.id', $productData['product_id']);

                // Si se especificó láminas por caja, filtrar por ello
                if ($sheetsPerBox > 0) {
                    $productInContainerQuery->wherePivot('sheets_per_box', $sheetsPerBox);
                }

                $productInContainer = $productInContainerQuery->first();

                if (!$productInContainer) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El producto no está asociado al contenedor seleccionado con esa cantidad de láminas.")->withInput();
                }

                // Validar que desde bodegas que reciben contenedores solo se despachen Cajas
                if (Warehouse::bodegaRecibeContenedores($data['warehouse_from_id']) && $product->tipo_medida !== 'caja') {
                    DB::rollBack();
                    $warehouse = Warehouse::find($data['warehouse_from_id']);
                    return back()->with('error', "Producto #" . ($index + 1) . ": Desde " . ($warehouse ? $warehouse->nombre : 'bodegas que reciben contenedores') . " solo se pueden despachar productos medidos en Cajas.")->withInput();
                }

                // Calcular unidades a descontar
                $unidadesADescontar = $productData['quantity'];
                // Si es caja, usar sheets_per_box del contenedor si existe, o el del producto
                if ($product->tipo_medida === 'caja') {
                    $units = ($sheetsPerBox > 0) ? $sheetsPerBox : ($product->unidades_por_caja > 0 ? $product->unidades_por_caja : 1);
                    $unidadesADescontar = $productData['quantity'] * $units;
                }

                // Validar stock según el tipo de bodega
                if (Warehouse::bodegaRecibeContenedores($data['warehouse_from_id'])) {
                    // Para bodegas que reciben contenedores, validar cajas en contenedor
                    $pivotQuery = DB::table('container_product')
                        ->join('containers', 'container_product.container_id', '=', 'containers.id')
                        ->where('container_product.container_id', $productData['container_id'])
                        ->where('container_product.product_id', $productData['product_id'])
                        ->where('containers.warehouse_id', $data['warehouse_from_id']);

                    if ($sheetsPerBox > 0) {
                        $pivotQuery->where('container_product.sheets_per_box', $sheetsPerBox);
                    }

                    $pivot = $pivotQuery->select('container_product.boxes', 'container_product.weight_per_box')
                        ->lockForUpdate()
                        ->first();

                    if (!$pivot) {
                        DB::rollBack();
                        return back()->with('error', "Producto #" . ($index + 1) . ": El producto no está asociado al contenedor o el contenedor no pertenece a esta bodega.")->withInput();
                    }

                    if ($pivot->boxes < $productData['quantity']) {
                        DB::rollBack();
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): No hay suficientes cajas en el contenedor. Disponible: {$pivot->boxes} cajas.")->withInput();
                    }
                } else {
                    // Para otras bodegas, lógica existente (resumida por brevedad en este parche, pero mantenemos la lógica original donde no se toca)
                    // NOTA: Si estás copiando todo el bloque, asegúrate de mantener la lógica else intacta. 
                    // Como el reemplazo es large, asumimos que el bloque else sigue igual o lo incluimos.
                    // Para minimizar riesgo, asumimos mantener lógica original para 'else'.
                    // RE-INCLUYENDO LOGICA ORIGINAL DEL ELSE:

                    // Para otras bodegas, calcular stock desde transferencias recibidas menos salidas
                    $stock = 0;

                    // Sumar transferencias recibidas
                    $receivedTransfers = TransferOrder::where('status', 'recibido')
                        ->where('warehouse_to_id', $data['warehouse_from_id'])
                        ->whereHas('products', function ($query) use ($product) {
                            $query->where('products.id', $product->id);
                        })
                        ->with([
                            'products' => function ($query) use ($product) {
                                $query->where('products.id', $product->id)->withPivot('quantity', 'good_sheets', 'bad_sheets', 'receive_by');
                            }
                        ])
                        ->get();

                    foreach ($receivedTransfers as $transfer) {
                        // Lógica de cálculo de stock original...
                        $productInTransfer = $transfer->products->first();
                        if ($productInTransfer) {
                            $goodSheets = $productInTransfer->pivot->good_sheets;
                            $receiveBy = $productInTransfer->pivot->receive_by ?? 'laminas';

                            if ($goodSheets !== null) {
                                if ($receiveBy === 'cajas') {
                                    if ($product->unidades_por_caja > 0) {
                                        $stock += $goodSheets * $product->unidades_por_caja;
                                    } else {
                                        $stock += $goodSheets;
                                    }
                                } else {
                                    $stock += $goodSheets;
                                }
                            } else {
                                $quantity = $productInTransfer->pivot->quantity;
                                if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                                    $quantity = $quantity * $product->unidades_por_caja;
                                }
                                $stock += $quantity;
                            }
                        }
                    }

                    // Descontar salidas
                    $salidas = \App\Models\Salida::where('warehouse_id', $data['warehouse_from_id'])
                        ->whereHas('products', function ($query) use ($product) {
                            $query->where('products.id', $product->id);
                        })
                        ->with([
                            'products' => function ($query) use ($product) {
                                $query->where('products.id', $product->id)->withPivot('quantity');
                            }
                        ])
                        ->get();

                    foreach ($salidas as $salida) {
                        $productInSalida = $salida->products->first();
                        if ($productInSalida) {
                            $quantity = $productInSalida->pivot->quantity;
                            $stock -= $quantity;
                        }
                    }

                    // Descontar transferencias salientes (en tránsito o recibidas)
                    $inTransitTransfers = TransferOrder::whereIn('status', ['en_transito', 'Pending', 'pending', 'recibido'])
                        ->where('warehouse_from_id', $data['warehouse_from_id'])
                        ->whereHas('products', function ($query) use ($product) {
                            $query->where('products.id', $product->id);
                        })
                        ->with([
                            'products' => function ($query) use ($product) {
                                $query->where('products.id', $product->id)->withPivot('quantity', 'sheets_per_box');
                            }
                        ])
                        ->get();

                    foreach ($inTransitTransfers as $transfer) {
                        $productInTransfer = $transfer->products->first();
                        if ($productInTransfer) {
                            $quantity = $productInTransfer->pivot->quantity;
                            // Si el producto se mide en cajas, convertir a unidades
                            if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                                // Usar sheets_per_box del pivot si existe, sino usar unidades_por_caja del producto
                                $sheetsPerBoxTransfer = $productInTransfer->pivot->sheets_per_box ?? $product->unidades_por_caja;
                                $quantity = $quantity * $sheetsPerBoxTransfer;
                            }
                            $stock -= $quantity;
                        }
                    }

                    // Validar stock disponible
                    if ($stock < $unidadesADescontar) {
                        DB::rollBack();
                        $cajasDisponibles = $product->tipo_medida === 'caja' && $product->unidades_por_caja > 0
                            ? floor($stock / $product->unidades_por_caja)
                            : 0;
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$cajasDisponibles} cajas ({$stock} unidades).")->withInput();
                    }
                }

                $productsToAttach[] = [
                    'product_id' => $productData['product_id'],
                    'container_id' => $productData['container_id'],
                    'quantity' => $productData['quantity'],
                    'unidades_a_descontar' => $unidadesADescontar,
                    'sheets_per_box' => $sheetsPerBox > 0 ? $sheetsPerBox : null,
                    'weight_per_box' => $pivot->weight_per_box ?? 0,
                ];
            }

            $useExternalDriver = !empty($data['use_external_driver']);
            $driverId = $useExternalDriver ? null : ($data['driver_id'] ?? null);
            if (!$useExternalDriver && !$driverId) {
                DB::rollBack();
                return back()->with('error', "Debe seleccionar un conductor o marcar conductor externo.")->withInput();
            }
            if (!$useExternalDriver) {
                $driver = \App\Models\Driver::find($driverId);
                if (!$driver) {
                    DB::rollBack();
                    return back()->with('error', "El conductor seleccionado no existe.")->withInput();
                }
            }

            // Obtener las ciudades de las bodegas
            $warehouseFrom = Warehouse::find($data['warehouse_from_id']);
            $warehouseTo = Warehouse::find($data['warehouse_to_id']);

            // Crear la transferencia
            $transfer = TransferOrder::create([
                'warehouse_from_id' => $data['warehouse_from_id'],
                'warehouse_to_id' => $data['warehouse_to_id'],
                'salida' => $warehouseFrom->ciudad ?? $warehouseFrom->nombre,
                'destino' => $warehouseTo->ciudad ?? $warehouseTo->nombre,
                'status' => 'en_transito',
                'date' => now(),
                'note' => $data['note'] ?? null,
                'driver_id' => $driverId,
                'external_driver_name' => $useExternalDriver ? ($data['external_driver_name'] ?? null) : null,
                'external_driver_identity' => $useExternalDriver ? ($data['external_driver_identity'] ?? null) : null,
                'external_driver_plate' => $useExternalDriver ? ($data['external_driver_plate'] ?? null) : null,
                'external_driver_phone' => $useExternalDriver ? ($data['external_driver_phone'] ?? null) : null,
                'aprobo' => $data['aprobo'] ?? null,
                'ciudad_destino' => $data['ciudad_destino'] ?? null,
            ]);

            // Descontar stock y asociar productos
            foreach ($productsToAttach as $index => $item) {
                // PASO 1: Descontar del contenedor (solo si es desde bodegas que reciben contenedores)
                if (Warehouse::bodegaRecibeContenedores($data['warehouse_from_id'])) {
                    $query = DB::table('container_product')
                        ->where('container_id', $item['container_id'])
                        ->where('product_id', $item['product_id']);

                    if (!empty($item['sheets_per_box'])) {
                        $query->where('sheets_per_box', $item['sheets_per_box']);
                    }

                    $rowsAffected = $query->decrement('boxes', $item['quantity']);

                    if ($rowsAffected === 0) {
                        DB::rollBack();
                        return back()->with('error', "Error al descontar del contenedor para el producto #" . ($index + 1))->withInput();
                    }

                    \Log::info('TRANSFER store - Descontado del contenedor', [
                        'container_id' => $item['container_id'],
                        'product_id' => $item['product_id'],
                        'sheets_per_box' => $item['sheets_per_box'] ?? 'N/A',
                        'cajas_descontadas' => $item['quantity'],
                        'rows_affected' => $rowsAffected
                    ]);
                }

                // PASO 2: Los productos son globales - el stock se calcula dinámicamente
                // No necesitamos descontar del stock del producto aquí
                // Solo descontamos del contenedor si es bodega que recibe contenedores (ya hecho arriba)

                \Log::info('TRANSFER store - Descontado del producto', [
                    'product_id' => $item['product_id'],
                    'unidades_descontadas' => $item['unidades_a_descontar'],
                    'rows_affected' => $rowsAffected ?? 0 // rowsAffected only exists if first block ran
                ]);

                // Asociar producto a la transferencia
                $transfer->products()->attach($item['product_id'], [
                    'quantity' => $item['quantity'],
                    'container_id' => $item['container_id'],
                    'sheets_per_box' => $item['sheets_per_box'] ?? null,
                    'weight_per_box' => $item['weight_per_box'] ?? 0
                ]);
            }

            DB::commit();
            \Log::info('TRANSFER store - Transferencia creada exitosamente', ['transfer_id' => $transfer->id]);

            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia creada correctamente.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('TRANSFER store - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', "Error al crear la transferencia: " . $e->getMessage())->withInput();
        }
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
    public function edit(TransferOrder $transferOrder)
    {
        $user = Auth::user();

        // Validar permisos según el rol
        if ($user->rol === 'clientes') {
            $user->load('almacenes');
            $bodegasPermitidas = $user->almacenes->pluck('id')->toArray();

            if (!in_array($transferOrder->warehouse_from_id, $bodegasPermitidas)) {
                return redirect()->route('transfer-orders.index')
                    ->with('error', 'No tienes permiso para editar esta transferencia.');
            }
        } elseif ($user->rol !== 'admin' && $transferOrder->warehouse_from_id !== $user->almacen_id) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para editar esta transferencia.');
        }
        if ($transferOrder->status !== 'en_transito') {
            return redirect()->route('transfer-orders.index')->with('error', 'Solo se pueden editar transferencias en tránsito.');
        }
        $transferOrder->load(['products' => function ($q) {
            $q->withPivot('quantity', 'container_id', 'good_sheets', 'bad_sheets', 'receive_by', 'sheets_per_box', 'weight_per_box');
        }]);
        $warehouses = Warehouse::orderBy('nombre')->get();
        $products = Product::with('containers')->orderBy('nombre')->get();
        $drivers = \App\Models\Driver::activeWithValidSocialSecurity()->orderBy('name')->get();
        return view('transfer-orders.edit', compact('transferOrder', 'warehouses', 'products', 'drivers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TransferOrder $transferOrder)
    {
        $user = Auth::user();
        if ($user->rol !== 'admin' && $transferOrder->warehouse_from_id !== $user->almacen_id) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para editar esta transferencia.');
        }
        if ($transferOrder->status !== 'en_transito') {
            return redirect()->route('transfer-orders.index')->with('error', 'Solo se pueden editar transferencias en tránsito.');
        }

        $data = $request->validate([
            'warehouse_from_id' => 'required|different:warehouse_to_id|exists:warehouses,id',
            'warehouse_to_id' => 'required|exists:warehouses,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.container_id' => 'required|exists:containers,id',
            'products.*.quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
            'use_external_driver' => 'nullable|boolean',
            'driver_id' => 'nullable|required_without:use_external_driver|exists:drivers,id',
            'external_driver_name' => 'nullable|required_if:use_external_driver,1|string|max:255',
            'external_driver_identity' => 'nullable|required_if:use_external_driver,1|string|max:50',
            'external_driver_plate' => 'nullable|required_if:use_external_driver,1|string|max:50',
            'external_driver_phone' => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            $almacenOrigenAnterior = $transferOrder->warehouse_from_id;
            $useExternalDriver = !empty($data['use_external_driver']);
            $driverId = $useExternalDriver ? null : ($data['driver_id'] ?? null);
            if (!$useExternalDriver && !$driverId) {
                DB::rollBack();
                return back()->with('error', "Debe seleccionar un conductor o marcar conductor externo.")->withInput();
            }
            if (!$useExternalDriver) {
                $driver = \App\Models\Driver::find($driverId);
                if (!$driver) {
                    DB::rollBack();
                    return back()->with('error', "El conductor seleccionado no existe.")->withInput();
                }
            }

            // Los productos son globales - restaurar solo las cajas del contenedor si es necesario
            // Los productos son globales - restaurar solo las cajas del contenedor si es necesario
            foreach ($transferOrder->products as $oldProduct) {
                $prodAnterior = Product::where('id', $oldProduct->id)
                    ->lockForUpdate()
                    ->first();

                // Si es desde bodegas que reciben contenedores, restaurar las cajas del contenedor
                if ($prodAnterior && Warehouse::bodegaRecibeContenedores($almacenOrigenAnterior) && $oldProduct->pivot->container_id) {
                    $query = DB::table('container_product')
                        ->where('container_id', $oldProduct->pivot->container_id)
                        ->where('product_id', $oldProduct->id);

                    if (isset($oldProduct->pivot->sheets_per_box) && $oldProduct->pivot->sheets_per_box > 0) {
                        $query->where('sheets_per_box', $oldProduct->pivot->sheets_per_box);
                    }

                    $query->increment('boxes', $oldProduct->pivot->quantity);
                }
            }

            // Validar y preparar nuevos productos
            $productsToAttach = [];
            foreach ($data['products'] as $index => $productData) {
                // Los productos son globales, buscar solo por ID
                $product = Product::where('id', $productData['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El producto no existe.")->withInput();
                }

                // Validar contenedor
                $container = \App\Models\Container::find($productData['container_id']);
                if (!$container) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El contenedor no existe.")->withInput();
                }

                // Validar que el producto esté en el contenedor
                $sheetsPerBox = $productData['sheets_per_box'] ?? 0;
                $productInContainerQuery = $container->products()->where('products.id', $productData['product_id']);

                // Si se especificó láminas por caja, filtrar por ello
                if ($sheetsPerBox > 0) {
                    $productInContainerQuery->wherePivot('sheets_per_box', $sheetsPerBox);
                }

                $productInContainer = $productInContainerQuery->first();

                if (!$productInContainer) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El producto no está asociado al contenedor seleccionado con esa cantidad de láminas.")->withInput();
                }

                // Validar que desde bodegas que reciben contenedores solo se despachen Cajas
                if (Warehouse::bodegaRecibeContenedores($data['warehouse_from_id']) && $product->tipo_medida !== 'caja') {
                    DB::rollBack();
                    $warehouse = Warehouse::find($data['warehouse_from_id']);
                    return back()->with('error', "Producto #" . ($index + 1) . ": Desde " . ($warehouse ? $warehouse->nombre : 'bodegas que reciben contenedores') . " solo se pueden despachar productos medidos en Cajas.")->withInput();
                }

                // Calcular unidades a descontar
                $unidadesADescontar = $productData['quantity'];
                // Si es caja, usar sheets_per_box del contenedor si existe, o el del producto
                if ($product->tipo_medida === 'caja') {
                    $units = ($sheetsPerBox > 0) ? $sheetsPerBox : ($product->unidades_por_caja > 0 ? $product->unidades_por_caja : 1);
                    $unidadesADescontar = $productData['quantity'] * $units;
                }

                // Validar stock según el tipo de bodega
                if (Warehouse::bodegaRecibeContenedores($data['warehouse_from_id'])) {
                    // Para bodegas que reciben contenedores, validar cajas en contenedor
                    $pivotQuery = DB::table('container_product')
                        ->join('containers', 'container_product.container_id', '=', 'containers.id')
                        ->where('container_product.container_id', $productData['container_id'])
                        ->where('container_product.product_id', $productData['product_id'])
                        ->where('containers.warehouse_id', $data['warehouse_from_id']);

                    if ($sheetsPerBox > 0) {
                        $pivotQuery->where('container_product.sheets_per_box', $sheetsPerBox);
                    }

                    $pivot = $pivotQuery->select('container_product.boxes', 'container_product.weight_per_box')
                        ->lockForUpdate()
                        ->first();

                    if (!$pivot) {
                        DB::rollBack();
                        return back()->with('error', "Producto #" . ($index + 1) . ": El producto no está asociado al contenedor o el contenedor no pertenece a esta bodega.")->withInput();
                    }

                    if ($pivot->boxes < $productData['quantity']) {
                        DB::rollBack();
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): No hay suficientes cajas en el contenedor. Disponible: {$pivot->boxes} cajas.")->withInput();
                    }
                } else {
                    // Para otras bodegas, calcular stock desde transferencias recibidas menos salidas
                    $stock = 0;

                    // Sumar transferencias recibidas
                    $receivedTransfers = TransferOrder::where('status', 'recibido')
                        ->where('warehouse_to_id', $data['warehouse_from_id'])
                        ->whereHas('products', function ($query) use ($product) {
                            $query->where('products.id', $product->id);
                        })
                        ->with([
                            'products' => function ($query) use ($product) {
                                $query->where('products.id', $product->id)->withPivot('quantity', 'good_sheets', 'bad_sheets', 'receive_by');
                            }
                        ])
                        ->get();

                    foreach ($receivedTransfers as $transfer) {
                        $productInTransfer = $transfer->products->first();
                        if ($productInTransfer) {
                            // Usar good_sheets si está disponible, sino usar quantity (para compatibilidad)
                            $goodSheets = $productInTransfer->pivot->good_sheets;
                            $receiveBy = $productInTransfer->pivot->receive_by ?? 'laminas'; // Por defecto 'laminas' para transferencias antiguas

                            if ($goodSheets !== null) {
                                if ($receiveBy === 'cajas') {
                                    // good_sheets contiene cajas recibidas, convertir a láminas para el stock
                                    if ($product->unidades_por_caja > 0) {
                                        $stock += $goodSheets * $product->unidades_por_caja;
                                    } else {
                                        $stock += $goodSheets; // Si no hay unidades_por_caja, asumir 1:1
                                    }
                                } else {
                                    // good_sheets contiene láminas recibidas
                                    $stock += $goodSheets;
                                }
                            } else {
                                // Transferencia antigua sin good_sheets
                                $quantity = $productInTransfer->pivot->quantity;
                                if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                                    $quantity = $quantity * $product->unidades_por_caja;
                                }
                                $stock += $quantity;
                            }
                        }
                    }

                    // Descontar salidas
                    $salidas = \App\Models\Salida::where('warehouse_id', $data['warehouse_from_id'])
                        ->whereHas('products', function ($query) use ($product) {
                            $query->where('products.id', $product->id);
                        })
                        ->with([
                            'products' => function ($query) use ($product) {
                                $query->where('products.id', $product->id)->withPivot('quantity');
                            }
                        ])
                        ->get();

                    foreach ($salidas as $salida) {
                        $productInSalida = $salida->products->first();
                        if ($productInSalida) {
                            // Las salidas ya se guardan en láminas (unidades), no en cajas
                            // No necesitamos convertir porque la cantidad ya está en unidades
                            $quantity = $productInSalida->pivot->quantity;
                            $stock -= $quantity;
                        }
                    }

                    // Descontar transferencias salientes (en tránsito o recibidas) excpepto la actual
                    $inTransitTransfers = TransferOrder::whereIn('status', ['en_transito', 'Pending', 'pending', 'recibido'])
                        ->where('warehouse_from_id', $data['warehouse_from_id'])
                        ->where('id', '!=', $transferOrder->id) // Excluir la transferencia actual
                        ->whereHas('products', function ($query) use ($product) {
                            $query->where('products.id', $product->id);
                        })
                        ->with([
                            'products' => function ($query) use ($product) {
                                $query->where('products.id', $product->id)->withPivot('quantity', 'sheets_per_box');
                            }
                        ])
                        ->get();

                    foreach ($inTransitTransfers as $transfer) {
                        $productInTransfer = $transfer->products->first();
                        if ($productInTransfer) {
                            $quantity = $productInTransfer->pivot->quantity;
                            // Si el producto se mide en cajas, convertir a unidades
                            if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                                // Usar sheets_per_box del pivot si existe, sino usar unidades_por_caja del producto
                                $sheetsPerBoxTransfer = $productInTransfer->pivot->sheets_per_box ?? $product->unidades_por_caja;
                                $quantity = $quantity * $sheetsPerBoxTransfer;
                            }
                            $stock -= $quantity;
                        }
                    }

                    // Validar stock disponible
                    if ($stock < $unidadesADescontar) {
                        DB::rollBack();
                        $cajasDisponibles = $product->tipo_medida === 'caja' && $product->unidades_por_caja > 0
                            ? floor($stock / $product->unidades_por_caja)
                            : 0;
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$cajasDisponibles} cajas ({$stock} unidades).")->withInput();
                    }
                }

                $productsToAttach[] = [
                    'product_id' => $productData['product_id'],
                    'container_id' => $productData['container_id'],
                    'quantity' => $productData['quantity'],
                    'unidades_a_descontar' => $unidadesADescontar,
                    'sheets_per_box' => $sheetsPerBox > 0 ? $sheetsPerBox : null,
                    'weight_per_box' => $pivot->weight_per_box ?? 0,
                ];
            }

            // Obtener las ciudades de las bodegas
            $warehouseFrom = Warehouse::find($data['warehouse_from_id']);
            $warehouseTo = Warehouse::find($data['warehouse_to_id']);

            // Actualizar la transferencia
            $transferOrder->update([
                'warehouse_from_id' => $data['warehouse_from_id'],
                'warehouse_to_id' => $data['warehouse_to_id'],
                'salida' => $warehouseFrom->ciudad ?? $warehouseFrom->nombre,
                'destino' => $warehouseTo->ciudad ?? $warehouseTo->nombre,
                'note' => $data['note'] ?? null,
                'driver_id' => $driverId,
                'external_driver_name' => $useExternalDriver ? ($data['external_driver_name'] ?? null) : null,
                'external_driver_identity' => $useExternalDriver ? ($data['external_driver_identity'] ?? null) : null,
                'external_driver_plate' => $useExternalDriver ? ($data['external_driver_plate'] ?? null) : null,
                'external_driver_phone' => $useExternalDriver ? ($data['external_driver_phone'] ?? null) : null,
            ]);

            // Descontar stock y asociar productos
            $transferOrder->products()->detach(); // Eliminar relaciones anteriores

            foreach ($productsToAttach as $index => $item) {
                // PASO 1: Descontar del contenedor (solo si es desde bodegas que reciben contenedores)
                if (Warehouse::bodegaRecibeContenedores($data['warehouse_from_id'])) {
                    $query = DB::table('container_product')
                        ->where('container_id', $item['container_id'])
                        ->where('product_id', $item['product_id']);

                    if (!empty($item['sheets_per_box'])) {
                        $query->where('sheets_per_box', $item['sheets_per_box']);
                    }

                    $rowsAffected = $query->decrement('boxes', $item['quantity']);

                    if ($rowsAffected === 0) {
                        DB::rollBack();
                        return back()->with('error', "Error al descontar del contenedor para el producto #" . ($index + 1))->withInput();
                    }
                }

                // PASO 2: Los productos son globales - el stock se calcula dinámicamente

                // Asociar producto a la transferencia
                $transferOrder->products()->attach($item['product_id'], [
                    'quantity' => $item['quantity'],
                    'container_id' => $item['container_id'],
                    'sheets_per_box' => $item['sheets_per_box'] ?? null,
                    'weight_per_box' => $item['weight_per_box'] ?? 0
                ]);
            }

            DB::commit();
            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia actualizada correctamente.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('TRANSFER update - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return back()->with('error', "Error al actualizar la transferencia: " . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(TransferOrder $transferOrder)
    {
        $user = Auth::user();

        // Validar permisos según el rol
        if ($user->rol === 'clientes') {
            $user->load('almacenes');
            $bodegasPermitidas = $user->almacenes->pluck('id')->toArray();

            if (!in_array($transferOrder->warehouse_from_id, $bodegasPermitidas)) {
                return redirect()->route('transfer-orders.index')
                    ->with('error', 'No tienes permiso para eliminar esta transferencia.');
            }
        } elseif ($user->rol !== 'admin' && $transferOrder->warehouse_from_id !== $user->almacen_id) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para eliminar esta transferencia.');
        }
        if ($transferOrder->status !== 'en_transito') {
            return redirect()->route('transfer-orders.index')->with('error', 'Solo se pueden eliminar transferencias en tránsito.');
        }

        DB::beginTransaction();
        try {
            // Los productos son globales - restaurar solo las cajas del contenedor si es necesario
            foreach ($transferOrder->products as $product) {
                $prod = Product::where('id', $product->id)
                    ->lockForUpdate()
                    ->first();

                // Si es desde bodegas que reciben contenedores, restaurar las cajas del contenedor
                if ($prod && Warehouse::bodegaRecibeContenedores($transferOrder->warehouse_from_id) && $product->pivot->container_id) {
                    $query = DB::table('container_product')
                        ->where('container_id', $product->pivot->container_id)
                        ->where('product_id', $product->id);

                    if (isset($product->pivot->sheets_per_box) && $product->pivot->sheets_per_box > 0) {
                        $query->where('sheets_per_box', $product->pivot->sheets_per_box);
                    }

                    $query->increment('boxes', $product->pivot->quantity);
                }
            }

            $transferOrder->delete();

            DB::commit();
            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia eliminada correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('TRANSFER destroy - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return back()->with('error', "Error al eliminar la transferencia: " . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para confirmar recepción de transferencia
     */
    public function showConfirmForm(TransferOrder $transferOrder)
    {
        $user = Auth::user();
        // Solo se puede confirmar si la transferencia está en tránsito
        if ($transferOrder->status !== 'en_transito') {
            return redirect()->route('transfer-orders.index')->with('error', 'Esta transferencia ya fue procesada.');
        }

        // Verificar permisos
        $canConfirm = false;
        $errorMessage = 'Solo se puede confirmar la recepción en la bodega destino.';

        if (in_array($user->rol, ['admin', 'funcionario'])) {
            $canConfirm = true;
        } elseif ($user->rol === 'clientes') {
            if (!$user->relationLoaded('almacenes')) {
                $user->load('almacenes');
            }
            $bodegasAsignadasIds = $user->almacenes->pluck('id')->toArray();
            $canConfirm = in_array($transferOrder->warehouse_to_id, $bodegasAsignadasIds);
            if (!$canConfirm) {
                $errorMessage = 'Solo puedes confirmar transferencias hacia tus bodegas asignadas.';
            }
        } else {
            $canConfirm = $user->almacen_id == $transferOrder->warehouse_to_id;
            if (!$canConfirm) {
                $errorMessage = 'Solo puedes confirmar transferencias hacia tu bodega asignada.';
            }
        }

        if (!$canConfirm) {
            return redirect()->route('transfer-orders.index')->with('error', $errorMessage);
        }

        $transferOrder->load([
            'from',
            'to',
            'products' => function ($query) {
                $query->withPivot('quantity', 'container_id');
            },
            'driver'
        ]);

        return view('transfer-orders.confirm', compact('transferOrder'));
    }

    /**
     * Confirmar recepción de transferencia
     */
    public function confirmReceived(Request $request, TransferOrder $transferOrder)
    {
        $user = Auth::user();
        // Solo se puede confirmar si la transferencia está en tránsito
        if ($transferOrder->status !== 'en_transito') {
            return redirect()->route('transfer-orders.index')->with('error', 'Esta transferencia ya fue procesada.');
        }

        // Verificar permisos
        $canConfirm = false;
        $errorMessage = 'Solo se puede confirmar la recepción en la bodega destino.';

        if (in_array($user->rol, ['admin', 'funcionario'])) {
            $canConfirm = true;
        } elseif ($user->rol === 'clientes') {
            if (!$user->relationLoaded('almacenes')) {
                $user->load('almacenes');
            }
            $bodegasAsignadasIds = $user->almacenes->pluck('id')->toArray();
            $canConfirm = in_array($transferOrder->warehouse_to_id, $bodegasAsignadasIds);
            if (!$canConfirm) {
                $errorMessage = 'Solo puedes confirmar transferencias hacia tus bodegas asignadas.';
            }
        } else {
            $canConfirm = $user->almacen_id == $transferOrder->warehouse_to_id;
            if (!$canConfirm) {
                $errorMessage = 'Solo puedes confirmar transferencias hacia tu bodega asignada.';
            }
        }

        if (!$canConfirm) {
            return redirect()->route('transfer-orders.index')->with('error', $errorMessage);
        }

        // Validar datos
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.good_sheets' => 'required|integer|min:0',
            'products.*.bad_sheets' => 'required|integer|min:0',
            'products.*.receive_by' => 'required|in:cajas,laminas',
        ]);

        $transferOrder->load('products');

        DB::beginTransaction();
        try {
            // Actualizar cada producto con las láminas buenas y malas
            foreach ($validated['products'] as $productData) {
                $productId = $productData['product_id'];
                $goodSheets = $productData['good_sheets'];
                $badSheets = $productData['bad_sheets'];

                // Verificar que el producto esté en la transferencia
                $productInTransfer = $transferOrder->products->find($productId);
                if (!$productInTransfer) {
                    throw new \Exception("El producto con ID {$productId} no está en esta transferencia.");
                }

                // Verificar que la suma de buenas y malas no exceda la cantidad enviada
                $totalReceived = $goodSheets + $badSheets;
                $quantitySent = $productInTransfer->pivot->quantity;
                $receiveBy = $productData['receive_by'];

                // Obtener el producto para validar según la forma de recepción
                $product = Product::find($productId);

                // Calcular el máximo según la forma de recepción
                if ($receiveBy === 'cajas') {
                    // Si reciben por cajas, validar contra las cajas enviadas
                    // Si el producto tiene tipo_medida 'caja', quantitySent ya está en cajas
                    // Si no, quantitySent está en láminas y debemos convertir a cajas
                    if ($product && $product->tipo_medida === 'caja') {
                        // Ya está en cajas
                        $maxValue = $quantitySent;
                    } else {
                        // Está en láminas, convertir a cajas si hay unidades_por_caja
                        if ($product && $product->unidades_por_caja > 0) {
                            $maxValue = floor($quantitySent / $product->unidades_por_caja);
                        } else {
                            $maxValue = $quantitySent; // Si no hay unidades_por_caja, asumir que es 1:1
                        }
                    }
                    $unitName = 'cajas';
                } else {
                    // Si reciben por láminas, convertir cajas a láminas si es necesario
                    if ($product && $product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                        $maxValue = $quantitySent * $product->unidades_por_caja;
                    } else {
                        $maxValue = $quantitySent;
                    }
                    $unitName = 'láminas';
                }

                if ($totalReceived > $maxValue) {
                    throw new \Exception("La suma de {$unitName} buenas y malas ({$totalReceived}) no puede exceder la cantidad enviada ({$maxValue} {$unitName}) para el producto {$product->nombre}.");
                }

                // Actualizar el pivot con las láminas buenas y malas y la forma de recepción
                DB::table('transfer_order_products')
                    ->where('transfer_order_id', $transferOrder->id)
                    ->where('product_id', $productId)
                    ->update([
                        'good_sheets' => $goodSheets,
                        'bad_sheets' => $badSheets,
                        'receive_by' => $productData['receive_by']
                    ]);
            }

            // Marcar la transferencia como recibida
            $transferOrder->update(['status' => 'recibido']);

            DB::commit();
            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia confirmada correctamente. Solo las láminas en buen estado se agregarán al stock.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('TRANSFER confirmReceived - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return back()->withInput()->with('error', "Error al confirmar la transferencia: " . $e->getMessage());
        }
    }

    /**
     * Exportar transferencia a PDF
     */
    public function export(TransferOrder $transferOrder)
    {
        $user = Auth::user();
        $transferOrder->load([
            'from',
            'to',
            'products' => function ($query) {
                $query->withPivot('quantity', 'container_id', 'sheets_per_box', 'weight_per_box');
            },
            'driver'
        ]);
        $showSignatures = session("transfer_signatures_{$transferOrder->id}", false);
        $currentUser = $user;
        $isExport = true;

        // Generar el PDF de transferencia
        $pdf = \PDF::loadView('transfer-orders.pdf', compact('transferOrder', 'showSignatures', 'currentUser', 'isExport'));

        // Crear directorio temporal si no existe
        $tempDir = storage_path('app/temp_pdfs');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Guardar el PDF de transferencia temporalmente
        $transferPdfPath = $tempDir . '/transfer_' . $transferOrder->id . '_' . time() . '.pdf';
        file_put_contents($transferPdfPath, $pdf->output());

        // Verificar si el conductor tiene PDF de seguridad social
        $socialSecurityPdfPath = null;
        if ($transferOrder->driver && $transferOrder->driver->social_security_pdf) {
            $fullPath = storage_path('app/public/' . $transferOrder->driver->social_security_pdf);
            if (File::exists($fullPath) && filesize($fullPath) > 0) {
                $socialSecurityPdfPath = $fullPath;
            }
        }

        // Si hay PDF de seguridad social, unirlo al PDF de transferencia
        if ($socialSecurityPdfPath) {
            try {
                $merger = new Merger();
                $merger->addFile($transferPdfPath);
                $merger->addFile($socialSecurityPdfPath);

                $mergedPdf = $merger->merge();

                // Limpiar el PDF temporal de transferencia
                @File::delete($transferPdfPath);

                // Devolver el PDF unido
                return response($mergedPdf)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="transferencia_' . $transferOrder->id . '.pdf"');
            } catch (\Exception $e) {
                // Si falla la unión, devolver solo el PDF de transferencia
                \Log::warning('Error al unir PDF de seguridad social: ' . $e->getMessage());
                return response()->file($transferPdfPath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="transferencia_' . $transferOrder->id . '.pdf"'
                ])->deleteFileAfterSend(true);
            }
        }

        // Si no hay PDF de seguridad social, devolver solo el PDF de transferencia
        return response()->file($transferPdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="transferencia_' . $transferOrder->id . '.pdf"'
        ])->deleteFileAfterSend(true);
    }

    /**
     * Imprimir transferencia
     */
    public function print(TransferOrder $transferOrder)
    {
        $user = Auth::user();
        $transferOrder->load([
            'from',
            'to',
            'products' => function ($query) {
                $query->withPivot('quantity', 'container_id', 'sheets_per_box', 'weight_per_box');
            },
            'driver'
        ]);
        $showSignatures = session("transfer_signatures_{$transferOrder->id}", false);
        $currentUser = $user;
        $isExport = true;

        // Generar el PDF de transferencia
        $pdf = \PDF::loadView('transfer-orders.pdf', compact('transferOrder', 'showSignatures', 'currentUser', 'isExport'));

        // Crear directorio temporal si no existe
        $tempDir = storage_path('app/temp_pdfs');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Guardar el PDF de transferencia temporalmente
        $transferPdfPath = $tempDir . '/transfer_' . $transferOrder->id . '_' . time() . '.pdf';
        file_put_contents($transferPdfPath, $pdf->output());

        // Verificar si el conductor tiene PDF de seguridad social
        $socialSecurityPdfPath = null;
        if ($transferOrder->driver && $transferOrder->driver->social_security_pdf) {
            $fullPath = storage_path('app/public/' . $transferOrder->driver->social_security_pdf);
            if (File::exists($fullPath) && filesize($fullPath) > 0) {
                $socialSecurityPdfPath = $fullPath;
            }
        }

        // Si hay PDF de seguridad social, unirlo al PDF de transferencia
        if ($socialSecurityPdfPath) {
            try {
                $merger = new Merger();
                $merger->addFile($transferPdfPath);
                $merger->addFile($socialSecurityPdfPath);

                $mergedPdf = $merger->merge();

                // Limpiar el PDF temporal de transferencia
                @File::delete($transferPdfPath);

                // Devolver el PDF unido para mostrar en el navegador (inline para imprimir)
                return response($mergedPdf)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="transferencia_' . $transferOrder->id . '.pdf"');
            } catch (\Exception $e) {
                // Si falla la unión, devolver solo el PDF de transferencia
                \Log::warning('Error al unir PDF de seguridad social en print: ' . $e->getMessage());
                return response()->file($transferPdfPath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="transferencia_' . $transferOrder->id . '.pdf"'
                ])->deleteFileAfterSend(true);
            }
        }

        // Si no hay PDF de seguridad social, devolver solo el PDF de transferencia
        return response()->file($transferPdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="transferencia_' . $transferOrder->id . '.pdf"'
        ])->deleteFileAfterSend(true);
    }

    /**
     * Obtener productos disponibles en una bodega específica
     */
    public function getProductsForWarehouse(Request $request, $warehouseId)
    {
        $warehouseId = (int) $warehouseId;
        $warehouse = Warehouse::findOrFail($warehouseId);
        $bodegasQueRecibenContenedores = Warehouse::getBodegasQueRecibenContenedores();

        // Si se está editando una transferencia, "recuperar" su stock: sumar las cajas de esa transferencia al disponible
        $forEditTransferId = $request->get('for_edit_transfer_id');
        $transferQuantitiesByProductContainer = []; // [ product_id => [ container_id => ['quantity' => q, 'sheets_per_box' => s] ] ]
        if ($forEditTransferId && in_array($warehouseId, $bodegasQueRecibenContenedores)) {
            $editTransfer = TransferOrder::with(['products' => function ($q) {
                $q->withPivot('container_id', 'quantity', 'sheets_per_box');
            }])->find($forEditTransferId);
            if ($editTransfer && $editTransfer->warehouse_from_id == $warehouseId) {
                foreach ($editTransfer->products as $p) {
                    $pid = $p->id;
                    $cid = $p->pivot->container_id;
                    if (!isset($transferQuantitiesByProductContainer[$pid])) {
                        $transferQuantitiesByProductContainer[$pid] = [];
                    }
                    $transferQuantitiesByProductContainer[$pid][$cid] = [
                        'quantity' => (int) ($p->pivot->quantity ?? 0),
                        'sheets_per_box' => $p->pivot->sheets_per_box ?? 0,
                    ];
                }
            }
        }

        // Obtener todos los productos globales
        $allProducts = Product::whereNull('almacen_id')
            ->with([
                'containers' => function ($query) use ($warehouseId) {
                    $query->where('containers.warehouse_id', $warehouseId);
                }
            ])
            ->orderBy('nombre')
            ->get();

        $productsWithStock = [];

        foreach ($allProducts as $product) {
            $stock = 0;

            if (in_array($warehouseId, $bodegasQueRecibenContenedores)) {
                // Bodega que recibe contenedores: stock desde container_product
                $containerProducts = DB::table('container_product')
                    ->join('containers', 'container_product.container_id', '=', 'containers.id')
                    ->where('container_product.product_id', $product->id)
                    ->where('containers.warehouse_id', $warehouseId)
                    ->select('container_product.boxes', 'container_product.sheets_per_box')
                    ->get();

                foreach ($containerProducts as $cp) {
                    $stock += ($cp->boxes ?? 0) * ($cp->sheets_per_box ?? 0);
                }

                // Descontar salidas para bodegas que reciben contenedores
                $salidas = \App\Models\Salida::where('warehouse_id', $warehouseId)
                    ->whereHas('products', function ($query) use ($product) {
                        $query->where('products.id', $product->id);
                    })
                    ->with([
                        'products' => function ($query) use ($product) {
                            $query->where('products.id', $product->id)->withPivot('quantity');
                        }
                    ])
                    ->get();

                foreach ($salidas as $salida) {
                    $productInSalida = $salida->products->first();
                    if ($productInSalida) {
                        // Las salidas ya se guardan en láminas (unidades), no en cajas
                        // No necesitamos convertir porque la cantidad ya está en unidades
                        $quantity = $productInSalida->pivot->quantity;
                        $stock -= $quantity;
                    }
                }
            } else {
                // Otra bodega: stock desde transferencias recibidas menos salidas
                $receivedTransfers = TransferOrder::where('status', 'recibido')
                    ->where('warehouse_to_id', $warehouseId)
                    ->whereHas('products', function ($query) use ($product) {
                        $query->where('products.id', $product->id);
                    })
                    ->with([
                        'products' => function ($query) use ($product) {
                            $query->where('products.id', $product->id)->withPivot('quantity', 'good_sheets', 'bad_sheets', 'receive_by');
                        }
                    ])
                    ->get();

                foreach ($receivedTransfers as $transfer) {
                    $productInTransfer = $transfer->products->first();
                    if ($productInTransfer) {
                        // Usar good_sheets si está disponible, sino usar quantity (para compatibilidad con transferencias antiguas)
                        $goodSheets = $productInTransfer->pivot->good_sheets;
                        $receiveBy = $productInTransfer->pivot->receive_by ?? 'laminas'; // Por defecto 'laminas' para transferencias antiguas

                        if ($goodSheets !== null) {
                            if ($receiveBy === 'cajas') {
                                // good_sheets contiene cajas recibidas, convertir a láminas para el stock
                                if ($product->unidades_por_caja > 0) {
                                    $stock += $goodSheets * $product->unidades_por_caja;
                                } else {
                                    $stock += $goodSheets; // Si no hay unidades_por_caja, asumir 1:1
                                }
                            } else {
                                // good_sheets contiene láminas recibidas
                                $stock += $goodSheets;
                            }
                        } else {
                            // Transferencia antigua sin good_sheets, usar quantity
                            $quantity = $productInTransfer->pivot->quantity;
                            // Si es tipo caja, convertir a unidades
                            if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                                $quantity = $quantity * $product->unidades_por_caja;
                            }
                            $stock += $quantity;
                        }
                    }
                }

                // Descontar salidas
                $salidas = \App\Models\Salida::where('warehouse_id', $warehouseId)
                    ->whereHas('products', function ($query) use ($product) {
                        $query->where('products.id', $product->id);
                    })
                    ->with([
                        'products' => function ($query) use ($product) {
                            $query->where('products.id', $product->id)->withPivot('quantity');
                        }
                    ])
                    ->get();

                foreach ($salidas as $salida) {
                    $productInSalida = $salida->products->first();
                    if ($productInSalida) {
                        $quantity = $productInSalida->pivot->quantity;
                        if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                            $quantity = $quantity * $product->unidades_por_caja;
                        }
                        $stock -= $quantity;
                    }
                }

                // Descontar transferencias en tránsito desde esta bodega
                $inTransitTransfers = TransferOrder::whereIn('status', ['en_transito', 'Pending', 'pending'])
                    ->where('warehouse_from_id', $warehouseId)
                    ->whereHas('products', function ($query) use ($product) {
                        $query->where('products.id', $product->id);
                    })
                    ->with([
                        'products' => function ($query) use ($product) {
                            $query->where('products.id', $product->id)->withPivot('quantity', 'sheets_per_box');
                        }
                    ])
                    ->get();

                foreach ($inTransitTransfers as $transfer) {
                    $productInTransfer = $transfer->products->first();
                    if ($productInTransfer) {
                        $quantity = $productInTransfer->pivot->quantity;
                        // Si el producto se mide en cajas, convertir a unidades
                        if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                            // Usar sheets_per_box del pivot si existe, sino usar unidades_por_caja del producto
                            $sheetsPerBoxTransfer = $productInTransfer->pivot->sheets_per_box ?? $product->unidades_por_caja;
                            $quantity = $quantity * $sheetsPerBoxTransfer;
                        }
                        $stock -= $quantity;
                    }
                }
            }

            // Para bodegas que reciben contenedores, crear una entrada por cada combinación producto-contenedor
            if (in_array($warehouseId, $bodegasQueRecibenContenedores)) {
                $containerIdsInTransfer = isset($transferQuantitiesByProductContainer[$product->id])
                    ? array_keys($transferQuantitiesByProductContainer[$product->id])
                    : [];
                $containerProductsQuery = DB::table('container_product')
                    ->join('containers', 'container_product.container_id', '=', 'containers.id')
                    ->where('container_product.product_id', $product->id)
                    ->where('containers.warehouse_id', $warehouseId);
                if (!empty($containerIdsInTransfer)) {
                    $containerProductsQuery->where(function ($q) use ($containerIdsInTransfer) {
                        $q->where('container_product.boxes', '>', 0)
                            ->orWhereIn('container_product.container_id', $containerIdsInTransfer);
                    });
                } else {
                    $containerProductsQuery->where('container_product.boxes', '>', 0);
                }
                $containerProducts = $containerProductsQuery
                    ->select('container_product.container_id', 'container_product.boxes', 'container_product.sheets_per_box', 'container_product.weight_per_box', 'containers.reference')
                    ->get();

                // Al editar una transferencia: cajas efectivas = cajas en BD + cajas que tiene esa transferencia (recuperar stock)
                $transferQtys = $transferQuantitiesByProductContainer[$product->id] ?? [];

                $stockTotalSinDescontar = 0;
                foreach ($containerProducts as $cp2) {
                    $boxesEffective = ($cp2->boxes ?? 0);
                    if (isset($transferQtys[$cp2->container_id])) {
                        $boxesEffective += $transferQtys[$cp2->container_id]['quantity'];
                    }
                    $stockTotalSinDescontar += $boxesEffective * ($cp2->sheets_per_box ?? 0);
                }

                foreach ($containerProducts as $cp) {
                    $boxesEnBd = (int) ($cp->boxes ?? 0);
                    $qtyEnTransferencia = isset($transferQtys[$cp->container_id]) ? $transferQtys[$cp->container_id]['quantity'] : 0;
                    $cajasEfectivas = $boxesEnBd + $qtyEnTransferencia;

                    $stockContenedor = $cajasEfectivas * ($cp->sheets_per_box ?? 0);
                    if ($stockContenedor > 0) {
                        $stockContenedorFinal = $stockContenedor;
                        if ($stockTotalSinDescontar > 0 && $stock < $stockTotalSinDescontar) {
                            $salidasTotales = $stockTotalSinDescontar - $stock;
                            $proporcion = $stockContenedor / $stockTotalSinDescontar;
                            $salidasDelContenedor = $salidasTotales * $proporcion;
                            $stockContenedorFinal = max(0, $stockContenedor - $salidasDelContenedor);
                        }

                        // Solo ofrecer producto si hay stock disponible (coherente con Stock y Salidas: 0 cajas/0 láminas = no usable)
                        if ($stockContenedorFinal <= 0) {
                            continue;
                        }

                        $sheetsPerBox = $cp->sheets_per_box ?? $product->unidades_por_caja ?? 1;
                        $cajasDisponibles = $sheetsPerBox > 0 ? (int) floor($stockContenedorFinal / $sheetsPerBox) : 0;

                        $productsWithStock[] = [
                            'id' => $product->id,
                            'nombre' => $product->nombre,
                            'codigo' => $product->codigo,
                            'medidas' => $product->medidas,
                            'tipo_medida' => $product->tipo_medida,
                            'unidades_por_caja' => $product->unidades_por_caja,
                            'stock' => $stockContenedorFinal,
                            'cajas_en_contenedor' => $cajasDisponibles,
                            'sheets_per_box' => $cp->sheets_per_box,
                            'weight_per_box' => $cp->weight_per_box,
                            'containers' => [
                                [
                                    'id' => $cp->container_id,
                                    'reference' => $cp->reference,
                                    'stock' => $stockContenedorFinal,
                                    'boxes' => $cajasDisponibles,
                                    'weight_per_box' => $cp->weight_per_box
                                ]
                            ]
                        ];
                    }
                }
            } else {
                // Para otras bodegas, incluir productos con stock > 0
                if ($stock > 0) {
                    // Obtener contenedores relacionados con este producto en esta bodega
                    $containers = [];
                    // Para otras bodegas, obtener contenedores de transferencias recibidas
                    $transfers = TransferOrder::where('status', 'recibido')
                        ->where('warehouse_to_id', $warehouseId)
                        ->whereHas('products', function ($query) use ($product) {
                            $query->where('products.id', $product->id);
                        })
                        ->with([
                            'products' => function ($query) use ($product) {
                                $query->where('products.id', $product->id)->withPivot('container_id');
                            }
                        ])
                        ->get();

                    $containerIds = [];
                    foreach ($transfers as $transfer) {
                        $productInTransfer = $transfer->products->first();
                        if ($productInTransfer && $productInTransfer->pivot->container_id) {
                            $containerIds[] = $productInTransfer->pivot->container_id;
                        }
                    }

                    if (!empty($containerIds)) {
                        $containersData = \App\Models\Container::whereIn('id', array_unique($containerIds))
                            ->select('id', 'reference')
                            ->get();

                        foreach ($containersData as $container) {
                            // Obtener weight_per_box del container_product original
                            $containerProduct = DB::table('container_product')
                                ->where('container_id', $container->id)
                                ->where('product_id', $product->id)
                                ->first();

                            $weightPerBox = $containerProduct ? $containerProduct->weight_per_box : null;

                            $containers[] = [
                                'id' => $container->id,
                                'reference' => $container->reference,
                                'stock' => $stock, // Agregar stock total del producto
                                'boxes' => $product->tipo_medida === 'caja' && $product->unidades_por_caja > 0
                                    ? floor($stock / $product->unidades_por_caja)
                                    : 0,
                                'weight_per_box' => $weightPerBox
                            ];
                        }
                    }

                    $productsWithStock[] = [
                        'id' => $product->id,
                        'nombre' => $product->nombre,
                        'codigo' => $product->codigo,
                        'medidas' => $product->medidas,
                        'tipo_medida' => $product->tipo_medida,
                        'unidades_por_caja' => $product->unidades_por_caja,
                        'stock' => max(0, $stock),
                        'containers' => $containers
                    ];
                }
            }
        }

        return response()->json($productsWithStock);
    }
}
