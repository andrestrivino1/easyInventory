# Research: Limpieza cosmética y de calidad de código (Fases 1–5)

**Date**: 2026-05-05
**Feature**: [spec.md](./spec.md)
**Plan**: [plan.md](./plan.md)

Este documento consolida las decisiones técnicas tomadas para resolver puntos no triviales del plan, antes de generar tareas concretas.

---

## R1. Estructura inusual del deployment Laravel — impacto en `asset('public/...')`

### Contexto

`resources/views/layouts/app.blade.php`, `auth/login.blade.php` y `auth/passwords/email.blade.php` usan `asset('public/logo.png')`, `asset('public/images/flags/...')`, etc. La spec inicial (FR-015) marcaba estas llamadas como un anti-patrón a corregir.

### Investigación

- `index.php` está en la **raíz del proyecto** (`c:\xampp\htdocs\easy_inventory\index.php`), no dentro de `public/`.
- `index.php` referencia `__DIR__.'/vendor/autoload.php'` y `__DIR__.'/bootstrap/app.php'` (sin `..`), confirmando que el document root del servidor es el directorio raíz, no `public/`.
- El `.htaccess` en raíz hace rewrite a `index.php`.
- Por lo tanto la URL pública es `http://localhost/easy_inventory/...` (XAMPP) y `asset('foo')` resuelve a `/easy_inventory/foo`. Para servir `public/logo.png` desde esta estructura, la única forma correcta es `asset('public/logo.png')` → `/easy_inventory/public/logo.png`.
- `asset('css/app.css')` también funciona porque existe físicamente `c:\xampp\htdocs\easy_inventory\css\app.css` (legacy de Mix; revisar) — nota: hay una carpeta `css/` y otra `public/css/` en la raíz, lo que genera ambigüedad.

### Decisión

**FR-015 queda fuera del alcance de esta feature.** Cambiar `asset('public/...')` → `asset('...')` rompería los iconos y el logo en la práctica del usuario.

### Justificación

- El usuario explícitamente pidió "sin modificar lógica ni funcionamiento". Tocar estas referencias cambiaría el funcionamiento (assets rotos en su entorno XAMPP).
- La corrección apropiada implica migrar a la estructura Laravel estándar (mover `index.php` y `.htaccess` a `public/`, ajustar el `vhost`/document root). Eso es un proyecto de despliegue, no un cambio cosmético.

### Alternativas consideradas

1. **Mover `index.php` a `public/`** — cambia el funcionamiento de despliegue (URLs, vhost). Rechazada: fuera del alcance "cosmético".
2. **Usar `url()` en vez de `asset()`** — mismo problema, sólo cambia la API.
3. **Conservar `asset('public/...')` tal cual** — ✅ elegida.

### Acciones derivadas

- Eliminar FR-015 del scope efectivo. La spec lo deja marcado, pero el plan y tasks.md no lo incluirán.
- Añadir mención en README.md de que el proyecto usa una estructura no estándar (deployment Laravel "single-root") como nota para futuros desarrolladores.
- (Recomendación futura, fuera de esta feature) crear feature aparte `002-laravel-public-root` para migrar a estructura estándar.

---

## R2. Estrategia de migración a Vite (bundler único)

### Contexto

El proyecto tiene `webpack.mix.js` (declara `app.js` y `app.css`) y `vite.config.js` (declara los mismos archivos). `package.json` apunta a Mix (`"dev": "npm run development"`, `"development": "mix"`). Hay que pasar a Vite sin perder paridad.

### Decisión

Migración en pasos atómicos:

1. Actualizar `package.json` scripts a Vite estándar:
   - `"dev": "vite"`
   - `"build": "vite build"`
2. Quitar `laravel-mix`, mantener `laravel-vite-plugin` si ya está; añadirlo si falta.
3. **No** cambiar las referencias `asset('css/app.css')` y `asset('js/app.js')` en blade en este PR. En su lugar, configurar Vite para que la salida quede en `public/css/app.css` y `public/js/app.js` (compatibilidad).
   - Alternativa A (preferida): usar `vite.config.js` con `build.outDir = 'public'` y `build.rollupOptions.output.assetFileNames = 'css/app.css'` y `entryFileNames = 'js/app.js'`.
   - Alternativa B (defer): migrar a `@vite([...])` directive en blade — requiere que el dev server corra en paralelo (`npm run dev`) y cambia el flujo de desarrollo del usuario. Se pospone.
4. Borrar `webpack.mix.js`.
5. Regenerar `package-lock.json` con `npm install`.

### Justificación

- Mantener rutas de salida hace que la migración sea invisible para el blade y para producción. El usuario sigue ejecutando `npm run build` y obtiene los mismos archivos servidos.
- `@vite()` directive sería más idiomático pero implica HMR y dev server separado del PHP server, lo que cambia el flujo de desarrollo. Eso debería ser otra feature.

### Alternativas consideradas

1. **Migrar a `@vite()` directive completo** — rechazada por cambio de flujo de desarrollo y riesgo de regresión.
2. **Mantener Mix y borrar `vite.config.js`** — rechazada porque el usuario explícitamente pidió Vite y Mix está deprecated.
3. **Configurar Vite con outDir personalizado (elegida)** — preserva paridad de archivos servidos.

---

## R3. Configuración de Pint compatible con `.styleci.yml` existente

### Contexto

El proyecto tiene `.styleci.yml` (preset Laravel, `version: 8`, deshabilita `no_unused_imports`). Pint debe coexistir sin generar conflictos ni reformatear todo el código en su primer run.

### Decisión

Crear `pint.json` con:

```json
{
  "preset": "laravel",
  "rules": {
    "no_unused_imports": false
  }
}
```

- **No** ejecutar `composer pint` automáticamente sobre el código existente como parte de esta feature. Sólo añadir la configuración y el script.
- En `composer.json` añadir:
  ```json
  "scripts": {
    ...,
    "pint": "vendor/bin/pint",
    "pint:test": "vendor/bin/pint --test"
  }
  ```
- Documentar en README cómo invocar.

### Justificación

- Mantener la regla `no_unused_imports: false` evita que Pint elimine imports que StyleCI tolera, manteniendo paridad estilística.
- No correr Pint sobre el código existente evita un PR masivo de reformateo que enmascararía los cambios del feature real.

### Alternativas consideradas

1. **Reemplazar StyleCI por Pint** — rechazada: `.styleci.yml` puede seguir activo si el usuario tiene integración StyleCI; coexisten sin conflicto.
2. **Ejecutar Pint sobre todo el código** — rechazada por mezclar reformateo masivo con cambios del feature.

### Pint requiere instalación

`laravel/pint` no está en `composer.json` actualmente. Hay que añadirlo como `require-dev`. Esto es la única adición a Composer en toda la feature.

---

## R4. Configuración de Prettier sin reformateo masivo

### Contexto

No hay `.prettierrc` ni script de format en `package.json`. Prettier es estándar de facto para JS/CSS.

### Decisión

Crear `.prettierrc` con configuración alineada a `.editorconfig`:

```json
{
  "semi": true,
  "singleQuote": true,
  "trailingComma": "es5",
  "printWidth": 100,
  "tabWidth": 4,
  "useTabs": false,
  "endOfLine": "lf"
}
```

Crear `.prettierignore`:

```
node_modules/
public/
vendor/
storage/
*.min.js
*.min.css
package-lock.json
composer.lock
```

Añadir a `package.json`:

```json
"scripts": {
  ...,
  "format": "prettier --write 'resources/**/*.{js,css}'",
  "format:check": "prettier --check 'resources/**/*.{js,css}'"
}
```

### Justificación

- `tabWidth: 4` alinea con `.editorconfig`.
- Limitar el glob a `resources/**/*.{js,css}` evita formatear blade (Prettier-Blade plugin requiere instalación adicional, fuera de scope).
- No instalar `prettier` como dependencia local en este PR — se confía en que el desarrollador tenga Prettier global o instale puntualmente. Alternativa: añadir `prettier` a `devDependencies`. **Decisión**: añadir a `devDependencies` para reproducibilidad.

### Alternativas consideradas

1. **Sólo ejecutar `npm run format` post-extracción** — sí se ejecuta UNA vez sobre los nuevos archivos (`session-manager.js`, `language-selector.js`, `sidebar.js`, `layout.css`) para que queden formateados desde el inicio. No sobre archivos preexistentes.
2. **Instalar prettier-plugin-blade** — fuera de scope, demasiada complejidad.

---

## R5. Extracción del CSS/JS del layout sin alterar el orden de carga ni el comportamiento

### Contexto

`resources/views/layouts/app.blade.php` mezcla:
- HTML del shell (sidebar, header, footer, content area)
- ~500 líneas de CSS dentro de `<style>`
- ~280 líneas de JS dentro de varios bloques `<script>` al final del body
- 3 dependencias externas vía CDN: `bootstrap-icons`, `chart.js`, `sweetalert2`

El JS depende de SweetAlert2 (`Swal.fire(...)`) y de `Chart.js` (cargado pero usado en otras vistas). El JS también usa Blade interpolation (`{{ route('logout') }}`, `{{ __('common.aceptar') }}`).

### Decisión

#### Para CSS (línea ~18 a ~506 del layout):

1. Crear `resources/css/layout.css` con todo el contenido del bloque `<style>`.
2. En el layout, sustituir el bloque `<style>` por un `<link rel="stylesheet" href="{{ asset('css/layout.css') }}">` justo después del `<link href="{{ asset('css/app.css') }}">`.
3. Configurar Vite para que `resources/css/layout.css` se compile y emita en `public/css/layout.css`.

#### Para JS:

Hay 3 grupos lógicos. **El JS contiene interpolación Blade** (`{{ route('...') }}`, `{{ __('...') }}`), por lo que no puede ser un archivo `.js` puro sin pre-procesado.

**Decisión**: Extraer en dos categorías:

- **Lo que tiene interpolación Blade** → archivos parciales blade incluidos vía `@include('partials.scripts.session-manager')` etc., en `resources/views/partials/scripts/`. Permanecen como `.blade.php` para preservar el `{{ ... }}`.
  - `partials/scripts/session-manager.blade.php`
  - `partials/scripts/language-selector.blade.php`
- **Lo que es JS puro** (sidebar mobile toggle, responsive tables) → archivo `.js` real bajo `resources/js/sidebar.js` y `resources/js/responsive-tables.js`, importados desde `resources/js/app.js`.

#### Justificación

- Esta separación respeta la realidad de que parte del JS depende de Blade. Forzarlo a `.js` puro requeriría exponer rutas/strings vía `<meta>` tags o `window.__appConfig`, lo cual es un refactor de comportamiento (cambia cómo se inyectan los datos), fuera de scope.
- El archivo `app.blade.php` final debería bajar de >900 líneas a < 200 (sólo HTML y `@include`s).

### Alternativas consideradas

1. **Convertir todo a JS puro con configuración inyectada** — rechazada por estar fuera del alcance "cosmético". Cambia cómo se inyectan rutas y traducciones.
2. **Dejar el JS interpolado dentro del `<script>` del layout** — rechazada porque rompe el Success Criterion SC-005 (layout < 200 líneas).
3. **Híbrido (elegida)**: parciales blade para JS interpolado, archivos `.js` para JS puro.

---

## R6. Auditoría de `axios` y `lodash` (FR-012)

### Investigación pendiente (a ejecutar durante implementación, no en research)

Buscar `import.*axios`, `require.*axios`, `import.*lodash`, `_.` (sin falsos positivos) en `resources/js/`.

### Decisión preliminar

Si no se importan en ningún archivo bajo `resources/js/` (o en los nuevos archivos extraídos), se eliminan. Si se importan, se conservan y se documenta en el README dónde se usan.

`bootstrap.js` (el de Laravel, no el framework CSS) suele importar axios — verificar:

```js
// resources/js/bootstrap.js (típico Laravel scaffold)
window.axios = require('axios');
```

Si éste es el caso y no se usa `window.axios` en ningún sitio, se elimina la línea. Si sí se usa, se conserva.

---

## R7. Comandos artisan personalizados a documentar en README

### Hallazgo

`app/Console/Commands/` contiene 4 comandos personalizados:
- `CleanDatabase`
- `DebugTransferProducts`
- `SyncItrsFromImports`
- `UpdateProductsCalibreFromName`

### Decisión

El README mencionará la existencia de estos comandos y su propósito a alto nivel (sin documentar flags internos), invitando al desarrollador a `php artisan list app` para ver detalles.

---

## Resumen de NEEDS CLARIFICATION pendientes

Ninguno crítico. Las decisiones quedaron tomadas con defaults razonables. Se asume que el usuario confirma estas decisiones implícitamente al pedir la ejecución de las fases. Si alguna se quiere revertir, basta con ajustar `tasks.md` antes de implementar.
