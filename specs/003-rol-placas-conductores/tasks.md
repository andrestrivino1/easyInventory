---
description: "Task list for feature: Rol placas con conductores asignados"
---

# Tasks: Rol "placas" con conductores asignados

**Input**: Design documents from `/specs/003-rol-placas-conductores/`
**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/http-routes.md](contracts/http-routes.md), [quickstart.md](quickstart.md)

**Tests**: INCLUDED — el plan enumera 5 feature tests (PHPUnit) sobre los escenarios de aceptación. Se generan tareas de prueba por historia.

**Organization**: Tareas agrupadas por User Story para implementación y validación independientes.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede correr en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: US1, US2, US3
- Rutas de archivo exactas en cada descripción (monolito Laravel, raíz del repo)

## Path Conventions

Monolito Laravel existente — rutas relativas a la raíz del repo `c:\xampp\htdocs\easy_inventory`: `app/`, `database/migrations/`, `resources/views/`, `routes/`, `tests/`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Sanidad del entorno (proyecto ya inicializado).

- [X] T001 Confirmar branch `003-rol-placas-conductores` activo y conexión a BD verificando `php artisan migrate:status` desde la raíz del repo (Apache + MySQL de XAMPP arriba).

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Capa de datos compartida por TODAS las historias (relación conductor↔usuario).

**⚠️ CRITICAL**: Ninguna User Story puede completarse hasta terminar esta fase.

- [X] T002 Crear migración de la pivote en `database/migrations/2026_05_23_000000_create_user_driver_table.php` con `user_id` y `driver_id` (FKs `cascadeOnDelete`), `timestamps` y `unique(['user_id','driver_id'])` (ver [data-model.md](data-model.md)).
- [ ] T003 Ejecutar `php artisan migrate` y confirmar que se crea `user_driver` sin alterar `users`, `drivers`, `liquidaciones`, `liquidacion_routes` (depende de T002).
- [X] T004 [P] Agregar a `app/Models/User.php` la relación `assignedDrivers()` (`belongsToMany(Driver::class, 'user_driver')->withTimestamps()`) y los helpers `isPlacas(): bool` y `assignedDriverIds(): array`.
- [X] T005 [P] Agregar a `app/Models/Driver.php` la relación inversa `placasUsers()` (`belongsToMany(User::class, 'user_driver')->withTimestamps()`).

**Checkpoint**: Pivote + relaciones listas. Las historias pueden comenzar.

---

## Phase 3: User Story 1 - Crear/editar usuario placas y asignar conductores (Priority: P1) 🎯 MVP

**Goal**: Un admin crea (y edita) un usuario con rol `placas` y elige sus conductores en el mismo formulario; la relación persiste y es compartible.

**Independent Test**: Crear un usuario rol `placas` con 2 conductores → verificar 2 filas en `user_driver`; editar para dejar 1 → 1 fila; asignar un mismo conductor a un segundo usuario `placas` → permitido.

### Tests for User Story 1 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [X] T006 [P] [US1] Feature test en `tests/Feature/PlacasUserManagementTest.php`: (a) crear usuario `placas` con `drivers[]` persiste rol + `sync` de asignaciones; (b) editar actualiza asignaciones; (c) min:1 conductor obligatorio falla validación; (d) un conductor asignado a dos usuarios `placas` es permitido.

### Implementation for User Story 1

- [X] T007 [US1] En `app/Http/Controllers/UserController.php` agregar `placas` a la regla `rol` (`in:...`) en `store()` y `update()`, y añadir reglas condicionales `drivers => required|array|min:1`, `drivers.* => exists:drivers,id` cuando `rol === 'placas'`.
- [X] T008 [US1] En `app/Http/Controllers/UserController.php` (`store()` y `update()`) sincronizar la relación: `assignedDrivers()->sync($request->drivers)` cuando `rol === 'placas'`; `detach()` cuando el rol deja de ser `placas`; mantener `almacen_id = null` y sin filas en `user_warehouse` para `placas` (depende de T007).
- [X] T009 [US1] En `app/Http/Controllers/UserController.php` (`create()` y `edit()`) pasar `$drivers = Driver::where('active',1)->orderBy('name')->get(['id','name','vehicle_plate'])` a las vistas, y en `edit()` exponer los IDs de conductores ya asignados (`$usuario->assignedDrivers`) (depende de T008).
- [X] T010 [P] [US1] En `resources/views/users/create.blade.php` agregar la opción de rol "Placas" y un grupo checkbox de conductores (estilo del grupo de bodegas), mostrado por JS solo cuando `rol === 'placas'`, con validación cliente de mínimo 1 (depende de T009).
- [X] T011 [P] [US1] En `resources/views/users/edit.blade.php` replicar el grupo de conductores y JS de create, **preseleccionando** los conductores ya asignados (depende de T009).

**Checkpoint**: US1 funcional — el alta/edición de usuarios `placas` con asignación de conductores funciona y es testeable de forma aislada (a nivel BD).

---

## Phase 4: User Story 2 - Acceso restringido al módulo y visibilidad por conductor (Priority: P2)

**Goal**: El usuario `placas` solo accede al módulo de Liquidación de Viajes y solo ve datos (listado, consolidado, selectores) de sus conductores asignados.

**Independent Test**: Login como `placas` (2 conductores) en un set con 5 conductores → aterriza en `/liquidaciones`; nav muestra solo Liquidación de Viajes; otros módulos por URL redirigen; listado/consolidado/selectores solo de sus 2; abrir liquidación de conductor no asignado → 403.

### Tests for User Story 2 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [X] T012 [P] [US2] Feature test en `tests/Feature/PlacasAccessIsolationTest.php`: login de `placas` redirige a `liquidaciones.index`; GET a `/products`, `/users`, `/drivers`, `/liquidaciones/rutas` redirige (302) a `liquidaciones.index`; GET a `/liquidaciones` responde 200.
- [X] T013 [P] [US2] Feature test en `tests/Feature/PlacasScopingTest.php`: el listado solo incluye liquidaciones de conductores asignados; el selector de conductor solo ofrece asignados; `GET /liquidaciones/{id}` de conductor no asignado → 403; consolidado = suma de asignados.

### Implementation for User Story 2

- [X] T014 [P] [US2] En `app/Providers/AppServiceProvider.php` cambiar el gate `liquidaciones.access` a `in_array($user->rol, ['admin','placas'])`.
- [X] T015 [P] [US2] En `app/Http/Middleware/BlockImporterAccess.php` agregar la rama `elseif ($userRole === 'placas')` con allow-list por nombre (rutas operativas `liquidaciones.*` + `liquidaciones.routes.peajes` + `language.switch`/`home`/`logout`) y `allowedPaths` solo `home`/`logout`/`language`; cualquier otra ruta → `redirect()->route('liquidaciones.index')` (ver [contracts/http-routes.md](contracts/http-routes.md)).
- [X] T016 [P] [US2] En `app/Http/Controllers/HomeController.php` agregar redirección `placas` → `liquidaciones.index` (mismo patrón que importer/itr).
- [X] T017 [US2] En `app/Policies/LiquidacionPolicy.php` reescribir `before()` (admin → `true`; rol distinto de `placas` → `false`; `placas` → `null` para caer a los métodos), agregar helper privado `owns(User,Liquidacion)` (driver_id ∈ `assignedDriverIds`) e implementar `viewAny` (true) y `view` (owns).
- [X] T018 [US2] En `app/Http/Controllers/LiquidacionController.php` `index()`: si el usuario es `placas`, filtrar la query `$base` con `whereIn('driver_id', $assignedIds)` y limitar `$drivers` (selector) a los asignados; el consolidado se recalcula solo (deriva de `$base`).
- [X] T019 [P] [US2] En `resources/views/layouts/app.blade.php` agregar el flag `$isPlacas` y una rama de navegación que muestre **solo** "Liquidación de Viajes" para ese rol.
- [X] T020 [P] [US2] En `resources/views/liquidaciones/index.blade.php` envolver el botón "Rutas" con `@can('viewAny', App\Models\LiquidacionRoute::class)` para ocultarlo a `placas`.

**Checkpoint**: US2 funcional — `placas` queda confinado al módulo y solo ve sus conductores. (US1 + US2 operativas.)

---

## Phase 5: User Story 3 - Flujo operativo completo sobre conductores asignados (Priority: P3)

**Goal**: `placas` ejecuta el flujo completo (crear, editar/eliminar borradores, cerrar, reabrir, anular, PDF) limitado a sus conductores; selecciona rutas existentes sin gestionar el catálogo.

**Independent Test**: Con un conductor asignado: crear (seleccionando ruta) → editar → cerrar → reabrir → anular → descargar PDF, todo OK y atribuido al usuario; POST `store` con `driver_id` no asignado → 422; acceso al catálogo de rutas bloqueado pero AJAX de peajes permitido.

### Tests for User Story 3 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [X] T021 [P] [US3] Feature test en `tests/Feature/PlacasFullFlowTest.php`: `placas` crea (conductor asignado + ruta), edita borrador, cierra, reabre (motivo), anula y descarga PDF; `store`/`update` con `driver_id` no asignado → 422; acciones sobre liquidación de conductor no asignado → 403.
- [X] T022 [P] [US3] Feature test en `tests/Feature/PlacasRouteCatalogBlockedTest.php`: GET/POST a `liquidaciones.routes.index|create|store|edit|update|destroy|toggle-active` para `placas` → bloqueado; `GET liquidaciones/rutas/{route}/peajes` → 200.

### Implementation for User Story 3

- [X] T023 [P] [US3] En `app/Policies/LiquidacionPolicy.php` añadir las verificaciones de `placas` (`owns` + regla de estado vigente) a `update`, `delete`, `close`, `reopen`, `cancel` y `downloadPdf` (depende de T017).
- [X] T024 [P] [US3] En `app/Http/Controllers/LiquidacionController.php` `create()` y `edit()`: limitar `$drivers` a los conductores asignados cuando el usuario es `placas` (depende de T018).
- [X] T025 [P] [US3] En `app/Http/Requests/StoreLiquidacionRequest.php` restringir `driver_id` con `Rule::in($user->assignedDriverIds())` cuando el usuario autenticado es `placas` (manteniendo `exists:drivers,id`).
- [X] T026 [P] [US3] En `app/Http/Requests/UpdateLiquidacionRequest.php` aplicar la misma restricción de `driver_id` para `placas`.
- [X] T027 [P] [US3] En `app/Http/Controllers/LiquidacionRouteController.php` agregar `authorize('viewAny', LiquidacionRoute::class)` a `index()`/`create()` y `authorize('create', LiquidacionRoute::class)` a `store()` como defensa en profundidad.

**Checkpoint**: US3 funcional — el flujo completo opera scoped por conductor y el catálogo de rutas queda fuera del alcance de `placas`.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Acabado y verificación transversal.

- [X] T028 [P] En `resources/views/users/index.blade.php` mostrar una etiqueta amigable para el rol `placas` (p. ej. "Placas") donde se listan los roles de usuario.
- [ ] T029 [P] Ejecutar `php artisan test --filter=Placas` y `php artisan test --filter=Liquidacion`; confirmar que `admin` y los demás roles no se ven afectados (FR-021/FR-022).
- [ ] T030 Ejecutar la validación end-to-end de [quickstart.md](quickstart.md) (US1→US2→US3 + checklist de aceptación SC-001…SC-006).

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de Setup — **BLOQUEA** todas las historias.
- **User Stories (Phase 3–5)**: dependen de Foundational. Orden de prioridad P1 → P2 → P3. US2 y US3 comparten archivos (`LiquidacionPolicy`, `LiquidacionController`), por lo que conviene hacerlas en orden.
- **Polish (Phase 6)**: depende de las historias deseadas.

### User Story Dependencies

- **US1 (P1)**: solo Foundational. Independiente (capa de admin/usuarios). MVP.
- **US2 (P2)**: solo Foundational para implementarse. Para *probarse* end-to-end conviene poder crear un usuario `placas` (US1) o sembrarlo por tinker (ver quickstart). Toca gate/middleware/home/policy/controller/vistas de navegación.
- **US3 (P3)**: Foundational + extiende `LiquidacionPolicy` (sobre lo de US2/T017) y `LiquidacionController` (sobre T018). Probarse requiere acceso (US2) y un usuario con conductor asignado (US1).

### Within Each User Story

- Los tests (incluidos) se escriben primero y deben fallar antes de implementar.
- Modelos/migración (Foundational) antes de servicios/controladores.
- Cambios de servidor (gate/middleware/policy/controller/requests) antes de los de vista cuando la vista depende de datos que pasa el controlador.

### Parallel Opportunities

- T004 y T005 (modelos distintos) en paralelo.
- US1: T010 y T011 (vistas distintas) en paralelo tras T009.
- US2: T014, T015, T016, T019, T020 (archivos distintos) en paralelo; T017 y T018 (archivos distintos) en paralelo.
- US3: T023, T024, T025, T026, T027 (archivos distintos) en paralelo.
- Tareas de prueba marcadas [P] de una misma historia, en paralelo.

---

## Parallel Example: User Story 2

```bash
# Tests de US2 juntos:
Task: "Feature test PlacasAccessIsolationTest.php (T012)"
Task: "Feature test PlacasScopingTest.php (T013)"

# Implementación de archivos distintos en paralelo:
Task: "Gate liquidaciones.access en AppServiceProvider.php (T014)"
Task: "Rama placas en BlockImporterAccess.php (T015)"
Task: "Redirect placas en HomeController.php (T016)"
Task: "Flag $isPlacas + nav en layouts/app.blade.php (T019)"
Task: "Ocultar botón Rutas en liquidaciones/index.blade.php (T020)"
```

---

## Implementation Strategy

### MVP First (User Story 1)

1. Phase 1: Setup.
2. Phase 2: Foundational (pivote + relaciones) — CRÍTICO.
3. Phase 3: US1 (crear/editar usuario `placas` + asignar conductores).
4. **STOP y VALIDAR**: verificar persistencia de asignaciones (independiente).

### Incremental Delivery

1. Setup + Foundational → base lista.
2. US1 → validar → demo (MVP: gestión del rol y asignaciones).
3. US2 → validar → demo (confinamiento + visibilidad por conductor).
4. US3 → validar → demo (flujo operativo completo scoped).
5. Polish → suite verde + quickstart.

### Notes

- [P] = archivos distintos, sin dependencias pendientes.
- US2/US3 reescriben `LiquidacionPolicy` y `LiquidacionController` en métodos distintos: respetar el orden (T017 antes de T023; T018 antes de T024).
- Commit tras cada tarea o grupo lógico.
- Cero cambios a tablas existentes; única migración nueva: `user_driver`.
