# Phase 1 — Contracts: HTTP routes afectadas

No se crean rutas nuevas. Solo cambian payloads de creación/edición y el render de detalle/PDF.

## Endpoints afectados (existentes)

| Método | Ruta | Nombre | Cambio |
|---|---|---|---|
| POST | `/liquidaciones` | `liquidaciones.store` | Acepta nuevo campo `sobreanticipo` (entero ≥ 0, opcional → 0). |
| PUT | `/liquidaciones/{liquidacion}` | `liquidaciones.update` | Idem store (hereda reglas). Solo en estado `borrador`. |
| GET | `/liquidaciones/{liquidacion}` | `liquidaciones.show` | Recuadro de totales reordenado en 2 columnas, etiquetas nuevas. |
| GET | `/liquidaciones/{liquidacion}/pdf` | `liquidaciones.pdf` | Encabezado sin `ANTICIPO EMPRESA`; recuadro 2 columnas; firma "FIRMA FUNCIONARIO REVISÓ". |

## Payload store/update (campos relevantes)

```
driver_id, vehicle_plate, route_id?, transportadora, telefono_empresa?,
anticipo_empresa (int ≥0, required),
anticipo_conductor (int ≥0, nullable→0),
sobreanticipo (int ≥0, nullable→0)        <-- NUEVO
descuentos (int ≥0, nullable→0),
fecha_inicio, fecha_fin, numero_mfto?, manifiesto_pdf? (file),
valor_flete (int ≥0, required),
expenses[], tolls[]
```

## Respuestas / vistas

- `show`: el recuadro "Totales" se divide en **columna izquierda** (Sumatoria de gastos, Sumatoria de peajes, Suma de gastos total de viaje, Valor flete pactado, Anticipo empresa de transporte, Saldo adeudado empresa de transporte) y **columna derecha** (Anticipos conductor, Ant - gastos, A favor de, Ganancia final de viaje).
- `pdf`: misma estructura de 2 columnas en la tabla de totales; encabezado superior sin la fila de anticipo empresa; caja de firma con texto "FIRMA FUNCIONARIO REVISÓ".

## Sin cambios
- Rutas de gastos mensuales (`liquidaciones.gastos.*`), manifiesto, peajes/driver-info AJAX, cerrar/reabrir/anular: sin cambios.
