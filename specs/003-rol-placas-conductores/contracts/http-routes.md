# Phase 1 — Contratos HTTP: Rol "placas"

Esta feature **no agrega endpoints nuevos**. Define cómo se comporta el rol `placas` frente a las rutas existentes. El contrato es, en esencia, una **matriz de autorización** y las respuestas esperadas ante acceso permitido/denegado.

## Capas de control (orden de evaluación por request)

1. **`auth`** (middleware) — debe estar autenticado.
2. **`BlockImporterAccess`** (middleware del grupo `web`) — si `rol === 'placas'` y la ruta no está en la allow-list → `302` redirect a `liquidaciones.index` con mensaje de error.
3. **`can:liquidaciones.access`** (gate, solo en el grupo `liquidaciones`) — `admin` o `placas` pasan; otros → `403`.
4. **`LiquidacionPolicy`** (por modelo, en métodos del controlador) — verifica propiedad por conductor + reglas de estado; falla → `403`.
5. **Form Request** (`Store/UpdateLiquidacionRequest`) — `driver_id` debe pertenecer a los conductores asignados; falla → `422` (redirect con errores).

## Allow-list del middleware para `placas`

Rutas permitidas (por **nombre**) cuando `rol === 'placas'`:

```
liquidaciones.index
liquidaciones.create
liquidaciones.store
liquidaciones.show
liquidaciones.edit
liquidaciones.update
liquidaciones.destroy
liquidaciones.cerrar
liquidaciones.reabrir
liquidaciones.anular
liquidaciones.pdf
liquidaciones.drivers.info        # AJAX: info del conductor para prellenar el form
liquidaciones.duplicate-check     # AJAX: aviso de placa+mfto duplicado
liquidaciones.routes.peajes       # AJAX: peajes de una ruta seleccionada (solo lectura)
language.switch
home
logout
```

Paths permitidos (fallback): `home`, `logout`, `language` (y `/`). **No** se incluye el path `liquidaciones`, de modo que las rutas del catálogo de rutas que no estén en la allow-list por nombre quedan bloqueadas.

Cualquier ruta fuera de esta lista (p. ej. `products.*`, `users.*`, `drivers.*`, `imports.*`, `itrs.*`, `stock.*`, `liquidaciones.routes.index|create|store|show|edit|update|destroy|toggle-active`) → **redirect 302** a `liquidaciones.index`.

## Matriz de autorización por endpoint

Leyenda: ✅ permitido · ⛔ bloqueado (middleware 302) · 🔒 permitido solo si el conductor de la liquidación está asignado (si no, 403 por policy) · 422 validación.

| Método | URI | Nombre | admin | placas |
|---|---|---|---|---|
| GET | `/liquidaciones` | `liquidaciones.index` | ✅ todas | ✅ **solo liquidaciones de conductores asignados** (listado + consolidado + selectores filtrados) |
| GET | `/liquidaciones/create` | `liquidaciones.create` | ✅ | ✅ (selector de conductor limitado a asignados) |
| POST | `/liquidaciones` | `liquidaciones.store` | ✅ | ✅ — `driver_id` debe ser asignado, si no **422** |
| GET | `/liquidaciones/{liquidacion}` | `liquidaciones.show` | ✅ | 🔒 |
| GET | `/liquidaciones/{liquidacion}/edit` | `liquidaciones.edit` | ✅ (solo Borrador) | 🔒 + solo Borrador |
| PUT | `/liquidaciones/{liquidacion}` | `liquidaciones.update` | ✅ (solo Borrador) | 🔒 + solo Borrador — `driver_id` asignado, si no **422** |
| DELETE | `/liquidaciones/{liquidacion}` | `liquidaciones.destroy` | ✅ (solo Borrador) | 🔒 + solo Borrador |
| POST | `/liquidaciones/{liquidacion}/cerrar` | `liquidaciones.cerrar` | ✅ (Borrador→Cerrada) | 🔒 + Borrador |
| POST | `/liquidaciones/{liquidacion}/reabrir` | `liquidaciones.reabrir` | ✅ (Cerrada→Borrador, motivo) | 🔒 + Cerrada, motivo |
| POST | `/liquidaciones/{liquidacion}/anular` | `liquidaciones.anular` | ✅ (Cerrada→Anulada, motivo) | 🔒 + Cerrada, motivo |
| GET | `/liquidaciones/{liquidacion}/pdf` | `liquidaciones.pdf` | ✅ | 🔒 |
| GET | `/liquidaciones/drivers/{driver}/info` | `liquidaciones.drivers.info` | ✅ | ✅ (recomendado limitar a asignados) |
| GET | `/liquidaciones/duplicate-check` | `liquidaciones.duplicate-check` | ✅ | ✅ |
| GET | `/liquidaciones/rutas/{route}/peajes` | `liquidaciones.routes.peajes` | ✅ | ✅ (solo lectura, para armar la liquidación) |
| GET | `/liquidaciones/rutas` | `liquidaciones.routes.index` | ✅ | ⛔ |
| GET | `/liquidaciones/rutas/create` | `liquidaciones.routes.create` | ✅ | ⛔ |
| POST | `/liquidaciones/rutas` | `liquidaciones.routes.store` | ✅ | ⛔ |
| GET | `/liquidaciones/rutas/{route}` | `liquidaciones.routes.show` | ✅ | ⛔ |
| GET | `/liquidaciones/rutas/{route}/edit` | `liquidaciones.routes.edit` | ✅ | ⛔ |
| PUT | `/liquidaciones/rutas/{route}` | `liquidaciones.routes.update` | ✅ | ⛔ |
| DELETE | `/liquidaciones/rutas/{route}` | `liquidaciones.routes.destroy` | ✅ | ⛔ |
| POST | `/liquidaciones/rutas/{route}/toggle-active` | `liquidaciones.routes.toggle-active` | ✅ | ⛔ |
| * | Cualquier otro módulo (`products`, `users`, `drivers`, `imports`, `itrs`, `stock`, …) | — | ✅ (según rol) | ⛔ redirect 302 |

## Endpoints de gestión de usuarios (solo `admin`)

El alta/edición de usuarios `placas` ocurre en el CRUD de usuarios existente, accesible **solo para admin** (constructor de `UserController` + `BlockImporterAccess` confina a `placas` fuera de `users.*`).

| Método | URI | Nombre | Cambio en esta feature |
|---|---|---|---|
| GET | `/users/create` | `users.create` | El form ofrece el rol "Placas"; al elegirlo, muestra el selector de conductores. `create()` pasa `$drivers`. |
| POST | `/users` | `users.store` | Valida `rol=placas` ⇒ `drivers` (`required|array|min:1`, `drivers.*` `exists:drivers,id`); `sync()` de `assignedDrivers`. |
| GET | `/users/{id}/edit` | `users.edit` | Preselecciona los conductores asignados. `edit()` pasa `$drivers` y `$usuario->assignedDrivers`. |
| PUT | `/users/{id}` | `users.update` | Igual que store; `detach()` si el rol deja de ser `placas`. |

### Contrato de request — `users.store` / `users.update` (rol placas)

```
nombre_completo : required, string, max:100
email           : required, email, unique (ignora propio en update)
telefono        : nullable, string, max:20
rol             : required, in:admin,clientes,funcionario,importer,import_viewer,proveedor_itr,placas
password        : required (store) | nullable (update), min:6, confirmed
drivers         : required, array, min:1            # solo cuando rol=placas
drivers.*       : exists:drivers,id                  # solo cuando rol=placas
```

**Respuestas**:
- Éxito → `302` redirect a `users.index` con `success`.
- Validación (sin conductores cuando rol=placas, o conductor inexistente) → `302 back` con errores (`422` lógico), preservando input.

## Casos de borde de contrato

- **`placas` accede por URL directa a `/liquidaciones/{id}` de un conductor no asignado** → `403` (policy `view`).
- **`placas` hace POST `store` con `driver_id` no asignado** (manipulación) → `422` (Form Request).
- **`placas` navega a `/users` o `/liquidaciones/rutas`** → `302` redirect a `liquidaciones.index`.
- **`placas` sin conductores asignados** → `liquidaciones.index` responde `200` con listado vacío y consolidado en cero; `create` no ofrece conductores (no puede guardar).
- **`admin`** → todas las filas anteriores responden como hoy, sin restricción por conductor (FR-021).
