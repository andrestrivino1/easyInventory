# Feature Specification: Informes y Analítica de Liquidaciones de Viajes

**Feature Branch**: `007-reportes-liquidaciones`
**Created**: 2026-06-09
**Status**: Draft
**Input**: User description: "Módulo de Informes y Analítica de Liquidaciones de Viajes (solo para rol admin): gráficas y PDF mensual, semestral y anual con el detalle de gastos (peajes, viáticos, categorías operativas, gastos fijos mensuales), ingresos por fletes y utilidad neta del periodo; vista consolidada de la empresa con desglose por conductor/placa."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Informe consolidado de un periodo con utilidad neta (Priority: P1)

El administrador entra al módulo de Informes, elige un periodo (un mes específico, un semestre o un año completo) y obtiene un resumen consolidado de toda la operación: cuánto ingresó por fletes, cuánto se gastó en total y desglosado por concepto (peajes, viáticos y cada categoría de gasto operativo, más los gastos fijos mensuales), y la utilidad neta del periodo indicando claramente si fue ganancia o pérdida.

**Why this priority**: Es el corazón del módulo y entrega valor por sí solo. Sin esto no hay informe; con solo esto el administrador ya puede responder "¿cuánto gané o perdí este mes/semestre/año y en qué se fue la plata?".

**Independent Test**: Se puede probar seleccionando un mes con liquidaciones cargadas y verificando que los totales (fletes, gastos por concepto, gastos fijos y utilidad neta) coincidan con la suma manual de las liquidaciones activas de ese periodo, y que el signo de la utilidad (ganancia/pérdida) sea correcto.

**Acceptance Scenarios**:

1. **Given** un mes con varias liquidaciones activas y sus gastos fijos mensuales registrados, **When** el administrador selecciona ese mes, **Then** ve el total de fletes, el total de cada concepto de gasto (peajes, viáticos y demás categorías operativas), el total de gastos fijos mensuales y la utilidad neta = fletes − (gastos operativos + peajes + gastos fijos mensuales).
2. **Given** un periodo cuyos gastos superan los ingresos, **When** se muestra el resultado, **Then** la utilidad neta aparece como pérdida, claramente diferenciada visualmente de una ganancia.
3. **Given** un semestre o un año seleccionado, **When** se calcula el informe, **Then** los totales agregan correctamente todos los meses incluidos en el rango.
4. **Given** liquidaciones en estado anulada o eliminada dentro del periodo, **When** se calcula el informe, **Then** esas liquidaciones NO se incluyen en ningún total.

---

### User Story 2 - Gráficas de evolución mensual y desglose de gastos (Priority: P2)

El administrador visualiza gráficas que muestran la evolución mes a mes de ingresos, gastos y utilidad a lo largo del periodo seleccionado, identificando en qué meses se generó más ganancia y en cuáles más pérdida, junto con un desglose visual de los gastos por categoría.

**Why this priority**: Convierte los números en una lectura rápida de tendencias y picos. Aporta mucho valor analítico, pero depende de que los cálculos consolidados (P1) ya existan.

**Independent Test**: Se puede probar con un año cargado verificando que la gráfica de evolución muestre un punto/barra por mes con valores correctos, que resalte el mes de mayor ganancia y el de mayor pérdida, y que el desglose por categoría sume el mismo total de gastos del resumen.

**Acceptance Scenarios**:

1. **Given** un año seleccionado con datos en varios meses, **When** se muestra el dashboard, **Then** aparece una gráfica de evolución mensual con ingresos, gastos y utilidad por mes.
2. **Given** la gráfica de evolución, **When** el administrador la observa, **Then** puede identificar el mes con mayor ganancia y el mes con mayor pérdida del periodo.
3. **Given** los gastos del periodo, **When** se muestra el desglose por categoría, **Then** se presenta una gráfica (barras o torta) con la participación de cada concepto de gasto.
4. **Given** un periodo sin liquidaciones activas, **When** se abre el dashboard, **Then** se muestra un estado vacío claro en lugar de gráficas en blanco o con error.

---

### User Story 3 - Exportar el informe a PDF (Priority: P2)

El administrador genera y descarga un PDF del informe para el periodo seleccionado (mensual, semestral o anual), con el detalle de ingresos, gastos por concepto, gastos fijos mensuales y la utilidad neta, apto para archivar o compartir.

**Why this priority**: Es un entregable explícitamente pedido. Tiene la misma prioridad que las gráficas porque es un objetivo central, pero reutiliza los cálculos de P1.

**Independent Test**: Se puede probar generando el PDF de un periodo y verificando que los totales del documento coincidan con los de la vista en pantalla y que el archivo descargue correctamente con el periodo identificado.

**Acceptance Scenarios**:

1. **Given** un periodo con datos, **When** el administrador genera el PDF, **Then** descarga un documento que incluye el rango del periodo, los ingresos por fletes, el desglose de gastos por concepto, los gastos fijos mensuales y la utilidad neta con su signo (ganancia/pérdida).
2. **Given** el mismo periodo visto en pantalla, **When** se compara con el PDF generado, **Then** los totales coinciden.
3. **Given** un periodo anual, **When** se genera el PDF, **Then** el documento refleja la totalización del año completo.

---

### User Story 4 - Desglose por conductor / placa (Priority: P3)

Sobre el informe consolidado, el administrador puede filtrar o desglosar por un conductor o placa específico para ver cuánto aportó cada uno a los ingresos, gastos y utilidad del periodo.

**Why this priority**: Aporta profundidad analítica útil para decisiones por vehículo, pero el módulo ya es valioso sin ella; por eso es la última prioridad.

**Independent Test**: Se puede probar seleccionando un conductor con liquidaciones en el periodo y verificando que sus totales sean un subconjunto correcto del consolidado, incluyendo sus gastos fijos mensuales propios.

**Acceptance Scenarios**:

1. **Given** un periodo consolidado, **When** el administrador filtra por un conductor/placa, **Then** todos los totales y gráficas se recalculan solo con las liquidaciones y gastos fijos de ese conductor.
2. **Given** el desglose por conductor, **When** se suman los resultados de todos los conductores del periodo, **Then** el total coincide con el consolidado de la empresa para ese periodo.
3. **Given** un PDF generado con un conductor filtrado, **When** se descarga, **Then** el documento indica claramente que corresponde a ese conductor/placa.

---

### Edge Cases

- **Periodo sin datos**: un mes/semestre/año sin liquidaciones activas debe mostrar un estado vacío con utilidad neta en cero, no un error.
- **Gastos fijos sin liquidaciones (o viceversa)**: un mes con gastos fijos mensuales registrados pero sin viajes debe reflejar la pérdida por esos costos fijos; un mes con viajes pero sin gastos fijos registrados debe calcular la utilidad solo con los gastos disponibles.
- **Liquidaciones anuladas o en borrador**: deben excluirse de todos los totales y gráficas.
- **Conductor sin gastos fijos del mes**: el desglose por conductor debe funcionar usando los gastos fijos que existan, sin asumir valores inventados.
- **Rangos a caballo entre años** (semestre/año): el cálculo debe atribuir cada liquidación al periodo según su fecha de inicio de viaje.
- **Aislamiento por cliente**: un administrador solo ve datos de su propio cliente/tenant; nunca de otro.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST ofrecer un módulo de Informes de Liquidaciones accesible únicamente para el rol administrador.
- **FR-002**: El sistema MUST permitir seleccionar el periodo del informe en tres granularidades: un mes específico, un semestre o un año completo.
- **FR-003**: El sistema MUST calcular y mostrar, para el periodo seleccionado, el total de ingresos por fletes de las liquidaciones activas.
- **FR-004**: El sistema MUST mostrar el total gastado desglosado por concepto: peajes, viáticos y cada una de las categorías de gasto operativo existentes.
- **FR-005**: El sistema MUST incluir los gastos fijos mensuales (sueldo conductor, seguridad social, cuota banco, cuota tercero, satelital, seguro vehículo y otros) correspondientes al periodo.
- **FR-006**: El sistema MUST calcular la utilidad neta del periodo como fletes − (gastos operativos + peajes + gastos fijos mensuales) e indicar claramente si el resultado es ganancia o pérdida.
- **FR-007**: El sistema MUST excluir de todos los cálculos las liquidaciones anuladas y las eliminadas (solo cuentan las activas).
- **FR-008**: El sistema MUST atribuir cada liquidación al periodo según su fecha de inicio de viaje.
- **FR-009**: El sistema MUST presentar una gráfica de evolución mes a mes de ingresos, gastos y utilidad dentro del periodo seleccionado.
- **FR-010**: El sistema MUST resaltar, en la evolución mensual, el mes de mayor ganancia y el de mayor pérdida del periodo.
- **FR-011**: El sistema MUST presentar una gráfica de desglose de gastos por categoría cuya suma coincida con el total de gastos del resumen.
- **FR-012**: El sistema MUST permitir generar y descargar el informe del periodo en formato PDF, con los mismos totales mostrados en pantalla.
- **FR-013**: El PDF MUST identificar el periodo (y el conductor/placa, si está filtrado) e incluir ingresos, desglose de gastos, gastos fijos y utilidad neta con su signo.
- **FR-014**: El sistema MUST mostrar una vista consolidada de toda la empresa por defecto y permitir filtrar/desglosar por un conductor o placa específico.
- **FR-015**: Al filtrar por conductor/placa, el sistema MUST recalcular todos los totales y gráficas usando solo las liquidaciones y los gastos fijos de ese conductor.
- **FR-016**: El sistema MUST respetar el aislamiento de datos por cliente (multi-tenant) existente, mostrando solo datos del cliente del administrador.
- **FR-017**: El sistema MUST mostrar un estado vacío claro cuando el periodo seleccionado no tenga liquidaciones activas, sin generar errores.
- **FR-018**: La suma de los desgloses por conductor de un periodo MUST coincidir con el consolidado de la empresa para ese mismo periodo.

### Key Entities *(include if feature involves data)*

- **Liquidación de viaje**: registro de un viaje liquidado; aporta el ingreso por flete, la ganancia de viaje y los totales de peajes y gastos operativos; tiene un estado (cuenta solo si está activa) y una fecha de inicio que determina el periodo.
- **Gasto operativo de liquidación**: monto asociado a una liquidación bajo una categoría de gasto (incluye viáticos como una categoría).
- **Categoría de gasto**: catálogo fijo de conceptos de gasto operativo usado para el desglose.
- **Peaje de liquidación**: monto de peaje asociado a una liquidación; se suma al total de peajes del periodo.
- **Gasto fijo mensual**: costos fijos por conductor/mes/año (sueldo, seguridad social, cuota banco, cuota tercero, satelital, seguro vehículo, otros) que reducen la utilidad neta del periodo.
- **Conductor / placa**: dimensión por la que se puede filtrar o desglosar el informe.
- **Informe de periodo**: resultado agregado (ingresos, gastos por concepto, gastos fijos, utilidad neta y evolución mensual) para un rango de tiempo y un alcance (consolidado o un conductor).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El administrador puede obtener el informe consolidado de cualquier mes, semestre o año en menos de 1 minuto desde que entra al módulo, sin pasos manuales de cálculo.
- **SC-002**: Los totales del informe (ingresos, gastos por concepto, gastos fijos y utilidad neta) coinciden al 100% con la suma de las liquidaciones activas del periodo verificada manualmente.
- **SC-003**: El administrador identifica de un vistazo, en la gráfica de evolución, el mes de mayor ganancia y el de mayor pérdida del periodo.
- **SC-004**: El PDF generado refleja exactamente los mismos totales que la vista en pantalla para el mismo periodo y alcance.
- **SC-005**: La suma de los desgloses por conductor de un periodo reproduce el consolidado de la empresa de ese periodo (diferencia cero).
- **SC-006**: Ningún usuario distinto del administrador puede acceder al módulo ni a sus informes, y ningún administrador ve datos de un cliente distinto al suyo.

## Assumptions

- El alcance es exclusivamente el módulo de Liquidaciones de Viajes; el módulo de Importaciones de contenedores queda fuera de este informe.
- La definición de utilidad usada es la utilidad neta final (descontando los gastos fijos mensuales), no solo la ganancia bruta de viaje.
- Se reutilizan los datos y cálculos ya existentes (totales por liquidación, agregación por periodo y por mes, y el descuento de gastos fijos mensuales); no se rediseña el modelo de cálculo de utilidad.
- El periodo de una liquidación se determina por su fecha de inicio de viaje, criterio ya usado por el sistema para agrupar por mes.
- Un semestre se interpreta como enero–junio o julio–diciembre del año seleccionado, salvo que el usuario indique otra cosa en una iteración posterior.
- El acceso queda restringido al rol administrador; otros roles (placas, cliente, etc.) no acceden a este módulo en esta versión.
- Las gráficas y la exportación a PDF se apoyan en las capacidades ya disponibles en el sistema; no se introducen servicios externos.
