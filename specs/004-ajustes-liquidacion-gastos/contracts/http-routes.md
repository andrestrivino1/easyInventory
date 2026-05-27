# HTTP Routes — Ajustes de liquidación y gastos mensuales

**Branch**: `004-ajustes-liquidacion-gastos` | **Date**: 2026-05-27

Todas las rutas viven bajo el grupo existente con prefijo `liquidaciones` y nombre `liquidaciones.`, middleware base `['auth', 'can:liquidaciones.access']` (definido en `routes/web.php`). Los endpoints nuevos se añaden dentro de ese grupo; los de **gastos mensuales** suman el gate admin-only.

## Convenciones

- Auth de sesión (Laravel UI). CSRF en formularios POST/PUT/DELETE.
- Respuestas: redirect + flash en flujos web; JSON solo en endpoints AJAX existentes (reusados).
- Montos COP enteros.

---

## Gastos mensuales (NUEVO) — admin-only

Grupo anidado con middleware adicional `can:liquidaciones.gastos.access` (`rol === 'admin'`). Recurso con parámetro `gasto`.

| Método | URI | Nombre | Controller@método | Descripción |
|---|---|---|---|---|
| GET | `/liquidaciones/gastos` | `liquidaciones.gastos.index` | `MonthlyExpenseController@index` | Lista paginada; filtros `?placa=&anio=&mes=` |
| GET | `/liquidaciones/gastos/create` | `liquidaciones.gastos.create` | `MonthlyExpenseController@create` | Formulario nuevo |
| POST | `/liquidaciones/gastos` | `liquidaciones.gastos.store` | `MonthlyExpenseController@store` | Crear |
| GET | `/liquidaciones/gastos/{gasto}/edit` | `liquidaciones.gastos.edit` | `MonthlyExpenseController@edit` | Formulario edición |
| PUT | `/liquidaciones/gastos/{gasto}` | `liquidaciones.gastos.update` | `MonthlyExpenseController@update` | Actualizar |
| DELETE | `/liquidaciones/gastos/{gasto}` | `liquidaciones.gastos.destroy` | `MonthlyExpenseController@destroy` | Eliminar |

> No se exponen `show` (la lista + edit cubren el caso).

### `index` — filtros y paginación (FR-005, FR-006)

Query params (todos opcionales):
- `placa` (string) → filtra por `vehicle_plate` exacta.
- `anio` (int), `mes` (int 1–12) → filtra por período.
- Paginación estándar (`?page=`), tamaño 25 por página.

Respuesta: vista `liquidaciones.gastos.index` con `$gastos` (LengthAwarePaginator), `$placas` (distinct para el select de filtro), valores de filtro actuales.

### `store` / `update` — payload (FR-002, FR-003, FR-007, FR-007b)

```text
driver_id          int    required, exists:drivers,id
anio               int    required, 2020..2100
mes                int    required, 1..12
sueldo_conductor   int    required, >=0   (default 0)
seguridad_social   int    required, >=0
cuota_banco        int    required, >=0
cuota_tercero      int    required, >=0
satelital          int    required, >=0
seguro_vehiculo    int    required, >=0
otro_valor         int    required, >=0
otro_descripcion   string nullable, max:150
```

Reglas:
- `vehicle_plate` NO viene del cliente → se setea server-side desde `Driver::find(driver_id)->vehicle_plate`.
- `created_by`/`updated_by` desde `auth()->id()`.
- Unicidad: `Rule::unique('monthly_expenses')->where(driver_id,anio,mes)` (en update, `->ignore($gasto->id)`). Violación → 422 / error de validación "Ya existe un gasto para ese conductor y período".

**Errores**:
- 403 si el usuario no es admin (gate `liquidaciones.gastos.access`).
- 422 validación (incl. duplicado conductor+período).

---

## Manifiesto PDF de la liquidación (NUEVO)

Adjunto 1:1 a una liquidación. Respeta el aislamiento de `placas` (la `LiquidacionPolicy` ya limita la liquidación a los conductores asignados).

| Método | URI | Nombre | Controller@método | Descripción |
|---|---|---|---|---|
| GET | `/liquidaciones/{liquidacion}/manifiesto` | `liquidaciones.manifiesto` | `LiquidacionController@manifiesto` | Ver/descargar el PDF (stream autenticado) |
| DELETE | `/liquidaciones/{liquidacion}/manifiesto` | `liquidaciones.manifiesto.destroy` | `LiquidacionController@destroyManifiesto` | Eliminar el manifiesto cargado |

> La **subida** del manifiesto NO tiene endpoint propio: viaja en el `multipart/form-data` de `store`/`update` de la liquidación (campo `manifiesto_pdf`).

### Subida (en `store`/`update` de liquidación) — FR-019, FR-021

Campo adicional en el form de liquidación:
```text
manifiesto_pdf   file   nullable, mimetypes:application/pdf, max:10240  (10 MB)
```
- Si se envía: guardar en `storage/app/manifiestos/`, borrar el archivo anterior si existía, setear `manifiesto_pdf_path`.
- Validación de tipo/tamaño en `StoreLiquidacionRequest`/update; rechazo con mensaje claro (FR-021).
- Solo editable en estado `borrador` (FR-022).

### `manifiesto` (GET) — FR-020
- Autorizado por `LiquidacionPolicy@view` (scoping `placas`).
- 404 si `manifiesto_pdf_path` es null o el archivo no existe.
- Respuesta: `Storage::download($path, 'manifiesto-{id}.pdf')` o stream inline `Content-Type: application/pdf`.

### `destroyManifiesto` (DELETE)
- Autorizado por `LiquidacionPolicy@update` + estado `borrador`.
- Borra archivo y setea `manifiesto_pdf_path = null`.

---

## Anticipos / descuentos (MOD a endpoints existentes) — FR-010..FR-015

No hay endpoints nuevos. Cambian los **payloads** de `liquidaciones.store` y `liquidaciones.update`:

```text
# Antes
anticipo           int
sobreanticipo      int

# Después
anticipo_empresa   int   required, >=0
anticipo_conductor int   required, >=0
descuentos         int   required, >=0   (default 0)
```
- `saldo_pendiente`, `total_anticipos`, etc. son derivados — NO se reciben del cliente; se recalculan server-side.
- `_form.blade.php` renombra los inputs; `liquidacion-form.js` actualiza el estado Alpine y muestra `saldo_pendiente` y la línea de descuentos en el panel de totales.

---

## Eliminar peaje del viaje (MOD frontend) — FR-016..FR-018

No hay endpoint nuevo. El array de peajes ya se envía completo en `store`/`update`; la fila eliminada en el cliente simplemente no se incluye y el sync full-replace de `liquidacion_tolls` la elimina. **Verificar** que `update` reconstruye los peajes desde el payload (no merge incremental).

---

## Resumen

| Área | Endpoints nuevos | Endpoints modificados |
|---|---|---|
| Gastos mensuales | 6 (index/create/store/edit/update/destroy) | — |
| Manifiesto PDF | 2 (ver, eliminar) + subida embebida en store/update | store/update |
| Anticipos/descuentos | 0 | store/update (payload) |
| Eliminar peaje | 0 | store/update (verificación) |
