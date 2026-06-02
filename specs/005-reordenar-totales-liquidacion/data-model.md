# Phase 1 — Data Model: Reordenar panel de totales

## Tabla alterada: `liquidaciones`

### Columna nueva

| Columna | Tipo | Null | Default | Posición | Notas |
|---|---|---|---|---|---|
| `sobreanticipo` | `DECIMAL(12,0)` | NOT NULL | `0` | `after anticipo_conductor` | Sobre-anticipo del conductor (monto COP entero). Se suma a `anticipo_conductor` para "Anticipos conductor". |

Migración: `2026_05_28_000000_add_sobreanticipo_to_liquidaciones.php`
- `up()`: `Schema::table` → `decimal('sobreanticipo', 12, 0)->default(0)->after('anticipo_conductor')`. Backfill por DEFAULT (0).
- `down()`: `dropColumn('sobreanticipo')`.

### Modelo `Liquidacion`
- `$fillable`: agregar `'sobreanticipo'`.
- `$casts`: agregar `'sobreanticipo' => 'integer'`.

## Semántica de columnas cacheadas (recalculadas en `LiquidacionCalculator::recalcAndSave`)

No se agregan columnas para los derivados; se **repurposan** las existentes. Base de cálculo: `sumatoria_gastos_operativos`, `descuentos`, `sumatoria_peajes`, `sumatoria_peajes_conductor`, `valor_flete`, `anticipo_empresa`, `anticipo_conductor`, `sobreanticipo`.

| Columna | Significado ANTES (004) | Significado NUEVO (005) | Fórmula nueva |
|---|---|---|---|
| `sumatoria_gastos_operativos` | suma de gastos operativos | igual (base) | `Σ expenses.valor` |
| `sumatoria_peajes` | suma de peajes usados | igual | `Σ tolls.valor (is_used)` |
| `sumatoria_peajes_conductor` | peajes que paga el conductor | igual (se conserva como dato) | `Σ tolls.valor (is_used, paid_by=conductor)` |
| `sumatoria_gastos_totales` | `gastos_op + peajes` | **+ descuentos** | `gastos_op + descuentos + peajes` |
| `total_anticipos` | `anticipo_empresa + anticipo_conductor` | **+ sobreanticipo** | `anticipo_empresa + anticipo_conductor + sobreanticipo` |
| `saldo_pendiente` | `anticipo_empresa − descuentos` | **"Saldo adeudado empresa"** | `valor_flete − anticipo_empresa` |
| `saldo_viaje` | `total_anticipos − gastos_op − peajes_conductor` | **"Ant - gastos"** | `(gastos_op + descuentos + peajes_conductor) − (anticipo_conductor + sobreanticipo)` |
| `ganancia_viaje` | `valor_flete − (gastos_op + peajes_empresa)` | **"Ganancia final"** | `valor_flete − (gastos_op + descuentos + peajes)` |
| `a_favor_de` | signo de `saldo_viaje` (viejo) | signo de `saldo_viaje` (=ant-gastos) | `>0→conductor, <0→empresa, =0→ninguno` |

> Las columnas `saldo_pendiente` y `saldo_viaje` conservan su nombre físico pero cambian de significado. Ya no se muestran sus etiquetas viejas ("Saldo pendiente"/"Saldo viaje"); el panel usa las etiquetas nuevas. Documentado para evitar confusión en mantenimiento.

## Valores derivados NO persistidos (se calculan en servicio/vista/JS)

| Celda | Fórmula | Dónde se calcula |
|---|---|---|
| Sumatoria de gastos | `sumatoria_gastos_operativos + descuentos + sumatoria_peajes_conductor` (el peaje del conductor sale de su bolsillo; no se resta de "Sumatoria de peajes") | accessor/helper + JS `sumGastos` |
| Anticipos conductor | `anticipo_conductor + sobreanticipo` | helper + JS `anticiposConductor` |

## Reglas de validación (Form Request)

| Campo | Reglas |
|---|---|
| `sobreanticipo` | `nullable, integer, min:0`; `prepareForValidation` convierte vacío→0 (igual que `anticipo_conductor`/`descuentos`). |

## Estados y permisos
- Sin cambios. Edición solo en `borrador` (policy existente). Scoping del rol `placas` sin cambios.

## Impacto en el consolidado (índice)
- `aggregate()` y `aggregateByMonth()` agregan `sum_gastos_totales`, `total_anticipos`, `saldo_viaje`, `ganancia_viaje`, `descuentos`. Al cambiar las fórmulas base, estos agregados reflejan la nueva definición automáticamente (no requieren cambio de código en el agregador). La UI del consolidado no se rediseña; solo cambian los números.
