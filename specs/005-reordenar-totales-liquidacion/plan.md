# Implementation Plan: Reordenar y renombrar el panel de totales de la liquidación

**Branch**: `005-reordenar-totales-liquidacion` | **Date**: 2026-05-28 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from [/specs/005-reordenar-totales-liquidacion/spec.md](spec.md)

## Summary

Reorganización **presentacional + de cálculo** del recuadro de totales de la liquidación de viaje (specs 002/003/004), dentro del monolito Laravel existente. No hay tablas nuevas; una sola columna nueva (`sobreanticipo`) y cambios de fórmula en `LiquidacionCalculator` + su réplica en cliente (Alpine).

Cambios clave:

1. **Recuadro de totales en 2 columnas con etiquetas nuevas** (US1, P1): izquierda = costos + relación con la empresa de transporte; derecha = relación con el conductor + rentabilidad. Aplica en `show.blade.php`, `pdf.blade.php` y la barra sticky del `_form`.
2. **Descuento empresa entra a los gastos** (US2, P1): "Sumatoria de gastos" = gastos operativos + descuentos; "Suma de gastos total" y "Ganancia final" lo arrastran. El input de descuentos se muestra al inicio de la sección de gastos del viaje.
3. **Reincorporar `sobreanticipo`** (US3, P2): nueva columna + input; "Anticipos conductor" = `anticipo_conductor + sobreanticipo`.
4. **PDF y firma** (US4, P2): quitar la fila ANTICIPO EMPRESA del **encabezado** del PDF (se conserva en el recuadro de totales y en el formulario); firma → "FIRMA FUNCIONARIO REVISÓ".

Tablas alteradas: 1 (`liquidaciones`: + `sobreanticipo`). El resto es lógica de servicio (`LiquidacionCalculator`), validación (Form Requests), vistas Blade, JS Alpine (inline + bundle).

### Mapa de fórmulas (nuevo recuadro)

Base persistida que se necesita: `sumatoria_gastos_operativos`, `descuentos`, `sumatoria_peajes`, `valor_flete`, `anticipo_empresa`, `anticipo_conductor`, `sobreanticipo` (nuevo).

| Celda (etiqueta nueva) | Fórmula | Columna cacheada que la respalda |
|---|---|---|
| Sumatoria de gastos | `gastos_operativos + descuentos` | derivada (no se persiste sola) |
| Sumatoria de peajes | `sumatoria_peajes` | `sumatoria_peajes` (sin cambio) |
| Suma de gastos total de viaje | `gastos_operativos + descuentos + sumatoria_peajes` | `sumatoria_gastos_totales` (fórmula cambia: + descuentos) |
| Valor flete pactado | `valor_flete` | `valor_flete` (sin cambio) |
| Anticipo empresa de transporte | `anticipo_empresa` | `anticipo_empresa` (sin cambio) |
| Saldo adeudado empresa de transporte | `valor_flete − anticipo_empresa` | `saldo_pendiente` (se repurposa) |
| Anticipos conductor | `anticipo_conductor + sobreanticipo` | derivada (no se persiste sola) |
| Ant - gastos | `(gastos_operativos + descuentos) − (anticipo_conductor + sobreanticipo)` | `saldo_viaje` (se repurposa) |
| A favor de | signo de `Ant - gastos`: >0 → conductor, <0 → empresa, =0 → ninguno | `a_favor_de` (entrada cambia a ant-gastos) |
| Ganancia final de viaje | `valor_flete − Suma de gastos total` | `ganancia_viaje` (fórmula cambia) |

> **Decisión de consistencia (ver research.md)**: las columnas cacheadas `sumatoria_gastos_totales`, `ganancia_viaje`, `saldo_viaje`, `saldo_pendiente`, `a_favor_de` se recalculan con las nuevas fórmulas en `recalcAndSave`. Como el panel consolidado (índice) agrega `sum_gastos_totales`, `saldo_viaje` y `ganancia_viaje`, sus totales cambiarán para reflejar la nueva definición (incluye descuentos y todos los peajes). Esto se considera coherencia deseada, no un rediseño del consolidado (que queda fuera de alcance en su UI). `total_anticipos` pasa a incluir `sobreanticipo` para no subreportar anticipos en el consolidado.

## Technical Context

**Language/Version**: PHP 8.2.12 (`composer.json` exige `^7.4 || ^8.0`); JavaScript ES2022 para Alpine.js.
**Primary Dependencies**: Laravel 8.75, Eloquent, Blade, Alpine.js 3.x, Bootstrap 5 (CDN del módulo), `barryvdh/laravel-dompdf` ^2.2 (PDF), Vite (build de `resources/js/*` → `public/js/app.js`).
**Storage**: MySQL/MariaDB (XAMPP), InnoDB, `utf8mb4_unicode_ci`. Montos COP enteros `DECIMAL(12,0)`.
**Testing**: PHPUnit 9.5 (Feature tests). **Nota**: las feature tests del módulo requieren MySQL real (`easy_inventory_test`), no sqlite `:memory:` (ver memoria [[feature-tests-need-mysql]]).
**Target Platform**: Web monolítica Laravel en Apache/XAMPP (Windows en dev).
**Project Type**: Web monolítica — extender repo existente (modelo, servicio, requests, vistas, migración, JS).
**Performance Goals**: recálculo en cliente (Alpine) instantáneo (<100ms); persistencia de cached fields en `save()` sin consultas extra.
**Constraints**:
- Ediciones de liquidación solo en estado `borrador` (policy existente).
- El rol `placas` solo ve/edita sus conductores (scoping existente). Sin cambios de permisos en esta feature.
- **Paridad de fórmulas obligatoria** entre `LiquidacionCalculator.php` (servidor), el fallback inline de `_form.blade.php` y `resources/js/liquidacion-form.js` (cliente). Cualquier cambio de fórmula toca los tres y requiere rebuild de Vite.
- `sobreanticipo` es columna nueva separada de `anticipo_conductor` (que en spec 004 fue el rename del viejo `sobreanticipo`); no confundir.
**Scale/Scope**: ~50–200 liquidaciones/mes. Sin endpoints nuevos: solo cambian los payloads de store/update (campo `sobreanticipo`) y el render de show/pdf/form.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Status**: La constitución (`.specify/memory/constitution.md`) está en estado **template** (placeholders sin ratificar). No hay principios formales que evaluar. Se aplican como gate implícito las convenciones del codebase (specs 002/003/004):

| Convención del codebase | Aplicación en esta feature |
|---|---|
| Cálculo centralizado en `LiquidacionCalculator` | Se modifican/añaden fórmulas en el servicio; nada de lógica de cálculo en controladores/vistas. |
| Paridad servidor/cliente de fórmulas | Actualizar servicio + JS inline + `liquidacion-form.js` + rebuild Vite. |
| Form Requests para validación | Agregar `sobreanticipo` a `StoreLiquidacionRequest` (heredado por Update). |
| Eloquent fillable/casts explícitos | Agregar `sobreanticipo` a `$fillable` y `$casts` (integer) en `Liquidacion`. |
| Migraciones sin `doctrine/dbal` | `Schema::table` + `addColumn` (no rename); patrón de 2026_05_27_000100. |
| Blade `@extends('layouts.app')` + Alpine para totales | Reordenar recuadro en show/pdf/form; mover input de descuentos. |
| Estados y policies | Sin cambios de estado/permiso; reuso de policy `borrador`. |

**Gates**: Ninguno bloqueante. Riesgo principal = desincronización de fórmulas servidor/cliente y efecto colateral en el consolidado (mitigado documentando la decisión de consistencia y con tests).

## Project Structure

### Documentation (this feature)

```text
specs/005-reordenar-totales-liquidacion/
├── plan.md              # Este archivo
├── spec.md              # Spec funcional (clarifications resueltas)
├── research.md          # Phase 0: decisiones técnicas
├── data-model.md        # Phase 1: alter liquidaciones (+ sobreanticipo) y semántica de cached fields
├── contracts/
│   └── http-routes.md   # Phase 1: endpoints afectados (payload store/update)
├── quickstart.md        # Phase 1: levantar, probar y validar
├── checklists/
│   └── requirements.md  # Checklist de calidad de la spec
└── tasks.md             # Phase 2 (lo crea /speckit-tasks)
```

### Source Code (repository root)

```text
app/
├── Models/
│   └── Liquidacion.php                          # MOD: fillable + casts (sobreanticipo)
├── Http/Requests/
│   └── StoreLiquidacionRequest.php              # MOD: regla + prepareForValidation para sobreanticipo
└── Services/
    └── LiquidacionCalculator.php                # MOD: nuevas fórmulas (gastos+descuentos, ant-gastos, ganancia, a_favor_de, saldo_adeudado)

database/migrations/
└── 2026_05_28_000000_add_sobreanticipo_to_liquidaciones.php   # NUEVO (+ columna, backfill 0)

resources/
├── views/liquidaciones/
│   ├── show.blade.php                           # MOD: recuadro de totales en 2 columnas + etiquetas nuevas
│   ├── pdf.blade.php                            # MOD: quitar ANTICIPO EMPRESA del encabezado; recuadro 2 columnas; firma "FIRMA FUNCIONARIO REVISÓ"
│   └── partials/
│       ├── _form.blade.php                      # MOD: input sobreanticipo; fallback Alpine con nuevas fórmulas; barra sticky con etiquetas nuevas
│       └── _expenses-table.blade.php            # MOD: mostrar el campo "Descuentos (empresa)" arriba de los gastos
└── js/
    └── liquidacion-form.js                      # MOD: réplica de las nuevas fórmulas + sobreanticipo

tests/Feature/
├── LiquidacionPanelTotalesTest.php              # NUEVO (US1/US2: fórmulas y etiquetas del recuadro)
├── LiquidacionSobreanticipoTest.php             # NUEVO (US3: sobreanticipo + anticipos conductor)
└── LiquidacionPdfAjustesTest.php                # NUEVO (US4: encabezado sin anticipo empresa + firma)
```

**Structure Decision**: Monolito Laravel existente. Se reutiliza el módulo `liquidaciones` por completo. La única migración agrega `sobreanticipo`; el resto del comportamiento se concentra en `LiquidacionCalculator` (servidor) con su réplica obligatoria en el cliente Alpine. La presentación se reordena en las 3 superficies (show / pdf / sticky del form).

## Complexity Tracking

> Sin violaciones de constitución (constitución en template). Puntos de fricción documentados:

| Decisión | Por qué se necesita | Alternativa más simple y por qué se rechaza (o no) |
|---|---|---|
| Repurposar columnas cacheadas (`saldo_pendiente`→saldo adeudado empresa; `saldo_viaje`→ant-gastos) en vez de crear columnas nuevas | Evita 2–3 columnas nuevas y mantiene el patrón de cached totals + consolidado. Las celdas viejas (saldo pendiente/saldo viaje) desaparecen del panel, así que su antiguo significado ya no se muestra. | Crear `saldo_adeudado_empresa` y `ant_gastos` nuevas y deprecar las viejas — se rechaza por churn de esquema y por dejar columnas muertas; el significado se documenta en data-model.md. Si en el futuro el consolidado necesita ambos sentidos, se revisará. |
| Cambiar fórmula de `ganancia_viaje` y `sumatoria_gastos_totales` (afecta consolidado) | Coherencia: la "Ganancia final" por viaje debe coincidir con la "ganancia" agregada del consolidado y con la utilidad final ya entregada en 004. | Mantener fórmula vieja en el consolidado y nueva solo en el panel — se rechaza por inconsistencia entre pantallas. |
| `sobreanticipo` como columna nueva | El usuario pide reincorporar el campo además de `anticipo_conductor`. | Reutilizar `anticipo_conductor` — imposible: son dos conceptos distintos que se suman. |
