# Quickstart: Rol "cliente funcionario"

Guía para configurar, ejecutar y validar manualmente la feature. **No requiere migraciones** (reutiliza `user_warehouse` y `containers.warehouse_id`).

## Prerrequisitos

- Entorno XAMPP en marcha (Apache + MySQL).
- Base de datos de desarrollo con al menos:
  - Una bodega que **recibe contenedores** (Buenaventura/Pablo Rojas) — ver `Warehouse::getBodegasQueRecibenContenedores()`.
  - Una bodega que **no** recibe contenedores (para validar la restricción de destino).
  - Algún contenedor existente en cada una (para validar el scoping).

## Sembrar un usuario `cliente_funcionario`

Por la interfaz (recomendado):

1. Iniciar sesión como `admin` → menú **Usuarios** → **Crear**.
2. Rol = **Cliente Funcionario**. El formulario muestra el grupo **Bodegas** (igual que para Clientes).
3. Seleccionar 1+ bodegas (incluir al menos una que reciba contenedores). Guardar.

Por tinker (alternativa rápida):

```php
php artisan tinker
$u = \App\Models\User::create([
    'nombre_completo' => 'Cliente Funcionario Demo',
    'name' => 'cf@demo.test',
    'email' => 'cf@demo.test',
    'password' => bcrypt('secret123'),
    'rol' => 'cliente_funcionario',
    'almacen_id' => null,
]);
$u->almacenes()->sync([/* IDs de bodegas asignadas */]);
```

## Validación manual (mapea a los User Stories)

### US1 — Gestión del usuario
- [ ] En el selector de rol aparece "Cliente Funcionario".
- [ ] Al elegirlo, se muestra el selector múltiple de **Bodegas** y exige ≥1.
- [ ] Tras guardar, el usuario queda con `rol = cliente_funcionario`, `almacen_id = NULL` y las bodegas en `user_warehouse`.
- [ ] Editar el usuario cambia la lista de bodegas y persiste.
- [ ] Cambiarlo a otro rol sin bodegas limpia `user_warehouse`.

### US2 — Paridad con `clientes`
- [ ] La navegación muestra los mismos módulos que vería un `clientes` con las mismas bodegas, **más** "Contenedores".
- [ ] En Productos, Transferencias, Salidas, Stock y Trazabilidad solo ve datos de sus bodegas asignadas.
- [ ] Acceso por URL a `warehouses`, `imports`, `itrs`, `liquidaciones`, `users` → denegado (igual que `clientes`).
- [ ] Comparar lado a lado con un usuario `clientes` de control con las mismas bodegas: mismos módulos, mismos datos.

### US3 — Contenedores
- [ ] El listado de Contenedores muestra **solo** contenedores de bodegas asignadas (no los de otras bodegas).
- [ ] **Crear**: el selector de bodega ofrece solo bodegas asignadas que reciben contenedores; crear funciona.
- [ ] Intentar crear apuntando (por manipulación) a una bodega no asignada → rechazado.
- [ ] **Editar** un contenedor de bodega asignada → OK; **Exportar**/**Imprimir** → OK.
- [ ] Abrir por URL directa el `edit`/`export` de un contenedor de bodega **no** asignada → redirige a `containers.index` con error.
- [ ] El botón **Eliminar** no aparece; un `DELETE` directo a `containers.destroy` → rechazado.

### No-regresión
- [ ] Un usuario `funcionario` sigue viendo Contenedores en **solo lectura** (sin crear/editar/eliminar).
- [ ] Un usuario `clientes` sigue **sin** ver ni acceder a Contenedores.
- [ ] Un usuario `admin` mantiene acceso total a todos los contenedores.

## Pruebas automatizadas

```bash
# Feature tests de la feature (MySQL easy_inventory_test)
php artisan test --filter=ClienteFuncionario
```

Tests esperados:
- `ClienteFuncionarioUserManagementTest` (US1)
- `ClienteFuncionarioInheritsClienteScopeTest` (US2)
- `ClienteFuncionarioContainersTest` (US3)

> Nota: según la memoria del proyecto, los Feature tests corren sobre MySQL `easy_inventory_test`; los tests de Breeze Auth/Example fallan de forma pre-existente y no cuentan como regresión.
