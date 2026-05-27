# Phase 1 — Data Model: Rol "placas"

Esta feature introduce **una sola tabla nueva** (pivote) y **ninguna columna nueva** en tablas existentes. El modelo de dominio de liquidaciones permanece intacto; el rol y el scoping se apoyan en datos ya existentes (`users.rol`, `drivers`, `liquidaciones.driver_id`).

## Entidades

### `users` (existente — sin cambios de esquema)

- Campo relevante: `rol` (string). Se admite un nuevo valor lógico: `'placas'`.
- Valores válidos tras esta feature: `admin`, `clientes`, `funcionario`, `importer`, `import_viewer`, `proveedor_itr`, **`placas`**.
- Un usuario `placas` **no** usa `almacen_id` ni la relación `almacenes` (FR-007): `almacen_id = null`, sin filas en `user_warehouse`.

### `drivers` (existente — sin cambios de esquema)

- Maestro de conductores ya usado por liquidaciones (identificado por `vehicle_plate`). Campos relevantes para esta feature: `id`, `name`, `vehicle_plate`, `active`.
- Es el eje del alcance del rol `placas`.

### `liquidaciones` (existente — sin cambios de esquema)

- Campo relevante: `driver_id` (FK → `drivers.id`). Determina la visibilidad de la liquidación para un usuario `placas`: visible/operable **solo si** `driver_id ∈ conductores asignados al usuario`.

### `user_driver` (NUEVA — tabla pivote)

Relación muchos-a-muchos entre `users` y `drivers` (asignación de conductores a usuarios `placas`). Sigue la convención del proyecto (`user_warehouse`).

| Columna | Tipo | Reglas |
|---|---|---|
| `id` | `bigIncrements` (PK) | — |
| `user_id` | `unsignedBigInteger`, FK → `users.id` | `onDelete('cascade')` |
| `driver_id` | `unsignedBigInteger`, FK → `drivers.id` | `onDelete('cascade')` |
| `created_at` / `updated_at` | `timestamps` | `withTimestamps()` en la relación |

**Índices / restricciones**:
- `unique(['user_id','driver_id'])` — impide asignaciones duplicadas.
- FKs con `cascade` — al eliminar un usuario o un conductor se eliminan sus asignaciones (sin filas huérfanas).

**Notas**:
- La asignación es **compartida** (FR-005): un mismo `driver_id` puede aparecer con distintos `user_id`. El unique es sobre el par, no sobre `driver_id` solo.
- Aunque la tabla solo se puebla para usuarios con `rol = 'placas'`, el esquema no fuerza el rol a nivel de BD; la regla de negocio vive en `UserController` (solo se sincronizan conductores cuando el rol es `placas`).

#### Migración propuesta

`database/migrations/2026_05_23_000000_create_user_driver_table.php`

```php
Schema::create('user_driver', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['user_id', 'driver_id']);
});
```

## Relaciones (Eloquent)

### `App\Models\User`

```php
public function assignedDrivers()
{
    return $this->belongsToMany(\App\Models\Driver::class, 'user_driver')->withTimestamps();
}

public function isPlacas(): bool
{
    return $this->rol === 'placas';
}

/** IDs de conductores asignados (para whereIn / Rule::in). */
public function assignedDriverIds(): array
{
    return $this->assignedDrivers()->pluck('drivers.id')->all();
}
```

### `App\Models\Driver` (inverso, opcional)

```php
public function placasUsers()
{
    return $this->belongsToMany(\App\Models\User::class, 'user_driver')->withTimestamps();
}
```

> Nota: `Driver` tiene `public $timestamps = false`, pero el pivote `user_driver` sí tiene timestamps; `withTimestamps()` aplica a la tabla pivote, no al modelo `Driver`, por lo que no hay conflicto.

## Reglas de validación (Form Requests / Controllers)

### Creación/edición de usuario (`UserController`)

- `rol` ∈ `{admin, clientes, funcionario, importer, import_viewer, proveedor_itr, placas}` (regla `in:`).
- Cuando `rol === 'placas'`:
  - `drivers` → `required|array|min:1`
  - `drivers.*` → `exists:drivers,id`
- Al persistir con `rol === 'placas'`: `$usuario->assignedDrivers()->sync($request->drivers)`.
- Al actualizar a un rol distinto de `placas`: `$usuario->assignedDrivers()->detach()`.

### Crear/editar liquidación (`StoreLiquidacionRequest` / `UpdateLiquidacionRequest`)

- Si el usuario autenticado es `placas`: `driver_id` → `required|integer|` + `Rule::in($user->assignedDriverIds())` (refuerza el `exists` existente para impedir conductores fuera de alcance).
- Para `admin`: regla `driver_id` sin restricción de pertenencia (comportamiento actual).

## Reglas de autorización (resumen por estado)

`LiquidacionPolicy` para un usuario `placas` (con `owns = driver_id ∈ assignedDriverIds`):

| Acción | Condición para `placas` |
|---|---|
| `viewAny` | permitido (scoping en consulta) |
| `view` | `owns` |
| `create` | permitido (conductor validado en Form Request) |
| `update` | `owns` **y** `liquidacion.isBorrador()` |
| `delete` | `owns` **y** `liquidacion.isBorrador()` |
| `close` | `owns` **y** `liquidacion.isBorrador()` |
| `reopen` | `owns` **y** `liquidacion.isCerrada()` |
| `cancel` | `owns` **y** `liquidacion.isCerrada()` |
| `downloadPdf` | `owns` |

`admin` mantiene bypass total vía `before()`. Roles distintos a `admin`/`placas` siguen bloqueados (`before()` devuelve `false`).

## Estados y transiciones

Sin cambios. El ciclo `Borrador → Cerrada → (Reabrir→Borrador | Anular→Anulada)` y los motivos obligatorios (min 10 chars) los gestiona `LiquidacionStateMachine` y se reutilizan tal cual; el rol `placas` solo añade la verificación de propiedad por conductor antes de invocarlos.

## Integridad y ciclo de vida de datos

- **Eliminar un usuario `placas`** → se borran sus filas en `user_driver` (cascade). Las liquidaciones que creó **no** se borran; quedan atribuidas a su `created_by` (histórico preservado).
- **Eliminar/desactivar un conductor** → al eliminarse, se borran sus asignaciones (cascade); al desactivarse (`active = 0`) deja de ofrecerse para nuevas liquidaciones, pero las históricas siguen visibles para sus `placas` asignados.
- **Desasignar un conductor de un `placas`** → ese usuario deja de ver/operar las liquidaciones de ese conductor (incluidas las que él creó), por scoping y policy.
