# Feature Specification: Liquidación de Viajes

**Feature Branch**: `002-liquidacion-viajes`
**Created**: 2026-05-19
**Status**: Draft
**Input**: User description: "vamos a implementar un modulo nuevo que sera liquidación viajes y tendra este formato todo sera tipo formulario para ingresar como tal la data, quiero que lo analices y me muestre que campos se llenaran y cuales seran fijos"

## Resumen del módulo

Sistema para registrar la liquidación financiera de cada viaje de transporte de mercancía. El operador captura por viaje los datos de cabecera (placa, ruta, transportadora, anticipos, fechas), los gastos operativos del conductor por categoría (ACPM, peajes, comisión, etc.) y el valor del flete cobrado al cliente. El sistema calcula automáticamente sumatorias, saldo del viaje y ganancia para imprimir el documento que firma el conductor.

## Clarifications

### Session 2026-05-19

- Q: ¿De dónde viene la placa del vehículo? → A: Se selecciona un Conductor del maestro `drivers` existente y la placa se auto-llena desde `drivers.vehicle_plate` (read-only por defecto, editable como excepción).
- Q: ¿Existe un maestro de transportadoras o se captura libre? → A: Texto libre. "TRANSPORTE" es una etiqueta fija; el input recibe el nombre (ej. "PEREIRANA DE TRANSPO"). No se crea maestro de transportadoras en v1.
- Q: ¿Qué elementos del formato son etiquetas/títulos fijos? → A: Son títulos fijos (no editables): TRANSPORTE, DESCRIPCIÓN, VALOR, GALONES, ANTICIPO, SOBREANTICIPO, FECHA INICIO, FECHA FIN, PEAJE, VALOR (peajes), TOTAL PEAJES, RUTA, NÚMERO DE MFTO, TELÉFONO EMPRESA, VALOR FLETE, SUMATORIA DE GASTOS, SUMATORIA DE PEAJES, TOTAL ANTICIPOS, SALDO VIAJE, GANANCIA VIAJE, A FAVOR DE, FIRMA CONDUCTOR. El input de RUTA es un select con rutas predefinidas.
- Q: ¿Las rutas dónde y cómo se gestionan? → A: La gestión de rutas (crear/editar/eliminar) es parte de este módulo, no un maestro global. Cada ruta se define por ciudad origen + ciudad destino y tiene asociada una lista ordenada de peajes (con valor sugerido y sentido ida/regreso). El admin debe poder crear una ruta y agregar sus peajes antes de poder usarla en una liquidación, o capturar peajes manualmente como fallback.
- Q: ¿Una liquidación es editable indefinidamente o tiene un ciclo de vida? → A: Dos estados — `Borrador` (editable libremente) y `Cerrada` (read-only, vista solo lectura). El admin la pasa a `Cerrada` cuando el conductor firma. Una liquidación `Cerrada` puede "Reabrirse" por el admin capturando un motivo; cada reapertura queda en el log de auditoría (quién, cuándo, motivo).
- Q: ¿Cómo debe comportarse la eliminación de liquidaciones? → A: Soft delete solo aplica a `Borrador` (se oculta del listado por defecto pero queda en BD para recuperación). Las `Cerrada` no se eliminan; se "anulan" capturando motivo, pasando a estado `Anulada`. Las `Anulada` siguen visibles en el listado con etiqueta visual "ANULADA" y son read-only (no se pueden reabrir, editar ni borrar).
- Q: ¿El módulo necesita totales mensuales además de los individuales? → A: Sí. El módulo entrega tres vistas: (1) formulario CRUD de la liquidación, (2) listado con filtros (fechas, placa, conductor, ruta, transportadora, estado) donde cada fila trae sus totales individuales y un botón "descargar PDF", y (3) panel de consolidado del conjunto filtrado (total gastos, total peajes, total anticipos, total flete, saldo total, ganancia total, # viajes, promedio por viaje), agrupable por mes calendario. El consolidado excluye `Anulada` y soft-deleted.
- Q: ¿Cuáles son las fórmulas exactas por viaje? → A: Confirmadas con ejemplo del documento. (a) Sumatoria gastos operativos = Σ valor 16 categorías (= 2.370.536). (b) Sumatoria peajes = Σ valor peajes (= 969.200). (c) Sumatoria gastos totales = operativos + peajes (= 3.339.736). (d) Total anticipos = anticipo + sobreanticipo (= 3.000.000). (e) **Saldo viaje = Total anticipos − Sumatoria gastos operativos** (= 629.464; nótese que NO incluye peajes en la resta — los peajes los paga la empresa). (f) **Ganancia viaje = Valor flete − Sumatoria gastos totales** (= 2.260.264; sí incluye peajes). (g) A favor de = "Empresa" si saldo > 0, "Conductor" si < 0. Etiqueta visual "SUMATORIA DE GASTOS" aparece dos veces en el PDF (preservado del Excel) pero internamente son dos cálculos distintos.

## Análisis de campos: fijos vs llenables

> Esta sección responde explícitamente a la pregunta del usuario sobre qué campos serán fijos y cuáles se llenarán.

### Etiquetas / Títulos fijos del formulario (no editables)

El operador NO escribe estos textos. Están impresos en el formulario y se ven igual en todas las liquidaciones.

**Encabezado del documento:**
- "LIQUIDACIÓN DE VIAJE"
- Logo y razón social "VIDRIOS J&P S.A.S."

**Cabecera de datos del viaje (etiquetas de campos):**
- TRANSPORTE
- RUTA
- ANTICIPO
- SOBREANTICIPO
- FECHA INICIO
- FECHA FIN
- NÚMERO DE MFTO
- TELÉFONO EMPRESA
- VALOR FLETE

**Encabezados de la tabla de gastos:**
- DESCRIPCIÓN | VALOR | GALONES

**Encabezados de la tabla de peajes:**
- PEAJE | VALOR

**Etiquetas de totales/cálculos:**
- SUMATORIA DE GASTOS
- SUMATORIA DE PEAJES
- TOTAL ANTICIPOS
- TOTAL PEAJES
- SALDO VIAJE
- GANANCIA VIAJE
- A FAVOR DE
- FIRMA CONDUCTOR

### Catálogo fijo de categorías de gastos (filas de la tabla DESCRIPCIÓN)

Lista cerrada; los nombres se muestran como filas en la tabla de gastos:

| # | Categoría | Tiene GALONES |
|---|---|---|
| 1 | ACPM | Sí |
| 2 | UREA | No |
| 3 | COMISIÓN | No |
| 4 | PORCENTAJE | No |
| 5 | MONTALLANTAS | No |
| 6 | PARQUEADEROS | No |
| 7 | LAVADA DEL CARRO | No |
| 8 | LUBRICANTES | No |
| 9 | ENGRASADA | No |
| 10 | ELÉCTRICO | No |
| 11 | BÁSCULA | No |
| 12 | EMBOLADA DE LLANTAS | No |
| 13 | VARIOS | No |
| 14 | CARPADA | No |
| 15 | DESCARPADA | No |
| 16 | VIÁTICOS | No |

### Catálogo de peajes por ruta (filas de la tabla PEAJE)

Definido una sola vez por ruta. Cuando el operador elige la ruta en el select, las filas de peajes se autocargan.

- Para `BUENAVENTURA-BOGOTÁ`: Loboguerrero, Betania, Uribe, Corozal, Túnel de la Línea, Gualanday, Chicoral, Chinauta, Chusacá (ida y regreso, con su valor sugerido por sentido).
- Otras rutas tendrán su propio listado de peajes (configurable por admin).

### Inputs llenables por viaje

Lo que el operador efectivamente escribe o selecciona en cada liquidación.

**Cabecera del viaje:**
| Etiqueta (título fijo) | Tipo de input | Origen |
|---|---|---|
| (selector de Conductor) | Select | Maestro `drivers` (solo activos). Al elegir autocompleta la placa. |
| PLACA | Texto (auto-llenado, editable como excepción) | `drivers.vehicle_plate` |
| RUTA | Select | Lista de rutas activas (gestionadas en la sección "Rutas" del mismo módulo). Se muestran como "ORIGEN → DESTINO". Al elegir autocarga la tabla de peajes. |
| TRANSPORTE | Texto libre | Operador escribe el nombre de la empresa transportadora (ej. "PEREIRANA DE TRANSPO") |
| ANTICIPO | Monto | Operador |
| SOBREANTICIPO | Monto (default 0) | Operador |
| FECHA INICIO | Fecha | Operador |
| FECHA FIN | Fecha | Operador |
| NÚMERO DE MFTO | Texto | Operador |
| TELÉFONO EMPRESA | Texto | Operador |
| VALOR FLETE | Monto | Operador |

**Tabla de gastos (16 filas fijas, una por categoría):**
| Columna | Tipo de input |
|---|---|
| DESCRIPCIÓN | Etiqueta fija (nombre de la categoría) — no editable |
| VALOR | Monto opcional (queda vacío si no aplica) |
| GALONES | Numérico — solo se llena en la fila ACPM |

**Tabla de peajes (N filas según ruta + extras):**
| Columna | Tipo de input |
|---|---|
| PEAJE | Etiqueta fija (nombre del peaje, viene de la ruta) — no editable salvo en peajes ad-hoc |
| VALOR | Monto editable por viaje (precargado desde el catálogo de la ruta) |

**Pie de la liquidación:**
- FIRMA CONDUCTOR: bloque para firma manual sobre la impresión en v1 (sin captura digital).

**Gastos** (uno por cada categoría del catálogo; todos opcionales — quedan vacíos si no aplican):
- Valor en pesos
- Solo ACPM tiene un segundo campo: galones

**Peajes** (la lista viene pre-cargada desde la ruta; el operador puede editar el valor por viaje si cambió la tarifa, marcar como no usado, o agregar uno adicional):
- Valor en pesos por cada peaje

**Conductor:**
- Firma (captura digital o impresa para firma manual)

### Campos calculados automáticamente (fórmulas individuales por viaje)

> **Nota terminológica**: el formato físico actual usa la etiqueta "SUMATORIA DE GASTOS" en dos filas distintas con valores distintos. Para evitar ambigüedad en la implementación, en el spec se nombran como **"Sumatoria de gastos operativos"** (solo gastos, sin peajes) y **"Sumatoria de gastos totales"** (gastos + peajes). En el PDF impreso se preserva la etiqueta visual original "SUMATORIA DE GASTOS" en ambas filas para no romper el formato del documento.

| # | Campo | Fórmula | Ejemplo |
|---|---|---|---|
| 1 | Sumatoria de gastos **operativos** | Σ `valor` de las 16 filas de la tabla de gastos | 2.370.536 |
| 2 | Sumatoria de peajes | Σ `valor` de los peajes capturados (incluye peajes ad-hoc, excluye marcados como "no usado") | 969.200 |
| 3 | Total peajes | = Sumatoria de peajes (misma cifra, etiqueta alterna del documento) | 969.200 |
| 4 | Sumatoria de gastos **totales** | Sumatoria gastos operativos + Sumatoria peajes | 3.339.736 |
| 5 | Total anticipos | Anticipo + Sobreanticipo | 3.000.000 |
| 6 | **Saldo viaje** | Total anticipos **−** Sumatoria de gastos **operativos** (NO incluye peajes — los peajes los paga la empresa, no se descuentan del anticipo del conductor) | 629.464 |
| 7 | **Ganancia viaje** | Valor flete **−** Sumatoria de gastos **totales** (gastos + peajes) | 2.260.264 |
| 8 | A favor de | "Empresa" si saldo > 0; "Conductor" si saldo < 0; "—" si = 0. Cuando saldo > 0 significa que el conductor recibió más anticipo que lo gastado y debe reintegrar; cuando < 0 la empresa le debe al conductor. | "Empresa" |

**Validación con el ejemplo del documento original:**
- Saldo viaje: 3.000.000 − 2.370.536 = **629.464** ✓
- Ganancia viaje: 5.600.000 − 3.339.736 = **2.260.264** ✓
- A favor de: saldo 629.464 > 0 → **Empresa** (VIDRIOS J&P) ✓

### Fórmulas del consolidado mensual / por filtro

Aplicadas sobre el conjunto de liquidaciones filtradas (excluyendo `Anulada` y soft-deleted). Si se activa "Agrupar por mes", las mismas fórmulas se aplican por separado a cada subconjunto mensual (basado en `fecha inicio` del viaje).

| # | Campo del consolidado | Fórmula |
|---|---|---|
| 1 | # viajes | Cantidad de liquidaciones en el conjunto |
| 2 | Σ Gastos operativos | Suma de "Sumatoria de gastos operativos" de cada viaje |
| 3 | Σ Peajes | Suma de "Sumatoria de peajes" de cada viaje |
| 4 | Σ Gastos totales | Σ Gastos operativos + Σ Peajes |
| 5 | Σ Anticipos | Suma de "Total anticipos" de cada viaje |
| 6 | Σ Flete | Suma de "Valor flete" de cada viaje |
| 7 | Σ Saldo | Suma de "Saldo viaje" de cada viaje (= Σ Anticipos − Σ Gastos operativos) |
| 8 | Σ Ganancia | Suma de "Ganancia viaje" de cada viaje (= Σ Flete − Σ Gastos totales) |
| 9 | Promedio ganancia por viaje | Σ Ganancia ÷ # viajes |
| 10 | Margen del periodo (%) | (Σ Ganancia ÷ Σ Flete) × 100 |

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Crear y guardar una liquidación de viaje (Priority: P1)

El operador de oficina recibe del conductor las facturas y notas de un viaje terminado. Abre el módulo, selecciona ruta y vehículo, captura anticipos, fechas, gastos y peajes, ingresa el valor flete, y guarda la liquidación. El sistema le muestra en pantalla las sumatorias y saldo antes de guardar.

**Why this priority**: Es el flujo central del módulo — sin esto el módulo no tiene valor.

**Independent Test**: Crear una liquidación completa con los datos del ejemplo (placa QJZ957, ruta BUENAVENTURA-BOGOTÁ, anticipo 3.000.000, gastos y peajes según el formato) y verificar que el sistema calcule saldo 629.464 y ganancia 2.260.264.

**Acceptance Scenarios**:

1. **Given** existe la ruta BUENAVENTURA-BOGOTÁ con su listado de peajes pre-cargado, **When** el operador la selecciona, **Then** el formulario muestra todos los peajes de la ruta con su valor sugerido editable.
2. **Given** el operador ha capturado anticipo 3.000.000 y un total de gastos de 2.370.536, **When** termina de ingresar la última categoría, **Then** el sistema muestra automáticamente "SALDO VIAJE = 629.464" y "A FAVOR DE: Empresa".
3. **Given** el operador llena todos los campos requeridos, **When** presiona "Guardar", **Then** la liquidación queda almacenada y disponible para consulta, edición e impresión.
4. **Given** una categoría de gasto no aplica al viaje (ej. CARPADA), **When** el operador la deja vacía, **Then** el sistema la registra como cero y no la incluye en la sumatoria.

### User Story 2 — Imprimir/exportar la liquidación para firma del conductor (Priority: P2)

Después de guardar, el operador imprime (o exporta a PDF) la liquidación con el formato visual del documento físico actual para que el conductor la revise y firme.

**Why this priority**: Es el entregable visible al conductor; sin él se mantiene la dependencia del Excel actual, pero la liquidación ya puede crearse y consultarse en sistema sin el PDF.

**Independent Test**: Generar el PDF de una liquidación previamente guardada y verificar que contenga: cabecera con placa/ruta/fechas, lista de gastos con valores, lista de peajes con valores, totales calculados, valor flete, ganancia, y el bloque "FIRMA CONDUCTOR".

**Acceptance Scenarios**:

1. **Given** una liquidación guardada con todos sus datos, **When** el operador presiona "Imprimir", **Then** se genera un PDF con el formato del Excel actual.
2. **Given** un PDF generado, **When** el operador lo abre, **Then** los totales del PDF coinciden con los totales mostrados en el formulario.

### User Story 3 — Listar, filtrar y editar liquidaciones, con descarga PDF por fila (Priority: P2)

El administrador entra al listado de liquidaciones para consultar y revisar viajes. Aplica filtros (rango de fechas, placa, conductor, ruta, transportadora, estado) y obtiene la lista con los totales individuales de cada viaje y un botón "Descargar PDF" por fila. Desde el listado puede abrir cada liquidación para edición (si está en `Borrador`).

**Why this priority**: Necesario para auditoría, control financiero, y entrega física al conductor; no bloquea la creación inicial del módulo.

**Independent Test**: Crear 3 liquidaciones de diferentes fechas/placas; filtrar por placa, verificar que aparecen solo las que coinciden con sus totales individuales correctos; descargar el PDF de una y comprobar que coincide con los valores de pantalla.

**Acceptance Scenarios**:

1. **Given** existen liquidaciones registradas, **When** el admin entra al listado, **Then** ve la lista ordenada por fecha de inicio descendente con columnas: estado, placa, ruta (origen→destino), conductor, fecha inicio, fecha fin, transportadora, total gastos, total peajes, saldo, ganancia, A favor de, acciones (Ver/Editar/PDF/Anular).
2. **Given** una liquidación en `Borrador` con un error en un gasto, **When** el admin la abre y la edita, **Then** las sumatorias y el consolidado se recalculan al guardar.
3. **Given** un filtro por rango de fechas, **When** el admin lo aplica, **Then** solo se listan liquidaciones cuya fecha inicio caiga en ese rango.
4. **Given** una liquidación en el listado, **When** el admin presiona "Descargar PDF" en su fila, **Then** se descarga el PDF de esa liquidación específica con todos sus totales.

### User Story 4 — Consolidado mensual del conjunto filtrado (Priority: P2)

El administrador, después de aplicar filtros en el listado (típicamente por placa o por conductor o por rango de fechas), ve un panel "Consolidado del periodo" con los totales agregados del conjunto filtrado: total gastos, total peajes, total anticipos, total flete, saldo total, ganancia total, cantidad de viajes y promedio por viaje. Puede agrupar el consolidado por mes calendario cuando el rango filtrado abarca varios meses, para ver mes a mes la rentabilidad.

**Why this priority**: Es la vista que necesita el área administrativa para evaluar rentabilidad por vehículo, conductor o ruta y tomar decisiones. Sin este panel el módulo solo da datos sueltos sin visión consolidada.

**Independent Test**: Capturar 5 liquidaciones distribuidas en 2 meses para la misma placa; filtrar por esa placa y verificar (a) que aparezcan solo esas 5, (b) que el panel "Consolidado del periodo" muestre los totales del conjunto, (c) que al activar "Agrupar por mes" se muestren los totales por cada mes separadamente.

**Acceptance Scenarios**:

1. **Given** N liquidaciones existentes para una placa, **When** el admin filtra por esa placa, **Then** el panel de consolidado muestra: suma de gastos, suma de peajes, suma de anticipos, suma de flete, saldo total, ganancia total, # de viajes, promedio de ganancia por viaje.
2. **Given** un filtro por rango de fechas que abarca 3 meses, **When** el admin activa "Agrupar por mes", **Then** el consolidado se descompone en 3 sub-paneles, uno por mes, con sus totales propios.
3. **Given** un conjunto filtrado que incluye liquidaciones en estado `Anulada`, **When** se calcula el consolidado, **Then** los valores de las `Anulada` y de las soft-deleted NO se incluyen en los totales ni en el conteo de viajes.
4. **Given** un consolidado mostrado en pantalla, **When** el admin cambia un filtro, **Then** el consolidado se recalcula automáticamente reflejando el nuevo conjunto.

### User Story 5 — Gestionar rutas y peajes (Priority: P2)



El administrador entra a la sección "Rutas" del módulo Liquidación de Viajes y define las rutas que opera la empresa. Cada ruta se identifica por **ciudad origen → ciudad destino** y tiene asociada una lista ordenada de peajes (nombre, valor sugerido, orden, sentido ida/regreso). Cuando luego se crea una liquidación, al elegir la ruta los peajes se autocargan en la tabla de peajes con su valor sugerido editable.

**Why this priority**: Es prerrequisito para que la liquidación principal (US1) tenga la experiencia esperada de auto-cargado de peajes. Sin esto, el operador debería capturar manualmente los peajes en cada liquidación.

**Independent Test**: Como admin, crear la ruta `BUENAVENTURA → BOGOTÁ` con sus peajes (Loboguerrero 43.300 ida, Betania 56.000 ida, …, Loboguerrero 43.300 regreso). Luego abrir el formulario de nueva liquidación, seleccionar esa ruta y verificar que aparecen todos los peajes precargados con sus valores sugeridos.

**Acceptance Scenarios**:

1. **Given** el administrador entra a "Rutas" dentro del módulo Liquidación de Viajes, **When** crea una nueva ruta capturando ciudad origen, ciudad destino y agregando uno o más peajes en orden con valor sugerido y sentido, **Then** la ruta queda disponible para usarse en una liquidación.
2. **Given** una ruta ya configurada, **When** el administrador edita el valor sugerido de un peaje, **Then** ese nuevo valor se usa como sugerencia en futuras liquidaciones; las liquidaciones existentes mantienen el valor que tenían capturado.
3. **Given** una ruta sin peajes configurados, **When** el operador la selecciona en una liquidación, **Then** el sistema muestra una tabla de peajes vacía y permite agregar peajes ad-hoc para esa liquidación.
4. **Given** una ruta que ya tiene liquidaciones asociadas, **When** el administrador intenta eliminarla, **Then** el sistema bloquea la eliminación con mensaje claro o, alternativamente, marca la ruta como inactiva sin afectar las liquidaciones existentes.

### Edge Cases

- ¿Qué pasa si el conductor no usó algún peaje de la ruta (por desvío)? — El operador debe poder marcarlo como "no usado" o dejarlo en cero sin que afecte la sumatoria.
- ¿Qué pasa si un viaje pasa por un peaje no incluido en la ruta maestro? — El operador debe poder agregar un peaje extra ad-hoc a la liquidación sin modificar la ruta maestro.
- ¿Qué pasa si los gastos superan el anticipo? — El saldo queda negativo y "A FAVOR DE" debe mostrar "Conductor" (la empresa le debe).
- ¿Qué pasa si las fechas son inválidas (fin antes de inicio)? — El sistema debe rechazar el guardado con mensaje claro.
- ¿Qué pasa si el operador intenta crear dos liquidaciones para la misma placa+fecha+manifiesto? — El sistema debe alertar de un posible duplicado (excluyendo de la comparación las `Anulada` y las soft-deleted).
- ¿Qué pasa al editar una liquidación ya impresa y firmada? — Si está en `Cerrada`, solo se puede modificar después de "Reabrir" capturando motivo. El log de auditoría registra el evento.
- ¿Qué pasa si el conductor asociado se inactiva (`active = false`) después de creada la liquidación? — La liquidación conserva el conductor original; el select solo filtra activos al crear nuevas liquidaciones.
- ¿Qué pasa si un conductor aún no tiene `vehicle_plate` cargada en su perfil? — El sistema debe permitir capturar la placa manualmente en la liquidación, y opcionalmente sugerir actualizar el maestro `drivers`.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE permitir crear una nueva liquidación capturando: conductor (seleccionado del maestro `drivers`), placa (auto-llenada desde `drivers.vehicle_plate`, editable como excepción), ruta, transportadora, anticipo, sobreanticipo, fecha inicio, fecha fin, número de manifiesto, teléfono empresa, valor flete y firma del conductor.
- **FR-001a**: El sistema DEBE mostrar en el selector de conductor únicamente los registros con `active = true` del maestro `drivers`, ordenados por nombre.
- **FR-001b**: El sistema DEBE permitir editar la placa autocompletada para casos en que el conductor manejó un vehículo distinto al asociado en su perfil; en este caso, debe guardarse la placa real del viaje en la liquidación (sin modificar el maestro `drivers`).
- **FR-002**: El sistema DEBE presentar la lista fija de 16 categorías de gastos (ACPM, UREA, COMISION, PORCENTAJE, MONTALLANTAS, PARQUEADEROS, LAVADA DEL CARRO, LUBRICANTES, ENGRASADA, ELECTRICO, BASCULA, EMBOLADA DE LLANTAS, VARIOS, CARPADA, DESCARPADA, VIATICOS) en el formulario, cada una con un campo de valor opcional.
- **FR-003**: El sistema DEBE permitir registrar para ACPM tanto el valor en pesos como la cantidad de galones consumidos.
- **FR-004**: El sistema DEBE pre-cargar la lista de peajes de la ruta seleccionada, mostrando cada peaje con su valor de referencia editable por el operador.
- **FR-005**: El sistema DEBE permitir al operador agregar peajes ad-hoc no incluidos en la ruta maestro, sin alterar la ruta maestro.
- **FR-006**: El sistema DEBE calcular en tiempo real (mientras el operador captura): sumatoria de gastos, sumatoria de peajes, total anticipos, saldo viaje, ganancia viaje y "A favor de".
- **FR-007**: El sistema DEBE determinar "A favor de" como: "Empresa" si saldo > 0, "Conductor" si saldo < 0, "—" si saldo = 0.
- **FR-008**: El sistema DEBE permitir guardar, listar, filtrar (por fecha, placa, ruta, conductor, estado), abrir y editar liquidaciones según su estado.
- **FR-008a**: El sistema DEBE manejar tres estados para cada liquidación: `Borrador` (editable), `Cerrada` (read-only, viva) y `Anulada` (read-only, marcada visualmente). Toda nueva liquidación inicia en `Borrador`. El admin puede pasarla a `Cerrada` cuando el conductor firma.
- **FR-008b**: El sistema DEBE permitir al admin "Reabrir" una liquidación `Cerrada` (regresándola a `Borrador`) siempre que capture un motivo de reapertura. Cada reapertura se registra en un log de auditoría con: usuario, fecha/hora, motivo, estado anterior.
- **FR-008c**: El sistema DEBE bloquear toda modificación de campos (cabecera, gastos, peajes) cuando la liquidación está en estado `Cerrada` o `Anulada`. Acciones permitidas en `Cerrada`: ver, imprimir/exportar PDF, Reabrir, Anular. Acciones permitidas en `Anulada`: ver, imprimir/exportar PDF (con marca de agua "ANULADA").
- **FR-008d**: El sistema DEBE permitir eliminar (soft delete) únicamente las liquidaciones en estado `Borrador`. La eliminación marca la liquidación como `deleted_at`, la oculta del listado por defecto, pero permite recuperarla desde una vista "Eliminadas". No es posible eliminar `Cerrada` ni `Anulada`.
- **FR-008e**: El sistema DEBE permitir al admin "Anular" una liquidación `Cerrada` capturando un motivo obligatorio. La liquidación pasa a `Anulada`, se queda visible en el listado con etiqueta visual "ANULADA", y la acción se registra en el log de auditoría.
- **FR-008f**: El sistema NO DEBE permitir reabrir una liquidación `Anulada`. La anulación es un estado terminal.
- **FR-009**: El sistema DEBE generar un PDF/impresión de la liquidación con el mismo layout visual del documento Excel actual, incluyendo el bloque "FIRMA CONDUCTOR".
- **FR-010**: El sistema DEBE incluir, dentro del mismo módulo de Liquidación de Viajes, una sección "Rutas" donde el administrador gestiona (crear, editar, listar, inactivar) las rutas. Cada ruta se identifica por ciudad origen + ciudad destino y tiene asociada una lista ordenada de peajes con: nombre del peaje, valor sugerido, orden, sentido (ida/regreso).
- **FR-010a**: El sistema DEBE permitir, desde la pantalla de edición de una ruta, agregar, reordenar, editar y eliminar peajes asociados.
- **FR-010b**: El sistema NO DEBE permitir eliminar una ruta que tenga liquidaciones asociadas; en su lugar, debe permitir marcarla como inactiva (no aparece en el select de nuevas liquidaciones, pero las anteriores siguen visibles).
- **FR-011**: El sistema DEBE validar que la fecha fin no sea anterior a la fecha inicio.
- **FR-012**: El sistema DEBE registrar quién creó y quién modificó por última vez cada liquidación, la fecha de cada acción, los cambios de estado (`Borrador` ↔ `Cerrada`) con su motivo, para fines de auditoría.
- **FR-013**: El sistema DEBE alertar (sin bloquear) si ya existe una liquidación NO anulada con la misma combinación placa + número de manifiesto. Las `Anulada` y las soft-deleted no cuentan para esta validación.
- **FR-014**: El sistema DEBE tratar la liquidación como un registro independiente, sin vínculo obligatorio con Salidas ni Transfer Orders. La placa, ruta y fechas se capturan directamente en la liquidación.
- **FR-015**: El sistema DEBE restringir la creación, edición, consulta y eliminación de liquidaciones únicamente al rol `admin`. Ningún otro rol (funcionario, importer, import_viewer, clientes, proveedor_itr) tiene acceso al módulo.
- **FR-016**: El sistema DEBE ofrecer en el listado de liquidaciones un panel "Consolidado del periodo" que muestre, para el conjunto filtrado: total gastos, total peajes, total anticipos, total flete, saldo total, ganancia total, cantidad de viajes, y promedio de ganancia por viaje.
- **FR-017**: El sistema DEBE permitir, en el listado, agrupar el consolidado por mes calendario (basado en `fecha inicio` del viaje) cuando el rango de filtro abarca más de un mes; mostrando un sub-panel por cada mes con sus propios totales.
- **FR-018**: El consolidado y los totales agrupados por mes DEBEN excluir las liquidaciones en estado `Anulada` y las soft-deleted; estas se siguen mostrando en el listado individual con su etiqueta visual correspondiente.
- **FR-019**: El sistema DEBE permitir filtrar el listado por: rango de fechas (desde/hasta sobre fecha inicio), placa, conductor, ruta, transportadora (texto parcial), y estado (Borrador / Cerrada / Anulada). Los filtros aplican simultáneamente al listado individual y al consolidado.
- **FR-020**: El sistema DEBE recalcular el consolidado automáticamente cuando el usuario cambie cualquier filtro, sin requerir un refresco manual de la página.
- **FR-021**: El sistema DEBE permitir descargar el PDF de cada liquidación desde su fila en el listado, sin necesidad de abrir el detalle.

### Key Entities

- **Liquidación de Viaje**: Documento financiero por viaje. Atributos: conductor (FK a `drivers`), placa (snapshot del viaje, default = `drivers.vehicle_plate`), ruta, transportadora, anticipo, sobreanticipo, fecha inicio, fecha fin, número manifiesto, teléfono empresa, valor flete, sumatoria gastos, sumatoria peajes, saldo viaje, ganancia viaje, A favor de, estado (`Borrador` / `Cerrada` / `Anulada`), motivo de anulación, creado por, modificado por, fechas de auditoría, deleted_at (soft delete solo aplicable en `Borrador`).
- **Log de auditoría de estado**: Registro de cada cambio de estado de una liquidación (Cerrar, Reabrir, Anular). Atributos: liquidación, usuario, fecha/hora, estado anterior, estado nuevo, motivo (obligatorio al reabrir y al anular).
- **Línea de gasto**: Cada uno de los 16 ítems capturados dentro de una liquidación. Atributos: liquidación, categoría (referencia al catálogo), valor, galones (solo ACPM).
- **Línea de peaje**: Cada peaje capturado en una liquidación. Atributos: liquidación, peaje (referencia al catálogo de la ruta o ad-hoc), valor, orden.
- **Categoría de gasto**: Catálogo cerrado de las 16 categorías. Atributo principal: nombre.
- **Ruta**: Trayecto entre dos ciudades operado por la empresa. Atributos: ciudad origen, ciudad destino, nombre derivado (ej. "BUENAVENTURA → BOGOTÁ"), descripción opcional, estado activo/inactivo. Una ruta tiene 0..N peajes asociados.
- **Peaje de ruta**: Peajes asociados a una ruta, con valor sugerido. Atributos: ruta, nombre del peaje, valor sugerido, orden, sentido (ida/regreso).
- **Conductor** *(reutiliza el modelo `Driver` existente)*: Maestro ya en el sistema. Aporta a la liquidación: id, nombre, identificación, teléfono, placa del vehículo asociado, propietario del vehículo, capacidad. No se modifica desde este módulo.
- **Transportadora**: Referencia al maestro de transportadoras. *(A confirmar si ya existe o se crea — pendiente de aclaración).*

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un operador entrenado captura una liquidación completa (cabecera + 16 gastos + ~18 peajes) en menos de 5 minutos.
- **SC-002**: El 100% de las sumatorias y cálculos derivados (saldo, ganancia, "A favor de") se actualizan en pantalla en menos de 1 segundo al cambiar un valor.
- **SC-003**: El PDF generado coincide al 100% con los valores capturados (sin redondeos visibles, sin discrepancias).
- **SC-004**: Se reduce a cero el uso del archivo Excel actual para liquidación dentro de los primeros 30 días posteriores al despliegue.
- **SC-005**: Un administrador encuentra y filtra cualquier liquidación de los últimos 12 meses en menos de 10 segundos.
- **SC-006**: Errores de cálculo reportados por contabilidad (post-implementación) son cero durante el primer trimestre.
- **SC-007**: El consolidado del listado se actualiza en pantalla en menos de 2 segundos al cambiar un filtro, con un set de datos de hasta 1.000 liquidaciones.
- **SC-008**: Los totales del consolidado (mensual y del periodo) coinciden 100% con la suma manual de las liquidaciones individuales mostradas en pantalla.

## Assumptions

- El sistema actual ya gestiona usuarios y autenticación — la nueva funcionalidad se integra al login existente y respeta los roles ya definidos.
- Las categorías de gastos son las 16 listadas en el formato físico actual y no cambian con frecuencia; si cambian, se gestionan por migración / seed, no por UI.
- Cada ruta tiene un set conocido de peajes; el orden y sentido (ida/regreso) se preserva tal como aparece en el documento.
- Las ciudades origen y destino de una ruta se capturan como texto libre (no se crea un maestro independiente de ciudades en v1).
- La firma del conductor en v1 puede capturarse en papel (sobre el PDF impreso). Una firma digital nativa se considera fuera de alcance v1.
- Los valores monetarios se manejan en pesos colombianos (COP), enteros, sin decimales.
- El consolidado mensual del conjunto filtrado SÍ está en alcance v1 (panel sumatorio en el listado, agrupable por mes). Los reportes gerenciales más complejos (por categoría de gasto, gráficas comparativas, exportación a Excel) quedan fuera de alcance v1.
- La placa del vehículo se obtiene del maestro `drivers` (campo `vehicle_plate`); no se crea un maestro de vehículos independiente.

## Out of Scope (v1)

- Firma electrónica / digital del conductor con validez legal.
- Liquidación parcial por tramos del mismo viaje.
- Conciliación bancaria automática del anticipo.
- Integración con cartera/contabilidad externa.
- Carga masiva por Excel.
- Reportería gerencial avanzada (gráficas, exportación a Excel, desgloses por categoría) — el consolidado simple del listado SÍ está incluido.
