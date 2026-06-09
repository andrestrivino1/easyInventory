# Phase 1 Data Model: Rol "cliente funcionario"

**Sin cambios de esquema.** Esta feature no agrega tablas, columnas ni migraciones. Reutiliza por completo el modelo de datos existente. Este documento describe las entidades involucradas, sus relaciones y las **reglas de scoping** que se aplican en la capa de aplicación.

---

## Entidades existentes reutilizadas

### Usuario (`users`)

- Campo relevante: `rol` (string). Se introduce un **nuevo valor**: `cliente_funcionario`.
- Campo `almacen_id`: queda `NULL` para este rol (igual que `clientes`/`funcionario`), porque la asignación de bodegas es muchos-a-muchos.
- Relación reutilizada: `almacenes()` → `belongsToMany(Warehouse, 'user_warehouse')` (la misma que usan `clientes` y `funcionario`).

**Helpers nuevos en el modelo `User`** (no son columnas; son métodos):

| Método | Devuelve | Uso |
|---|---|---|
| `isCliente(): bool` | `in_array($rol, ['clientes','cliente_funcionario'], true)` | Predicado de "alcance cliente"; reemplaza los checks de scoping `=== 'clientes'`. |
| `isClienteFuncionario(): bool` | `$rol === 'cliente_funcionario'` | Gobierna la diferencia: acceso a Contenedores y su scoping/restricciones. |
| `assignedWarehouseIds(): array` | IDs (int) de `almacenes` | Filtro de contenedores y validación de bodega destino. |

### Bodega (`warehouses`)

- Sin cambios. Métodos estáticos existentes que se reutilizan:
  - `Warehouse::getBodegasQueRecibenContenedores(): array` — IDs de bodegas que pueden recibir contenedores (Buenaventura/Pablo Rojas).
  - `Warehouse::bodegaRecibeContenedores($id): bool` — validación de destino.

### Contenedor (`containers`)

- Sin cambios. Campos: `reference`, `note`, `warehouse_id` (FK a `warehouses`). `timestamps = false`.
- Relación: `warehouse()` → `belongsTo(Warehouse)`. El `warehouse_id` es el **eje del scoping** para `cliente_funcionario`.

### Relación Usuario–Bodega (`user_warehouse`)

- Pivote existente (muchos-a-muchos). Sin cambios. Define qué bodegas "posee" un `cliente_funcionario` y, por tanto, qué contenedores ve y dónde puede crearlos.

---

## Reglas de scoping y autorización (capa de aplicación)

> Estas reglas se implementan en controladores/vistas; no son constraints de base de datos.

### Alcance heredado de `clientes` (módulos no-Contenedores)

- **R1**: En todo punto donde hoy aplica `=== 'clientes'` para acotar datos a bodegas asignadas (dashboard, stock, salidas, transferencias, trazabilidad), `cliente_funcionario` queda incluido vía `User::isCliente()`. Resultado: visibilidad y datos **idénticos** a un `clientes` con las mismas bodegas (SC-002).
- **R2**: Las restricciones de `clientes` frente a módulos no permitidos (Bodegas, Importación, ITR, Liquidación de Viajes, Usuarios) aplican igual a `cliente_funcionario`, por el mismo mecanismo (navegación + controladores admin-only).

### Diferencia: módulo Contenedores

- **R3 (visibilidad)**: `cliente_funcionario` solo ve contenedores con `warehouse_id ∈ assignedWarehouseIds()`. El listado se filtra; la búsqueda global opera dentro del scope.
- **R4 (acceso por acción)**: cualquier acción sobre un contenedor concreto (`edit`, `update`, `export`, `print`) exige `container.warehouse_id ∈ assignedWarehouseIds()`; en caso contrario se deniega (redirect con error). Cubre el acceso directo por URL (SC-004).
- **R5 (crear/editar)**: permitido. El destino válido de un contenedor es `getBodegasQueRecibenContenedores() ∩ assignedWarehouseIds()`. El selector de bodega y la validación de `store`/`update` se limitan a ese conjunto.
- **R6 (eliminar)**: prohibido. `destroy()` rechaza la operación para `cliente_funcionario` (botón oculto + guard de servidor). 100% de intentos bloqueados (SC-005).
- **R7 (admin)**: `admin` conserva acceso total y sin restricciones a todos los contenedores y bodegas (FR-017).
- **R8 (no regresión)**: `clientes` (sin Contenedores) y `funcionario` (solo lectura en Contenedores) mantienen su comportamiento actual (FR-018, SC-006).

---

## Transiciones de estado

No aplica. El módulo de Contenedores no tiene máquina de estados; las operaciones son CRUD directo. El rol no introduce estados nuevos.

---

## Gestión del usuario (formulario)

- **R9**: Al crear/editar un usuario con rol `cliente_funcionario`, se exige `almacenes[]` con ≥1 bodega (mismas reglas que `clientes`); se sincroniza `almacenes()->sync()` y se fija `almacen_id = NULL`.
- **R10**: Al cambiar un usuario **desde** `cliente_funcionario` hacia un rol sin bodegas, se limpian las relaciones pivote (`almacenes()->detach()`), igual que la lógica vigente para `clientes`. Al cambiar **hacia** `cliente_funcionario`, se establecen las bodegas seleccionadas y se limpian relaciones ajenas (p. ej. `assignedDrivers` si venía de `placas`).
