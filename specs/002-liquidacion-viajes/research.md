# Phase 0: Research & Decisions — Liquidación de Viajes

**Branch**: `002-liquidacion-viajes` | **Date**: 2026-05-19

Cada decisión técnica del plan se documenta como `Decision / Rationale / Alternatives`.

---

## R1. Motor de generación de PDF

**Decision**: Usar **`barryvdh/laravel-dompdf` ^2.2** (ya instalado en `composer.json`).

**Rationale**:
- Ya presente en el repo — cero cambios de dependencia.
- Wrapper Laravel idiomático: `Pdf::loadView('liquidaciones.pdf', [...])->download(...)`.
- Renderiza HTML+CSS directamente; el formato del Excel original se puede replicar con una `<table>` Blade.
- Otros módulos del repo probablemente lo usan (importaciones, ITRs) — consistencia.

**Alternatives considered**:
- **TCPDF** (`tecnickcom/tcpdf` también en composer): API basada en `Cell()/MultiCell()`. Más control fino pero código procedural y verboso para una tabla compleja.
- **mPDF**: mejor soporte de CSS/Unicode pero requiere instalación adicional.
- **wkhtmltopdf / snappy**: requiere binario externo en el servidor (XAMPP). Operacionalmente más costoso.
- **Print-to-PDF del navegador**: cero dependencias backend, pero formato dependiente del navegador del cliente y sin posibilidad de marca de agua "ANULADA" controlada.

---

## R2. Almacenamiento de totales (stored vs computed)

**Decision**: **Híbrido** — almacenar `sumatoria_gastos_operativos`, `sumatoria_peajes`, `total_anticipos`, `saldo_viaje`, `ganancia_viaje` como columnas en `liquidaciones`, recalculadas en cada `save()` dentro de una transacción. Para el formulario en vivo se usa Alpine.js (cliente) sin tocar BD.

**Rationale**:
- SC-007 exige consolidado en <2s con 1.000 filas → necesita agregación SQL directa (`SUM(saldo_viaje) FROM liquidaciones WHERE …`).
- Cargar 16 líneas de gasto + N peajes por liquidación solo para sumar en PHP no escala.
- Almacenar es seguro porque toda escritura pasa por el `LiquidacionCalculator` y se hace en transacción atómica.

**Alternatives considered**:
- **Solo computed accessors Eloquent**: simple y "siempre correcto", pero N+1 garantizado en el listado y consolidado lento.
- **Vista materializada SQL**: overkill para volumen estimado (50–200 liquidaciones/mes), y mantenimiento extra de la vista.

---

## R3. Catálogo de las 16 categorías de gastos

**Decision**: **Tabla `expense_categories`** seedeada con las 16 filas fijas (id, name, has_galones, sort_order, active). Cada fila de `liquidacion_expenses` referencia `expense_category_id` por FK.

**Rationale**:
- Permite queries `GROUP BY expense_category_id` para reportería futura (gastos por categoría a nivel global).
- Normaliza nombres (evita typos como "BÁSCULA" vs "BASCULA").
- El spec dice "se gestionan por migración/seed, no por UI" — encaja perfectamente con seeder versionado.
- `has_galones` modela explícitamente que solo ACPM tiene campo galones; el form puede inferir del flag sin hardcode.

**Alternatives considered**:
- **Enum PHP** (`enum ExpenseCategory: string { case ACPM = 'acpm'; … }`): requiere PHP 8.1+. La app declara PHP 7.4|8.0 — limitaría a enum simulado con constantes. Además `GROUP BY` se complica.
- **Columnas dedicadas** (`gasto_acpm`, `gasto_urea`, …, 16 columnas): denormalizado, rompe reportería; agregar/quitar categoría = migración.
- **Config PHP** (`config/liquidacion.php`): no soporta queries SQL agregadas.

---

## R4. Estrategia de agregación del consolidado

**Decision**: **Query Builder con `DB::raw('SUM(...)')`** en `LiquidacionCalculator::aggregate($filters)`. Para "Agrupar por mes", añadir `GROUP BY DATE_FORMAT(fecha_inicio, '%Y-%m')`.

**Rationale**:
- Aprovecha índices SQL (`idx_liquidaciones_fecha_inicio`, `idx_liquidaciones_driver`, `idx_liquidaciones_route`).
- Una sola query devuelve todos los totales del periodo; otra query (idéntica + GROUP BY mes) devuelve el desglose mensual.
- Excluir `Anulada` y soft-deleted vía `WHERE estado != 'anulada' AND deleted_at IS NULL`.

**Alternatives considered**:
- **Eloquent collections en PHP** (`Liquidacion::filter($f)->get()->sum('saldo_viaje')`): carga todas las filas a memoria. Con 1.000 filas + 16 gastos cada una = ~16k registros transferidos para sumar 4 columnas. No cumple SC-007.
- **Cache (Redis/Memcached)**: prematuro para el volumen actual; complica invalidación cuando se cambia/anula/reabre.

---

## R5. Transiciones de estado (Borrador / Cerrada / Anulada)

**Decision**: Columna `estado` ENUM en `liquidaciones` + servicio `LiquidacionStateMachine` con métodos `close($liq, $user)`, `reopen($liq, $user, $motivo)`, `cancel($liq, $user, $motivo)`. Cada método valida transición permitida y escribe en `liquidacion_state_logs` dentro de transacción.

**Rationale**:
- Patrón explícito y testeable (no se confía en eventos Eloquent silenciosos).
- Centraliza reglas: validar estado origen, requerir motivo cuando aplica, escribir log.
- Lanza excepciones (`InvalidStateTransition`, `MotivoRequired`) que controllers traducen a 422.

**Transiciones permitidas:**

| Desde \ A | Borrador | Cerrada | Anulada |
|---|---|---|---|
| (nueva) | ✅ default | ❌ | ❌ |
| Borrador | — | ✅ (close) | ❌ |
| Cerrada | ✅ (reopen, motivo) | — | ✅ (cancel, motivo) |
| Anulada | ❌ | ❌ | — (terminal) |

**Alternatives considered**:
- **Paquete `spatie/laravel-model-states`**: muy bueno pero suma dependencia para un FSM de 3 estados que ya es directo.
- **Eloquent model events** (`saving`, `updating`): difícil capturar "razón de cambio" y mezclar lógica de transición con CRUD genérico.

---

## R6. Control de acceso (FR-015: solo admin)

**Decision**: **Policy `LiquidacionPolicy`** + **gate `liquidaciones.access`** + middleware en grupo de rutas. El check de rol replica el patrón ya usado en el sidebar: `$user->rol === 'admin'`.

```php
// AppServiceProvider boot()
Gate::define('liquidaciones.access', fn($user) => $user->rol === 'admin');

// routes/web.php
Route::middleware(['auth','can:liquidaciones.access'])->group(function () {
    Route::resource('liquidaciones', LiquidacionController::class);
    // ...
});
```

**Rationale**:
- Reusa convención existente (`$user->rol`).
- Middleware aplica a todo el módulo de un solo lugar — no se olvida.
- Policy permite granularidad por liquidación (ej. solo el creador puede reabrir su propia liquidación si el negocio lo pide después).

**Alternatives considered**:
- **Spatie laravel-permission**: ya hay roles en `users.rol` como string; no vale la pena migrar a un paquete entero por una feature.
- **Checks `if ($user->rol === 'admin')` en cada controller**: dispersa la regla; se nos olvida en uno y queda exposed.

---

## R7. Integración con sidebar + i18n existente

**Decision**:
- Agregar item al sidebar (`resources/views/layouts/app.blade.php`) condicional a `$user->rol === 'admin'` con icono `bi-cash-coin` o `bi-fuel-pump`: "Liquidación de Viajes".
- Sub-rutas (Liquidaciones / Rutas) se navegan desde el index del módulo, no como ítems separados del sidebar (mantiene el sidebar limpio).
- Strings en `resources/lang/<es|en|zh>/liquidaciones.php` siguiendo el patrón existente (`__('liquidaciones.titulo')`, etc.). Para v1 solo `es` con clave; `en` y `zh` quedan como TODO con fallback al español (Laravel devuelve la key si no existe traducción).

**Rationale**:
- Consistencia visual y de patrones con el resto del app.
- Permite que el módulo se "vea nativo" desde el primer despliegue.

**Alternatives considered**:
- Hacer i18n completo de 3 idiomas en v1: scope inflado, todavía no hay confirmación de que usuarios `en`/`zh` accederán al módulo.
- No tocar sidebar y dejar acceso solo por URL directa: mala UX.

---

## R8. Captura de firma del conductor (v1)

**Decision**: **Firma sobre papel**. El PDF imprime un bloque "FIRMA CONDUCTOR" en blanco; el conductor firma físicamente sobre la impresión. No se guarda imagen de firma en BD.

**Rationale**:
- Confirmado en spec → Assumptions: "La firma del conductor en v1 puede capturarse en papel (sobre el PDF impreso). Una firma digital nativa se considera fuera de alcance v1."
- Cero complejidad técnica de canvas/upload.

**Alternatives considered**:
- **Canvas + signature pad JS**: requiere librería extra + storage de imagen + manejo de tamaño/calidad. Fuera de alcance.
- **Upload de foto firmada**: similar, fuera de alcance.

---

## R9. Snapshot vs referencia de datos (placa, ruta, transportadora) en la liquidación

**Decision**: **Snapshot por valor** para datos que pueden cambiar en el maestro y afectarían liquidaciones históricas:
- `vehicle_plate`: copia desde `drivers.vehicle_plate` al guardar (editable como excepción FR-001b). Si el conductor cambia placa después, las liquidaciones viejas conservan la placa real del viaje.
- `transportadora` y `telefono_empresa`: texto libre.
- Ruta: FK a `routes`, pero los **peajes** sí son snapshot (se copian a `liquidacion_tolls` al elegir la ruta). Si el admin cambia el valor sugerido de un peaje en la ruta, las liquidaciones existentes mantienen el valor que tenían capturado.

**Rationale**:
- Reglas contables: una liquidación cerrada/anulada NO puede cambiar de números porque cambió un maestro.
- Spec FR-010 explícitamente dice "las liquidaciones existentes no cambian" cuando se edita un valor sugerido.

**Alternatives considered**:
- Referenciar `route_tolls.id` y leer valor sugerido dinámicamente: viola la regla contable.
- Snapshot completo de `drivers.*` en `liquidaciones.*` (denormalización total): exceso para v1; el `driver_id` FK basta para mostrar nombre/teléfono actualizado en el listado y en el PDF se usa el snapshot.

---

## R10. Validación de unicidad placa + manifiesto (FR-013)

**Decision**: Validación a nivel de **Form Request** (no constraint UNIQUE en BD) que consulta `Liquidacion::where('vehicle_plate', $plate)->where('numero_mfto', $mfto)->where('estado','!=','anulada')->whereNull('deleted_at')->exists()` y muestra **warning (no error)** en la UI — el operador puede confirmar y guardar igual.

**Rationale**:
- FR-013 dice "alertar sin bloquear".
- UNIQUE en BD bloquearía → contradice el requisito.
- Excluye `Anulada` y soft-deleted como el spec exige.

**Alternatives considered**:
- Índice UNIQUE con `deleted_at` incluido: complica anulaciones; viola "alertar sin bloquear".

---

## Resumen de decisiones

| # | Tema | Decisión |
|---|---|---|
| R1 | PDF | `barryvdh/laravel-dompdf` (ya instalado) |
| R2 | Totales | Híbrido: stored en BD + computed en Alpine.js para el form |
| R3 | Categorías gastos | Tabla `expense_categories` seedeada |
| R4 | Consolidado | Query Builder con SUM + GROUP BY mes |
| R5 | Estados | Columna ENUM + servicio `LiquidacionStateMachine` + log |
| R6 | Auth | Gate + Policy + middleware (`$user->rol === 'admin'`) |
| R7 | Sidebar + i18n | Item nuevo solo `admin`; lang keys `es` v1, fallback al key |
| R8 | Firma | Papel sobre PDF impreso (sin captura digital) |
| R9 | Snapshot data | `vehicle_plate` y peajes copiados al guardar; ruta FK |
| R10 | Unicidad placa+mfto | Warning en Form Request, no UNIQUE en BD |

**Sin `NEEDS CLARIFICATION` restantes**. Listo para Phase 1.
