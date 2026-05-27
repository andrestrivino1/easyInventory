# Research — Ajustes de liquidación y gastos mensuales

**Branch**: `004-ajustes-liquidacion-gastos` | **Date**: 2026-05-27

Decisiones técnicas para resolver los puntos abiertos del Technical Context. La spec no dejó `[NEEDS CLARIFICATION]`; aquí se fijan decisiones de implementación.

---

## R1. Estrategia para renombrar `anticipo`/`sobreanticipo`

**Decision**: Renombrar a `anticipo_empresa` y `anticipo_conductor` mediante SQL crudo en la migración:
`DB::statement("ALTER TABLE liquidaciones CHANGE anticipo anticipo_empresa DECIMAL(12,0) NOT NULL DEFAULT 0")` y equivalente para `sobreanticipo`. Los datos se conservan en el rename (no hay copia).

**Rationale**: Laravel 8 `renameColumn()` requiere `doctrine/dbal`, que **no** está en `composer.json`. Usar `ALTER TABLE ... CHANGE` evita sumar una dependencia solo para una migración. El rename preserva los valores existentes, cumpliendo FR-011 ("migrar datos").

**Alternatives considered**:
- Agregar `doctrine/dbal` y usar `renameColumn()` — descartado: dependencia nueva innecesaria.
- Crear columnas nuevas + copiar + borrar viejas — descartado: más pasos, ventana de inconsistencia, nombres duplicados.

**Impacto en código** (todas las referencias a `anticipo`/`sobreanticipo`): `Liquidacion` (fillable, casts), `LiquidacionCalculator::computeTotalAnticipos` + `recalcAndSave`, `StoreLiquidacionRequest`, `resources/views/liquidaciones/partials/_form.blade.php`, `show.blade.php`, `index.blade.php`, `pdf.blade.php`, `resources/js/liquidacion-form.js`. `total_anticipos = anticipo_empresa + anticipo_conductor` se mantiene.

---

## R2. Modelo de período del gasto mensual

**Decision**: Columnas `anio SMALLINT UNSIGNED` + `mes TINYINT UNSIGNED` (1–12) en `monthly_expenses`, con `UNIQUE(driver_id, anio, mes)`. Filtros por `vehicle_plate` y por `(anio, mes)`.

**Rationale**: Cumple FR-007 (un registro por conductor por mes) y FR-007b (unicidad). Enteros separados permiten índices y filtros simples por mes/año, y un selector de mes/año en el formulario. Más legible que un `DATE` con día ficticio.

**Alternatives considered**:
- `periodo CHAR(7)` `'YYYY-MM'` — válido, pero menos cómodo para filtrar por año o por mes por separado.
- `periodo DATE` (día 1) — fuerza convención de "día 1" y casts; innecesario.

---

## R3. `saldo_pendiente` vs `saldo_viaje` (coexistencia)

**Decision**: `saldo_pendiente` es un campo **nuevo e independiente** de `saldo_viaje`. Fórmula `saldo_pendiente = anticipo_empresa − descuentos` (FR-013). Se persiste como columna cacheada `DECIMAL(12,0)` (puede ser negativa) y se recalcula en `recalcAndSave`. `saldo_viaje`, `ganancia_viaje`, `total_anticipos` mantienen su semántica actual (con `anticipo_empresa`+`anticipo_conductor` como sumandos del total de anticipos).

**Rationale**: La spec define explícitamente una fórmula distinta para "saldo pendiente"; no reemplaza al saldo del viaje. Persistirlo mantiene la simetría con los demás totales cacheados y permite mostrarlo en listas y PDF sin recomputar.

**Alternatives considered**: Calcular `saldo_pendiente` solo en la vista — rechazado por romper la simetría con `saldo_viaje`/`sumatoria_*` ya persistidos.

**Nota sobre `descuentos`**: los descuentos de la transportadora **no** alteran `ganancia_viaje` ni `saldo_viaje`; solo alimentan `saldo_pendiente` y se muestran como línea explícita en totales (FR-014). (Si en QA se decide que afectan la ganancia, es un cambio acotado en el calculator.)

---

## R4. Almacenamiento y entrega del manifiesto PDF

**Decision**: Columna `manifiesto_pdf_path VARCHAR(255) NULL` en `liquidaciones`. Subida de un único PDF a `storage/app/manifiestos/` (disco `local`, no público). Ruta autenticada `liquidaciones.manifiesto` (ver/descargar) que hace `Storage::download`/stream, replicando `DriverController::viewSocialSecurityPdf`. Reemplazo: al subir uno nuevo se borra el archivo anterior. Eliminación: ruta/acción que borra archivo y limpia la columna.

**Rationale**: Reusa el patrón ya probado de `drivers.social_security_pdf` e `itrs` (PDF en `storage/app`, servido por ruta autenticada). Mantiene los archivos fuera de `public/`. Validación `mimetypes:application/pdf` + `max` (tamaño) en el Form Request.

**Tamaño máximo**: 10 MB (`max:10240` KB) como valor por defecto razonable (ajustable). Resuelve la asunción abierta de la spec.

**Alternatives considered**:
- Disco `public` + symlink — rechazado: los manifiestos no deben ser públicos sin auth (aislamiento `placas`).
- Tabla de adjuntos múltiple — rechazado: la clarificación fijó **un** PDF por liquidación (R-cardinalidad).

---

## R5. Eliminar peaje del viaje (UI vs backend)

**Decision**: Cambio principalmente de **frontend**. La tabla "Peajes del viaje" se renderiza desde un array Alpine; se agrega un botón "eliminar" por fila que hace `splice()` del array. El guardado ya sincroniza `liquidacion_tolls` con estrategia **delete + insert** (regla de la spec 002), por lo que una fila quitada del array simplemente deja de existir tras `update()`. No se modifica `route_tolls` (catálogo), cumpliendo FR-017.

**Rationale**: Aprovecha el sync existente; minimiza cambios de backend. Solo se verifica que `store`/`update` reconstruyen `liquidacion_tolls` a partir del payload (no hacen merge incremental).

**Verificación pendiente en implementación**: confirmar en `LiquidacionController::update` que el sync de peajes es full-replace; si fuese incremental, ajustar para borrar las filas ausentes del payload.

---

## R6. Independencia de Gastos mensuales

**Decision**: `monthly_expenses` es un registro autónomo (control de costos fijos). **No** se inyecta en `liquidacion_expenses` ni en los cálculos de `LiquidacionCalculator` (FR-008). Sin relación FK hacia `liquidaciones`.

**Rationale**: La spec lo define como lista/CRUD independiente; mezclarlo con el cálculo por viaje sería alcance no pedido y cambiaría la matemática del módulo.

---

## R7. Control de acceso admin-only para Gastos mensuales

**Decision**: Nuevo gate `liquidaciones.gastos.access` en `AppServiceProvider`: `return $user->rol === 'admin';`. Las rutas de gastos mensuales se agrupan con middleware `can:liquidaciones.gastos.access` (además del `auth` y `can:liquidaciones.access` del módulo). El botón "Gastos mensuales" en `index.blade.php` se envuelve en `@can('liquidaciones.gastos.access')`.

**Rationale**: El gate existente `liquidaciones.access` admite `admin` y `placas`; Gastos mensuales debe excluir `placas` (FR-009, clarificación Q1=A). Un gate dedicado es explícito y testeable.

**Alternatives considered**: Chequear `$user->rol` inline en el controlador — rechazado por menos testeable y disperso; el gate centraliza la regla.

---

## R8. Autollenado de placa al elegir conductor (gastos mensuales)

**Decision**: Reusar el endpoint existente `liquidaciones.drivers.info` (`drivers/{driver}/info`) que ya devuelve datos del conductor (incluida `vehicle_plate`) para autocompletar la placa en el formulario de gasto mensual vía Alpine/fetch. La placa se persiste como snapshot en `monthly_expenses.vehicle_plate` al guardar (server-side desde `driver_id`, no confiando en el cliente).

**Rationale**: Evita duplicar lógica AJAX; la fuente de verdad de la placa es `drivers.vehicle_plate`. El snapshot permite filtrar por placa aunque el conductor cambie de vehículo después.

**Alternatives considered**: Derivar la placa solo en runtime por join — rechazado: el filtro por placa histórica y la coherencia del registro mensual se benefician del snapshot.

---

## Resumen de decisiones

| # | Tema | Decisión |
|---|---|---|
| R1 | Rename columnas | `ALTER TABLE ... CHANGE` (sin doctrine/dbal); preserva datos |
| R2 | Período gasto | `anio` + `mes` ints, `UNIQUE(driver_id, anio, mes)` |
| R3 | Saldo pendiente | Campo nuevo cacheado = `anticipo_empresa − descuentos`; no toca `saldo_viaje` |
| R4 | Manifiesto PDF | `manifiesto_pdf_path` + `storage/app/manifiestos`, ruta auth, máx 10 MB |
| R5 | Eliminar peaje | UI Alpine `splice()`; backend ya hace full-replace de tolls |
| R6 | Gastos independientes | Sin FK ni efecto en cálculo de liquidación |
| R7 | Acceso admin-only | Gate `liquidaciones.gastos.access` (`rol === 'admin'`) |
| R8 | Autollenado placa | Reusar `drivers/{driver}/info`; snapshot server-side |
