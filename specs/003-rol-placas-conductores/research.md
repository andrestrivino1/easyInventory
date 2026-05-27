# Phase 0 — Research & Decisiones técnicas: Rol "placas"

Decisiones tomadas para implementar el rol `placas` sobre el codebase existente. No quedaron `NEEDS CLARIFICATION`: las tres ambigüedades de alcance se resolvieron con el usuario durante `/speckit-specify` (flujo completo, rutas solo de selección, asignación compartida).

---

## D1 — Representación del rol

- **Decisión**: Agregar `placas` como un nuevo valor del string `users.rol`. No se introduce ningún paquete de permisos.
- **Rationale**: El codebase no usa Spatie ni una tabla de roles; todos los roles (`admin`, `clientes`, `funcionario`, `importer`, `import_viewer`, `proveedor_itr`) son strings validados en `UserController` y ramificados en middleware/vistas. Mantener la misma mecánica minimiza el cambio y la curva de mantenimiento.
- **Alternativas consideradas**:
  - *Spatie laravel-permission*: sobreingeniería para un sistema con ~6 roles fijos; introduciría 5 tablas y migración de datos.
  - *Enum PHP*: el resto de roles son strings sueltos; un enum solo para `placas` sería inconsistente.

## D2 — Relación conductor ↔ usuario placas

- **Decisión**: Tabla pivote `user_driver` (muchos-a-muchos), relación `User::assignedDrivers()` con `belongsToMany(Driver::class, 'user_driver')`, sincronizada con `sync()` en `store`/`update` del `UserController`.
- **Rationale**: La asignación es compartida (FR-005: un conductor puede pertenecer a varios `placas`), lo que exige N:N. El proyecto ya implementa N:N usuario↔bodega con la pivote `user_warehouse` y `sync()`; se replica ese patrón exacto (nombre `user_<entidad>`, `withTimestamps()`).
- **Alternativas consideradas**:
  - *Columna `assigned_user_id` en `drivers`*: 1:N, impide compartir un conductor y modifica una tabla existente (rompe la restricción de "cero cambios a tablas existentes").
  - *Campo JSON con IDs en `users`*: no permite integridad referencial (FK), ni `whereIn` indexado, ni `sync`.

## D3 — Confinamiento al módulo (acceso)

- **Decisión**: Extender el middleware `BlockImporterAccess` con una rama `placas` que usa una **allow-list por nombre de ruta** (las rutas operativas de `liquidaciones.*` + `liquidaciones.routes.peajes` + `home`/`logout`/`language.switch`); cualquier otra ruta redirige a `liquidaciones.index`.
- **Rationale**: Es el patrón establecido en el codebase para roles confinados (`importer`, `import_viewer`, `proveedor_itr`). Reusarlo garantiza consistencia y que el confinamiento aplique tanto a navegación como a acceso directo por URL (el middleware corre en el grupo `web` para todas las rutas).
- **Detalle clave**: A diferencia de la rama `importer` (que permite todo el path `imports`), para `placas` **no** se incluye el path `liquidaciones` en `$allowedPaths`; se confía exclusivamente en la allow-list por nombre. Así se excluyen los nombres del catálogo de rutas (`liquidaciones.routes.index|create|store|show|edit|update|destroy|toggle-active`) mientras se permiten las acciones operativas y el AJAX de peajes (`liquidaciones.routes.peajes`).
- **Alternativas consideradas**:
  - *Middleware nuevo dedicado*: duplicaría lógica; el patrón existente ya resuelve el caso.
  - *Solo gate/policy*: no oculta otros módulos de la navegación ni redirige el acceso directo de forma uniforme.

## D4 — Apertura del grupo de rutas de liquidaciones

- **Decisión**: Ampliar el gate `liquidaciones.access` (en `AppServiceProvider`) de `rol === 'admin'` a `in_array($user->rol, ['admin','placas'])`.
- **Rationale**: El grupo de rutas `liquidaciones` está protegido por `can:liquidaciones.access`. Para que `placas` entre al módulo, el gate debe admitirlo; el scoping fino lo hacen la policy y las consultas.
- **Alternativas consideradas**: *Quitar el gate y depender del middleware*: debilita la defensa en profundidad; el gate es barato y explícito.

## D5 — Scoping por conductor (qué ve y qué opera placas)

- **Decisión**: Doble enforcement:
  1. **Consultas (`LiquidacionController`)**: cuando el usuario es `placas`, `index()` agrega `whereIn('driver_id', $assignedIds)` a la query base (afecta listado, consolidado y consolidado mensual, que derivan del mismo `$base`). Los selectores de conductor en `index()`, `create()` y `edit()` se limitan a los conductores asignados.
  2. **Policy (`LiquidacionPolicy`)**: `before()` hace bypass para `admin`, bloquea otros roles, y deja pasar a `placas` a los métodos por-acción. Cada método (`view`, `update`, `delete`, `close`, `reopen`, `cancel`, `downloadPdf`) verifica que `liquidacion.driver_id` pertenezca a los conductores asignados, **además** de las reglas de estado vigentes.
- **Rationale**: Las consultas garantizan UX y rendimiento (no se cargan ni muestran datos fuera de alcance) y la policy garantiza seguridad ante manipulación de IDs por URL (SC-002, SC-005). El consolidado se recalcula automáticamente porque `LiquidacionCalculator::aggregate()` opera sobre el `$base` ya filtrado.
- **Alternativas consideradas**:
  - *Global scope en el modelo `Liquidacion`*: afectaría también a `admin` y a otros contextos; un global scope condicionado al rol del usuario autenticado es frágil en comandos/jobs sin usuario. Se prefiere scoping explícito en el controlador.
  - *Solo policy*: obligaría a paginar/listar todo y rechazar por item; ineficiente y mostraría conteos/consolidados incorrectos.

## D6 — Validación del conductor en crear/editar liquidación

- **Decisión**: En `StoreLiquidacionRequest`/`UpdateLiquidacionRequest`, cuando el usuario es `placas`, restringir `driver_id` a `Rule::in($user->assignedDriverIds())` (además del `exists:drivers,id` existente).
- **Rationale**: Impide que un `placas` cree/edite una liquidación para un conductor no asignado manipulando el `driver_id` del formulario (FR-013, SC-005). Centralizar en el Form Request mantiene el controlador limpio y aprovecha la validación declarativa.
- **Alternativas consideradas**: *Chequeo manual en el controlador*: disperso y fácil de olvidar entre `store` y `update`.

## D7 — Catálogo de Rutas fuera del alcance de placas

- **Decisión**: `placas` solo selecciona rutas existentes (cargadas en el form de liquidación por `LiquidacionController::create()/edit()`) y consume el endpoint AJAX `liquidaciones.routes.peajes`. El catálogo (index/create/edit/delete/toggle) queda bloqueado por la allow-list del middleware (D3); como defensa en profundidad se agregan `authorize('viewAny'/'create', LiquidacionRoute::class)` a `index`/`create`/`store` del `LiquidacionRouteController`, y `LiquidacionRoutePolicy::before()` ya bloquea a todo no-admin.
- **Rationale**: Decisión confirmada por el usuario ("solo seleccionar"). Las rutas son datos de catálogo compartido gestionados por `admin`. El endpoint de peajes no tiene `authorize` y es necesario para que `placas` arme la liquidación, por eso se incluye en la allow-list.
- **Alternativas consideradas**: *Permitir gestión de rutas a placas*: rechazada por el usuario; mezclaría datos maestros compartidos con un rol operativo acotado.

## D8 — Punto de entrada y navegación

- **Decisión**: `HomeController::index()` redirige a `placas` a `liquidaciones.index` (mismo patrón que importer/itr). En `layouts/app.blade.php` se agrega el flag `$isPlacas` y una rama de navegación que muestra **solo** "Liquidación de Viajes". El botón "Rutas" del índice de liquidaciones se condiciona a `@can('viewAny', App\Models\LiquidacionRoute::class)` (solo admin).
- **Rationale**: Coherencia con los demás roles confinados; evita enlaces muertos o no autorizados en la UI.
- **Alternativas consideradas**: *Dejar la navegación general y solo bloquear por middleware*: mostraría enlaces que redirigen con error; mala UX.

## D9 — Gestión de la asignación en el formulario de usuarios

- **Decisión**: En `users/create.blade.php` y `users/edit.blade.php`, agregar la opción de rol "Placas" y un grupo de selección de conductores (checkbox-group, reutilizando el estilo de bodegas), mostrado por JS solo cuando `rol === 'placas'`, con validación de mínimo 1 conductor (cliente y servidor). `UserController::create()/edit()` pasan la lista de conductores activos; `store()/update()` validan y `sync()` la relación, y hacen `detach()` si el rol deja de ser `placas`.
- **Rationale**: Replica el patrón ya probado de selección múltiple de bodegas para `funcionario`/`clientes` (mismo componente visual, misma validación min:1, mismo `sync`). Mínima fricción para el admin (SC-003).
- **Alternativas consideradas**: *Pantalla aparte para asignar conductores*: añade pasos; el usuario pidió explícitamente elegir conductores "en la creación del usuario".

## D10 — Migración no destructiva y orden

- **Decisión**: Una sola migración nueva `create_user_driver_table` con `user_id`, `driver_id`, `timestamps`, FKs con `onDelete('cascade')` y `unique(['user_id','driver_id'])`. Sin tocar `users`, `drivers`, `liquidaciones` ni `liquidacion_routes`.
- **Rationale**: La cascada limpia asignaciones si se elimina un usuario o un conductor, evitando filas huérfanas. El unique compuesto impide asignaciones duplicadas. Cumple "cero cambios a tablas existentes".
- **Alternativas consideradas**: *Sin FKs (solo índices)*: pierde integridad referencial; el proyecto usa FKs en `user_warehouse`.
