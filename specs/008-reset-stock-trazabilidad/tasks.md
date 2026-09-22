---

description: "Task list for feature implementation"
---

# Tasks: Reinicio de Stock, Trazabilidad e Importaciones con Descuento Independiente de Contenedor

**Input**: Design documents from `specs/008-reset-stock-trazabilidad/`
**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/cli-commands.md](contracts/cli-commands.md)

**Tests**: SÍ se incluyen. `research.md` R9 define una tabla de cobertura mínima obligatoria y la
especificación fija criterios de éxito medibles (SC-001 a SC-010) que sólo son verificables con
pruebas automatizadas.

**Organization**: Las tareas se agrupan por historia de usuario para poder implementarlas y
probarlas de forma independiente.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Se puede ejecutar en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: A qué historia pertenece (US1, US2, US3)
- Cada tarea incluye la ruta exacta del archivo

## Path Conventions

Monolito Laravel 8 en la raíz del repositorio: `app/`, `database/migrations/`, `resources/views/`,
`tests/Feature/`. Vistas en Blade — **sin Inertia ni Vue**.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Preparar el entorno y asegurar una base de comparación fiable antes de tocar datos.

- [X] T001 Verificar que la base de pruebas existe y que `phpunit.xml` apunta a `easy_inventory_test`, ejecutando `php artisan test --filter=NoSuchTest` para confirmar que el arranque conecta contra MySQL
- [X] T002 [P] Generar respaldo completo previo con `mysqldump -u root easy_inventory > C:\xampp\backups\easy_inventory_pre_reset.sql` y verificar que el archivo no está vacío
- [X] T003 [P] Registrar el estado base de la suite ejecutando `php artisan test` y anotando qué pruebas ya fallan (las de Breeze `Auth`/`Example` fallan de forma preexistente y NO deben contarse como regresión)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Esquema de archivo y de auditoría. Sin esto no se puede borrar nada de forma recuperable.

**⚠️ CRITICAL**: Bloquea US1 y US2. US3 no depende de esta fase y puede avanzar en paralelo.

- [X] T004 Crear migración `database/migrations/2026_09_22_000001_create_inventory_archive_tables.php` con las seis tablas de archivo (`archived_containers`, `archived_container_product`, `archived_transfer_orders`, `archived_transfer_order_products`, `archived_salidas`, `archived_salida_products`), cada una replicando las columnas de su tabla origen más `archived_at` (timestamp) y `archive_batch_id` (char 36, indexado), **sin claves foráneas** y conservando el `id` original como columna ordinaria no autoincremental, con guardas `Schema::hasTable()` como el resto de migraciones del repositorio
- [X] T005 [P] Crear migración `database/migrations/2026_09_22_000002_create_inventory_reset_logs.php` con la tabla `inventory_reset_logs` (`id`, `batch_id` char 36 único, `executed_at`, `executed_by` nullable, `scope`, `counts` json, `notes` nullable), con guarda `Schema::hasTable()`
- [X] T006 Ejecutar `php artisan migrate` y verificar en MySQL que las siete tablas nuevas existen con las columnas esperadas (depende de T004, T005)

**Checkpoint**: Esquema de archivo listo — US1 y US2 pueden comenzar.

---

## Phase 3: User Story 1 - Stock y Trazabilidad arrancan vacíos (Priority: P1) 🎯 MVP

**Goal**: Dejar Stock y Trazabilidad completamente vacíos, de forma recuperable, conservando intactos productos, bodegas, conductores, usuarios, Liquidaciones e ITR.

**Independent Test**: Ejecutar la limpieza y comprobar que Stock y Trazabilidad no devuelven ninguna fila para ninguna bodega ni rol, mientras Productos, Bodegas y Conductores conservan exactamente los mismos registros que antes.

### Tests for User Story 1 ⚠️

> **NOTA: Escribir estas pruebas PRIMERO y confirmar que FALLAN antes de implementar.**

- [X] T007 [P] [US1] Crear `tests/Feature/InventoryResetTest.php` con casos: Stock queda vacío para todas las bodegas (SC-001); Trazabilidad queda vacía (SC-002); `products`, `warehouses`, `drivers`, `conductors`, `users` conservan su recuento exacto (SC-004, FR-004); `itrs` y `liquidaciones` intactas (FR-005)
- [X] T008 [P] [US1] Añadir a `tests/Feature/InventoryResetTest.php` el caso de idempotencia: segunda ejecución consecutiva termina con éxito, sin error y sin alterar el consecutivo del DO (FR-010)
- [X] T009 [P] [US1] Crear `tests/Feature/InventoryResetRestoreTest.php` que verifique que toda fila borrada existe en su tabla de archivo con el `archive_batch_id` correcto, y que `inventory:reset-restore` repone los registros con sus `id` originales (FR-006)
- [X] T010 [P] [US1] Añadir a `tests/Feature/InventoryResetTest.php` el caso de alcance por rol: usuarios con rol `cliente` y `funcionario` tampoco ven datos residuales en Stock ni Trazabilidad a través de sus filtros de bodega (SC-001, SC-002)

### Implementation for User Story 1

- [X] T011 [US1] Crear `app/Services/InventoryResetService.php` con el método de archivado y borrado del ámbito `inventory`: copiar a las tablas de archivo y borrar en orden de clave foránea (`salida_products` → `salidas` → `transfer_order_products` → `transfer_orders` → `container_product` → `containers`), todo dentro de una única transacción, devolviendo los recuentos por tabla
- [X] T012 [US1] Añadir a `app/Services/InventoryResetService.php` el método de restauración por `batch_id`, que repone en orden inverso (`containers` → `container_product` → `transfer_orders` → `transfer_order_products` → `salidas` → `salida_products`) y rechaza la operación si las tablas vivas no están vacías salvo que se fuerce
- [X] T013 [US1] Crear `app/Console/Commands/InventoryReset.php` con la firma `inventory:reset {--scope=all} {--force} {--dry-run} {--by=}` según [contracts/cli-commands.md](contracts/cli-commands.md), incluyendo confirmación interactiva, generación de `batch_id` UUID, escritura en `inventory_reset_logs` y resumen por tabla
- [X] T014 [US1] Crear `app/Console/Commands/InventoryResetRestore.php` con la firma `inventory:reset-restore {batch_id} {--force} {--dry-run}` que invoque el método de restauración de `InventoryResetService`
- [X] T015 [US1] Implementar el modo `--dry-run` en `app/Console/Commands/InventoryReset.php` de modo que informe los recuentos afectados sin escribir absolutamente nada en la base de datos
- [X] T016 [P] [US1] Añadir estado vacío legible en `resources/views/stock/index.blade.php` para que, sin existencias, se muestre un mensaje comprensible en lugar de una tabla desarmada o totales en blanco (FR-007)
- [X] T017 [P] [US1] Añadir estado vacío legible en `resources/views/traceability/index.blade.php` para cuando no haya movimientos (FR-007)
- [X] T018 [P] [US1] Verificar y corregir `resources/views/stock/pdf.blade.php` para que la exportación se genere sin error con cero filas (FR-008)
- [X] T019 [P] [US1] Verificar y corregir `resources/views/traceability/pdf.blade.php` para que la exportación se genere sin error con cero filas (FR-008)
- [X] T020 [US1] Verificar que las exportaciones a Excel de `app/Http/Controllers/StockController.php` y `app/Http/Controllers/TraceabilityController.php` responden correctamente sobre módulos vacíos (FR-008, SC-009)
- [X] T021 [US1] Ejecutar `php artisan test --filter="InventoryReset"` y confirmar que T007-T010 pasan

**Checkpoint**: Stock y Trazabilidad vacíos y recuperables, datos maestros intactos. **Entregable MVP.**

---

## Phase 4: User Story 2 - Vaciar Importación conservando el consecutivo del DO (Priority: P2)

**Goal**: Dejar el módulo de Importación sin importaciones visibles, garantizando que el siguiente DO continúa desde el último emitido y nunca reutiliza un número.

**Independent Test**: Ejecutar el vaciado, confirmar que el listado de Importación queda sin filas, y crear una importación nueva para verificar que su DO es exactamente el siguiente al mayor emitido antes del vaciado.

### Tests for User Story 2 ⚠️

- [X] T022 [P] [US2] Crear `tests/Feature/DoCodeConsecutiveTest.php` con el caso central: dado el mayor DO del año `VJP26-147`, tras ejecutar `inventory:reset --scope=imports` la siguiente importación recibe `VJP26-148` y **nunca** `VJP26-001` (SC-005, FR-014)
- [X] T023 [P] [US2] Añadir a `tests/Feature/DoCodeConsecutiveTest.php` el caso de no reutilización: ningún DO emitido previamente puede volver a asignarse aunque su importación esté eliminada (FR-015)
- [X] T024 [P] [US2] Añadir a `tests/Feature/DoCodeConsecutiveTest.php` el caso de exclusión: las importaciones eliminadas no aparecen en listados, reportes ni informes del módulo para ningún rol (FR-011, FR-013, SC-003)

### Implementation for User Story 2

- [X] T025 [US2] Añadir a `app/Services/InventoryResetService.php` el ámbito `imports`, que ejecuta `Import::query()->delete()` (borrado suave) sin tocar `import_containers` ni la lógica del consecutivo del DO (FR-012)
- [X] T026 [US2] Conectar el ámbito `imports` en `app/Console/Commands/InventoryReset.php` de modo que `--scope=imports` y `--scope=all` lo invoquen, y registrar sus recuentos en `inventory_reset_logs`
- [X] T027 [US2] Añadir al resumen de `app/Console/Commands/InventoryReset.php` la línea que imprime cuál será el siguiente número de DO, para confirmar en pantalla que la numeración continúa (FR-014)
- [X] T028 [US2] Añadir el ámbito `imports` a la restauración en `app/Services/InventoryResetService.php`, ejecutando `restore()` sobre las importaciones borradas en suave
- [X] T029 [US2] Envolver el cálculo del consecutivo y la inserción de `app/Http/Controllers/ImportController.php:316-386` en una transacción con reintento acotado ante colisión de clave única en `imports.do_code`, de modo que dos importaciones simultáneas no reciban el mismo número (FR-017, ver `research.md` R8)
- [X] T030 [P] [US2] Añadir a `tests/Feature/DoCodeConsecutiveTest.php` el caso de concurrencia: dos inserciones que calculan el mismo número no producen un error de integridad visible al usuario (FR-017)
- [X] T031 [US2] Ejecutar `php artisan test --filter=DoCodeConsecutive` y confirmar que T022-T024 y T030 pasan

**Checkpoint**: Importación vacía, numeración continua, protegida contra concurrencia.

---

## Phase 5: User Story 3 - Transferencias y salidas descuentan sin exigir contenedor (Priority: P2)

**Goal**: Hacer el contenedor opcional en transferencias, registrar `NULL` de verdad en salidas cuando el usuario no elige, y que ambos movimientos queden reflejados en Trazabilidad.

**Independent Test**: Registrar una transferencia y una salida sin seleccionar contenedor, verificando que ambas se guardan, que el stock disminuye en la cantidad correcta y que ambos movimientos aparecen en Trazabilidad.

**Nota**: Esta historia **no depende** de las fases 2, 3 ni 4. Puede desarrollarse en paralelo.

### Tests for User Story 3 ⚠️

- [X] T032 [P] [US3] Crear `tests/Feature/ContainerOptionalTest.php` con los casos de guardado: una transferencia y una salida se registran sin `container_id` y el stock del producto disminuye en la cantidad correcta (FR-018, FR-019, FR-020, SC-006)
- [X] T033 [P] [US3] Añadir a `tests/Feature/ContainerOptionalTest.php` el caso de reparto FIFO: un producto repartido en tres contenedores, al sacar una cantidad mayor que la de cualquiera individual pero menor que el total, se descuenta consumiendo los contenedores en orden `containers.id` ascendente (FR-020)
- [X] T034 [P] [US3] Añadir a `tests/Feature/ContainerOptionalTest.php` el caso de rechazo sin descuento parcial: una cantidad superior al total disponible se rechaza con mensaje que indica el disponible, y **ningún** `container_product.boxes` cambia (FR-022, SC-010)
- [X] T035 [P] [US3] Añadir a `tests/Feature/ContainerOptionalTest.php` los casos de trazabilidad: un movimiento sin contenedor aparece con `container_id` nulo y otro con contenedor elegido aparece asociado a él (FR-023, FR-024, SC-007)
- [X] T036 [P] [US3] Añadir a `tests/Feature/ContainerOptionalTest.php` el caso de `sheets_per_box`: un mismo producto presente dos veces en un contenedor con distinto `sheets_per_box` se reparte fila por fila sin descontar de más (ver `data-model.md` §5)

### Implementation for User Story 3

- [X] T037 [US3] Crear `app/Services/ContainerAllocator.php` que implemente el reparto FIFO de `data-model.md` §5: reunir las filas de `container_product` de la bodega ordenadas por `containers.id` ascendente, **iterando por fila y no por contenedor**, validar que la suma alcanza antes de descontar nada, y repartir dentro de una transacción
- [X] T038 [US3] Cambiar la validación de `app/Http/Controllers/TransferOrderController.php:155` de `required|exists:containers,id` a `nullable|exists:containers,id` en `store()`
- [X] T039 [US3] Cambiar la validación de `app/Http/Controllers/TransferOrderController.php:551` de `required|exists:containers,id` a `nullable|exists:containers,id` en `update()`
- [X] T040 [US3] Sustituir el descuento de `app/Http/Controllers/TransferOrderController.php:429` para que, cuando no haya `container_id`, invoque `ContainerAllocator` en lugar del `decrement('boxes', ...)` directo, y guarde `container_id = NULL` en `transfer_order_products` (FR-020, FR-024)
- [X] T041 [US3] Conservar en `app/Http/Controllers/TransferOrderController.php` la ruta con contenedor elegido: validar disponibilidad contra ese contenedor y descontar de él (FR-021)
- [X] T042 [US3] Añadir a `app/Http/Controllers/TransferOrderController.php` la validación contra el total disponible del producto en la bodega cuando no se elige contenedor, rechazando con mensaje que indique la cantidad disponible (FR-022)
- [X] T043 [US3] Sustituir la elección arbitraria de contenedor de `app/Http/Controllers/SalidaController.php:217-230` por `container_id = NULL` explícito, eliminando la dependencia del orden no determinista de MySQL (ver `research.md` R5)
- [X] T044 [US3] Sustituir el mismo patrón en `app/Http/Controllers/SalidaController.php:270-273` para bodegas que no reciben contenedores, registrando `NULL` explícito
- [X] T045 [US3] Ajustar la validación de cantidad de `app/Http/Controllers/SalidaController.php:311-327` para que, sin contenedor elegido, se valide contra el total disponible del producto en la bodega (FR-022)
- [X] T046 [P] [US3] Quitar el atributo `required` del `<select>` de contenedor en `resources/views/transfer-orders/create.blade.php:561` y añadir la opción "Sin contenedor específico"
- [X] T047 [P] [US3] Aplicar el mismo cambio al `<select>` de contenedor en `resources/views/transfer-orders/edit.blade.php`
- [X] T048 [P] [US3] Cambiar la etiqueta engañosa `Contenedor*` por `Contenedor (opcional)` en `resources/views/salidas/create.blade.php:590`, ya que el campo nunca fue obligatorio
- [X] T049 [P] [US3] Aplicar el mismo cambio de etiqueta en `resources/views/salidas/edit.blade.php`
- [X] T050 [US3] Mostrar "sin contenedor" de forma clara e inequívoca en `resources/views/traceability/index.blade.php` cuando el movimiento no tenga contenedor asociado, en lugar de una celda en blanco ambigua (FR-024)
- [X] T051 [P] [US3] Aplicar la misma presentación en `resources/views/traceability/pdf.blade.php`
- [X] T052 [US3] Verificar que la confirmación de recepción de `app/Http/Controllers/TransferOrderController.php:978` sigue funcionando con transferencias registradas sin contenedor (FR-026)
- [X] T053 [US3] Ejecutar `php artisan test --filter=ContainerOptional` y confirmar que T032-T036 pasan

**Checkpoint**: Transferencias y salidas operables sin elegir contenedor, reflejadas en Trazabilidad.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Consistencia de cifras y validación final.

> **T054-T056 corresponden a la Fase D del plan**: arreglan un defecto **preexistente** que FR-025
> convierte en requisito. Con los módulos vacíos la divergencia es invisible, pero reaparece con la
> primera operación real. Ver `plan.md` → Riesgos #2.

- [X] T054 Unificar `StockController::index()` (`app/Http/Controllers/StockController.php:166-191`) y `StockController::getStockData()` (`:703-729`) sobre el cálculo canónico `calcularStockPorBodega()` (`:1856`), eliminando la divergencia por la que las exportaciones restan transferencias en tránsito y la pantalla no (FR-025)
- [X] T055 Aplicar la misma unificación entre `TraceabilityController::index()` y `TraceabilityController::getTraceabilityData()` en `app/Http/Controllers/TraceabilityController.php` para evitar que pantalla y exportación diverjan — **sin cambios de código necesarios**: al compararlas resultaron lógicamente idénticas (sólo difieren los comentarios). Se añadió en su lugar una prueba en `tests/Feature/StockConsistencyTest.php` que detectaría una divergencia futura. Ver `quickstart.md` → Desviaciones #2
- [X] T056 [P] Crear `tests/Feature/StockConsistencyTest.php` que verifique que los totales de la pantalla de Stock y los de sus exportaciones coinciden exactamente para el mismo conjunto de datos (FR-025, SC-008)
- [X] T057 [P] Documentar en `specs/008-reset-stock-trazabilidad/quickstart.md` cualquier desviación encontrada durante la implementación respecto de lo planificado
- [X] T058 Ejecutar la validación manual completa de [quickstart.md](quickstart.md) sobre una copia de los datos de producción: los 8 puntos de "En la aplicación", los 4 del consecutivo del DO y los 7 de contenedor opcional
- [X] T059 Ejecutar `php artisan test` completo y confirmar que no hay regresiones respecto del estado base anotado en T003

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias — puede empezar de inmediato
- **Foundational (Phase 2)**: Depende de Setup — **bloquea US1 y US2**
- **US1 (Phase 3)**: Depende de Foundational
- **US2 (Phase 4)**: Depende de Foundational. Independiente de US1
- **US3 (Phase 5)**: **No depende de Foundational** — puede empezar justo después de Setup
- **Polish (Phase 6)**: Depende de que US1 y US3 estén completas

### Dependencia real entre historias

```
Setup (T001-T003)
   ├──> Foundational (T004-T006) ──┬──> US1 (T007-T021)  🎯 MVP
   │                               └──> US2 (T022-T031)
   └────────────────────────────────────> US3 (T032-T053)   [en paralelo desde el inicio]
                                                │
   US1 + US3 ───────────────────────────────────┴──> Polish (T054-T059)
```

**Observación**: US3 es la única historia que no toca el esquema de la base de datos —
`transfer_order_products.container_id` y `salida_products.container_id` **ya admiten `NULL`**. Es
puramente validación, lógica de reparto e interfaz, así que no necesita esperar a las migraciones.

### Within Each User Story

- Las pruebas se escriben PRIMERO y deben FALLAR antes de implementar
- Servicios antes que comandos y controladores
- Lógica antes que vistas
- La historia se cierra con su tarea de verificación (T021, T031, T053)

### Parallel Opportunities

- T002 y T003 en paralelo tras T001
- T004 y T005 en paralelo
- Todas las pruebas de una historia marcadas [P] en paralelo: T007-T010, T022-T024, T032-T036
- Las vistas de US3 en paralelo: T046, T047, T048, T049, T051
- Las vistas de US1 en paralelo: T016, T017, T018, T019
- **US3 completa en paralelo con Foundational + US1 + US2** (distinto conjunto de archivos)

---

## Parallel Example: User Story 3

```bash
# Las cinco pruebas de US3 a la vez (archivo común, secciones distintas —
# coordinar si dos personas editan ContainerOptionalTest.php simultáneamente):
Task: "Casos de guardado sin contenedor en tests/Feature/ContainerOptionalTest.php"
Task: "Caso de reparto FIFO en tests/Feature/ContainerOptionalTest.php"
Task: "Caso de rechazo sin descuento parcial en tests/Feature/ContainerOptionalTest.php"
Task: "Casos de trazabilidad en tests/Feature/ContainerOptionalTest.php"
Task: "Caso de sheets_per_box en tests/Feature/ContainerOptionalTest.php"

# Las cinco vistas de US3, archivos totalmente independientes:
Task: "Quitar required en resources/views/transfer-orders/create.blade.php"
Task: "Quitar required en resources/views/transfer-orders/edit.blade.php"
Task: "Corregir etiqueta en resources/views/salidas/create.blade.php"
Task: "Corregir etiqueta en resources/views/salidas/edit.blade.php"
Task: "Presentar sin contenedor en resources/views/traceability/pdf.blade.php"
```

---

## Implementation Strategy

### MVP First (User Story 1)

1. Completar Phase 1: Setup
2. Completar Phase 2: Foundational (**crítico** — bloquea US1 y US2)
3. Completar Phase 3: User Story 1
4. **PARAR Y VALIDAR**: Stock y Trazabilidad vacíos, recuperables, maestros intactos
5. Desplegar o demostrar si procede

### Incremental Delivery

1. Setup + Foundational → esquema de archivo listo
2. US1 → validar → **MVP**: arranque en limpio del inventario
3. US2 → validar → Importación vacía con numeración continua
4. US3 → validar → contenedor opcional operativo
5. Polish → consistencia de cifras y validación completa

### Parallel Team Strategy

Con dos personas, el reparto natural aprovecha que US3 no toca el esquema:

- **Persona A**: Setup → Foundational → US1 → US2
- **Persona B**: US3 completa, desde el final de Setup
- Ambas convergen en Polish

---

## Notes

- **Antes de ejecutar la limpieza en producción**, hacer el respaldo de T002 aunque el borrado sea recuperable: el archivo protege de un error lógico, no de un fallo de disco
- **T034 es la prueba más importante de US3**: un reparto FIFO mal implementado puede consumir los primeros contenedores antes de detectar que falta cantidad, dejando stock descuadrado
- **Cambio visible para los usuarios**: tras T043-T044, las salidas sin contenedor dejarán de mostrar un contenedor elegido al azar y aparecerán vacías. Conviene avisar: parecerá que "se perdió" un dato
- Las pruebas de características **requieren MySQL**; no funcionan sobre SQLite en memoria
- Las pruebas `Auth`/`Example` de Breeze fallan de forma preexistente — no son regresión
- `app/Console/Commands/CleanDatabase.php` **no se toca**: trunca de forma irreversible y borra productos, conductores y transportistas, lo que incumpliría FR-004 y FR-006
- Confirmar después de cada tarea o grupo lógico
- [P] = archivos distintos, sin dependencias pendientes
