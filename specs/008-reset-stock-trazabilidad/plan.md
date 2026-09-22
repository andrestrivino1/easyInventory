# Implementation Plan: Reinicio de Stock, Trazabilidad e Importaciones con Descuento Independiente de Contenedor

**Branch**: `008-reset-stock-trazabilidad` | **Date**: 2026-09-22 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `specs/008-reset-stock-trazabilidad/spec.md`

## Summary

Tres trabajos independientes que comparten una misma puesta en marcha:

1. **Vaciar Stock y Trazabilidad** archivando las filas antes de borrarlas, de modo que la
   información siga siendo recuperable dentro de la base de datos.
2. **Vaciar el módulo de Importación** con el borrado suave nativo que ya existe, lo que hace que el
   consecutivo del DO continúe solo, sin tocar su lógica.
3. **Hacer el contenedor opcional** en transferencias (hoy es obligatorio) y registrar `NULL` de
   verdad en salidas cuando el usuario no elige, repartiendo el descuento físico en FIFO entre
   contenedores.

El enfoque técnico central está en `research.md` R1: se archiva y se borra en lugar de retrofitear
`deleted_at`, porque las rutas de lectura del inventario tienen **33 consultas crudas `DB::table()`
y 26 uniones** que no aplican los scopes de Eloquent. Un solo `whereNull('deleted_at')` olvidado
produciría stock fantasma silencioso — precisamente el fallo que este trabajo busca erradicar. El
archivo preserva la propiedad que se pidió (recuperable, no destruido) sin tocar ninguna consulta de
lectura.

## Technical Context

**Language/Version**: PHP 8.2.12 (el repositorio declara `^7.4 || ^8.0` en `composer.json`)
**Primary Dependencies**: Laravel 8.75, `barryvdh/laravel-dompdf` ^2.2, Blade + jQuery/JS inline (**sin Inertia ni Vue**)
**Storage**: MySQL/MariaDB (XAMPP). Tablas relevantes: `containers`, `container_product`, `transfer_orders`, `transfer_order_products`, `salidas`, `salida_products`, `imports`
**Testing**: PHPUnit ^9.5 sobre **MySQL** (`phpunit.xml` fija `DB_CONNECTION=mysql`, `DB_DATABASE=easy_inventory_test`). Las pruebas de características requieren MySQL, no SQLite
**Target Platform**: Servidor web con PHP-FPM/LiteSpeed; la aplicación se sirve bajo `/inventory`
**Project Type**: Aplicación web monolítica Laravel (MVC con Blade)
**Performance Goals**: No hay objetivos nuevos. La limpieza es una operación puntual fuera de horario; se estima en segundos para el volumen actual (millares de filas, no millones)
**Constraints**: La limpieza debe ser transaccional (todo o nada) y recuperable. Las rutas de lectura de Stock/Trazabilidad **no deben modificarse** para soportar el borrado
**Scale/Scope**: 6 tablas de inventario limpiadas, 1 módulo con borrado suave, 4 controladores tocados, ~20 vistas Blade en los módulos afectados

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Estado de la constitución**: `.specify/memory/constitution.md` está **sin rellenar** — conserva
todos los marcadores de plantilla (`[PRINCIPLE_1_NAME]`, `[SECTION_2_NAME]`, etc.) y no define
ningún principio del proyecto.

**Resultado de la comprobación**: **no hay puertas que evaluar.** No se declara ninguna violación
porque no existe ninguna regla contra la cual medirla. La sección `Complexity Tracking` queda vacía
por el mismo motivo.

En ausencia de una constitución, este plan se somete a los criterios que el propio repositorio ya
practica:

| Criterio observado en el repositorio | Cómo lo cumple este plan |
|---|---|
| Migraciones defensivas (`Schema::hasColumn` / `hasTable`) | Las migraciones nuevas comprueban existencia antes de crear |
| Operaciones destructivas exigen confirmación | El comando pide confirmación salvo `--force`, y ofrece `--dry-run` |
| Trabajo acotado por especificación en `specs/` | Este plan y sus artefactos |
| Pruebas de características sobre MySQL | `research.md` R9 |

**Recomendación (fuera de alcance)**: ejecutar `/speckit-constitution` en algún momento. Ocho
funcionalidades llevan ya evaluándose contra una plantilla vacía.

## Project Structure

### Documentation (this feature)

```text
specs/008-reset-stock-trazabilidad/
├── plan.md                      # Este archivo
├── spec.md                      # Especificación
├── research.md                  # Fase 0 — decisiones técnicas (R1-R9)
├── data-model.md                # Fase 1 — entidades, reglas, algoritmo FIFO
├── quickstart.md                # Fase 1 — cómo ejecutar y verificar
├── contracts/
│   └── cli-commands.md          # Fase 1 — contrato del comando y de los formularios
├── checklists/
│   └── requirements.md          # Validación de calidad de la especificación
└── tasks.md                     # Fase 2 — lo genera /speckit-tasks, NO este comando
```

### Source Code (repository root)

```text
app/
├── Console/Commands/
│   ├── InventoryReset.php              # NUEVO — comando de limpieza
│   ├── InventoryResetRestore.php       # NUEVO — restauración de un lote
│   └── CleanDatabase.php               # SIN TOCAR (trunca y borra productos: no sirve aquí)
├── Services/
│   ├── InventoryResetService.php       # NUEVO — archivar + borrar, transaccional
│   ├── ContainerAllocator.php          # NUEVO — reparto FIFO entre contenedores
│   ├── LiquidacionCalculator.php       # sin cambios
│   └── LiquidacionStateMachine.php     # sin cambios
├── Http/Controllers/
│   ├── TransferOrderController.php     # container_id required → nullable; reparto FIFO
│   ├── SalidaController.php            # registrar NULL en vez de contenedor arbitrario
│   ├── StockController.php             # unificar index() y getStockData() (FR-025)
│   ├── TraceabilityController.php      # mostrar "sin contenedor" con claridad (FR-024)
│   └── ImportController.php            # transacción + reintento del DO (FR-017)
└── Models/
    └── Import.php                      # sin cambios (SoftDeletes y DO_CODE_FLOOR ya están)

database/migrations/
├── 2026_09_22_000001_create_inventory_archive_tables.php   # NUEVO — 6 tablas de archivo
└── 2026_09_22_000002_create_inventory_reset_logs.php       # NUEVO — registro de ejecuciones

resources/views/
├── transfer-orders/create.blade.php    # quitar required del <select> de contenedor
├── transfer-orders/edit.blade.php      # idem
├── salidas/create.blade.php            # quitar el asterisco engañoso de "Contenedor*"
├── salidas/edit.blade.php              # idem
├── stock/index.blade.php               # estado vacío legible
└── traceability/index.blade.php        # estado vacío legible + "sin contenedor"

tests/Feature/
├── InventoryResetTest.php              # NUEVO — vaciado, preservación, idempotencia
├── InventoryResetRestoreTest.php       # NUEVO — recuperabilidad real
├── DoCodeConsecutiveTest.php           # NUEVO — DO continúa tras el vaciado
└── ContainerOptionalTest.php           # NUEVO — transferencias/salidas sin contenedor
```

**Structure Decision**: monolito Laravel MVC existente. Se introduce `app/Services/` para la lógica
nueva porque la carpeta **ya existe** (`LiquidacionCalculator`, `LiquidacionStateMachine`) y porque
hoy **no hay ninguna capa de servicio de inventario**: toda la lógica vive dentro de cuatro
controladores muy grandes (`StockController` 2.014 líneas, `SalidaController` 1.229,
`TransferOrderController` 1.548). Colocar el reparto FIFO y la limpieza en servicios los hace
verificables sin atravesar HTTP, en lugar de engordar aún más esos controladores.

## Fases de entrega

Alineadas con las prioridades de la especificación. Cada fase es entregable y verificable por sí
sola.

### Fase A — Limpieza de inventario (User Story 1, P1)

1. Migración de las seis tablas de archivo + `inventory_reset_logs`.
2. `InventoryResetService`: archivar y borrar en orden de FK, dentro de una transacción.
3. Comandos `inventory:reset` e `inventory:reset-restore` con `--dry-run`, `--force`, `--scope`.
4. Estados vacíos legibles en `stock/index.blade.php` y `traceability/index.blade.php`.
5. Verificar que las exportaciones PDF/Excel no fallan sin datos (FR-008).
6. Pruebas: vaciado, preservación de maestros, idempotencia, recuperabilidad, alcance por rol.

**Entregable**: Stock y Trazabilidad vacíos y recuperables, con los datos maestros intactos.

### Fase B — Vaciado de Importación y consecutivo del DO (User Story 2, P2)

1. Ámbito `imports` del comando: `Import::query()->delete()`.
2. Prueba que confirma que el siguiente DO es N+1 y **nunca** 001.
3. Transacción + reintento ante colisión de clave única en `ImportController::store()` (FR-017).

**Entregable**: Importación vacía, numeración continua y protegida contra concurrencia.

### Fase C — Contenedor opcional (User Story 3, P2)

1. `ContainerAllocator`: reparto FIFO **por fila** de `container_product`.
2. `TransferOrderController`: `required` → `nullable` en `store()` y `update()`; usar el asignador;
   guardar `container_id = NULL`.
3. `SalidaController`: sustituir la elección arbitraria (`:217-230`, `:270-273`) por `NULL`
   explícito; validar contra el total.
4. Vistas: quitar `required` del `<select>` de transferencias; corregir la etiqueta de salidas.
5. Trazabilidad: mostrar "sin contenedor" de forma clara (FR-024).
6. Pruebas: guardado sin contenedor, reparto FIFO correcto, rechazo por exceso sin descuento
   parcial.

**Entregable**: transferencias y salidas operables sin elegir contenedor, reflejadas en Trazabilidad.

### Fase D — Consistencia de Stock (FR-025) — *decisión de alcance pendiente*

Unificar `StockController::index()` y `getStockData()` sobre `calcularStockPorBodega()`
(`research.md` R7). Ver **Riesgos** más abajo.

## Riesgos y decisiones que conviene confirmar

| # | Asunto | Situación | Recomendación |
|---|---|---|---|
| 1 | **Mecanismo de borrado del inventario** | Se eligió "borrado suave". Se implementa como **archivo + borrado**, no como `deleted_at`, por los 33 puntos de consulta cruda (`research.md` R1) | Confirmar que archivo recuperable satisface la intención. Es la decisión de mayor impacto del plan |
| 2 | **FR-025 / Fase D** | `index()` y `getStockData()` **ya divergen** hoy: las exportaciones restan transferencias en tránsito y la pantalla no. Es un defecto preexistente que FR-025 convierte en requisito | Incluir. Con los módulos vacíos es invisible, pero reaparece con la primera operación real |
| 3 | **FR-017 / concurrencia del DO** | Es un **endurecimiento**, no una preservación del comportamiento actual | Incluir: está acotado a un punto del código. Diferible si se prefiere reducir alcance |
| 4 | **Cambio visible en Salidas** | Los movimientos sin contenedor pasarán de mostrar un contenedor arbitrario a mostrarse vacíos | Es lo correcto y lo que se eligió, pero conviene avisar a los usuarios: parecerá que "se perdió" un dato |
| 5 | **Año del DO** | `arrival_date` decide el año pero el filtro alternativo usa `created_at`; pueden caer en grupos distintos | Documentado, **fuera de alcance**. No tocar aquí |
| 6 | **Respaldo previo** | Aunque el borrado sea recuperable, conviene un volcado completo antes de ejecutar en producción | Hacer el volcado igualmente. El archivo protege de errores lógicos, no de un fallo de disco |

## Complexity Tracking

> Sin entradas: la constitución no define puertas, por lo que no hay violaciones que justificar.

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| — | — | — |
