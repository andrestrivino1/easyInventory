# Feature Specification: Rol "cliente funcionario" (clientes + módulo Contenedores)

**Feature Branch**: `006-rol-cliente-funcionario`
**Created**: 2026-06-02
**Status**: Draft
**Input**: User description: "se debe crear un nuevo rol llamado cliente funcionario, este tendra los mismo permisos de clientes pero con la diferencia que este podra trabajar en el modulo contenedores"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Crear un usuario con rol "cliente funcionario" (Priority: P1)

Un administrador necesita dar acceso a una persona que opera igual que un cliente —limitada a sus bodegas asignadas— pero que, además, debe poder trabajar el día a día del módulo de Contenedores. Desde la pantalla de creación de usuarios, el administrador elige el nuevo rol "cliente funcionario" y le asigna una o más bodegas, exactamente igual que hoy se hace con el rol "clientes". También puede ajustar esa lista de bodegas más adelante editando al usuario.

**Why this priority**: Es la base de toda la funcionalidad: sin poder crear el usuario con el nuevo rol y su relación de bodegas, ninguna de las demás capacidades existe. Es la primera pieza demostrable de valor.

**Independent Test**: Crear un usuario seleccionando el rol "cliente funcionario", elegir dos bodegas, guardar y verificar que el usuario queda registrado con ese rol y con exactamente esas dos bodegas asociadas. Editar el usuario para agregar/quitar una bodega y confirmar que los cambios persisten.

**Acceptance Scenarios**:

1. **Given** un administrador en la pantalla de creación de usuarios, **When** abre el selector de rol, **Then** aparece la opción "cliente funcionario" junto a los roles existentes.
2. **Given** el rol "cliente funcionario" seleccionado, **When** el administrador configura el usuario, **Then** se le solicita asignar una o más bodegas (igual que para el rol "clientes"), no una sola bodega fija.
3. **Given** un usuario "cliente funcionario" con dos bodegas asignadas, **When** el administrador guarda, **Then** el usuario queda creado con ese rol y relacionado con exactamente esas dos bodegas.
4. **Given** un usuario "cliente funcionario" ya creado, **When** el administrador edita su lista de bodegas y guarda, **Then** la relación se actualiza a la nueva selección.
5. **Given** un usuario existente con otro rol, **When** el administrador lo cambia a "cliente funcionario", **Then** el usuario adopta el alcance del nuevo rol sin conservar accesos del rol anterior.

---

### User Story 2 - Operar como cliente, limitado a las bodegas asignadas (Priority: P2)

Una persona con rol "cliente funcionario" inicia sesión y dispone exactamente del mismo alcance que un usuario "clientes": ve y opera los módulos que hoy ve un cliente (Movimientos, Productos, Transferencias, Salidas, Stock, Trazabilidad), siempre limitado a sus bodegas asignadas, y sin acceso a los módulos reservados a otros roles (Bodegas, Importación, ITR, Liquidación de Viajes, Usuarios).

**Why this priority**: Garantiza que el nuevo rol hereda fielmente el comportamiento del rol "clientes". Es el cimiento sobre el que se agrega la única diferencia (Contenedores), y asegura que no se filtre información de bodegas ajenas.

**Independent Test**: Iniciar sesión con un usuario "cliente funcionario" que tiene 2 bodegas asignadas, en un sistema con datos de varias bodegas. Verificar que los módulos visibles y los datos mostrados coinciden con los de un usuario "clientes" con las mismas bodegas, y que los módulos no permitidos siguen siendo inaccesibles (navegación y URL directa).

**Acceptance Scenarios**:

1. **Given** un usuario "cliente funcionario" autenticado, **When** observa la navegación, **Then** ve los mismos módulos que vería un usuario "clientes" con sus mismas bodegas, más el acceso a Contenedores.
2. **Given** un usuario "cliente funcionario" con bodegas A y B asignadas, **When** consulta Movimientos, Productos, Transferencias, Salidas, Stock o Trazabilidad, **Then** solo ve información correspondiente a las bodegas A y B.
3. **Given** un usuario "cliente funcionario", **When** intenta acceder por navegación o URL directa a un módulo reservado (Bodegas, Importación, ITR, Liquidación de Viajes, Usuarios), **Then** el acceso es denegado igual que para un usuario "clientes".
4. **Given** un usuario "cliente funcionario", **When** realiza acciones de transferencias o salidas, **Then** aplican exactamente las mismas reglas y restricciones que para el rol "clientes".

---

### User Story 3 - Trabajar el módulo de Contenedores sobre sus bodegas asignadas (Priority: P3)

La diferencia respecto al rol "clientes" es que el "cliente funcionario" sí puede entrar al módulo de Contenedores y trabajar en él: ver el listado de contenedores de sus bodegas asignadas, crear nuevos contenedores y editarlos, además de exportarlos e imprimirlos. No puede eliminar contenedores. Su visibilidad y operación quedan limitadas a las bodegas que tiene asignadas.

**Why this priority**: Es el valor diferencial que justifica crear el rol. Depende de US1 (existencia del usuario) y US2 (alcance heredado de clientes), por eso es posterior, pero es la razón de ser de la solicitud.

**Independent Test**: Con un usuario "cliente funcionario" que tiene la bodega A asignada (y siendo A una bodega que recibe contenedores), abrir el módulo de Contenedores: verificar que ve los contenedores de A y no los de bodegas no asignadas; crear un contenedor para A, editarlo, exportarlo e imprimirlo; comprobar que la opción de eliminar no está disponible y que intentar eliminar (incluso por acción directa) es rechazado.

**Acceptance Scenarios**:

1. **Given** un usuario "cliente funcionario" autenticado, **When** observa la navegación, **Then** aparece el acceso al módulo de Contenedores.
2. **Given** un usuario "cliente funcionario" con bodegas A y B asignadas, **When** abre el listado de Contenedores, **Then** solo ve contenedores cuya bodega sea A o B.
3. **Given** un usuario "cliente funcionario", **When** crea un contenedor, **Then** solo puede asignarlo a una de sus bodegas asignadas que además reciba contenedores.
4. **Given** un contenedor de una de sus bodegas asignadas, **When** el usuario "cliente funcionario" lo edita y guarda, **Then** los cambios se persisten.
5. **Given** un contenedor de una de sus bodegas asignadas, **When** el usuario "cliente funcionario" lo exporta o lo imprime, **Then** el documento se genera correctamente.
6. **Given** un usuario "cliente funcionario", **When** observa un contenedor, **Then** no se le ofrece la opción de eliminarlo y cualquier intento de eliminación es rechazado.
7. **Given** un usuario "cliente funcionario", **When** intenta ver, editar o crear un contenedor en una bodega que no tiene asignada (por URL o parámetros), **Then** la acción es denegada.

---

### Edge Cases

- **Usuario "cliente funcionario" sin bodegas asignadas**: el sistema se comporta como con un "clientes" sin bodegas; el módulo de Contenedores se muestra vacío y no puede crear contenedores porque no tiene bodegas válidas donde asignarlos.
- **Bodega asignada que no recibe contenedores**: si una bodega asignada no está habilitada para recibir contenedores, el usuario no puede crear contenedores en ella; solo aparecen como destino las bodegas asignadas que sí reciben contenedores.
- **Bodega desasignada posteriormente**: si el administrador quita una bodega al usuario "cliente funcionario", ese usuario deja de ver los contenedores de dicha bodega (incluidos los que él mismo creó).
- **Intento de eliminación**: aunque el flujo no ofrezca el botón de eliminar, cualquier intento directo de eliminar un contenedor por parte de un "cliente funcionario" debe ser rechazado.
- **Intento de manipulación de bodega ajena**: si un "cliente funcionario" intenta operar (por URL o parámetros) sobre un contenedor de una bodega no asignada, la acción es denegada.
- **Acceso del administrador**: el administrador conserva acceso total y sin restricciones a todos los contenedores y bodegas, independientemente de las asignaciones del "cliente funcionario".
- **Convivencia con "clientes" y "funcionario"**: el comportamiento de los roles "clientes" (sin acceso a Contenedores) y "funcionario" (solo lectura en Contenedores) permanece sin cambios.

## Requirements *(mandatory)*

### Functional Requirements

#### Rol y gestión de usuarios

- **FR-001**: El sistema MUST ofrecer un nuevo rol de usuario denominado "cliente funcionario".
- **FR-002**: Al crear o editar un usuario, el administrador MUST poder seleccionar el rol "cliente funcionario" en el selector de roles.
- **FR-003**: El rol "cliente funcionario" MUST gestionarse con asignación de una o más bodegas (relación múltiple), de la misma forma que el rol "clientes", y NO con una bodega única fija.
- **FR-004**: El administrador MUST poder modificar la lista de bodegas asignadas a un usuario "cliente funcionario" mediante la edición del usuario.
- **FR-005**: La gestión de usuarios "cliente funcionario" MUST estar disponible únicamente para administradores.
- **FR-006**: Al cambiar un usuario hacia o desde el rol "cliente funcionario", el sistema MUST ajustar sus relaciones de acceso para reflejar el alcance del nuevo rol, sin conservar accesos del rol anterior.

#### Alcance heredado del rol "clientes"

- **FR-007**: Un usuario "cliente funcionario" MUST tener exactamente los mismos permisos y alcance que un usuario "clientes" en todos los módulos que hoy usa un cliente (Movimientos, Productos, Transferencias, Salidas, Stock, Trazabilidad), limitado a sus bodegas asignadas.
- **FR-008**: Un usuario "cliente funcionario" MUST estar sujeto a las mismas restricciones de acceso que un "clientes" frente a los módulos reservados a otros roles (Bodegas, Importación, ITR, Liquidación de Viajes, Usuarios), tanto en navegación como por acceso directo a sus direcciones.
- **FR-009**: Las reglas y validaciones que hoy aplican al rol "clientes" en Transferencias, Salidas y demás módulos MUST aplicar de forma idéntica al rol "cliente funcionario".

#### Diferencia: módulo de Contenedores

- **FR-010**: Un usuario "cliente funcionario" MUST poder acceder al módulo de Contenedores, tanto desde la navegación visible como por sus direcciones.
- **FR-011**: Un usuario "cliente funcionario" MUST ver únicamente los contenedores correspondientes a sus bodegas asignadas; los contenedores de bodegas no asignadas NO MUST ser visibles ni operables.
- **FR-012**: Un usuario "cliente funcionario" MUST poder crear contenedores, pudiendo asignarlos únicamente a bodegas que tenga asignadas y que además estén habilitadas para recibir contenedores.
- **FR-013**: Un usuario "cliente funcionario" MUST poder editar los contenedores de sus bodegas asignadas.
- **FR-014**: Un usuario "cliente funcionario" MUST poder exportar e imprimir los contenedores de sus bodegas asignadas.
- **FR-015**: Un usuario "cliente funcionario" NO MUST poder eliminar contenedores; la opción no debe ofrecerse y cualquier intento de eliminación MUST ser rechazado.
- **FR-016**: Cualquier intento de un usuario "cliente funcionario" de ver, crear, editar, exportar o imprimir un contenedor de una bodega no asignada MUST ser denegado.

#### Compatibilidad

- **FR-017**: El administrador MUST conservar acceso total y sin restricciones a todos los contenedores y bodegas, independientemente de las asignaciones de los usuarios "cliente funcionario".
- **FR-018**: La incorporación del rol "cliente funcionario" NO MUST alterar el comportamiento ni el alcance de los roles existentes (en particular "clientes" y "funcionario", que mantienen su acceso actual al módulo de Contenedores).

### Key Entities *(include if feature involves data)*

- **Usuario**: persona que accede al sistema. Adquiere un nuevo valor de rol, "cliente funcionario", que define un alcance idéntico al de "clientes" más el acceso operativo (crear/editar/exportar/imprimir, sin eliminar) al módulo de Contenedores, todo limitado a sus bodegas asignadas.
- **Bodega**: almacén existente. El "cliente funcionario" se relaciona con una o más bodegas (relación múltiple), igual que el rol "clientes"; esa relación define su visibilidad y operación tanto en los módulos heredados como en Contenedores.
- **Contenedor**: entidad existente asociada a una bodega. Su bodega determina la visibilidad y operabilidad para un usuario "cliente funcionario". Solo las bodegas habilitadas para recibir contenedores pueden ser destino de un contenedor nuevo.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador puede crear un usuario "cliente funcionario" y asignarle sus bodegas en un único formulario, en menos de 2 minutos.
- **SC-002**: El alcance de un usuario "cliente funcionario" coincide al 100% con el de un usuario "clientes" con las mismas bodegas en los módulos heredados (mismos módulos visibles, mismos datos visibles, mismas restricciones).
- **SC-003**: Un usuario "cliente funcionario" puede completar de forma autónoma el flujo de Contenedores (ver, crear, editar, exportar, imprimir) sobre una bodega asignada, sin intervención del administrador.
- **SC-004**: Un usuario "cliente funcionario" no puede ver ni operar ningún contenedor de una bodega que no tenga asignada (0% de fuga de información fuera de su alcance).
- **SC-005**: El 100% de los intentos de eliminación de contenedores por un usuario "cliente funcionario" son rechazados.
- **SC-006**: La incorporación del rol no produce ningún cambio observable en el comportamiento de los roles "clientes" y "funcionario" en el módulo de Contenedores.

## Assumptions

- El nombre del rol solicitado, "cliente funcionario", es un nuevo valor de rol dentro del esquema de roles existente; se reutiliza el mecanismo de autenticación, sesión y asignación de bodegas ya empleado por el rol "clientes" (relación múltiple de bodegas, sin bodega única fija).
- "Mismos permisos de clientes" significa replicar fielmente el alcance actual del rol "clientes": los módulos que hoy ve y opera un cliente, con su limitación por bodegas asignadas, y las mismas restricciones frente a los módulos no permitidos.
- "Trabajar en el módulo Contenedores" se interpreta, por decisión del solicitante, como crear y editar contenedores (además de exportar e imprimir), pero NO eliminarlos.
- La visibilidad de contenedores para el "cliente funcionario" se limita a sus bodegas asignadas, de forma coherente con cómo el rol "clientes" está limitado a sus bodegas (decisión del solicitante), a diferencia del acceso global que hoy tienen admin y "funcionario" en ese módulo.
- Solo las bodegas habilitadas para recibir contenedores pueden ser destino de un contenedor nuevo; un "cliente funcionario" solo podrá crear contenedores en la intersección de sus bodegas asignadas con las bodegas que reciben contenedores.
- Los roles existentes "clientes" (sin acceso a Contenedores) y "funcionario" (solo lectura en Contenedores) mantienen su comportamiento actual sin cambios.
- El administrador conserva visibilidad y control totales sobre el módulo de Contenedores, sin verse afectado por las asignaciones de los usuarios "cliente funcionario".
