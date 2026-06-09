# Data Model — Informes y Analítica de Liquidaciones de Viajes

**No hay tablas ni migraciones nuevas.** El informe es de **solo lectura agregada** sobre el esquema existente. Esta sección documenta (a) las tablas/columnas que se leen, (b) las agregaciones que produce el módulo, y (c) las fórmulas exactas.

---

## 1. Fuentes de datos (existentes, solo lectura)

### `liquidaciones`
Una fila por viaje liquidado. Columnas usadas (todas cacheadas por `LiquidacionCalculator::recalcAndSave`):

| Columna | Uso en el informe |
|---|---|
| `fecha_inicio` | Atribución al periodo (mes/semestre/año). Driver de la agrupación. |
| `estado` | Filtro: solo cuentan las **activas** (`scopeActivas` excluye `anulada` y soft-deleted). |
| `driver_id` | Desglose por conductor (US4). |
| `vehicle_plate` | Etiqueta de placa en el desglose por conductor. |
| `valor_flete` | Ingreso por flete. → `sum_flete`. |
| `sumatoria_gastos_operativos` | Total de gastos operativos (16 categorías) por viaje. → `sum_gastos_operativos`. |
| `sumatoria_peajes` | Total de peajes usados por viaje. → `sum_peajes`. |
| `sumatoria_gastos_totales` | Gastos op + descuentos + peajes. → `sum_gastos_totales`. |
| `ganancia_viaje` | `valor_flete − sumatoria_gastos_totales`. → `sum_ganancia` (base de la utilidad). |

> El informe **no** recomputa por fila: usa estas columnas cacheadas vía `aggregate()`/`aggregateByMonth()`. La única bajada a nivel de detalle es el **desglose por categoría** (ver `liquidacion_expenses`), porque no existe cache por categoría.

### `liquidacion_expenses` + `expense_categories`
Desglose **por categoría operativa**. `liquidacion_expenses(liquidacion_id, expense_category_id, valor)` ⋈ `expense_categories(id, code, name, sort_order, active)`. Las 16 categorías incluyen **VIÁTICOS** (no es una columna aparte). Agregado por categoría para las liquidaciones activas del periodo.

### `liquidacion_tolls`
No se consulta directamente para el total: los peajes ya están sumados en `liquidaciones.sumatoria_peajes`. (Se documenta por completitud; el informe reporta peajes como un renglón propio, separado de las categorías operativas.)

### `monthly_expenses`
Costos fijos por `(driver_id, anio, mes)`. 7 conceptos: `sueldo_conductor`, `seguridad_social`, `cuota_banco`, `cuota_tercero`, `satelital`, `seguro_vehiculo`, `otro_valor` (+ `otro_descripcion`). Se suman para las tuplas (conductor, año, mes) presentes en el periodo y se restan a la ganancia para obtener la **utilidad neta**.

### `drivers`
`id`, `name`, `vehicle_plate` para etiquetar el desglose por conductor y poblar el selector de filtro.

---

## 2. Entidades de lectura producidas por el módulo (no persistidas)

Estructuras en memoria que el controlador compone y pasa a la vista / PDF.

### `ResumenPeriodo`
Totales consolidados del periodo (alcance: empresa completa, o un conductor si se filtra).

| Campo | Origen | Definición |
|---|---|---|
| `sum_flete` | `aggregate()` | Σ `valor_flete` |
| `sum_gastos_operativos` | `aggregate()` | Σ `sumatoria_gastos_operativos` |
| `sum_peajes` | `aggregate()` | Σ `sumatoria_peajes` |
| `sum_gastos_totales` | `aggregate()` | Σ `sumatoria_gastos_totales` |
| `sum_ganancia` | `aggregate()` | Σ `ganancia_viaje` (ganancia bruta de viajes) |
| `sum_gastos_mensuales` | `monthlyExpensesTotalFor(tripPeriods)` | Σ de los 7 conceptos fijos de las tuplas del periodo |
| **`utilidad_neta`** | derivado | **`sum_ganancia − sum_gastos_mensuales`** |
| `resultado` | derivado | `'ganancia'` si `utilidad_neta > 0`, `'perdida'` si `< 0`, `'equilibrio'` si `= 0` |
| `count` | `aggregate()` | Nº de liquidaciones activas |
| `margen_pct` | `aggregate()` | `sum_ganancia / sum_flete * 100` |

### `DesgloseCategorias` (nuevo)
Lista del gasto operativo por categoría. Producido por `expensesByCategory()`.

| Campo | Definición |
|---|---|
| `code`, `name` | Identidad de la categoría (incluye VIÁTICOS) |
| `total` | Σ `liquidacion_expenses.valor` para esa categoría en el periodo |

Invariante: `Σ DesgloseCategorias.total == ResumenPeriodo.sum_gastos_operativos` (FR-011).

### `DesgloseGastosFijos` (nuevo)
Detalle de los 7 conceptos fijos del periodo. Producido por `monthlyExpensesBreakdownFor()`.

| Campo | Definición |
|---|---|
| `sueldo_conductor`, `seguridad_social`, `cuota_banco`, `cuota_tercero`, `satelital`, `seguro_vehiculo`, `otro_valor` | Σ de cada concepto sobre las tuplas (conductor, año, mes) del periodo |
| `total` | Σ de los 7 = `ResumenPeriodo.sum_gastos_mensuales` |

### `EvolucionMensual`
Serie por mes (`YYYY-MM`) dentro del periodo, para las gráficas y el resalte mejor/peor.

| Campo | Origen | Definición |
|---|---|---|
| `periodo` | `aggregateByMonth()` | `YYYY-MM` |
| `sum_flete`, `sum_gastos_totales`, `sum_ganancia` | `aggregateByMonth()` | Σ del mes |
| `sum_gastos_mensuales` | `monthlyExpensesTotalFor(tripPeriodsByMonth[periodo])` | Fijos del mes |
| `utilidad_neta` | derivado | `sum_ganancia − sum_gastos_mensuales` |

Derivados de la serie: `mes_mayor_ganancia` = periodo con `utilidad_neta` máxima; `mes_mayor_perdida` = periodo con `utilidad_neta` mínima (FR-010).

### `DesgloseConductor` (US4)
`ResumenPeriodo` calculado con el `Builder` base acotado por `where('driver_id', $id)`. Incluye los gastos fijos **propios** de ese conductor (sus tuplas conductor/mes). Invariante: `Σ DesgloseConductor(utilidad_neta) == ResumenPeriodo(utilidad_neta)` para el mismo periodo (FR-018, SC-005).

---

## 3. Fórmulas (resumen normativo)

```
sum_ganancia          = Σ (valor_flete − sumatoria_gastos_totales)         [ya cacheado por viaje]
sum_gastos_mensuales  = Σ (7 conceptos fijos)  por (driver_id, anio, mes) únicos del periodo
utilidad_neta         = sum_ganancia − sum_gastos_mensuales                 [= utilidad_final del índice]
resultado             = ganancia (>0) | perdida (<0) | equilibrio (=0)

Σ_categorias(valor)   = sum_gastos_operativos        (invariante de consistencia)
peajes                = sum_peajes                    (renglón propio, fuera de categorías)
```

Todos los agregados aplican `scopeActivas()` (excluye `anulada` + soft-deleted) — FR-007.

---

## 4. Métodos nuevos en `LiquidacionCalculator` (contrato interno)

| Método | Firma | Devuelve |
|---|---|---|
| `expensesByCategory` | `(Builder $query): Collection` | Colección de `['code','name','total']` por categoría, periodo acotado, `->activas()` |
| `monthlyExpensesBreakdownFor` | `($tuples): array` | `['sueldo_conductor'=>int, …, 'otro_valor'=>int, 'total'=>int]` |
| `aggregateByDriver` *(opcional)* | `(Builder $query): Collection` | Un `ResumenPeriodo` por `driver_id` (o reutilizar `aggregate()` con `where('driver_id',…)`) |

Reutilizados sin cambios: `aggregate`, `aggregateByMonth`, `tripPeriods`, `tripPeriodsByMonth`, `monthlyExpensesTotalFor`.

---

## 5. Reglas de validación de entrada (request del informe)

| Parámetro | Regla |
|---|---|
| `tipo` | requerido, `in:mes,semestre,anio` |
| `anio` | requerido, entero, rango razonable (p. ej. `2000..2100`) |
| `mes` | requerido si `tipo=mes`, `in:1..12` |
| `semestre` | requerido si `tipo=semestre`, `in:1,2` |
| `driver_id` | opcional, `exists:drivers,id` (filtro/desglose por conductor) |
| `charts[]` *(solo POST PDF)* | opcional, data-URLs PNG base64 de las gráficas a embeber |
