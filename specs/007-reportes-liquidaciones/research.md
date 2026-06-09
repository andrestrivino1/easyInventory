# Research — Informes y Analítica de Liquidaciones de Viajes

Decisiones técnicas (Phase 0). Cada una resuelve un punto que el spec dejaba abierto o que la implementación necesita fijar. No hay `NEEDS CLARIFICATION` pendientes.

---

## 1. Gráficas dentro del PDF (DomPDF no ejecuta JavaScript)

**Decisión**: El dashboard renderiza las gráficas con **Chart.js** en el navegador. Para el PDF, el cliente captura cada `<canvas>` con `chart.toBase64Image()` / `canvas.toDataURL('image/png')` y envía los data-URLs al endpoint PDF mediante un **POST**; la plantilla DomPDF las embebe como `<img src="data:image/png;base64,...">`. Si el POST no trae imágenes (p. ej. enlace directo), el PDF se genera igual con **todas las tablas numéricas** y omite las imágenes.

**Rationale**:
- DomPDF (el paquete ya instalado y usado en `LiquidacionPdfController`) **no corre JS**, así que no puede renderizar un `<canvas>` de Chart.js por sí mismo.
- Capturar a PNG produce en el PDF **exactamente** la misma gráfica que ve el usuario (cumple "que genere una gráfica" y SC-004 de coincidencia con pantalla) sin introducir un motor nuevo.
- DomPDF incrusta imágenes base64 de forma nativa y fiable.

**Alternativas consideradas**:
- *Reimplementar las gráficas en HTML/CSS (barras con divs) para DomPDF*: 100% server-side y enlazable por GET, pero duplica la lógica de gráficas y no coincide visualmente con la pantalla. Se conserva solo como **fallback conceptual**: las tablas numéricas (no barras CSS) son el respaldo real del dato.
- *Cambiar a wkhtmltopdf/snappy (sí ejecuta JS)*: requiere binario externo y nueva dependencia; rechazado para no ampliar el stack.
- *Servicio headless (Puppeteer/Browsershot) para renderizar*: sobredimensionado para este alcance y añade Node/Chromium en producción.

**Implicación de contrato**: el endpoint PDF acepta `POST` con un payload opcional de imágenes (data-URLs) además de los parámetros de periodo. Ver [contracts/http-routes.md](contracts/http-routes.md).

---

## 2. Definición de "semestre" y mapeo de periodo → rango de fechas

**Decisión**: El informe acepta un `tipo` de periodo con tres valores y se traduce a `[fecha_desde, fecha_hasta]` sobre `liquidaciones.fecha_inicio`:
- **mes**: `anio` + `mes` → `[YYYY-MM-01, último día del mes]`.
- **semestre**: `anio` + `semestre ∈ {1,2}` → S1 = `[YYYY-01-01, YYYY-06-30]`, S2 = `[YYYY-07-01, YYYY-12-31]` (semestres **calendario fijos**, no móviles).
- **anio**: `anio` → `[YYYY-01-01, YYYY-12-31]`.

**Rationale**: `fecha_inicio` ya es el criterio de agrupación mensual del sistema (`aggregateByMonth`, `tripPeriods`). Los semestres calendario fijos son la interpretación más común para informes contables y coinciden con la agrupación por mes existente. Documentado también como Assumption en el spec; ajustable en `/speckit-clarify` si se quisieran semestres móviles.

**Alternativas consideradas**:
- *Rango de fechas libre (desde/hasta arbitrario)*: más flexible pero el usuario pidió explícitamente mes/semestre/año; un selector libre se puede añadir luego sin reescribir la agregación (todo pasa por `fecha_desde/fecha_hasta`).
- *Semestres móviles (últimos 6 meses)*: rechazado por ambigüedad contable; no fue lo pedido.

---

## 3. Reutilización del cálculo de utilidad neta

**Decisión**: La **utilidad neta** del informe es exactamente la `utilidad_final` que el índice ya calcula:
`utilidad_neta = sum_ganancia − monthlyExpensesTotalFor(tripPeriods(query))`,
donde `sum_ganancia` proviene de `LiquidacionCalculator::aggregate()` y los gastos fijos de `monthlyExpensesTotalFor()`. Para el desglose mensual (mejor/peor mes) se usa `aggregateByMonth()` + `tripPeriodsByMonth()`, ya disponibles.

**Rationale**: Garantiza que el informe **no diverja** del consolidado que el admin ya ve en `liquidaciones.index` (SC-002, SC-004). Reutiliza código probado; cero reimplementación de la fórmula de utilidad.

**Alternativas consideradas**:
- *Recalcular la utilidad en el controlador del informe*: riesgo de divergencia y duplicación; rechazado.

---

## 4. Desgloses que NO existen hoy y deben añadirse al servicio

**Decisión**: Añadir a `LiquidacionCalculator` dos métodos de solo lectura (sobre `Builder ->activas()`):

1. `expensesByCategory(Builder $query): Collection` — join `liquidacion_expenses → expense_categories`, filtrado por las liquidaciones del periodo, agrupado por categoría → `[{ code, name, total }]`. Cubre el desglose "tanto en viáticos, tanto en ACPM, …" (viáticos es una categoría más). Los **peajes** se reportan aparte vía `sum_peajes` del consolidado (no son `expense_categories`).
2. `monthlyExpensesBreakdownFor($tuples): array` — como `monthlyExpensesTotalFor` pero devolviendo cada uno de los **7 conceptos** por separado (`sueldo_conductor`, `seguridad_social`, `cuota_banco`, `cuota_tercero`, `satelital`, `seguro_vehiculo`, `otro_valor`) además del total. Cubre "cuánto se gastó en sueldo, cuánto en seguridad social, …".

(Opcional) `aggregateByDriver(Builder $query): Collection` para el desglose por conductor (US4), o bien reutilizar `aggregate()` aplicando un `where('driver_id', …)` al `Builder` base — ver decisión 6.

**Rationale**: El consolidado actual solo persiste el **total** de gastos operativos (`sumatoria_gastos_operativos`), no por categoría; y `monthlyExpensesTotalFor` solo da el total agregado. El informe exige ambos desgloses. Mantenerlos en el servicio conserva la centralización y el filtro `->activas()`.

**Alternativas consideradas**:
- *Añadir columnas cacheadas por categoría en `liquidaciones`*: migración + mantenimiento en cada save; innecesario para lectura de informes y contradice "cero migraciones".

---

## 5. Acceso restringido a admin

**Decisión**: Definir `Gate::define('liquidaciones.reportes.access', fn ($u) => $u->rol === 'admin')` en `AppServiceProvider` y proteger las 2 rutas con `middleware('can:liquidaciones.reportes.access')` dentro del grupo `liquidaciones` existente (que ya exige `auth` + `can:liquidaciones.access`).

**Rationale**: Reproduce el patrón admin-only ya usado para los gastos mensuales (`liquidaciones.gastos.access`). El dato (sueldos, utilidad) es sensible; el spec fija acceso solo admin (FR-001, SC-006). Un gate dedicado hace explícita la superficie y permite evolucionar sin tocar el de gastos.

**Alternativas consideradas**:
- *Reutilizar `liquidaciones.gastos.access`*: acopla dos features bajo un mismo nombre; rechazado por claridad.
- *Policy*: no hay instancia de modelo que autorizar (es lectura agregada); un gate es lo idiomático aquí.

---

## 6. Multi-tenant / aislamiento por cliente en Liquidaciones

**Decisión**: En este módulo **no hay scoping por `cliente`**: las liquidaciones no tienen `cliente_id` y el acceso es exclusivamente `admin` (operador único de la flota). El informe agrega **todas** las liquidaciones activas del rango. El "aislamiento por cliente" que menciona el spec se satisface por construcción: ningún rol cliente/funcionario accede al módulo.

**Rationale**: El aislamiento multi-tenant del sistema aplica a Inventario/Importaciones (por `warehouse`/`cliente`), no a Liquidaciones, que es admin/placas. Como el informe es admin-only y las liquidaciones no son por cliente, no existe riesgo de fuga entre tenants dentro de este módulo.

**Implicación**: El desglose por conductor (US4) usa `driver_id`, no `cliente`. Para `placas` (que sí tiene conductores asignados) este módulo **no** se habilita en esta versión (solo admin).

---

## 7. Estado vacío y bordes

**Decisión**: Si el periodo no tiene liquidaciones activas, el dashboard muestra un estado vacío (totales en 0, utilidad neta 0) y el PDF se genera con las tablas en cero; nunca un error (FR-017). Un mes con gastos fijos pero sin viajes refleja **pérdida** por esos fijos (la utilidad neta resta los gastos mensuales aunque `sum_ganancia` sea 0) — comportamiento heredado de la fórmula existente.

**Rationale**: Coincide con FR-017 y con los edge cases del spec; no requiere lógica especial más allá de los `COALESCE(...,0)` ya presentes en las agregaciones.
