# Implementation Plan: Limpieza cosmética y de calidad de código (Fases 1–5)

**Branch**: `001-cosmetic-cleanup` | **Date**: 2026-05-05 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `specs/001-cosmetic-cleanup/spec.md`

## Summary

Aplicar 5 fases de mejoras puramente estéticas/de calidad sobre el repositorio Laravel `easy_inventory` sin alterar ninguna lógica de negocio, comportamiento de usuario ni dependencia mayor:

1. **Limpieza de cruft**: borrar `inventory.zip` (65 MB), reubicar dump SQL, borrar `.env.backup`, mejorar `.gitignore`, reescribir `README.md`.
2. **Bundler único (Vite)**: borrar `webpack.mix.js`, actualizar scripts a Vite, quitar `laravel-mix`.
3. **Limpieza dependencias frontend**: quitar `bootstrap`, auditar `axios`/`lodash`.
4. **Refactor cosmético del layout**: extraer ~500 líneas de CSS y ~280 líneas de JS inline desde `resources/views/layouts/app.blade.php` a archivos dedicados; corregir `asset('public/...')` → `asset('...')`.
5. **Herramientas de calidad**: añadir `pint.json`, `.prettierrc`, mejorar `.editorconfig`, scripts de formato.

Enfoque técnico: cambios incrementales y reversibles, validados manualmente en navegador. La migración a Vite mantiene paridad de salida (mismo HTML renderizado, mismas rutas de assets servidos al cliente). El refactor del layout preserva el DOM pixel a pixel — sólo cambia dónde residen los estilos/scripts. Tooling se añade pero no se ejecuta de forma agresiva sobre el código existente para evitar reformateos masivos en este PR.

## Technical Context

**Language/Version**: PHP `^7.4 || ^8.0` (declarado en `composer.json`); Node.js LTS para tooling frontend.
**Primary Dependencies**: Laravel `^8.75`, Tailwind `^3.1`, Alpine.js `^3.4`, Chart.js (CDN). Tras esta feature: Vite (ya instalado vía `vite.config.js`), Pint (nuevo), Prettier (nuevo).
**Storage**: MySQL/MariaDB (XAMPP local). Sin cambios en esta feature.
**Testing**: PHPUnit 9.5 con tests en `tests/Feature/` y `tests/Unit/`. Las pruebas existentes deben seguir pasando sin modificaciones.
**Target Platform**: Servidor LAMP/XAMPP en Windows (entorno actual del usuario) y previsiblemente Linux para producción. Navegadores modernos (Chrome/Edge) para el cliente.
**Project Type**: Web application (monolito Laravel con Blade + assets compilados).
**Performance Goals**: No se introducen objetivos nuevos. Como subproducto: `npm run build` con Vite debería ser tan rápido o más rápido que Mix. El bundle no debe crecer.
**Constraints**:
- Cero impacto en lógica de negocio.
- Cero impacto visual o de comportamiento para el usuario final.
- Cambios localizados en archivos de configuración, README, layout y tooling.
- Sin actualizaciones mayores de Laravel/PHP/Tailwind/Alpine.
**Scale/Scope**: Repositorio único, ~15 controladores, ~12 modelos, 65 plantillas Blade, 52 migraciones. Esta feature toca aproximadamente: 1 layout Blade, ~5 archivos de configuración raíz, ~3 archivos JS/CSS nuevos en `resources/`, README. No toca código bajo `app/`, `routes/`, `config/`, `database/`, `tests/`.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Estado**: La `constitution.md` del proyecto es una plantilla sin principios ratificados (placeholders `[PRINCIPLE_1_NAME]`, etc.). No hay reglas formales contra las que validar.

**Resultado del gate**: ✅ PASS por ausencia de constitución ratificada. Se aplican estos principios implícitos auto-impuestos por la spec, todos cumplidos:

| Principio implícito | Cumplimiento |
|---|---|
| No alterar lógica de negocio | ✅ Plan toca sólo configuración, layout cosmético y tooling |
| No alterar comportamiento de usuario | ✅ Validación manual en navegador como criterio de aceptación |
| Cambios reversibles | ✅ Todo en una rama feature, revertible con un `git revert` |
| No upgrades mayores | ✅ Sólo se añade Vite (ya presente), Pint, Prettier |
| Tests existentes siguen pasando | ✅ No se tocan tests ni código testeado |

**Re-evaluación post Fase 1**: ✅ PASS. Los artefactos de diseño (research, quickstart) no introducen violaciones.

## Project Structure

### Documentation (this feature)

```text
specs/001-cosmetic-cleanup/
├── plan.md              # Este archivo
├── spec.md              # Especificación funcional
├── research.md          # Decisiones técnicas (Phase 0)
├── quickstart.md        # Guía de validación manual (Phase 1)
├── checklists/
│   └── requirements.md  # Checklist de calidad de la spec
└── tasks.md             # Generado por /speckit-tasks (no por /speckit-plan)
```

> **Nota**: No se generan `data-model.md` ni `contracts/` para esta feature porque no introduce entidades de dominio nuevas ni interfaces externas. El skill template lo permite explícitamente: "Skip if project is purely internal (build scripts, one-off tools, etc.)".

### Source Code (repository root)

Estructura Laravel estándar — la feature **toca únicamente** los archivos marcados con `← MODIFICADO` o `← NUEVO`:

```text
easy_inventory/
├── app/                              # ← NO TOCADO
├── bootstrap/                        # ← NO TOCADO
├── config/                           # ← NO TOCADO
├── database/
│   ├── migrations/                   # ← NO TOCADO
│   ├── seeders/                      # ← NO TOCADO
│   ├── factories/                    # ← NO TOCADO
│   └── dumps/                        # ← NUEVO (destino de vidriosj_inventory.sql)
│       └── vidriosj_inventory.sql    # ← MOVIDO desde la raíz
├── public/                           # ← NO TOCADO (assets compilados se sustituyen, no se mueven manualmente)
├── resources/
│   ├── css/
│   │   ├── app.css                   # ← NO TOCADO
│   │   └── layout.css                # ← NUEVO (CSS extraído del layout)
│   ├── js/
│   │   ├── app.js                    # ← MODIFICADO (puede importar los nuevos módulos)
│   │   ├── bootstrap.js              # ← NO TOCADO
│   │   ├── session-manager.js        # ← NUEVO (gestión de sesión expirada e interceptores)
│   │   ├── language-selector.js     # ← NUEVO (selector de idioma)
│   │   └── sidebar.js                # ← NUEVO (toggle móvil + tablas responsivas)
│   ├── lang/                         # ← NO TOCADO
│   └── views/
│       └── layouts/
│           └── app.blade.php         # ← MODIFICADO (extracción de CSS/JS, fix asset paths)
├── routes/                           # ← NO TOCADO
├── storage/                          # ← NO TOCADO
├── tests/                            # ← NO TOCADO
├── vendor/                           # ← NO TOCADO
├── .editorconfig                     # ← MODIFICADO (reglas para JS/blade/json)
├── .env                              # ← NO TOCADO
├── .env.backup                       # ← BORRADO
├── .env.dev                          # ← NO TOCADO (revisar si entra en .gitignore)
├── .env.example                      # ← NO TOCADO
├── .gitignore                        # ← MODIFICADO (cubre .env.*, public/build, storage/app/public)
├── .prettierrc                       # ← NUEVO
├── .styleci.yml                      # ← NO TOCADO (coexiste con Pint)
├── composer.json                     # ← MODIFICADO (script `pint`)
├── inventory.zip                     # ← BORRADO
├── package.json                      # ← MODIFICADO (Vite scripts, sin laravel-mix, sin bootstrap)
├── package-lock.json                 # ← REGENERADO
├── pint.json                         # ← NUEVO
├── README.md                         # ← REESCRITO
├── vidriosj_inventory.sql            # ← MOVIDO a database/dumps/
├── vite.config.js                    # ← NO TOCADO (ya existe; revisar si necesita ajuste menor)
└── webpack.mix.js                    # ← BORRADO
```

**Structure Decision**: Web application monolítica Laravel — se mantiene la estructura Laravel estándar. La feature opera quirúrgicamente sobre archivos de configuración raíz, el layout principal y nuevos assets en `resources/`. Cero cambios en `app/`, `routes/`, `config/`, `database/migrations/`, `tests/`.

## Complexity Tracking

> No hay violaciones de constitución que justificar. El plan es deliberadamente simple: una rama, un PR, validación manual, sin nuevas abstracciones ni capas.

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|--------------------------------------|
| (ninguna) | — | — |
