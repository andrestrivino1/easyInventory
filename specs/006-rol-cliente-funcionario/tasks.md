---
description: "Task list for Rol cliente funcionario (clientes + módulo Contenedores)"
---

# Tasks: Rol "cliente funcionario" (clientes + módulo Contenedores)

**Input**: Design documents from `/specs/006-rol-cliente-funcionario/`
**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/http-routes.md](contracts/http-routes.md)

**Tests**: Incluidos — el plan (research D8) define 3 Feature tests, uno por User Story, sobre MySQL `easy_inventory_test`.

**Organization**: Tareas agrupadas por User Story para implementación y prueba independientes.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: A qué User Story pertenece (US1, US2, US3)
- Rutas de archivo exactas en cada descripción

## Path Conventions

Monolito Laravel existente. Rutas relativas a la raíz del repo: `app/`, `resources/views/`, `tests/Feature/`. **Sin migraciones** (se reutiliza `user_warehouse` y `containers.warehouse_id`).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Preparación del entorno; no hay inicialización de proyecto ni migraciones.

- [x] T001 Verificar branch `006-rol-cliente-funcionario` activo y base de datos de pruebas `easy_inventory_test` disponible (`php artisan migrate:fresh --env=testing` si aplica). Confirmar que NO se requieren migraciones nuevas para esta feature.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Predicados de rol que habilitan el scoping de US2 y la diferencia de Contenedores de US3.

**⚠️ CRITICAL**: US2 y US3 no pueden empezar hasta completar esta fase.

- [x] T002 Agregar a `app/Models/User.php` los helpers: `isCliente(): bool` (→ `in_array($this->rol, ['clientes','cliente_funcionario'], true)`), `isClienteFuncionario(): bool` (→ `$this->rol === 'cliente_funcionario'`) y `assignedWarehouseIds(): array` (IDs int de `almacenes`, siguiendo el patrón de `assignedDriverIds()`).

**Checkpoint**: Predicados listos — US2 y US3 pueden comenzar (en paralelo si hay capacidad). US1 es independiente de esta fase.

---

## Phase 3: User Story 1 - Crear un usuario con rol "cliente funcionario" (Priority: P1) 🎯 MVP

**Goal**: El administrador puede crear/editar usuarios con el rol `cliente_funcionario` y asignarles una o más bodegas, igual que para `clientes`.

**Independent Test**: Crear un usuario con rol "cliente funcionario" y 2 bodegas; verificar `rol`, `almacen_id = NULL` y las 2 bodegas en `user_warehouse`. Editar para agregar/quitar una bodega y confirmar persistencia.

### Tests for User Story 1 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [x] T003 [US1] Crear `tests/Feature/ClienteFuncionarioUserManagementTest.php`: validar (a) creación con rol `cliente_funcionario` + sync de `almacenes` y `almacen_id = NULL`; (b) edición que cambia la lista de bodegas; (c) cambio de rol hacia/desde `cliente_funcionario` que establece/limpia relaciones pivote; (d) rechazo de creación sin bodegas (`almacenes` requerido ≥1).

### Implementation for User Story 1

- [x] T004 [US1] En `app/Http/Controllers/UserController.php` (`store` y `update`): agregar `cliente_funcionario` a las reglas `'rol' => 'required|in:...'`; incluirlo en las ramas que aplican `almacenes` requerido (≥1), sincronizan `almacenes()->sync()` con `almacen_id = NULL`, y limpian relaciones al cambiar de rol (tratarlo exactamente como `clientes`).
- [x] T005 [P] [US1] En `resources/views/users/create.blade.php`: agregar `<option value="cliente_funcionario">Cliente Funcionario (Clientes + Contenedores)</option>` e incluir `cliente_funcionario` en la rama `else if (rol === 'clientes')` del JS `toggleBodegaFields` (mostrar grupo "Bodegas" + validación ≥1).
- [x] T006 [P] [US1] En `resources/views/users/edit.blade.php`: agregar la misma `<option>` (con preselección por `$usuario->rol`), incluir el rol en la rama `clientes` del JS, y preseleccionar las bodegas asignadas.
- [x] T007 [P] [US1] En `resources/views/users/index.blade.php` (línea ~120): extender la condición `rol === 'funcionario' || rol === 'clientes'` para mostrar también las bodegas asignadas de los usuarios `cliente_funcionario`.

**Checkpoint**: US1 funcional y testeable de forma independiente (gestión del usuario completa).

---

## Phase 4: User Story 2 - Operar como cliente, limitado a bodegas asignadas (Priority: P2)

**Goal**: `cliente_funcionario` tiene alcance idéntico a `clientes` en los módulos heredados (mismos módulos visibles, mismos datos por bodega, mismas restricciones).

**Independent Test**: Sembrar un `cliente_funcionario` y un `clientes` de control con las mismas 2 bodegas; verificar que ven los mismos módulos/datos en Dashboard, Productos, Transferencias, Salidas, Stock y Trazabilidad, y que los módulos negados (Bodegas, Importación, ITR, Liquidación, Usuarios) siguen denegados para ambos.

> Nota de navegación: el menú lateral de `clientes` se sirve en la rama `@elseif(!$isImporter && !$isImportViewer)` de `layouts/app.blade.php`, en la que `cliente_funcionario` ya cae automáticamente; no requiere cambios en US2 (el enlace de Contenedores se agrega en US3).

### Tests for User Story 2 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [x] T008 [US2] Crear `tests/Feature/ClienteFuncionarioInheritsClienteScopeTest.php`: con bodegas asignadas, comparar el alcance de un `cliente_funcionario` contra un `clientes` de control — mismos datos visibles por bodega en stock/salidas/transferencias/trazabilidad/dashboard, y mismas denegaciones de acceso a módulos no permitidos.

### Implementation for User Story 2 (migrar checks de scoping `=== 'clientes'` a `->isCliente()`)

- [x] T009 [P] [US2] En `app/Http/Controllers/WelcomeController.php`: reemplazar los 3 checks `$user->rol === 'clientes'` (productos, bodegas, transferencias) por `$user->isCliente()`.
- [x] T010 [P] [US2] En `app/Http/Controllers/StockController.php`: reemplazar los checks de scoping `=== 'clientes'` por `isCliente()`.
- [x] T011 [P] [US2] En `app/Http/Controllers/SalidaController.php`: reemplazar los checks de scoping `=== 'clientes'` por `isCliente()` (listado, creación, validación de bodega).
- [x] T012 [P] [US2] En `app/Http/Controllers/TransferOrderController.php`: reemplazar los checks de scoping `=== 'clientes'` y `in_array($user->rol, ['funcionario','clientes'])` por el predicado correspondiente que incluya `cliente_funcionario` (carga de `almacenes`, permiso de crear, validación de bodegas, edición).
- [x] T013 [P] [US2] En `app/Http/Controllers/TraceabilityController.php`: reemplazar los checks de scoping `=== 'clientes'` por `isCliente()`.
- [x] T014 [P] [US2] En `resources/views/stock/index.blade.php` (línea ~96): incluir `cliente_funcionario` en `$isCliente`.
- [x] T015 [P] [US2] En `resources/views/salidas/create.blade.php` (línea ~194): incluir `cliente_funcionario` en `$isCliente`.
- [x] T016 [P] [US2] En `resources/views/transfer-orders/index.blade.php` (líneas ~144, ~153, ~276, ~300): incluir `cliente_funcionario` en la carga de `almacenes`, en el permiso de acción y en las ramas de display de cliente.
- [x] T017 [P] [US2] En `resources/views/traceability/index.blade.php` (líneas ~157, ~175, ~181): incluir `cliente_funcionario` en el selector/hidden de bodega del cliente.
- [x] T018 [P] [US2] En `resources/views/products/index.blade.php` (línea ~133): cambiar `auth()->user()->rol !== 'clientes'` por la negación del predicado de alcance cliente (`!auth()->user()->isCliente()`) para ocultar acciones de admin también a `cliente_funcionario`.

**Checkpoint**: US1 y US2 funcionan de forma independiente; `cliente_funcionario` es indistinguible de `clientes` en los módulos heredados.

---

## Phase 5: User Story 3 - Trabajar el módulo de Contenedores (Priority: P3)

**Goal**: `cliente_funcionario` ve el módulo Contenedores y puede ver/crear/editar/exportar/imprimir contenedores de sus bodegas asignadas, **sin eliminar**.

**Independent Test**: Sembrar un `cliente_funcionario` con la bodega A asignada (A recibe contenedores) y contenedores en A y en otra bodega B; verificar listado solo de A, crear/editar/export/print en A OK, acceso a un contenedor de B denegado, botón Eliminar ausente y `DELETE` directo rechazado.

### Tests for User Story 3 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [x] T019 [US3] Crear `tests/Feature/ClienteFuncionarioContainersTest.php`: (a) `containers.index` visible y filtrado por bodegas asignadas; (b) `create`/`store` OK con bodega asignada que recibe contenedores y rechazo con bodega no asignada; (c) `edit`/`update`/`export`/`print` de contenedor asignado OK y de bodega no asignada → redirect con error; (d) `destroy` rechazado para el rol; (e) no-regresión: `funcionario` sigue en solo lectura y `clientes` sin acceso.

### Implementation for User Story 3

- [x] T020 [US3] En `resources/views/layouts/app.blade.php`: agregar el flag `$isClienteFuncionario = $user && $user->rol === 'cliente_funcionario';` e incluirlo en la condición que muestra el enlace "Contenedores" (línea ~60: `@if($isPabloRojas || $isFuncionario || $isClienteFuncionario)`), sin alterar el resto del menú heredado.
- [x] T021 [US3] En `app/Http/Controllers/ContainerController.php`: (a) `index()` — si `isClienteFuncionario()`, acotar `Container::whereIn('warehouse_id', $user->assignedWarehouseIds())` (búsqueda dentro del scope); (b) `create()`/`edit()` — permitir el rol y limitar `$warehouses` a `getBodegasQueRecibenContenedores() ∩ assignedWarehouseIds()`; (c) `store()`/`update()` — validar `warehouse_id ∈` ese conjunto; en `edit/update/export/print` verificar que `$container->warehouse_id ∈ assignedWarehouseIds()`, si no → redirect a `containers.index` con error; (d) `destroy()` — rechazar para `isClienteFuncionario()` (redirect con error), análogo al guard `funcionario`.
- [x] T022 [P] [US3] En `resources/views/containers/index.blade.php`: ocultar el botón/enlace "Eliminar" cuando el usuario es `cliente_funcionario` (botón visible solo para roles que pueden borrar).
- [x] T023 [P] [US3] En `resources/views/containers/create.blade.php`: asegurar que el selector de bodega solo liste las bodegas pasadas por el controlador (asignadas ∩ reciben-contenedores) y muestre un mensaje si el conjunto es vacío.
- [x] T024 [P] [US3] En `resources/views/containers/edit.blade.php`: idem al create — selector de bodega limitado al conjunto permitido.

**Checkpoint**: Las 3 User Stories funcionan de forma independiente.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Validación integral y verificación de no-regresión.

- [x] T025 [P] Auditar con `grep` que no queden checks de **scoping** `=== 'clientes'` sin migrar en `app/` y `resources/views/` (excluyendo opciones de rol y reglas `in:` que sí deben permanecer explícitas).
- [x] T026 Ejecutar `php artisan test --filter=ClienteFuncionario` (los 3 Feature tests en verde) y verificar no-regresión manual: `funcionario` sigue en solo lectura en Contenedores, `clientes` sin acceso a Contenedores, `admin` con acceso total.
- [x] T027 [P] Ejecutar la validación manual de [quickstart.md](quickstart.md) (checklists US1/US2/US3 + no-regresión).

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2 / T002)**: depende de Setup. **Bloquea US2 y US3**. US1 NO depende de T002.
- **US1 (Phase 3)**: puede empezar tras Setup (independiente de T002).
- **US2 (Phase 4)**: depende de T002.
- **US3 (Phase 5)**: depende de T002.
- **Polish (Phase 6)**: depende de las stories implementadas.

### User Story Dependencies

- **US1 (P1)**: independiente. Es el MVP.
- **US2 (P2)**: depende de T002 (helper `isCliente()`); independiente de US1 (los tests siembran el usuario directamente).
- **US3 (P3)**: depende de T002 (`isClienteFuncionario()` / `assignedWarehouseIds()`); independiente de US1 y US2.

### Within Each User Story

- El test de la story se escribe primero y debe FALLAR antes de implementar.
- En US1: T004 (controlador) antes o en paralelo con las vistas T005–T007 (archivos distintos).
- En US2/US3: los `[P]` son archivos distintos y pueden ir en paralelo.

### Parallel Opportunities

- US2: T009–T018 son todos `[P]` (archivos distintos) — alta paralelización tras T008.
- US3: T022–T024 son `[P]`; T020 y T021 tocan archivos propios (layout y controlador).
- Tras T002, US2 y US3 pueden desarrollarse en paralelo por personas distintas.

---

## Parallel Example: User Story 2

```bash
# Tras T002 y el test T008, migrar todos los archivos de scoping en paralelo:
Task: "WelcomeController → isCliente()"
Task: "StockController → isCliente()"
Task: "SalidaController → isCliente()"
Task: "TransferOrderController → predicado con cliente_funcionario"
Task: "TraceabilityController → isCliente()"
Task: "stock/index.blade.php → $isCliente"
Task: "salidas/create.blade.php → $isCliente"
Task: "transfer-orders/index.blade.php → ramas de cliente"
Task: "traceability/index.blade.php → selector de bodega"
Task: "products/index.blade.php → !isCliente()"
```

---

## Implementation Strategy

### MVP First (User Story 1)

1. Phase 1 (Setup) → Phase 2 (T002) → Phase 3 (US1).
2. **STOP y VALIDAR**: crear/editar un `cliente_funcionario` con bodegas funciona y persiste.
3. Demo del MVP (el rol existe y se gestiona).

### Incremental Delivery

1. Setup + Foundational → base lista.
2. US1 → gestión del usuario (MVP).
3. US2 → paridad con `clientes` (alcance heredado).
4. US3 → diferencia: módulo Contenedores.
5. Cada story agrega valor sin romper las anteriores.

### Parallel Team Strategy

Tras T002: Dev A → US1; Dev B → US2; Dev C → US3. Las tres integran de forma independiente; los tests siembran sus propios usuarios.

---

## Notes

- [P] = archivos distintos, sin dependencias.
- **Cero migraciones**: se reutiliza `user_warehouse` y `containers.warehouse_id`.
- Mantener inalterados los roles `admin`, `clientes` y `funcionario` (FR-017, FR-018, SC-006).
- Distinguir SIEMPRE entre checks de **scoping** (migrar a `isCliente()`) y **listas de rol / reglas `in:` / opciones** (extender explícitamente con `cliente_funcionario`).
- Feature tests sobre MySQL `easy_inventory_test`; los tests de Breeze Auth/Example fallan de forma pre-existente y no cuentan como regresión.
- Confirmar que cada test FALLA antes de implementar su story.
