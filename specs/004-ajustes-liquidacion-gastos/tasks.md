---
description: "Task list for Ajustes de liquidación y gastos mensuales"
---

# Tasks: Ajustes de liquidación y gastos mensuales

**Input**: Design documents from `/specs/004-ajustes-liquidacion-gastos/`
**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/http-routes.md](contracts/http-routes.md)

**Tests**: INCLUIDOS — el plan y quickstart definen feature tests como entregables (convención de specs 002/003). Las feature tests requieren **MySQL real**, no sqlite `:memory:` ([[feature-tests-need-mysql]]).

**Organization**: Tareas agrupadas por user story para implementación/validación independiente. Monolito Laravel — rutas relativas desde la raíz del repo.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede correr en paralelo (archivo distinto, sin dependencias pendientes)
- **[Story]**: US1–US4 según spec.md
- Rutas de archivo exactas incluidas

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Preparar el entorno de pruebas (el código base ya existe).

- [x] T001 Configurar conexión de pruebas a **MySQL** (no sqlite) en `phpunit.xml` y/o `.env.testing`, creando una base de pruebas que aplique el historial de migraciones del proyecto ([[feature-tests-need-mysql]]).

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Cambios de esquema y núcleo de cálculo compartidos por **US2 y US4** (manifiesto). ⚠️ US2 y US4 NO pueden empezar hasta completar esta fase. **US1 y US3 NO dependen de esta fase** (pueden empezar tras Setup).

- [x] T002 Crear migración `database/migrations/2026_05_27_000100_adjust_liquidaciones_anticipos_descuentos.php`: rename `anticipo`→`anticipo_empresa` y `sobreanticipo`→`anticipo_conductor` vía `DB::statement("ALTER TABLE liquidaciones CHANGE ...")`; agregar `descuentos DECIMAL(12,0) NOT NULL DEFAULT 0`, `saldo_pendiente DECIMAL(12,0) NOT NULL DEFAULT 0` (sin UNSIGNED, permite negativos), `manifiesto_pdf_path VARCHAR(255) NULL`; backfill `saldo_pendiente = anticipo_empresa - descuentos`; `down()` inverso.
- [x] T003 Actualizar `app/Models/Liquidacion.php`: en `$fillable` quitar `anticipo`/`sobreanticipo`, agregar `anticipo_empresa`, `anticipo_conductor`, `descuentos`, `saldo_pendiente`, `manifiesto_pdf_path`; ajustar `$casts` (renombrar claves + `descuentos`/`saldo_pendiente` → integer); agregar helper `hasManifiesto(): bool`.
- [x] T004 Actualizar `app/Services/LiquidacionCalculator.php`: `computeTotalAnticipos($empresa,$conductor)`; en `recalcAndSave` leer `anticipo_empresa`/`anticipo_conductor` y setear `saldo_pendiente = (int)$liq->anticipo_empresa - (int)$liq->descuentos`; agregar `COALESCE(SUM(descuentos),0) as sum_descuentos` en `aggregate` y `aggregateByMonth`.

**Checkpoint**: La app compila con los campos renombrados y `saldo_pendiente` calculado; US2/US4 pueden comenzar.

---

## Phase 3: User Story 1 - Gastos mensuales (Priority: P1) 🎯 MVP

**Goal**: Sub-módulo CRUD admin-only de costos fijos mensuales por conductor/placa, con lista paginada y filtro por placa/mes.

**Independent Test**: Como admin, abrir "Gastos mensuales", crear un registro (conductor + 7 valores), verlo en la lista, editarlo, filtrar por placa, eliminarlo; confirmar paginación con >25 registros; confirmar que un usuario `placas` recibe 403.

**Dependencias**: Solo requiere Phase 1 (independiente de Phase 2).

### Tests for User Story 1 ⚠️ (escribir primero, deben FALLAR)

- [x] T005 [P] [US1] `tests/Feature/MonthlyExpenseCrudTest.php`: crear/editar/eliminar como admin, filtro por placa, paginación, unicidad conductor+período (FR-007b), placa derivada server-side.
- [x] T006 [P] [US1] `tests/Feature/MonthlyExpenseAdminOnlyTest.php`: usuario `placas` recibe 403 en index/create/store/edit/update/destroy; admin OK (FR-009).

### Implementation for User Story 1

- [x] T007 [US1] Crear migración `database/migrations/2026_05_27_000000_create_monthly_expenses_table.php` con columnas e índices de [data-model.md](data-model.md) (UNIQUE driver_id+anio+mes, INDEX vehicle_plate, INDEX anio+mes, FKs driver/users).
- [x] T008 [P] [US1] Crear `app/Models/MonthlyExpense.php` (fillable, casts int, `driver(): BelongsTo`, accessor `total`, scopes `placa`/`periodo`).
- [x] T009 [US1] En `app/Providers/AppServiceProvider.php` definir gate `liquidaciones.gastos.access` (`return $user->rol === 'admin';`).
- [x] T010 [US1] En `routes/web.php`, dentro del grupo `liquidaciones`, agregar sub-grupo con `can:liquidaciones.gastos.access` y rutas resource `liquidaciones.gastos.*` (index/create/store/edit/update/destroy) con parámetro `gasto`.
- [x] T011 [P] [US1] Crear `app/Http/Requests/StoreMonthlyExpenseRequest.php` (validación de driver_id/anio/mes/montos≥0/otro_descripcion; unicidad conductor+período con `Rule::unique`; `authorize` vía gate).
- [x] T012 [P] [US1] Crear `app/Http/Requests/UpdateMonthlyExpenseRequest.php` (igual a Store con `->ignore($gasto)` en la unicidad).
- [x] T013 [US1] Crear `app/Http/Controllers/MonthlyExpenseController.php`: `index` (filtros `placa`/`anio`/`mes` + paginación 25 + lista de placas para el select), `create`, `store` (set `vehicle_plate` desde `driver`, `created_by`), `edit`, `update` (`updated_by`), `destroy` (depende de T008, T011, T012).
- [x] T014 [P] [US1] Crear `resources/views/liquidaciones/gastos/index.blade.php`: tabla paginada con columnas (conductor, placa, período, montos, total), formulario de filtro por placa y mes/año, botón "Nuevo".
- [x] T015 [P] [US1] Crear `resources/views/liquidaciones/gastos/_form.blade.php`: select de conductor que autollena la placa vía `liquidaciones.drivers.info` (Alpine/fetch), inputs de los 7 montos + descripción "Otro", selector mes/año.
- [x] T016 [US1] En `resources/views/liquidaciones/index.blade.php` agregar botón/acceso "Gastos mensuales" envuelto en `@can('liquidaciones.gastos.access')`.

**Checkpoint**: US1 completamente funcional y testeable de forma independiente (MVP).

---

## Phase 4: User Story 2 - Anticipos diferenciados, descuentos y saldo pendiente (Priority: P2)

**Goal**: Capturar anticipo empresa y anticipo conductor por separado, registrar descuentos de la transportadora, mostrar saldo pendiente (= anticipo empresa − descuentos) y la línea de descuentos en totales (pantalla + PDF + consolidado).

**Independent Test**: Crear/editar liquidación con anticipo empresa, anticipo conductor y descuentos; verificar `total_anticipos = empresa + conductor`, `saldo_pendiente = empresa − descuentos`, y que los descuentos aparecen como línea en el panel de totales y en el PDF.

**Dependencias**: Phase 2 (T002–T004).

### Tests for User Story 2 ⚠️

- [x] T017 [P] [US2] `tests/Feature/LiquidacionAnticiposDescuentosTest.php`: `total_anticipos` y `saldo_pendiente` correctos tras store/update; `sum_descuentos` en el consolidado; campos derivados no aceptados desde el cliente.

### Implementation for User Story 2

- [x] T018 [US2] Actualizar `app/Http/Requests/StoreLiquidacionRequest.php` (y la validación de update correspondiente): reemplazar reglas `anticipo`/`sobreanticipo` por `anticipo_empresa`/`anticipo_conductor` (required, ≥0) y agregar `descuentos` (required, ≥0, default 0).
- [x] T019 [US2] Actualizar `app/Http/Controllers/LiquidacionController.php` `store`/`update` para persistir los nuevos campos (no aceptar `saldo_pendiente`/`total_anticipos` del cliente; recálculo vía `LiquidacionCalculator`).
- [x] T020 [US2] Actualizar `resources/views/liquidaciones/partials/_form.blade.php`: inputs "Anticipo empresa", "Anticipo conductor" y "Descuentos" (reemplazan anticipo/sobreanticipo).
- [x] T021 [US2] Actualizar `resources/js/liquidacion-form.js`: renombrar campos de anticipo en el estado Alpine, agregar `descuentos` y cómputo/visualización de `saldoPendiente = anticipoEmpresa - descuentos` en el panel de totales.
- [x] T022 [US2] Actualizar `resources/views/liquidaciones/show.blade.php`: mostrar anticipo empresa, anticipo conductor, descuentos y saldo pendiente (reemplaza ANTICIPO/SOBREANTICIPO).
- [x] T023 [US2] Actualizar `resources/views/liquidaciones/pdf.blade.php`: etiquetas de anticipos desglosados y **línea explícita de Descuentos** en los totales (FR-014).
- [x] T024 [US2] Actualizar el consolidado en `resources/views/liquidaciones/index.blade.php` para mostrar `sum_descuentos` del agregado (US6/FR-014).

**Checkpoint**: US1 y US2 funcionan de forma independiente.

---

## Phase 5: User Story 3 - Eliminar un peaje en los peajes del viaje (Priority: P3)

**Goal**: Botón para eliminar una fila de peaje en "Peajes del viaje"; la fila desaparece de la liquidación y de la sumatoria, sin tocar el catálogo de la ruta.

**Independent Test**: Abrir una liquidación con peajes precargados, eliminar una fila, guardar; confirmar que la fila ya no existe ni suma en `sumatoria_peajes`, y que `route_tolls` de la ruta queda intacto.

**Dependencias**: Funcionalmente independiente; comparte `resources/js/liquidacion-form.js` con US2 (no marcar [P] entre sí).

### Tests for User Story 3 ⚠️

- [x] T025 [P] [US3] `tests/Feature/LiquidacionTollDeleteTest.php`: quitar un peaje del payload de update elimina la fila de `liquidacion_tolls` y recalcula `sumatoria_peajes`; `route_tolls` sin cambios (FR-017/FR-018).

### Implementation for User Story 3

- [x] T026 [US3] En `resources/views/liquidaciones/partials/_tolls-table.blade.php` agregar un botón "eliminar" por fila de peaje.
- [x] T027 [US3] En `resources/js/liquidacion-form.js` agregar método `removeToll(index)` que hace `splice()` del array de peajes y dispara el recálculo de totales.
- [x] T028 [US3] Verificar/ajustar `app/Http/Controllers/LiquidacionController.php` `update` para que sincronice `liquidacion_tolls` por **full-replace** desde el payload (borrar filas ausentes); ajustar si hiciera merge incremental.

**Checkpoint**: US1, US2 y US3 funcionan de forma independiente.

---

## Phase 6: User Story 4 - Adjuntar el PDF del manifiesto al viaje (Priority: P3)

**Goal**: Subir un único PDF de manifiesto por liquidación (reemplazable/eliminable), con ver/descargar, respetando el aislamiento del rol `placas`.

**Independent Test**: Crear/editar liquidación, subir un PDF de manifiesto, guardar, reabrir/descargar; reemplazarlo (se borra el anterior) y eliminarlo; subir un no-PDF y confirmar rechazo con mensaje.

**Dependencias**: Phase 2 (columna `manifiesto_pdf_path` en T002). Comparte `_form.blade.php`/`show.blade.php`/`StoreLiquidacionRequest`/`LiquidacionController` con US2 (no marcar [P] entre sí).

### Tests for User Story 4 ⚠️

- [x] T029 [P] [US4] `tests/Feature/LiquidacionManifiestoPdfTest.php` (con `Storage::fake`): subir PDF asocia archivo; ver/descargar; reemplazo borra el anterior; eliminar limpia la columna; rechazo de no-PDF y de archivo > máx (FR-019..FR-021).

### Implementation for User Story 4

- [x] T030 [US4] En `app/Http/Requests/StoreLiquidacionRequest.php` (y update) agregar `manifiesto_pdf` (nullable, `mimetypes:application/pdf`, `max:10240`).
- [x] T031 [US4] En `app/Http/Controllers/LiquidacionController.php` `store`/`update`: si llega `manifiesto_pdf`, guardar en `storage/app/manifiestos`, borrar el archivo anterior si existía, setear `manifiesto_pdf_path` (solo en estado `borrador`).
- [x] T032 [US4] En `app/Http/Controllers/LiquidacionController.php` agregar `manifiesto(Liquidacion $liquidacion)` (autoriza `view` por policy; stream/descarga; 404 si no hay archivo) y `destroyManifiesto(Liquidacion $liquidacion)` (autoriza `update` + borrador; borra archivo y limpia columna).
- [x] T033 [US4] En `routes/web.php` agregar `GET liquidaciones/{liquidacion}/manifiesto` (`liquidaciones.manifiesto`) y `DELETE liquidaciones/{liquidacion}/manifiesto` (`liquidaciones.manifiesto.destroy`).
- [x] T034 [US4] En `resources/views/liquidaciones/partials/_form.blade.php` agregar input `file` `manifiesto_pdf` y asegurar `enctype="multipart/form-data"` en el form.
- [x] T035 [US4] En `resources/views/liquidaciones/show.blade.php` agregar enlaces ver/descargar y acción eliminar del manifiesto (cuando exista).

**Checkpoint**: Las 4 user stories funcionan de forma independiente.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [ ] T036 [P] Crear factory/seeder de `monthly_expenses` (≥500 filas) para validar paginación/rendimiento (SC-003) en `database/factories/MonthlyExpenseFactory.php` y/o un seeder. — DIFERIDA (la validación de rendimiento depende de poder correr la suite; ver T038).
- [x] T037 [P] Rebuild de assets con Vite (`npm run build`) y verificar `public/js/app.js` / `public/css/app.css` actualizados (cache-busting `filemtime`). ✅ bundle incluye `saldoPendiente`/`anticipoEmpresa`.
- [x] T038 Ejecutar los tests contra MySQL. ✅ **37/37 verdes** para `Placas|Liquidacion|MonthlyExpense` (17 nuevos + 20 existentes). Para desbloquear `migrate:fresh` se arreglaron DOS migraciones preexistentes y ajenas a 004 (índices huérfanos): `2025_12_09_153223_create_products_table` (faltaba `->unique()` en `codigo`) y `2025_12_27_130207_create_container_product_table` (faltaba el nombre explícito `unique_container_product`). Los 14 fallos restantes de la suite completa son tests de scaffolding de Breeze preexistentes (`Auth/*`, `ExampleTest`: `nombre_completo` requerido, `/`→login), no relacionados con esta feature.
- [ ] T039 Ejecutar el checklist de validación manual de [quickstart.md](quickstart.md) (mapeo a SC-001…SC-008). — PENDIENTE (validación manual en navegador por el usuario).

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: tras Setup. Bloquea **US2 y US4** (no a US1/US3).
- **US1 (Phase 3)**: tras Setup. Independiente de Phase 2 → puede empezar de inmediato (MVP).
- **US2 (Phase 4)**: tras Phase 2.
- **US3 (Phase 5)**: tras Setup (funcionalmente); comparte `liquidacion-form.js` con US2.
- **US4 (Phase 6)**: tras Phase 2 (necesita `manifiesto_pdf_path`); comparte archivos de form/controller/request con US2.
- **Polish (Phase 7)**: tras las stories deseadas.

### Cross-Story File Coupling (evitar conflictos)

- `resources/views/liquidaciones/partials/_form.blade.php` → US2 (T020), US4 (T034)
- `resources/js/liquidacion-form.js` → US2 (T021), US3 (T027)
- `app/Http/Requests/StoreLiquidacionRequest.php` → US2 (T018), US4 (T030)
- `app/Http/Controllers/LiquidacionController.php` → US2 (T019), US3 (T028), US4 (T031/T032)
- `resources/views/liquidaciones/show.blade.php` → US2 (T022), US4 (T035)

→ Estas tareas NO son [P] entre stories; si se trabajan US2/US3/US4 en paralelo, serializar las ediciones de estos archivos compartidos.

### Within Each User Story

- Tests primero (deben fallar) → migración/modelo → request/servicio → controlador → rutas → vistas/JS.

### Parallel Opportunities

- US1 es completamente paralelo a Phase 2 (archivos disjuntos).
- Dentro de US1: T005/T006 (tests) en paralelo; T008/T011/T012/T014/T015 en paralelo (archivos distintos) tras sus prerequisitos.
- Tests por story marcados [P] corren juntos.

---

## Parallel Example: User Story 1

```bash
# Tests de US1 juntos (deben fallar primero):
Task: "tests/Feature/MonthlyExpenseCrudTest.php"
Task: "tests/Feature/MonthlyExpenseAdminOnlyTest.php"

# Tras la migración (T007), archivos disjuntos en paralelo:
Task: "app/Models/MonthlyExpense.php"
Task: "app/Http/Requests/StoreMonthlyExpenseRequest.php"
Task: "app/Http/Requests/UpdateMonthlyExpenseRequest.php"
Task: "resources/views/liquidaciones/gastos/index.blade.php"
Task: "resources/views/liquidaciones/gastos/_form.blade.php"
```

---

## Implementation Strategy

### MVP First (solo US1)

1. Phase 1 (Setup) → 2. Phase 3 (US1) completa → 3. **PARAR y VALIDAR** US1 (admin-only, CRUD, filtro, paginación) → demo.

US1 no requiere Phase 2, así que es el MVP más rápido y de mayor valor.

### Incremental Delivery

1. Setup → US1 (MVP, demo) → Foundational (Phase 2) → US2 (demo) → US3 (demo) → US4 (demo) → Polish.
2. Cada story agrega valor sin romper las anteriores; serializar ediciones de los archivos compartidos listados arriba.

---

## Notes

- [P] = archivos distintos, sin dependencias pendientes.
- Feature tests sobre **MySQL** real ([[feature-tests-need-mysql]]); usar `Storage::fake` para el manifiesto.
- El rename de columnas (T002) es el cambio más sensible: confirmar que ninguna referencia a `anticipo`/`sobreanticipo` queda en modelo, calculator, requests, vistas, PDF ni JS.
- Commit por tarea o grupo lógico; el usuario gestiona los commits (sin co-autor Claude).
- Total: 39 tareas (US1: 12 · US2: 8 · US3: 4 · US4: 7 · Setup/Foundational: 4 · Polish: 4).
