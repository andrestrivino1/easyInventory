# Implementation Plan: Liquidación de Viajes

**Branch**: `002-liquidacion-viajes` | **Date**: 2026-05-19 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from [/specs/002-liquidacion-viajes/spec.md](spec.md)

## Summary

Implementar dentro del Laravel monolito existente un módulo "Liquidación de Viajes" accesible solo para `admin` con tres entregables:

1. **CRUD del formulario** (mimicry del Excel actual) con cálculos en tiempo real vía Alpine.js, ciclo de vida `Borrador → Cerrada → Anulada` y reuso del maestro `drivers` para conductor + placa.
2. **Listado filtrable** con totales individuales por fila y botón "Descargar PDF" (generado con `barryvdh/laravel-dompdf`, ya presente en `composer.json`).
3. **Consolidado del periodo / mensual** del conjunto filtrado, calculado en SQL para mantener el SLA de <2s con hasta 1.000 liquidaciones.

Tablas nuevas (7): `expense_categories` (catálogo seed de 16 filas), `routes`, `route_tolls`, `liquidaciones`, `liquidacion_expenses`, `liquidacion_tolls`, `liquidacion_state_logs`. No se altera ningún modelo existente (Driver, User, etc.).

## Technical Context

**Language/Version**: PHP 8.2.12 (cumple `composer.json` requirement `^7.4 || ^8.0`); JavaScript ES2022 para Alpine.js.
**Primary Dependencies**: Laravel Framework 8.75, Eloquent ORM, Blade, Alpine.js 3.x, Tailwind CSS, `barryvdh/laravel-dompdf` ^2.2 (PDF), Vite (build).
**Storage**: MySQL/MariaDB vía XAMPP (motor `InnoDB`, charset `utf8mb4_unicode_ci`, alineado con resto del schema).
**Testing**: PHPUnit 9.5 (Feature + Unit), Pest opcional. La constitución del proyecto está en template — no exige TDD obligatorio. Para esta feature: feature tests sobre los casos de aceptación de cada User Story.
**Target Platform**: Web app dentro del Laravel monolito existente (corre en Apache/XAMPP en dev).
**Project Type**: Web monolítica (Laravel) — extender el repo existente con nuevos modelos, controladores, vistas y migraciones; ningún proyecto nuevo.
**Performance Goals**:
- Cálculos en pantalla del formulario: <1s (SC-002), Alpine.js en cliente.
- Consolidado del listado con hasta 1.000 liquidaciones: <2s (SC-007), agregaciones SQL con índices en `liquidaciones.fecha_inicio`, `driver_id`, `route_id`, `estado`.
- Carga del listado paginado: <500ms con paginación de 25–50 filas.
**Constraints**:
- Solo `admin` puede acceder (FR-015) — usar gate/policy + middleware existente del sidebar.
- Preservar layout del PDF idéntico al Excel original (FR-009).
- Cero modificación a tablas existentes (`drivers`, `users`, etc.).
- Soft delete solo para `Borrador` (FR-008d) usando `SoftDeletes` trait de Eloquent.
**Scale/Scope**:
- ~50–200 liquidaciones/mes estimadas; 1–3 admins.
- ~16 filas de gastos × N liquidaciones; ~10–20 peajes por ruta × N rutas (estimado <20 rutas).
- 1 vista pública (admin), 2 sub-vistas (Liquidaciones, Rutas), ~10 endpoints HTTP.

## Constitution Check

**Status**: ⚠️ Constitución del proyecto está en estado **template** — `/.specify/memory/constitution.md` no tiene principios ratificados (placeholders sin reemplazar). No hay reglas formales que aplicar.

**Acción**: Aplicar buenas prácticas del codebase existente como guía implícita:

| Práctica del codebase | Aplicación en este módulo |
|---|---|
| Convención Laravel MVC (Controller + Resource routes) | Sí, `LiquidacionController` con resource routes; `LiquidacionRouteController` para rutas. |
| Eloquent models con relaciones explícitas | Sí, modelos con `hasMany`/`belongsTo` según data-model. |
| Soft deletes donde aplica (`drivers`, `containers`) | Sí, `SoftDeletes` en `liquidaciones`, restringido a estado `Borrador` vía policy. |
| Blade views con `@extends('layouts.app')` | Sí, reutilizar el layout cosmético recién extraído. |
| Role-based access en sidebar (`$user->rol`) | Sí, gate `liquidaciones.access` + middleware `can:` o `auth.admin`. |
| Migraciones con timestamps + FKs | Sí, una migración por tabla, en orden topológico. |
| Validación con Form Requests | Sí, `StoreLiquidacionRequest`, `UpdateLiquidacionRequest`, `StoreRouteRequest`. |
| PDF con `barryvdh/laravel-dompdf` | Sí, view Blade dedicada para el PDF, manteniendo el formato Excel. |

**Gates**: Ninguno bloqueante. Pasar a Phase 0.

## Project Structure

### Documentation (this feature)

```text
specs/002-liquidacion-viajes/
├── plan.md                       # Este archivo
├── spec.md                       # Spec funcional
├── research.md                   # Phase 0: decisiones técnicas
├── data-model.md                 # Phase 1: esquema relacional
├── contracts/
│   └── http-routes.md            # Endpoints HTTP y contratos request/response
├── quickstart.md                 # Cómo levantar, probar y validar la feature
├── checklists/
│   └── requirements.md           # Checklist de calidad del spec
└── tasks.md                      # /speckit-tasks (NO se crea en este comando)
```

### Source Code (repository root — Laravel monolito, extensión)

```text
app/
├── Models/
│   ├── ExpenseCategory.php       # Nuevo — catálogo seed de 16 filas
│   ├── Route.php                 # Nuevo — rutas
│   ├── RouteToll.php             # Nuevo — peajes asociados a ruta
│   ├── Liquidacion.php           # Nuevo — entidad principal
│   ├── LiquidacionExpense.php    # Nuevo — línea de gasto
│   ├── LiquidacionToll.php       # Nuevo — línea de peaje
│   └── LiquidacionStateLog.php   # Nuevo — auditoría de transiciones
├── Http/
│   ├── Controllers/
│   │   ├── LiquidacionController.php          # Nuevo — CRUD + listado + consolidado
│   │   ├── LiquidacionRouteController.php     # Nuevo — CRUD rutas + peajes
│   │   └── LiquidacionPdfController.php       # Nuevo — descarga PDF (separado para claridad)
│   ├── Requests/
│   │   ├── StoreLiquidacionRequest.php
│   │   ├── UpdateLiquidacionRequest.php
│   │   ├── CloseLiquidacionRequest.php        # transición a Cerrada
│   │   ├── ReopenLiquidacionRequest.php       # transición a Borrador (con motivo)
│   │   ├── CancelLiquidacionRequest.php       # transición a Anulada (con motivo)
│   │   ├── StoreRouteRequest.php
│   │   └── UpdateRouteRequest.php
│   └── Middleware/
│       └── (reuso del middleware admin existente — sin código nuevo)
├── Policies/
│   ├── LiquidacionPolicy.php
│   └── RoutePolicy.php
└── Services/
    └── LiquidacionCalculator.php              # Servicio para cálculos individuales y agregados

database/
├── migrations/
│   ├── 2026_05_19_120000_create_expense_categories_table.php
│   ├── 2026_05_19_120100_create_routes_table.php
│   ├── 2026_05_19_120200_create_route_tolls_table.php
│   ├── 2026_05_19_120300_create_liquidaciones_table.php
│   ├── 2026_05_19_120400_create_liquidacion_expenses_table.php
│   ├── 2026_05_19_120500_create_liquidacion_tolls_table.php
│   └── 2026_05_19_120600_create_liquidacion_state_logs_table.php
└── seeders/
    └── ExpenseCategorySeeder.php              # Las 16 categorías fijas

resources/
├── views/
│   ├── liquidaciones/
│   │   ├── index.blade.php                    # Listado + filtros + consolidado
│   │   ├── create.blade.php                   # Formulario nuevo
│   │   ├── edit.blade.php                     # Formulario edición (solo Borrador)
│   │   ├── show.blade.php                     # Vista solo lectura (Cerrada/Anulada)
│   │   ├── pdf.blade.php                      # Layout del PDF (mimick Excel)
│   │   └── partials/
│   │       ├── _form.blade.php                # Form reusable create/edit
│   │       ├── _expenses-table.blade.php
│   │       ├── _tolls-table.blade.php
│   │       └── _consolidado-panel.blade.php
│   └── liquidaciones/routes/
│       ├── index.blade.php
│       ├── create.blade.php
│       └── edit.blade.php
└── js/
    └── liquidacion-form.js                    # Alpine.js component para cálculos en vivo

routes/
└── web.php                                    # Agregar rutas resource + custom (close/reopen/cancel/pdf)

tests/
├── Feature/
│   ├── LiquidacionCrudTest.php                # US1 + US3 acceptance
│   ├── LiquidacionPdfTest.php                 # US2 acceptance
│   ├── LiquidacionConsolidadoTest.php         # US4 acceptance
│   ├── LiquidacionRouteCrudTest.php           # US5 acceptance
│   ├── LiquidacionStateTransitionsTest.php    # Estados + auditoría
│   └── LiquidacionAuthorizationTest.php       # FR-015 (solo admin)
└── Unit/
    └── LiquidacionCalculatorTest.php          # Fórmulas individuales y agregadas
```

**Structure Decision**: Extensión del Laravel monolito existente — sin sub-proyectos. Todos los archivos siguen las convenciones que ya hay en `app/Models`, `app/Http/Controllers`, `resources/views/<entidad>/<accion>.blade.php`, etc. La sección "Rutas" del módulo vive bajo `resources/views/liquidaciones/routes/` para no contaminar el namespace global `routes/` (Laravel ya usa esa carpeta para `routes/web.php`).

## Phase 0: Outline & Research

**Output**: [research.md](research.md) — decisiones técnicas resueltas:

- Elección de motor PDF (dompdf vs TCPDF vs print del browser)
- Estrategia de cálculo (stored vs computed para totales)
- Estrategia para las 16 categorías fijas (enum, config, o tabla seed)
- Estrategia de agregación del consolidado (Eloquent vs raw SQL, en BD vs en PHP)
- Estrategia de transición de estados (Eloquent state pattern vs columna `enum` + service)
- Acceso (gate vs policy vs middleware vs sidebar role check)
- Integración con sidebar e i18n existentes
- Captura de firma del conductor en v1

## Phase 1: Design & Contracts

**Outputs:**

- [data-model.md](data-model.md) — esquema relacional completo, FKs, índices, reglas
- [contracts/http-routes.md](contracts/http-routes.md) — endpoints HTTP, payloads, respuestas
- [quickstart.md](quickstart.md) — cómo correr migrations, seeds, probar el flujo completo, verificar PDF

**Update CLAUDE.md**: cambiar el plan activo entre los marcadores `<!-- SPECKIT START -->` … `<!-- SPECKIT END -->` para apuntar a esta plan.md.

### Post-Design Constitution Re-Check

Sin reglas formales en constitución; la post-revisión solo confirma que las convenciones Laravel se respetan en data-model y contracts. ✅ Pasa.

## Complexity Tracking

No hay violaciones de constitución (constitución en template). Posibles puntos de complejidad inherentes a justificar al equipo:

| Decisión | Por qué | Alternativa más simple rechazada porque |
|---|---|---|
| 7 tablas nuevas en una sola feature | Modelo relacional limpio que soporta reporting (group by category/route/driver). Si se denormaliza pierde flexibilidad de reportes futuros. | Una sola tabla con 32 columnas (16 gastos + N peajes) hace imposible los queries `GROUP BY categoria` y rompe normalización 3FN. |
| Storage de totales calculados en la tabla `liquidaciones` (no solo computed accessors) | Performance del consolidado (SC-007 <2s con 1000 filas requiere agregación SQL directa, no recálculo en PHP). | Computed accessors en Eloquent obligan a cargar todas las líneas en cada query del consolidado → no escala. |
| Tabla aparte `liquidacion_state_logs` (auditoría) | FR-012 obliga a registrar quién/cuándo/motivo de cada transición; con motivo libre largo no cabe en columnas del registro principal. | Columnas `closed_at`, `cancelled_at` en `liquidaciones` perdería el log histórico de reaperturas múltiples. |
| Separar `LiquidacionPdfController` del controlador principal | Mantiene el controlador CRUD enfocado; el PDF tiene side-effects distintos (stream binario, no JSON/HTML normal). | Método extra en el controller principal mezcla responsabilidades y dificulta tests. |
