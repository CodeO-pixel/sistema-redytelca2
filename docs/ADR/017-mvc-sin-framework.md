# ADR 017 — MVC sin framework

- **Estado**: aceptado
- **Fecha**: 2026-10-16
- **Contexto**: indicación del profesor.

## Contexto

El profesor exige aplicar el patrón Modelo-Vista-Controlador en la
plataforma. La constitución §1 prohíbe frameworks externos (Laravel,
Symfony) y build tools. MVC es un patrón de diseño, no un framework.
Compatible.

Estado actual del backend: Transaction Script. Cada `api_*.php` mezcla
validación, lógica de negocio, acceso a datos y manejo de errores en
el mismo archivo. `conexion.php` tiene `$pdo` como variable global.
Los errores se detectan por `$e->errorInfo[1]`. Las transacciones se
abren inline en cada endpoint.

Estado actual del frontend: `app.js` monolítico (~2500 líneas). Sin
separación física entre estado, vista y lógica.

## Decisión

Implementar MVC completo en backend y frontend siguiendo la estructura
estándar de la industria (Controller → Service → Repository → Model →
Database).

### Backend

- `core/` — kernel compartido:
  - `Bootstrap.php` — arranque + autoloading PSR-4.
  - `Config.php` — lee configuración por entorno.
  - `Database.php` — wrapper PDO.
  - `Response.php` — helpers JSON + conversión de excepciones a HTTP.
  - `Validator.php` — validador base.
  - `Auth.php` — middlewares actuales (`requireStaffAuth`,
    `requirePageAccess`, `requireOperation`, `setAuditContext`).
  - `Exceptions/` — excepciones de dominio (`ValidationException`,
    `NotFoundException`, `AuthorizationException`, `DomainException`).
  - `BaseModel.php`, `BaseRepository.php`, `BaseService.php`,
    `BaseController.php`.
- `models/` — entidades de dominio. Atributos + comportamiento.
  Sin SQL.
- `repositories/` — acceso a datos. Aquí vive todo el SQL (PDO).
- `services/` — lógica de negocio y orquestación. Abren y cierran
  transacciones.
- `validators/` — validación por entidad.
- `controllers/` — manejo del request. Delegan al servicio.
- `views/` — HTML del portal de pago. El admin usa JSON.
- `api/` — endpoints thin: auth → controller → JSON.

### Frontend

- `js/models/` — estado + operaciones fetch.
- `js/views/` — funciones de render.
- `js/controllers/` — orquestación.
- `js/core/` — Session, ApiClient, Router.

### Convenciones

- **Namespaces PSR-4**: `App\Core`, `App\Models`, `App\Repositories`,
  `App\Services`, `App\Controllers`, `App\Validators`, `App\Exceptions`.
- **Autoloading**: `spl_autoload_register` en `Bootstrap.php`.
- **Dependency Injection**: por constructor. `$pdo` se inyecta, no es
  global.
- **Transacciones**: las maneja el Service, no el Controller ni el
  Repository.
- **Page Controller**: cada endpoint en `api/` es un controlador
  delgado. No se implementa Front Controller con Router.

## Alternativas descartadas

- **Laravel/Symfony**: viola constitución §1.
- **Micro-framework (Slim, Lumen)**: dependencia externa. Composer no
  disponible en el entorno objetivo (XAMPP/cPanel).
- **Front Controller con Router**: solo se justifica en aplicaciones
  con lógica de enrutamiento compleja. La industria recomienda Page
  Controller para la mayoría de los casos. Aquí se usa Page Controller.
- **Container de DI**: útil cuando hay 100+ clases con dependencias
  complejas. Con ~20 clases, la inyección por constructor es suficiente.
- **Pipeline formal de middleware**: `requirePageAccess` +
  `requireOperation` ya cumplen la función. Un pipeline formal sería
  refactor cosmético sin ganancia real.

## Consecuencias

**Positivas**:
- Cumple indicación docente con MVC real, no solo carpetas nuevas
  sobre Transaction Script.
- Separación real de responsabilidades (Controller → Service →
  Repository → Model).
- DI por constructor permite tests unitarios con mocks.
- Excepciones de dominio reemplazan la detección por `$e->errorInfo[1]`.
- Alineado con PSR-4, PSR-1 y la práctica de la industria.

**Negativas**:
- Refactor masivo. ~35 endpoints backend, ~50 funciones frontend,
  toda la lógica reubicada. Riesgo alto de regresión.
- Se hace por lotes con verificación entre fases.
- Se construye un esqueleto propio de MVC (micro-framework casero).
  No viola la constitución (prohíbe frameworks externos), pero implica
  más código a mantener.

**Neutras**:
- Las URLs no cambian (Page Controller).
- El RBAC no se toca.
- La BD no cambia por MVC.
- Los triggers de BD no cambian.

## Referencias

- Constitución §1.
- PHP-FIG PSR-4 (autoloading).
- Fowler, *Patterns of Enterprise Application Architecture*:
  Service Layer, Repository, Page Controller, Front Controller.
- Symfony, Laravel — referencias de estructura de carpetas.
- Splynx (Yii2) — referencia de MVC en sistemas ISP.
- Universidad de La Coruña — Page Controller "mejor elección para la
  mayoría de las aplicaciones".
- Loco.rs — "JSON views for an API surface".