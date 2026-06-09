---
description: "Task list for Informes y Analítica de Liquidaciones de Viajes"
---

# Tasks: Informes y Analítica de Liquidaciones de Viajes

**Input**: Design documents from [/specs/007-reportes-liquidaciones/](.)
**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/http-routes.md](contracts/http-routes.md)

**Tests**: INCLUIDOS. El módulo de Liquidaciones se valida con Feature tests (PHPUnit 9.5) sobre MySQL `easy_inventory_test` (ver memoria del proyecto). Los tests de cada historia se escriben antes de la implementación correspondiente.

**Organization**: Tareas agrupadas por historia de usuario para implementación y prueba independientes. Monolito Laravel — rutas en `routes/web.php`, lógica en `app/`, vistas en `resources/views/`, tests en `tests/Feature/`. **Cero migraciones.**

## Format: `[ID] [P?] [Story] Description`

- **[P]**: puede correr en paralelo (archivo distinto, sin dependencias pendientes)
- **[Story]**: historia de usuario (US1–US4); Setup/Foundational/Polish sin etiqueta

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Preparar la estructura mínima de la feature (el proyecto Laravel ya existe).

- [ ] T001 [P] Crear el directorio de vistas del informe `resources/views/liquidaciones/reportes/` con `index.blade.php` y `pdf.blade.php` como archivos vacíos (placeholders que se llenan en sus fases).
- [ ] T002 [P] Añadir un helper de datos de prueba en `tests/Feature/` (trait o método) que cree liquidaciones **activas** con `expenses` por categoría (incl. VIÁTICOS), `tolls` usados, y filas en `monthly_expenses` para los conductores/meses usados — reutilizando el patrón de los tests existentes de liquidaciones.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Acceso, ruta base, esqueleto del controlador y resolución de periodo que TODAS las historias necesitan.

**⚠️ CRITICAL**: Ninguna historia puede empezar hasta completar esta fase.

- [ ] T003 Definir el Gate `liquidaciones.reportes.access` (solo `admin`) en `app/Providers/AppServiceProvider.php`, junto a los gates `liquidaciones.access` y `liquidaciones.gastos.access` existentes.
- [ ] T004 Registrar la ruta `GET /liquidaciones/reportes` (name `liquidaciones.reportes.index`) en `routes/web.php`, dentro del grupo `liquidaciones` y **antes** del wildcard `{liquidacion}` del resource, con `middleware('can:liquidaciones.reportes.access')`.
- [ ] T005 Crear `app/Http/Controllers/LiquidacionReportController.php` con: `index()` (esqueleto que retorna la vista), método privado `resolvePeriod($tipo,$anio,$mes,$semestre): array` → `[$desde,$hasta]` (mes/semestre fijo S1 ene–jun · S2 jul–dic/año), y método privado `baseQuery($desde,$hasta,$driverId=null)` que arma el `Builder` de `Liquidacion` por rango de `fecha_inicio` (+ `where('driver_id')` opcional). Ver [data-model.md](data-model.md) y [research.md](research.md#2-definición-de-semestre-y-mapeo-de-periodo--rango-de-fechas).
- [ ] T006 Añadir el enlace de navegación **"Informes"** (visible solo si `auth()->user()->rol === 'admin'`) apuntando a `liquidaciones.reportes.index` en `resources/views/layouts/app.blade.php`, junto al enlace "Liquidación de Viajes".

**Checkpoint**: La ruta del informe existe, está protegida a admin y el controlador resuelve periodos. Las historias pueden comenzar.

---

## Phase 3: User Story 1 - Informe consolidado de un periodo con utilidad neta (Priority: P1) 🎯 MVP

**Goal**: Que el admin elija un periodo (mes/semestre/año) y vea ingresos por fletes, gasto desglosado por concepto (peajes, viáticos y cada categoría operativa, + los 7 gastos fijos) y la **utilidad neta** con su signo (ganancia/pérdida).

**Independent Test**: Seleccionar un mes con datos y verificar que cada total coincide con la suma manual de las liquidaciones activas, que la utilidad neta = `sum_ganancia − gastos fijos`, y que el signo es correcto; un periodo sin datos muestra estado vacío en 0.

### Tests for User Story 1 ⚠️ (escribir primero, deben fallar)

- [ ] T007 [P] [US1] `tests/Feature/ReporteLiquidacionConsolidadoTest.php`: totales por mes/semestre/año; `utilidad_neta = sum_ganancia − sum_gastos_mensuales`; exclusión de liquidaciones `anulada` y soft-deleted; estado vacío (0) sin error; paridad de `utilidad_neta` con la `utilidad_final` del consolidado de `liquidaciones.index` para el mismo periodo.
- [ ] T008 [P] [US1] `tests/Feature/ReporteLiquidacionAccesoTest.php`: `GET /liquidaciones/reportes` → 200 para `admin`, **403** para `placas`/`clientes`/`cliente_funcionario`/`funcionario`, redirección a login para invitado.

### Implementation for User Story 1

- [ ] T009 [US1] Añadir `expensesByCategory(Builder $query): Collection` a `app/Services/LiquidacionCalculator.php` (join `liquidacion_expenses → expense_categories`, `->activas()`, agrupado por categoría → `['code','name','total']`); invariante: suma == `sum_gastos_operativos`.
- [ ] T010 [US1] Añadir `monthlyExpensesBreakdownFor($tuples): array` a `app/Services/LiquidacionCalculator.php` (los 7 conceptos `sueldo_conductor`…`otro_valor` por separado + `total`), reutilizando el patrón de `monthlyExpensesTotalFor`.
- [ ] T011 [US1] Implementar la composición de `index()` en `LiquidacionReportController`: `ResumenPeriodo` (`aggregate()` + `monthlyExpensesTotalFor(tripPeriods())` + `utilidad_neta` + `resultado`), `categorias` (`expensesByCategory`), `gastosFijos` (`monthlyExpensesBreakdownFor(tripPeriods())`), `drivers` para el selector y eco de `filtros`. Reutiliza métodos existentes del servicio.
- [ ] T012 [US1] Validar los parámetros de periodo en el controlador (o FormRequest): `tipo in:mes,semestre,anio`; `mes` requerido/`1..12` si `tipo=mes`; `semestre` requerido/`in:1,2` si `tipo=semestre`; `anio` entero en rango; `driver_id` `exists:drivers,id` opcional. Defaults: mes actual.
- [ ] T013 [US1] Construir `resources/views/liquidaciones/reportes/index.blade.php`: formulario de filtros (tipo/anio/mes/semestre/driver) con auto-submit; tarjetas de resumen (fletes, peajes, viáticos + categorías, bloque de 7 gastos fijos, **utilidad neta** con color/etiqueta ganancia vs pérdida); estado vacío claro. Bootstrap 5, dentro de `layouts.app`.

**Checkpoint**: US1 funcional y testeable — el admin ya obtiene el informe consolidado con utilidad neta (MVP).

---

## Phase 4: User Story 2 - Gráficas de evolución mensual y desglose de gastos (Priority: P2)

**Goal**: Gráficas de evolución mes a mes (ingresos/gastos/utilidad) con resalte del mejor y peor mes, y gráfica de desglose de gastos por categoría.

**Independent Test**: Con un año cargado, la gráfica de evolución muestra un punto/barra por mes con valores correctos, resalta el mes de mayor ganancia y el de mayor pérdida, y el desglose por categoría suma el total de gastos operativos del resumen.

### Tests for User Story 2 ⚠️

- [ ] T014 [P] [US2] `tests/Feature/ReporteLiquidacionDesglosesTest.php` (parte evolución/categorías): la serie por mes (`aggregateByMonth` + fijos por mes) es correcta; `mes_mayor_ganancia`/`mes_mayor_perdida` se derivan bien; `expensesByCategory` suma == `sum_gastos_operativos`.

### Implementation for User Story 2

- [ ] T015 [US2] Extender `index()` en `LiquidacionReportController` para componer `EvolucionMensual` (`aggregateByMonth()` + fijos por mes vía `tripPeriodsByMonth()`/`monthlyExpensesTotalFor()` + `utilidad_neta` por mes) y calcular `mes_mayor_ganancia` y `mes_mayor_perdida`; pasar series como JSON a la vista.
- [ ] T016 [US2] Añadir las gráficas Chart.js a `resources/views/liquidaciones/reportes/index.blade.php`: evolución (línea/barras: fletes, gastos, utilidad por mes) con resalte mejor/peor mes; desglose por categoría (torta o barras). Datos inyectados desde el controlador.
- [ ] T017 [US2] Manejar el estado vacío de las gráficas en la vista (mensaje en lugar de canvas en blanco cuando el periodo no tiene datos).

**Checkpoint**: US1 + US2 funcionan de forma independiente; el admin ve números y gráficas.

---

## Phase 5: User Story 3 - Exportar el informe a PDF (Priority: P2)

**Goal**: Descargar un PDF del periodo (mensual/semestral/anual) con los mismos totales de pantalla y las gráficas embebidas.

**Independent Test**: Generar el PDF de un periodo y verificar que descarga con `Content-Type: application/pdf`, que los totales coinciden con la pantalla, y que el documento se genera incluso sin imágenes de gráficas (fallback a tablas).

### Tests for User Story 3 ⚠️

- [ ] T018 [P] [US3] `tests/Feature/ReporteLiquidacionPdfTest.php`: `POST /liquidaciones/reportes/pdf` → `application/pdf` para `admin`, **403** para no-admin; el PDF se genera sin `charts[]` (fallback); los totales del payload reconstruyen el mismo periodo que el dashboard.

### Implementation for User Story 3

- [ ] T019 [US3] Registrar `POST /liquidaciones/reportes/pdf` (name `liquidaciones.reportes.pdf`) en `routes/web.php`, dentro del grupo `liquidaciones`, **antes** del wildcard `{liquidacion}`, con `can:liquidaciones.reportes.access`.
- [ ] T020 [US3] Implementar `pdf()` en `LiquidacionReportController`: reutilizar la misma composición de `index()` (mismo `resolvePeriod`/`baseQuery`), aceptar `charts[evolucion]`/`charts[categorias]` (data-URLs PNG), renderizar con `Barryvdh\DomPDF\Facade\Pdf::loadView()` y `->download("informe-liquidaciones-<periodo>[-<placa>].pdf")`.
- [ ] T021 [US3] Crear `resources/views/liquidaciones/reportes/pdf.blade.php`: encabezado con periodo (y conductor/placa si aplica), tablas de ingresos, desglose por categoría, los 7 gastos fijos y **utilidad neta con su signo**; embeber las imágenes de gráficas si llegan (`<img src="{{ $charts['evolucion'] }}">`), con fallback a solo tablas. CSS inline estilo `liquidaciones/pdf.blade.php`.
- [ ] T022 [US3] Añadir el botón **"Descargar PDF"** + JS en `index.blade.php` que captura los canvas Chart.js (`chart.toBase64Image()`) en inputs ocultos y hace `POST` del formulario al endpoint PDF (con `_token` y los parámetros del periodo/conductor).

**Checkpoint**: US1 + US2 + US3 — informe en pantalla y descargable en PDF fiel.

---

## Phase 6: User Story 4 - Desglose por conductor / placa (Priority: P3)

**Goal**: Filtrar/desglosar el consolidado por conductor o placa; la suma de los conductores reproduce el consolidado.

**Independent Test**: Filtrar por un conductor y verificar que todos los totales y gráficas se recalculan solo con ese conductor y sus gastos fijos; sumar la utilidad neta de todos los conductores del periodo == consolidado.

### Tests for User Story 4 ⚠️

- [ ] T023 [P] [US4] Ampliar `tests/Feature/ReporteLiquidacionDesglosesTest.php` (parte conductor): filtrar por `driver_id` acota todos los totales; la suma de `utilidad_neta` por conductor == consolidado del periodo (diferencia cero, SC-005).

### Implementation for User Story 4

- [ ] T024 [US4] Añadir `aggregateByDriver(Builder $query): Collection` a `app/Services/LiquidacionCalculator.php` (un `ResumenPeriodo` por `driver_id`, incluyendo los gastos fijos propios de cada conductor) — o documentar el uso de `aggregate()` por conductor con `where('driver_id')` sobre `baseQuery`.
- [ ] T025 [US4] Extender `index()`/`pdf()` en `LiquidacionReportController`: cuando NO hay filtro de conductor, componer la lista `porConductor`; cuando `driver_id` viene, acotar `baseQuery` a ese conductor (todo el informe queda desglosado a uno solo).
- [ ] T026 [US4] Añadir la sección/tabla de desglose por conductor a `index.blade.php` y reflejar el conductor filtrado en el PDF (encabezado + nombre de archivo con placa).

**Checkpoint**: Las 4 historias funcionan de forma independiente.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [ ] T027 [P] Ejecutar la validación de [quickstart.md](quickstart.md) de punta a punta (admin + roles denegados + PDF).
- [ ] T028 Verificar la paridad de `utilidad_neta` del informe con la `utilidad_final` del consolidado de `liquidaciones.index` para varios periodos (SC-002/SC-004).
- [ ] T029 Revisar que TODA agregación use `->activas()` y que el orden de rutas (`reportes` y `reportes/pdf` antes de `{liquidacion}`) sea correcto en `routes/web.php`.
- [ ] T030 Ejecutar `php artisan test --filter=ReporteLiquidacion` y dejar la suite en verde.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de Setup; **bloquea** todas las historias (gate + ruta + controlador base).
- **User Stories (Phase 3–6)**: dependen de Foundational. US1 es el MVP. US2/US3/US4 dependen de la composición base de US1 (comparten `index()`), por lo que en la práctica se hacen tras US1 aunque sean testeables por separado.
- **Polish (Phase 7)**: tras las historias deseadas.

### User Story Dependencies

- **US1 (P1)**: solo Foundational. Entrega el informe consolidado (MVP).
- **US2 (P2)**: reutiliza la composición de US1 (extiende `index()` con la serie mensual). Testeable por separado.
- **US3 (P2)**: reutiliza la composición de US1 para el PDF. Independiente de US2 (si no hay imágenes, el PDF usa tablas).
- **US4 (P3)**: añade dimensión por conductor sobre la base de US1.

### Within Each User Story

- Tests primero (deben fallar) → métodos de servicio → controlador → vista.
- Los métodos del servicio (`LiquidacionCalculator.php`) y la vista (`index.blade.php`) se editan en varias fases: dentro de un mismo archivo las tareas son **secuenciales** (no `[P]`).

### Parallel Opportunities

- Setup: T001 y T002 en paralelo.
- US1: T007 y T008 (tests, archivos distintos) en paralelo; luego T009→T013 (T009 y T010 tocan el mismo servicio → secuenciales).
- Los tests de cada historia (`[P]`) corren en paralelo dentro de su fase.

---

## Parallel Example: User Story 1

```bash
# Tests de US1 juntos (archivos distintos):
Task: "ReporteLiquidacionConsolidadoTest en tests/Feature/ReporteLiquidacionConsolidadoTest.php"
Task: "ReporteLiquidacionAccesoTest en tests/Feature/ReporteLiquidacionAccesoTest.php"
```

---

## Implementation Strategy

### MVP First (solo US1)

1. Fase 1 Setup → Fase 2 Foundational (gate + ruta + controlador base).
2. Fase 3 US1 (consolidado + utilidad neta).
3. **PARAR y VALIDAR**: probar US1 de forma independiente (quickstart pasos 1–4, 10–14).
4. Demo: el admin ya obtiene el informe con utilidad neta por mes/semestre/año.

### Incremental Delivery

1. Setup + Foundational → base lista.
2. US1 → informe consolidado (MVP) → demo.
3. US2 → gráficas → demo.
4. US3 → PDF → demo.
5. US4 → desglose por conductor → demo.

---

## Notes

- **Cero migraciones**: todo es lectura agregada sobre el esquema existente.
- Reutilizar al máximo `LiquidacionCalculator` (`aggregate`, `aggregateByMonth`, `tripPeriods`, `tripPeriodsByMonth`, `monthlyExpensesTotalFor`); solo se añaden `expensesByCategory`, `monthlyExpensesBreakdownFor` y (opcional) `aggregateByDriver`.
- La `utilidad_neta` del informe DEBE igualar la `utilidad_final` del índice — no redefinir la fórmula.
- Mantener el acceso admin-only en cada endpoint (gate `liquidaciones.reportes.access`).
- Feature tests sobre MySQL `easy_inventory_test`; commit tras cada tarea o grupo lógico.
