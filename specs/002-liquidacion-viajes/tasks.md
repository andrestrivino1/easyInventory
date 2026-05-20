---
description: "Task list for Liquidación de Viajes implementation"
---

# Tasks: Liquidación de Viajes

**Input**: Design documents from [/specs/002-liquidacion-viajes/](.)
**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/http-routes.md](contracts/http-routes.md), [quickstart.md](quickstart.md)
**Tests**: Incluidos como Feature tests + 1 Unit test. Los acceptance scenarios del spec se mapean 1:1 a tests. Si se desea omitir tests para velocidad de MVP, se pueden saltar las tareas marcadas `(test)` sin romper el resto.

**Organization**: Tareas agrupadas por user story para entrega incremental e independiente. Orden de fase = orden de prioridad del spec.

## Format: `[ID] [P?] [Story?] Description`

- **[P]**: Puede correr en paralelo con otras tareas [P] de la misma fase (archivos distintos, sin dependencias intermedias).
- **[Story]**: Etiqueta US1..US5 sólo en fases de user story.
- **Path Convention**: Laravel monolito en raíz del repo. Rutas son relativas al repo root.

---

## Phase 1: Setup (Infraestructura compartida)

**Purpose**: Confirmar deps, crear estructura de carpetas que no existen, registrar el módulo en sidebar.

- [ ] T001 Verificar que `barryvdh/laravel-dompdf` está cargado: correr `composer show barryvdh/laravel-dompdf` y confirmar versión ^2.2 (ya en composer.json; sin acción si está OK)
- [ ] T002 [P] Crear carpeta `resources/views/liquidaciones/partials/` y `resources/views/liquidaciones/routes/` con `.gitkeep` para que Git las trackee
- [ ] T003 [P] Crear carpeta `app/Services/` con `.gitkeep` si no existe (nuevo namespace en el repo)
- [ ] T004 Registrar el ítem "Liquidación de Viajes" en el sidebar `resources/views/layouts/app.blade.php` condicionado a `$user->rol === 'admin'`, con icono `bi-cash-coin` y enlace a `route('liquidaciones.index')`

**Checkpoint**: Estructura lista. Las tareas T001–T004 NO bloquean el desarrollo del backend.

---

## Phase 2: Foundational (Bloqueante para todas las user stories)

**Purpose**: Migrations, modelos base, seeder, policy/gate, registro de rutas Laravel. SIN esto ninguna user story puede empezar.

**⚠️ CRITICAL**: Todo lo siguiente DEBE completarse antes de Phase 3+.

### Migrations (orden topológico — no se pueden paralelizar entre sí pero sí con tareas no-migration)

- [ ] T005 Crear migration [database/migrations/2026_05_19_120000_create_expense_categories_table.php](../../database/migrations/2026_05_19_120000_create_expense_categories_table.php) per [data-model.md#tabla-1](data-model.md) con columnas id, code (unique), name, has_galones, sort_order, active, timestamps; ejecutar `php artisan migrate`
- [ ] T006 Crear migration [database/migrations/2026_05_19_120100_create_routes_table.php](../../database/migrations/2026_05_19_120100_create_routes_table.php) con columnas id, origen, destino, name, descripcion, active, timestamps + INDEX(active), INDEX(name)
- [ ] T007 Crear migration [database/migrations/2026_05_19_120200_create_route_tolls_table.php](../../database/migrations/2026_05_19_120200_create_route_tolls_table.php) con columnas id, route_id (FK CASCADE), name, suggested_value, sort_order, direction (ENUM), timestamps + INDEX(route_id, sort_order)
- [ ] T008 Crear migration [database/migrations/2026_05_19_120300_create_liquidaciones_table.php](../../database/migrations/2026_05_19_120300_create_liquidaciones_table.php) con todas las columnas de [data-model.md#tabla-4](data-model.md), incluidos los stored caches (`sumatoria_*`, `saldo_viaje`, `ganancia_viaje`, `a_favor_de`), FKs a drivers/routes/users, `deleted_at`, y los 6 índices (fecha_inicio, driver_id, route_id, vehicle_plate, estado, compuesto)
- [ ] T009 Crear migration [database/migrations/2026_05_19_120400_create_liquidacion_expenses_table.php](../../database/migrations/2026_05_19_120400_create_liquidacion_expenses_table.php) con id, liquidacion_id (FK CASCADE), expense_category_id (FK RESTRICT), valor, galones, timestamps + UNIQUE(liquidacion_id, expense_category_id)
- [ ] T010 Crear migration [database/migrations/2026_05_19_120500_create_liquidacion_tolls_table.php](../../database/migrations/2026_05_19_120500_create_liquidacion_tolls_table.php) con id, liquidacion_id (FK CASCADE), route_toll_id (FK SET NULL), name, valor, sort_order, direction, is_adhoc, is_used, timestamps + INDEX(liquidacion_id, sort_order)
- [ ] T011 Crear migration [database/migrations/2026_05_19_120600_create_liquidacion_state_logs_table.php](../../database/migrations/2026_05_19_120600_create_liquidacion_state_logs_table.php) con id, liquidacion_id (FK CASCADE), user_id (FK), from_state, to_state, motivo, created_at (sin updated_at)

### Seeder

- [ ] T012 Crear [database/seeders/ExpenseCategorySeeder.php](../../database/seeders/ExpenseCategorySeeder.php) que inserta las 16 categorías fijas con `code`, `name`, `has_galones` (1 solo para `acpm`), `sort_order` (1..16); registrarlo en `DatabaseSeeder.php`; correr `php artisan db:seed --class=ExpenseCategorySeeder`

### Modelos base

- [ ] T013 [P] Crear modelo [app/Models/ExpenseCategory.php](../../app/Models/ExpenseCategory.php) con `$fillable`, scope `active()`, scope `ordered()`
- [ ] T014 [P] Crear modelo [app/Models/Route.php](../../app/Models/Route.php) con `$fillable`, accessor `name` que devuelve `"{$origen} → {$destino}"`, mutator que mantiene cache de `name`, relación `tolls()` (hasMany RouteToll ordered by sort_order), scope `active()`
- [ ] T015 [P] Crear modelo [app/Models/RouteToll.php](../../app/Models/RouteToll.php) con `$fillable`, relación `route()` (belongsTo)
- [ ] T016 [P] Crear modelo [app/Models/Liquidacion.php](../../app/Models/Liquidacion.php) con `SoftDeletes` trait, `$fillable`, relaciones `driver()`, `route()`, `creator()`, `updater()`, `expenses()` (hasMany), `tolls()` (hasMany), `stateLogs()` (hasMany), enums casts para `estado` y `a_favor_de`, scopes `borrador()`, `cerrada()`, `anulada()`, `activas()` (excluye anuladas + soft-deleted)
- [ ] T017 [P] Crear modelo [app/Models/LiquidacionExpense.php](../../app/Models/LiquidacionExpense.php) con `$fillable`, relaciones `liquidacion()`, `category()`
- [ ] T018 [P] Crear modelo [app/Models/LiquidacionToll.php](../../app/Models/LiquidacionToll.php) con `$fillable`, relación `liquidacion()`, scope `used()` (where is_used = 1)
- [ ] T019 [P] Crear modelo [app/Models/LiquidacionStateLog.php](../../app/Models/LiquidacionStateLog.php) con `$fillable`, relaciones `liquidacion()`, `user()`, sin `updated_at` (`public $timestamps = false; protected $dates = ['created_at'];`)

### Autorización

- [ ] T020 En [app/Providers/AppServiceProvider.php](../../app/Providers/AppServiceProvider.php) método `boot()` definir `Gate::define('liquidaciones.access', fn ($user) => $user->rol === 'admin')`
- [ ] T021 Crear [app/Policies/LiquidacionPolicy.php](../../app/Policies/LiquidacionPolicy.php) con métodos `viewAny`, `view`, `create`, `update` (solo borrador), `delete` (solo borrador), `close` (solo borrador), `reopen` (solo cerrada), `cancel` (solo cerrada); todos delegan al gate `liquidaciones.access` + estado
- [ ] T022 [P] Crear [app/Policies/RoutePolicy.php](../../app/Policies/RoutePolicy.php) con CRUD methods que delegan a `liquidaciones.access`
- [ ] T023 Registrar las dos policies en [app/Providers/AuthServiceProvider.php](../../app/Providers/AuthServiceProvider.php) `$policies` array

### Registro de rutas Laravel

- [ ] T024 En [routes/web.php](../../routes/web.php) agregar grupo `Route::middleware(['auth','can:liquidaciones.access'])->prefix('liquidaciones')->name('liquidaciones.')->group(...)` que incluya los 21 endpoints listados en [contracts/http-routes.md](contracts/http-routes.md). Por ahora los controllers no existen — declarar nombres pero stub vacío si Laravel lo permite, o crear controller skeletons mínimos.
- [ ] T025 [P] Crear skeletons vacíos de controllers [app/Http/Controllers/LiquidacionController.php](../../app/Http/Controllers/LiquidacionController.php), [app/Http/Controllers/LiquidacionRouteController.php](../../app/Http/Controllers/LiquidacionRouteController.php), [app/Http/Controllers/LiquidacionPdfController.php](../../app/Http/Controllers/LiquidacionPdfController.php) con stub methods que devuelven `abort(501)`. Las implementaciones reales llegan en cada user story.

**Checkpoint**: Foundation lista. Migraciones aplicadas, modelos creados, gate definido, rutas registradas (devolviendo 501 placeholder). El módulo aparece en sidebar para admin.

---

## Phase 3: User Story 1 — Crear y guardar una liquidación (Priority: P1) 🎯 MVP

**Goal**: Admin puede crear, ver y editar liquidaciones en estado `Borrador` capturando todos los datos del formato, con cálculos en vivo en pantalla. MVP funciona con peajes ad-hoc (sin necesidad de US5 completa).

**Independent Test**: Crear liquidación con los datos del ejemplo del Excel (ACPM 1.344.636/120 gal, UREA 134.900, ..., flete 5.600.000) y verificar que el sistema calcule en pantalla saldo 629.464 y ganancia 2.260.264 ANTES de guardar; al guardar la liquidación queda persistida en estado `borrador` con esos valores.

### Servicio de cálculo

- [ ] T026 [US1] Crear [app/Services/LiquidacionCalculator.php](../../app/Services/LiquidacionCalculator.php) con métodos estáticos: `computeSumatoriaGastos(array $expenses): int`, `computeSumatoriaPeajes(array $tolls): int`, `computeTotalAnticipos(int $anticipo, int $sobreanticipo): int`, `computeSaldoViaje(int $totalAnticipos, int $sumatoriaGastosOperativos): int`, `computeGananciaViaje(int $valorFlete, int $sumatoriaGastosTotales): int`, `aFavorDe(int $saldo): string`. Implementar las fórmulas exactas de [spec.md#fórmulas-individuales](spec.md).

### Form Requests (validación)

- [ ] T027 [P] [US1] Crear [app/Http/Requests/StoreLiquidacionRequest.php](../../app/Http/Requests/StoreLiquidacionRequest.php) con `authorize()` delegado a policy `create` y `rules()` per [contracts/http-routes.md#post-liquidaciones](contracts/http-routes.md): driver_id exists+active, vehicle_plate required, route_id nullable+exists+active, transportadora required, anticipo/sobreanticipo/valor_flete integer min:0, fecha_fin >= fecha_inicio, expenses[*].category_code exists, expenses[*].galones solo si category.has_galones, tolls[*].direction in:ida,regreso
- [ ] T028 [P] [US1] Crear [app/Http/Requests/UpdateLiquidacionRequest.php](../../app/Http/Requests/UpdateLiquidacionRequest.php) heredando reglas de Store + `authorize()` delegado a policy `update`

### Controller — métodos CRUD core

- [ ] T029 [US1] Implementar `LiquidacionController::create()` en [app/Http/Controllers/LiquidacionController.php](../../app/Http/Controllers/LiquidacionController.php) que retorna `view('liquidaciones.create', compact('drivers','routes','categories'))` con drivers activos, routes activas y las 16 expense_categories ordenadas
- [ ] T030 [US1] Implementar `LiquidacionController::store(StoreLiquidacionRequest $request)` que en transacción: crea Liquidacion, sincroniza expenses + tolls, llama `LiquidacionCalculator` para calcular y persistir los stored caches, registra `created_by`/`updated_by`; redirige a `show` con flash; alerta FR-013 vía sessión flash `warning` si hay duplicado
- [ ] T031 [US1] Implementar `LiquidacionController::show(Liquidacion $liquidacion)` que retorna `view('liquidaciones.show', ['liq' => $liquidacion->load('driver','route','expenses.category','tolls','stateLogs.user')])`
- [ ] T032 [US1] Implementar `LiquidacionController::edit(Liquidacion $liquidacion)` que aplica policy `update` (rechaza si no es borrador) y retorna `view('liquidaciones.edit', …)` reusando el mismo partial del form
- [ ] T033 [US1] Implementar `LiquidacionController::update(UpdateLiquidacionRequest $request, Liquidacion $liquidacion)`: misma lógica transaccional que `store` (delete expenses/tolls existentes + recrear + recalcular), `updated_by = current user`

### Vistas Blade

- [ ] T034 [US1] Crear [resources/views/liquidaciones/partials/_form.blade.php](../../resources/views/liquidaciones/partials/_form.blade.php) con: cabecera (select conductor, placa auto-llenada, select ruta, transporte, anticipo, sobreanticipo, fechas, MFTO, teléfono, flete), tabla de gastos con 16 filas pre-renderizadas, tabla de peajes (vacía inicialmente, se llena al elegir ruta o con botón "Agregar peaje ad-hoc"), panel de totales calculados (read-only, refresh por Alpine). Marca con `x-data` el componente Alpine.
- [ ] T035 [US1] Crear [resources/views/liquidaciones/partials/_expenses-table.blade.php](../../resources/views/liquidaciones/partials/_expenses-table.blade.php) (sub-partial del form) que itera las 16 `expense_categories` con inputs de `valor` y, condicional a `has_galones`, también `galones`
- [ ] T036 [US1] Crear [resources/views/liquidaciones/partials/_tolls-table.blade.php](../../resources/views/liquidaciones/partials/_tolls-table.blade.php) con tabla dinámica de peajes manejada por Alpine (filas agregables/removibles), columnas PEAJE/VALOR + checkbox "usado" + flag is_adhoc visualmente diferenciado
- [ ] T037 [US1] Crear [resources/views/liquidaciones/create.blade.php](../../resources/views/liquidaciones/create.blade.php) que extiende `layouts.app` y `@includes('liquidaciones.partials._form')` con `action="{{ route('liquidaciones.store') }}"` y `method=POST`
- [ ] T038 [US1] Crear [resources/views/liquidaciones/edit.blade.php](../../resources/views/liquidaciones/edit.blade.php) análoga con method spoofing PUT y datos pre-llenados desde `$liq`
- [ ] T039 [US1] Crear [resources/views/liquidaciones/show.blade.php](../../resources/views/liquidaciones/show.blade.php) en modo read-only, con badges de estado, botones contextuales según estado (Editar/Cerrar/Reabrir/Anular/PDF/Eliminar), y sección "Historial" mostrando los `state_logs`

### Frontend (Alpine.js)

- [ ] T040 [US1] Crear [resources/js/liquidacion-form.js](../../resources/js/liquidacion-form.js) con componente Alpine `liquidacionForm()` que: reactivamente calcula `sumatoriaGastosOperativos`, `sumatoriaPeajes`, `sumatoriaGastosTotales`, `totalAnticipos`, `saldoViaje`, `gananciaViaje`, `aFavorDe`; replica las fórmulas de `LiquidacionCalculator` exactamente; expone método `loadTollsForRoute(routeId)` que hace fetch a `/liquidaciones/rutas/{id}/peajes` y prepoblar la tabla; método `loadDriverInfo(driverId)` que llena placa automáticamente
- [ ] T041 [US1] Importar `liquidacion-form.js` en [resources/js/app.js](../../resources/js/app.js) y correr `npm run build` para verificar que compila sin errores

### Endpoints AJAX helpers

- [ ] T042 [US1] Implementar `LiquidacionController::driverInfo(Driver $driver)` que devuelve JSON `{ id, name, vehicle_plate, vehicle_owner, phone }` per [contracts/http-routes.md#get-liquidacionesdriversidinfo](contracts/http-routes.md)
- [ ] T043 [US1] Implementar `LiquidacionController::duplicateCheck(Request $request)` que devuelve JSON `{ duplicate, existing_id, existing_fecha_inicio }` per FR-013, excluyendo anuladas y soft-deleted

### Tests (Feature)

- [ ] T044 [P] [US1] (test) Crear [tests/Feature/LiquidacionCrudTest.php](../../tests/Feature/LiquidacionCrudTest.php) con tests: `admin_can_create_liquidacion_with_example_data` (valida saldo=629464, ganancia=2260264), `non_admin_cannot_create`, `update_only_in_borrador`, `duplicate_warning_triggers`, `dates_validation`
- [ ] T045 [P] [US1] (test) Crear [tests/Unit/LiquidacionCalculatorTest.php](../../tests/Unit/LiquidacionCalculatorTest.php) con tests unitarios de las 6 fórmulas usando los valores del ejemplo

**Checkpoint US1**: ✅ Admin puede crear/ver/editar liquidaciones. Cálculos en pantalla coinciden con backend. MVP entregable.

---

## Phase 4: User Story 2 — Imprimir/exportar PDF (Priority: P2)

**Goal**: Cada liquidación tiene un botón "Descargar PDF" que genera el documento con el formato visual del Excel original, listo para firma del conductor.

**Independent Test**: Sobre una liquidación creada en US1, presionar PDF; el archivo descargado debe abrir y mostrar los mismos totales y la sección "FIRMA CONDUCTOR" en blanco. Para una liquidación anulada, el PDF debe incluir marca de agua "ANULADA".

### Vista PDF

- [ ] T046 [US2] Crear [resources/views/liquidaciones/pdf.blade.php](../../resources/views/liquidaciones/pdf.blade.php) con layout HTML+CSS estilo Excel: tabla principal con header (logo VIDRIOS J&P + LIQUIDACION DE VIAJE + PLACA + RUTA), bloque TRANSPORTE/ANTICIPO/SOBREANTICIPO/FECHAS, tabla de gastos (DESCRIPCION/VALOR/GALONES), tabla de peajes (PEAJE/VALOR) a la derecha, bloque inferior con SUMATORIA DE GASTOS, SUMATORIA DE PEAJES, SUMATORIA DE GASTOS (totales), TOTAL ANTICIPOS, SALDO VIAJE, VALOR FLETE, GANACIA VIAJE, A FAVOR DE; bloque FIRMA CONDUCTOR vacío. CSS embed para que dompdf lo renderice.
- [ ] T047 [US2] En [resources/views/liquidaciones/pdf.blade.php](../../resources/views/liquidaciones/pdf.blade.php) agregar marca de agua diagonal "ANULADA" en rojo translúcido (`position: fixed`, rotate -30deg) condicionada a `$liq->estado === 'anulada'`

### Controller PDF

- [ ] T048 [US2] Implementar `LiquidacionPdfController::download(Liquidacion $liquidacion)` en [app/Http/Controllers/LiquidacionPdfController.php](../../app/Http/Controllers/LiquidacionPdfController.php) que usa `Pdf::loadView('liquidaciones.pdf', ['liq' => $liquidacion->load(...)])` y retorna `->download("liquidacion_{$plate}_{$fechaInicio}.pdf")`
- [ ] T049 [US2] Agregar botón "Descargar PDF" en [resources/views/liquidaciones/show.blade.php](../../resources/views/liquidaciones/show.blade.php) apuntando a `route('liquidaciones.pdf', $liq)` (target=_blank)

### Tests

- [ ] T050 [P] [US2] (test) Crear [tests/Feature/LiquidacionPdfTest.php](../../tests/Feature/LiquidacionPdfTest.php) con tests: `admin_can_download_pdf`, `pdf_contains_expected_totals` (parsear bytes y verificar palabras clave), `anulada_includes_watermark`, `non_admin_blocked`

**Checkpoint US2**: ✅ Cualquier liquidación es descargable como PDF formateado.

---

## Phase 5: User Story 3 — Listar, filtrar y editar con descarga PDF por fila (Priority: P2)

**Goal**: Admin entra a `/liquidaciones` y ve todas las liquidaciones con filtros (fechas, placa, conductor, ruta, transportadora, estado), totales individuales por fila, botón PDF por fila, y acciones contextuales.

**Independent Test**: Capturar 3 liquidaciones con distintas placas y fechas; filtrar por placa de la liquidación de ejemplo y verificar que solo aparezca esa; descargar el PDF desde el listado sin abrir el detalle.

### Controller listado

- [ ] T051 [US3] Implementar `LiquidacionController::index(Request $request)` en [app/Http/Controllers/LiquidacionController.php](../../app/Http/Controllers/LiquidacionController.php) que:
  - Lee query params per [contracts/http-routes.md#get-liquidaciones](contracts/http-routes.md)
  - Construye query con scopes y `when($filter, …)`
  - Paginate(25)
  - Pasa a la view + lista de drivers/routes para los selects de filtro

### Vista listado

- [ ] T052 [US3] Crear [resources/views/liquidaciones/index.blade.php](../../resources/views/liquidaciones/index.blade.php) con: barra de filtros (form GET con todos los query params), tabla con columnas Estado / Placa / Ruta / Conductor / Fecha inicio / Fecha fin / Transportadora / Total gastos / Total peajes / Saldo / Ganancia / A favor de / Acciones, paginación de Laravel, badge de estado por color, link "Descargar PDF" por fila
- [ ] T053 [US3] En el listado, mostrar liquidaciones en estado `anulada` con clase CSS `liquidacion-anulada` (texto tachado o background diferente) y `borrador` con etiqueta "BORRADOR"

### Eliminación (soft delete)

- [ ] T054 [US3] Implementar `LiquidacionController::destroy(Liquidacion $liquidacion)` que aplica policy `delete` (solo borrador), llama `$liquidacion->delete()` (soft), retorna redirect con flash

### Tests

- [ ] T055 [P] [US3] (test) Crear [tests/Feature/LiquidacionListadoTest.php](../../tests/Feature/LiquidacionListadoTest.php) con tests: `lists_paginated`, `filter_by_placa`, `filter_by_fecha_range`, `filter_by_estado_excludes_others`, `soft_delete_only_borrador`, `pdf_link_per_row_works`

**Checkpoint US3**: ✅ Listado con filtros funcional, PDF descargable por fila, soft delete operativo.

---

## Phase 6: User Story 4 — Consolidado mensual del conjunto filtrado (Priority: P2)

**Goal**: En el mismo listado, mostrar panel "Consolidado del periodo" con totales agregados del conjunto filtrado, y opción de "Agrupar por mes" cuando el rango abarca múltiples meses.

**Independent Test**: Capturar 5 liquidaciones distribuidas en 2 meses para la misma placa; filtrar por esa placa y verificar (a) que aparezcan las 5, (b) que el panel muestre los totales agregados, (c) que al activar "Agrupar por mes" se muestren 2 sub-paneles. Una liquidación anulada NO se cuenta en el consolidado.

### Servicio de agregación

- [ ] T056 [US4] Extender [app/Services/LiquidacionCalculator.php](../../app/Services/LiquidacionCalculator.php) con método `aggregate(Builder $query): array` que devuelve `['count' => …, 'sum_gastos_operativos' => …, 'sum_peajes' => …, 'sum_gastos_totales' => …, 'sum_anticipos' => …, 'sum_flete' => …, 'sum_saldo' => …, 'sum_ganancia' => …, 'avg_ganancia' => …, 'margen_pct' => …]` usando una sola query con `selectRaw('SUM(...)')`; excluye estado='anulada' y deleted_at IS NOT NULL
- [ ] T057 [US4] Agregar al mismo servicio método `aggregateByMonth(Builder $query): Collection` que aplica `groupBy(DB::raw("DATE_FORMAT(fecha_inicio, '%Y-%m')"))` y devuelve una colección de los mismos campos por mes calendario

### Integración con listado

- [ ] T058 [US4] En `LiquidacionController::index()` después de obtener la paginación, clonar el query base (sin paginate) y pasarlo a `LiquidacionCalculator::aggregate()` → variable `$consolidado`; si `$request->boolean('agrupar_por_mes')` también obtener `$consolidadoMensual`; pasar ambas a la view

### Vista del consolidado

- [ ] T059 [US4] Crear [resources/views/liquidaciones/partials/_consolidado-panel.blade.php](../../resources/views/liquidaciones/partials/_consolidado-panel.blade.php) con grid responsive mostrando los 10 totales del aggregate, incluida etiqueta de moneda COP y formateo con `number_format`
- [ ] T060 [US4] En [resources/views/liquidaciones/index.blade.php](../../resources/views/liquidaciones/index.blade.php) incluir el panel debajo de los filtros y, si hay `$consolidadoMensual`, render adicional iterando para mostrar un sub-panel por mes con título "Mes YYYY-MM"
- [ ] T061 [US4] Agregar al form de filtros un checkbox "Agrupar por mes" que envía `agrupar_por_mes=1`

### Tests

- [ ] T062 [P] [US4] (test) Crear [tests/Feature/LiquidacionConsolidadoTest.php](../../tests/Feature/LiquidacionConsolidadoTest.php) con tests: `aggregate_returns_correct_sums`, `aggregate_excludes_anuladas`, `aggregate_excludes_soft_deleted`, `group_by_month_splits_correctly`, `margen_percent_calculation`

**Checkpoint US4**: ✅ Consolidado del periodo y mensual operativo. Decisiones gerenciales soportadas.

---

## Phase 7: User Story 5 — Gestionar rutas y peajes (Priority: P2)

**Goal**: Admin entra a "Liquidación > Rutas", crea/edita rutas con ciudad origen + destino + lista ordenada de peajes (orden, valor sugerido, sentido). Las rutas activas aparecen en el select del formulario de liquidación, y al elegirlas autocargan los peajes en la tabla.

**Independent Test**: Crear ruta `BUENAVENTURA → BOGOTÁ` con los 18 peajes del ejemplo; verificar que al crear una nueva liquidación y elegir esa ruta, los 18 peajes aparezcan pre-cargados en la tabla con su valor sugerido editable.

### Form Requests

- [ ] T063 [P] [US5] Crear [app/Http/Requests/StoreRouteRequest.php](../../app/Http/Requests/StoreRouteRequest.php) con rules: origen required string max:100, destino required string max:100, descripcion nullable, active boolean, tolls array, tolls.*.name required, tolls.*.suggested_value integer min:0, tolls.*.direction in:ida,regreso, tolls.*.sort_order integer min:1
- [ ] T064 [P] [US5] Crear [app/Http/Requests/UpdateRouteRequest.php](../../app/Http/Requests/UpdateRouteRequest.php) idéntico a Store + authorize delegado a policy update

### Controller rutas

- [ ] T065 [US5] Implementar métodos `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `toggleActive`, `peajes` (JSON) en [app/Http/Controllers/LiquidacionRouteController.php](../../app/Http/Controllers/LiquidacionRouteController.php) per [contracts/http-routes.md#5-rutas--crud](contracts/http-routes.md). `destroy` debe bloquear con 422 si la ruta tiene liquidaciones referenciándola (catch QueryException de FK restrict).

### Vistas

- [ ] T066 [P] [US5] Crear [resources/views/liquidaciones/routes/index.blade.php](../../resources/views/liquidaciones/routes/index.blade.php) con tabla de rutas (origen, destino, # peajes, estado activo) + botón "Nueva ruta" + acciones Editar/Activar-Inactivar/Eliminar
- [ ] T067 [P] [US5] Crear [resources/views/liquidaciones/routes/create.blade.php](../../resources/views/liquidaciones/routes/create.blade.php) y [resources/views/liquidaciones/routes/edit.blade.php](../../resources/views/liquidaciones/routes/edit.blade.php) con form para datos de la ruta + tabla dinámica (Alpine) para peajes (agregar/eliminar/reordenar) con columnas Nombre / Valor sugerido / Orden / Sentido
- [ ] T068 [P] [US5] Crear [resources/views/liquidaciones/routes/show.blade.php](../../resources/views/liquidaciones/routes/show.blade.php) read-only con lista de peajes

### Integración con formulario de liquidación

- [ ] T069 [US5] En [resources/js/liquidacion-form.js](../../resources/js/liquidacion-form.js) confirmar que `loadTollsForRoute()` (T040) llama a `/liquidaciones/rutas/{id}/peajes` y popula la tabla con `name`, `suggested_value`, `sort_order`, `direction`; cada fila inicia con `is_used=true`, `is_adhoc=false`, `route_toll_id` capturado
- [ ] T070 [US5] Agregar enlace "Rutas" en la página `liquidaciones.index` (sub-navegación dentro del módulo, no en el sidebar global) apuntando a `route('liquidaciones.routes.index')`

### Tests

- [ ] T071 [P] [US5] (test) Crear [tests/Feature/LiquidacionRouteCrudTest.php](../../tests/Feature/LiquidacionRouteCrudTest.php) con tests: `admin_can_create_route_with_tolls`, `peajes_endpoint_returns_route_tolls`, `cannot_delete_route_with_liquidaciones`, `can_toggle_active`, `inactive_routes_not_in_create_form`

**Checkpoint US5**: ✅ Rutas completas. Formulario de liquidación auto-carga peajes al elegir ruta. UX completa.

---

## Phase 8: Estados — Transiciones Borrador / Cerrada / Anulada (Cross-story enabler)

**Goal**: Implementar las transiciones de ciclo de vida (Cerrar / Reabrir / Anular) con log de auditoría. Aplica a US1, US3 y US4.

**Independent Test**: Sobre una liquidación en `borrador`, presionar Cerrar → estado pasa a `cerrada`. Reabrir con motivo → vuelve a `borrador` y queda registro en log. Anular con motivo → estado `anulada` (terminal) y la liquidación deja de sumar en consolidado pero sigue visible en listado con marca.

### Servicio de transiciones

- [ ] T072 Crear [app/Services/LiquidacionStateMachine.php](../../app/Services/LiquidacionStateMachine.php) con métodos `close(Liquidacion $liq, User $user)`, `reopen(Liquidacion $liq, User $user, string $motivo)`, `cancel(Liquidacion $liq, User $user, string $motivo)`. Cada uno: valida transición permitida (lanza `InvalidStateTransitionException`), valida motivo no vacío cuando aplica, dentro de transacción cambia `estado`, escribe fila en `liquidacion_state_logs`, actualiza `motivo_anulacion` si aplica, `updated_by`

### Form Requests

- [ ] T073 [P] Crear [app/Http/Requests/CloseLiquidacionRequest.php](../../app/Http/Requests/CloseLiquidacionRequest.php) (no body necesario, solo `authorize()` a policy `close`)
- [ ] T074 [P] Crear [app/Http/Requests/ReopenLiquidacionRequest.php](../../app/Http/Requests/ReopenLiquidacionRequest.php) con rule `motivo required string min:10 max:500` + authorize a policy `reopen`
- [ ] T075 [P] Crear [app/Http/Requests/CancelLiquidacionRequest.php](../../app/Http/Requests/CancelLiquidacionRequest.php) con rule `motivo required string min:10 max:500` + authorize a policy `cancel`

### Controller methods

- [ ] T076 Implementar en [app/Http/Controllers/LiquidacionController.php](../../app/Http/Controllers/LiquidacionController.php): `cerrar(CloseLiquidacionRequest $request, Liquidacion $liq)`, `reabrir(ReopenLiquidacionRequest $request, Liquidacion $liq)`, `anular(CancelLiquidacionRequest $request, Liquidacion $liq)` — cada uno invoca `LiquidacionStateMachine`, atrapa excepciones de transición inválida y retorna redirect con flash

### UI

- [ ] T077 En [resources/views/liquidaciones/show.blade.php](../../resources/views/liquidaciones/show.blade.php) agregar botones Cerrar / Reabrir / Anular visibles según `$liq->estado`; Reabrir y Anular abren modal (Alpine) con textarea para motivo antes del POST
- [ ] T078 En el panel "Historial" del show, listar los `state_logs` en orden cronológico con quién/cuándo/motivo

### Tests

- [ ] T079 [P] (test) Crear [tests/Feature/LiquidacionStateTransitionsTest.php](../../tests/Feature/LiquidacionStateTransitionsTest.php) con tests: `close_only_from_borrador`, `reopen_requires_motivo`, `cancel_requires_motivo`, `anulada_is_terminal`, `state_log_records_transition`, `cannot_edit_after_close`

**Checkpoint Phase 8**: ✅ Ciclo de vida completo. Auditoría registrada. Reglas contables respetadas.

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: i18n, autorización end-to-end, datos de muestra, validación completa con quickstart.

- [ ] T080 [P] Crear archivo de traducciones [resources/lang/es/liquidaciones.php](../../resources/lang/es/liquidaciones.php) con keys para todos los labels visibles (titulo, conductor, placa, ruta, transporte, anticipo, ..., a_favor_empresa, a_favor_conductor); reemplazar strings hardcoded en las views por `__('liquidaciones.xxx')`
- [ ] T081 [P] Stub de traducciones [resources/lang/en/liquidaciones.php](../../resources/lang/en/liquidaciones.php) con misma estructura — valores quedan en español como fallback v1
- [ ] T082 [P] Stub de traducciones [resources/lang/zh/liquidaciones.php](../../resources/lang/zh/liquidaciones.php) misma estructura, fallback
- [ ] T083 [P] (test) Crear [tests/Feature/LiquidacionAuthorizationTest.php](../../tests/Feature/LiquidacionAuthorizationTest.php) con tests: `funcionario_blocked_from_all_endpoints` (probar los 21 endpoints retornan 403), `importer_blocked`, `import_viewer_blocked`, `clientes_blocked`, `proveedor_itr_blocked`, `guest_redirected_to_login`, `sidebar_hidden_for_non_admin`
- [ ] T084 (opcional) Crear seeder de demo [database/seeders/LiquidacionDemoDataSeeder.php](../../database/seeders/LiquidacionDemoDataSeeder.php) (NO incluido en `DatabaseSeeder` por defecto) que pre-llena la ruta BUENAVENTURA-BOGOTÁ con sus 18 peajes y una liquidación con los datos del ejemplo del Excel para demos rápidas
- [ ] T085 Correr el flujo completo de [quickstart.md](quickstart.md) Pasos A–E en entorno local (XAMPP corriendo); marcar cada paso como ✓ en el documento o crear issue si algún paso falla
- [ ] T086 [P] Limpieza: revisar que ningún controller tenga lógica de negocio inline (debe estar en `LiquidacionCalculator` o `LiquidacionStateMachine`); revisar que ningún `dd()`/`var_dump()`/`console.log` quedó en el código; revisar que las views no hardcoden strings que deberían estar en lang files
- [ ] T087 Documentar en [README.md](../../README.md) en la sección "Modules" una línea sobre Liquidación de Viajes con link al módulo y al spec

**Checkpoint Final**: ✅ Feature completa. Spec satisfecho. Quickstart pasa. Listo para release.

---

## Dependencies & Execution Order

### Phase Dependencies

```text
Phase 1 (Setup)
  └─→ Phase 2 (Foundational)  [BLOQUEA todas las user stories]
      ├─→ Phase 3 (US1)  🎯 MVP
      ├─→ Phase 4 (US2 PDF)         [depende de US1 para tener data]
      ├─→ Phase 5 (US3 Listado)     [depende de US1]
      ├─→ Phase 6 (US4 Consolidado) [depende de US3 para la query base del listado]
      ├─→ Phase 7 (US5 Rutas)       [independiente de US1; pero el formulario de US1 sólo aprovecha auto-load después de tener rutas]
      └─→ Phase 8 (Estados)         [depende de US1 para tener liquidaciones que transicionar]
          └─→ Phase 9 (Polish)
```

### User Story Dependencies

- **US1 (P1)**: Solo Foundational. Funciona standalone con peajes ad-hoc.
- **US2 (P2)**: Depende de US1 (necesita liquidación para imprimir).
- **US3 (P2)**: Depende de US1 (necesita liquidaciones para listar).
- **US4 (P2)**: Depende de US3 (extiende el listado con el panel consolidado).
- **US5 (P2)**: Solo Foundational. Independiente, pero potencia US1.
- **Estados (Phase 8)**: Depende de US1 mínimo.

### Within Each User Story

- Models → Services → Form Requests → Controller methods → Views → JS → Tests.
- Tests pueden escribirse antes (TDD opcional) o después (verificación).
- Commit después de cada checkpoint de fase.

### Parallel Opportunities

**Phase 1 Setup**: T002, T003 paralelas (carpetas distintas).

**Phase 2 Foundational**: Migrations T005–T011 secuenciales (FK dependencies). Modelos T013–T019 paralelos entre sí (archivos distintos). Policies T022 paralela a T021. Controller skeletons T025 paralelos.

**Phase 3 US1**: T027–T028 (Form Requests) paralelos. T034–T036 (partials) paralelos. T044–T045 (tests) paralelos.

**Phase 4 US2**: T050 (test) paralelo a T049 (view tweak).

**Phase 5 US3**: T055 paralelo a la implementación.

**Phase 6 US4**: T062 paralelo a la implementación.

**Phase 7 US5**: T063–T064 paralelos. T066–T068 paralelos. T071 paralelo a la implementación.

**Phase 8 Estados**: T073–T075 paralelos. T079 paralelo a T076–T077.

**Phase 9 Polish**: T080–T082 paralelos. T083, T086 paralelos.

---

## Parallel Example: User Story 1 (Phase 3)

```bash
# Después de Foundational completa, lanzar en paralelo:

# Form Requests (archivos independientes)
Task: "T027 [P] [US1] StoreLiquidacionRequest in app/Http/Requests/StoreLiquidacionRequest.php"
Task: "T028 [P] [US1] UpdateLiquidacionRequest in app/Http/Requests/UpdateLiquidacionRequest.php"

# Partials Blade (archivos independientes)
Task: "T034 [US1] _form.blade.php"
Task: "T035 [US1] _expenses-table.blade.php"
Task: "T036 [US1] _tolls-table.blade.php"

# Tests
Task: "T044 [P] [US1] LiquidacionCrudTest"
Task: "T045 [P] [US1] LiquidacionCalculatorTest"
```

---

## Implementation Strategy

### MVP First (Solo US1 + Foundational)

1. ✅ Phase 1: Setup
2. ✅ Phase 2: Foundational
3. ✅ Phase 3: US1 — capturar/ver/editar liquidaciones con peajes ad-hoc
4. **STOP & VALIDATE**: probar paso B del [quickstart.md](quickstart.md)
5. Deploy / demo a un admin para validar UX

Total estimado: T001–T045 (45 tareas).

### Incremental Delivery (entregas semanales)

- **Sprint 1**: Phases 1 + 2 (foundational)
- **Sprint 2**: Phase 3 (US1) → MVP demo
- **Sprint 3**: Phase 4 (US2 PDF) + Phase 5 (US3 Listado)
- **Sprint 4**: Phase 6 (US4 Consolidado) + Phase 7 (US5 Rutas)
- **Sprint 5**: Phase 8 (Estados) + Phase 9 (Polish + i18n + autorización tests)

### Parallel Team Strategy

Si hay 2+ devs:

1. Todos juntos: Phases 1 + 2.
2. Tras Foundational:
   - Dev A: US1 (Phase 3)
   - Dev B: US5 Rutas (Phase 7) — no depende de US1
3. Tras US1:
   - Dev A: Phase 8 Estados
   - Dev B: US2 PDF (Phase 4)
   - Dev C (si existe): US3 Listado (Phase 5) → encadena con US4
4. Polish (Phase 9) al final, después de validación con stakeholder.

---

## Notes

- **`[P]`** = archivos distintos, sin dependencias de tareas previas incompletas en la misma fase.
- **`[US#]`** = trazabilidad directa a la user story del spec.
- **`(test)`** = tarea opcional de testing automático. Se puede saltar para acelerar MVP, pero se requiere para cumplir SC-008 (consistencia 100%).
- Cada tarea apunta a un archivo concreto del repo (Laravel paths).
- Las migrations DEBEN crearse en orden topológico de FKs (expense_categories y routes primero, después route_tolls y liquidaciones, etc.).
- **Commit** sugerido al final de cada Phase, no por tarea (pre-commit hook de Spec Kit puede ayudar con `/speckit-git-commit`).
- Si una tarea queda bloqueada (ej. FK constraint que la BD rechaza), marcar en línea y consultar con el dueño antes de avanzar.
