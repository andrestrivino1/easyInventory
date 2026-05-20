# Quickstart — Liquidación de Viajes

**Branch**: `002-liquidacion-viajes` | **Date**: 2026-05-19

Guía para levantar, sembrar y validar la feature end-to-end en entorno local.

## Pre-requisitos

- Repositorio en branch `002-liquidacion-viajes`.
- XAMPP corriendo con Apache + MySQL.
- Node 24+ y npm 11+ (ya instalados, ver [research.md](research.md)).
- PHP 8.2.x en el PATH (verificable con `php --version`).
- `composer install` ya corrido (vendor presente).

## Comandos clave

```powershell
# 1) Instalar dependencias (idempotente)
composer install
npm install

# 2) Aplicar migraciones nuevas
php artisan migrate

# 3) Seed del catálogo de categorías de gastos
php artisan db:seed --class=ExpenseCategorySeeder

# 4) Build de assets (Vite)
npm run build           # producción, o:
npm run dev             # watcher en desarrollo

# 5) Levantar el server local (alternativa a XAMPP si se prefiere)
php artisan serve       # http://localhost:8000
```

## Flujo de validación manual

### Paso A — Crear ruta + peajes (US5)

1. Login como un usuario con `rol = 'admin'` (ej. `gerencia@vidriosjyp.com`).
2. Sidebar → "Liquidación de Viajes" → entrar a **Rutas** → "Nueva ruta".
3. Capturar:
   - Origen: `BUENAVENTURA`
   - Destino: `BOGOTÁ`
   - Descripción: (opcional)
4. En la sección "Peajes", agregar (en este orden) — ida:
   - Loboguerrero, 43300, ida
   - Betania, 56000, ida
   - Uribe, 56000, ida
   - Corozal, 58900, ida
   - Túnel de la Línea, 37900, ida
   - Gualanday, 57000, ida
   - Chicoral, 58000, ida
   - Chinauta, 61700, ida
   - Chusacá, 61700, ida
5. Agregar regreso (mismos peajes en orden inverso, valor de Corozal regreso = 47100, Lobo regreso = 43300):
   - Chusacá 61700, Chinauta 61700, Chicoral 58000, Gualanday 57000, Túnel 37900, Corozal 47100, Uribe 56000, Betania 56000, Loboguerrero 43300 (regreso)
6. Guardar. Verificar que aparece en el listado de rutas activa.

### Paso B — Crear liquidación (US1)

1. Sidebar → "Liquidación de Viajes" → **Liquidaciones** → "Nueva".
2. Seleccionar **Conductor**: cualquier driver activo del maestro. Verificar que la **placa** se auto-completa.
3. **Ruta**: seleccionar `BUENAVENTURA → BOGOTÁ`. Verificar que la **tabla de peajes** se llena con los 18 peajes capturados en el Paso A.
4. Cabecera:
   - Transporte: `PEREIRANA DE TRANSPO`
   - Anticipo: `3000000`
   - Sobreanticipo: `0`
   - Fecha inicio: `2026-03-02`, Fecha fin: `2026-03-05`
   - Número MFTO: cualquier valor
   - Teléfono empresa: (opcional)
   - Valor flete: `5600000`
5. Tabla de gastos — capturar:
   - ACPM: valor `1344636`, galones `120`
   - UREA: `134900`
   - COMISIÓN: `100000`
   - PORCENTAJE: `560000`
   - PARQUEADEROS: `11000`
   - LAVADA DEL CARRO: `70000`
   - EMBOLADA DE LLANTAS: `120000`
   - VARIOS: `30000`
   - Las demás vacías
6. Verificar **en pantalla en vivo** (sin guardar):
   - Sumatoria gastos operativos: `2.370.536`
   - Sumatoria peajes: `969.200`
   - Sumatoria gastos totales: `3.339.736`
   - Total anticipos: `3.000.000`
   - **Saldo viaje: `629.464`** (a favor de Empresa)
   - **Ganancia viaje: `2.260.264`**
7. Guardar (queda en `Borrador`).

### Paso C — Cerrar y validar PDF (US2)

1. Desde el detalle → botón "Cerrar". Estado pasa a `Cerrada`.
2. Botón "PDF" → descarga `liquidacion_<placa>_<fecha>.pdf`.
3. Abrir el PDF y verificar que los totales del PDF coincidan con los de pantalla.
4. Probar "Reabrir" con motivo "Validación quickstart" → vuelve a `Borrador`. Revisar pestaña/sección "Historial" muestra el log con quién, cuándo y motivo.
5. Volver a cerrar.

### Paso D — Listado + Consolidado (US3 + US4)

1. Crear al menos 3 liquidaciones más en distintas fechas (puede ser de la misma placa o conductor) para tener data para el consolidado.
2. Ir al listado `/liquidaciones`.
3. Aplicar filtro por **placa** = `QJZ957`. Verificar que el listado se reduce y el **panel de Consolidado del periodo** muestra Σ correctos.
4. Aplicar filtro de **rango de fechas** que abarque 2 meses. Activar **"Agrupar por mes"** → verificar que aparezcan sub-paneles, uno por mes.
5. Anular una liquidación → verificar que sigue visible en el listado con marca "ANULADA" pero **NO** suma en el consolidado.

### Paso E — Autorización (FR-015)

1. Logout. Login con un usuario con `rol = 'funcionario'`.
2. Intentar navegar a `/liquidaciones` directamente por URL → debe devolver 403 (Forbidden) o redirección con flash de "Acceso denegado".
3. Verificar que el ítem "Liquidación de Viajes" NO aparece en el sidebar para este rol.

## Validación automatizada

```powershell
# Correr todos los tests de la feature
php artisan test --filter=Liquidacion

# Tests específicos
php artisan test tests/Feature/LiquidacionCrudTest.php
php artisan test tests/Feature/LiquidacionConsolidadoTest.php
php artisan test tests/Unit/LiquidacionCalculatorTest.php
```

Los tests de Feature usan `RefreshDatabase` trait. Cada test:
- Crea un admin con `factory(User::class)->create(['rol' => 'admin'])`.
- Crea drivers, rutas, peajes según necesite.
- Ejerce el endpoint y verifica response + DB.

## Datos de muestra

El seeder `ExpenseCategorySeeder` deja las 16 categorías. Opcional: crear `LiquidacionSampleDataSeeder` (NO incluido en producción) que pre-llene la ruta del ejemplo + una liquidación con los datos del Excel original para demos.

## Troubleshooting

| Síntoma | Causa probable | Acción |
|---|---|---|
| `Class 'ExpenseCategory' not found` | Falta seeder de categorías | `php artisan db:seed --class=ExpenseCategorySeeder` |
| PDF descarga vacío o roto | dompdf no encuentra la view | Verificar `resources/views/liquidaciones/pdf.blade.php` existe |
| Cálculos no se actualizan en pantalla | Alpine.js no compiló | `npm run build` para producción, `npm run dev` en local |
| 403 al entrar siendo admin | Gate `liquidaciones.access` no registrado | Confirmar `AppServiceProvider::boot()` define el gate |
| Sidebar no muestra el módulo | Vista layout no actualizada | Verificar `resources/views/layouts/app.blade.php` agrega `<li>` condicional al rol admin |

## Criterios de aceptación del quickstart

Al completar Pasos A–E sin errores:

- ✅ US1 (CRUD liquidación) verificada.
- ✅ US2 (PDF) verificada.
- ✅ US3 (Listado + filtros + descarga PDF por fila) verificada.
- ✅ US4 (Consolidado mensual) verificada.
- ✅ US5 (Gestión de rutas) verificada.
- ✅ FR-001..FR-021 todas cubiertas por el flujo.
- ✅ SC-001 (capturar liquidación en <5min), SC-002 (<1s cálculo), SC-007 (<2s consolidado), SC-008 (totales 100% consistentes).
