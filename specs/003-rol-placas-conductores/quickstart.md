# Quickstart — Rol "placas" con conductores asignados

Cómo levantar, migrar, sembrar datos de prueba y validar la feature de extremo a extremo. Entorno: Laravel 8 sobre XAMPP (Apache + MySQL/MariaDB), Windows. Comandos en PowerShell desde la raíz del repo (`c:\xampp\htdocs\easy_inventory`).

## 1. Preparar el entorno

```powershell
# Servicios: iniciar Apache y MySQL desde el panel de XAMPP (o):
# C:\xampp\xampp_start.exe

# Dependencias (si hiciera falta)
composer install
npm install ; npm run build   # solo si se tocan assets del form de usuarios
```

## 2. Migrar la tabla pivote

```powershell
php artisan migrate
```

Debe crear la tabla `user_driver` (sin alterar `users`, `drivers`, `liquidaciones`, `liquidacion_routes`). Verificar:

```powershell
php artisan migrate:status
```

## 3. Crear datos de prueba (tinker)

```powershell
php artisan tinker
```

```php
use App\Models\User; use App\Models\Driver; use Illuminate\Support\Facades\Hash;

// Conductores de prueba (si no existen)
$d1 = Driver::firstOrCreate(['vehicle_plate' => 'ABC123'], ['name' => 'Conductor A', 'active' => 1]);
$d2 = Driver::firstOrCreate(['vehicle_plate' => 'DEF456'], ['name' => 'Conductor B', 'active' => 1]);
$d3 = Driver::firstOrCreate(['vehicle_plate' => 'GHI789'], ['name' => 'Conductor C (no asignado)', 'active' => 1]);

// Usuario placas con A y B asignados
$u = User::create([
    'nombre_completo' => 'Usuario Placas',
    'name' => 'placas@test.com',
    'email' => 'placas@test.com',
    'rol' => 'placas',
    'password' => Hash::make('secret123'),
]);
$u->assignedDrivers()->sync([$d1->id, $d2->id]);
$u->assignedDrivers()->pluck('name'); // => Conductor A, Conductor B
```

> Alternativa por UI: iniciar sesión como `admin` → **Usuarios → Nuevo** → rol **Placas** → seleccionar conductores → Guardar.

## 4. Validación funcional (por User Story)

### US1 — Crear/editar usuario placas y asignar conductores
1. Como `admin`, ir a **Usuarios → Nuevo**.
2. Elegir rol **Placas** ⇒ aparece el selector de conductores. Los campos de bodega **no** deben mostrarse.
3. Guardar sin seleccionar conductor ⇒ error "Debes seleccionar al menos un conductor".
4. Seleccionar A y B, guardar ⇒ usuario creado. En BD: `select * from user_driver where user_id = <id>` muestra 2 filas.
5. Editar el usuario, quitar B y dejar solo A ⇒ guardar ⇒ `user_driver` queda con 1 fila.
6. Asignar el conductor A a un segundo usuario placas ⇒ permitido (asignación compartida).

### US2 — Aislamiento y visibilidad limitada
1. Cerrar sesión e iniciar como `placas@test.com`.
2. Tras login, la URL debe ser `/liquidaciones` (redirección de `home`).
3. La navegación lateral muestra **solo** "Liquidación de Viajes" (sin Movimientos, Productos, Usuarios, Rutas, etc.).
4. Intentar por URL directa: `/products`, `/users`, `/drivers`, `/liquidaciones/rutas` ⇒ redirige a `/liquidaciones` con mensaje de acceso no autorizado.
5. En el listado, solo aparecen liquidaciones de A y B; el filtro "Conductor" solo ofrece A y B; el consolidado suma solo esas.
6. Abrir por URL directa una liquidación de C (`/liquidaciones/{id_de_C}`) ⇒ `403`.

### US3 — Flujo operativo completo (conductores asignados)
1. **Crear**: Nueva liquidación → seleccionar conductor A → seleccionar una ruta existente (los peajes cargan vía AJAX) → guardar ⇒ queda en **Borrador**, atribuida al usuario placas (`created_by`).
2. **Editar** el borrador → guardar.
3. **Cerrar** ⇒ estado **Cerrada**.
4. **Reabrir** (motivo ≥ 10 chars) ⇒ vuelve a **Borrador**; el motivo queda en el historial.
5. **Anular** (desde Cerrada, motivo) ⇒ estado **Anulada**.
6. **PDF**: descargar el PDF de una liquidación de A ⇒ se genera.
7. **Manipulación**: hacer POST a `store` con `driver_id` de C (p. ej. desde la consola del navegador) ⇒ rechazado (`422`).
8. **Catálogo de rutas**: confirmar que no hay botón "Rutas" y que `/liquidaciones/rutas/create` redirige.

### admin sin cambios
1. Iniciar como `admin`: ve todas las liquidaciones (A, B y C), gestiona el catálogo de rutas y los usuarios, sin restricción.

## 5. Pruebas automatizadas

```powershell
php artisan test --filter=Placas
```

Cubren:
- `PlacasUserManagementTest` — alta/edición + `sync` de conductores + asignación compartida (US1).
- `PlacasAccessIsolationTest` — confinamiento al módulo (nav + URL directa) y redirección de `home` (US2).
- `PlacasScopingTest` — listado/consolidado/selectores filtrados; `403` cross-driver (US2).
- `PlacasFullFlowTest` — crear→editar→cerrar→reabrir→anular→PDF sobre conductor asignado (US3).
- `PlacasRouteCatalogBlockedTest` — catálogo de rutas bloqueado; AJAX de peajes permitido (FR-018).

Para correr toda la suite del módulo y verificar que `admin` no se ve afectado:

```powershell
php artisan test --filter=Liquidacion
```

## 6. Checklist de aceptación (rápido)

- [ ] `php artisan migrate` crea `user_driver`; las tablas existentes no cambian.
- [ ] Admin puede crear un usuario `placas` con ≥1 conductor en un solo formulario (SC-003).
- [ ] `placas` solo ve el módulo de Liquidación de Viajes; el resto redirige (SC-001).
- [ ] `placas` solo ve/opera liquidaciones de sus conductores; 0% de fuga (SC-002).
- [ ] Intentos de actuar fuera de alcance se bloquean siempre (`403`/`422`) (SC-005).
- [ ] Consolidado de `placas` = suma de sus conductores asignados (SC-006).
- [ ] `placas` completa el flujo crear→cerrar sin ayuda de admin (SC-004).
- [ ] `admin` mantiene acceso total (FR-021); demás roles sin cambios (FR-022).
- [ ] `php artisan test --filter=Placas` en verde.
