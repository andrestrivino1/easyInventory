# Quickstart — Reordenar panel de totales de la liquidación

## Levantar el entorno (XAMPP / dev)

```powershell
# Apache + MySQL desde el panel de XAMPP, o:
# (servidor embebido alterno)
php artisan serve
```

## Aplicar el cambio de esquema

```powershell
php artisan migrate            # aplica 2026_05_28_000000_add_sobreanticipo_to_liquidaciones
```

## Reconstruir el bundle de JS (obligatorio tras tocar fórmulas)

```powershell
npm install      # solo la primera vez
npm run build    # Vite -> public/js/app.js (replica de fórmulas en liquidacion-form.js)
```

> Si en desarrollo usas `npm run dev`, el HMR sirve el JS; igual verifica que el **fallback inline** de `_form.blade.php` y `resources/js/liquidacion-form.js` tengan las MISMAS fórmulas.

## Verificación funcional (golden path)

1. Crear/editar una liquidación en estado **borrador** con:
   - Gastos operativos (p. ej. 3.159.000), peajes (p. ej. 981.000),
   - Descuentos empresa (p. ej. 100.000),
   - Anticipo empresa (p. ej. 4.690.000), Anticipo conductor (3.000.000), **Sobre anticipo** (500.000),
   - Valor flete (6.700.000).
2. En la barra sticky / al guardar, comprobar el recuadro:
   - **Izquierda**: Sumatoria de gastos = 3.259.000 (3.159.000 + 100.000); Sumatoria de peajes = 981.000; Suma de gastos total = 4.240.000 (3.259.000 + 981.000); Valor flete pactado = 6.700.000; Anticipo empresa de transporte = 4.690.000; Saldo adeudado empresa = 2.010.000 (6.700.000 − 4.690.000).
   - **Derecha**: Anticipos conductor = 3.500.000 (3.000.000 + 500.000); Ant - gastos = −241.000 (3.259.000 − 3.500.000); A favor de = EMPRESA (ant-gastos < 0); Ganancia final = 2.460.000 (6.700.000 − 4.240.000).
3. Abrir el detalle (`show`) y confirmar el mismo orden/etiquetas y resaltado de signo.
4. Generar el **PDF**: el encabezado NO muestra "ANTICIPO EMPRESA"; el recuadro de totales sí muestra "Anticipo empresa de transporte" y "Saldo adeudado empresa de transporte"; la caja de firma dice **FIRMA FUNCIONARIO REVISÓ**.
5. Editar el **descuento empresa** o el **sobre anticipo** y verificar que los totales dependientes se actualizan sin recargar (Alpine) y al guardar (persistencia).

## Edge cases a probar
- Descuento = 0 → "Sumatoria de gastos" == gastos operativos.
- Sobre anticipo vacío/0 → "Anticipos conductor" == anticipo conductor.
- Anticipo empresa > flete → "Saldo adeudado empresa" negativo (rojo).
- Gastos totales > flete → "Ganancia final" negativa (rojo).
- Liquidación histórica (sin sobreanticipo) → se trata como 0; el panel calcula sin errores.

## Tests

```powershell
# Requiere MySQL real (easy_inventory_test), no sqlite. Ver memoria feature-tests-need-mysql.
php artisan test --filter=LiquidacionPanelTotalesTest
php artisan test --filter=LiquidacionSobreanticipoTest
php artisan test --filter=LiquidacionPdfAjustesTest
```

## Paridad de fórmulas (checklist de revisión)
Tras cualquier cambio de fórmula, confirmar que coinciden en:
- `app/Services/LiquidacionCalculator.php`
- fallback inline en `resources/views/liquidaciones/partials/_form.blade.php`
- `resources/js/liquidacion-form.js` (+ `npm run build`)
