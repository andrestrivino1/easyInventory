# Quickstart — Ajustes de liquidación y gastos mensuales

**Branch**: `004-ajustes-liquidacion-gastos` | **Date**: 2026-05-27

Cómo levantar, implementar incrementalmente y validar esta feature en el monolito Laravel (XAMPP/Windows).

## Requisitos

- XAMPP con Apache + MySQL/MariaDB corriendo.
- PHP 8.2, Composer, Node + npm (Vite).
- Base `easy_inventory` migrada al estado de la rama `main` (specs 002 + 003 aplicadas).

## Levantar el entorno (dev)

```powershell
# Dependencias (si hiciera falta)
composer install
npm install

# Migrar la base (aplica monthly_expenses + ajustes de liquidaciones)
php artisan migrate

# Build de assets (Alpine + JS del módulo)
npm run build          # o: npm run dev  (watch)

# Servir (o usar el vhost de XAMPP apuntando a /public)
php artisan serve
```

## Orden de implementación (por User Story, incremental)

> Cada historia es entregable y testeable por sí sola. Sugerido P1 → P2 → P3.

### US1 — Gastos mensuales (P1)
1. Migración `create_monthly_expenses_table`.
2. Modelo `MonthlyExpense` (fillable, casts, relación `driver`, scopes).
3. Gate `liquidaciones.gastos.access` en `AppServiceProvider` (`rol === 'admin'`).
4. Rutas `liquidaciones.gastos.*` (grupo con `can:liquidaciones.gastos.access`).
5. `MonthlyExpenseController` (index con filtros placa/mes + paginación; create/store/edit/update/destroy).
6. `StoreMonthlyExpenseRequest` / `UpdateMonthlyExpenseRequest` (validación + unicidad conductor+período; placa server-side).
7. Vistas `gastos/index.blade.php` (tabla paginada + filtros) y `gastos/_form.blade.php` (select conductor → placa auto vía `drivers/{driver}/info`).
8. Botón "Gastos mensuales" en `liquidaciones/index.blade.php` dentro de `@can('liquidaciones.gastos.access')`.

### US2 — Anticipos / descuentos / saldo pendiente (P2)
1. Migración `adjust_liquidaciones_anticipos_descuentos` (rename + 3 columnas + backfill).
2. `Liquidacion` model (fillable/casts).
3. `LiquidacionCalculator` (`computeTotalAnticipos`, `saldo_pendiente`, `sum_descuentos` en agregados).
4. `StoreLiquidacionRequest`/update (nuevos campos).
5. `_form.blade.php` (inputs anticipo empresa/conductor + descuentos), `liquidacion-form.js` (estado Alpine + saldo pendiente).
6. `show.blade.php` y `pdf.blade.php` (mostrar anticipos desglosados, descuentos como línea de totales, saldo pendiente).

### US3 — Eliminar peaje del viaje (P3)
1. Botón eliminar por fila en `_tolls-table.blade.php`; método Alpine `removeToll(i)` en `liquidacion-form.js`.
2. Verificar full-replace de `liquidacion_tolls` en `LiquidacionController::update`.

### US4 — Manifiesto PDF (P3)
1. (Columna ya creada en US2 o en su propia migración si se hace primero.)
2. Input `manifiesto_pdf` en `_form.blade.php`; validación en el Form Request.
3. Manejo de subida/reemplazo en `store`/`update` (guardar en `storage/app/manifiestos`, borrar anterior).
4. Rutas `liquidaciones.manifiesto` (ver/descargar) y `liquidaciones.manifiesto.destroy`.
5. Enlaces en `show.blade.php` (ver/descargar/eliminar manifiesto).

## Validación manual (mapeo a Success Criteria)

| Paso | Verifica |
|---|---|
| Crear gasto mensual (conductor + 7 valores), <1 min | SC-001 |
| Filtrar lista por placa → solo esa placa | SC-002 |
| Cargar lista con ≥500 registros (seeder) → paginada, fluida | SC-003 |
| Login como usuario `placas` → NO ve botón ni ruta de gastos (403) | FR-009 / Q1=A |
| Liquidación: capturar anticipo empresa, anticipo conductor, descuentos → saldo pendiente = empresa − descuentos | SC-004 |
| Ver descuentos como línea en totales (pantalla y PDF) | SC-005 |
| Eliminar fila de peaje → totales recalculan sin recargar (<5s); catálogo de ruta intacto | SC-006 / FR-017 |
| Subir PDF de manifiesto → reabrir/descargar | SC-007 |
| Subir un archivo no-PDF → rechazado con mensaje | SC-008 |

## Tests automatizados

> **Importante**: las feature tests requieren **MySQL real**, no sqlite `:memory:` — el historial de migraciones del proyecto falla al migrar en sqlite. Configurar `phpunit.xml` / `.env.testing` a una base MySQL de pruebas.

```powershell
php artisan test --filter=MonthlyExpense
php artisan test --filter=Liquidacion
```

Cobertura objetivo:
- `MonthlyExpenseCrudTest` — crear/editar/eliminar, filtro por placa, paginación, unicidad conductor+período.
- `MonthlyExpenseAdminOnlyTest` — `placas` recibe 403 en todas las rutas de gastos; admin OK.
- `LiquidacionAnticiposDescuentosTest` — saldo pendiente = anticipo_empresa − descuentos; total_anticipos = empresa + conductor; descuentos en agregados.
- `LiquidacionTollDeleteTest` — quitar peaje del payload borra la fila y recalcula `sumatoria_peajes`; `route_tolls` intacto.
- `LiquidacionManifiestoPdfTest` — subir PDF asocia archivo; ver/descargar; rechazar no-PDF; eliminar limpia la columna; reemplazo borra el anterior.

## Rollback

```powershell
php artisan migrate:rollback --step=2   # revierte ajustes de liquidaciones + monthly_expenses
```
El `down()` del rename usa `ALTER TABLE ... CHANGE` inverso (anticipo_empresa→anticipo, anticipo_conductor→sobreanticipo) y `dropColumn` de las nuevas. Borrar manualmente `storage/app/manifiestos` si se desea limpieza total.
