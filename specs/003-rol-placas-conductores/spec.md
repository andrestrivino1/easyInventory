# Feature Specification: Rol "placas" con conductores asignados

**Feature Branch**: `003-rol-placas-conductores`
**Created**: 2026-05-23
**Status**: Draft
**Input**: User description: "Se creara un nuevo rol llamado placas el cual tendra como función solo ver el modulo de liquidación de viajes y realizar acciones para los condutores que se le hallan asignado. En la creación de este usuario se debera permitir elegir a que conductores tendra relación y asi realizar el flujo actual en dicho modulo"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Crear un usuario con rol "placas" y asignarle conductores (Priority: P1)

Un administrador necesita dar acceso a una persona que gestiona la operación de uno o varios conductores específicos, sin darle acceso al resto del sistema. Desde la pantalla de creación de usuarios, el administrador elige el rol "placas" y, en ese momento, selecciona los conductores con los que ese usuario tendrá relación. El administrador también puede ajustar esa lista de conductores más adelante editando al usuario.

**Why this priority**: Es la base de toda la funcionalidad: sin la posibilidad de crear el usuario con su relación de conductores, ninguna de las demás capacidades existe. Es la primera pieza demostrable de valor.

**Independent Test**: Crear un usuario seleccionando el rol "placas", elegir dos conductores, guardar y verificar que el usuario queda registrado con ese rol y con exactamente esos dos conductores asociados. Editar el usuario para agregar/quitar un conductor y confirmar que los cambios persisten.

**Acceptance Scenarios**:

1. **Given** un administrador en la pantalla de creación de usuarios, **When** selecciona el rol "placas", **Then** aparece un control para elegir uno o más conductores existentes.
2. **Given** el rol "placas" seleccionado con al menos un conductor elegido, **When** guarda el usuario, **Then** el usuario se crea con rol "placas" y queda relacionado con los conductores seleccionados.
3. **Given** un usuario "placas" ya creado, **When** el administrador edita su lista de conductores asignados y guarda, **Then** la relación se actualiza a la nueva selección.
4. **Given** un mismo conductor, **When** el administrador lo asigna a dos usuarios "placas" distintos, **Then** el sistema lo permite (la asignación es compartida).

---

### User Story 2 - Acceso restringido al módulo y visibilidad limitada a conductores asignados (Priority: P2)

Una persona con rol "placas" inicia sesión y únicamente puede ver y entrar al módulo de Liquidación de Viajes; el resto de módulos del sistema no le aparecen ni puede acceder a ellos. Dentro del módulo, solo ve las liquidaciones, los conductores y los totales correspondientes a los conductores que le fueron asignados, nunca información de otros conductores.

**Why this priority**: Es la garantía de seguridad y aislamiento que da sentido al rol. Una vez creado el usuario (US1), esta historia entrega el valor central: el usuario ve solo lo que le corresponde.

**Independent Test**: Iniciar sesión con un usuario "placas" que tiene 2 conductores asignados, en un sistema con liquidaciones de 5 conductores distintos. Verificar que la navegación solo muestra Liquidación de Viajes, que el listado muestra únicamente las liquidaciones de sus 2 conductores, que los filtros y selectores de conductor solo ofrecen esos 2, y que intentar abrir por URL directa una liquidación de otro conductor o cualquier otro módulo es denegado.

**Acceptance Scenarios**:

1. **Given** un usuario "placas" autenticado, **When** observa la navegación, **Then** solo se muestra el acceso a Liquidación de Viajes y ningún otro módulo.
2. **Given** un usuario "placas" autenticado, **When** intenta acceder por URL directa a un módulo distinto de Liquidación de Viajes, **Then** el acceso es denegado.
3. **Given** un usuario "placas" con conductores A y B asignados, **When** abre el listado de liquidaciones, **Then** solo ve liquidaciones cuyos conductores sean A o B.
4. **Given** ese mismo usuario, **When** usa filtros o selectores de conductor, **Then** solo aparecen como opciones los conductores A y B.
5. **Given** ese mismo usuario, **When** ve el consolidado/totales del módulo, **Then** los valores se calculan únicamente sobre las liquidaciones de A y B.
6. **Given** ese mismo usuario, **When** intenta abrir por URL directa una liquidación de un conductor no asignado, **Then** el acceso es denegado.

---

### User Story 3 - Operar el flujo completo de liquidación sobre los conductores asignados (Priority: P3)

Una persona con rol "placas" puede realizar el flujo operativo completo del módulo de Liquidación de Viajes, igual que hoy, pero limitado a sus conductores asignados: crear una liquidación, editar y eliminar borradores, cerrarla, reabrirla, anularla y descargar su PDF. Para crear o editar, selecciona rutas del catálogo existente, sin poder modificar dicho catálogo.

**Why this priority**: Completa el objetivo del rol permitiéndole trabajar de forma autónoma. Depende de US1 y US2, por eso es posterior, pero es donde se materializa la operación diaria.

**Independent Test**: Con un usuario "placas" y un conductor asignado, crear una liquidación nueva para ese conductor seleccionando una ruta existente, guardarla como borrador, editarla, cerrarla, reabrirla, anularla y descargar el PDF; verificar que cada acción se completa y queda atribuida a ese usuario, y que no puede crear/editar rutas del catálogo.

**Acceptance Scenarios**:

1. **Given** un usuario "placas" con conductor A asignado, **When** crea una liquidación seleccionando al conductor A y una ruta existente, **Then** la liquidación se crea como borrador y queda atribuida a ese usuario.
2. **Given** una liquidación en borrador de un conductor asignado, **When** el usuario "placas" la edita o la elimina, **Then** la acción se realiza respetando las reglas de estado vigentes (solo borradores son editables/eliminables).
3. **Given** una liquidación en borrador de un conductor asignado, **When** el usuario "placas" la cierra, **Then** pasa al estado cerrada.
4. **Given** una liquidación cerrada de un conductor asignado, **When** el usuario "placas" la reabre o la anula indicando el motivo requerido, **Then** la transición de estado se aplica y queda registrada en el historial.
5. **Given** un usuario "placas" creando/editando una liquidación, **When** elige la ruta, **Then** puede seleccionar rutas existentes pero no crear, editar ni eliminar rutas del catálogo.
6. **Given** un usuario "placas", **When** intenta crear una liquidación para un conductor que no tiene asignado, **Then** la acción es rechazada.
7. **Given** un usuario "placas", **When** descarga el PDF de una liquidación de un conductor asignado, **Then** el documento se genera correctamente.

---

### Edge Cases

- **Usuario "placas" sin conductores asignados**: el módulo se muestra vacío; no puede crear liquidaciones porque no tiene conductores disponibles para seleccionar.
- **Conductor desactivado**: un conductor inactivo no aparece como opción para crear nuevas liquidaciones, pero las liquidaciones históricas de ese conductor siguen siendo visibles para los usuarios "placas" a los que está asignado.
- **Conductor desasignado posteriormente**: si el administrador quita un conductor de un usuario "placas", ese usuario deja de ver las liquidaciones de dicho conductor (incluidas las que él mismo creó).
- **Conductor compartido**: cuando un conductor está asignado a dos usuarios "placas", ambos ven y pueden operar sus liquidaciones, y las acciones quedan atribuidas a quien las ejecuta.
- **Intento de manipulación**: si un usuario "placas" intenta operar (por URL o parámetros) sobre un conductor o liquidación que no le corresponde, la acción es denegada.
- **Acceso del administrador**: el administrador conserva acceso total y sin restricciones a todas las liquidaciones y conductores, independientemente de las asignaciones de los usuarios "placas".
- **Reglas de estado**: las transiciones de estado (borrador → cerrada → reabrir/anular) y los motivos obligatorios funcionan igual que hoy; el rol "placas" no las altera, solo las ejecuta dentro de su alcance.

## Requirements *(mandatory)*

### Functional Requirements

#### Rol y gestión de usuarios

- **FR-001**: El sistema MUST ofrecer un nuevo rol de usuario denominado "placas".
- **FR-002**: Al crear un usuario, el administrador MUST poder seleccionar el rol "placas".
- **FR-003**: Cuando se selecciona el rol "placas" al crear un usuario, el sistema MUST permitir elegir uno o más conductores existentes para relacionarlos con ese usuario.
- **FR-004**: El sistema MUST permitir al administrador modificar la lista de conductores asignados a un usuario "placas" mediante la edición del usuario.
- **FR-005**: El sistema MUST permitir que un mismo conductor esté asignado a varios usuarios "placas" simultáneamente (asignación compartida, sin exclusividad).
- **FR-006**: La gestión de usuarios "placas" y de sus asignaciones de conductores MUST estar disponible únicamente para administradores.
- **FR-007**: El sistema NO requiere asignar bodegas a los usuarios con rol "placas".

#### Acceso y aislamiento

- **FR-008**: Un usuario "placas" MUST poder acceder únicamente al módulo de Liquidación de Viajes; el resto de los módulos del sistema MUST ser inaccesibles para él, tanto en la navegación visible como por acceso directo a sus direcciones.
- **FR-009**: Tras iniciar sesión, un usuario "placas" MUST ser llevado al módulo de Liquidación de Viajes como punto de entrada.
- **FR-010**: Un usuario "placas" MUST ver y operar únicamente las liquidaciones cuyo conductor figure entre sus conductores asignados (listado, detalle, filtros, consolidado y PDF).
- **FR-011**: Los selectores y filtros de conductor disponibles para un usuario "placas" MUST contener únicamente sus conductores asignados.
- **FR-012**: Los totales y el consolidado mostrados a un usuario "placas" MUST calcularse exclusivamente sobre las liquidaciones de sus conductores asignados.
- **FR-013**: Cualquier intento de un usuario "placas" de visualizar u operar una liquidación de un conductor no asignado MUST ser denegado.

#### Flujo operativo

- **FR-014**: Un usuario "placas" MUST poder crear liquidaciones para cualquiera de sus conductores asignados.
- **FR-015**: Un usuario "placas" MUST poder editar y eliminar liquidaciones en borrador de sus conductores asignados, respetando las reglas de estado vigentes.
- **FR-016**: Un usuario "placas" MUST poder cerrar, reabrir y anular liquidaciones de sus conductores asignados, siguiendo las mismas reglas de transición de estado y de motivos obligatorios que aplican hoy.
- **FR-017**: Un usuario "placas" MUST poder descargar el PDF de las liquidaciones de sus conductores asignados.
- **FR-018**: Al crear o editar una liquidación, un usuario "placas" MUST poder seleccionar rutas del catálogo existente, pero NO MUST poder crear, editar ni eliminar rutas ni peajes del catálogo.
- **FR-019**: Un usuario "placas" NO MUST poder gestionar los datos maestros de los conductores (crear/editar conductores); solo los referencia dentro del módulo de liquidación.
- **FR-020**: Las acciones realizadas por un usuario "placas" MUST quedar atribuidas a dicho usuario en los registros de creación, actualización e historial de estados de la liquidación.

#### Compatibilidad

- **FR-021**: El administrador MUST conservar acceso total y sin restricciones a todas las liquidaciones, conductores y rutas, independientemente de las asignaciones de los usuarios "placas".
- **FR-022**: La incorporación del rol "placas" NO MUST alterar el comportamiento ni el alcance de los roles existentes.

### Key Entities *(include if feature involves data)*

- **Usuario**: persona que accede al sistema. Adquiere un nuevo valor de rol, "placas", que define su alcance restringido al módulo de Liquidación de Viajes.
- **Conductor**: persona que conduce el vehículo asociado a una liquidación (entidad existente, identificada por su placa). Es el eje del alcance: cada usuario "placas" se relaciona con un conjunto de conductores.
- **Asignación Conductor–Usuario "placas"**: relación que vincula a un usuario "placas" con uno o más conductores. Es de muchos-a-muchos: un usuario puede tener varios conductores y un conductor puede pertenecer a varios usuarios "placas".
- **Liquidación de Viajes**: documento de liquidación existente, asociado a un conductor. Determina su visibilidad para un usuario "placas" a través del conductor.
- **Ruta**: elemento del catálogo (origen/destino con sus peajes) que se selecciona al crear una liquidación; para el rol "placas" es de solo selección.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los módulos distintos a Liquidación de Viajes resultan inaccesibles para un usuario "placas", tanto en navegación como por acceso directo.
- **SC-002**: Un usuario "placas" no puede ver ni operar ninguna liquidación de un conductor que no tenga asignado (0% de fuga de información fuera de su alcance).
- **SC-003**: Un administrador puede crear un usuario "placas" y asignarle sus conductores en un único formulario, en menos de 2 minutos.
- **SC-004**: Un usuario "placas" puede completar de forma autónoma el flujo de una liquidación (desde crear hasta cerrar) para un conductor asignado, sin intervención del administrador.
- **SC-005**: Todo intento de un usuario "placas" de actuar sobre un conductor o liquidación fuera de su alcance es bloqueado el 100% de las veces.
- **SC-006**: Los totales y el consolidado vistos por un usuario "placas" coinciden exactamente con la suma de las liquidaciones de sus conductores asignados (sin incluir ni excluir registros indebidamente).

## Assumptions

- Se reutiliza el mecanismo de autenticación y sesión existente; "placas" es un nuevo valor de rol dentro del esquema actual de roles del sistema.
- El "conductor" corresponde a la entidad de conductores ya utilizada por el módulo de Liquidación de Viajes (identificada por su placa), no a ninguna otra entidad en desuso.
- Un usuario "placas" no requiere asignación de bodegas; su alcance se define exclusivamente por sus conductores asignados.
- Se permite crear un usuario "placas" con al menos un conductor; un usuario sin conductores asignados simplemente no tendrá información ni podrá crear liquidaciones.
- La asignación conductor–usuario es compartida: un conductor puede estar asignado a varios usuarios "placas".
- El catálogo de rutas y peajes sigue siendo gestionado únicamente por administradores; el rol "placas" solo selecciona rutas existentes.
- Las reglas de estados de la liquidación (borrador, cerrada, anulada), las validaciones y los motivos obligatorios permanecen sin cambios; el rol "placas" las ejecuta dentro de su alcance, no las modifica.
- El administrador mantiene visibilidad y control totales sobre todo el módulo, sin verse afectado por las asignaciones de los usuarios "placas".
