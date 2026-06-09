# Implementation Plan: Rol "cliente funcionario" (clientes + módulo Contenedores)

**Branch**: `006-rol-cliente-funcionario` | **Date**: 2026-06-02 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from [/specs/006-rol-cliente-funcionario/spec.md](spec.md)

## Summary

Agregar al monolito Laravel existente un nuevo valor de rol `cliente_funcionario` que **hereda íntegramente el alcance del rol `clientes`** (módulos Movimientos, Productos, Transferencias, Salidas, Stock, Trazabilidad, todos acotados a sus bodegas asignadas) y le suma **una sola diferencia**: acceso operativo al módulo de **Contenedores**, donde puede ver, crear, editar, exportar e imprimir contenedores —**sin eliminar**—, limitado a sus **bodegas asignadas**.

El alcance se logra **sin tablas nuevas**: el rol reutiliza la relación muchos-a-muchos existente `user_warehouse` (`almacenes`) que ya usa `clientes`. El trabajo se reduce a tres frentes:

1. **Registro del rol**: agregar `cliente_funcionario` a las reglas de validación y al formulario de usuarios, tratándolo exactamente como `clientes` en la sincronización de bodegas (`almacenes()->sync(...)`).
2. **Herencia del alcance `clientes`**: introducir un helper `User::isCliente()` que devuelve `true` para `clientes` **y** `cliente_funcionario`, y reemplazar con él los chequeos de scoping `=== 'clientes'` dispersos en controladores y vistas, de modo que el nuevo rol se comporte de forma idéntica a `clientes` en todos los módulos heredados.
3. **Diferencia en Contenedores**: habilitar la entrada al módulo (navegación + acceso) para el nuevo rol y aplicar, dentro de `ContainerController`, scoping por bodegas asignadas, restricción del destino de creación a `bodegas asignadas ∩ bodegas que reciben contenedores`, y bloqueo de la eliminación (defensa en profundidad ante acceso directo por URL).

No se altera el comportamiento de `admin` (acceso total), `clientes` (sin Contenedores) ni `funcionario` (solo lectura en Contenedores).

## Technical Context

**Language/Version**: PHP 8.2.12 (cumple `composer.json` `^7.4 || ^8.0`); JavaScript ES2022 (vanilla, en el form de usuarios).
**Primary Dependencies**: Laravel Framework 8.75, Eloquent ORM, Blade. Sin dependencias nuevas. (`barryvdh/laravel-dompdf` ya se usa para export/print de contenedores; esta feature no toca esa capa, solo añade el guard de acceso.)
**Storage**: MySQL/MariaDB vía XAMPP (InnoDB, `utf8mb4_unicode_ci`). **Cero migraciones nuevas**: se reutiliza la pivote existente `user_warehouse` y la columna `containers.warehouse_id`.
**Testing**: PHPUnit 9.5 (Feature). Los Feature tests corren sobre MySQL `easy_inventory_test` (ver memoria del proyecto). Tests por cada User Story: gestión del usuario, herencia de alcance clientes, y operación + scoping + bloqueo de borrado en Contenedores.
**Target Platform**: Web monolítica Laravel (Apache/XAMPP en dev).
**Project Type**: Web monolítica — extensión del repo existente (modelo, controladores, vistas, navegación). Ningún proyecto nuevo.
**Performance Goals**:
- Listado de Contenedores para `cliente_funcionario` con scoping: comparable a admin; el filtro `whereIn('warehouse_id', $asignadas)` usa el índice de FK existente sobre `containers.warehouse_id`.
- Resolución de "bodegas asignadas" del usuario: 1 consulta por request (relación `belongsToMany almacenes`), idéntica a la ya usada por `clientes`.
**Constraints**:
- Cero modificación de esquema (sin migraciones); se reutiliza `user_warehouse` y `containers.warehouse_id`.
- `cliente_funcionario` MUST comportarse igual que `clientes` en los módulos heredados (SC-002).
- `cliente_funcionario` MUST ver/operar solo contenedores de sus bodegas asignadas (0% de fuga — SC-004).
- `cliente_funcionario` NO MUST eliminar contenedores; el 100% de los intentos de borrado son rechazados (SC-005).
- `admin`, `clientes` y `funcionario` conservan su comportamiento actual e inalterado (FR-017, FR-018, SC-006).
**Scale/Scope**:
- 1–20 usuarios `cliente_funcionario` estimados; cada uno con 1–N bodegas asignadas.
- Reutiliza el módulo de contenedores existente (~7 endpoints); agrega 0 endpoints HTTP nuevos.
- Cambios puntuales en ~10–14 archivos existentes (controladores con scoping clientes + vistas + form usuarios + navegación) + helper en `User` + tests. Sin migraciones.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Status**: La constitución del proyecto (`/.specify/memory/constitution.md`) está en estado **template** — no hay principios ratificados (placeholders sin reemplazar). No existen gates formales que aplicar.

**Acción**: Aplicar como guía implícita las convenciones ya presentes en el codebase:

| Práctica del codebase | Aplicación en esta feature |
|---|---|
| Roles como string en `users.rol` (sin Spatie) | Sí — `cliente_funcionario` es un nuevo valor del string `rol`. Sin paquete de permisos. |
| Scoping de clientes por bodegas vía relación `almacenes` (`belongsToMany user_warehouse`) y checks `=== 'clientes'` en controladores/vistas | Sí — se reutiliza la misma relación; se introduce `User::isCliente()` para incluir al nuevo rol en cada check de scoping, sin duplicar lógica. |
| Acceso a módulos por rol mayormente vía navegación (`layouts/app.blade.php`) + scoping per-controller (clientes **no** está en `BlockImporterAccess`) | Sí — se sigue el mismo modelo: navegación condicionada por flag de rol + guards inline en `ContainerController`. No se agrega rama en `BlockImporterAccess`. |
| Guards de rol inline en `ContainerController` (`if ($user->rol === 'funcionario') ...`) | Sí — se agregan guards inline análogos para `cliente_funcionario` (bloqueo de borrado + scoping por bodega), en lugar de introducir una Policy nueva, por consistencia con el módulo existente. |
| Formulario de usuarios con campos dinámicos por rol (JS `toggle`) y `almacenes()->sync()` | Sí — `cliente_funcionario` se trata igual que `clientes`: muestra el grupo "Bodegas" y sincroniza `almacenes`. |
| Redirección de `home` por rol en `HomeController` | No aplica — `clientes` no se redirige; va al dashboard normal. `cliente_funcionario` hereda ese comportamiento (sin redirección especial). |

**Gates**: Ninguno bloqueante. Pasar a Phase 0.

## Project Structure

### Documentation (this feature)

```text
specs/006-rol-cliente-funcionario/
├── plan.md                       # Este archivo
├── spec.md                       # Spec funcional
├── research.md                   # Phase 0: decisiones técnicas
├── data-model.md                 # Phase 1: rol, relación de bodegas, reglas de scoping de contenedores
├── contracts/
│   └── http-routes.md            # Phase 1: matriz de autorización por endpoint de Contenedores para el nuevo rol
├── quickstart.md                 # Phase 1: cómo sembrar el rol y validar el flujo
├── checklists/
│   └── requirements.md           # Checklist de calidad del spec (ya creado)
└── tasks.md                      # /speckit-tasks (NO se crea en este comando)
```

### Source Code (repository root — Laravel monolito, extensión)

Archivos **nuevos**:

```text
tests/
└── Feature/
    ├── ClienteFuncionarioUserManagementTest.php   # US1: crear/editar usuario + asignar bodegas; cambio de rol limpia/establece relaciones
    ├── ClienteFuncionarioInheritsClienteScopeTest.php  # US2: mismo alcance que clientes (módulos visibles + datos por bodega + módulos negados)
    └── ClienteFuncionarioContainersTest.php       # US3: nav + scoping por bodega + crear/editar/export/print + borrado bloqueado + acceso cruzado denegado
```

Archivos **modificados** (cambios puntuales):

```text
app/
├── Models/
│   └── User.php                              # + helpers isCliente() (clientes|cliente_funcionario), isClienteFuncionario(), assignedWarehouseIds()
└── Http/
    └── Controllers/
        ├── UserController.php                # + 'cliente_funcionario' en reglas in:; tratarlo como clientes en sync de almacenes y limpieza al cambiar de rol
        ├── ContainerController.php           # scoping index() por bodegas asignadas; guards en create/store/edit/update/export/print; bloqueo de destroy() para el rol
        ├── WelcomeController.php             # reemplazar `=== 'clientes'` por isCliente() en el dashboard
        ├── StockController.php               # idem: scoping de stock por bodegas asignadas incluye el nuevo rol
        ├── SalidaController.php              # idem: scoping de salidas
        ├── TransferOrderController.php       # idem: scoping y reglas de transferencias
        └── TraceabilityController.php        # idem: scoping de trazabilidad

resources/
└── views/
    ├── layouts/app.blade.php                 # + $isClienteFuncionario; mostrar enlace "Contenedores" para el nuevo rol (manteniendo el resto del menú de clientes)
    ├── users/
    │   ├── create.blade.php                  # + opción de rol "Cliente Funcionario"; JS toggle lo trata como clientes (grupo Bodegas + validación)
    │   └── edit.blade.php                    # idem + preselección de bodegas asignadas
    └── containers/
        ├── index.blade.php                   # ocultar el botón "Eliminar" para el nuevo rol (botón visible solo a quien puede borrar)
        ├── create.blade.php                  # (si aplica) limitar el selector de bodega a las asignadas que reciben contenedores
        └── edit.blade.php                    # idem
```

> Nota: la lista exacta de archivos con checks `=== 'clientes'` a migrar al helper se enumera de forma exhaustiva en [research.md](research.md) y se materializa en `/speckit-tasks`. Vistas con scoping de clientes (`stock`, `traceability`, `products`, `transfer-orders`, `salidas`) se revisan para sustituir el check por el helper donde gobierne visibilidad/datos del cliente.

**Structure Decision**: Extensión del monolito Laravel existente, sin sub-proyectos. La estrategia de autorización reproduce el modelo **ya usado para `clientes`** (no el de `placas`):

1. **Herencia por helper**: `User::isCliente()` centraliza la pertenencia al "alcance cliente" e incluye al nuevo rol, evitando duplicar cada rama de scoping. Es el mecanismo que garantiza SC-002 (paridad con `clientes`) con el mínimo de cambios y sin divergencia futura.
2. **Navegación condicionada**: `layouts/app.blade.php` muestra a `cliente_funcionario` el mismo menú de `clientes` **más** el enlace de Contenedores (flag `$isClienteFuncionario`).
3. **Guards inline en `ContainerController`** (defensa en profundidad): cada acción verifica que el contenedor pertenezca a una bodega asignada y bloquea el borrado, cubriendo el acceso directo por URL/parámetros (SC-004, SC-005). Se mantiene el estilo inline del controlador existente en lugar de introducir una Policy.

## Complexity Tracking

No hay violaciones de constitución (constitución en template). Puntos de complejidad inherentes y su justificación:

| Decisión | Por qué | Alternativa más simple rechazada porque |
|---|---|---|
| Helper `User::isCliente()` y refactor de los checks `=== 'clientes'` dispersos | El nuevo rol debe ser idéntico a `clientes` en todos los módulos heredados; centralizar el predicado evita olvidar un sitio y garantiza paridad (SC-002). | Duplicar `|| $rol === 'cliente_funcionario'` en cada sitio es frágil y propenso a omisiones; un nuevo rol "alias" no existe en el modelo de strings del codebase. |
| Scoping de Contenedores por bodega replicado en consulta (`index`) **y** en guards por acción (`edit/update/export/print/destroy`) | La consulta evita mostrar datos/opciones fuera de alcance (UX); los guards por acción bloquean el acceso directo por ID (seguridad). Responsabilidades distintas. | Solo el filtro de listado deja abierto el acceso por URL a contenedores de bodegas no asignadas (viola SC-004). |
| Guards inline en el controlador en vez de `ContainerPolicy` | El módulo de contenedores ya resuelve autorización inline (`funcionario` solo lectura); mantener el estilo reduce superficie y es coherente. | Introducir una Policy nueva solo para este rol añade una capa que el resto del módulo no usa, aumentando inconsistencia. |
