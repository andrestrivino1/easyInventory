# Implementation Plan: Ajustes de liquidación y gastos mensuales

**Branch**: `004-ajustes-liquidacion-gastos` | **Date**: 2026-05-27 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from [/specs/004-ajustes-liquidacion-gastos/spec.md](spec.md)

## Summary

Cinco ajustes al módulo existente **Liquidación de Viajes** (specs 002 + 003), todos dentro del monolito Laravel actual, sin proyectos nuevos:

1. **Gastos mensuales** (US1, P1): nuevo sub-módulo CRUD **solo para administradores**. Tabla nueva `monthly_expenses` con costos fijos por conductor+placa+período (mes/año): Sueldo conductor, Seguridad social, Cuota banco, Cuota tercero, Satelital, Seguro vehículo y Otro (monto + descripción). Lista paginada, filtro por placa y por mes, unicidad por conductor+período. Registro independiente: no entra en el cálculo de las liquidaciones por viaje.
2. **Anticipos diferenciados + descuentos + saldo pendiente** (US2, P2): renombrar `anticipo`→`anticipo_empresa` y `sobreanticipo`→`anticipo_conductor` (migración de datos), agregar `descuentos` y campo derivado `saldo_pendiente = anticipo_empresa − descuentos`. Mostrar línea de descuentos en totales (pantalla y PDF).
3. **Eliminar peaje del viaje** (US3, P3): botón de eliminar fila en la tabla "Peajes del viaje" (Alpine), aprovechando que el guardado ya hace sync delete+insert de `liquidacion_tolls`.
4. **Manifiesto PDF** (US4, P3): nueva columna `manifiesto_pdf_path` en `liquidaciones`, subida de un único PDF (reemplazable/eliminable), con ver/descargar, replicando el patrón de `drivers.social_security_pdf`.

Tabla nueva: 1 (`monthly_expenses`). Tablas alteradas: 1 (`liquidaciones`: rename de 2 columnas + 3 columnas nuevas). El resto es lógica de servicio, controladores, vistas Blade y Alpine.

## Technical Context

**Language/Version**: PHP 8.2.12 (`composer.json` exige `^7.4 || ^8.0`); JavaScript ES2022 para Alpine.js.
**Primary Dependencies**: Laravel 8.75, Eloquent, Blade, Alpine.js 3.x, Tailwind + Bootstrap 5 (CDN del módulo), `barryvdh/laravel-dompdf` ^2.2 (PDF), Vite (build de `resources/js/*` → `public/js/app.js`).
**Storage**: MySQL/MariaDB (XAMPP), InnoDB, `utf8mb4_unicode_ci`. Archivos PDF en `storage/app` (igual que `social_security_pdf`/`itrs`).
**Testing**: PHPUnit 9.5 (Feature tests). **Nota**: las feature tests del módulo requieren MySQL real, no sqlite `:memory:` — el historial de migraciones falla al migrar en sqlite (ver memoria [[feature-tests-need-mysql]]). Configurar conexión de pruebas a MySQL.
**Target Platform**: Web monolítica Laravel en Apache/XAMPP (Windows en dev).
**Project Type**: Web monolítica — extender repo existente (modelos, controladores, vistas, migraciones).
**Performance Goals**:
- Lista de gastos mensuales con ≥500 registros: paginada, <500ms por página (SC-003).
- Filtro por placa/mes: índices en `monthly_expenses(vehicle_plate)` y `(anio, mes)`.
- Totales de liquidación con descuentos: recálculo en cliente (Alpine) <1s; persistencia de cached fields en `save()`.
**Constraints**:
- Gastos mensuales **exclusivo admin** (FR-009): gate nuevo `liquidaciones.gastos.access` (solo `rol === 'admin'`); el sidebar/botón no se muestra a `placas`.
- Ediciones de liquidación solo en estado `borrador` (FR-022), reusando la policy existente.
- Manifiestos PDF respetan el aislamiento del rol `placas` (solo de sus conductores) vía la policy/scoping ya existente de liquidaciones.
- Rename de columnas debe actualizar **todas** las referencias (`anticipo`/`sobreanticipo`) en modelo, calculator, requests, vistas, PDF y JS.
- Montos COP enteros `DECIMAL(12,0)` (FR-023).
**Scale/Scope**: ~50–200 liquidaciones/mes; ~20–50 conductores → ~20–50 gastos mensuales/mes. 1–3 admins. Nuevos endpoints: ~7 (gastos mensuales CRUD + manifiesto ver/descargar/eliminar).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Status**: ⚠️ La constitución (`.specify/memory/constitution.md`) está en estado **template** (placeholders sin ratificar). No hay principios formales que evaluar. Se aplican como gate implícito las convenciones ya establecidas en specs 002/003 y el codebase:

| Convención del codebase | Aplicación en esta feature |
|---|---|
| MVC Laravel + resource routes | `MonthlyExpenseController` (resource bajo prefijo `liquidaciones`); reuso de `LiquidacionController` para manifiesto. |
| Eloquent con relaciones explícitas | Modelo `MonthlyExpense` (`belongsTo Driver`); reuso de `Liquidacion`. |
| Form Requests para validación | `StoreMonthlyExpenseRequest`, `UpdateMonthlyExpenseRequest`; extender `StoreLiquidacionRequest`/`UpdateLiquidacionRequest`. |
| Gates/policies por rol | Gate nuevo `liquidaciones.gastos.access` (admin); reuso de `LiquidacionPolicy` y gate `liquidaciones.access`. |
| Migraciones idempotentes con FKs/índices | 1 migración nueva (`monthly_expenses`), 1 de alteración (`liquidaciones`); rename con `Schema::table`+`renameColumn` (requiere `doctrine/dbal`). |
| PDF/archivos como `social_security_pdf` | Manifiesto PDF guardado en `storage/app`, ruta de ver/descargar autenticada. |
| Blade `@extends('layouts.app')` + Alpine para totales | Vistas de gastos mensuales y ajustes al `_form`/`_tolls-table`/`show`/`pdf`. |
| Cálculo centralizado en `LiquidacionCalculator` | Ampliar el servicio con `descuentos` y `saldo_pendiente`. |

**Gates**: Ninguno bloqueante. La dependencia `doctrine/dbal` (necesaria para `renameColumn` en Laravel 8) se evalúa en Phase 0 (alternativa: migración manual con SQL `CHANGE`).

## Project Structure

### Documentation (this feature)

```text
specs/004-ajustes-liquidacion-gastos/
├── plan.md              # Este archivo
├── spec.md              # Spec funcional (con Clarifications)
├── research.md          # Phase 0: decisiones técnicas
├── data-model.md        # Phase 1: esquema (monthly_expenses + alter liquidaciones)
├── contracts/
│   └── http-routes.md   # Phase 1: endpoints HTTP
├── quickstart.md        # Phase 1: levantar, probar y validar
├── checklists/
│   └── requirements.md  # Checklist de calidad de la spec
└── tasks.md             # Phase 2 (lo crea /speckit-tasks)
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── MonthlyExpense.php                      # NUEVO
│   └── Liquidacion.php                          # MOD: fillable/casts (rename + nuevos campos)
├── Http/
│   ├── Controllers/
│   │   ├── MonthlyExpenseController.php         # NUEVO (index/create/store/edit/update/destroy)
│   │   ├── LiquidacionController.php            # MOD: store/update (descuentos, manifiesto), manifiesto ver/eliminar
│   │   └── LiquidacionPdfController.php         # MOD: descuentos en el PDF (vía vista)
│   └── Requests/
│       ├── StoreMonthlyExpenseRequest.php       # NUEVO
│       ├── UpdateMonthlyExpenseRequest.php      # NUEVO
│       ├── StoreLiquidacionRequest.php          # MOD: anticipo_empresa/anticipo_conductor/descuentos/manifiesto
│       └── UpdateLiquidacionRequest.php         # MOD (si existe; si no, validación en update)
├── Policies/
│   └── LiquidacionPolicy.php                    # (reuso; sin cambios salvo scoping de manifiesto)
├── Providers/
│   └── AppServiceProvider.php                   # MOD: gate liquidaciones.gastos.access (admin)
└── Services/
    └── LiquidacionCalculator.php                # MOD: descuentos + saldo_pendiente

database/migrations/
├── 2026_05_27_000000_create_monthly_expenses_table.php          # NUEVO
└── 2026_05_27_000100_adjust_liquidaciones_anticipos_descuentos.php  # NUEVO (rename + add cols)

resources/
├── views/liquidaciones/
│   ├── index.blade.php                          # MOD: botón "Gastos mensuales" (solo admin)
│   ├── show.blade.php                           # MOD: anticipos, descuentos, saldo pendiente, manifiesto
│   ├── pdf.blade.php                            # MOD: línea de descuentos en totales
│   ├── partials/_form.blade.php                 # MOD: campos anticipos/descuentos + input PDF manifiesto
│   ├── partials/_tolls-table.blade.php          # MOD: botón eliminar fila de peaje
│   └── gastos/                                   # NUEVO
│       ├── index.blade.php                       # lista paginada + filtro placa/mes
│       └── _form.blade.php                       # create/edit
└── js/
    └── liquidacion-form.js                       # MOD: nombres de anticipos, descuentos/saldo, removeToll()

routes/web.php                                    # MOD: rutas gastos mensuales + manifiesto

tests/Feature/
├── MonthlyExpenseCrudTest.php                    # NUEVO (US1)
├── MonthlyExpenseAdminOnlyTest.php               # NUEVO (US1 / FR-009)
├── LiquidacionAnticiposDescuentosTest.php        # NUEVO (US2)
├── LiquidacionTollDeleteTest.php                 # NUEVO (US3)
└── LiquidacionManifiestoPdfTest.php              # NUEVO (US4)
```

**Structure Decision**: Monolito Laravel existente. Se reutiliza el prefijo de rutas `liquidaciones.` y el layout/Alpine ya presentes. Gastos mensuales es un sub-recurso del módulo (rutas `liquidaciones.gastos.*`) protegido por un gate admin-only adicional. El manifiesto PDF se adjunta a `liquidaciones` (1:1) reusando el patrón de archivos de `drivers`.

## Complexity Tracking

> Sin violaciones de constitución (constitución en template). El único punto de fricción es el **rename de columnas** `anticipo`/`sobreanticipo`, que toca múltiples archivos.

| Decisión | Por qué se necesita | Alternativa más simple y por qué se rechaza (o no) |
|---|---|---|
| Rename de columnas (vs. agregar nuevas y deprecar) | FR-011 pide reemplazar y migrar; mantener nombres viejos generaría deuda y confusión semántica (empresa/conductor). | Agregar `anticipo_empresa`/`anticipo_conductor` nuevas y copiar datos, dejando las viejas nullables — se evita por duplicar columnas y dejar nombres engañosos. Si `doctrine/dbal` no está disponible, se hace `DB::statement` con `ALTER TABLE ... CHANGE`. |
| `saldo_pendiente` como columna cacheada (vs. solo calculado en vista) | Mantener coherencia con los demás `sumatoria_*`/`saldo_viaje` que ya se persisten; permite mostrarlo en listas/PDF sin recomputar. | Calcular al vuelo en cada vista — válido, pero rompe la simetría con el resto del modelo; se persiste por consistencia. |
