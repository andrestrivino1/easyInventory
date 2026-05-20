# HTTP Contracts — Liquidación de Viajes

**Branch**: `002-liquidacion-viajes` | **Date**: 2026-05-19

Contratos de las rutas Laravel del módulo. Todas requieren `auth` + `can:liquidaciones.access` (solo `admin`).

Base path: `/liquidaciones` (el módulo Rutas vive anidado lógicamente; usa `/liquidaciones/rutas` para no chocar con `routes/` del filesystem).

---

## 1. Liquidaciones — Listado + Consolidado

### GET `/liquidaciones`

Lista paginada de liquidaciones + panel de consolidado del conjunto filtrado.

**Query params** (todos opcionales, todos combinables):

| Param | Tipo | Notas |
|---|---|---|
| `fecha_desde` | date (YYYY-MM-DD) | Filtra `fecha_inicio >= fecha_desde` |
| `fecha_hasta` | date (YYYY-MM-DD) | Filtra `fecha_inicio <= fecha_hasta` |
| `placa` | string | LIKE %placa% sobre `vehicle_plate` |
| `driver_id` | int | FK exacto |
| `route_id` | int | FK exacto |
| `transportadora` | string | LIKE %transportadora% |
| `estado` | enum `borrador|cerrada|anulada|all` | Default `all` (incluye anuladas con marca); en el consolidado siempre se excluyen anuladas |
| `agrupar_por_mes` | boolean | Si `true`, devuelve consolidado desglosado por mes |
| `page` | int | Paginación estándar Laravel |
| `per_page` | int | Default 25, max 100 |
| `incluir_eliminadas` | boolean | Default `false`; si `true`, incluye soft-deleted (solo accesible a admin) |

**Response 200** (vista Blade renderizada):

- Listado paginado de liquidaciones con columnas: estado, placa, ruta (origen→destino), conductor, fecha inicio, fecha fin, transportadora, total gastos operativos, total peajes, saldo, ganancia, A favor de, acciones (Ver / Editar / PDF / Cerrar / Reabrir / Anular según estado).
- Panel "Consolidado del periodo" con: # viajes, Σ gastos operativos, Σ peajes, Σ gastos totales, Σ anticipos, Σ flete, Σ saldo, Σ ganancia, promedio ganancia/viaje, margen % del periodo.
- Si `agrupar_por_mes=true`: tabla adicional con un sub-panel por mes.

**Validaciones**:
- `fecha_desde <= fecha_hasta` si ambas presentes.
- `per_page <= 100`.

---

## 2. Liquidaciones — CRUD

### GET `/liquidaciones/create`

Form de nueva liquidación. Renderiza:
- Select de conductor (drivers activos), select de ruta (rutas activas), 16 filas de la tabla de gastos pre-renderizadas, tabla de peajes vacía (se llena al elegir ruta vía JS).

### POST `/liquidaciones`

Crea una nueva liquidación en estado `borrador`.

**Request body** (form-encoded o JSON):

```json
{
  "driver_id": 7,
  "vehicle_plate": "QJZ957",
  "route_id": 3,
  "transportadora": "PEREIRANA DE TRANSPO",
  "telefono_empresa": "+57 6 312 0000",
  "anticipo": 3000000,
  "sobreanticipo": 0,
  "fecha_inicio": "2026-03-02",
  "fecha_fin": "2026-03-05",
  "numero_mfto": "MFTO-12345",
  "valor_flete": 5600000,
  "expenses": [
    { "category_code": "acpm", "valor": 1344636, "galones": 120 },
    { "category_code": "urea", "valor": 134900 },
    { "category_code": "comision", "valor": 100000 },
    { "category_code": "porcentaje", "valor": 560000 },
    { "category_code": "parqueaderos", "valor": 11000 },
    { "category_code": "lavada_del_carro", "valor": 70000 },
    { "category_code": "embolada_de_llantas", "valor": 120000 },
    { "category_code": "varios", "valor": 30000 }
  ],
  "tolls": [
    { "name": "Loboguerrero", "valor": 43300, "sort_order": 1, "direction": "ida", "route_toll_id": 11, "is_used": true, "is_adhoc": false },
    { "name": "Betania",      "valor": 56000, "sort_order": 2, "direction": "ida", "route_toll_id": 12 },
    "..."
  ]
}
```

**Validaciones**:
- `driver_id` exists en `drivers`, con `active = 1`.
- `vehicle_plate` requerido (default desde driver).
- `route_id` nullable; si presente exists en `routes` con `active = 1`.
- `transportadora` requerido string ≤150.
- `anticipo`, `sobreanticipo`, `valor_flete` enteros ≥ 0.
- `fecha_inicio` <= `fecha_fin`.
- `expenses[*].category_code` exists en `expense_categories.code`.
- `expenses[*].galones` solo permitido cuando `category.has_galones = 1`.
- `tolls[*].direction` ∈ {`ida`,`regreso`}.

**Response 201** (web): redirect a `show` con flash success.
**Response 422** (validation): vuelve al formulario con errores + preserva input.
**Response 409** (warning duplicado FR-013): para versión AJAX, devolver JSON `{ "warning": "duplicate", "existing_id": N }` y dejar al usuario confirmar — implementado como check pre-submit que muestra modal de confirmación, no como rechazo del POST.

### GET `/liquidaciones/{id}`

Vista detalle (read-only siempre — si está en Borrador, también muestra botón "Editar"). Incluye:
- Cabecera con datos del viaje, conductor, ruta.
- Tabla de gastos con valores.
- Tabla de peajes con valores.
- Totales calculados (snapshot).
- Si `estado = 'anulada'`: marca de agua visual "ANULADA" + motivo + log de auditoría.
- Botones según estado:
  - `borrador`: Editar, Cerrar, Eliminar, PDF.
  - `cerrada`: Reabrir, Anular, PDF.
  - `anulada`: PDF (con marca anulada).

### GET `/liquidaciones/{id}/edit`

Form de edición. **Solo accesible si `estado = 'borrador'`**. Policy `update` falsea si estado distinto.

### PUT/PATCH `/liquidaciones/{id}`

Actualiza una liquidación en `borrador`. Mismo payload que POST. Mismas validaciones. Reescribe líneas (delete + insert) y recalcula totales.

**Response 200**: redirect a `show`.
**Response 403**: si liquidación no está en `borrador`.

### DELETE `/liquidaciones/{id}`

Soft delete. **Solo si `estado = 'borrador'`**.

**Response 200**: redirect al listado con flash.
**Response 403**: si liquidación está `cerrada` o `anulada`.

---

## 3. Liquidaciones — Transiciones de estado

### POST `/liquidaciones/{id}/cerrar`

Pasa de `borrador` → `cerrada`. No requiere motivo.

**Response 200**: redirect a `show` con flash.
**Response 422**: si estado no es `borrador`.

### POST `/liquidaciones/{id}/reabrir`

Pasa de `cerrada` → `borrador`. **Requiere motivo**.

**Request body**:
```json
{ "motivo": "Corrección de valor de peaje" }
```

**Validaciones**: `motivo` requerido, string ≥10 ≤500 chars.
**Response 200**: redirect.
**Response 422**: si estado no es `cerrada` o motivo inválido.

Registra fila en `liquidacion_state_logs`.

### POST `/liquidaciones/{id}/anular`

Pasa de `cerrada` → `anulada` (estado terminal). **Requiere motivo**.

**Request body**:
```json
{ "motivo": "Conductor reportó error en MFTO duplicado" }
```

**Validaciones**: `motivo` requerido, string ≥10 ≤500 chars.
**Response 200**: redirect.
**Response 422**: si estado no es `cerrada` o motivo inválido.

Registra fila en `liquidacion_state_logs` + actualiza `liquidaciones.motivo_anulacion`.

---

## 4. Liquidaciones — PDF

### GET `/liquidaciones/{id}/pdf`

Genera y descarga el PDF de la liquidación.

**Response 200**:
- Content-Type: `application/pdf`
- Content-Disposition: `attachment; filename="liquidacion_{vehicle_plate}_{fecha_inicio}.pdf"`
- Body: bytes del PDF generado por `barryvdh/laravel-dompdf` renderizando `resources/views/liquidaciones/pdf.blade.php`.
- Si `estado = 'anulada'`: imprime marca de agua "ANULADA" diagonal en el cuerpo.

**Query params**: ninguno.

---

## 5. Rutas — CRUD

### GET `/liquidaciones/rutas`

Lista de rutas. Filtros: `active` (default true), `q` (búsqueda por origen/destino).

### GET `/liquidaciones/rutas/create`

Form de nueva ruta + sección "Peajes" con tabla dinámica vacía.

### POST `/liquidaciones/rutas`

Crea ruta + peajes en una sola transacción.

**Request body**:
```json
{
  "origen": "BUENAVENTURA",
  "destino": "BOGOTÁ",
  "descripcion": "Ruta principal carga marítima",
  "active": true,
  "tolls": [
    { "name": "Loboguerrero", "suggested_value": 43300, "sort_order": 1,  "direction": "ida" },
    { "name": "Betania",      "suggested_value": 56000, "sort_order": 2,  "direction": "ida" },
    "...",
    { "name": "Loboguerrero", "suggested_value": 43300, "sort_order": 18, "direction": "regreso" }
  ]
}
```

**Validaciones**:
- `origen`, `destino` requeridos, string ≤100.
- `tolls[*].name` requerido string ≤100.
- `tolls[*].suggested_value` entero ≥0.
- `tolls[*].direction` ∈ {`ida`,`regreso`}.
- `tolls[*].sort_order` único entre los peajes de la ruta.

**Response 201**: redirect a `show` o al listado.

### GET `/liquidaciones/rutas/{id}`

Vista detalle con peajes asociados.

### GET `/liquidaciones/rutas/{id}/edit`

Form de edición de ruta + peajes (CRUD de peajes inline).

### PUT/PATCH `/liquidaciones/rutas/{id}`

Actualiza ruta + sincroniza peajes (delete + recreate por simplicidad; cantidades pequeñas).

**Response 200**: redirect.

### DELETE `/liquidaciones/rutas/{id}`

Hard delete **solo si no hay liquidaciones que referencien esta ruta** (FK ON DELETE RESTRICT). En caso contrario, devuelve 422 con instrucción de inactivar.

### POST `/liquidaciones/rutas/{id}/toggle-active`

Cambia el flag `active` de la ruta. Útil cuando no se puede borrar.

---

## 6. Helpers / endpoints AJAX

### GET `/liquidaciones/rutas/{id}/peajes`

Devuelve JSON con la lista de peajes catálogo de una ruta. Usado por el form de liquidación cuando el operador selecciona ruta.

**Response 200**:
```json
{
  "tolls": [
    { "id": 11, "name": "Loboguerrero", "suggested_value": 43300, "sort_order": 1, "direction": "ida" },
    "..."
  ]
}
```

### GET `/liquidaciones/drivers/{id}/info`

Devuelve datos relevantes del conductor para auto-llenar el formulario (placa, propietario, capacidad).

**Response 200**:
```json
{
  "id": 7,
  "name": "JUAN PEREZ",
  "vehicle_plate": "QJZ957",
  "vehicle_owner": "JUAN PEREZ",
  "phone": "+57 300 0000000"
}
```

### GET `/liquidaciones/duplicate-check`

Helper para FR-013. Verifica si existe liquidación no-anulada con misma placa+manifiesto.

**Query**: `?placa=QJZ957&numero_mfto=MFTO-12345&except_id=N`
**Response**:
```json
{ "duplicate": true,  "existing_id": 42, "existing_fecha_inicio": "2026-03-02" }
```
o
```json
{ "duplicate": false }
```

---

## Resumen de endpoints

| Método | Path | Acción | Estado requerido |
|---|---|---|---|
| GET | `/liquidaciones` | Listado + consolidado | — |
| GET | `/liquidaciones/create` | Form nueva | — |
| POST | `/liquidaciones` | Crear (= `borrador`) | — |
| GET | `/liquidaciones/{id}` | Detalle | cualquiera |
| GET | `/liquidaciones/{id}/edit` | Form edición | `borrador` |
| PUT | `/liquidaciones/{id}` | Actualizar | `borrador` |
| DELETE | `/liquidaciones/{id}` | Soft delete | `borrador` |
| POST | `/liquidaciones/{id}/cerrar` | Borrador → Cerrada | `borrador` |
| POST | `/liquidaciones/{id}/reabrir` | Cerrada → Borrador | `cerrada` |
| POST | `/liquidaciones/{id}/anular` | Cerrada → Anulada | `cerrada` |
| GET | `/liquidaciones/{id}/pdf` | PDF download | cualquiera |
| GET | `/liquidaciones/rutas` | Listar rutas | — |
| GET | `/liquidaciones/rutas/create` | Form nueva ruta | — |
| POST | `/liquidaciones/rutas` | Crear ruta + peajes | — |
| GET | `/liquidaciones/rutas/{id}` | Detalle ruta | — |
| GET | `/liquidaciones/rutas/{id}/edit` | Form edición ruta | — |
| PUT | `/liquidaciones/rutas/{id}` | Actualizar ruta + peajes | — |
| DELETE | `/liquidaciones/rutas/{id}` | Eliminar ruta | sin liquidaciones |
| POST | `/liquidaciones/rutas/{id}/toggle-active` | Activar/inactivar | — |
| GET | `/liquidaciones/rutas/{id}/peajes` | JSON peajes (AJAX) | — |
| GET | `/liquidaciones/drivers/{id}/info` | JSON driver (AJAX) | — |
| GET | `/liquidaciones/duplicate-check` | JSON dup check (AJAX) | — |
