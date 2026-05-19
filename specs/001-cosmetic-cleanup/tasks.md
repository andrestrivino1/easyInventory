---
description: "Task list for Limpieza cosmética y de calidad de código (Fases 1–5)"
---

# Tasks: Limpieza cosmética y de calidad de código (Fases 1–5)

**Input**: Design documents from `specs/001-cosmetic-cleanup/`
**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [quickstart.md](./quickstart.md)

**Tests**: No se generan tasks de test automatizadas para esta feature. La validación es manual vía `quickstart.md` (cosmetic cleanup, sin lógica nueva).

**Organization**: Tareas agrupadas por user story para entregas incrementales.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivo distinto, sin dependencias)
- **[Story]**: User story de la spec (US1..US5)
- Cada tarea incluye ruta de archivo exacta

## Path Conventions

Estructura Laravel monolítica con document root en la raíz del proyecto (`c:\xampp\htdocs\easy_inventory\`). Toda ruta relativa parte de la raíz.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Captura del estado previo y preparación del entorno de validación. Sin estas tareas no se puede medir paridad ni revertir con seguridad.

- [ ] T001 Capturar baseline visual y funcional siguiendo `specs/001-cosmetic-cleanup/quickstart.md` Paso 0 (screenshots de login, dashboard, productos, transferencias, salidas, importaciones, stock, trazabilidad, usuarios, sidebar móvil, selector de idioma). Guardar en carpeta local fuera del repo o en `specs/001-cosmetic-cleanup/baseline/` (gitignored).
- [ ] T002 Ejecutar `php artisan test` y guardar el output como baseline en `specs/001-cosmetic-cleanup/baseline/test-output.txt` (gitignored). Confirmar que el set de tests pasa íntegramente antes de cualquier cambio.
- [ ] T003 [P] Verificar que la rama actual es `001-cosmetic-cleanup` con `git branch --show-current`.
- [ ] T004 [P] Verificar entorno: `php --version` (≥7.4), `node --version` (LTS), `composer --version`, `npm --version`.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Tareas que deben completarse antes de cualquier user story para evitar regresiones.

**⚠️ CRITICAL**: Ningún cambio funcional empieza hasta que esta fase termine.

- [ ] T005 Crear directorio `database/dumps/` con `mkdir -p database/dumps` y añadir un `.gitkeep` para mantenerlo en el repo.
- [ ] T006 Crear archivo `specs/001-cosmetic-cleanup/baseline/.gitignore` con contenido `*` para que ningún screenshot/baseline se commitee accidentalmente (la carpeta se crea sólo si T001 la usa).

**Checkpoint**: Estructura mínima lista. Las user stories pueden comenzar.

---

## Phase 3: User Story 1 - Limpieza del repositorio (Priority: P1) 🎯 MVP

**Goal**: Eliminar cruft (zip de 65 MB, dump SQL en raíz, .env.backup), actualizar `.gitignore` y reescribir `README.md` con info real del proyecto.

**Independent Test**: Tras esta fase, clonar el repo en máquina limpia debe ser significativamente más liviano y `README.md` debe permitir a un nuevo dev levantar el proyecto en <15 min. La aplicación debe seguir arrancando idéntica.

### Implementation for User Story 1

- [ ] T007 [US1] Mover `vidriosj_inventory.sql` de la raíz a `database/dumps/vidriosj_inventory.sql` con `git mv vidriosj_inventory.sql database/dumps/vidriosj_inventory.sql`.
- [ ] T008 [US1] Eliminar `inventory.zip` (65 MB) con `git rm inventory.zip`.
- [ ] T009 [US1] Eliminar `.env.backup` con `git rm .env.backup`.
- [ ] T010 [P] [US1] Actualizar `.gitignore` añadiendo:
  - `.env.*` (excluyendo `!.env.example`)
  - `/public/build`
  - `/storage/app/public`
  - `/specs/*/baseline/`
  Mantener las entradas existentes intactas.
- [ ] T011 [P] [US1] Reescribir `README.md` con las siguientes secciones:
  - Título: "VIDRIOS J&P S.A.S. — Sistema de Inventario (easy_inventory)"
  - Descripción de dominio y stack (Laravel 8, PHP 7.4/8.x, Tailwind, Alpine, Vite, MySQL)
  - Requisitos previos (XAMPP/LAMP, PHP, Composer, Node, MySQL)
  - Pasos de instalación (clonar, `composer install`, `npm install`, `cp .env.example .env`, `php artisan key:generate`, importar `database/dumps/vidriosj_inventory.sql`, `php artisan migrate`, `npm run build`, `php artisan serve` o XAMPP)
  - Roles del sistema: admin, funcionario, importer, import_viewer, proveedor_itr (1 línea por rol con su capacidad)
  - Comandos artisan personalizados (lista de los 4 con descripción 1-línea: `app:clean-database`, `app:debug-transfer-products`, `app:sync-itrs-from-imports`, `app:update-products-calibre-from-name`)
  - Nota sobre estructura no estándar: `index.php` está en la raíz; `asset('public/...')` es correcto en este deployment (referencia a `research.md` R1)
  - Comandos de tooling: `composer pint`, `npm run format`, `npm run dev`, `npm run build`
- [ ] T012 [US1] Ejecutar `php artisan serve` y abrir la app en navegador → verificar que login y al menos 2 listados (productos y transferencias) cargan idénticos al baseline (T001).
- [ ] T013 [US1] Ejecutar `git status` y confirmar que sólo se modificaron: `inventory.zip` (D), `vidriosj_inventory.sql` → `database/dumps/` (R), `.env.backup` (D), `.gitignore` (M), `README.md` (M), nuevo `database/dumps/.gitkeep`.

**Checkpoint**: Repositorio limpio. App funcionando idéntica. Listo para User Story 2.

---

## Phase 4: User Story 2 - Bundler único Vite (Priority: P1)

**Goal**: Migrar de `webpack.mix.js` a Vite como único bundler, manteniendo paridad de assets servidos (mismas rutas `public/css/app.css` y `public/js/app.js`).

**Independent Test**: Tras esta fase, `npm install && npm run build` debe completar y producir los assets en las mismas rutas que antes; la app cargada en navegador no debe mostrar errores 404 ni de consola.

### Implementation for User Story 2

- [ ] T014 [US2] Editar `vite.config.js` para configurar `build.outDir = 'public'`, `emptyOutDir = false`, y `rollupOptions.output` con:
  - `entryFileNames: (chunk) => chunk.name === 'app' ? 'js/app.js' : 'js/[name]-[hash].js'`
  - `assetFileNames: (asset) => asset.name?.endsWith('.css') ? 'css/app.css' : 'assets/[name]-[hash][extname]'`
  Mantener el `laravel-vite-plugin` configurado con los mismos entry points (`resources/css/app.css`, `resources/js/app.js`). El objetivo es que el build genere `public/css/app.css` y `public/js/app.js` para preservar las referencias `asset('css/app.css')` y `asset('js/app.js')` del layout.
- [ ] T015 [US2] Editar `package.json`:
  - Reemplazar scripts `dev`, `development`, `watch`, `watch-poll`, `hot`, `prod`, `production` por: `"dev": "vite"` y `"build": "vite build"`
  - Eliminar `laravel-mix` de `devDependencies`
  - Añadir `laravel-vite-plugin` a `devDependencies` si no está ya (verificar con `npm ls laravel-vite-plugin`)
- [ ] T016 [US2] Eliminar `webpack.mix.js` con `git rm webpack.mix.js`.
- [ ] T017 [US2] Ejecutar `rm -rf node_modules && npm install` para regenerar `package-lock.json` coherentemente.
- [ ] T018 [US2] Ejecutar `npm run build` y verificar:
  - Salida contiene 0 errores
  - `public/css/app.css` existe y no está vacío
  - `public/js/app.js` existe y no está vacío
  - El comando completa en menos de 60s (SC-003)
- [ ] T019 [US2] Cargar la app en navegador → DevTools → Network → confirmar que `app.css` y `app.js` cargan con `200 OK` y mismos hashes de contenido (o equivalente comportamiento). DevTools → Console → 0 errores.
- [ ] T020 [US2] Comparar paridad visual contra baseline T001 en al menos 3 páginas (login, dashboard, productos). 0 diferencias visuales.

**Checkpoint**: Bundler único en Vite. Mix eliminado. App funcionando idéntica.

---

## Phase 5: User Story 4 - Limpieza dependencias frontend (Priority: P2)

> **Nota de orden**: User Story 3 (refactor de layout) se ejecuta DESPUÉS de US4 porque US3 podría introducir nuevos imports en JS, y conviene tener el `package.json` ya limpio antes.

**Goal**: Quitar `bootstrap` de `package.json` (no se importa, sólo se usan iconos vía CDN) y auditar `axios`/`lodash`.

**Independent Test**: Tras esta fase, `npm run build` sigue funcionando, los iconos siguen visibles (vienen del CDN), `node_modules` ocupa menos espacio.

### Implementation for User Story 4

- [ ] T021 [US4] Buscar imports de `bootstrap` en `resources/js/`:
  ```bash
  grep -rn "bootstrap" resources/js/
  ```
  Confirmar que no aparece ningún `import 'bootstrap'` ni `require('bootstrap')`. Si aparece, abortar y reportar al usuario.
- [ ] T022 [P] [US4] Auditar `axios`: `grep -rn "axios" resources/js/` y revisar `resources/js/bootstrap.js`. Si `window.axios = require('axios')` está presente y NO se usa `window.axios` ni `axios.` en ningún archivo del proyecto (verificar con `grep -rn "axios\." resources/`), eliminar la línea de `bootstrap.js`. En caso contrario, conservar y documentar el uso en una sección "Dependencias frontend" del README.
- [ ] T023 [P] [US4] Auditar `lodash`: `grep -rn "lodash\|require.*lodash\|import.*lodash" resources/js/`. Si no se usa, eliminar de `devDependencies`.
- [ ] T024 [US4] Editar `package.json` eliminando `bootstrap` de `dependencies`. Si `axios`/`lodash` quedaron sin uso tras T022/T023, eliminarlos también.
- [ ] T025 [US4] Ejecutar `rm -rf node_modules && npm install` y luego `npm run build`. Verificar que el build sigue verde.
- [ ] T026 [US4] Recargar la app en navegador con cache limpio (Ctrl+F5). Confirmar que TODOS los iconos `bi bi-*` siguen visibles (logo de la sidebar, botones de logout, iconos de menús, etc.). Si algún icono falla, revertir T024 para `bootstrap` y reportar.

**Checkpoint**: Dependencias frontend limpias. Iconos funcionando vía CDN.

---

## Phase 6: User Story 3 - Layout mantenible (Priority: P2)

**Goal**: Reducir `resources/views/layouts/app.blade.php` de >900 líneas a <200 extrayendo CSS/JS a archivos dedicados, preservando exactamente el HTML renderizado y todo el comportamiento.

**Independent Test**: Tras esta fase, `wc -l resources/views/layouts/app.blade.php` debe dar < 200; recorrer la app debe mostrar 0 diferencias visuales y 0 cambios de comportamiento (sidebar, header, selector idioma, detector de inactividad, interceptores fetch/XHR/forms, sidebar móvil, tablas responsivas).

### Implementation for User Story 3

#### CSS extraction

- [ ] T027 [US3] Crear `resources/css/layout.css` con TODO el contenido del bloque `<style>...</style>` actualmente en `resources/views/layouts/app.blade.php` (líneas ~18 a ~506). Copiar tal cual; no reformatear.
- [ ] T028 [US3] Verificar que `vite.config.js` (modificado en T014) compila también `resources/css/layout.css`. Añadirlo al array de entries del `laravel-vite-plugin` o como import en `resources/css/app.css`. Decisión: importarlo desde `app.css` con `@import './layout.css';` para no cambiar el set de entry points y que el build siga generando `public/css/app.css` con todo combinado.

#### JS extraction (puro, sin Blade interpolation)

- [ ] T029 [P] [US3] Crear `resources/js/sidebar.js` con el bloque "SIDEBAR MOBILE TOGGLE" del layout (líneas ~889 a ~903). Es JS puro, no requiere Blade.
- [ ] T030 [P] [US3] Crear `resources/js/responsive-tables.js` con el bloque "RESPONSIVE TABLES" del layout (líneas ~905 a ~919). JS puro.
- [ ] T031 [US3] Editar `resources/js/app.js` para añadir:
  ```js
  import './sidebar.js';
  import './responsive-tables.js';
  ```
  después de la importación de Alpine.

#### JS extraction con Blade interpolation (parciales blade)

- [ ] T032 [P] [US3] Crear `resources/views/partials/scripts/session-manager.blade.php` con TODO el JS de gestión de sesión: variable `sessionExpiredAlertShown`, función `doLogout()`, función `showSessionExpiredAlert()`, interceptor de fetch, interceptor de XMLHttpRequest, interceptor de formularios, detector de inactividad (30 min), verificación periódica (5 min). Todo entre etiquetas `<script>...</script>`. Conserva las interpolaciones `{{ route("logout") }}`, `{{ __("common.aceptar") }}`, `{{ route("home") }}`, etc.
- [ ] T033 [P] [US3] Crear `resources/views/partials/scripts/language-selector.blade.php` con el JS del language selector (eventos del trigger, dropdown, mapeo de banderas e idiomas, función `changeLanguage(locale)`). Conserva interpolaciones `{{ asset(...) }}` y `{{ __(...) }}`.

#### Modificar el layout

- [ ] T034 [US3] Editar `resources/views/layouts/app.blade.php`:
  - Reemplazar el bloque `<style>...</style>` (líneas ~18 a ~506) por un comentario HTML `<!-- estilos del shell movidos a resources/css/layout.css -->` (el CSS ya viene incluido en `app.css` vía import).
  - Reemplazar los bloques `<script>...</script>` con JS interpolado (líneas ~636 a ~919) por:
    ```blade
    @include('partials.scripts.session-manager')
    @include('partials.scripts.language-selector')
    ```
  - Eliminar los bloques de JS puro (sidebar toggle y responsive tables) — ahora cargan vía `app.js`.
  - Mantener el `<script src="{{ asset('js/app.js') }}"></script>` y el CDN de SweetAlert2 intactos.
  - Mantener TODO el HTML del shell (sidebar `<aside>`, header, content area, footer) intacto, byte por byte.
  - Mantener `@yield('content')` y `@yield('scripts')`.
- [ ] T035 [US3] Verificar que el archivo final tiene menos de 200 líneas: `wc -l resources/views/layouts/app.blade.php`.
- [ ] T036 [US3] Ejecutar `npm run build` y `php artisan view:clear`.
- [ ] T037 [US3] Validación visual y funcional EXHAUSTIVA contra baseline T001:
  - Login → idéntico
  - Dashboard → idéntico
  - Sidebar items según rol (admin debe ver todos los items; funcionario subset; importer/import_viewer/proveedor_itr otros subsets) → idénticos
  - Selector de idioma: abrir, cambiar a EN, verificar redirect y nueva interfaz; volver a ES
  - Botón logout en header → cierra sesión correctamente
  - Resize ventana a < 600 px: botón hamburguesa aparece, sidebar oculta, click hamburguesa abre sidebar, click fuera cierra
  - Tablas responsivas en móvil: scroll horizontal funciona
  - Detector de inactividad: temporalmente reducir `INACTIVITY_TIME` a 60000 ms, esperar 1 min, confirmar SweetAlert. Revertir.
  - Interceptor fetch/XHR: provocar 401 (cerrar sesión en otra pestaña, hacer una acción AJAX en la pestaña actual) → SweetAlert
  - DevTools → Console: 0 errores nuevos
- [ ] T038 [US3] Confirmar que NO se modificó ningún `asset('public/...')` (ver research R1): `grep -rn "asset('public/" resources/views/layouts/app.blade.php` debe seguir mostrando las mismas líneas que antes (si bien serán las mismas líneas en posiciones nuevas).

**Checkpoint**: Layout < 200 líneas. App pixel-equivalente y funcionalmente equivalente.

---

## Phase 7: User Story 5 - Herramientas de calidad (Priority: P3)

**Goal**: Añadir Pint, Prettier y mejorar `.editorconfig` sin reformatear código existente.

**Independent Test**: `composer pint:test` y `npm run format:check` ejecutan sin errores de configuración. Los archivos NUEVOS (creados en US3) pasan el check.

### Implementation for User Story 5

- [ ] T039 [P] [US5] Crear `pint.json` en la raíz con:
  ```json
  {
    "preset": "laravel",
    "rules": {
      "no_unused_imports": false
    }
  }
  ```
- [ ] T040 [P] [US5] Crear `.prettierrc` en la raíz con:
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
- [ ] T041 [P] [US5] Crear `.prettierignore` en la raíz con:
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
- [ ] T042 [P] [US5] Editar `.editorconfig` añadiendo bloques explícitos:
  ```
  [*.js]
  indent_size = 4

  [*.blade.php]
  indent_size = 4

  [*.json]
  indent_size = 2

  [*.css]
  indent_size = 4
  ```
  Mantener los bloques existentes (`[*]`, `[*.md]`, `[*.{yml,yaml}]`, `[docker-compose.yml]`).
- [ ] T043 [US5] Editar `composer.json` añadiendo a `require-dev`: `"laravel/pint": "^1.0"`. Añadir a `scripts`:
  ```json
  "pint": "vendor/bin/pint",
  "pint:test": "vendor/bin/pint --test"
  ```
- [ ] T044 [US5] Editar `package.json` añadiendo a `devDependencies`: `"prettier": "^3.0.0"`. Añadir a `scripts`:
  ```json
  "format": "prettier --write \"resources/**/*.{js,css}\"",
  "format:check": "prettier --check \"resources/**/*.{js,css}\""
  ```
- [ ] T045 [US5] Ejecutar `composer install` para instalar Pint.
- [ ] T046 [US5] Ejecutar `npm install` para instalar Prettier.
- [ ] T047 [US5] Ejecutar `npm run format -- 'resources/css/layout.css' 'resources/js/sidebar.js' 'resources/js/responsive-tables.js'` (sólo sobre archivos nuevos creados en US3) para que queden formateados.
- [ ] T048 [US5] Verificar que `composer pint:test` corre sin crash de configuración. Si reporta diferencias en archivos existentes, NO aplicar — sólo confirmar que la herramienta está funcional.
- [ ] T049 [US5] Verificar que `npm run format:check` corre sin crash sobre los archivos nuevos.
- [ ] T050 [US5] Documentar en README los nuevos comandos disponibles (`composer pint`, `composer pint:test`, `npm run format`, `npm run format:check`) en la sección de tooling.

**Checkpoint**: Tooling de calidad disponible. No se reformatearon archivos existentes.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Validación final integral y limpieza.

- [ ] T051 Ejecutar quickstart.md completo, marcando TODAS las casillas. Documentar cualquier desviación en notas.
- [ ] T052 Ejecutar `php artisan test` y comparar contra baseline T002. Mismo número de tests pasando.
- [ ] T053 [P] Ejecutar `git diff main..001-cosmetic-cleanup --stat` y verificar que NO hay cambios en `app/`, `routes/`, `config/`, `database/migrations/`, `database/seeders/`, `database/factories/`, `tests/`.
- [ ] T054 [P] Verificar tamaño del repo: `du -sh .git` y `du -sh --exclude=node_modules --exclude=vendor .` antes y después; debe haberse reducido en al menos 60 MB en el árbol de trabajo (SC-001).
- [ ] T055 Ejecutar `php artisan view:clear && php artisan config:clear && php artisan route:clear` y volver a recorrer la app una última vez para confirmar que no hay caches sucias enmascarando un bug.
- [ ] T056 Si todo está verde, hacer commit final consolidado o serie de commits por fase. Sugerencia: 5 commits, uno por fase, en orden, con mensajes descriptivos. Mantener cada commit atómico para facilitar revert.
- [ ] T057 Crear PR contra `main` con descripción que enlace a `specs/001-cosmetic-cleanup/spec.md` y `quickstart.md`, listando los success criteria (SC-001 a SC-008) marcados como cumplidos.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: Sin dependencias — primero.
- **Phase 2 (Foundational)**: Depende de Setup.
- **Phase 3 (US1 — Limpieza)**: Depende de Foundational. P1, MVP.
- **Phase 4 (US2 — Vite)**: Depende de Foundational. Independiente de US1 (puede ir en paralelo si hay 2 desarrolladores).
- **Phase 5 (US4 — Frontend deps)**: Depende de US2 (necesita el bundler ya migrado para validar el build).
- **Phase 6 (US3 — Layout)**: Depende de US2 (necesita Vite para compilar `layout.css`) y US4 (mejor tener deps limpias antes).
- **Phase 7 (US5 — Tooling)**: Depende de US3 (formatea los archivos nuevos creados en US3).
- **Phase 8 (Polish)**: Depende de TODAS las anteriores.

### Within Each User Story

- Sin tests automatizados (no requeridos).
- Tareas marcadas [P] dentro de la misma user story pueden ejecutarse en paralelo (archivos distintos).
- La validación visual es siempre la última tarea de cada user story.

### Parallel Opportunities

- Phase 1: T003 y T004 en paralelo.
- Phase 3 (US1): T010 y T011 en paralelo (archivos distintos: `.gitignore` y `README.md`).
- Phase 5 (US4): T022 y T023 en paralelo (auditorías de imports independientes).
- Phase 6 (US3): T029, T030, T032, T033 todos en paralelo (4 archivos nuevos distintos). T031 después.
- Phase 7 (US5): T039, T040, T041, T042 todos en paralelo (4 archivos nuevos distintos).
- Phase 8 (Polish): T053 y T054 en paralelo.

Si trabaja un único desarrollador (caso esperado), la sugerencia es seguir el orden secuencial de phases, aprovechando los [P] dentro de cada phase.

---

## Parallel Example: Phase 6 (US3 — Layout)

```bash
# Crear los 4 archivos nuevos en paralelo:
Task: "Crear resources/js/sidebar.js con bloque sidebar mobile toggle"
Task: "Crear resources/js/responsive-tables.js con bloque responsive tables"
Task: "Crear resources/views/partials/scripts/session-manager.blade.php"
Task: "Crear resources/views/partials/scripts/language-selector.blade.php"

# Después, secuencial:
Task: "Editar resources/js/app.js para importar sidebar y responsive-tables"
Task: "Editar resources/views/layouts/app.blade.php removiendo bloques inline e incluyendo parciales"
```

---

## Implementation Strategy

### MVP First (User Story 1 only)

1. Phase 1 (Setup) — capturar baseline.
2. Phase 2 (Foundational) — crear `database/dumps/`.
3. Phase 3 (US1) — limpieza, README, .gitignore.
4. **STOP & VALIDATE**: confirmar que la app sigue funcionando idéntica.
5. Commit / merge si se desea entregar MVP.

### Incremental Delivery

1. Setup + Foundational → listo.
2. US1 → MVP (repo limpio + README útil).
3. US2 → Vite operativo.
4. US4 → deps limpias.
5. US3 → layout mantenible (mayor cambio cosmético).
6. US5 → tooling de calidad.
7. Polish → validación integral.

Cada phase es revertible independientemente con `git revert` del commit correspondiente.

### Single Developer Strategy (esperado)

Ejecutar phases en orden: 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8. Aprovechar [P] dentro de cada phase para ahorrar tiempo cuando se delegue a Claude/agente.

---

## Notes

- **Sin tests automatizados** porque la spec es 100% cosmética y no introduce lógica nueva. La validación es manual vía `quickstart.md`.
- **[P] dentro de la misma user story** = archivos distintos, sin dependencias entre ellos.
- **Validación visual** es OBLIGATORIA al cierre de cada user story; saltársela rompe la garantía de paridad.
- **No modificar `asset('public/...')`** (ver research.md R1) — es correcto en este deployment.
- **Commits atómicos por phase** facilitan revert si una phase introduce regresión.
- **Caches Laravel** (`view:clear`, `config:clear`, `route:clear`) limpiar antes de cada validación visual para evitar falsos positivos/negativos.
