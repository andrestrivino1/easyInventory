# HTTP Contracts — Informes y Analítica de Liquidaciones de Viajes

Endpoints nuevos, **2 en total**, dentro del grupo existente
`Route::middleware(['auth','can:liquidaciones.access'])->prefix('liquidaciones')->name('liquidaciones.')`,
con una capa adicional `can:liquidaciones.reportes.access` (admin-only).

> Importante de orden de rutas: declarar estas rutas **antes** del wildcard `{liquidacion}` del resource (igual que `gastos`), para que `liquidaciones/reportes` no sea capturado como `liquidaciones/{liquidacion}`.

---

## Gate

```php
// AppServiceProvider::boot()
Gate::define('liquidaciones.reportes.access', fn ($user) => $user->rol === 'admin');
```

---

## 1. Dashboard del informe (HTML)

```
GET /liquidaciones/reportes
name: liquidaciones.reportes.index
middleware: auth, can:liquidaciones.access, can:liquidaciones.reportes.access
controller: LiquidacionReportController@index
```

**Query params** (todos opcionales; sin `tipo` se usa el mes actual por defecto):

| Param | Tipo | Notas |
|---|---|---|
| `tipo` | `mes\|semestre\|anio` | Default `mes`. |
| `anio` | int | Default año actual. |
| `mes` | int 1–12 | Requerido si `tipo=mes`. |
| `semestre` | int 1–2 | Requerido si `tipo=semestre`. |
| `driver_id` | int | Opcional. Filtra todo el informe a un conductor. |

**Respuesta**: vista `liquidaciones.reportes.index` con:
`resumen` (ResumenPeriodo), `categorias` (DesgloseCategorias), `gastosFijos` (DesgloseGastosFijos), `evolucion` (EvolucionMensual + mejor/peor mes), `porConductor` (opcional, lista DesgloseConductor cuando no se filtra a uno solo), `drivers` (para el selector), `filtros` (eco de la selección).

**Errores**:
- No autenticado → 302 a login.
- Autenticado no-admin → **403** (gate `liquidaciones.reportes.access`).
- Periodo sin datos → **200** con estado vacío (totales en 0), no error.

---

## 2. Exportación a PDF

```
POST /liquidaciones/reportes/pdf
name: liquidaciones.reportes.pdf
middleware: auth, can:liquidaciones.access, can:liquidaciones.reportes.access
controller: LiquidacionReportController@pdf
```

**Por qué POST**: el cliente envía las gráficas ya renderizadas por Chart.js como imágenes PNG (data-URLs) para embeberlas en el PDF (DomPDF no ejecuta JS). Ver [research.md](../research.md#1-gráficas-dentro-del-pdf-dompdf-no-ejecuta-javascript).

**Body** (`application/x-www-form-urlencoded` o `multipart`):

| Campo | Tipo | Notas |
|---|---|---|
| `_token` | string | CSRF (estándar Laravel). |
| `tipo`, `anio`, `mes`, `semestre`, `driver_id` | — | Mismos que el dashboard; reconstruyen el mismo periodo/alcance. |
| `charts[evolucion]` | string (data-URL PNG) | Opcional. Gráfica de evolución mensual. |
| `charts[categorias]` | string (data-URL PNG) | Opcional. Gráfica de desglose por categoría. |

**Respuesta**: `200` con `Content-Type: application/pdf` y `Content-Disposition: attachment; filename="informe-liquidaciones-<periodo>[-<placa>].pdf"`.
El PDF contiene: encabezado con periodo (y conductor/placa si aplica), tabla de ingresos/gastos por concepto, desglose por categoría, desglose de los 7 gastos fijos, utilidad neta con su signo (ganancia/pérdida) y —si llegaron— las imágenes de las gráficas. Si no llegan imágenes, se omiten esas y se conservan **todas las tablas** (fallback).

**Invariante**: los totales del PDF == los del dashboard para el mismo `(tipo, anio, mes/semestre, driver_id)` — SC-004.

**Errores**: igual que el dashboard (403 a no-admin; 200 con ceros si el periodo está vacío).

---

## Matriz de autorización

| Rol | `GET reportes` | `POST reportes/pdf` |
|---|---|---|
| `admin` | ✅ 200 | ✅ 200 (PDF) |
| `placas` | ❌ 403 | ❌ 403 |
| `clientes` / `cliente_funcionario` | ❌ 403 | ❌ 403 |
| `importer` / `funcionario` / otros | ❌ 403 | ❌ 403 |
| invitado | 302 login | 302 login |

> `placas` pasa `can:liquidaciones.access` (puede ver el listado de sus conductores) pero **no** `can:liquidaciones.reportes.access`, por lo que el informe le devuelve 403. Esto materializa FR-001 / SC-006.

---

## Navegación

`resources/views/layouts/app.blade.php`: añadir el enlace **"Informes"** (icono `bi bi-bar-chart-line` o similar) apuntando a `liquidaciones.reportes.index`, visible **solo** cuando `auth()->user()->rol === 'admin'` (junto al enlace existente "Liquidación de Viajes").
