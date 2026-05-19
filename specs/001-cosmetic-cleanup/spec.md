# Feature Specification: Limpieza cosmética y de calidad de código (Fases 1–5)

**Feature Branch**: `001-cosmetic-cleanup`
**Created**: 2026-05-05
**Status**: Draft
**Input**: User description: "Ejecutar Fases 1–5 del plan de mejoras cosméticas/de calidad para easy_inventory (Laravel 8) sin modificar lógica ni funcionamiento. Limpieza de cruft, bundler único (Vite), limpieza de dependencias frontend, refactor cosmético del layout, herramientas de calidad de código."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Limpieza del repositorio (Priority: P1)

Como mantenedor del proyecto, quiero que el repositorio quede libre de archivos innecesarios (binarios pesados, dumps, copias de configuración) y que el `.gitignore` y `README.md` reflejen la realidad del proyecto, para que cualquier desarrollador nuevo pueda clonar y entender el repositorio en minutos.

**Why this priority**: La basura en el repo penaliza el `git clone` (más de 65 MB innecesarios) y un README genérico de Laravel obstaculiza el onboarding. Es la mejora con mayor impacto inmediato y menor riesgo.

**Independent Test**: Se puede validar de forma independiente clonando el repo en una máquina limpia, midiendo el tamaño del clone y leyendo el README. El proyecto debe seguir compilando y arrancando exactamente como antes.

**Acceptance Scenarios**:

1. **Given** el repositorio actual con `inventory.zip` (65 MB), **When** se completa la limpieza, **Then** ese archivo ya no está en el árbol de trabajo y el tamaño total del repositorio (excluyendo `node_modules`/`vendor`) se reduce significativamente.
2. **Given** el dump SQL `vidriosj_inventory.sql` en la raíz, **When** se completa la limpieza, **Then** el dump queda ubicado en `database/dumps/` y la raíz del proyecto sólo contiene archivos de configuración estándar de Laravel.
3. **Given** un nuevo desarrollador clona el repo, **When** abre `README.md`, **Then** ve el nombre real del proyecto, stack, instrucciones de instalación, comandos artisan disponibles y descripción de roles, no la plantilla por defecto de Laravel.
4. **Given** se ejecuta `php artisan serve` y se navega por todas las funcionalidades existentes (login, productos, transferencias, salidas, importaciones, ITR, stock, trazabilidad, usuarios), **When** se compara el comportamiento contra el estado previo, **Then** no hay diferencias visibles ni errores nuevos.

---

### User Story 2 - Bundler único (Vite) (Priority: P1)

Como desarrollador, quiero que el proyecto use un único sistema de empaquetado moderno (Vite) en lugar de tener `webpack.mix.js` y `vite.config.js` coexistiendo, para que el flujo de desarrollo de assets sea claro y la documentación de Laravel actual aplique sin ambigüedad.

**Why this priority**: La coexistencia de Mix y Vite genera confusión sobre qué comando usar (`npm run dev` actualmente apunta a Mix), bloatea `package-lock.json` y mantiene una dependencia obsoleta (`laravel-mix`). Es prerequisito para futuras mejoras frontend.

**Independent Test**: Validable ejecutando `npm install && npm run build` en una clonación limpia y comprobando que `public/build/` (Vite) o el destino actual de assets se genera correctamente, y que la aplicación servida con `php artisan serve` carga los estilos y scripts sin errores en consola.

**Acceptance Scenarios**:

1. **Given** el proyecto contiene `webpack.mix.js`, **When** se completa la migración a bundler único, **Then** `webpack.mix.js` ya no existe y los scripts de `package.json` (`dev`, `build`) operan con Vite.
2. **Given** la dependencia `laravel-mix` en `package.json`, **When** se completa la migración, **Then** ya no aparece en `devDependencies` y `package-lock.json` queda regenerado coherentemente.
3. **Given** las plantillas Blade que cargan assets compilados, **When** se completa la migración, **Then** los assets se sirven correctamente y la aplicación se ve y funciona idéntica a antes (mismas imágenes, fuentes, comportamiento JS).

---

### User Story 3 - Layout mantenible (Priority: P2)

Como desarrollador frontend, quiero que `resources/views/layouts/app.blade.php` no contenga ~500 líneas de CSS inline ni ~280 líneas de JS inline, sino que esos assets estén en archivos dedicados versionables y minificables, para poder modificar el aspecto o el comportamiento del shell de la app sin tocar un archivo Blade gigante.

**Why this priority**: El layout actual mezcla HTML, CSS y JS en un solo archivo de >900 líneas, lo que dificulta el mantenimiento, no aprovecha el cacheo del navegador para CSS/JS y no permite linting/formato sobre esos bloques. La extracción es puramente cosmética y reversible.

**Independent Test**: Validable abriendo cada página del sistema (dashboard, productos, transferencias, etc.) en un navegador y comparando visualmente y funcionalmente con un screenshot/grabación previa. La sidebar, el header, el selector de idioma, el detector de inactividad, los interceptores fetch/XHR y el toggle móvil deben comportarse idénticamente.

**Acceptance Scenarios**:

1. **Given** las ~500 líneas de CSS inline en el `<style>` del layout, **When** se completa la extracción, **Then** ese CSS vive en `resources/css/layout.css` (o equivalente bajo el flujo de Vite), se carga desde el layout y la apariencia es pixel-equivalente.
2. **Given** los ~280 líneas de JS inline (gestión de sesión expirada, interceptores, language selector, sidebar móvil, tablas responsivas), **When** se completa la extracción, **Then** ese JS vive en archivos separados (`session-manager.js`, `language-selector.js`, `sidebar.js` o equivalentes) referenciados desde el layout y todas las interacciones funcionan igual.
3. **Given** los `asset('public/...')` incorrectos en el layout, **When** se completa el refactor, **Then** se usan rutas correctas `asset('...')` y los recursos (logo, banderas) cargan correctamente.

---

### User Story 4 - Limpieza de dependencias frontend (Priority: P2)

Como mantenedor, quiero que `package.json` sólo contenga dependencias que el proyecto realmente importa y usa, para reducir el tamaño de `node_modules`, eliminar superficie de auditoría y evitar que un futuro contribuyente piense que Bootstrap es parte del stack.

**Why this priority**: `bootstrap` figura en `dependencies` pero el bundle no lo importa (se usan iconos vía CDN). Mantenerlo confunde y aumenta el tamaño de `node_modules`. La limpieza es trivial y baja riesgo.

**Independent Test**: Validable ejecutando `npm install && npm run build` y verificando que el bundle resultante no contiene Bootstrap y la apariencia de la app se mantiene.

**Acceptance Scenarios**:

1. **Given** `bootstrap` declarado en `package.json`, **When** se completa la limpieza, **Then** ya no aparece en `dependencies` y la aplicación sigue funcionando con la misma apariencia (los iconos siguen cargando vía CDN como hoy).
2. **Given** `axios` y `lodash` en `devDependencies` provenientes del scaffold, **When** se completa la auditoría, **Then** se conservan si hay imports en el código del proyecto y se eliminan en caso contrario, documentando la decisión.

---

### User Story 5 - Herramientas de calidad de código (Priority: P3)

Como desarrollador, quiero disponer de herramientas automatizadas de formato (Pint para PHP, Prettier para JS/CSS) y un `.editorconfig` más completo, para que las contribuciones futuras mantengan un estilo consistente sin esfuerzo manual.

**Why this priority**: Sin herramientas de formato, cada PR puede traer divergencias de estilo. La inversión en configuración es baja y rinde beneficios en cada cambio futuro. No es bloqueante para nada del producto.

**Independent Test**: Validable ejecutando `composer pint` y `npm run format` en el proyecto: deben correr sin errores de configuración y, en modo `--test`, no introducir cambios masivos no deseados sobre el código actual.

**Acceptance Scenarios**:

1. **Given** el proyecto sin `pint.json`, **When** se completa la fase, **Then** existe un `pint.json` con preset Laravel y un script `composer pint` (o equivalente) configurado y funcional.
2. **Given** el proyecto sin configuración de Prettier, **When** se completa la fase, **Then** existe un `.prettierrc` con reglas para JS/CSS y un script `npm run format` que ejecuta Prettier sobre los archivos relevantes sin tocar archivos generados (`public/`, `vendor/`, `node_modules/`).
3. **Given** `.editorconfig` actual sin reglas para JS/Blade, **When** se completa la fase, **Then** el archivo declara explícitamente reglas para `*.js`, `*.blade.php` y `*.json` coherentes con Prettier/Pint.

---

### Edge Cases

- **Hook fallback en Windows (PowerShell)**: Si los scripts `.ps1` no pueden ejecutarse por política de ejecución, los pasos de validación documentan invocación con `-ExecutionPolicy Bypass`.
- **Asset paths en producción**: Si la corrección de `asset('public/...')` rompiera enlaces previamente "funcionando" por accidente (símbolos en `public/public/`), debe verificarse que los recursos físicos existen en la ruta correcta antes del despliegue.
- **`package-lock.json` regenerado**: Tras quitar `laravel-mix` y `bootstrap`, el lockfile cambiará. Debe regenerarse limpiamente y commitearse junto al `package.json`.
- **Hook de auto-commit del spec-kit**: El proyecto tiene hooks `after_*` que pueden ofrecer commits automáticos; el desarrollador puede aceptarlos o saltarlos sin afectar el resultado funcional.
- **Caches de Laravel**: Tras cambios cosméticos en Blade, ejecutar `php artisan view:clear` y `config:clear` no debe ser estrictamente necesario, pero está documentado como parte de la verificación.
- **Vista previa visual entre sistemas operativos**: La extracción de CSS no debe alterar el rendering en Windows/Linux/macOS — la equivalencia se valida visualmente, no por hash de archivo.

## Requirements *(mandatory)*

### Functional Requirements

#### Fase 1 — Limpieza
- **FR-001**: El sistema MUST eliminar `inventory.zip` del directorio raíz del proyecto.
- **FR-002**: El sistema MUST mover `vidriosj_inventory.sql` desde la raíz a `database/dumps/vidriosj_inventory.sql`, creando el directorio si no existe.
- **FR-003**: El sistema MUST eliminar el archivo `.env.backup` de la raíz.
- **FR-004**: El sistema MUST actualizar `.gitignore` para excluir explícitamente `.env.*` (excepto `.env.example`), `/public/build`, y `/storage/app/public`.
- **FR-005**: El sistema MUST reescribir `README.md` con: nombre del producto, descripción del dominio (gestión de inventario para VIDRIOS J&P S.A.S.), stack tecnológico, requisitos de instalación, comandos artisan disponibles (incluyendo los comandos de consola personalizados), y descripción de los roles del sistema (admin, funcionario, importer, import_viewer, proveedor_itr).

#### Fase 2 — Bundler único (Vite)
- **FR-006**: El sistema MUST eliminar `webpack.mix.js` del proyecto.
- **FR-007**: El sistema MUST actualizar `package.json` para que los scripts `dev` y `build` invoquen Vite en lugar de Mix.
- **FR-008**: El sistema MUST eliminar `laravel-mix` de `devDependencies` en `package.json`.
- **FR-009**: El sistema MUST regenerar `package-lock.json` de forma coherente con los cambios en `package.json`.
- **FR-010**: El sistema MUST garantizar que la aplicación servida en local cargue los assets compilados (CSS y JS) sin errores en consola del navegador y mantenga apariencia y comportamiento idénticos al estado previo.

#### Fase 3 — Limpieza dependencias frontend
- **FR-011**: El sistema MUST eliminar `bootstrap` de `dependencies` en `package.json`.
- **FR-012**: El sistema MUST auditar el uso de `axios` y `lodash` en el código del proyecto (`resources/js/`, blade inline JS extraído) y eliminarlos si no se importan; si se conservan, debe documentarse el archivo donde se usan.

#### Fase 4 — Refactor cosmético del layout
- **FR-013**: El sistema MUST extraer el CSS contenido en el bloque `<style>` de `resources/views/layouts/app.blade.php` a un archivo `resources/css/layout.css` (o equivalente bajo el flujo de Vite), referenciado desde el layout.
- **FR-014**: El sistema MUST extraer el JavaScript inline (gestor de sesión expirada, interceptores fetch/XHR/forms, detector de inactividad, verificación periódica de sesión, language selector, sidebar móvil, tablas responsivas) desde `app.blade.php` a archivos JS separados (al menos: `session-manager.js`, `language-selector.js`, `sidebar.js`) bajo `resources/js/`.
- **FR-015**: El sistema MUST corregir las referencias a assets que usan `asset('public/...')` para que usen `asset('...')`, ajustando paths físicos si es necesario sin mover archivos en `public/`.
- **FR-016**: El sistema MUST preservar exactamente el HTML renderizado del layout (estructura DOM, clases, atributos) tras el refactor — sólo cambian los archivos donde residen los estilos y scripts.

#### Fase 5 — Herramientas de calidad
- **FR-017**: El sistema MUST añadir `pint.json` en la raíz con preset Laravel y reglas alineadas a `.styleci.yml` actual (incluyendo `no_unused_imports: false` si aplicable).
- **FR-018**: El sistema MUST añadir `.prettierrc` con reglas para JS y CSS coherentes con `.editorconfig`.
- **FR-019**: El sistema MUST mejorar `.editorconfig` añadiendo bloques explícitos para `*.js`, `*.blade.php` y `*.json`.
- **FR-020**: El sistema MUST añadir scripts de conveniencia: en `composer.json` un script `pint`; en `package.json` un script `format` que invoque Prettier sobre `resources/`.

#### Restricciones generales (todas las fases)
- **FR-021**: El sistema MUST NOT modificar ningún controlador, modelo, middleware, servicio, ruta, migración, seeder, factory, traducción ni archivo de configuración bajo `config/`.
- **FR-022**: El sistema MUST NOT modificar el comportamiento visible al usuario final: páginas, flujos, mensajes de error, mensajes de éxito y permisos por rol deben permanecer idénticos.
- **FR-023**: El sistema MUST NOT actualizar versiones mayores ni medias de Laravel, PHP, Tailwind, Alpine ni de ninguna dependencia con riesgo de breaking change. Sólo se permiten cambios estrictamente necesarios para el bundler único (Vite) y para añadir Prettier/Pint.
- **FR-024**: El sistema MUST permitir reversión completa mediante un `git revert` del commit (o conjunto de commits) generado por estas fases, sin pérdida de funcionalidad.

### Key Entities *(no aplica)*

Esta especificación no introduce entidades de dominio nuevas; opera sobre archivos de configuración, assets y plantillas existentes.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El tamaño del repositorio (excluyendo `node_modules` y `vendor`) se reduce en al menos 60 MB tras eliminar `inventory.zip`.
- **SC-002**: La raíz del proyecto contiene únicamente archivos de configuración (`composer.json`, `package.json`, `.env*`, `artisan`, `phpunit.xml`, `vite.config.js`, `tailwind.config.js`, `postcss.config.js`, `.editorconfig`, `.gitignore`, `.gitattributes`, `.htaccess`, `.styleci.yml`, `pint.json`, `.prettierrc`, `Dockerfile`, `index.php`, `server.php`, `README.md`, `favicon.ico`, `robots.txt`, `mix-manifest.json` si persiste como compatibilidad), directorios estándar de Laravel y nada más.
- **SC-003**: Tras ejecutar `npm install && npm run build` en una clonación limpia, la operación completa en menos de 60 segundos (en hardware razonable) y produce assets servibles sin errores.
- **SC-004**: Al recorrer manualmente las 9 áreas funcionales principales (login, dashboard, productos, transferencias, salidas, importaciones, ITR, stock, trazabilidad) en un navegador, **0 diferencias visuales ni de comportamiento** se observan respecto al estado previo.
- **SC-005**: `resources/views/layouts/app.blade.php` se reduce a menos de 200 líneas (desde >900) preservando el HTML renderizado.
- **SC-006**: `package.json` declara 0 dependencias no utilizadas verificables (Bootstrap eliminado; axios/lodash auditados).
- **SC-007**: `composer pint --test` y `npm run format -- --check` ejecutan sin errores de configuración.
- **SC-008**: El README permite a un desarrollador nuevo levantar el proyecto en menos de 15 minutos siguiendo sólo las instrucciones del archivo.

## Assumptions

- Se asume que el repositorio cuenta con un git inicializado y hay libertad para crear/borrar archivos en una rama de feature (`001-cosmetic-cleanup`).
- Se asume que `inventory.zip` es una copia de respaldo histórica que no necesita ser preservada en el repositorio (si el usuario indica lo contrario, se moverá fuera del proyecto antes de borrarlo).
- Se asume que `vidriosj_inventory.sql` es un dump útil para desarrollo local y se desea conservar en una ubicación más adecuada (`database/dumps/`) en lugar de borrarlo.
- Se asume que todos los iconos visibles actualmente en la aplicación provienen de Bootstrap Icons cargado vía CDN (`https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/...`) y no del paquete `bootstrap` en `node_modules`.
- Se asume que el flujo de carga de assets actual (`asset('css/app.css')` y `asset('js/app.js')`) seguirá usándose como compatibilidad mientras se migra a `@vite(...)`, **o** que la migración a `@vite()` se realiza preservando rutas equivalentes — la decisión queda dentro del alcance permitido siempre que el resultado funcional sea idéntico.
- Se asume que el archivo `mix-manifest.json` puede permanecer si quita riesgo de regresión, o eliminarse si Vite genera su propio manifest; ambas alternativas son aceptables siempre que la app cargue bien.
- Se asume que las pruebas existentes (PHPUnit) no dependen de archivos del frontend y seguirán pasando sin cambios.
- Se asume que el desarrollador validará manualmente la equivalencia visual en al menos un navegador moderno (Chrome o Edge) antes de mergear.
