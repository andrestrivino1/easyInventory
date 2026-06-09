# Quickstart — Informes y Analítica de Liquidaciones de Viajes

Cómo validar la feature localmente (XAMPP + MySQL) una vez implementada.

## Requisitos previos

- Stack del proyecto en marcha (Apache/XAMPP, MySQL).
- Sesión iniciada como usuario con `rol = 'admin'`.
- Datos de prueba: varias **liquidaciones activas** (estado `cerrada`/`borrador`, **no** `anulada`) con `fecha_inicio` repartida en varios meses, con gastos operativos en varias categorías (incl. VIÁTICOS) y peajes; y filas en `monthly_expenses` para los conductores/meses involucrados.

## Flujo principal (US1 — consolidado + utilidad neta)

1. Ir a **Liquidación de Viajes → Informes** (enlace visible solo a admin) o `GET /liquidaciones/reportes`.
2. Seleccionar **tipo = mes**, un `anio`/`mes` con datos. Verificar:
   - Total de **fletes**, total de **peajes**, total de **viáticos** y de cada categoría operativa.
   - Bloque de **gastos fijos mensuales** con los 7 conceptos (sueldo, seguridad social, …).
   - **Utilidad neta** = fletes − (gastos operativos + peajes + gastos fijos), con etiqueta clara **Ganancia** o **Pérdida**.
3. Comparar la utilidad neta con la que muestra `GET /liquidaciones` (consolidado del índice, "utilidad final") para el mismo mes → deben **coincidir** (SC-002).
4. Cambiar a **semestre** (S1 = ene–jun, S2 = jul–dic) y a **año**; verificar que los totales agregan los meses correctos (FR-002).

## Gráficas y mejor/peor mes (US2)

5. Con **tipo = anio**, verificar:
   - Gráfica de **evolución mensual** (fletes/gastos/utilidad por mes).
   - Resalte del **mes de mayor ganancia** y el de **mayor pérdida** (FR-010).
   - Gráfica de **desglose por categoría**; su suma == total de gastos operativos del resumen (FR-011).

## Exportar PDF (US3)

6. Pulsar **Descargar PDF**. El navegador captura las gráficas (PNG) y hace `POST /liquidaciones/reportes/pdf`.
7. Abrir el PDF y verificar que:
   - Identifica el **periodo** (y el conductor/placa si está filtrado).
   - Incluye tablas de ingresos, desglose por concepto, gastos fijos y **utilidad neta con su signo**.
   - Muestra las gráficas embebidas.
   - Los totales **coinciden** con la pantalla (SC-004).

## Desglose por conductor (US4)

8. Seleccionar un **conductor** en el filtro. Verificar que todos los totales y gráficas se recalculan solo con ese conductor y sus gastos fijos.
9. Sumar manualmente la utilidad neta de cada conductor del periodo → debe igualar el consolidado de la empresa (FR-018, SC-005).

## Casos borde

10. Elegir un periodo **sin liquidaciones activas** → estado vacío con totales en 0, **sin error** (FR-017).
11. Un mes con **gastos fijos pero sin viajes** → utilidad neta negativa (pérdida por costos fijos).
12. Confirmar que una liquidación **anulada** del periodo **no** afecta ningún total.

## Autorización (SC-006)

13. Cerrar sesión / entrar como `placas` → `GET /liquidaciones/reportes` debe devolver **403** (aunque sí vea `GET /liquidaciones`).
14. Entrar como `clientes`/`funcionario`/otro → **403**. Invitado → redirige a login.

## Tests automatizados

```powershell
# Feature tests del módulo (MySQL easy_inventory_test — ver memoria del proyecto)
php artisan test --filter=ReporteLiquidacion
```

Cubren: consolidado/utilidad neta por periodo, exclusión de anuladas/borradas, estado vacío, desglose por categoría (suma == operativos), evolución mensual (mejor/peor mes), desglose por conductor (suma == consolidado) y autorización admin-only.
