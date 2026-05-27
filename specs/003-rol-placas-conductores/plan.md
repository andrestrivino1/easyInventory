# Implementation Plan: Rol "placas" con conductores asignados

**Branch**: `003-rol-placas-conductores` | **Date**: 2026-05-23 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from [/specs/003-rol-placas-conductores/spec.md](spec.md)

## Summary

Agregar al monolito Laravel existente un nuevo valor de rol `placas` que restringe al usuario **únicamente** al módulo de Liquidación de Viajes y limita todo lo que ve y opera a un conjunto de **conductores (`drivers`) que se le asignan**. La asignación se elige en la creación del usuario (y se puede editar después) y es compartida (un conductor puede pertenecer a varios usuarios `placas`).

El alcance se logra **sin tablas nuevas en el dominio de liquidaciones ni cambios destructivos**: una única tabla pivote `user_driver` (muchos-a-muchos `users`↔`drivers`, siguiendo la convención existente `user_warehouse`). El resto es:

1. **Registro del rol**: agregar `placas` a las reglas de validación y al formulario de usuarios, con un selector múltiple de conductores; sincronizar la relación en `store`/`update`.
2. **Aislamiento de acceso**: ampliar el gate `liquidaciones.access` y el middleware `BlockImporterAccess` (patrón ya usado para importer/itr) para confinar a `placas` al módulo; redirección de `home`; navegación lateral que solo muestra Liquidación de Viajes.
3. **Scoping por conductor (defensa en profundidad)**: filtrar las consultas y los selectores de conductor en `LiquidacionController` a los conductores asignados, y reforzar la `LiquidacionPolicy` para que cada acción sobre una liquidación verifique que su conductor pertenece al usuario `placas`. El catálogo de Rutas queda fuera del alcance de `placas` (solo selección, vía el form y el endpoint AJAX de peajes).

No se altera el comportamiento de `admin` (acceso total) ni de los demás roles.

## Technical Context

**Language/Version**: PHP 8.2.12 (cumple `composer.json` `^7.4 || ^8.0`); JavaScript ES2022 (vanilla, en el form de usuarios).
**Primary Dependencies**: Laravel Framework 8.75, Eloquent ORM, Blade. Sin dependencias nuevas. (El módulo de liquidaciones ya usa Alpine.js 3.x y `barryvdh/laravel-dompdf`; esta feature no toca esa capa salvo el scoping.)
**Storage**: MySQL/MariaDB vía XAMPP (InnoDB, `utf8mb4_unicode_ci`, alineado con el resto del schema). Una migración nueva: pivote `user_driver`.
**Testing**: PHPUnit 9.5 (Feature + Unit). Feature tests sobre los escenarios de aceptación de cada User Story (aislamiento, scoping, flujo completo, gestión de asignaciones).
**Target Platform**: Web monolítica Laravel (Apache/XAMPP en dev).
**Project Type**: Web monolítica — extensión del repo existente (modelo, migración, policy, middleware, controlador, vistas). Ningún proyecto nuevo.
**Performance Goals**:
- Carga del listado de liquidaciones para `placas` con scoping: <500ms (igual que admin; el filtro `whereIn(driver_id, ...)` usa el índice existente `liquidaciones.driver_id`).
- Resolución de "conductores asignados" del usuario: 1 consulta cacheada por request (relación `belongsToMany`).
**Constraints**:
- Cero modificación a las tablas `users`, `drivers`, `liquidaciones`, `liquidacion_routes` (solo se agrega la pivote `user_driver`).
- `placas` NO puede salir del módulo de Liquidación de Viajes (navegación + acceso directo por URL).
- `placas` NO puede gestionar el catálogo de Rutas/Peajes; solo seleccionarlas.
- `placas` NO puede ver ni operar liquidaciones de conductores no asignados (0% de fuga — SC-002).
- Reglas de estado (Borrador→Cerrada→Anulada/Reabrir) y motivos obligatorios **sin cambios**; `placas` las ejecuta dentro de su alcance.
- `admin` conserva acceso total e inalterado (FR-021).
**Scale/Scope**:
- 1–10 usuarios `placas` estimados; cada uno con 1–20 conductores asignados.
- Reutiliza el módulo de liquidaciones existente (~10 endpoints); agrega 0 endpoints HTTP nuevos.
- Cambios puntuales en ~10 archivos existentes + 1 migración + 1 modelo de relación + tests.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Status**: La constitución del proyecto (`/.specify/memory/constitution.md`) está en estado **template** — no hay principios ratificados (placeholders sin reemplazar). No existen gates formales que aplicar.

**Acción**: Aplicar como guía implícita las convenciones ya presentes en el codebase:

| Práctica del codebase | Aplicación en esta feature |
|---|---|
| Roles como string en `users.rol` (sin Spatie) | Sí — `placas` es un nuevo valor del string `rol`. Sin paquete de permisos. |
| Acceso restringido por rol vía middleware `BlockImporterAccess` (allow-list por nombre/ruta) | Sí — se agrega una rama `placas` con su allow-list, idéntico patrón a importer/import_viewer/proveedor_itr. |
| Gates en `AppServiceProvider` (`liquidaciones.access`) | Sí — se amplía el gate a `['admin','placas']`. |
| Policies Eloquent con `before()` para bypass admin | Sí — se reescribe `LiquidacionPolicy::before()` para bypass admin, bloqueo de otros roles, y fall-through de `placas` a chequeos por método. |
| Relación muchos-a-muchos vía pivote `user_warehouse` (`belongsToMany` + `sync`) | Sí — pivote `user_driver` con misma mecánica (`sync` en store/update). |
| Formulario de usuarios con campos dinámicos por rol (JS `toggle`) | Sí — se agrega grupo "Conductores" mostrado cuando `rol === 'placas'`, con validación de mínimo 1. |
| Redirección de `home` por rol en `HomeController` | Sí — `placas` → `liquidaciones.index`. |
| Navegación lateral condicionada por flags de rol | Sí — `$isPlacas` muestra solo "Liquidación de Viajes". |
| Migraciones con timestamps + FKs | Sí — una migración para `user_driver` con FKs y unique compuesto. |

**Gates**: Ninguno bloqueante. Pasar a Phase 0.

## Project Structure

### Documentation (this feature)

```text
specs/003-rol-placas-conductores/
├── plan.md                       # Este archivo
├── spec.md                       # Spec funcional
├── research.md                   # Phase 0: decisiones técnicas
├── data-model.md                 # Phase 1: pivote + relaciones + reglas de scoping
├── contracts/
│   └── http-routes.md            # Phase 1: matriz de autorización por endpoint para placas
├── quickstart.md                 # Phase 1: cómo migrar, sembrar datos y validar el flujo
├── checklists/
│   └── requirements.md           # Checklist de calidad del spec (ya creado)
└── tasks.md                      # /speckit-tasks (NO se crea en este comando)
```

### Source Code (repository root — Laravel monolito, extensión)

Archivos **nuevos**:

```text
database/
└── migrations/
    └── 2026_05_23_000000_create_user_driver_table.php   # Pivote users↔drivers

tests/
└── Feature/
    ├── PlacasUserManagementTest.php       # US1: crear/editar usuario placas + asignar conductores
    ├── PlacasAccessIsolationTest.php      # US2: confinamiento al módulo (nav + URL directa) + home redirect
    ├── PlacasScopingTest.php              # US2: solo ve/opera conductores asignados; 403 cross-driver
    ├── PlacasFullFlowTest.php             # US3: crear→editar→cerrar→reabrir→anular→PDF (asignado)
    └── PlacasRouteCatalogBlockedTest.php  # FR-018: catálogo de rutas bloqueado; peajes AJAX permitido
```

Archivos **modificados** (cambios puntuales):

```text
app/
├── Models/
│   └── User.php                           # + assignedDrivers() belongsToMany; helper isPlacas()/assignedDriverIds()
├── Providers/
│   └── AppServiceProvider.php             # Gate liquidaciones.access → in_array(rol, ['admin','placas'])
├── Http/
│   ├── Middleware/
│   │   └── BlockImporterAccess.php        # + rama 'placas' con allow-list de rutas del módulo
│   ├── Controllers/
│   │   ├── HomeController.php             # + redirect placas → liquidaciones.index
│   │   ├── UserController.php             # + 'placas' en reglas rol; reglas drivers[]; sync assignedDrivers; pasar $drivers a create/edit
│   │   ├── LiquidacionController.php      # scoping: index() filtra por conductores asignados; create()/edit() limitan $drivers
│   │   └── LiquidacionRouteController.php # defensa: authorize('viewAny'/'create') en index/create/store
│   └── Requests/
│       ├── StoreLiquidacionRequest.php    # driver_id ∈ conductores asignados (cuando rol placas)
│       └── UpdateLiquidacionRequest.php   # idem
└── Policies/
    └── LiquidacionPolicy.php              # before(): admin bypass, otros bloqueados, placas fall-through; métodos verifican propiedad por conductor

resources/
└── views/
    ├── layouts/app.blade.php              # + $isPlacas; rama de navegación que solo muestra Liquidación de Viajes
    ├── users/
    │   ├── create.blade.php               # + opción rol "Placas"; grupo selector de conductores; JS toggle + validación
    │   └── edit.blade.php                 # idem + preseleccionar conductores asignados
    └── liquidaciones/
        └── index.blade.php                # botón "Rutas" visible solo para admin (@can viewAny LiquidacionRoute)
```

**Structure Decision**: Extensión del monolito Laravel existente, sin sub-proyectos. La autorización sigue el patrón de tres capas ya presente en el codebase para roles restringidos:

1. **Middleware `BlockImporterAccess`** (allow-list por nombre de ruta) → confina al módulo. Misma forma que las ramas `importer`, `import_viewer`, `proveedor_itr`.
2. **Gate `liquidaciones.access`** → permite la entrada al grupo de rutas de liquidaciones a `admin` y `placas`.
3. **`LiquidacionPolicy`** (per-modelo) → garantiza que cada acción de un `placas` recae solo sobre liquidaciones de sus conductores asignados (defensa en profundidad ante manipulación de URL/parámetros).

El scoping de listados y selectores se hace en `LiquidacionController` (consultas filtradas por `assignedDriverIds()`), evitando que `placas` siquiera vea opciones fuera de su alcance.

## Complexity Tracking

No hay violaciones de constitución (constitución en template). Puntos de complejidad inherentes y su justificación:

| Decisión | Por qué | Alternativa más simple rechazada porque |
|---|---|---|
| Autorización en 3 capas (middleware + gate + policy) | El middleware confina al módulo; el gate abre el grupo de rutas; la policy impide fuga entre conductores ante manipulación directa. Cada capa cubre un vector distinto. | Solo middleware dejaría a `placas` ver/operar liquidaciones de conductores no asignados vía IDs en la URL (viola SC-002). Solo policy no ocultaría los demás módulos de la navegación ni del acceso directo. |
| Tabla pivote nueva `user_driver` | La relación conductor↔usuario es muchos-a-muchos (asignación compartida, FR-005). | Una columna `assigned_user_id` en `drivers` impide compartir un conductor entre varios `placas` y modificaría una tabla existente. |
| Scoping replicado en consultas (controller) **y** policy | Las consultas evitan mostrar datos/opciones fuera de alcance (UX + rendimiento); la policy bloquea el acceso por modelo (seguridad). Son responsabilidades distintas. | Scoping solo en consultas deja abierto el acceso directo por ID; policy sola obliga a cargar y rechazar registros que igualmente no deberían listarse. |
