---
description: "Task list for: Reordenar y renombrar el panel de totales de la liquidación"
---

# Tasks: Reordenar y renombrar el panel de totales de la liquidación

**Input**: Design documents from `/specs/005-reordenar-totales-liquidacion/`
**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/http-routes.md](contracts/http-routes.md), [quickstart.md](quickstart.md)

**Tests**: SÍ se incluyen (Feature tests en MySQL `easy_inventory_test`). El spec define un "Independent Test" por historia y el plan lista 3 archivos de prueba. Ver memoria feature-tests-need-mysql.

**Organization**: Tareas agrupadas por historia de usuario. El motor de cálculo (columna `sobreanticipo` + `LiquidacionCalculator` + paridad JS) es compartido y vive en la fase Foundational; cada historia entrega/valida una superficie distinta (show / gastos / form / pdf).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: puede correr en paralelo (archivo distinto, sin dependencias pendientes)
- **[Story]**: US1, US2, US3, US4
- Rutas de archivo exactas en cada tarea

## Convenciones de cálculo (referencia rápida)

- Sumatoria de gastos = `gastos_op + descuentos`
- Suma de gastos total = `gastos_op + descuentos + peajes`
- Saldo adeudado empresa = `valor_flete − anticipo_empresa` → col `saldo_pendiente`
- Anticipos conductor = `anticipo_conductor + sobreanticipo`
- Ant - gastos = `(gastos_op + descuentos) − (anticipo_conductor + sobreanticipo)` → col `saldo_viaje`
- A favor de = signo de Ant-gastos: `>0 empresa, <0 conductor, =0 ninguno`
- Ganancia final = `valor_flete − Suma de gastos total` → col `ganancia_viaje`

---

## Phase 1: Setup

**Purpose**: Preparar entorno de build y pruebas.

- [x] T001 Verificar conexión de pruebas a MySQL `easy_inventory_test` (revisar `phpunit.xml` / `.env.testing`) y que `php artisan migrate:fresh --env=testing` corre sin errores, según la memoria feature-tests-need-mysql.
- [x] T002 [P] Confirmar toolchain de front: `npm install` y que `npm run build` (Vite) genera `public/js/app.js`.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Motor de datos y cálculo compartido por TODAS las historias. ⚠️ Ninguna historia puede completarse hasta terminar esta fase.

- [x] T003 Crear migración `database/migrations/2026_05_28_000000_add_sobreanticipo_to_liquidaciones.php`: `up()` agrega `decimal('sobreanticipo', 12, 0)->default(0)->after('anticipo_conductor')`; `down()` hace `dropColumn('sobreanticipo')`.
- [x] T004 Aplicar la migración: `php artisan migrate` (depende de T003).
- [x] T005 [P] Agregar `'sobreanticipo'` a `$fillable` y `'sobreanticipo' => 'integer'` a `$casts` en `app/Models/Liquidacion.php`.
- [x] T006 [P] Agregar regla `'sobreanticipo' => ['nullable','integer','min:0']` y convertir vacío→0 en `prepareForValidation()` de `app/Http/Requests/StoreLiquidacionRequest.php` (UpdateLiquidacionRequest la hereda).
- [x] T007 [P] Reescribir `LiquidacionCalculator::recalcAndSave` en `app/Services/LiquidacionCalculator.php` con las fórmulas nuevas: `sumatoria_gastos_totales = gastos_op + descuentos + peajes`; `saldo_pendiente = valor_flete − anticipo_empresa`; `saldo_viaje = (gastos_op + descuentos) − (anticipo_conductor + sobreanticipo)`; `ganancia_viaje = valor_flete − sumatoria_gastos_totales`; `total_anticipos = anticipo_empresa + anticipo_conductor + sobreanticipo`; `a_favor_de = aFavorDe(saldo_viaje)`. Agregar helpers `sumGastos()` (op+descuentos) y `anticiposConductor()` si ayudan a las vistas.
- [x] T008 [P] Replicar las nuevas fórmulas en `resources/js/liquidacion-form.js`: estado `sobreanticipo` (desde `config.initialSobreanticipo`); getters `sumGastos` (op+descuentos), `anticiposConductor`, `antGastos`, `saldoAdeudadoEmpresa`, `gananciaViaje` (= flete − sumGastosTotales con descuentos), `aFavorDeLabel` basado en `antGastos`.
- [x] T009 Replicar EXACTAMENTE los mismos getters/estado del fallback Alpine inline en `resources/views/liquidaciones/partials/_form.blade.php` (bloque `window.liquidacionForm` en `alpine:init`) e inyectar `"initialSobreanticipo" => (int)($liq->sobreanticipo ?? 0)` en el `x-data` JSON (mismo archivo).
- [x] T010 Ejecutar `npm run build` para regenerar `public/js/app.js` con la lógica de T008 (depende de T008).

**Checkpoint**: motor listo — el cálculo (servidor + cliente) ya produce los valores nuevos; las historias solo reordenan/exponen superficies.

---

## Phase 3: User Story 1 - Panel de totales reordenado y renombrado (Priority: P1) 🎯 MVP

**Goal**: Recuadro de totales en 2 columnas con etiquetas nuevas en la vista de detalle y en la barra sticky del formulario.

**Independent Test**: abrir una liquidación con valores conocidos y verificar el orden/etiquetas de ambas columnas y los valores derivados.

### Tests for User Story 1

- [x] T011 [P] [US1] Crear `tests/Feature/LiquidacionPanelTotalesTest.php`: dado gastos 3.159.000, descuentos 100.000, peajes 981.000, flete 6.700.000, anticipo empresa 4.690.000, asserts: Sumatoria de gastos=3.259.000, Suma de gastos total=4.240.000, Saldo adeudado empresa=2.010.000, Ganancia final=2.460.000 (sobre los campos cacheados `sumatoria_gastos_totales`, `saldo_pendiente`, `ganancia_viaje` tras guardar, y/o assert del HTML de `show`).

### Implementation for User Story 1

- [x] T012 [US1] Reordenar el recuadro "Totales" en `resources/views/liquidaciones/show.blade.php` a 2 columnas: IZQUIERDA (Sumatoria de gastos = `sumatoria_gastos_operativos + descuentos`, Sumatoria de peajes, Suma de gastos total de viaje = `sumatoria_gastos_totales`, Valor flete pactado, Anticipo empresa de transporte, Saldo adeudado empresa de transporte = `saldo_pendiente`); DERECHA (Anticipos conductor = `anticipo_conductor + sobreanticipo`, Ant - gastos = `saldo_viaje`, A favor de, Ganancia final de viaje = `ganancia_viaje`). Mantener resaltado verde/rojo por signo. Quitar las celdas viejas (Total anticipos, Peajes conductor, Descuentos, Saldo pendiente, Saldo viaje).
- [x] T013 [US1] Actualizar la barra sticky de `resources/views/liquidaciones/partials/_form.blade.php` para reflejar las mismas etiquetas/orden y bindings (`sumGastos`, `sumPeajes`, `sumGastosTotales`, `valorFlete`, `anticipoEmpresa`, `saldoAdeudadoEmpresa`, `anticiposConductor`, `antGastos`, `aFavorDeLabel`, `gananciaViaje`).

**Checkpoint**: el panel de pantalla y la barra sticky muestran las 2 columnas nuevas; US1 verificable de forma independiente.

---

## Phase 4: User Story 2 - Descuento empresa en los gastos del viaje (Priority: P1)

**Goal**: el input de "Descuentos (empresa)" se ubica al inicio de la sección de gastos del viaje y suma a la "Sumatoria de gastos".

**Independent Test**: editar el descuento y verificar que Sumatoria de gastos, Suma de gastos total y Ganancia final cambian en consecuencia.

### Tests for User Story 2

- [x] T014 [P] [US2] Agregar a `tests/Feature/LiquidacionPanelTotalesTest.php` un caso: con descuento=0 la Sumatoria de gastos == gastos operativos; al subir el descuento, `sumatoria_gastos_totales` y `ganancia_viaje` cambian por ese delta.

### Implementation for User Story 2

- [x] T015 [US2] Mover el campo `descuentos` (input `name="descuentos"`, `x-model.number="descuentos"`) desde la cabecera del `resources/views/liquidaciones/partials/_form.blade.php` al inicio de la sección de gastos en `resources/views/liquidaciones/partials/_expenses-table.blade.php`, presentándolo como fila/encabezado "Descuentos (empresa)".
- [x] T016 [US2] En `resources/views/liquidaciones/show.blade.php`, asegurar que "Sumatoria de gastos" muestre `sumatoria_gastos_operativos + descuentos` y retirar la línea suelta de "DESCUENTOS" de la cabecera (queda implícito dentro de los gastos).

**Checkpoint**: el descuento vive en los gastos y se refleja en los totales; US1 + US2 funcionan.

---

## Phase 5: User Story 3 - Reincorporar "Sobre anticipo" (Priority: P2)

**Goal**: campo "Sobre anticipo" editable en el formulario; "Anticipos conductor" = anticipo conductor + sobre anticipo.

**Independent Test**: capturar anticipo conductor=3.000.000 y sobre anticipo=500.000 → Anticipos conductor=3.500.000.

### Tests for User Story 3

- [x] T017 [P] [US3] Crear `tests/Feature/LiquidacionSobreanticipoTest.php`: guardar con `anticipo_conductor=3.000.000`, `sobreanticipo=500.000`; assert "Anticipos conductor"=3.500.000 y `saldo_viaje` (Ant-gastos) = `(gastos_op+descuentos) − 3.500.000`; verificar A favor de por signo (positivo→empresa, negativo→conductor); y que una liquidación sin `sobreanticipo` lo trata como 0.

### Implementation for User Story 3

- [x] T018 [US3] Agregar el input "SOBRE ANTICIPO" (`name="sobreanticipo"`, `type=number`, `min=0`, `x-model.number="sobreanticipo"`, `value="{{ old('sobreanticipo', $liq->sobreanticipo ?? 0) }}"`) en la cabecera de anticipos de `resources/views/liquidaciones/partials/_form.blade.php`, junto a Anticipo conductor.
- [x] T019 [US3] En `resources/views/liquidaciones/show.blade.php`, mostrar el dato de "Sobre anticipo" en la cabecera (o junto a Anticipo conductor) para trazabilidad, sin romper el recuadro de 2 columnas.

**Checkpoint**: el sobre anticipo se captura, persiste y entra en "Anticipos conductor"; US1–US3 funcionan.

---

## Phase 6: User Story 4 - Ajustes al PDF y a la firma (Priority: P2)

**Goal**: el PDF no muestra el anticipo empresa en el encabezado (sí en el recuadro de totales y en el formulario); la firma dice "FIRMA FUNCIONARIO REVISÓ".

**Independent Test**: generar el PDF y verificar encabezado sin anticipo empresa, recuadro con las celdas de empresa, y texto de firma.

### Tests for User Story 4

- [x] T020 [P] [US4] Crear `tests/Feature/LiquidacionPdfAjustesTest.php`: GET `liquidaciones.pdf` responde 200; el HTML renderizado del PDF NO contiene la fila de encabezado "ANTICIPO EMPRESA"; SÍ contiene "Saldo adeudado empresa" y "FIRMA FUNCIONARIO REVISÓ".

### Implementation for User Story 4

- [x] T021 [US4] En `resources/views/liquidaciones/pdf.blade.php`, eliminar la fila `ANTICIPO EMPRESA` del encabezado (`header-table`, ~líneas 147–151) y reacomodar FECHA INICIO / FECHA FIN para no dejar celdas vacías.
- [x] T022 [US4] Reordenar la tabla de totales del PDF (`data-table`, ~líneas 199–250) a las 2 columnas y etiquetas nuevas (incluye Anticipo empresa de transporte y Saldo adeudado empresa de transporte; quita Total anticipos/Peajes conductor/Saldo pendiente/Saldo viaje viejos).
- [x] T023 [US4] Cambiar el texto de `.firma-box` de "FIRMA CONDUCTOR" a "FIRMA FUNCIONARIO REVISÓ" en `resources/views/liquidaciones/pdf.blade.php`.

**Checkpoint**: PDF y firma actualizados; todas las historias funcionan.

---

## Phase 7: Polish & Cross-Cutting

- [x] T024 Revisar la PARIDAD de fórmulas entre `app/Services/LiquidacionCalculator.php`, el fallback inline de `_form.blade.php` y `resources/js/liquidacion-form.js`; reconstruir con `npm run build` si hubo cambios tardíos.
- [x] T025 Ejecutar la validación golden-path de [quickstart.md](quickstart.md) (los números del ejemplo del PDF) en el navegador: barra sticky, `show` y PDF.
- [x] T026 Correr la suite: `php artisan test --filter=LiquidacionPanelTotalesTest`, `--filter=LiquidacionSobreanticipoTest`, `--filter=LiquidacionPdfAjustesTest` (MySQL de pruebas).

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de Setup. BLOQUEA todas las historias. T004 depende de T003; T010 depende de T008; T009 depende de tener T007 (modelo) ya listo para inyectar `sobreanticipo`.
- **US1 (Phase 3)**: depende de Foundational.
- **US2 (Phase 4)**: depende de Foundational; toca `_expenses-table` (archivo propio) y `_form`/`show` (compartidos con US1 → secuenciar).
- **US3 (Phase 5)**: depende de Foundational; toca `_form`/`show` (compartidos → secuenciar tras US1/US2).
- **US4 (Phase 6)**: depende de Foundational; `pdf.blade.php` es archivo propio → puede ir en paralelo a US1–US3 una vez hecho Foundational.
- **Polish (Phase 7)**: depende de las historias deseadas.

### Within Each User Story

- Tests primero (deben fallar antes de implementar).
- En este feature, el cálculo ya vive en Foundational; los tests de historia validan la superficie + el comportamiento.

### Parallel Opportunities

- T005, T006, T007, T008 (Foundational) son archivos distintos → `[P]`.
- US4 (`pdf.blade.php`) puede desarrollarse en paralelo a US1–US3 tras Foundational, porque no comparte archivo.
- Tests `[P]` (T011, T014, T017, T020) pueden escribirse en paralelo.
- **Cuidado (NO paralelizar)**: `_form.blade.php` lo tocan Foundational (T009), US1 (T013) y US3 (T018); `show.blade.php` lo tocan US1 (T012), US2 (T016), US3 (T019) → secuenciales.

---

## Parallel Example: Foundational

```bash
# Tras crear y aplicar la migración (T003 → T004), en paralelo:
Task: "T005 Liquidacion model fillable/casts (app/Models/Liquidacion.php)"
Task: "T006 StoreLiquidacionRequest sobreanticipo (app/Http/Requests/StoreLiquidacionRequest.php)"
Task: "T007 LiquidacionCalculator recalcAndSave (app/Services/LiquidacionCalculator.php)"
Task: "T008 liquidacion-form.js getters (resources/js/liquidacion-form.js)"
```

---

## Implementation Strategy

### MVP First (US1 + US2)

1. Phase 1 (Setup) → Phase 2 (Foundational: motor de cálculo + columna).
2. Phase 3 (US1: panel 2 columnas) → **VALIDAR** con números conocidos.
3. Phase 4 (US2: descuento en gastos) → validar que arrastra a los totales.
4. MVP listo: el recuadro nuevo ya es correcto en pantalla.

### Incremental Delivery

1. Foundational → US1 → US2 (panel correcto en pantalla).
2. US3 (sobre anticipo) → US4 (PDF/firma).
3. Cada historia agrega valor sin romper las anteriores.

---

## Notes

- `[P]` = archivos distintos, sin dependencias pendientes.
- El motor de cálculo es compartido (Foundational); la independencia de historias se preserva validando cada superficie con su test.
- Mantener la paridad servidor/cliente de fórmulas SIEMPRE (3 lugares) y reconstruir Vite.
- Commit por tarea o grupo lógico. Parar en cada checkpoint para validar.
