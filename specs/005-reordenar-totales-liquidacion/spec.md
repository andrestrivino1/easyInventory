# Feature Specification: Reordenar y renombrar el panel de totales de la liquidación

**Feature Branch**: `005-reordenar-totales-liquidacion`
**Created**: 2026-05-28
**Status**: Draft
**Input**: User description: "vamos a ordenar esta parte a la izquierda irán estos campos: Sumatoria de gastos = ya está bien + descuento empresa; Sumatoria de peajes = ya está bien; Suma de gastos total de viaje; Valor flete pactado; Anticipo empresa de transporte; Saldo adeudado empresa de transporte. A la derecha: Anticipos conductor = (anticipo conductor + sobreanticipo); Ant - gastos; A favor de; Ganancia final de viaje. El campo descuentos (Empresa) irá arriba en los gastos de viaje y sumará a esos valores. Para el PDF no irá anticipo empresa pero sí en el formulario, y nuevamente agregaremos el campo sobre anticipo. La firma debe decir 'firma funcionario revisó'."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Panel de totales reordenado y renombrado (Priority: P1)

El usuario que revisa una liquidación de viaje ve el recuadro de totales reorganizado en dos columnas con etiquetas claras y orientadas al negocio. La columna izquierda agrupa los conceptos de costos y la relación con la **empresa de transporte**; la columna derecha agrupa la relación con el **conductor** y la rentabilidad final del viaje.

**Why this priority**: Es el corazón del cambio. El recuadro de totales es lo que el dueño/funcionario usa para entender cuánto costó el viaje, quién le debe a quién y cuánto ganó. Sin esta reorganización el resto de ajustes no tienen sentido.

**Independent Test**: Abrir una liquidación existente con valores conocidos y verificar que cada celda del recuadro muestra la etiqueta nueva y el número calculado según las fórmulas definidas, en la columna correcta.

**Acceptance Scenarios**:

1. **Given** una liquidación con gastos operativos, peajes, descuento empresa, anticipos y flete cargados, **When** el usuario abre el detalle de la liquidación, **Then** la columna izquierda muestra en orden: "Sumatoria de gastos", "Sumatoria de peajes", "Suma de gastos total de viaje", "Valor flete pactado", "Anticipo empresa de transporte" y "Saldo adeudado empresa de transporte".
2. **Given** la misma liquidación, **When** el usuario observa la columna derecha, **Then** ve en orden: "Anticipos conductor", "Ant - gastos", "A favor de" y "Ganancia final de viaje".
3. **Given** una liquidación con descuento empresa = 100.000 y gastos operativos = 3.159.000, **When** se muestra "Sumatoria de gastos", **Then** el valor mostrado es 3.259.000 (gastos operativos + descuento empresa).

---

### User Story 2 - Descuento empresa se mueve a los gastos del viaje (Priority: P1)

El campo "Descuentos (Empresa)" deja de estar como una celda aparte en el recuadro de totales y pasa a ubicarse **arriba, dentro de la sección de gastos del viaje**, sumándose a la sumatoria de gastos del viaje.

**Why this priority**: Esta decisión cambia cómo se calcula la "Sumatoria de gastos" y, en cascada, la "Suma de gastos total de viaje" y la "Ganancia final". Es prerequisito de los cálculos de la Historia 1.

**Independent Test**: Editar el descuento empresa de una liquidación y verificar que la "Sumatoria de gastos" y los totales que dependen de ella cambian en consecuencia.

**Acceptance Scenarios**:

1. **Given** una liquidación en edición, **When** el usuario captura el descuento empresa dentro de la sección de gastos del viaje, **Then** ese valor se suma a la sumatoria de gastos del viaje.
2. **Given** un descuento empresa modificado, **When** se recalcula la liquidación, **Then** "Sumatoria de gastos", "Suma de gastos total de viaje" y "Ganancia final de viaje" reflejan el nuevo descuento.

---

### User Story 3 - Reincorporar el campo "Sobre anticipo" del conductor (Priority: P2)

El formulario de liquidación vuelve a tener un campo independiente "Sobre anticipo" que el usuario captura, y los "Anticipos conductor" del recuadro se calculan como `anticipo conductor + sobre anticipo`.

**Why this priority**: Necesario para que la celda "Anticipos conductor" y, en cascada, "Ant - gastos" y "A favor de" se calculen correctamente. Sin el campo, la suma queda incompleta.

**Independent Test**: Capturar anticipo conductor = 3.000.000 y sobre anticipo = 500.000 y verificar que "Anticipos conductor" muestra 3.500.000.

**Acceptance Scenarios**:

1. **Given** el formulario de liquidación, **When** el usuario abre la sección de anticipos, **Then** existe un campo "Sobre anticipo" editable además de "Anticipo conductor" y "Anticipo empresa".
2. **Given** anticipo conductor = 3.000.000 y sobre anticipo = 500.000, **When** se guarda y muestra la liquidación, **Then** "Anticipos conductor" = 3.500.000.

---

### User Story 4 - Ajustes al PDF y a la firma (Priority: P2)

El PDF de la liquidación ya no muestra el "Anticipo empresa" en su encabezado, pero el formulario sí lo conserva. La línea de firma del PDF dice "FIRMA FUNCIONARIO REVISÓ" en lugar de "FIRMA CONDUCTOR".

**Why this priority**: El PDF es el documento que se entrega/archiva; el anticipo empresa es información interna que no debe imprimirse, y la firma debe reflejar quién revisó el documento.

**Independent Test**: Generar el PDF de una liquidación y verificar que no aparece el anticipo empresa en el encabezado y que la firma dice "FIRMA FUNCIONARIO REVISÓ".

**Acceptance Scenarios**:

1. **Given** una liquidación con anticipo empresa cargado, **When** se genera el PDF, **Then** el encabezado del PDF no muestra el anticipo empresa.
2. **Given** la misma liquidación, **When** se abre el formulario de edición, **Then** el campo "Anticipo empresa" sigue presente y editable.
3. **Given** cualquier liquidación, **When** se genera el PDF, **Then** la línea de firma dice "FIRMA FUNCIONARIO REVISÓ".

---

### Edge Cases

- **Descuento empresa = 0**: "Sumatoria de gastos" debe igualar la sumatoria de gastos operativos (sin alteración).
- **Sobre anticipo vacío/0**: "Anticipos conductor" debe igualar el anticipo conductor.
- **Saldo adeudado negativo**: si el anticipo empresa supera el valor flete pactado, "Saldo adeudado empresa de transporte" puede ser negativo; debe mostrarse con su signo y resaltado coherente con el resto del recuadro.
- **Ganancia final negativa**: si los gastos totales superan el flete, "Ganancia final de viaje" es negativa y debe mostrarse resaltada en rojo.
- **Ant - gastos = 0**: cuando la sumatoria de gastos iguala los anticipos del conductor, "A favor de" muestra "ninguno".
- **Liquidaciones históricas** creadas antes de existir el campo "Sobre anticipo": deben tratarse como sobre anticipo = 0 sin romper los cálculos.

## Requirements *(mandatory)*

### Functional Requirements

#### Recuadro de totales — columna izquierda

- **FR-001**: El sistema MUST mostrar la celda "Sumatoria de gastos" con el valor `sumatoria de gastos operativos + descuento empresa`.
- **FR-002**: El sistema MUST mostrar la celda "Sumatoria de peajes" con el mismo valor que hoy (suma de peajes usados del viaje), sin cambios de cálculo.
- **FR-003**: El sistema MUST mostrar la celda "Suma de gastos total de viaje" con el valor `Sumatoria de gastos (FR-001) + Sumatoria de peajes (FR-002)`. *(Ver Assumptions: la definición original del usuario era circular; se asume esta fórmula como total de gastos del viaje.)*
- **FR-004**: El sistema MUST mostrar la celda "Valor flete pactado" con el valor del flete capturado (sin cambios de cálculo, solo nueva etiqueta).
- **FR-005**: El sistema MUST mostrar la celda "Anticipo empresa de transporte" con el valor del anticipo empresa capturado.
- **FR-006**: El sistema MUST mostrar la celda "Saldo adeudado empresa de transporte" con el valor `Valor flete pactado − Anticipo empresa de transporte`.

#### Recuadro de totales — columna derecha

- **FR-007**: El sistema MUST mostrar la celda "Anticipos conductor" con el valor `anticipo conductor + sobre anticipo`.
- **FR-008**: El sistema MUST mostrar la celda "Ant - gastos" con el valor `Sumatoria de gastos (FR-001, incluye descuento empresa) − Anticipos conductor (FR-007)`.
- **FR-009**: El sistema MUST mostrar la celda "A favor de" derivada del signo de "Ant - gastos" (FR-008): si "Ant - gastos" es positivo (gastos > anticipos conductor) queda a favor del **conductor**; si es negativo, a favor de la **empresa**; si es cero, **ninguno**.
- **FR-010**: El sistema MUST mostrar la celda "Ganancia final de viaje" con el valor `Valor flete pactado − Suma de gastos total de viaje (FR-003)`.

#### Gastos del viaje y descuento empresa

- **FR-011**: El sistema MUST ubicar el campo "Descuentos (Empresa)" dentro de la sección de gastos del viaje (arriba), no como celda independiente del recuadro de totales.
- **FR-012**: El sistema MUST sumar el descuento empresa a la sumatoria de gastos del viaje, de modo que afecte FR-001, FR-003 y FR-010.

#### Anticipos y formulario

- **FR-013**: El sistema MUST ofrecer en el formulario un campo "Sobre anticipo" editable, independiente de "Anticipo conductor" y "Anticipo empresa".
- **FR-014**: El sistema MUST persistir el valor de "Sobre anticipo" por liquidación y usarlo en FR-007.
- **FR-015**: El sistema MUST conservar en el formulario el campo "Anticipo empresa" editable.
- **FR-016**: El sistema MUST tratar las liquidaciones sin valor de "Sobre anticipo" (históricas) como sobre anticipo = 0.

#### PDF y firma

- **FR-017**: El sistema MUST omitir el "Anticipo empresa" únicamente del **encabezado** del PDF de la liquidación. El recuadro de totales del PDF SÍ imprime las celdas "Anticipo empresa de transporte" (FR-005) y "Saldo adeudado empresa de transporte" (FR-006).
- **FR-018**: El sistema MUST cambiar la etiqueta de la línea de firma del PDF a "FIRMA FUNCIONARIO REVISÓ".

#### Consistencia

- **FR-019**: El sistema MUST aplicar el nuevo orden, etiquetas y fórmulas de manera consistente entre la vista de detalle en pantalla y el PDF (salvo las omisiones específicas del PDF en FR-017).
- **FR-020**: El sistema MUST recalcular todos los valores derivados cuando cambien gastos, peajes, descuento empresa, anticipos, sobre anticipo o flete.

### Key Entities *(include if feature involves data)*

- **Liquidación de viaje**: representa el ajuste económico de un viaje. Atributos relevantes para esta feature: gastos operativos (sumatoria), peajes (sumatoria total y porción del conductor), descuento empresa, valor flete, anticipo empresa, anticipo conductor, sobre anticipo (nuevo), y los valores derivados (sumatoria de gastos, suma de gastos total, saldo adeudado empresa, anticipos conductor, ant - gastos, a favor de, ganancia final).
- **Sobre anticipo**: nuevo dato monetario capturado por liquidación, que se suma al anticipo del conductor para el cálculo de "Anticipos conductor".

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las celdas del recuadro de totales (pantalla y PDF) usan las etiquetas y el orden nuevos descritos en FR-001 a FR-010.
- **SC-002**: Para un conjunto de liquidaciones de prueba con valores conocidos, el 100% de los valores derivados (Sumatoria de gastos, Suma de gastos total, Saldo adeudado empresa, Anticipos conductor, Ant - gastos, Ganancia final) coinciden con el cálculo manual esperado.
- **SC-003**: El PDF generado no contiene el dato del anticipo empresa donde se definió omitirlo, verificable por inspección del documento.
- **SC-004**: La línea de firma del PDF dice "FIRMA FUNCIONARIO REVISÓ" en el 100% de los PDFs generados.
- **SC-005**: Modificar el descuento empresa o el sobre anticipo y recalcular actualiza los totales dependientes sin requerir intervención manual adicional.

## Assumptions

- "Suma de gastos total de viaje" se interpreta como `Sumatoria de gastos (incl. descuento empresa) + Sumatoria de peajes`, ya que la definición textual del usuario era auto-referente (circular). Mantiene el patrón del actual "SUMATORIA DE GASTOS (TOTAL)".
- "Sobre anticipo" se almacena como un nuevo dato monetario por liquidación (entero, sin centavos, igual que el resto de montos), con valor por defecto 0.
- "Anticipo empresa" se conserva en el formulario y solo se omite en el PDF; el cálculo de "Saldo adeudado empresa de transporte" sigue usándolo internamente.
- El resaltado de signo (verde para positivo, rojo para negativo) se mantiene coherente con el comportamiento actual del recuadro.
- "Ant - gastos" usa la Sumatoria de gastos que INCLUYE el descuento empresa (FR-001); "A favor de" sigue el signo de "Ant - gastos" (positivo = gastos > anticipos → conductor; negativo → empresa). Confirmado por el usuario con un ejemplo.
- En el PDF solo se omite el anticipo empresa del encabezado; las celdas de empresa del recuadro de totales sí se imprimen. Confirmado por el usuario.
- El alcance es la vista de detalle de la liquidación individual, su formulario, y su PDF. El panel consolidado de varias liquidaciones (índice) no forma parte de este cambio salvo que un cálculo compartido lo afecte indirectamente.
- Los conceptos de "Saldo pendiente" y "Saldo viaje" actuales se reemplazan/renombran por las nuevas celdas ("Saldo adeudado empresa de transporte" y "Ant - gastos") en la presentación; la decisión de conservar o no las columnas internas subyacentes se resuelve en la fase de planeación.
