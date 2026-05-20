# Data Model — Liquidación de Viajes

**Branch**: `002-liquidacion-viajes` | **Date**: 2026-05-19

Motor: MySQL/MariaDB (InnoDB, `utf8mb4_unicode_ci`). Convenciones Laravel 8.

## Diagrama de relaciones

```text
users (existente)
  └──< liquidaciones.created_by
  └──< liquidaciones.updated_by
  └──< liquidacion_state_logs.user_id

drivers (existente)
  └──< liquidaciones.driver_id

routes
  ├──< route_tolls           (peajes catálogo de la ruta)
  └──< liquidaciones.route_id

expense_categories (seed: 16)
  └──< liquidacion_expenses.expense_category_id

liquidaciones
  ├──< liquidacion_expenses   (1 fila por categoría con valor)
  ├──< liquidacion_tolls      (1 fila por peaje del viaje, snapshot)
  └──< liquidacion_state_logs (auditoría de transiciones)
```

---

## Tabla 1: `expense_categories`

Catálogo cerrado de las 16 categorías. Seeded en `database/seeders/ExpenseCategorySeeder.php`. No se gestiona por UI.

| Columna | Tipo | Constraints | Notas |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| code | VARCHAR(40) | NOT NULL, UNIQUE | Snake case: `acpm`, `urea`, `comision`, `porcentaje`, `montallantas`, `parqueaderos`, `lavada_del_carro`, `lubricantes`, `engrasada`, `electrico`, `bascula`, `embolada_de_llantas`, `varios`, `carpada`, `descarpada`, `viaticos` |
| name | VARCHAR(100) | NOT NULL | Etiqueta visual en mayúsculas (ej. "ACPM", "LAVADA DEL CARRO") |
| has_galones | TINYINT(1) | NOT NULL, DEFAULT 0 | Solo `1` para `acpm` |
| sort_order | SMALLINT UNSIGNED | NOT NULL, DEFAULT 0 | 1..16 según orden de la tabla del Excel |
| active | TINYINT(1) | NOT NULL, DEFAULT 1 | Para soft-disable futuro |
| created_at, updated_at | TIMESTAMP | nullable | Laravel default |

**Índices**: PK(id), UNIQUE(code), INDEX(sort_order).

**Seed inicial** (orden = imagen del Excel):

```
1  acpm                 ACPM                  has_galones=1
2  urea                 UREA
3  comision             COMISIÓN
4  porcentaje           PORCENTAJE
5  montallantas         MONTALLANTAS
6  parqueaderos         PARQUEADEROS
7  lavada_del_carro     LAVADA DEL CARRO
8  lubricantes          LUBRICANTES
9  engrasada            ENGRASADA
10 electrico            ELÉCTRICO
11 bascula              BÁSCULA
12 embolada_de_llantas  EMBOLADA DE LLANTAS
13 varios               VARIOS
14 carpada              CARPADA
15 descarpada           DESCARPADA
16 viaticos             VIÁTICOS
```

---

## Tabla 2: `routes`

Rutas de transporte gestionables por admin desde el mismo módulo.

| Columna | Tipo | Constraints | Notas |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| origen | VARCHAR(100) | NOT NULL | Ciudad origen, texto libre |
| destino | VARCHAR(100) | NOT NULL | Ciudad destino, texto libre |
| name | VARCHAR(255) | NOT NULL | Cache `"{origen} → {destino}"`, mantenido por modelo en `saving` |
| descripcion | TEXT | nullable | Notas internas |
| active | TINYINT(1) | NOT NULL, DEFAULT 1 | Inactivar oculta del select de nuevas liquidaciones |
| created_at, updated_at | TIMESTAMP | nullable | |

**Índices**: PK(id), INDEX(active), INDEX(name) para el select.

**Reglas**:
- `UNIQUE(origen, destino)` no se aplica (puede haber rutas con misma cabeza pero distintas notas/peajes).
- No FK obligatoria a ciudades — texto libre por decisión spec.

---

## Tabla 3: `route_tolls`

Catálogo de peajes asociados a una ruta. Sirve como plantilla para autocargar `liquidacion_tolls`.

| Columna | Tipo | Constraints | Notas |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| route_id | BIGINT UNSIGNED | NOT NULL, FK → routes.id, ON DELETE CASCADE | Si la ruta puede eliminarse físicamente (solo si no tiene liquidaciones), arrastra sus peajes |
| name | VARCHAR(100) | NOT NULL | "Loboguerrero", "Túnel de la Línea", etc. |
| suggested_value | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | Pesos COP, sin decimales |
| sort_order | SMALLINT UNSIGNED | NOT NULL | 1..N en el orden del documento |
| direction | ENUM('ida','regreso') | NOT NULL, DEFAULT 'ida' | Sentido |
| created_at, updated_at | TIMESTAMP | nullable | |

**Índices**: PK(id), INDEX(route_id, sort_order).

---

## Tabla 4: `liquidaciones`

Entidad principal. Una fila = un viaje liquidado.

| Columna | Tipo | Constraints | Notas |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| driver_id | BIGINT UNSIGNED | NOT NULL, FK → drivers.id, ON DELETE RESTRICT | Conductor del viaje |
| vehicle_plate | VARCHAR(20) | NOT NULL | Snapshot de `drivers.vehicle_plate` al guardar; editable como excepción (FR-001b) |
| route_id | BIGINT UNSIGNED | nullable, FK → routes.id, ON DELETE RESTRICT | Puede ser null para liquidaciones ad-hoc; los peajes se capturan manualmente entonces |
| transportadora | VARCHAR(150) | NOT NULL | Texto libre |
| telefono_empresa | VARCHAR(40) | nullable | Texto libre |
| anticipo | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | COP enteros |
| sobreanticipo | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | COP enteros |
| fecha_inicio | DATE | NOT NULL | |
| fecha_fin | DATE | NOT NULL | Validación: ≥ fecha_inicio |
| numero_mfto | VARCHAR(60) | nullable | Manifiesto |
| valor_flete | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | COP |
| estado | ENUM('borrador','cerrada','anulada') | NOT NULL, DEFAULT 'borrador' | |
| motivo_anulacion | TEXT | nullable | Solo se llena al pasar a `anulada` |
| **sumatoria_gastos_operativos** | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | Stored cache. Σ `liquidacion_expenses.valor` |
| **sumatoria_peajes** | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | Stored cache. Σ `liquidacion_tolls.valor` |
| **sumatoria_gastos_totales** | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | Stored cache. = operativos + peajes |
| **total_anticipos** | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | Stored cache. = anticipo + sobreanticipo |
| **saldo_viaje** | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | Stored cache. = total_anticipos − sumatoria_gastos_operativos |
| **ganancia_viaje** | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | Stored cache. = valor_flete − sumatoria_gastos_totales |
| **a_favor_de** | ENUM('empresa','conductor','ninguno') | NOT NULL, DEFAULT 'ninguno' | Derivado del signo de `saldo_viaje` |
| created_by | BIGINT UNSIGNED | NOT NULL, FK → users.id | |
| updated_by | BIGINT UNSIGNED | NOT NULL, FK → users.id | |
| created_at, updated_at | TIMESTAMP | nullable | |
| deleted_at | TIMESTAMP | nullable | Soft delete; solo permitido si `estado = 'borrador'` |

**Índices**:
- PK(id)
- INDEX(`fecha_inicio`) — para filtros por rango
- INDEX(`driver_id`) — para filtros por conductor / placa
- INDEX(`route_id`) — para filtros por ruta
- INDEX(`vehicle_plate`) — para filtros directos por placa
- INDEX(`estado`) — para excluir anuladas en consolidado
- COMPUESTO(`fecha_inicio`, `estado`, `deleted_at`) — listado paginado eficiente

**Reglas (en modelo / form requests)**:
- `fecha_fin >= fecha_inicio` (validation)
- `anticipo >= 0`, `sobreanticipo >= 0`, `valor_flete >= 0` (validation)
- Recalcular todos los `sumatoria_*`, `saldo_viaje`, `ganancia_viaje`, `a_favor_de` en cada `save()` que toque líneas o columnas base.
- `motivo_anulacion` requerido sólo al transicionar a `anulada`.
- Soft delete bloqueado si `estado != 'borrador'` (en policy).

---

## Tabla 5: `liquidacion_expenses`

Una fila por categoría con valor capturado (o 0 si no aplica — opcional dejar la fila o no escribirla; ver "Reglas").

| Columna | Tipo | Constraints | Notas |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| liquidacion_id | BIGINT UNSIGNED | NOT NULL, FK → liquidaciones.id, ON DELETE CASCADE | |
| expense_category_id | BIGINT UNSIGNED | NOT NULL, FK → expense_categories.id, ON DELETE RESTRICT | |
| valor | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | COP |
| galones | DECIMAL(8,2) | nullable | Solo para ACPM cuando aplica |
| created_at, updated_at | TIMESTAMP | nullable | |

**Índices**:
- PK(id)
- UNIQUE(`liquidacion_id`, `expense_category_id`) — cada categoría aparece máximo 1 vez por liquidación
- INDEX(`expense_category_id`) — para reportes por categoría globales

**Reglas**:
- Si `valor = 0` y `galones IS NULL` se puede omitir la fila (no obligatorio crearla). El modelo `Liquidacion::expenses` debe ser robusto a categorías ausentes (= 0).
- `galones` solo válido cuando `expense_category.has_galones = 1`.

---

## Tabla 6: `liquidacion_tolls`

Peajes capturados por viaje. Snapshot del catálogo de la ruta al momento de crear, más posibles peajes ad-hoc.

| Columna | Tipo | Constraints | Notas |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| liquidacion_id | BIGINT UNSIGNED | NOT NULL, FK → liquidaciones.id, ON DELETE CASCADE | |
| route_toll_id | BIGINT UNSIGNED | nullable, FK → route_tolls.id, ON DELETE SET NULL | Trazabilidad al catálogo; NULL si fue ad-hoc o si el peaje del catálogo se borró luego |
| name | VARCHAR(100) | NOT NULL | Snapshot del nombre |
| valor | DECIMAL(12,0) | NOT NULL, DEFAULT 0 | COP, editable por el operador en la liquidación |
| sort_order | SMALLINT UNSIGNED | NOT NULL | |
| direction | ENUM('ida','regreso') | NOT NULL, DEFAULT 'ida' | |
| is_adhoc | TINYINT(1) | NOT NULL, DEFAULT 0 | `1` si lo agregó manualmente el operador para esa liquidación |
| is_used | TINYINT(1) | NOT NULL, DEFAULT 1 | `0` cuando el operador marca "no usado" (no se suma en `sumatoria_peajes`) |
| created_at, updated_at | TIMESTAMP | nullable | |

**Índices**:
- PK(id)
- INDEX(`liquidacion_id`, `sort_order`)

**Reglas**:
- `sumatoria_peajes` solo suma filas con `is_used = 1`.
- Si `route_toll_id` se borra del catálogo, conservar el snapshot (`name`, `valor`) y dejar el FK NULL.

---

## Tabla 7: `liquidacion_state_logs`

Bitácora de cada transición de estado (Cerrar / Reabrir / Anular).

| Columna | Tipo | Constraints | Notas |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| liquidacion_id | BIGINT UNSIGNED | NOT NULL, FK → liquidaciones.id, ON DELETE CASCADE | |
| user_id | BIGINT UNSIGNED | NOT NULL, FK → users.id | Quien ejecutó la transición |
| from_state | ENUM('borrador','cerrada','anulada') | NOT NULL | |
| to_state | ENUM('borrador','cerrada','anulada') | NOT NULL | |
| motivo | TEXT | nullable | Requerido al reabrir o anular (validación en service) |
| created_at | TIMESTAMP | NOT NULL | (sin `updated_at`; log inmutable) |

**Índices**:
- PK(id)
- INDEX(`liquidacion_id`, `created_at`) — historial cronológico
- INDEX(`user_id`)

---

## Reglas de integridad y transacciones

1. **Crear/Editar liquidación** se hace en una sola transacción que:
   - Inserta/actualiza la fila de `liquidaciones`.
   - Sincroniza filas de `liquidacion_expenses` (upsert por `expense_category_id`).
   - Sincroniza filas de `liquidacion_tolls` (delete + insert simple, las cantidades son pequeñas).
   - Recalcula los campos `sumatoria_*`, `saldo_viaje`, `ganancia_viaje`, `a_favor_de` y los persiste en `liquidaciones`.

2. **Transiciones de estado** (Close/Reopen/Cancel):
   - Validar transición permitida (R5 del research).
   - Validar motivo cuando aplica (Reopen, Cancel).
   - Update de `liquidaciones.estado` + insert en `liquidacion_state_logs` en la misma transacción.

3. **Soft delete** solo si `estado = 'borrador'`. La policy bloquea el resto.

4. **Eliminación de ruta**:
   - Si `routes.id` está referenciada por alguna `liquidaciones.route_id` → no permitir hard delete (la migración la define con `ON DELETE RESTRICT`).
   - El admin debe inactivar (`routes.active = 0`) en lugar de borrar.

## Estimaciones de volumen

- 50–200 liquidaciones/mes × ~16 expenses × ~20 tolls/liquidación
- Año 1: ~2.400 liquidaciones, ~38.000 expenses, ~48.000 tolls, ~5.000 state logs
- Crecimiento lineal. Tablas crecen <100 MB/año en disco. Sin necesidad de particionamiento.

## Campos derivados / formula reference (mirror del spec)

| Campo derivado | Fórmula |
|---|---|
| `sumatoria_gastos_operativos` | `SUM(liquidacion_expenses.valor)` |
| `sumatoria_peajes` | `SUM(liquidacion_tolls.valor WHERE is_used = 1)` |
| `sumatoria_gastos_totales` | `sumatoria_gastos_operativos + sumatoria_peajes` |
| `total_anticipos` | `anticipo + sobreanticipo` |
| `saldo_viaje` | `total_anticipos − sumatoria_gastos_operativos` |
| `ganancia_viaje` | `valor_flete − sumatoria_gastos_totales` |
| `a_favor_de` | `saldo_viaje > 0 → empresa`, `< 0 → conductor`, `= 0 → ninguno` |
