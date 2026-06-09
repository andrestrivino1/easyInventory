# Phase 1 Contract: Autorización HTTP del rol `cliente_funcionario`

No se agregan endpoints HTTP nuevos. Este contrato define el **comportamiento de autorización esperado** sobre las rutas existentes para el rol `cliente_funcionario`, comparado con los roles adyacentes. Es la base para los Feature tests.

Convenciones:
- ✅ Permitido (con scoping por bodegas asignadas cuando aplica).
- 🔒 Permitido solo si el recurso pertenece a una bodega asignada del usuario; en caso contrario, **denegado** (redirect a `containers.index` con error).
- ❌ Denegado siempre para el rol.
- "= clientes": comportamiento idéntico al rol `clientes` (heredado vía `User::isCliente()`).

---

## Módulo Contenedores (`Route::resource('containers', ...)` + export/print)

| Método | Ruta (name) | `admin` | `funcionario` | `clientes` | **`cliente_funcionario`** |
|---|---|---|---|---|---|
| GET | `containers.index` | ✅ todos | ✅ todos (lectura) | ❌ (sin acceso al módulo) | ✅ **solo bodegas asignadas** |
| GET | `containers.create` | ✅ | ❌ redirect (solo lectura) | ❌ | ✅ (bodega destino ∈ asignadas ∩ reciben-contenedores) |
| POST | `containers.store` | ✅ | ❌ | ❌ | 🔒 `warehouse_id` ∈ asignadas ∩ reciben-contenedores; si no, error |
| GET | `containers.show` | ✅ | ✅ | ❌ | 🔒 contenedor de bodega asignada |
| GET | `containers.edit` | ✅ | ❌ redirect (solo lectura) | ❌ | 🔒 contenedor de bodega asignada |
| PUT/PATCH | `containers.update` | ✅ | ❌ | ❌ | 🔒 contenedor y nuevo `warehouse_id` ∈ asignadas |
| DELETE | `containers.destroy` | ✅ | ❌ | ❌ | ❌ **borrado prohibido (rechazado en servidor)** |
| GET | `containers.export` | ✅ | ✅ | ❌ | 🔒 contenedor de bodega asignada |
| GET | `containers.print` | ✅ | ✅ | ❌ | 🔒 contenedor de bodega asignada |

Notas:
- El guard `funcionario` (solo lectura) **no se modifica**: sus filas se conservan tal cual.
- Para `cliente_funcionario`, todo acceso 🔒 a un recurso de bodega **no asignada** redirige a `containers.index` con mensaje de error (no se filtra de forma silenciosa al detalle).
- La búsqueda (`?search=`) del index opera **dentro** del scope de bodegas asignadas.

---

## Módulos heredados de `clientes` (sin cambios de contrato)

Para `cliente_funcionario`, el contrato de estos módulos es **exactamente el de `clientes`** (mismo acceso, mismo scoping por bodegas asignadas). Se listan para fijar la expectativa de los tests de paridad (SC-002):

| Módulo | Rutas | `cliente_funcionario` |
|---|---|---|
| Dashboard / Movimientos | `home` | = clientes (dashboard scoped por bodegas asignadas; sin redirección especial) |
| Productos | `products.*` | = clientes |
| Transferencias | `transfer-orders.*` | = clientes (crear/ver/scoping por bodegas asignadas) |
| Salidas | `salidas.*` | = clientes |
| Stock | `stock.*` | = clientes |
| Trazabilidad | `traceability.*` | = clientes |

Módulos **negados** (igual que `clientes`):

| Módulo | Rutas | `cliente_funcionario` |
|---|---|---|
| Bodegas | `warehouses.*` | ❌ (admin-only) |
| Importación | `imports.*` | ❌ |
| ITR | `itrs.*` | ❌ |
| Liquidación de Viajes | `liquidaciones.*` | ❌ (gate `liquidaciones.access` no lo incluye) |
| Usuarios | `users.*` | ❌ (controlador admin-only) |

---

## Gestión de usuarios (`users.*`, admin)

| Acción | Comportamiento para el rol `cliente_funcionario` |
|---|---|
| Crear/editar usuario | El selector de rol incluye `cliente_funcionario`; exige `almacenes[]` (≥1); valida `in:...,cliente_funcionario`. |
| Persistencia | `almacen_id = NULL`; `almacenes()->sync(seleccionadas)`. |
| Cambio de rol | Hacia el rol: set de bodegas + limpieza de relaciones ajenas. Desde el rol: `almacenes()->detach()` si el rol destino no usa bodegas. |
