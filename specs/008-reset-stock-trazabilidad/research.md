# Research: Reinicio de Stock, Trazabilidad e Importaciones

**Feature**: `008-reset-stock-trazabilidad`
**Date**: 2026-09-22
**Phase**: 0 (Outline & Research)

Este documento resuelve las incógnitas técnicas de la especificación antes de diseñar. Cada entrada
registra la decisión, su justificación y las alternativas descartadas.

---

## Hallazgo estructural que condiciona todo el diseño

Antes de las decisiones, el hecho que reordena el alcance:

**El módulo de Importación y las existencias de inventario no comparten datos.**

- `imports` → `import_containers` es un expediente **documental**. Termina ahí: `import_containers`
  no tiene FK hacia `containers`.
- `containers` → `container_product` es el **saldo físico** del inventario. Se crea de forma
  independiente desde `ContainerController::store()` a partir de una referencia de texto libre que
  escribe el usuario (`app/Http/Controllers/ContainerController.php:63-125`).
- Emparejar la referencia de un contenedor documental con uno físico es una **convención manual, no
  forzada por el esquema**.

**Consecuencia**: borrar importaciones **no** vacía el stock, y vaciar el stock **no** afecta a las
importaciones. Son dos limpiezas separadas que deben ejecutarse de forma explícita. La
especificación ya refleja esto (User Story 1 y User Story 2 son independientes).

---

## R1. Cómo lograr un borrado recuperable del inventario

**Incógnita**: el solicitante pidió borrado suave (recuperable). ¿Cómo se implementa sobre tablas
que hoy no lo soportan?

**Estado actual**: sólo `Import` y `Liquidacion` usan `SoftDeletes`
(`app/Models/Import.php:13`). Las tablas de inventario —`containers`, `container_product`,
`transfer_orders`, `transfer_order_products`, `salidas`, `salida_products`— **no tienen
`deleted_at`**.

**Volumen de código que habría que tocar para retrofitear `deleted_at`**:

| Controlador | `DB::table(...)` | `->join(...)` |
|---|---|---|
| `StockController` | 5 | 4 |
| `SalidaController` | 17 | 17 |
| `TransferOrderController` | 11 | 5 |
| `TraceabilityController` | 0 | 0 |
| **Total** | **33** | **26** |

Las consultas crudas con `DB::table()` **no aplican el scope global de Eloquent**, así que cada uno
de esos 33 puntos necesitaría un `whereNull('deleted_at')` añadido a mano. Además, `StockController`
y `TraceabilityController` tienen su lógica **duplicada** entre `index()` y
`getStockData()` / `getTraceabilityData()`, lo que duplica los puntos de fallo. Un solo `whereNull`
olvidado produce stock fantasma silencioso: el peor modo de fallo posible para este módulo.

**Decisión**: **archivar y luego borrar.** El comando copia las filas afectadas a tablas de archivo
(`archived_containers`, `archived_container_product`, `archived_transfer_orders`,
`archived_transfer_order_products`, `archived_salidas`, `archived_salida_products`), cada una con la
estructura de la original más `archived_at` y `archive_batch_id`; después elimina las filas vivas.

**Rationale**:

- Cumple la propiedad de negocio que se pidió: la información **sigue en la base de datos y es
  recuperable** (FR-006), no se destruye.
- **Cero cambios en las rutas de lectura**: ninguna de las 33 consultas crudas ni de las 26 uniones
  necesita modificarse. Esto elimina de raíz el riesgo de stock fantasma.
- La recuperación es un `INSERT ... SELECT` desde la tabla de archivo, acotado por
  `archive_batch_id`.
- El archivo es auditable: queda constancia de qué se limpió, cuándo y en qué lote.

**Alternativas descartadas**:

- *Retrofit de `deleted_at` en las seis tablas*: descartado por los 33 puntos de consulta cruda y la
  lógica duplicada. Alto riesgo de saldos incorrectos silenciosos, que es exactamente lo que este
  trabajo busca eliminar.
- *Volcado SQL a `storage/app/backups/`*: recuperable, pero fuera de la base de datos. Más frágil
  (depende de que el archivo sobreviva a despliegues) y más difícil de auditar o de restaurar
  parcialmente.
- *Marca de corte por fecha ("ocultar todo lo anterior a X")*: requiere igualmente tocar las 33
  consultas y además deja lógica condicional permanente en el código.

**Nota de transparencia**: el mecanismo difiere del `deleted_at` nativo que el solicitante
probablemente tenía en mente, pero preserva exactamente la propiedad que eligió (recuperable, no
destruido). Queda señalado como decisión explícita para su validación.

---

## R2. Cómo lograr un borrado recuperable de las importaciones

**Decisión**: usar el `SoftDeletes` **nativo que ya existe** — `Import::query()->delete()`.

**Rationale**:

- `imports.deleted_at` ya existe
  (`database/migrations/2026_07_15_000000_add_soft_deletes_to_imports_table.php`) y el modelo ya usa
  el trait.
- Todas las consultas del módulo pasan por Eloquent, así que el scope global las excluye
  automáticamente de listados, reportes e informes (FR-011, FR-013) sin tocar ni una consulta.
- Es exactamente el mecanismo que el commit `b225793` introdujo para este propósito.

**Consecuencia importante**: aquí sí se obtiene `deleted_at` literal. El inventario usa archivo
(R1). Son dos mecanismos distintos para la misma propiedad de negocio, cada uno elegido por el coste
y el riesgo de su módulo.

---

## R3. Continuidad del consecutivo del DO tras el vaciado

**Incógnita**: "el consecutivo del DO será a partir del último que quede" — ¿cómo se garantiza si no
queda ninguna importación visible?

**Estado actual** (`app/Http/Controllers/ImportController.php:316-347`):

```
$lastImport = Import::withTrashed()               // <- cuenta las eliminadas
    ->whereRaw('SUBSTRING(do_code, 4, 2) = ?', [$year])
    ->orderByDesc('do_code')->first();
$next = $lastImport ? intval($m[1]) + 1 : 1;
$floor = Import::DO_CODE_FLOOR[$year] ?? 0;       // ['26' => 55]
if ($next < $floor) { $next = $floor; }
$doCode = sprintf('VJP%s-%03d', $year, $next);
```

**Decisión**: **no cambiar nada de esta lógica.** El borrado suave de R2 la satisface tal cual.

**Rationale**: `withTrashed()` sigue viendo las importaciones eliminadas, así que el "último
emitido" se deduce directamente de los registros conservados. El piso por año
(`Import::DO_CODE_FLOOR`, `app/Models/Import.php:24-26`) ya existe precisamente como red de
seguridad para continuar la numeración tras una limpieza de datos. FR-014, FR-015 y FR-016 se
cumplen sin escribir código nuevo.

**Verificación requerida**: una prueba automatizada que ejecute el vaciado y confirme que el
siguiente DO es N+1 y no 001. La lógica es correcta hoy; la prueba la protege de regresiones.

**Alternativa descartada**: introducir una tabla contador o una secuencia. Innecesario: duplicaría
la fuente de verdad y el mecanismo actual ya es monótono.

---

## R4. Asignación de contenedor en transferencias cuando el usuario no elige

**Incógnita**: si el contenedor es opcional, ¿de dónde se descuenta físicamente?

**Estado actual**: `'products.*.container_id' => 'required|exists:containers,id'`
(`app/Http/Controllers/TransferOrderController.php:155` en `store()` y `:551` en `update()`), más el
atributo `required` del `<select>` en `resources/views/transfer-orders/create.blade.php:561`. El
descuento físico ocurre en un único punto:
`DB::table('container_product')->...->decrement('boxes', ...)`
(`app/Http/Controllers/TransferOrderController.php:429`), y **sólo** cuando la bodega de origen
recibe contenedores.

**Tensión a resolver**: el usuario eligió que, sin contenedor seleccionado, la trazabilidad quede
**sin contenedor**. Pero el saldo físico vive en filas concretas de `container_product`: hay que
descontar de alguna.

**Decisión**: separar la **asignación física** del **registro del movimiento**.

- **Asignación física**: repartir la cantidad entre los contenedores de la bodega que tengan ese
  producto, en orden determinista **FIFO por contenedor** (`containers.id` ascendente, que es el
  orden de creación), consumiendo cada fila hasta agotar la cantidad solicitada.
- **Registro del movimiento**: guardar `container_id = NULL` en `transfer_order_products`, de modo
  que la Trazabilidad lo muestre sin contenedor (FR-024).

**Rationale**:

- Respeta literalmente lo que el solicitante eligió: el usuario no elige contenedor y la trazabilidad
  no muestra ninguno.
- El reparto FIFO es determinista y reproducible, a diferencia del comportamiento actual de Salidas
  (ver R5).
- Mantiene el saldo de `container_product` correcto, que es lo que sostiene el cálculo de stock de
  las bodegas que reciben contenedores.

**Cuidado con `sheets_per_box`**: el índice `unique(container_id, product_id)` de `container_product`
fue **eliminado** en
`database/migrations/2026_01_25_000000_update_container_and_transfer_structure.php:21`, así que un
mismo producto puede aparecer varias veces en un contenedor con distinto `sheets_per_box`. El
algoritmo de reparto debe iterar **por fila**, no por contenedor, y el `decrement` actual —que sin
filtro de `sheets_per_box` afectaría a varias filas a la vez— no puede reutilizarse sin adaptarlo.

**Alternativas descartadas**:

- *Asignar automáticamente y mostrar ese contenedor en la trazabilidad*: era la opción B que el
  solicitante **no** eligió.
- *Crear un contenedor sintético "sin contenedor"*: contamina el catálogo de contenedores y rompe
  los listados de contenedores por bodega.

---

## R5. Salidas: por qué no necesitan reparto

**Hallazgo**: **las salidas nunca descuentan nada.** `SalidaController::store()` sólo inserta en
`salida_products` (`app/Http/Controllers/SalidaController.php:369`); el stock se recalcula restando
esas filas. Hay un comentario que confirma la intención en `SalidaController.php:356-357`.

**Decisión**: para salidas, cuando no se elige contenedor, **guardar `container_id = NULL` y no
repartir nada**. Validar la cantidad contra el total disponible del producto en la bodega.

**Rationale**: no hay saldo físico que mover, así que el reparto FIFO de R4 no aplica. Es
estrictamente más simple.

**Defecto existente que esto corrige**: hoy, cuando no se elige contenedor,
`app/Http/Controllers/SalidaController.php:217-230` toma el contenedor de **la primera fila que
devuelva la consulta**, y esa consulta **no tiene `ORDER BY`**: el resultado depende del orden
arbitrario de MySQL. El mismo patrón está en `SalidaController.php:270-273` para bodegas sin
contenedores. Registrar `NULL` explícitamente elimina esta no-determinación en lugar de sustituirla
por otra regla implícita.

**Nota de UI**: `resources/views/salidas/create.blade.php:590` muestra la etiqueta `Contenedor*` con
asterisco, pero el `<select>` **no** lleva atributo `required` — el asterisco es puramente
cosmético y debe retirarse para no confundir (FR-019).

---

## R6. Dónde vive la operación de limpieza

**Decisión**: un comando de consola de Laravel, ejecutado manualmente una sola vez. **Sin ninguna
ruta, botón ni opción en la interfaz.**

**Rationale**: el solicitante eligió explícitamente "una sola vez (puesta en marcha)" (FR-009). No
exponerlo en la aplicación elimina la posibilidad de que alguien vacíe la operación por error. Un
comando de consola exige acceso al servidor, que es la barrera adecuada para una acción de este
calibre.

**Por qué no se reutiliza `db:clean`**: el comando existente
(`app/Console/Commands/CleanDatabase.php:49-62`) **no sirve** para este trabajo:

1. Hace `truncate()`, es decir, borrado **irreversible** — su propio mensaje avisa "Esta acción NO se
   puede deshacer". Incompatible con FR-006.
2. Borra también `products`, `drivers` y `conductors`, que **FR-004 exige conservar**.
3. No toca `imports`, que es justo lo que FR-011 pide vaciar.

Se crea un comando nuevo y acotado. `db:clean` se deja intacto.

**Idempotencia** (FR-010): el comando debe terminar sin error sobre un sistema ya vacío, y no debe
alterar el consecutivo del DO al re-ejecutarse.

---

## R7. Consistencia entre la pantalla de Stock y sus exportaciones

**Hallazgo**: `StockController::index()` y `StockController::getStockData()` (usada por las
exportaciones) **han divergido**. `getStockData()` resta las transferencias en tránsito
(`app/Http/Controllers/StockController.php:703-729`); `index()` no lo hace — sólo resta salidas
(`:166-191`). La misma consulta puede dar cifras distintas en pantalla y en el PDF.

**Decisión**: unificar ambas rutas sobre el cálculo canónico ya existente,
`StockController::calcularStockPorBodega()` (`app/Http/Controllers/StockController.php:1856`).

**Rationale**: FR-025 exige consistencia explícita entre pantalla y exportaciones. Con los módulos
vacíos la divergencia es invisible, pero reaparecería en cuanto se cargue la primera operación —
justo cuando la confianza en las cifras es más crítica.

**Alcance**: es un arreglo de un defecto preexistente que FR-025 convierte en requisito. Se señala
como tal en el plan para que la decisión de incluirlo o diferirlo sea consciente.

---

## R8. Concurrencia en la numeración del DO

**Hallazgo**: el cálculo del siguiente DO (R3) **no tiene bloqueo ni reintento**. `imports.do_code`
tiene índice único (`database/migrations/2026_01_05_000002_add_do_code_to_imports_table.php`), así
que dos `store()` simultáneos calculan el mismo `$next` y el segundo falla con una excepción de
integridad que llega al usuario como error genérico.

**Decisión**: envolver el cálculo y la inserción en una transacción con reintento acotado ante
colisión de clave única.

**Rationale**: FR-017 lo exige. El arreglo es contenido —un punto del código— y convierte un error
crudo en un reintento transparente.

**Señalado como endurecimiento, no preservación**: esto **mejora** el comportamiento actual en lugar
de conservarlo. Es candidato razonable a diferirse si se prefiere acotar el alcance; se deja
explícito en el plan para que sea una decisión y no un descuido.

**Defecto relacionado, fuera de alcance**: el año del DO se deduce de `arrival_date` mientras el
filtro alternativo usa `created_at`, así que una importación cuyo año de llegada difiera del de
creación cae en otro grupo de numeración. Se documenta, no se corrige aquí.

---

## R9. Estrategia de pruebas

**Decisión**: pruebas de características (feature tests) con PHPUnit sobre MySQL.

**Contexto verificado**: `phpunit.xml` fija `DB_CONNECTION=mysql` y
`DB_DATABASE=easy_inventory_test`. Las pruebas de características de este repositorio **requieren
MySQL**, no SQLite en memoria. Las pruebas `Auth`/`Example` de Breeze fallan de forma preexistente y
no deben tomarse como regresión.

**Cobertura mínima**:

| Área | Qué se prueba |
|---|---|
| Limpieza de inventario | Stock y Trazabilidad quedan vacíos; productos/bodegas/conductores/usuarios intactos |
| Recuperabilidad | Las filas archivadas existen y permiten restaurar el lote |
| Idempotencia | Segunda ejecución sin error y sin alterar el DO |
| Consecutivo DO | Tras el vaciado, el siguiente DO es N+1 y nunca 001 |
| Contenedor opcional | Transferencia y salida se guardan sin contenedor |
| Reparto FIFO | El descuento reparte correctamente entre varios contenedores |
| Validación | Cantidad mayor al disponible se rechaza y no descuenta nada |
| Alcance por rol | Cliente y funcionario no ven datos residuales |

---

## Contexto técnico confirmado

| Aspecto | Valor |
|---|---|
| Framework | Laravel 8.75 |
| PHP | 8.2.12 (repositorio declara `^7.4 \|\| ^8.0`) |
| Vistas | Blade + jQuery/JS inline — **sin Inertia ni Vue** |
| PDF | `barryvdh/laravel-dompdf` ^2.2 |
| "Excel" | Tablas HTML servidas como `application/vnd.ms-excel` |
| Pruebas | PHPUnit ^9.5 sobre MySQL (`easy_inventory_test`) |
| Capa de servicios | Sólo `LiquidacionCalculator` y `LiquidacionStateMachine` — **no hay servicio de inventario**; la lógica vive en los controladores |

---

## Incógnitas resueltas

Todas las marcas `NEEDS CLARIFICATION` del contexto técnico quedan resueltas:

- Mecanismo de borrado recuperable del inventario → R1 (archivo + borrado)
- Mecanismo de borrado recuperable de importaciones → R2 (`SoftDeletes` nativo)
- Continuidad del consecutivo del DO → R3 (sin cambios; ya funciona)
- Origen del descuento sin contenedor → R4 (reparto FIFO, movimiento sin contenedor)
- Comportamiento de salidas → R5 (sin reparto; registrar `NULL`)
- Ubicación de la limpieza → R6 (comando de consola, sin interfaz)
- Estrategia de pruebas → R9 (PHPUnit sobre MySQL)
