# Phase 0 Research: Rol "cliente funcionario"

Decisiones técnicas para introducir el rol `cliente_funcionario` reutilizando al máximo los mecanismos existentes del monolito Laravel. No quedan marcadores `NEEDS CLARIFICATION` (las dos decisiones de alcance se resolvieron en el spec: crear/editar sin borrar; visibilidad limitada a bodegas asignadas).

---

## D1. Identificador interno del rol

- **Decision**: Nuevo valor de string `cliente_funcionario` en `users.rol`. Etiqueta visible: "Cliente Funcionario (Clientes + Contenedores)".
- **Rationale**: El codebase modela roles como un string libre en `users.rol` (sin Spatie ni tabla de roles). La convención de nombres compuestos usa snake_case (`import_viewer`, `proveedor_itr`). `cliente_funcionario` evita colisión con los roles existentes `clientes` y `funcionario`.
- **Alternatives considered**:
  - Reusar `clientes` con un flag booleano `puede_contenedores`: rechazado — agrega una columna/migración y un segundo eje de permisos que el resto del sistema no entiende; el codebase razona por `rol`, no por flags.
  - Paquete de permisos (Spatie): rechazado — introduce una dependencia y un modelo de autorización ajeno al patrón vigente.

## D2. Herencia del alcance de `clientes` (el punto central)

- **Decision**: Agregar a `User` un helper `isCliente(): bool` → `in_array($this->rol, ['clientes', 'cliente_funcionario'], true)`, y reemplazar los chequeos de **scoping/permiso** `=== 'clientes'` dispersos por `->isCliente()`. Añadir además `isClienteFuncionario(): bool` y `assignedWarehouseIds(): array` (IDs de `almacenes`, forzados a int como en `assignedDriverIds()`).
- **Rationale**: El requisito SC-002 exige paridad exacta con `clientes` en todos los módulos heredados. Centralizar el predicado garantiza que ningún sitio de scoping quede sin cubrir y evita divergencia futura. Sigue el patrón ya presente (`isPlacas()`, `assignedDriverIds()`).
- **Alternatives considered**:
  - Duplicar `|| $rol === 'cliente_funcionario'` en cada sitio: rechazado — frágil, propenso a omitir un sitio (rompería el aislamiento por bodega).
  - Normalizar el rol a `clientes` en un middleware: rechazado — perdería la distinción necesaria para habilitar Contenedores y para el formulario de usuarios.

### Inventario de sitios `clientes` y su tratamiento

> 42 ocurrencias en 6 controladores + varias vistas. **No todas se migran al helper**: hay que distinguir tres categorías.

**(A) Sitios de SCOPING/PERMISO → migrar a `->isCliente()`** (el nuevo rol debe entrar a estas ramas):

- `app/Http/Controllers/WelcomeController.php` (dashboard: productos, bodegas, transferencias por bodegas asignadas) — 3 checks.
- `app/Http/Controllers/StockController.php` (stock por bodegas asignadas) — 2 checks.
- `app/Http/Controllers/SalidaController.php` (listado/creación/validación de salidas por bodega) — ~8 checks.
- `app/Http/Controllers/TransferOrderController.php` (listado, permiso de crear, validación de bodegas, edición) — ~13 checks.
- `app/Http/Controllers/TraceabilityController.php` (trazabilidad por bodega) — 2 checks.
- Vistas con visibilidad/datos de cliente: `resources/views/stock/index.blade.php` (`$isCliente`), `resources/views/salidas/create.blade.php` (`$isCliente`), `resources/views/transfer-orders/index.blade.php` (carga de `almacenes`, permiso de acción, ramas de display), `resources/views/traceability/index.blade.php` (selector/hidden de bodega), `resources/views/products/index.blade.php` (`rol !== 'clientes'` para ocultar acciones — debe pasar a "no es alcance cliente").

**(B) Sitios de LISTA DE ROLES / formulario → extender añadiendo el nuevo rol explícitamente** (NO usar el helper):

- `app/Http/Controllers/UserController.php`:
  - Reglas `'rol' => 'required|in:admin,clientes,funcionario,...,placas'` (store y update) → agregar `cliente_funcionario`.
  - Ramas de sincronización de bodegas `if ($request->rol === 'funcionario' || $request->rol === 'clientes')` → incluir `cliente_funcionario` para que use `almacenes()->sync()` con `almacen_id = null`.
  - Ramas de limpieza al cambiar de rol → tratar `cliente_funcionario` igual que `clientes`.
- `resources/views/users/create.blade.php` y `edit.blade.php`: agregar `<option value="cliente_funcionario">`; en el JS `toggleBodegaFields`, incluir `cliente_funcionario` en la rama de `clientes` (muestra grupo "Bodegas" + validación de ≥1).
- `resources/views/users/index.blade.php` (línea 120: `rol === 'funcionario' || rol === 'clientes'`): extender para mostrar las bodegas asignadas del nuevo rol en el listado de usuarios.

**(C) Sitios que NO deben cambiar**: las etiquetas/option de `clientes`, y cualquier comparación cuyo objetivo sea distinguir específicamente al rol `clientes` puro (no hay ninguno que deba excluir al nuevo rol salvo el módulo Contenedores, gobernado aparte).

## D3. Acceso al módulo de Contenedores

- **Decision**: Habilitar Contenedores para `cliente_funcionario` por **navegación** (`layouts/app.blade.php`, nuevo flag `$isClienteFuncionario`) y por **guards inline en `ContainerController`**. No se agrega rama en `BlockImporterAccess` ni se crea una Policy.
- **Rationale**: El rol `clientes` no está confinado por `BlockImporterAccess`; su acceso se gobierna por navegación + scoping per-controller. El módulo Contenedores ya resuelve autorización inline (`funcionario` = solo lectura). Mantener ese estilo es coherente y minimiza superficie.
- **Alternatives considered**:
  - `ContainerPolicy` + `authorize()`: rechazado — el módulo no usa Policies hoy; introducir una solo para este rol genera inconsistencia.
  - Rama en `BlockImporterAccess`: rechazado — ese middleware es para roles *confinados a un único módulo* (importer/itr/placas); `cliente_funcionario` usa varios módulos, no encaja.

## D4. Scoping de contenedores por bodega asignada

- **Decision**: En `ContainerController::index()`, cuando `Auth::user()->isClienteFuncionario()`, filtrar `Container::whereIn('warehouse_id', $user->assignedWarehouseIds())`. En `edit/update/export/print/destroy`, verificar que `$container->warehouse_id ∈ assignedWarehouseIds()`; si no, redirigir a `containers.index` con error (403 lógico). La búsqueda global (`search`) se aplica **dentro** del scope.
- **Rationale**: Contenedores se relacionan con bodegas por `containers.warehouse_id`; las bodegas del usuario son `almacenes`. Doble capa (filtro de listado + guard por acción) cumple SC-004 ante manipulación de URL. Coherente con cómo `clientes` se acota a sus bodegas en otros módulos.
- **Alternatives considered**: solo filtrar el listado (sin guard por acción) — rechazado, deja abierto el acceso directo por ID a contenedores de bodegas ajenas.

## D5. Destino de creación de contenedores

- **Decision**: Para `cliente_funcionario`, el selector de bodega y la validación de `store/update` se limitan a `getBodegasQueRecibenContenedores() ∩ assignedWarehouseIds()`. Si el conjunto es vacío, no puede crear contenedores (mensaje informativo).
- **Rationale**: El módulo ya restringe destinos a "bodegas que reciben contenedores" (Buenaventura/Pablo Rojas) vía `Warehouse::getBodegasQueRecibenContenedores()` y `bodegaRecibeContenedores()`. El nuevo rol añade la restricción adicional de "solo mis bodegas". La intersección respeta ambas reglas.
- **Alternatives considered**: permitir cualquier bodega que reciba contenedores (sin intersección) — rechazado, violaría el aislamiento por bodega del rol.

## D6. Bloqueo de eliminación

- **Decision**: En `ContainerController::destroy()`, rechazar cuando `isClienteFuncionario()` (redirect a `containers.index` con error), análogo al guard `funcionario` ya existente. En `containers/index.blade.php`, ocultar el botón "Eliminar" para el rol.
- **Rationale**: FR-015/SC-005 exigen que el 100% de los intentos de borrado (incluido acceso directo) sean rechazados; el guard de servidor es la garantía, ocultar el botón es UX.
- **Alternatives considered**: solo ocultar el botón — rechazado, no protege ante `DELETE` directo.

## D7. Sin migraciones / reutilización de `user_warehouse`

- **Decision**: Reutilizar la relación `almacenes` (`belongsToMany` sobre `user_warehouse`) que ya emplean `clientes` y `funcionario`. Cero migraciones, cero columnas nuevas.
- **Rationale**: El rol comparte exactamente el modelo de asignación de bodegas de `clientes`. `UserController` ya hace `almacenes()->sync()` para esos roles; basta incluir el nuevo rol en esas ramas.
- **Alternatives considered**: tabla pivote dedicada — innecesaria; la semántica es idéntica a la de `clientes`.

## D8. Estrategia de pruebas

- **Decision**: 3 Feature tests (uno por User Story) sobre MySQL `easy_inventory_test`:
  1. **Gestión del usuario**: crear/editar con rol `cliente_funcionario`, sincronización de `almacenes`, y limpieza de relaciones al cambiar de/ hacia el rol.
  2. **Herencia de alcance clientes**: con bodegas asignadas, verificar que ve los mismos módulos/datos que un `clientes` equivalente y que los módulos negados siguen negados (paralelo a un usuario `clientes` de control).
  3. **Contenedores**: navegación visible; listado scoped por bodega; crear/editar/export/print sobre bodega asignada OK; bodega no asignada denegada; `destroy` rechazado.
- **Rationale**: Cubre los tres User Stories y los Success Criteria medibles. Reutiliza el patrón de los Feature tests de liquidaciones ya presentes.
- **Nota**: Según memoria del proyecto, los Feature tests corren sobre MySQL `easy_inventory_test`; los tests de Breeze Auth/Example fallan de forma pre-existente y no se consideran regresiones.
