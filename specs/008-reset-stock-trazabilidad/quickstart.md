# Quickstart: Reinicio de Stock, Trazabilidad e Importaciones

**Feature**: `008-reset-stock-trazabilidad`
**Fecha**: 2026-09-22

Cómo ejecutar y verificar este trabajo. Pensado para quien implementa y para quien valida el
resultado en el servidor.

---

## Entorno

| Aspecto | Valor |
|---|---|
| Raíz del proyecto | `C:\xampp\htdocs\easy_inventory` |
| Base de datos de desarrollo | MySQL/MariaDB bajo XAMPP |
| Base de datos de pruebas | `easy_inventory_test` (fijada en `phpunit.xml`) |
| PHP | 8.2.12 |
| Framework | Laravel 8.75 |

**Importante**: las pruebas de características de este repositorio **requieren MySQL**. No funcionan
sobre SQLite en memoria. Las pruebas `Auth` y `Example` de Breeze fallan de forma preexistente —
no son una regresión de este trabajo.

---

## Preparar

```powershell
# Dependencias
composer install

# Base de datos de pruebas (si aún no existe)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS easy_inventory_test"

# Migraciones nuevas
php artisan migrate
```

---

## Ejecutar la limpieza

### Paso 1 — Respaldo completo (no omitir)

El borrado es recuperable, pero el archivo protege de un error lógico, **no** de un fallo de disco ni
de una migración mal aplicada.

```powershell
mysqldump -u root easy_inventory > C:\xampp\backups\easy_inventory_pre_reset.sql
```

### Paso 2 — Ensayo sin efectos

```powershell
php artisan inventory:reset --dry-run
```

Informa qué se borraría, por tabla, **sin escribir nada**. Conviene revisar que los recuentos de
`products`, `warehouses`, `drivers` y `users` aparezcan como conservados.

### Paso 3 — Ejecutar

```powershell
php artisan inventory:reset --scope=all --by="andres"
```

Anotar el `batch_id` que imprime: es lo que permite deshacer la operación.

### Ámbitos por separado

```powershell
php artisan inventory:reset --scope=inventory   # sólo stock y trazabilidad
php artisan inventory:reset --scope=imports     # sólo importaciones
```

### Deshacer

```powershell
php artisan inventory:reset-restore 9f2c1a84-3e77-4b10-9d52-6c0ab1e4f7d2 --dry-run
php artisan inventory:reset-restore 9f2c1a84-3e77-4b10-9d52-6c0ab1e4f7d2
```

La restauración se rechaza si las tablas vivas ya tienen datos nuevos, salvo que se pase `--force`
(reponer sobre datos nuevos duplicaría identificadores).

---

## Verificar el resultado

### En la aplicación

| # | Qué hacer | Qué se espera |
|---|---|---|
| 1 | Abrir **Stock** como admin, sin filtros | Ninguna existencia; estado vacío legible, no un error |
| 2 | Abrir **Stock** eligiendo cada bodega | Vacío en todas |
| 3 | Abrir **Trazabilidad** sin filtros | Ningún movimiento |
| 4 | Abrir **Importación** | Ninguna importación listada |
| 5 | Abrir **Productos**, **Bodegas**, **Conductores**, **Usuarios** | Todos con sus registros intactos |
| 6 | Entrar como **cliente** y como **funcionario** | Stock y Trazabilidad también vacíos, sin datos residuales |
| 7 | Exportar Stock y Trazabilidad a PDF y a Excel | Se generan sin error, sin filas |
| 8 | Abrir **Liquidaciones** e **ITR** | Intactos |

### El consecutivo del DO

La verificación más importante de la Fase B:

1. Antes de la limpieza, anotar el DO más alto del año (por ejemplo `VJP26-147`).
2. Ejecutar la limpieza.
3. Crear una importación nueva.
4. **Debe recibir `VJP26-148`**, nunca `VJP26-001`.

```powershell
# Comprobar qué número se emitirá, contando las eliminadas en suave
php artisan tinker
>>> App\Models\Import::withTrashed()->orderByDesc('do_code')->first()->do_code;
```

### Contenedor opcional

| # | Qué hacer | Qué se espera |
|---|---|---|
| 1 | Crear una **transferencia** sin elegir contenedor | Se guarda sin error de validación |
| 2 | Crear una **salida** sin elegir contenedor | Se guarda sin error |
| 3 | Consultar Trazabilidad | Ambos movimientos aparecen; la columna de contenedor se ve vacía con claridad |
| 4 | Crear una transferencia **eligiendo** contenedor | La trazabilidad muestra ese contenedor |
| 5 | Producto repartido en varios contenedores: sacar más de lo que tiene uno solo, pero menos que el total | Se permite y descuenta correctamente |
| 6 | Intentar sacar más que el total disponible | Se rechaza con un mensaje que indica el disponible, **y el stock no cambia** |
| 7 | Confirmar la recepción de una transferencia sin contenedor | La existencia queda disponible en la bodega destino |

**Sobre el punto 6**: verificar explícitamente que no hubo descuento parcial. Un reparto FIFO mal
implementado puede consumir los primeros contenedores antes de detectar que falta cantidad.

### Idempotencia

```powershell
php artisan inventory:reset --scope=all --force
php artisan inventory:reset --scope=all --force   # segunda vez
```

La segunda ejecución debe terminar con éxito, sin error y **sin alterar el consecutivo del DO**.

---

## Pruebas automatizadas

```powershell
# Todo el conjunto de esta funcionalidad
php artisan test --filter="InventoryReset|DoCodeConsecutive|ContainerOptional"

# Una por una
php artisan test --filter=InventoryResetTest
php artisan test --filter=InventoryResetRestoreTest
php artisan test --filter=DoCodeConsecutiveTest
php artisan test --filter=ContainerOptionalTest
```

Si `migrate:fresh` falla en la base de pruebas, revisar las migraciones con índices huérfanos: ya
hubo dos arreglos de ese tipo en este repositorio.

---

## Comprobaciones en la base de datos

```sql
-- Las fuentes de stock y trazabilidad deben estar vacías
SELECT 'containers' t, COUNT(*) n FROM containers
UNION ALL SELECT 'container_product', COUNT(*) FROM container_product
UNION ALL SELECT 'transfer_orders', COUNT(*) FROM transfer_orders
UNION ALL SELECT 'transfer_order_products', COUNT(*) FROM transfer_order_products
UNION ALL SELECT 'salidas', COUNT(*) FROM salidas
UNION ALL SELECT 'salida_products', COUNT(*) FROM salida_products;

-- Los datos maestros deben seguir ahí
SELECT 'products' t, COUNT(*) n FROM products
UNION ALL SELECT 'warehouses', COUNT(*) FROM warehouses
UNION ALL SELECT 'drivers', COUNT(*) FROM drivers
UNION ALL SELECT 'users', COUNT(*) FROM users;

-- Las importaciones: cero visibles, pero conservadas
SELECT COUNT(*) AS visibles      FROM imports WHERE deleted_at IS NULL;
SELECT COUNT(*) AS conservadas   FROM imports WHERE deleted_at IS NOT NULL;

-- El archivo debe contener lo borrado
SELECT archive_batch_id, COUNT(*) FROM archived_containers GROUP BY archive_batch_id;

-- Historial de ejecuciones
SELECT batch_id, executed_at, executed_by, scope FROM inventory_reset_logs ORDER BY executed_at DESC;
```

---

## Desviaciones encontradas durante la implementación

Lo que el plan supuso y lo que resultó ser cierto al implementarlo.

### 1. El doble descuento iba al revés de lo previsto

`plan.md` (Riesgos #2) daba por hecho que la **pantalla** de Stock estaba mal por no restar las
transferencias en tránsito, y que la **exportación** estaba bien por restarlas.

Es al contrario. En las bodegas que reciben contenedores, el saldo base sale de
`container_product.boxes`, y ese saldo **ya se descuenta al crear la transferencia**. Restar además
la transferencia la contaba **dos veces**.

Comprobado con una prueba: 10 cajas, transferencia de 3 → el saldo real es 7 cajas (70 láminas),
pero el cálculo canónico devolvía 40 láminas.

Se corrigió en dos sitios:

- `StockController::calcularStockPorBodega()` — ahora sólo resta las transferencias salientes en las
  bodegas que **no** reciben contenedores, exactamente la misma condición que gobierna el descuento
  en `TransferOrderController`.
- `StockController::getStockData()` — se retiró la resta de tránsitos del total unificado.

**Efecto visible**: el stock de las bodegas de Buenaventura / Pablo Rojas que tuvieran
transferencias salientes aparecía **por debajo del real**. Tras la corrección sube al valor correcto.
Con los módulos vacíos no se nota; reaparecería con la primera transferencia.

### 2. Trazabilidad no divergía

El plan preveía unificar `TraceabilityController::index()` y `getTraceabilityData()` (T055). Al
compararlos, la lógica es **idéntica**: sólo cambian los comentarios. No se refactorizó nada; se
añadió una prueba que fija el comportamiento para detectar una divergencia futura.

### 3. Había que reponer el saldo de los movimientos sin contenedor

No estaba en el plan. Al editar o anular una transferencia, el código repone las cajas del
contenedor, pero el `if` exigía que hubiera `container_id`. Con contenedor opcional, una
transferencia sin contenedor **no habría repuesto nada** y el saldo se habría perdido o descontado
dos veces.

Se añadió `ContainerAllocator::restoreToWarehouse()`, que repone el total sobre la primera fila FIFO
del producto (sin filtrar por `boxes > 0`, porque la fila de la que se descontó puede haber quedado
justo en cero). El total de la bodega vuelve a ser exacto; el reparto entre contenedores puede
diferir del original, algo irrelevante en un movimiento que por definición no tiene contenedor.

### 4. Deriva de esquema entre producción y las migraciones

`transfer_orders.driver_id` es **NOT NULL** en el esquema que generan las migraciones, pero
**nullable** en producción. No se tocó; se documenta porque obligó a ajustar los fixtures de prueba.

### 5. MariaDB local no arrancaba

Ajeno a la funcionalidad, pero bloqueaba toda la verificación: el servidor moría al cargar las
tablas de privilegios. La causa era `mysql.proxies_priv` corrupta (su índice `.MAI` había crecido a
2,1 MB frente a los 24 KB normales). Se restauró desde `C:\xampp\mysql\backup`. Los archivos
corruptos quedaron como `proxies_priv.*.corrupt` y las tablas de sistema respaldadas en
`C:\xampp\mysql\_backups_claude\`.

---

## Archivos de referencia

Puntos del código que este trabajo toca, para orientarse rápido:

| Qué | Dónde |
|---|---|
| Consecutivo del DO | `app/Http/Controllers/ImportController.php:316-347` |
| Piso de numeración por año | `app/Models/Import.php:24-26` |
| Contenedor obligatorio en transferencias | `app/Http/Controllers/TransferOrderController.php:155` y `:551` |
| `<select>` con `required` | `resources/views/transfer-orders/create.blade.php:561` |
| Descuento físico del contenedor | `app/Http/Controllers/TransferOrderController.php:429` |
| Elección arbitraria en salidas | `app/Http/Controllers/SalidaController.php:217-230` y `:270-273` |
| Etiqueta engañosa `Contenedor*` | `resources/views/salidas/create.blade.php:590` |
| Cálculo canónico de stock | `app/Http/Controllers/StockController.php:1856` |
| Divergencia pantalla/exportación | `app/Http/Controllers/StockController.php:166-191` vs `:703-729` |
| Comando antiguo que **no** sirve | `app/Console/Commands/CleanDatabase.php:49-62` |
