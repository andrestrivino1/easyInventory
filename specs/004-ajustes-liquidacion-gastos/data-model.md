# Data Model — Ajustes de liquidación y gastos mensuales

**Branch**: `004-ajustes-liquidacion-gastos` | **Date**: 2026-05-27

Motor: MySQL/MariaDB (InnoDB, `utf8mb4_unicode_ci`). Convenciones Laravel 8. Extiende el modelo de la spec 002.

## Resumen de cambios

```text
drivers (existente)
  └──< monthly_expenses.driver_id          (NUEVO)

liquidaciones (existente — ALTERADA)
  ├─ anticipo        → RENOMBRA a anticipo_empresa
  ├─ sobreanticipo   → RENOMBRA a anticipo_conductor
  ├─ + descuentos            (NUEVO)
  ├─ + saldo_pendiente       (NUEVO, cacheado)
  └─ + manifiesto_pdf_path   (NUEVO)
```

---

## Tabla NUEVA: `monthly_expenses`

Costos fijos mensuales por conductor+vehículo. Un registro por conductor por período (mes/año). Independiente de las liquidaciones (sin FK a `liquidaciones`).

| Columna | Tipo | Constraints | Notas |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| driver_id | BIGINT UNSIGNED | NOT NULL, FK → drivers.id, ON DELETE RESTRICT | Conductor |
| vehicle_plate | VARCHAR(20) | nullable | Snapshot de `drivers.vehicle_plate` al guardar; null si el conductor no tiene placa |
| anio | SMALLINT UNSIGNED | NOT NULL | Año del período (ej. 2026) |
| mes | TINYINT UNSIGNED | NOT NULL | Mes 1–12 |
| sueldo_conductor | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | COP |
| seguridad_social | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | COP — "Seguridad social conductor" |
| cuota_banco | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | COP |
| cuota_tercero | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | COP |
| satelital | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | COP |
| seguro_vehiculo | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | COP |
| otro_valor | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | COP |
| otro_descripcion | VARCHAR(150) | nullable | Etiqueta del concepto "Otro" (clarificación Q2=B) |
| created_by | BIGINT UNSIGNED | NOT NULL, FK → users.id | |
| updated_by | BIGINT UNSIGNED | NOT NULL, FK → users.id | |
| created_at, updated_at | TIMESTAMP | nullable | Laravel default |

**Índices**:
- PK(id)
- UNIQUE(`driver_id`, `anio`, `mes`) — un gasto por conductor por mes (FR-007b)
- INDEX(`vehicle_plate`) — filtro por placa (FR-005)
- INDEX(`anio`, `mes`) — filtro por período

**Reglas (modelo / Form Request)**:
- `driver_id` requerido y existente.
- `anio` entre p. ej. 2020 y 2100; `mes` entre 1 y 12.
- Todos los montos `>= 0`; por defecto 0 (se permite guardar todo en 0 — edge case spec).
- `otro_descripcion` opcional (recomendado si `otro_valor > 0`).
- `vehicle_plate` se setea server-side desde `driver->vehicle_plate` al crear/editar (no se confía en el cliente).
- Unicidad `driver_id+anio+mes` validada con `Rule::unique` (ignorando el propio id en update).

**Modelo Eloquent** `App\Models\MonthlyExpense`:
- `$fillable`: driver_id, vehicle_plate, anio, mes, sueldo_conductor, seguridad_social, cuota_banco, cuota_tercero, satelital, seguro_vehiculo, otro_valor, otro_descripcion, created_by, updated_by.
- `$casts`: anio→int, mes→int, todos los montos→int.
- Relación `driver(): BelongsTo`.
- Accessor opcional `total` = suma de los 7 montos (para mostrar en lista).
- Scopes: `scopePlaca($q,$placa)`, `scopePeriodo($q,$anio,$mes)`.

---

## Tabla ALTERADA: `liquidaciones`

### Renombrados (R1 — `ALTER TABLE ... CHANGE`, preserva datos)

| Antes | Después | Tipo | Notas |
|---|---|---|---|
| anticipo | **anticipo_empresa** | DECIMAL(12,0) NOT NULL DEFAULT 0 | FR-011 |
| sobreanticipo | **anticipo_conductor** | DECIMAL(12,0) NOT NULL DEFAULT 0 | FR-011 |

### Columnas nuevas

| Columna | Tipo | Constraints | Notas |
|---|---|---|---|
| descuentos | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | Descuentos de la empresa de transporte (FR-012) |
| saldo_pendiente | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | Cacheado = `anticipo_empresa − descuentos` (FR-013); puede ser negativo |
| manifiesto_pdf_path | VARCHAR(255) | nullable | Ruta del PDF en `storage/app` (FR-019); null si no hay manifiesto |

> `DECIMAL(12,0)` es UNSIGNED en otras columnas, pero `saldo_pendiente` debe permitir negativos → declararla **sin** UNSIGNED.

### Campos derivados (recálculo en `LiquidacionCalculator::recalcAndSave`)

| Campo | Fórmula (actualizada) |
|---|---|
| total_anticipos | `anticipo_empresa + anticipo_conductor` (antes anticipo + sobreanticipo) |
| saldo_viaje | `total_anticipos − sumatoria_gastos_operativos − peajes_conductor` (sin cambio semántico) |
| ganancia_viaje | `valor_flete − (gastos_operativos + peajes_empresa)` (sin cambio) |
| **saldo_pendiente** | `anticipo_empresa − descuentos` (NUEVO) |
| a_favor_de | derivado de `saldo_viaje` (sin cambio) |

**Cambios en `Liquidacion` model**:
- `$fillable`: quitar `anticipo`, `sobreanticipo`; agregar `anticipo_empresa`, `anticipo_conductor`, `descuentos`, `saldo_pendiente`, `manifiesto_pdf_path`.
- `$casts`: renombrar las dos claves de anticipo; agregar `descuentos`→int, `saldo_pendiente`→int.
- Helper `hasManifiesto(): bool` = `!is_null($this->manifiesto_pdf_path)`.

**Cambios en `LiquidacionCalculator`**:
- `computeTotalAnticipos(int $empresa, int $conductor)` (renombrar parámetros).
- `recalcAndSave`: leer `$liq->anticipo_empresa`/`$liq->anticipo_conductor`; calcular y setear `$liq->saldo_pendiente = (int)$liq->anticipo_empresa - (int)$liq->descuentos;`.
- `aggregate`/`aggregateByMonth`: agregar `COALESCE(SUM(descuentos),0) as sum_descuentos` para mostrar descuentos en el consolidado (FR-014/US6).

---

## `liquidacion_tolls` (sin cambio de esquema)

US3 (eliminar peaje) **no** altera el esquema: usa el sync full-replace existente. Sin migración. Solo cambia el frontend (`_tolls-table.blade.php` + `liquidacion-form.js`).

---

## Migraciones (orden)

1. `2026_05_27_000000_create_monthly_expenses_table.php` — crea `monthly_expenses` con índices y FKs.
2. `2026_05_27_000100_adjust_liquidaciones_anticipos_descuentos.php`:
   - `DB::statement` CHANGE para los dos renames.
   - `Schema::table` para agregar `descuentos`, `saldo_pendiente`, `manifiesto_pdf_path`.
   - `down()`: revertir (CHANGE inverso + dropColumn).
   - Backfill: `saldo_pendiente = anticipo_empresa - descuentos` (descuentos arranca en 0, así que `saldo_pendiente = anticipo_empresa`) para filas existentes.

## Estimaciones de volumen

- `monthly_expenses`: ~20–50 conductores × 12 meses ≈ 240–600 filas/año. Trivial.
- `liquidaciones`: sin cambio de volumen; +3 columnas estrechas.
