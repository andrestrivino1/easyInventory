# Contrato: Comando de limpieza de arranque

**Feature**: `008-reset-stock-trazabilidad`
**Tipo de interfaz**: comando de consola (Artisan). **No se expone ninguna ruta HTTP** (FR-009).

---

## `inventory:reset`

Vacía el inventario y/o las importaciones dejando la información recuperable.

### Firma

```
php artisan inventory:reset {--scope=all} {--force} {--dry-run} {--by=}
```

### Opciones

| Opción | Valores | Por defecto | Significado |
|---|---|---|---|
| `--scope` | `inventory`, `imports`, `all` | `all` | Qué se limpia. `inventory` = stock + trazabilidad; `imports` = módulo de Importación |
| `--force` | bandera | ausente | Omite la confirmación interactiva. Requerido en entornos no interactivos |
| `--dry-run` | bandera | ausente | Informa qué se borraría **sin borrar nada** |
| `--by` | texto | `null` | Identificador de quién ejecuta, guardado en el registro de ejecución |

### Comportamiento

1. **Confirmación**: sin `--force`, pide confirmación explícita indicando cuántas filas se verán
   afectadas por tabla.
2. **Registro de lote**: genera un `batch_id` (UUID) y crea la fila en `inventory_reset_logs`.
3. **Ámbito `inventory`** — dentro de una única transacción:
   - Archiva y borra, en este orden: `salida_products` → `salidas` →
     `transfer_order_products` → `transfer_orders` → `container_product` → `containers`.
   - **No toca** `products`, `warehouses`, `drivers`, `conductors`, `users` ni sus tablas de
     asignación (FR-004).
4. **Ámbito `imports`**:
   - `Import::query()->delete()` (borrado suave). `import_containers` se conserva.
   - **No altera** la lógica del consecutivo del DO: `withTrashed()` sigue viendo las eliminadas.
5. **Resumen**: imprime las filas archivadas y borradas por tabla, y el `batch_id`.

### Garantías

| Garantía | Requisito |
|---|---|
| Toda fila borrada existe previamente en su tabla de archivo | FR-006 |
| Re-ejecutar sobre un sistema vacío termina con éxito y sin efectos | FR-010 |
| Re-ejecutar no altera el consecutivo del DO | FR-010, edge case |
| Un fallo a mitad deja la base de datos sin cambios (transacción) | — |
| `--dry-run` no escribe absolutamente nada | — |

### Códigos de salida

| Código | Significado |
|---|---|
| `0` | Éxito, o cancelado por el usuario en la confirmación |
| `1` | Fallo: la transacción se revirtió y no se borró nada |

### Salida de ejemplo

```
$ php artisan inventory:reset --scope=all --by="andres"

  Lote: 9f2c1a84-3e77-4b10-9d52-6c0ab1e4f7d2

  Inventario
    salida_products            1.284 filas  → archivadas → borradas
    salidas                      312 filas  → archivadas → borradas
    transfer_order_products      897 filas  → archivadas → borradas
    transfer_orders              241 filas  → archivadas → borradas
    container_product            563 filas  → archivadas → borradas
    containers                    88 filas  → archivadas → borradas

  Importación
    imports                      147 filas  → borrado suave

  Conservados intactos: products (412), warehouses (9), drivers (23), users (31)

  Consecutivo DO: el siguiente número será VJP26-148
```

La última línea es deliberada: confirma en pantalla que la numeración continúa (FR-014) en lugar de
dejar que se descubra al crear la siguiente importación.

---

## `inventory:reset-restore`

Deshace una ejecución concreta reponiendo las filas archivadas. Es la contrapartida que hace real la
promesa de recuperabilidad.

### Firma

```
php artisan inventory:reset-restore {batch_id} {--force} {--dry-run}
```

### Comportamiento

1. Verifica que el `batch_id` existe en `inventory_reset_logs`.
2. **Rechaza la restauración si las tablas vivas no están vacías**, salvo que se pase `--force`:
   reponer sobre datos nuevos produciría identificadores duplicados y saldos incoherentes.
3. Repone en orden inverso (de madre a hija): `containers` → `container_product` →
   `transfer_orders` → `transfer_order_products` → `salidas` → `salida_products`.
4. Para el ámbito `imports`, ejecuta `restore()` sobre las importaciones borradas en suave.

### Garantías

| Garantía |
|---|
| Los identificadores originales se reponen tal cual, preservando las relaciones |
| Todo ocurre en una transacción |
| `--dry-run` no escribe nada |

---

## Contratos de formulario modificados

Estos no son endpoints nuevos: son cambios en las reglas de validación de rutas ya existentes.

### `POST /transfer-orders` y `PUT /transfer-orders/{id}`

| Campo | Antes | Después |
|---|---|---|
| `products.*.container_id` | `required\|exists:containers,id` | `nullable\|exists:containers,id` |

**Reglas de negocio**:

- `container_id` presente → validar disponibilidad contra ese contenedor y descontar de él
  (FR-021).
- `container_id` ausente o `null` → validar contra el total del producto en la bodega, repartir en
  FIFO entre contenedores y guardar `container_id = NULL` en el pivote (FR-020, FR-024).
- Cantidad superior a la disponible → `422` / redirección con error indicando la cantidad
  disponible, **sin descuento parcial** (FR-022).

### `POST /salidas` y `PUT /salidas/{id}`

| Campo | Antes | Después |
|---|---|---|
| `products.*.container_id` | `nullable\|exists:containers,id` (sin cambio) | `nullable\|exists:containers,id` |

**Cambio de comportamiento, no de contrato**: cuando el campo llega vacío, el sistema debe guardar
`NULL` en lugar del contenedor arbitrario que hoy toma de una consulta sin `ORDER BY`
(`SalidaController.php:217-230`).

### Respuestas sobre módulos vacíos

| Ruta | Con los módulos vacíos |
|---|---|
| `GET /stock` | `200` con estado vacío legible (FR-007) |
| `GET /traceability` | `200` con estado vacío legible (FR-007) |
| `GET /imports` | `200` sin filas (FR-011) |
| Exportaciones PDF/Excel de las tres | `200` con documento válido y sin filas (FR-008) |
