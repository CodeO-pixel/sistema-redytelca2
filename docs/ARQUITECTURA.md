# Arquitectura — REDYTELCA

Descripción real del sistema tal como está construido. No incluye
features aspiracionales. Si algo aquí no coincide con el código, es un
bug del documento.

**Este documento describe cómo está construido.** No lleva:
- Estado de specs / fases → `ROADMAP.md`.
- Dónde vamos / fase activa → `MEMORY.md`.
- Hallazgos y deuda técnica → `AUDITORIA_2026.md`.
- Esquema detallado (tablas, columnas, triggers, relaciones) → `BD.md`.
- Convenciones de código y trampas del dominio → `AGENTS.md`.
- Estándares, frases de defensa, industria descartada → `REFERENCIA.md`.

## Visión general

Sistema de gestión ISP con dos roles operativos:
- **Staff interno**: empleados del ISP (administrador, operador técnico).
- **Clientes del portal**: usuarios finales que acceden a su propio panel.
  *Actualmente sin flujo real* (se implementa en Fase 5).

Arquitectura monolítica **modular** sin frameworks. Backend PHP sirviendo
JSON a un frontend vanilla JS. Los módulos están separados por dominio
funcional (`clientes`, `facturas`, `pagos`, `tickets`, etc.). Es la
práctica recomendada por la industria para sistemas de este tamaño
(Splynx y SmartOLT operan con la misma arquitectura).
Navegador (index.html + app.js → js/)
↓ fetch + X-Session-Token
Endpoints thin (api_*.php, *.php)
↓ Http::init() → core/Bootstrap.php → PDO
Controller → Service → Repository → Model → DB
↓
MariaDB (bd_redytelca)

text

## Backend

### Stack

- PHP 8.2 + PDO + MariaDB 10.4.
- Sin frameworks, sin Composer.
- Detalle de stack y comandos → `AGENTS.md` §Stack.

### Kernel MVC (`core/`)

Todas las capas comparten un kernel común en `core/`:

- `Bootstrap.php` — autoloading PSR-4 + `Bootstrap::init()` (PDO configurado + timezone UTC).
- `Http.php` — `Http::init()`: CORS, preflight, PDO. Punto de entrada estándar de endpoints thin.
- `Config.php` — lectura de `.env` o entorno.
- `Database.php` — wrapper PDO.
- `Response.php` — `Response::json()`, `Response::error()`, `Response::fromException()`.
- `Validator.php` — acumulador de reglas (`make()->required()->int()->...->validateOrFail()`).
- `Auth.php` — middlewares de autenticación y autorización (ver §Modelo de autorización).
- `Exceptions/` — `AppException`, `AuthenticationException`, `AuthorizationException`, `ConflictException`, `DomainException`, `NotFoundException`, `ValidationException`.
- `BaseModel.php`, `BaseRepository.php`, `BaseService.php`, `BaseController.php` — clases base.

`conexion.php` fue eliminado en 139d Tanda E (2026-10-18). La fuente de
verdad del auth y del PDO es `core/`.

### Capas MVC

| Capa | Dónde | Responsabilidad |
|---|---|---|
| **Model** | `models/` | Entidad de dominio: atributos + getters/setters. Sin SQL. |
| **Repository** | `repositories/` | Todo el SQL (PDO). Hereda de `BaseRepository`. |
| **Service** | `services/` | Lógica de negocio. Abre y cierra transacciones. No conoce HTTP. |
| **Validator** | `validators/` | `validateCreate()` + `validateUpdate()`. Solo forma, no reglas de negocio. |
| **Controller** | `controllers/` | Recibe el request, delega al service, devuelve JSON. No escribe SQL. |
| **Endpoint thin** | `api_*.php`, `*.php` | Auth → instanciar controller con `$pdo` → invocar método. |

**Page Controller** (Fowler, *PoEAA*). Cada endpoint en raíz es un thin
file que delega al controller. No hay Front Controller ni container de
DI: el alcance (20 controllers, 36 thin files) no lo justifica.

### Endpoints thin

Patrón (ver también `AGENTS.md` §"Endpoints thin"):

```php
<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core/Bootstrap.php';

use App\Controllers\XxxController;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Response;

try {
    $pdo = Http::init();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $controller = new XxxController($pdo);

    if ($method === 'GET') {
        Auth::requirePageAccess($pdo, 'xxx');
        Auth::requireOperation($pdo, 'xxx', 'leer');
        $controller->index();
    } elseif ($method === 'POST') {
        Auth::requirePageAccess($pdo, 'xxx');
        Auth::requireOperation($pdo, 'xxx', 'crear');
        $controller->store();
    } elseif ($method === 'PUT') {
        Auth::requirePageAccess($pdo, 'xxx');
        Auth::requireOperation($pdo, 'xxx', 'editar');
        $controller->update();
    } elseif ($method === 'DELETE') {
        Auth::requirePageAccess($pdo, 'xxx');
        Auth::requireOperation($pdo, 'xxx', 'eliminar');
        $controller->destroy();
    } else {
        Response::error('Método no soportado', 405);
    }
} catch (\Throwable $e) {
    Response::fromException($e);
}
Endpoints por dominio
Recursos CRUD (patrón api_<recurso>.php):

Endpoint	Recurso	Spec
clientes_listar.php	Clientes (listar)	139a
registrar_cliente.php	Clientes (crear)	139a
cliente_actualizar.php	Clientes (editar)	139a
cliente_eliminar.php	Clientes (desactivar)	139a
api_servicios.php	Servicios (N por cliente)	139b
api_contratos.php + api_contratos_vencer.php	Contratos	139b
api_facturas.php + api_facturas_vencer.php	Facturas	139b
api_pagos.php	Pagos (SoD admin para validar/rechazar)	139b
api_planes.php	Planes	139b
api_metodos_pago.php	Métodos de pago	139b
api_nodos.php	Nodos	139c
api_olts.php	OLTs	139c
api_naps.php	NAPs	139c
api_equipos.php	Equipos CPE	139c
api_tickets.php	Tickets	139c
api_tareas.php	Tareas	139c
api_pedidos.php	Pedidos (nuevo)	139c
roles_listar.php + roles_editar.php + roles_eliminar.php	Roles	139d
permisos_listar.php + permisos_guardar.php	Matriz de permisos	139d
api_empresa.php + api_empresa_publica.php	Configuración de empresa	139d
Autenticación y sesión (spec 139a):

Endpoint	Función
login.php	AuthController::login → AuthService::login. Crea token en sesiones. Roles desde usuario_rol. Módulos desde rol_modulo_pagina (con filtro SoD: no-Admin no ve módulo 4).
logout.php	AuthController::logout → AuthService::logout. SesionRepository::revocarByToken.
auth_state.php	AuthController::state → AuthService::validateSession. Renueva expira_en.
cambiar_password.php	UsuarioController::cambiarPassword. Auto-cambio o reset por admin. Único thin que usa `Auth::requireStaffAuth($pdo, null, true)` (bypass de must_change_password).
Administración de usuarios y roles (specs 139a/139d):

Endpoint	Función
usuarios_listar.php / usuarios_crear.php / usuarios_editar.php / usuarios_eliminar.php	UsuarioController (139a)
usuarios_verificar_email.php	UsuarioController::verificarEmail (139a)
roles_listar.php / roles_editar.php / roles_eliminar.php	RolController (139d)
permisos_listar.php / permisos_guardar.php	PermisoController (139d)
api_perfil.php	PerfilController::index (139a)
Response::fromException
Único punto de mapeo de excepciones a HTTP:

Excepción / código	HTTP	Notas
ValidationException	422	Incluye errors[] campo por campo
AuthenticationException	401	—
AuthorizationException	403	—
NotFoundException	404	—
ConflictException	409	—
DomainException	422	—
PDOException 1062	409	UNIQUE violado
PDOException 1451	409	FK en DELETE
PDOException 1452	409	FK en INSERT/UPDATE
PDOException 1644	422	SIGNAL de trigger (transicion_estado)
InvalidArgumentException	400	—
Resto	500	Genérico, sin fuga
Los controllers no atrapan excepciones de dominio. Solo el thin file
hace try/catch → Response::fromException.

Frontend
Estado actual (hasta 140)
index.html: SPA de una sola página con múltiples <div class="page">.

app.js: JavaScript vanilla monolítico (~2500 líneas). Estado global
en window.*Cache, window.selectedIds, window.dashboardCharts.
Sin separación estado/vista/lógica.

styles.css: estilos únicos, sin preprocesadores.

branding.css.php: stylesheet render-blocking que inyecta --accent
y --accent-rgb desde la BD para evitar el FOUC del branding
pre-login. Se carga en <head> después de styles.css (gana por
orden de aparición a igual especificidad). Es un thin CSS que usa
`Http::init()` + `ConfiguracionEmpresaRepository::findPublicBranding()`.
Reescrito en 139d Tanda D.

Estado futuro (spec 140, Fase 1)
app.js se reemplaza por una estructura modular:

text
js/
├── core/
│   ├── Session.js       — token, localStorage, buildFetchHeaders
│   ├── ApiClient.js     — wrapper de fetch con manejo 401/403
│   └── Router.js        — showPage, navegación
├── models/              — uno por entidad (estado + fetch)
├── views/               — uno por módulo (render)
└── controllers/         — uno por módulo (orquesta model + view)
Interacción con la API (estado actual)
Fetch directo, sin wrapper global. Cada función maneja su error con
try/catch.

alert()/confirm() nativos para feedback. Se reemplazan en Fase 3
(UI/UX) / spec 040.

Sin manejo global de 401/403. BUG-6.1 (permisos en tiempo real) y F08
(limpieza de cachés al logout) se resuelven en Fase 2.

Base de datos
Motor: MariaDB 10.4. Charset utf8mb4_unicode_ci. InnoDB.

44 tablas. Esquema completo, relaciones y triggers → docs/BD.md.

Fuente de verdad → bd_redytelca.sql.

Auditoría
Tabla auditoria_logs (renombrada de logs en BD-COMPLETA, 2026-10-16).

145 triggers activos: 129 de auditoría, 2 de SLA, 2 de unicidad de contrato, 10 de validación de transición, 2 de inmutabilidad de logs.

Los triggers leen @app_usuario_id, @app_credencial_id,
@app_tipo_actor (fallback 'sistema').

Auth::setAuditContext($pdo, $tipoActor, $idUsuario, $idCredencial)
setea esas variables al inicio de cada request autenticado.

Los logs son inmutables: trg_auditoria_logs_inmutable_update y
trg_auditoria_logs_inmutable_delete.

Vistas
v_clientes_360

v_equipos_asignados

v_estado_cuenta_cliente

v_facturas_con_saldo

Detalle de columnas de cada vista → docs/BD.md.

Modelo de autenticación
No usa $_SESSION nativo (regla del proyecto).

Token de 64 caracteres hex (bin2hex(random_bytes(32))).

Tabla sesiones con token, tipo_usuario, usuario_id/credencial_id,
creado_en, expira_en, revocado_en, motivo_cierre.

Duración: 30 días con renovación deslizante en cada verificación.

Header X-Session-Token.

must_change_password enforced en backend: Auth::requireStaffAuth()
rechaza con 403 si el flag está a 1. `cambiar_password.php` es el
ÚNICO thin que pasa `$bypassMustChangePassword = true` para permitir
el cambio (BUG-6.2, cerrado en 139d Tanda B).

Flujo de login
POST login.php {username, password}.

AuthController::login → AuthService::login.

Verifica usuarios.estado='Activo' (si no, 401).

password_verify() contra el hash. Fallback legacy para texto plano.

Se crea fila en sesiones con token y expiración.

Roles desde usuario_rol.

Módulos permitidos desde rol_modulo_pagina (nivel 1: visibilidad).

Guard SoD: si el usuario no tiene rol 1 (Administrador), se
filtran los módulos con id_modulo === 4 (Configuración).

Devuelve {token, usuario, roles, rol_nombre, modulos_permitidos, must_change_password}.

Cliente persiste en localStorage (hasta 140; después lo maneja
js/core/Session.js).

Flujo de verificación
Cliente envía X-Session-Token en cada petición.

Auth::requireStaffAuth() / Auth::requireClientAuth() buscan el
token en sesiones con expira_en > NOW().

Si encuentra, extiende 30 días más.

Los módulos se recalculan en cada auth_state.php. Guard SoD:
mismo filtro que en login.

Modelo de autorización
RBAC de dos niveles (patrón AWS VAMS), conforme a ANSI/INCITS 359 en
el nivel 2.

Nivel 1 — Visibilidad de página
Tabla rol_modulo_pagina (relación N:M entre rol_modulo y paginas).

Auth::requirePageAccess($pdo, 'url') verifica que el rol del usuario
tenga fila en rol_modulo_pagina para esa URL. Si no, 403.

Guard SoD: rechaza con 403 si la página pertenece al módulo 4
(Configuración) y el usuario no tiene el rol 1 (Administrador). El
guard aplica antes de la consulta de visibilidad. Fundamento:
NIST SP 800-53 AC-5 (SoD) y AC-6 (Mínimo Privilegio). Patrón: Google
Cloud IAM Deny Policies.

Nivel 2 — Operación sobre página
Tabla rol_modulo_permiso (relación N:M entre rol_modulo, paginas
y permisos).

Auth::requireOperation($pdo, 'url', 'leer|crear|editar|eliminar|validar')
verifica que el rol tenga el permiso por operación específica.

El verbo HTTP determina la operación: GET = leer, POST = crear,
PUT = editar, DELETE = eliminar.

SoD adicional en pagos: validar/rechazar un pago requiere rol 1
(Administrador). Vive en PagoController::update, no en
requireOperation.

Roles
Los roles del usuario se leen desde usuario_rol (N:M, multi-rol).

Asignación
PermisoController::guardarMatriz (spec 139d) escribe ambas tablas en
la misma transacción:

rol_modulo_pagina recibe las páginas visibles (visible = true).

rol_modulo_permiso recibe los permisos por operación marcados.

Nota: los permisos se insertan aunque la visibilidad esté
desmarcada. Desmarcar visibilidad deja los permisos inertes:
requirePageAccess rechaza antes de llegar a requireOperation.
Re-marcar visibilidad los reactiva sin reconfigurarlos.

Modelo de sesiones
Tabla sesiones (ver §Modelo de autenticación).

SesionRepository encapsula el SQL (findByToken, findVigenteByToken,
findByUsuarioId, revocarByToken, deleteExpiradasByUsuarioId).

logout.php → AuthService::logout → SesionRepository::revocarByToken
(setea expira_en = NOW(), revocado_en = NOW(),
motivo_cierre = 'logout').

Sin gestión de sesiones concurrentes (múltiples tokens por usuario son
válidos).

Modelo de auditoría
Triggers AFTER INSERT/UPDATE/DELETE en tablas de negocio insertan fila
en auditoria_logs.

Los triggers leen variables de sesión MySQL @app_usuario_id,
@app_credencial_id, @app_tipo_actor (con COALESCE(..., 'sistema')
como fallback).

Auth::setAuditContext() setea esas variables al inicio de cada
request autenticado, dentro de Auth::requireStaffAuth() y
Auth::requireClientAuth().

Logs inmutables: trg_auditoria_logs_inmutable_update y
trg_auditoria_logs_inmutable_delete.

Detalle de la tabla → docs/BD.md. Detalle de triggers → docs/BD.md.

Integraciones
Integración	Estado
Hardware de red (OLTs, routers)	No implementado. CRUD de inventario solamente (Fase 1).
Pasarela de pagos	No implementado. pagos se registra manualmente (Fase 1).
Portal de cliente	Stub. portal_cliente.php no llama a ningún endpoint. Se implementa en Fase 5 (096a–f).
Notificaciones	Tablas existen, sin uso. Se implementa en Fase 5 (086a–d).
Email	No implementado. email_verified se gestiona manualmente. Se implementa en Fase 7 (116a–d).
IPAM	Tablas ip_pools/ip_asignaciones eliminadas en BD-COMPLETA (ADR 015). Se reintroduce en Fase 7 (114a–c) si aplica.
Referencias
bd_redytelca.sql — fuente de verdad del esquema.

docs/BD.md — esquema detallado (tablas, columnas, triggers, relaciones).

docs/ROADMAP.md — plan por fases y estado de specs.

docs/AUDITORIA_2026.md — informe canónico de hallazgos.

docs/REFERENCIA.md — estándares, frases de defensa, industria descartada.

docs/VERIFICACION.md — lecciones de verificación.

MEMORY.md — estado entre sesiones.

AGENTS.md — reglas operativas del agente, convenciones de código,
trampas del dominio.

docs/ADR/001-tokens-vs-jwt.md — por qué tokens en BD en lugar de JWT.

docs/ADR/002-sin-frameworks.md — por qué PHP/JS puro sin frameworks.

docs/ADR/003-servicios-1n.md — por qué un cliente tiene N servicios.

docs/ADR/004-rbac-migracion-001a.md — migración al modelo RBAC Core N:M.

docs/ADR/005-extension-rol-modulo.md — extensión rol_modulo + rol_modulo_permiso.

docs/ADR/006-auditoria-inmutable.md — auditoría inmutable vía triggers.

docs/ADR/007-config-admin-only.md — Config Admin-only (Grupo E, Diseño B.3).

docs/ADR/008-rbac-dos-niveles.md — RBAC dos niveles con rol_modulo_pagina.

docs/ADR/009-branding-publico-separado.md — endpoint público de branding.

docs/ADR/010-portal-cliente-separado.md — portal separado del panel admin.

docs/ADR/011-notificaciones-servicio.md — notificaciones como servicio transversal.

docs/ADR/012-alcance-proyecto.md — dentro/fuera del alcance de tesis.

docs/ADR/013-bd-completa-refactor.md — refactor consolidado de BD.

docs/ADR/014-normalizacion-direcciones-telefonos.md — normalización.

docs/ADR/015-reduccion-alcance-bd-completa.md — reducción de alcance.

docs/ADR/016-convencion-claves.md — convención PK=id / FK=tabla_id.

docs/ADR/017-mvc-sin-framework.md — MVC completo sin frameworks externos.

text