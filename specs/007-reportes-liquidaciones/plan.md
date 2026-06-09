# Implementation Plan: Informes y Analítica de Liquidaciones de Viajes

**Branch**: `007-reportes-liquidaciones` | **Date**: 2026-06-09 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from [/specs/007-reportes-liquidaciones/spec.md](spec.md)

## Summary

Agregar al módulo de Liquidaciones de Viajes (monolito Laravel existente) una sección de **Informes/Analítica exclusiva para `admin`** que, sobre los datos ya existentes, entrega:

1. **Resumen consolidado por periodo** (mes / semestre / año): ingresos por fletes, gasto total desglosado por concepto (peajes, viáticos y las 16 categorías operativas + los 7 conceptos de gasto fijo mensual) y **utilidad neta** = `fletes − (gastos operativos + peajes + gastos fijos mensuales)`, indicando ganancia o pérdida.
2. **Gráficas** (Chart.js, ya cargado): evolución mes a mes de ingresos/gastos/utilidad con resalte del mejor y peor mes, y desglose de gastos por categoría (torta/barras).
3. **Exportación a PDF** (DomPDF, ya instalado) del periodo, con los mismos totales y con las gráficas embebidas como imagen.
4. **Desglose por conductor/placa**: el consolidado se puede filtrar por un conductor; la suma de los conductores reproduce el consolidado.

El trabajo es **capa de presentación + agregación**, reutilizando al máximo `LiquidacionCalculator`:

- `aggregate()`, `aggregateByMonth()`, `tripPeriods()`, `tripPeriodsByMonth()` y `monthlyExpensesTotalFor()` **ya existen** y ya calculan la utilidad final restando gastos fijos. Se reutilizan tal cual.
- Se **añaden 2 métodos de agregación** al servicio para los desgloses que hoy no existen: gasto **por categoría operativa** y **detalle de los 7 conceptos** de gasto fijo mensual.
- Un **controlador nuevo** (`LiquidacionReportController`) traduce la selección de periodo (mes/semestre/año + conductor opcional) a un rango `fecha_desde/fecha_hasta` y compone los datos; una **vista dashboard** Blade dibuja números + Chart.js; un **controlador PDF** (o método) renderiza la plantilla con las gráficas embebidas como PNG.

**Cero migraciones nuevas.** No se altera el cálculo de utilidad existente; solo se agrega lectura agregada y presentación. Acceso restringido a `admin` con un gate dedicado.

## Technical Context

**Language/Version**: PHP 8.2.12 (`composer.json` `^7.4 || ^8.0`); JavaScript ES2022 (vanilla + Chart.js 4) en la vista del dashboard.
**Primary Dependencies**: Laravel 8.75, Eloquent, Blade. `barryvdh/laravel-dompdf` (ya instalado) para el PDF. **Chart.js 4.5.1** (ya cargado en `layouts/app.blade.php`) para las gráficas en pantalla y para producir las imágenes PNG que se embeben en el PDF. Sin dependencias nuevas.
**Storage**: MySQL/MariaDB (XAMPP, InnoDB, `utf8mb4_unicode_ci`). **Cero migraciones nuevas**: lectura sobre `liquidaciones`, `liquidacion_expenses`, `expense_categories`, `liquidacion_tolls` (vía columnas cacheadas `sumatoria_*`) y `monthly_expenses`.
**Testing**: PHPUnit 9.5 (Feature) sobre MySQL `easy_inventory_test` (ver memoria del proyecto). Tests por User Story: consolidado/utilidad neta por periodo, exclusión de anuladas/borradas, desglose por categoría, agregación por mes (mejor/peor mes), desglose por conductor (suma = consolidado), y autorización (solo admin).
**Target Platform**: Web monolítica Laravel (Apache/XAMPP en dev).
**Project Type**: Web monolítica — extensión del módulo existente de Liquidaciones (controladores + servicio + vistas + rutas + gate). Ningún proyecto nuevo.
**Performance Goals**:
- Resumen consolidado de un periodo: 1 consulta agregada sobre `liquidaciones` (índice `idx_liq_listado (fecha_inicio, estado, deleted_at)`), igual que el consolidado actual del índice.
- Desglose por categoría: 1 consulta con join `liquidacion_expenses → expense_categories` acotada por rango de fechas; desglose mensual y por conductor reutilizan el mismo patrón agrupando.
- Gastos fijos: 1 consulta sobre `monthly_expenses` por las tuplas (driver, año, mes) del periodo (patrón ya existente).
- Objetivo SC-001: informe en pantalla en < 1 minuto de interacción del usuario (en la práctica, render sub-segundo para volúmenes esperados).
**Constraints**:
- **Cero modificación de esquema** (sin migraciones); solo lectura agregada.
- La **utilidad neta MUST** ser idéntica a la `utilidad_final` que ya calcula el índice (`sum_ganancia − gastos_mensuales`) para no divergir (SC-002, SC-004).
- Solo `admin` accede; ningún otro rol ve el módulo ni el PDF (SC-006).
- Las liquidaciones **anuladas y soft-deleted** se excluyen de todo total (FR-007) — garantizado por `scopeActivas()` ya usado en el servicio.
- El PDF MUST reflejar exactamente los totales de pantalla (SC-004).
**Scale/Scope**:
- 1 usuario admin (o pocos) genera informes; volumen de liquidaciones del orden de cientos/miles por año.
- Endpoints HTTP nuevos: **2** (dashboard de informe + descarga PDF), bajo el prefijo/gate de liquidaciones.
- Archivos nuevos: 1 controlador, +2 métodos en `LiquidacionCalculator`, 1 vista dashboard, 1 vista PDF, 1 gate, 2–3 tests Feature. Modificaciones puntuales: `routes/web.php`, `AppServiceProvider` (gate), `layouts/app.blade.php` (navegación admin).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Status**: La constitución del proyecto (`.specify/memory/constitution.md`) está en estado **template** (placeholders sin ratificar). No hay gates formales que aplicar.

**Acción**: Aplicar como guía implícita las convenciones ya presentes en el módulo de Liquidaciones:

| Práctica del codebase | Aplicación en esta feature |
|---|---|
| Acceso por **Gate** (`liquidaciones.access`, `liquidaciones.gastos.access` admin-only) en `AppServiceProvider` | Sí — se define `liquidaciones.reportes.access` (admin-only), igual que el gate de gastos, y se protege la ruta con `can:`. |
| Agregación centralizada en `LiquidacionCalculator` (estático, sobre `Builder` `->activas()`) | Sí — los nuevos desgloses (por categoría, conceptos de gasto fijo) se agregan como métodos estáticos del mismo servicio, manteniendo el filtro `->activas()`. |
| Reutilizar columnas cacheadas (`sumatoria_*`, `ganancia_viaje`) en vez de recomputar por fila | Sí — el resumen usa las sumas cacheadas; solo el desglose **por categoría** baja a `liquidacion_expenses` (no existe cache por categoría). |
| Utilidad final ya calculada en `index()` (`sum_ganancia − monthlyExpensesTotalFor(tripPeriods)`) restringida a admin | Sí — se reutiliza idéntica fórmula y método; no se redefine la utilidad. |
| PDF con DomPDF (`Pdf::loadView()->download()`), patrón de `LiquidacionPdfController` | Sí — el PDF del informe sigue el mismo patrón (vista Blade dedicada + DomPDF). |
| Navegación por rol en `layouts/app.blade.php` | Sí — el enlace "Informes" se muestra solo a `admin`, junto al de Liquidación de Viajes. |
| Chart.js ya incluido en el layout, sin uso actual | Sí — primer uso real; gráficas client-side en el dashboard y captura a PNG para el PDF. |

**Gates**: Ninguno bloqueante. Pasar a Phase 0.

## Project Structure

### Documentation (this feature)

```text
specs/007-reportes-liquidaciones/
├── plan.md                       # Este archivo
├── spec.md                       # Spec funcional
├── research.md                   # Phase 0: decisiones técnicas (PDF+gráficas, semestre, scoping)
├── data-model.md                 # Phase 1: entidades de lectura, agregaciones y fórmulas
├── contracts/
│   └── http-routes.md            # Phase 1: endpoints del informe + matriz de autorización
├── quickstart.md                 # Phase 1: cómo validar el informe y el PDF localmente
├── checklists/
│   └── requirements.md           # Checklist de calidad del spec (ya creado)
└── tasks.md                      # /speckit-tasks (NO se crea en este comando)
```

### Source Code (repository root — Laravel monolito, extensión)

Archivos **nuevos**:

```text
app/
└── Http/
    └── Controllers/
        └── LiquidacionReportController.php     # dashboard del informe (HTML) + exportación PDF

resources/
└── views/
    └── liquidaciones/
        └── reportes/
            ├── index.blade.php                 # dashboard: filtros de periodo/conductor + tarjetas + Chart.js
            └── pdf.blade.php                   # plantilla DomPDF del informe (tablas + gráficas como <img> PNG)

tests/
└── Feature/
    ├── ReporteLiquidacionConsolidadoTest.php   # US1: totales por periodo, utilidad neta, exclusión de anuladas/borradas, estado vacío
    ├── ReporteLiquidacionDesglosesTest.php     # US2/US4: por categoría, por mes (mejor/peor), por conductor (suma = consolidado)
    └── ReporteLiquidacionAccesoTest.php        # US1/SC-006: solo admin accede al dashboard y al PDF
```

Archivos **modificados** (cambios puntuales):

```text
app/
├── Providers/
│   └── AppServiceProvider.php                  # + Gate::define('liquidaciones.reportes.access', admin-only)
└── Services/
    └── LiquidacionCalculator.php               # + expensesByCategory(Builder) y monthlyExpensesBreakdownFor($tuples)
                                                 #   (opcional) aggregateByDriver(Builder) para el desglose por conductor

routes/
└── web.php                                     # + 2 rutas dentro del grupo liquidaciones, con can:liquidaciones.reportes.access

resources/
└── views/
    └── layouts/app.blade.php                   # + enlace "Informes" visible solo para admin
```

**Structure Decision**: Extensión del monolito Laravel dentro del módulo de Liquidaciones, **sin sub-proyectos ni migraciones**. La estrategia reproduce el modelo ya usado por el módulo:

1. **Autorización por Gate dedicado** (`liquidaciones.reportes.access`, admin-only), idéntico en forma al gate de gastos mensuales; la ruta se protege con `can:` dentro del grupo `liquidaciones` existente. No se introduce Policy (el dato es de lectura agregada, sin instancia de modelo que autorizar).
2. **Agregación centralizada**: toda la lógica de sumas vive en `LiquidacionCalculator` (reutilizando lo existente y añadiendo solo los dos desgloses que faltan), de modo que el controlador queda delgado y la utilidad neta no diverge del índice.
3. **PDF fiel a pantalla**: el dashboard renderiza las gráficas con Chart.js; al exportar, las gráficas se capturan a PNG (`canvas.toDataURL`) y se envían al endpoint PDF, que las embebe como `<img>` en la plantilla DomPDF (DomPDF no ejecuta JS). Fallback documentado: si no llegan imágenes, el PDF incluye igualmente todas las **tablas numéricas** (la gráfica es complemento, no la fuente del dato).

## Complexity Tracking

No hay violaciones de constitución (constitución en template). Puntos de complejidad inherentes y su justificación:

| Decisión | Por qué | Alternativa más simple rechazada porque |
|---|---|---|
| Capturar las gráficas Chart.js a PNG y enviarlas al endpoint PDF (POST con data-URLs) | DomPDF no ejecuta JavaScript: es la única forma de que el PDF muestre **las mismas** gráficas que la pantalla, que es lo que el usuario pidió. | Un GET puro no puede incrustar la gráfica renderizada por JS; reimplementar gráficas en CSS para DomPDF duplica lógica y no coincide visualmente. (Las tablas numéricas siguen como fallback si no hay imagen.) |
| Nuevos métodos `expensesByCategory` y `monthlyExpensesBreakdownFor` en el servicio | El consolidado actual solo guarda `sumatoria_gastos_operativos` (total), no por categoría; el informe exige el desglose por concepto (peajes/viáticos/cada categoría) y por los 7 conceptos fijos. | Recomputar en el controlador rompe la centralización en `LiquidacionCalculator` y arriesga divergencia con la utilidad final. |
| Gate nuevo `liquidaciones.reportes.access` en vez de reutilizar `liquidaciones.gastos.access` | Hace explícita la superficie de acceso del módulo y permite ajustar a futuro sin tocar el de gastos; ambos son admin-only hoy. | Reutilizar el gate de gastos acopla dos features distintas bajo un mismo nombre semántico. |
