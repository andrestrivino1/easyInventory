# Quickstart: Validación manual de las Fases 1–5

**Date**: 2026-05-05
**Feature**: [spec.md](./spec.md)

Esta guía es el protocolo de aceptación para las Fases 1–5 una vez implementadas. Está pensada para ejecutarse sobre la rama `001-cosmetic-cleanup` antes de mergear.

---

## Pre-requisitos del entorno

- XAMPP corriendo con Apache + MySQL.
- Base de datos `vidriosj_inventory` (o equivalente) cargada.
- Node.js LTS y npm instalados.
- PHP 8.x y Composer instalados.
- Navegador moderno (Chrome o Edge) abierto en `http://localhost/easy_inventory/`.

---

## Paso 0 — Captura del estado previo (baseline)

Antes de implementar nada, recorrer la app y capturar:

1. Tomar screenshots de:
   - Pantalla de login.
   - Dashboard tras login (admin).
   - Listado de productos.
   - Listado de transferencias.
   - Listado de salidas.
   - Listado de importaciones.
   - Pantalla de stock.
   - Pantalla de trazabilidad.
   - Listado de usuarios (como admin).
   - Sidebar abierta en móvil (resize ventana < 600 px).
   - Selector de idioma desplegado.
2. Anotar tiempo total de carga del dashboard (DevTools → Network).
3. Ejecutar `php artisan test` y guardar el output (debe quedar verde tras los cambios).

> Si no se hace esta captura previa, la validación post-cambios se vuelve subjetiva. Dedicar 5 minutos.

---

## Paso 1 — Validar Fase 1 (Limpieza)

```bash
# Verificar que inventory.zip ya no está
test ! -f inventory.zip && echo "OK: inventory.zip eliminado"

# Verificar que el dump fue movido
test -f database/dumps/vidriosj_inventory.sql && echo "OK: dump reubicado"
test ! -f vidriosj_inventory.sql && echo "OK: dump no está en raíz"

# Verificar .env.backup
test ! -f .env.backup && echo "OK: .env.backup eliminado"

# Verificar README
head -3 README.md
# Esperado: título del proyecto, no la cabecera de Laravel
```

- [ ] `inventory.zip` no existe.
- [ ] `vidriosj_inventory.sql` está en `database/dumps/`, no en raíz.
- [ ] `.env.backup` no existe.
- [ ] `.gitignore` cubre `.env.*`, `/public/build`, `/storage/app/public`.
- [ ] `README.md` describe el proyecto (nombre, stack, instalación, roles, comandos artisan).
- [ ] La aplicación sigue arrancando sin errores: `php artisan serve` → abrir en browser.

---

## Paso 2 — Validar Fase 2 (Bundler único)

```bash
test ! -f webpack.mix.js && echo "OK: webpack.mix.js eliminado"

# package.json debe tener Vite scripts
grep -E '"dev":\s*"vite"' package.json && echo "OK: dev script usa vite"
grep -E '"build":\s*"vite' package.json && echo "OK: build script usa vite"
grep -v "laravel-mix" package.json | grep -v "package-lock" > /dev/null && echo "OK: sin laravel-mix"

# Reinstalar y compilar
rm -rf node_modules
npm install
npm run build

# Verificar que se generaron assets en las rutas esperadas
test -f public/css/app.css && echo "OK: CSS compilado"
test -f public/js/app.js && echo "OK: JS compilado"
```

- [ ] `webpack.mix.js` no existe.
- [ ] `package.json` scripts `dev` y `build` invocan Vite.
- [ ] `laravel-mix` no aparece en `devDependencies`.
- [ ] `npm install` completa sin errores.
- [ ] `npm run build` completa en menos de 60s y produce `public/css/app.css` y `public/js/app.js`.
- [ ] Cargar la app en navegador → DevTools → Console: 0 errores 404 ni de carga de assets.

---

## Paso 3 — Validar Fase 3 (Limpieza dependencias frontend)

```bash
grep '"bootstrap"' package.json && echo "FAIL: bootstrap aún presente" || echo "OK: bootstrap eliminado"
```

- [ ] `bootstrap` no aparece en `dependencies`.
- [ ] Auditoría de `axios` y `lodash` documentada (en README o en este quickstart): conservados o eliminados con razón.
- [ ] Tras `npm install` el tamaño de `node_modules` es razonablemente menor que antes (puede medirse con `du -sh node_modules`).
- [ ] Iconos siguen visibles en la app (vienen del CDN de bootstrap-icons, no del paquete npm).

---

## Paso 4 — Validar Fase 4 (Refactor cosmético del layout)

### 4a. Métricas estructurales

```bash
wc -l resources/views/layouts/app.blade.php
# Esperado: < 200 líneas

# Archivos nuevos esperados
ls resources/css/layout.css
ls resources/views/partials/scripts/session-manager.blade.php
ls resources/views/partials/scripts/language-selector.blade.php
ls resources/js/sidebar.js
ls resources/js/responsive-tables.js  # o equivalente
```

### 4b. Paridad visual y funcional (CRÍTICA)

Comparar contra los screenshots del Paso 0:

- [ ] **Login**: idéntico — colores, tipografía, posición del selector de idioma, banderas visibles.
- [ ] **Dashboard**: idéntico — sidebar, header con nombre de usuario, footer, layout de tarjetas (small-box).
- [ ] **Listados** (productos, transferencias, salidas, importaciones, stock, trazabilidad, usuarios): tablas con mismo estilo, mismas columnas, misma paginación.
- [ ] **Sidebar móvil** (resize a < 600 px): botón hamburguesa visible, click abre/cierra sidebar, click fuera la cierra.
- [ ] **Selector de idioma**: trigger se abre, muestra 3 opciones (es/en/zh) con sus banderas, click en una redirige a la URL de cambio de idioma.
- [ ] **Detector de inactividad**: simular dejando la pestaña inactiva 30 minutos (o reducir temporalmente `INACTIVITY_TIME` a 60s en una versión de test) → debe mostrar SweetAlert2 de sesión expirada.
- [ ] **Interceptor de fetch/XHR**: provocar un 401 (por ejemplo, cerrando sesión en otra pestaña y luego haciendo una acción que dispare AJAX) → SweetAlert2 de sesión expirada.
- [ ] **Tablas responsivas**: en viewport móvil, las tablas tienen scroll horizontal, no rompen el layout.
- [ ] **Logout**: el botón en el header cierra sesión correctamente.
- [ ] DevTools → Console: 0 errores nuevos en ninguna página.
- [ ] DevTools → Network: la cantidad de requests es comparable; CSS y JS extraídos cargan con `200 OK`.

### 4c. Verificar que `asset('public/...')` NO se modificó

```bash
grep -rn "asset('public/" resources/views/ --include="*.php" | wc -l
# Esperado: > 0 (las llamadas siguen presentes — ver R1 en research.md)
```

- [ ] Las llamadas `asset('public/logo.png')` y `asset('public/images/flags/...')` siguen igual (ver justificación en `research.md` R1).

---

## Paso 5 — Validar Fase 5 (Herramientas de calidad)

```bash
# Pint
test -f pint.json && echo "OK: pint.json existe"
composer pint:test
# Esperado: muestra diferencias propuestas pero no las aplica; salida no debe ser un crash

# Prettier
test -f .prettierrc && echo "OK: .prettierrc existe"
test -f .prettierignore && echo "OK: .prettierignore existe"
npm run format:check
# Esperado: sólo los archivos NUEVOS pasan la verificación;
# si hay diferencias en archivos viejos, no son bloqueantes en este PR.
```

- [ ] `pint.json` existe con preset Laravel y `no_unused_imports: false`.
- [ ] `composer pint:test` ejecuta sin crash de configuración.
- [ ] `.prettierrc` existe con configuración alineada al `.editorconfig`.
- [ ] `.prettierignore` excluye `node_modules`, `public`, `vendor`, `storage`, lockfiles.
- [ ] `npm run format` y `npm run format:check` ejecutan sin errores.
- [ ] `.editorconfig` tiene bloques explícitos para `*.js`, `*.blade.php`, `*.json`.
- [ ] `composer.json` tiene scripts `pint` y `pint:test`.
- [ ] `package.json` tiene scripts `format` y `format:check`.
- [ ] `prettier` está en `devDependencies`.
- [ ] `laravel/pint` está en `require-dev`.

---

## Paso 6 — Verificación final

```bash
# Tests existentes deben seguir verdes
php artisan test

# Caches limpios
php artisan view:clear
php artisan config:clear
php artisan route:clear

# Verificar branch y diff
git status
git log --oneline 001-cosmetic-cleanup ^main | head -20
git diff main..001-cosmetic-cleanup --stat
```

- [ ] `php artisan test` pasa con el mismo número de assertions que antes.
- [ ] `git diff` muestra cambios concentrados en: archivos raíz de configuración, layout, README, archivos nuevos en `resources/`, `database/dumps/`. **Cero cambios** en `app/`, `routes/`, `config/`, `database/migrations/`, `tests/`.
- [ ] Volver a recorrer la app en navegador y comparar con screenshots del Paso 0: paridad total.

---

## Criterios de Go / No-Go para mergear

**GO** si todas las casillas anteriores están marcadas y:
- 0 regresiones visuales.
- 0 regresiones funcionales.
- Tests verdes.
- Tamaño del repo (excluyendo `node_modules`/`vendor`) reducido en al menos 60 MB.
- Layout < 200 líneas.

**NO-GO** si:
- Algún asset no carga (404).
- Algún flujo de UI cambió de comportamiento.
- Tests rotos.
- DevTools muestra errores nuevos en consola.

---

## Rollback

Si tras mergear se detecta una regresión:

```bash
git revert <merge-commit-sha>
git push
```

Como toda la feature es cosmética, el revert es seguro y no toca datos ni esquema.
