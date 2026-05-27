# Feature Specification: Ajustes de liquidación y gastos mensuales

**Feature Branch**: `004-ajustes-liquidacion-gastos`
**Created**: 2026-05-27
**Status**: Draft
**Input**: User description: "se agregaran algunos nuevos ajustes: 1) Poder eliminar un peaje de una ruta en el proceso de los peajes del viaje. 2) Agregar un botón 'gastos mensuales'. 3) Dentro de gastos mensuales mostrar lista con los gastos ingresados (Sueldo conductor, Seguridad social conductor, Cuota banco, Cuota tercero, Satelital, Seguro vehículo, Otro); modificar y eliminar; al crear se selecciona conductor y su vehículo asociado; filtrar por placas y paginar las listas. 4) En anticipos serán dos campos (anticipo empresa y anticipo conductor), más un campo de descuentos de la empresa de transporte y saldo pendiente. 5) Permitir subir un PDF para montar los manifiestos de cada viaje. 6) En los totales mostrar los descuentos que realizó la empresa."

## Clarifications

### Session 2026-05-27

- Q: ¿Qué alcance tiene el rol "placas" sobre el módulo de Gastos mensuales? → A: Solo administradores gestionan y ven gastos mensuales; el rol "placas" no accede a esta pantalla.
- Q: ¿El campo "Otro" del gasto mensual lleva solo monto o también descripción? → A: Monto "Otro" + una descripción/etiqueta de texto que explique el concepto.
- Q: ¿Cuántos PDFs de manifiesto admite cada liquidación? → A: Un solo PDF por liquidación, reemplazable o eliminable.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Registrar y gestionar gastos mensuales por conductor/placa (Priority: P1)

El administrador necesita llevar un registro de los costos fijos mensuales asociados a cada conductor y su vehículo (placa), separado de los gastos operativos de cada viaje. Desde el módulo de Liquidación de Viajes, un nuevo botón **"Gastos mensuales"** abre una pantalla con la lista de gastos mensuales registrados. Al crear un gasto mensual, el administrador selecciona el conductor (lo que define automáticamente la placa/vehículo asociado) y captura los valores: Sueldo conductor, Seguridad social conductor, Cuota banco, Cuota tercero, Satelital, Seguro vehículo y Otro. Cada registro puede modificarse o eliminarse. La lista se puede filtrar por placa y está paginada.

**Why this priority**: Es la pieza de mayor valor nuevo y totalmente autónoma: no depende de ninguno de los demás ajustes y entrega de inmediato la capacidad de controlar los costos fijos por vehículo. Puede construirse, probarse y demostrarse por sí sola.

**Independent Test**: Entrar al módulo, pulsar "Gastos mensuales", crear un registro eligiendo un conductor y capturando los siete valores, guardar, verlo en la lista, editarlo, filtrarlo por placa y eliminarlo. La lista debe paginar cuando hay más registros que el tamaño de página.

**Acceptance Scenarios**:

1. **Given** un administrador en el módulo de Liquidación de Viajes, **When** observa la pantalla principal, **Then** ve un botón "Gastos mensuales" que abre la pantalla de gastos mensuales.
2. **Given** la pantalla de creación de gasto mensual, **When** selecciona un conductor, **Then** la placa/vehículo asociado se muestra automáticamente a partir del conductor elegido.
3. **Given** un conductor seleccionado, **When** captura los valores (Sueldo conductor, Seguridad social conductor, Cuota banco, Cuota tercero, Satelital, Seguro vehículo, Otro) y guarda, **Then** el gasto mensual queda registrado y aparece en la lista.
4. **Given** un gasto mensual existente, **When** el administrador lo edita y guarda, **Then** los nuevos valores persisten; **When** lo elimina, **Then** desaparece de la lista.
5. **Given** una lista con gastos de varias placas, **When** el administrador filtra por una placa, **Then** solo se muestran los gastos de esa placa.
6. **Given** más gastos que el tamaño de página, **When** el administrador navega la lista, **Then** los registros se presentan paginados.

---

### User Story 2 - Anticipos diferenciados, descuentos de la transportadora y saldo pendiente (Priority: P2)

Al registrar/editar una liquidación, el operador necesita separar el anticipo en dos conceptos —**anticipo empresa** y **anticipo conductor**— y registrar los **descuentos** que aplica la empresa de transporte, viendo además el **saldo pendiente** resultante. Los totales de la liquidación deben mostrar explícitamente los descuentos realizados por la empresa.

**Why this priority**: Cambia la matemática financiera central de la liquidación, por lo que tiene alto valor pero también más riesgo; se prioriza después del módulo autónomo de gastos mensuales para no bloquear ese valor.

**Independent Test**: Crear/editar una liquidación capturando anticipo empresa y anticipo conductor por separado, un valor de descuentos de la transportadora, y verificar que el saldo pendiente y el panel de totales reflejan esos valores (incluyendo una línea visible de descuentos de la empresa) tanto en pantalla como en el PDF.

**Acceptance Scenarios**:

1. **Given** el formulario de liquidación, **When** el operador llega a la sección de anticipos, **Then** ve dos campos separados: "Anticipo empresa" y "Anticipo conductor".
2. **Given** la sección de anticipos, **When** el operador captura un valor de "Descuentos" de la empresa de transporte, **Then** ese valor se guarda asociado a la liquidación.
3. **Given** valores de anticipos y descuentos capturados, **When** el operador observa la liquidación, **Then** se muestra un "Saldo pendiente" derivado de esos valores.
4. **Given** una liquidación con descuentos, **When** el operador revisa el panel de totales (en pantalla y en el PDF), **Then** aparece una línea explícita con los descuentos realizados por la empresa.

---

### User Story 3 - Eliminar un peaje en los peajes del viaje (Priority: P3)

Al editar la tabla "Peajes del viaje" de una liquidación, el operador necesita poder **eliminar** una fila de peaje (no solo marcarla como "no usado"), por ejemplo cuando un peaje del catálogo de la ruta no aplica a ese viaje en particular.

**Why this priority**: Es una mejora de usabilidad acotada sobre una tabla que ya existe; aporta valor pero es de menor alcance que los ajustes financieros y el nuevo módulo.

**Independent Test**: Abrir una liquidación con peajes precargados desde la ruta, eliminar una fila de peaje, guardar y confirmar que esa fila ya no forma parte de la liquidación ni de sus totales.

**Acceptance Scenarios**:

1. **Given** la tabla "Peajes del viaje" con varias filas, **When** el operador acciona "eliminar" en una fila, **Then** esa fila se quita de la lista de peajes del viaje.
2. **Given** una fila de peaje eliminada y la liquidación guardada, **When** se recalculan los totales, **Then** el valor de ese peaje ya no se suma a la sumatoria de peajes.
3. **Given** un peaje proveniente del catálogo de la ruta, **When** se elimina dentro de la liquidación, **Then** la eliminación afecta solo a esa liquidación y no modifica el catálogo de la ruta.

---

### User Story 4 - Adjuntar el PDF del manifiesto al viaje (Priority: P3)

El operador necesita **subir un archivo PDF** con el manifiesto correspondiente a cada viaje, de modo que quede asociado a la liquidación y pueda consultarse/descargarse luego.

**Why this priority**: Aporta trazabilidad documental, pero la liquidación es funcional sin ello; por eso es prioridad baja.

**Independent Test**: Crear/editar una liquidación, subir un PDF de manifiesto, guardar, y verificar que el documento queda asociado y es consultable/descargable; reemplazarlo o eliminarlo y confirmar el cambio.

**Acceptance Scenarios**:

1. **Given** el formulario de liquidación, **When** el operador sube un archivo PDF como manifiesto, **Then** el archivo queda asociado a esa liquidación.
2. **Given** una liquidación con manifiesto cargado, **When** el operador la consulta, **Then** puede ver/descargar el PDF del manifiesto.
3. **Given** un archivo que no es PDF, **When** el operador intenta subirlo como manifiesto, **Then** el sistema lo rechaza con un mensaje claro.

---

### Edge Cases

- **Gasto mensual sin valores**: ¿Se permite guardar un gasto mensual con todos los campos en cero? (Asunción: se permite, todos los montos por defecto 0; al menos debe haber un conductor seleccionado.)
- **Conductor sin placa registrada**: si el conductor seleccionado no tiene `vehicle_plate`, el gasto mensual queda sin placa para filtrar; el sistema debe manejarlo sin error (mostrarlo como "sin placa").
- **Duplicados de gasto mensual**: al intentar registrar un segundo gasto del mismo conductor para el mismo período (mes/año), el sistema debe impedirlo con un mensaje claro (FR-007b).
- **Eliminar el último peaje del viaje**: la tabla puede quedar sin filas; los totales de peajes deben quedar en 0 sin error.
- **PDF de manifiesto demasiado grande / corrupto**: el sistema debe rechazar archivos que excedan el tamaño máximo o que no sean PDF válidos, con mensaje claro.
- **Liquidación en estado "cerrada" o "anulada"**: las ediciones (anticipos, descuentos, peajes, manifiesto) deben respetar las reglas de estado existentes (no editable fuera de "borrador", según el módulo actual).
- **Descuentos mayores que el anticipo empresa**: como saldo pendiente = anticipo empresa − descuentos, el resultado puede ser negativo o cero; el sistema debe mostrarlo coherentemente sin bloquear el guardado.
- **Acceso por rol "placas"**: Gastos mensuales es exclusivo de administradores (placas no accede). Para los manifiestos PDF sigue aplicando el aislamiento del rol existente: un usuario "placas" solo gestiona/consulta manifiestos de las liquidaciones de los conductores que tiene asignados.

## Requirements *(mandatory)*

### Functional Requirements

#### Gastos mensuales (US1)

- **FR-001**: El módulo de Liquidación de Viajes MUST ofrecer un botón/acceso "Gastos mensuales" que abra una pantalla dedicada de gastos mensuales.
- **FR-002**: El sistema MUST permitir crear un gasto mensual seleccionando un conductor; la placa/vehículo asociado MUST derivarse automáticamente del conductor seleccionado y mostrarse al usuario.
- **FR-003**: Cada gasto mensual MUST contener los siguientes montos: Sueldo conductor, Seguridad social conductor, Cuota banco, Cuota tercero, Satelital, Seguro vehículo y Otro. El campo "Otro" MUST incluir, además del monto, una descripción/etiqueta de texto que explique el concepto.
- **FR-004**: El sistema MUST permitir modificar y eliminar un gasto mensual existente.
- **FR-005**: El sistema MUST mostrar la lista de gastos mensuales y MUST permitir filtrarla por placa.
- **FR-006**: La lista de gastos mensuales MUST estar paginada.
- **FR-007**: Cada gasto mensual MUST corresponder a un período mensual (mes/año) que se selecciona al crearlo; la lista MUST poder filtrarse por placa y por mes, y MUST conservar el historial mensual (un registro por conductor por mes).
- **FR-007b**: El sistema MUST impedir registrar dos gastos mensuales para el mismo conductor en el mismo período (mes/año).
- **FR-008**: Los gastos mensuales MUST ser un registro independiente de los gastos operativos por viaje (`liquidacion_expenses`) y NO se inyectan automáticamente en el cálculo de cada liquidación. (Ver Asunciones.)
- **FR-009**: El módulo de Gastos mensuales MUST ser exclusivo de administradores: el rol "placas" NO MUST tener acceso a esta pantalla ni a sus datos (el botón/acceso no se muestra a usuarios "placas").

#### Anticipos, descuentos y saldo pendiente (US2)

- **FR-010**: La liquidación MUST registrar el anticipo en dos campos separados: "Anticipo empresa" y "Anticipo conductor".
- **FR-011**: Los campos "Anticipo empresa" y "Anticipo conductor" MUST reemplazar a los campos existentes `anticipo` y `sobreanticipo` respectivamente; los datos existentes MUST migrarse (anticipo → anticipo empresa, sobreanticipo → anticipo conductor).
- **FR-012**: La liquidación MUST registrar un campo de "Descuentos" correspondiente a los descuentos que aplica la empresa de transporte.
- **FR-013**: El sistema MUST mostrar un "Saldo pendiente" calculado como **anticipo empresa − descuentos** (los descuentos de la empresa de transporte se restan del anticipo de la empresa), y MUST recalcularlo automáticamente al cambiar el anticipo empresa o los descuentos.
- **FR-014**: El panel de totales (en pantalla y en el PDF) MUST mostrar una línea explícita con los descuentos realizados por la empresa.
- **FR-015**: Todos los montos derivados afectados (sumatorias y saldos) MUST recalcularse de forma consistente cuando cambien los anticipos o los descuentos.

#### Peajes del viaje (US3)

- **FR-016**: En la tabla "Peajes del viaje" de una liquidación, el operador MUST poder eliminar una fila de peaje, además de la opción existente de marcarla como "no usado".
- **FR-017**: Eliminar un peaje dentro de una liquidación MUST afectar únicamente a esa liquidación y NO MUST modificar el catálogo de peajes de la ruta.
- **FR-018**: Tras eliminar un peaje, la sumatoria de peajes y los totales de la liquidación MUST recalcularse excluyendo ese peaje.

#### Manifiesto PDF (US4)

- **FR-019**: El sistema MUST permitir subir un único archivo PDF como manifiesto asociado a una liquidación/viaje. Subir un nuevo manifiesto MUST reemplazar al anterior; el sistema MUST permitir también eliminar el manifiesto cargado.
- **FR-020**: El sistema MUST permitir consultar/descargar el PDF del manifiesto de una liquidación que lo tenga cargado.
- **FR-021**: El sistema MUST rechazar archivos que no sean PDF o que excedan el tamaño máximo permitido, con un mensaje claro.

#### Reglas transversales

- **FR-022**: Todas las ediciones (anticipos, descuentos, peajes, manifiesto) MUST respetar las reglas de estado existentes de la liquidación (editable solo en estado "borrador").
- **FR-023**: Los montos monetarios MUST manejarse como pesos colombianos enteros (sin decimales), de forma coherente con el módulo actual.

### Key Entities *(include if feature involves data)*

- **Gasto mensual**: Costo fijo mensual asociado a un conductor y su vehículo (placa). Atributos: conductor (y placa derivada), período mensual, y los montos Sueldo conductor, Seguridad social conductor, Cuota banco, Cuota tercero, Satelital, Seguro vehículo, Otro (monto + descripción de texto). Independiente de las liquidaciones por viaje.
- **Liquidación (existente, ampliada)**: Se amplía con anticipo empresa (reemplaza `anticipo`), anticipo conductor (reemplaza `sobreanticipo`), descuentos de la transportadora, saldo pendiente (= anticipo empresa − descuentos) y un único documento PDF de manifiesto adjunto (reemplazable/eliminable).
- **Peaje del viaje (existente)**: Fila de peaje dentro de una liquidación; ahora puede eliminarse (no solo marcarse como no usada).
- **Conductor / Vehículo (existente)**: El conductor posee una placa (`vehicle_plate`); seleccionar conductor determina el vehículo asociado tanto en gastos mensuales como en la liquidación.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador puede crear un gasto mensual completo (conductor + siete valores) en menos de 1 minuto.
- **SC-002**: La lista de gastos mensuales filtra por placa y muestra únicamente los registros de esa placa en el 100% de los casos.
- **SC-003**: La lista de gastos mensuales se mantiene utilizable (paginada) con al menos 500 registros sin degradación perceptible.
- **SC-004**: En el 100% de las liquidaciones, anticipo empresa y anticipo conductor se capturan y muestran por separado, y el saldo pendiente coincide con la fórmula definida (anticipo empresa − descuentos).
- **SC-005**: Los descuentos de la empresa aparecen como línea explícita en el 100% de los totales mostrados en pantalla y en el PDF.
- **SC-006**: Un operador puede eliminar un peaje del viaje y ver los totales recalculados sin recargar manualmente datos, en menos de 5 segundos.
- **SC-007**: Un operador puede adjuntar un PDF de manifiesto y volver a abrirlo/descargarlo desde la liquidación en el 100% de los intentos con archivos PDF válidos.
- **SC-008**: Los archivos que no son PDF se rechazan en el 100% de los intentos con un mensaje claro.

## Assumptions

- El módulo base de Liquidación de Viajes (especificaciones 002 y 003) ya existe y se reutiliza; estos ajustes lo amplían.
- "Conductor y su vehículo asociado" se interpreta usando el campo de placa existente del conductor (`drivers.vehicle_plate`); no se crea una entidad de vehículo independiente.
- Los gastos mensuales son un registro independiente (informativo/control de costos fijos) y, salvo indicación contraria, NO se suman automáticamente al cálculo de cada liquidación por viaje.
- "Descuentos" es un único campo monetario por liquidación (no una lista de líneas de descuento).
- Gastos mensuales es un módulo exclusivo de administradores; el rol "placas" no accede a él. Los manifiestos PDF sí siguen el aislamiento del rol "placas" (solo los de sus conductores asignados).
- Las ediciones solo se permiten mientras la liquidación está en estado "borrador", igual que el comportamiento actual del módulo.
- El tamaño máximo del PDF de manifiesto y el tamaño de página de las listas seguirán los valores por defecto razonables del proyecto (p. ej. paginación estándar de la app); se afinarán en la fase de planificación.
