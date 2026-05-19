# VIDRIOS J&P S.A.S. — Sistema de Inventario (`easy_inventory`)

Aplicación web de gestión de inventario, transferencias, salidas, importaciones y trazabilidad para VIDRIOS J&P S.A.S. (NIT 901.701.161-4). Interfaz multi-rol y multi-idioma (español, inglés, chino).

## Stack tecnológico

- **Backend**: PHP `^7.4 || ^8.0`, Laravel `^8.75`
- **Frontend**: Tailwind CSS 3, Alpine.js 3, Chart.js (vía CDN), SweetAlert2 (vía CDN), Bootstrap Icons (vía CDN)
- **Bundler**: Vite
- **Base de datos**: MySQL / MariaDB
- **Autenticación**: Laravel Breeze + Sanctum (tokens API)
- **PDF**: DomPDF, FPDF/FPDI, TCPDF, pdftk

## Requisitos

- PHP 7.4+ (recomendado 8.x)
- Composer 2.x
- Node.js LTS y npm
- MySQL/MariaDB (XAMPP, LAMP o equivalente)
- Servidor Apache con `mod_rewrite` (o `php artisan serve` para desarrollo)

## Instalación

```bash
# 1. Clonar y entrar al proyecto
git clone <repo-url> easy_inventory
cd easy_inventory

# 2. Dependencias PHP y Node
composer install
npm install

# 3. Configuración de entorno
cp .env.example .env
php artisan key:generate

# 4. Base de datos
# Crear la BD vacía (ej. en MySQL: CREATE DATABASE vidriosj_inventory;)
# Importar el dump de referencia:
mysql -u root -p vidriosj_inventory < database/dumps/vidriosj_inventory.sql
# O ejecutar las migraciones desde cero:
php artisan migrate

# 5. Compilar assets
npm run build

# 6. Servir
php artisan serve
# o acceder vía XAMPP en http://localhost/easy_inventory/
```

## Estructura no estándar (importante)

Este proyecto despliega Laravel con `index.php` y `.htaccess` en la **raíz** del repositorio, no en `public/`. El document root del servidor web debe apuntar a la raíz del proyecto.

Consecuencia práctica: en plantillas Blade, los assets dentro de `public/` se referencian como `asset('public/logo.png')`, `asset('public/images/flags/colombia.png')`, etc. **Esto es correcto en este deployment**; no cambiar a `asset('...')` sin reorganizar primero la estructura a estándar Laravel.

## Roles del sistema

- **`admin`**: acceso total. Gestiona usuarios, bodegas, productos, transferencias, salidas, importaciones, ITR, conductores, contenedores, stock, trazabilidad.
- **`funcionario`**: operación diaria (productos, transferencias, salidas, conductores, contenedores, ITR, stock, trazabilidad).
- **`importer`**: ve y opera sólo el módulo de importaciones (vista de proveedor).
- **`import_viewer`**: lectura del módulo de importaciones.
- **`proveedor_itr`**: acceso restringido al módulo ITR (desembalaje).

## Módulos / áreas funcionales

- Movimientos / Dashboard
- Productos
- Bodegas (sólo admin)
- Transferencias
- Salidas
- Conductores y contenedores
- Stock
- Trazabilidad
- Importaciones
- ITR (desembalaje)
- Usuarios (sólo admin)

## Comandos artisan personalizados

Los siguientes comandos viven en `app/Console/Commands/`:

- `php artisan app:clean-database` — limpieza/mantenimiento de datos.
- `php artisan app:debug-transfer-products` — diagnóstico de productos en órdenes de transferencia.
- `php artisan app:sync-itrs-from-imports` — sincroniza registros ITR a partir de importaciones.
- `php artisan app:update-products-calibre-from-name` — recalcula el calibre de productos a partir de su nombre.

Ejecutar `php artisan list app` para ver descripciones y firmas detalladas.

## Comandos de desarrollo

```bash
# Servir la app (sin Apache)
php artisan serve

# Compilar assets una vez
npm run build

# Modo desarrollo con HMR
npm run dev

# Format de código PHP (Pint)
composer pint           # aplicar formato
composer pint:test      # sólo verificar (no modifica)

# Format de código JS/CSS (Prettier)
npm run format          # aplicar formato
npm run format:check    # sólo verificar

# Tests
php artisan test
```

## Internacionalización

Tres idiomas disponibles: español (default), inglés, chino. Las traducciones viven en `resources/lang/{es,en,zh}/common.php`. Cambio de idioma vía el selector del header (rutas en `routes/web.php`).

## Licencia

Software propietario de VIDRIOS J&P S.A.S.
