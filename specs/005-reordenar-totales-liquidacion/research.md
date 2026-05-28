# Phase 0 — Research: Reordenar panel de totales de la liquidación

Resuelve las decisiones técnicas antes del diseño. No quedan NEEDS CLARIFICATION (las 3 ambigüedades funcionales se resolvieron en spec.md con el usuario el 2026-05-28).

## D1 — Nuevo campo `sobreanticipo`: columna nueva vs. reutilizar

- **Decisión**: agregar columna `sobreanticipo DECIMAL(12,0) NOT NULL DEFAULT 0` a `liquidaciones`, separada de `anticipo_conductor`.
- **Rationale**: en spec 004 el viejo `sobreanticipo` se renombró a `anticipo_conductor` (migración `2026_05_27_000100`). El usuario ahora pide ambos conceptos sumados: `Anticipos conductor = anticipo_conductor + sobreanticipo`. Son dos entradas distintas → columna nueva.
- **Alternativas**: reutilizar `anticipo_conductor` (rechazado: colapsa dos conceptos); columna nullable (rechazado: el resto de montos son `NOT NULL DEFAULT 0`, se mantiene la convención y FR-016 se cumple con default 0).

## D2 — ¿Persistir las nuevas celdas derivadas o calcularlas al vuelo?

- **Decisión**: NO crear columnas nuevas para "Sumatoria de gastos" ni "Anticipos conductor" (se derivan en servicio/vista/JS). **Repurposar** columnas cacheadas existentes para los valores que el consolidado ya agrega:
  - `sumatoria_gastos_totales` ← `gastos_op + descuentos + peajes` (antes sin descuentos).
  - `ganancia_viaje` ← `valor_flete − sumatoria_gastos_totales` (antes `valor_flete − (gastos_op + peajes_empresa)`).
  - `saldo_viaje` ← `(gastos_op + descuentos) − (anticipo_conductor + sobreanticipo)` = celda "Ant - gastos".
  - `saldo_pendiente` ← `valor_flete − anticipo_empresa` = celda "Saldo adeudado empresa".
  - `a_favor_de` ← signo de `saldo_viaje` (ahora "ant - gastos"); el helper `aFavorDe()` mapea >0→conductor (gastos > anticipos: la empresa le debe), <0→empresa, =0→ninguno (confirmado por el usuario con un ejemplo).
  - `total_anticipos` ← `anticipo_empresa + anticipo_conductor + sobreanticipo` (suma `sobreanticipo` para no subreportar en el consolidado).
- **Rationale**: minimiza el churn de esquema (1 sola columna nueva), mantiene el patrón de "cached totals" y deja el consolidado coherente con la nueva definición de ganancia. Las celdas viejas (Saldo pendiente / Saldo viaje) desaparecen del panel, así que repurposar sus columnas no genera ambigüedad visible.
- **Alternativas**: (a) columnas nuevas `saldo_adeudado_empresa`, `ant_gastos` y deprecar las viejas — rechazado por churn y columnas muertas; (b) no persistir nada y calcular todo en vista — rechazado porque el consolidado agrega columnas vía SQL (`SUM(...)`).
- **Riesgo aceptado**: el panel consolidado (índice) cambiará sus totales de gastos/ganancia/saldo al reflejar la nueva fórmula. Es la coherencia buscada; la UI del consolidado no se rediseña en esta feature.

## D3 — Paridad de fórmulas servidor/cliente

- **Decisión**: actualizar las fórmulas en los **tres** lugares y reconstruir el bundle:
  1. `app/Services/LiquidacionCalculator.php` (fuente de verdad, persistencia).
  2. Fallback inline de Alpine en `resources/views/liquidaciones/partials/_form.blade.php` (sobrescribe el bundle en `alpine:init`).
  3. `resources/js/liquidacion-form.js` (origen del bundle Vite → `public/js/app.js`).
- **Rationale**: el `_form` carga un fallback inline que sobrescribe `window.liquidacionForm`; si solo se cambia el bundle, la vista usa la versión inline desactualizada (y viceversa en otras vistas). Mantener ambos evita totales divergentes en pantalla.
- **Acción de build**: `npm run build` (Vite) tras editar el `.js`. Documentado en quickstart.

## D4 — Migración sin `doctrine/dbal`

- **Decisión**: `Schema::table('liquidaciones', fn ($t) => $t->decimal('sobreanticipo', 12, 0)->default(0)->after('anticipo_conductor'))`. No se necesita rename → no se requiere `doctrine/dbal`. Backfill implícito por `DEFAULT 0`; `down()` hace `dropColumn('sobreanticipo')`.
- **Rationale**: agregar columna no necesita dbal en Laravel 8; solo el rename lo necesitaba (de ahí el `ALTER ... CHANGE` en 004). Patrón consistente con el codebase.

## D5 — PDF: alcance de la omisión del anticipo empresa

- **Decisión**: quitar únicamente la fila `ANTICIPO EMPRESA` del **encabezado** (`header-table`, `pdf.blade.php:147–151`). El recuadro de totales del PDF SÍ imprime "Anticipo empresa de transporte" y "Saldo adeudado empresa de transporte" (confirmado por el usuario).
- **Detalle**: al quitar esa fila queda libre la celda de "FECHA INICIO"; se reubica FECHA INICIO/FECHA FIN para no dejar huecos (p. ej., mover FECHA INICIO a la fila de ANTICIPO CONDUCTOR o reestructurar a 2 columnas de fechas).
- **Firma**: cambiar el texto de `.firma-box` de "FIRMA CONDUCTOR" a "FIRMA FUNCIONARIO REVISÓ".

## D6 — Ubicación del input de descuentos ("arriba en los gastos del viaje")

- **Decisión**: mover el input `descuentos` del bloque de cabecera del `_form` al inicio de la sección de gastos (`_expenses-table.blade.php`), como una fila/encabezado de "Descuentos (empresa)". Sigue enviándose como `name="descuentos"` con `x-model.number="descuentos"`; la celda "Sumatoria de gastos" del panel pasa a mostrar `sumGastosOperativos + descuentos`.
- **Rationale**: refleja el modelo mental del usuario (el descuento es un costo del viaje) y hace evidente que suma a los gastos. No cambia el contrato del payload.
- **Alternativa**: dejar el input donde está y solo cambiar el cálculo — rechazado porque el usuario pidió explícitamente moverlo "arriba en los gastos".

## D7 — Testing en MySQL

- **Decisión**: las nuevas Feature tests corren sobre MySQL `easy_inventory_test` (no sqlite). Ver memoria [[feature-tests-need-mysql]].
- **Cobertura**: (US1/US2) fórmulas del recuadro con descuentos; (US3) `sobreanticipo` y "Anticipos conductor"; "A favor de" por signo de ant-gastos; (US4) el PDF responde 200 y la firma/encabezado son correctos (assert sobre el HTML renderizado de la vista `pdf` o el contenido del PDF).
