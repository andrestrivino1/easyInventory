# Feature Specification: Reinicio de Stock, Trazabilidad e Importaciones con Descuento Independiente de Contenedor

**Feature Branch**: `008-reset-stock-trazabilidad`
**Created**: 2026-09-22
**Status**: Draft
**Input**: User description: "Necesitamos realizar varios ajustes: 1. eliminar el stock actual es decir que dicho modulo se muestre vacio. 2. Al realizar transferencias y salidas debemos descontar los productos independientemente de que contenedor venga, pero si se debera ver reflejado en la trazabilidad de aqui en adelante. 3. La trazabilidad como el stock se deben quedar vacio. 4. En importación vamos a eliminar todo y el consecutivo del DO sera apartir del ultimo que quede"

## Resumen

La operación va a arrancar de nuevo con datos en limpio. Esto implica tres cambios coordinados pero independientes entre sí:

1. **Arranque en cero de inventario**: los módulos de Stock y Trazabilidad deben quedar vacíos, sin existencias ni movimientos históricos.
2. **Vaciado del módulo de Importación**, conservando la numeración consecutiva de los DO ya emitidos (no se reutilizan números).
3. **Cambio permanente de comportamiento**: de aquí en adelante, transferencias y salidas descuentan producto sin obligar a elegir un contenedor, y el movimiento igual queda registrado en la trazabilidad.

Los puntos 1 y 2 son limpiezas que se ejecutan **una sola vez** en la puesta en marcha. El punto 3 es un cambio de reglas que rige de forma permanente.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Stock y Trazabilidad arrancan vacíos (Priority: P1)

El administrador entra al módulo de Stock y no ve ninguna existencia: ningún producto con cantidad, ningún contenedor con saldo, ninguna transferencia ni salida listada. Entra luego al módulo de Trazabilidad y tampoco ve movimientos históricos. Ambos módulos muestran un estado vacío claro, no una pantalla rota ni totales en blanco. El catálogo de productos, las bodegas, los conductores y los usuarios siguen intactos y disponibles para operar desde cero.

**Why this priority**: Es la base del arranque en limpio que se pidió. Sin esto, cualquier movimiento nuevo se mezcla con saldos históricos incorrectos y la operación no puede confiar en las cifras. Entrega valor por sí solo: deja el sistema listo para empezar a cargar la operación real.

**Independent Test**: Se puede probar ejecutando la limpieza y verificando que Stock y Trazabilidad no devuelvan ninguna fila para ninguna bodega ni para ningún rol, mientras que los listados de Productos, Bodegas y Conductores conservan exactamente los mismos registros que antes.

**Acceptance Scenarios**:

1. **Given** el sistema con existencias y movimientos históricos cargados, **When** se ejecuta la limpieza de arranque, **Then** el módulo de Stock no muestra ningún producto con cantidad para ninguna bodega.
2. **Given** la limpieza ya ejecutada, **When** el administrador abre el módulo de Trazabilidad sin filtros, **Then** no se lista ningún movimiento de entrada ni de salida.
3. **Given** la limpieza ya ejecutada, **When** el administrador abre Stock o Trazabilidad, **Then** ve un mensaje de estado vacío comprensible y no un error, una tabla desarmada ni totales inconsistentes.
4. **Given** la limpieza ya ejecutada, **When** se consultan los listados de Productos, Bodegas, Conductores y Usuarios, **Then** todos conservan la misma cantidad de registros que antes de la limpieza.
5. **Given** la limpieza ya ejecutada, **When** un usuario con rol cliente o funcionario abre Stock o Trazabilidad, **Then** también ve los módulos vacíos, sin fugas de datos previos por su alcance de bodega.
6. **Given** la limpieza ya ejecutada, **When** se exporta Stock o Trazabilidad a PDF o a Excel, **Then** el documento se genera correctamente y sale sin filas de datos.
7. **Given** la limpieza ejecutada, **When** se registra una nueva entrada de inventario, **Then** aparece como el primer y único registro, sin arrastrar saldos anteriores.

---

### User Story 2 - Vaciar Importación conservando el consecutivo del DO (Priority: P2)

El administrador entra al módulo de Importación y no ve ninguna importación listada. Sin embargo, cuando crea la siguiente importación, el número de DO que el sistema asigna **continúa a partir del último número ya emitido**, sin volver a empezar en 001 y sin repetir un número que alguna vez existió. La información eliminada no se destruye de forma definitiva: queda fuera de todas las pantallas y reportes, pero es recuperable si se necesita auditar.

**Why this priority**: Es un objetivo explícito del pedido y es independiente del inventario (Importación y Stock no comparten datos). Se prioriza después del arranque de inventario porque la operación diaria de bodega depende primero de que Stock y Trazabilidad estén limpios.

**Independent Test**: Se puede probar ejecutando el vaciado, confirmando que el listado de Importación queda sin filas, y creando una importación nueva para verificar que su DO es exactamente el siguiente al mayor número emitido antes del vaciado.

**Acceptance Scenarios**:

1. **Given** importaciones históricas cargadas, **When** se ejecuta el vaciado del módulo, **Then** el listado de Importación no muestra ninguna importación para ningún rol.
2. **Given** que el mayor DO emitido del año era el número N, **When** se crea la primera importación después del vaciado, **Then** el sistema asigna el número N+1 y no el 001.
3. **Given** el vaciado ejecutado, **When** se crea una importación nueva, **Then** su número de DO no coincide con ningún número emitido anteriormente.
4. **Given** el vaciado ejecutado, **When** se consultan los reportes e informes del módulo de Importación, **Then** no incluyen las importaciones eliminadas en ningún total ni listado.
5. **Given** el vaciado ejecutado, **When** se revisa el almacenamiento de datos, **Then** la información eliminada sigue existiendo marcada como eliminada y es recuperable.
6. **Given** el vaciado ejecutado y un año calendario nuevo, **When** se crea la primera importación de ese año nuevo, **Then** la numeración del nuevo año arranca según la regla propia de ese año, sin verse afectada por los números del año anterior.

---

### User Story 3 - Transferencias y salidas descuentan sin exigir contenedor (Priority: P2)

Al registrar una transferencia o una salida, el usuario indica el producto y la cantidad, y el sistema descuenta esa cantidad del total disponible de ese producto en la bodega, sin importar de qué contenedor provenga. Elegir un contenedor pasa a ser **opcional**: si el usuario lo elige, el movimiento queda asociado a ese contenedor; si no lo elige, el descuento se hace contra el total del producto y el movimiento queda registrado sin contenedor. En ambos casos el movimiento aparece en la Trazabilidad de aquí en adelante.

**Why this priority**: Es el único cambio permanente de reglas del pedido y elimina una fricción real: hoy las transferencias obligan a escoger un contenedor. Se prioriza junto al vaciado de Importación porque el arranque en limpio (P1) es el que habilita operar con las reglas nuevas desde el primer movimiento.

**Independent Test**: Se puede probar registrando una transferencia y una salida sin seleccionar contenedor, y verificando que ambas se guardan, que el stock del producto disminuye en la cantidad correcta y que ambos movimientos aparecen listados en Trazabilidad.

**Acceptance Scenarios**:

1. **Given** un producto con existencias en una bodega, **When** el usuario registra una transferencia sin seleccionar contenedor, **Then** la transferencia se guarda sin error y el stock del producto en la bodega de origen disminuye en la cantidad transferida.
2. **Given** un producto con existencias en una bodega, **When** el usuario registra una salida sin seleccionar contenedor, **Then** la salida se guarda sin error y el stock del producto disminuye en la cantidad indicada.
3. **Given** un movimiento registrado sin contenedor, **When** se consulta la Trazabilidad, **Then** el movimiento aparece con producto, bodega, cantidad, fecha y referencia, y la columna de contenedor se muestra vacía de forma clara.
4. **Given** un movimiento en el que el usuario sí eligió un contenedor, **When** se consulta la Trazabilidad, **Then** el movimiento aparece asociado a ese contenedor.
5. **Given** un producto cuya existencia está repartida entre varios contenedores, **When** se registra una salida por una cantidad mayor a la de cualquier contenedor individual pero menor o igual al total disponible, **Then** el movimiento se permite y se descuenta correctamente del total.
6. **Given** un producto con existencia disponible, **When** se intenta registrar una salida o transferencia por una cantidad mayor al total disponible en la bodega, **Then** el sistema la rechaza con un mensaje claro y no descuenta nada.
7. **Given** una transferencia registrada sin contenedor, **When** la bodega destino confirma su recepción, **Then** la existencia queda disponible en la bodega destino y el movimiento de entrada aparece en Trazabilidad.

---

### Edge Cases

- **Cantidad mayor al total disponible**: el sistema rechaza el movimiento con un mensaje claro indicando la cantidad disponible, y no realiza ningún descuento parcial.
- **Producto sin existencias**: no puede seleccionarse para una salida o transferencia; si se intenta, el sistema lo rechaza con un mensaje comprensible.
- **Movimientos en curso al momento de la limpieza**: las transferencias enviadas y aún no recibidas también quedan eliminadas por la limpieza; no pueden quedar transferencias "en tránsito" apuntando a existencias que ya no existen.
- **Vaciado ejecutado dos veces**: si la limpieza se ejecutara de nuevo sobre un sistema ya vacío, debe terminar sin error y sin alterar el consecutivo del DO.
- **Consecutivo del DO tras el vaciado**: el siguiente número nunca puede ser inferior o igual a un número ya emitido, aun cuando no quede ninguna importación visible de la cual deducirlo.
- **Concurrencia en la numeración del DO**: si dos usuarios crean una importación al mismo tiempo, el sistema no debe asignarles el mismo número de DO.
- **Alcance por rol**: tras la limpieza, ningún rol (admin, funcionario, cliente, cliente-funcionario) debe ver datos residuales en Stock, Trazabilidad ni Importación a través de sus filtros de bodega o de cliente.
- **Exportaciones sobre módulos vacíos**: los PDF y las exportaciones a Excel de Stock, Trazabilidad e Importación deben generarse correctamente aunque no haya datos.
- **Contenedor elegido sin existencia suficiente**: si el usuario elige explícitamente un contenedor que no tiene la cantidad solicitada, el sistema debe informarlo con claridad en lugar de descontar silenciosamente de otro origen.
- **Módulos no incluidos**: Liquidaciones, ITR, Productos, Bodegas, Conductores y Usuarios no se ven afectados por ninguna de estas limpiezas.

## Requirements *(mandatory)*

### Functional Requirements

#### Limpieza de inventario (Stock y Trazabilidad)

- **FR-001**: El sistema MUST dejar el módulo de Stock sin ninguna existencia visible para ninguna bodega y para ningún rol tras la limpieza de arranque.
- **FR-002**: El sistema MUST dejar el módulo de Trazabilidad sin ningún movimiento histórico visible tras la limpieza de arranque.
- **FR-003**: La limpieza MUST eliminar las existencias en contenedores, las transferencias y las salidas históricas, que son las fuentes de las que se derivan Stock y Trazabilidad.
- **FR-004**: La limpieza MUST conservar intactos el catálogo de productos, las bodegas, los conductores, los usuarios y sus asignaciones de bodega y de cliente.
- **FR-005**: La limpieza MUST conservar intactos los módulos de Liquidaciones e ITR.
- **FR-006**: La información eliminada por la limpieza MUST quedar marcada como eliminada y ser recuperable, no destruida de forma definitiva.
- **FR-007**: Los módulos de Stock y Trazabilidad MUST mostrar un estado vacío comprensible cuando no hay datos, sin errores ni tablas desarmadas.
- **FR-008**: Las exportaciones a PDF y a Excel de Stock y Trazabilidad MUST generarse correctamente cuando no hay datos.
- **FR-009**: La limpieza MUST ejecutarse una sola vez como operación de puesta en marcha, sin exponer ninguna opción en la interfaz de la aplicación que permita repetirla.
- **FR-010**: La limpieza MUST poder ejecutarse de nuevo sin producir error si el sistema ya se encuentra vacío.

#### Limpieza del módulo de Importación y consecutivo del DO

- **FR-011**: El sistema MUST dejar el módulo de Importación sin ninguna importación visible para ningún rol tras el vaciado.
- **FR-012**: Las importaciones eliminadas MUST quedar marcadas como eliminadas y ser recuperables, no destruidas de forma definitiva.
- **FR-013**: Las importaciones eliminadas MUST quedar excluidas de todos los listados, reportes e informes del módulo.
- **FR-014**: El sistema MUST asignar a la siguiente importación creada el número de DO inmediatamente posterior al mayor número emitido para ese año antes del vaciado.
- **FR-015**: El sistema MUST garantizar que un número de DO ya emitido nunca se vuelva a asignar, aunque su importación haya sido eliminada.
- **FR-016**: El sistema MUST conservar el formato y la lógica de numeración por año ya existentes para el DO.
- **FR-017**: El sistema MUST impedir que dos importaciones creadas simultáneamente reciban el mismo número de DO.

#### Descuento independiente del contenedor

- **FR-018**: Los usuarios MUST poder registrar una transferencia sin seleccionar un contenedor de origen.
- **FR-019**: Los usuarios MUST poder registrar una salida sin seleccionar un contenedor de origen.
- **FR-020**: Cuando no se selecciona contenedor, el sistema MUST descontar la cantidad del total disponible del producto en la bodega, sin importar en qué contenedor se encuentre.
- **FR-021**: Cuando el usuario sí selecciona un contenedor, el sistema MUST asociar el movimiento a ese contenedor y validar la disponibilidad contra ese contenedor.
- **FR-022**: El sistema MUST validar la cantidad solicitada contra el total disponible del producto en la bodega cuando no se selecciona contenedor, y rechazar el movimiento con un mensaje claro si la excede.
- **FR-023**: El sistema MUST registrar en la Trazabilidad todo movimiento de transferencia y de salida realizado de aquí en adelante, se haya seleccionado contenedor o no.
- **FR-024**: La Trazabilidad MUST mostrar de forma clara y sin ambigüedad cuándo un movimiento no tiene contenedor asociado.
- **FR-025**: El sistema MUST reflejar el descuento en el Stock inmediatamente después de registrar el movimiento, de forma consistente entre la pantalla de Stock y sus exportaciones.
- **FR-026**: El sistema MUST mantener el comportamiento actual de confirmación de recepción de transferencias, incluidos los movimientos registrados sin contenedor.

### Key Entities *(include if data involved)*

- **Existencia (Stock)**: cantidad disponible de un producto en una bodega. No es un dato que se almacene directamente: se deriva de las entradas registradas menos las transferencias y salidas. Tras la limpieza, toda existencia queda en cero.
- **Movimiento de Trazabilidad**: registro histórico de una entrada o una salida de producto, con fecha, bodega, cantidad, referencia del documento que lo originó y, opcionalmente, el contenedor asociado. Se deriva de las mismas fuentes que la existencia. Tras la limpieza, no queda ningún movimiento.
- **Contenedor de inventario**: agrupación física de producto dentro de una bodega, con su propio saldo por producto. Deja de ser obligatorio para registrar movimientos de salida y pasa a ser un dato opcional.
- **Importación**: expediente documental de una importación, identificado por un número de DO único. Es independiente de los contenedores de inventario: vaciar Importación no afecta al Stock.
- **Consecutivo de DO**: numeración anual y única de las importaciones. Debe ser monótona creciente: un número emitido nunca se reutiliza, aunque su importación se elimine.
- **Transferencia**: movimiento de producto entre dos bodegas, con un estado de envío y de recepción. El contenedor de origen pasa a ser opcional.
- **Salida**: movimiento de producto que retira existencia de una bodega hacia un destino externo. El contenedor de origen ya era opcional y se mantiene así.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Tras la limpieza, el módulo de Stock muestra cero existencias para el 100% de las bodegas y para el 100% de los roles del sistema.
- **SC-002**: Tras la limpieza, el módulo de Trazabilidad muestra cero movimientos para el 100% de las combinaciones de bodega, producto y rango de fechas consultables.
- **SC-003**: Tras la limpieza, el módulo de Importación muestra cero importaciones para el 100% de los roles.
- **SC-004**: El 100% de los productos, bodegas, conductores y usuarios existentes antes de la limpieza sigue disponible después de ella.
- **SC-005**: La primera importación creada después del vaciado recibe un número de DO estrictamente mayor que todos los emitidos previamente, verificable en el 100% de los intentos.
- **SC-006**: Un usuario puede completar el registro de una transferencia o de una salida sin seleccionar contenedor en un solo intento, sin recibir errores de validación relacionados con el contenedor.
- **SC-007**: El 100% de las transferencias y salidas registradas después del cambio aparece en la Trazabilidad con su fecha, producto, bodega y cantidad correctos.
- **SC-008**: Las cantidades descontadas coinciden exactamente con las cantidades registradas en el 100% de los movimientos de prueba, sin diferencias entre la pantalla de Stock y sus exportaciones.
- **SC-009**: Las exportaciones a PDF y Excel de Stock, Trazabilidad e Importación se generan sin error sobre módulos vacíos en el 100% de los intentos.
- **SC-010**: Ningún movimiento puede registrarse por una cantidad superior a la disponible: el 100% de esos intentos se rechaza con un mensaje que indica la cantidad disponible.

## Assumptions

- **Alcance de la limpieza**: se ejecuta **una sola vez**, como operación de puesta en marcha. No se expone ningún botón ni opción en la aplicación para repetirla, lo que evita que alguien vacíe los módulos por error. (Confirmado con el solicitante.)
- **Reversibilidad**: el borrado es **suave / recuperable**. Los registros desaparecen de todas las pantallas, reportes y exportaciones, pero permanecen almacenados y marcados como eliminados para efectos de auditoría. (Confirmado con el solicitante.)
- **Contenedor opcional**: seleccionar contenedor en transferencias y salidas queda **a elección del usuario**. Si lo elige, el movimiento se asocia a ese contenedor; si no, se descuenta del total del producto y la trazabilidad queda sin contenedor. (Confirmado con el solicitante.)
- **Importación y Stock son independientes**: el expediente documental de Importación no está vinculado a las existencias de inventario. Por eso vaciar Importación no vacía el Stock, y ambas limpiezas deben hacerse de forma explícita y por separado.
- **Datos maestros preservados**: productos, bodegas, conductores, usuarios y sus asignaciones no se tocan. Solo se limpian existencias, movimientos e importaciones.
- **Módulos fuera de alcance**: Liquidaciones, ITR, gastos, rutas y peajes no se ven afectados.
- **Sin cambios de permisos**: los roles y sus alcances de visibilidad siguen funcionando como hoy; la limpieza no redefine quién ve qué.
- **Momento de ejecución**: la limpieza se realiza en una ventana en la que no hay operación en curso, de modo que no queden movimientos a medio registrar.
- **Unidad de medida**: se conserva el manejo actual de cajas y láminas; este cambio no altera cómo se convierten ni se presentan las cantidades.
- **"A partir del último que quede"**: se interpreta como que la numeración del DO continúa desde el mayor número emitido hasta el momento del vaciado, sin reiniciar en 001 y sin reutilizar números. El borrado suave permite deducirlo directamente de los registros conservados.
