# Data Model: Reinicio de Stock, Trazabilidad e Importaciones

**Feature**: `008-reset-stock-trazabilidad`
**Date**: 2026-09-22
**Phase**: 1 (Design & Contracts)

---

## 1. Estado actual relevante

### Las dos mitades desconectadas

```
LADO DOCUMENTAL (Importación)          LADO FÍSICO (Inventario)
─────────────────────────────          ────────────────────────────────
imports  (SoftDeletes)                 warehouses
  └─< import_containers                  └─< containers
        · reference (texto libre)              └─< container_product  ← SALDO REAL
        ·                                            · boxes
        ·   ✗ SIN FK ENTRE LAS DOS MITADES ✗        · sheets_per_box
        ·                                            · weight_per_box
  └─  itrs (import_id, nullOnDelete)
```

El emparejamiento entre `import_containers.reference` y `containers.reference` es una **convención
manual escrita a mano por el usuario**, no una relación del esquema. Por eso las dos limpiezas son
independientes.

### Fuentes de las que se derivan Stock y Trazabilidad

Ni Stock ni Trazabilidad tienen tabla propia. Ambos se calculan en tiempo de consulta a partir de
exactamente tres fuentes:

| Fuente | Aporta | Tabla de detalle |
|---|---|---|
| Contenedores | Entradas (`boxes × sheets_per_box`) | `container_product` |
| Transferencias | Salida en origen; entrada en destino si `status = 'recibido'` | `transfer_order_products` |
| Salidas | Salidas | `salida_products` |

Dos tablas son callejones sin salida y **no** deben confundirse con el saldo:

- `products.stock` — vestigial. Se escribe una sola vez con `0` fijo
  (`app/Http/Controllers/ProductController.php:102`). Nunca se incrementa ni decrementa.
- `product_warehouse_stock` — tabla vacía de relleno: la migración sólo crea `id` + timestamps, sin
  `product_id`, sin `warehouse_id` y sin cantidad. Cero código la referencia.

**Implicación para FR-001/FR-002**: vaciar Stock y Trazabilidad significa vaciar esas tres fuentes.
No hay ningún saldo agregado que reiniciar aparte.

---

## 2. Entidades nuevas

### 2.1 Tablas de archivo (recuperabilidad — FR-006)

Seis tablas nuevas, una por cada tabla de inventario que se limpia. Cada una replica la estructura
de su tabla original y añade dos columnas de control:

| Columna añadida | Tipo | Propósito |
|---|---|---|
| `archived_at` | `timestamp` | Cuándo se archivó la fila |
| `archive_batch_id` | `char(36)`, indexado | Agrupa todas las filas de una misma ejecución, para restaurar el lote completo |

Tablas:

```
archived_containers                 ← containers
archived_container_product          ← container_product
archived_transfer_orders            ← transfer_orders
archived_transfer_order_products    ← transfer_order_products
archived_salidas                    ← salidas
archived_salida_products            ← salida_products
```

**Reglas de diseño**:

- **Sin claves foráneas.** Las tablas de archivo son un depósito inerte; las FK obligarían a que los
  registros vivos que ya no existen sigan existiendo, que es justo lo contrario del objetivo.
- **Se conserva el `id` original** como columna ordinaria (no autoincremental), para que la
  restauración reponga las mismas relaciones.
- **Sin modelos Eloquent.** Se acceden sólo desde el comando de limpieza y desde consultas manuales
  de auditoría. Añadir modelos invitaría a que el código de producción leyera de ellas.
- **No se archiva `products`, `warehouses`, `drivers`, `conductors` ni `users`**: FR-004 exige
  conservarlos vivos, así que no se tocan en absoluto.

### 2.2 Registro de ejecución de la limpieza

Una tabla `inventory_reset_logs` que documenta cada ejecución:

| Columna | Tipo | Propósito |
|---|---|---|
| `id` | `bigint` PK | — |
| `batch_id` | `char(36)`, único | Corresponde con `archive_batch_id` de las tablas de archivo |
| `executed_at` | `timestamp` | Cuándo se ejecutó |
| `executed_by` | `varchar(255)`, nullable | Quién la lanzó (usuario del sistema operativo o identificador manual) |
| `scope` | `varchar(50)` | `inventory`, `imports` o `all` |
| `counts` | `json` | Filas archivadas/eliminadas por tabla |
| `notes` | `text`, nullable | Observaciones |

**Rationale**: convierte una operación destructiva e irrepetible en un hecho auditable. Sin esto, la
única evidencia de lo ocurrido serían las filas archivadas, sin contexto de cuándo ni por qué.

---

## 3. Cambios sobre entidades existentes

### 3.1 `transfer_order_products.container_id` → opcional

| Aspecto | Antes | Después |
|---|---|---|
| Columna en BD | `container_id` ya es **nullable** con `nullOnDelete` | Sin cambio — **no hace falta migración** |
| Validación `store()` | `required\|exists:containers,id` (`TransferOrderController.php:155`) | `nullable\|exists:containers,id` |
| Validación `update()` | `required\|exists:containers,id` (`TransferOrderController.php:551`) | `nullable\|exists:containers,id` |
| `<select>` en la vista | atributo `required` (`transfer-orders/create.blade.php:561`) | sin `required`, con opción "Sin contenedor específico" |

**La columna ya admite `NULL`.** El bloqueo es puramente de validación y de interfaz. No se requiere
cambio de esquema para FR-018.

### 3.2 `salida_products.container_id` → se mantiene opcional, pero se registra `NULL` de verdad

| Aspecto | Antes | Después |
|---|---|---|
| Validación | ya es `nullable` (`SalidaController.php:141` y `:654`) | sin cambio |
| Valor guardado si el usuario no elige | contenedor **arbitrario** tomado de la primera fila devuelta por una consulta **sin `ORDER BY`** (`SalidaController.php:217-230` y `:270-273`) | `NULL` explícito |
| Etiqueta en la vista | `Contenedor*` con asterisco engañoso (`salidas/create.blade.php:590`) | `Contenedor (opcional)` |

**Este es el cambio de comportamiento con mayor efecto visible**: los movimientos de salida sin
contenedor dejarán de mostrar un contenedor elegido al azar y pasarán a mostrarse vacíos.

### 3.3 `imports` → sin cambios de esquema

`deleted_at` ya existe y el modelo ya usa `SoftDeletes`. El vaciado es
`Import::query()->delete()`. La numeración del DO sigue funcionando sin tocar nada gracias a
`withTrashed()` (ver `research.md` R3).

---

## 4. Reglas de validación

Derivadas de los requisitos funcionales:

| Regla | Requisito | Dónde aplica |
|---|---|---|
| Sin contenedor elegido, la cantidad se valida contra el **total disponible del producto en la bodega** | FR-020, FR-022 | Transferencias y salidas |
| Con contenedor elegido, la cantidad se valida contra **ese contenedor** | FR-021 | Transferencias y salidas |
| Cantidad superior a la disponible → rechazo con mensaje que indica el disponible, **sin descuento parcial** | FR-022, SC-010 | Transferencias y salidas |
| Producto sin existencias → no seleccionable | Edge case | Transferencias y salidas |
| La limpieza conserva productos, bodegas, conductores, usuarios y sus asignaciones | FR-004 | Comando |
| La limpieza conserva Liquidaciones e ITR | FR-005 | Comando |
| Re-ejecutar la limpieza sobre un sistema vacío no produce error ni altera el DO | FR-010 | Comando |
| Un DO emitido nunca se reasigna | FR-015 | `ImportController::store()` |
| Dos importaciones simultáneas no reciben el mismo DO | FR-017 | `ImportController::store()` |

---

## 5. Algoritmo de reparto FIFO (transferencias sin contenedor)

Aplica **sólo** a transferencias cuya bodega de origen recibe contenedores. Las salidas no descuentan
nada (`research.md` R5) y las bodegas que no reciben contenedores derivan su saldo, así que ninguna
de las dos entra aquí.

```
ENTRADA: producto P, bodega origen B, cantidad Q (en cajas)

1. Reunir las filas de container_product de los contenedores de B que tengan P,
   ordenadas por containers.id ASC  (FIFO = orden de creación).
   → Iterar POR FILA, no por contenedor: un mismo producto puede aparecer
     varias veces en un contenedor con distinto sheets_per_box, porque el índice
     unique(container_id, product_id) fue eliminado.

2. Si SUMA(boxes) < Q  →  rechazar, sin descontar nada (FR-022).

3. restante = Q
   Para cada fila en orden FIFO mientras restante > 0:
       tomar = MIN(fila.boxes, restante)
       fila.boxes -= tomar
       restante   -= tomar

4. Registrar el movimiento con container_id = NULL (FR-024).
```

**Puntos críticos**:

- Los pasos 2 y 3 van dentro de **una sola transacción**: la validación y el descuento no pueden
  separarse, o dos transferencias concurrentes dejarían saldos negativos.
- El `decrement('boxes', ...)` actual de `TransferOrderController.php:429` **no se puede reutilizar
  tal cual**: sin filtro de `sheets_per_box` afecta a varias filas a la vez. El reparto necesita
  actualizar fila por fila.
- El saldo nunca debe quedar negativo. El paso 2 lo garantiza dentro de la transacción.

---

## 6. Qué se borra y qué se conserva

| Tabla | Acción | Requisito |
|---|---|---|
| `container_product` | Archivar → borrar | FR-003 |
| `containers` | Archivar → borrar | FR-003 |
| `transfer_order_products` | Archivar → borrar | FR-003 |
| `transfer_orders` | Archivar → borrar | FR-003 |
| `salida_products` | Archivar → borrar | FR-003 |
| `salidas` | Archivar → borrar | FR-003 |
| `imports` | Borrado suave (`deleted_at`) | FR-011, FR-012 |
| `import_containers` | **Se conserva**: cuelga de `imports`, que sólo se borra en suave | FR-012 |
| `products` | **Intacta** | FR-004 |
| `warehouses` | **Intacta** | FR-004 |
| `drivers`, `conductors` | **Intactas** | FR-004 |
| `users`, `user_warehouse`, `user_driver` | **Intactas** | FR-004 |
| `itrs`, `itr_date_histories` | **Intactas** | FR-005 |
| `liquidacion*`, `monthly_expenses`, `expense_categories`, `routes`, `route_tolls` | **Intactas** | FR-005 |

**Orden de borrado** (respetando las claves foráneas, de hija a madre):

```
salida_products → salidas
transfer_order_products → transfer_orders
container_product → containers
```

Se archiva cada tabla **antes** de borrarla. Todo dentro de una transacción: si algo falla, no queda
un estado a medio limpiar.

**Nota sobre transferencias en tránsito**: quedan eliminadas junto con el resto. No pueden
sobrevivir transferencias `en_transito` apuntando a contenedores que ya no existen (edge case de la
especificación).

---

## 7. Transiciones de estado

Este trabajo **no introduce ni modifica ninguna máquina de estados**. Los estados de transferencia
(`en_transito` → `recibido`) y el flujo de confirmación de recepción se conservan tal cual, incluidos
los movimientos registrados sin contenedor (FR-026).
