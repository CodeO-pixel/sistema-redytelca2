# Arquitectura — REDYTELCA

Descripción real del sistema tal como está construido. No incluye features aspiracionales. Si algo aquí no coincide con el código, es un bug del documento.

## Visión general

Sistema de gestión ISP con dos roles operativos:
- **Staff interno**: empleados del ISP (administrador, operador técnico).
- **Clientes del portal**: usuarios finales que acceden a su propio panel. *Actualmente sin flujo real (ver §7)*.

Arquitectura monolítica sin frameworks. Backend PHP sirviendo JSON a un frontend vanilla JS en la misma aplicación.

```
Navegador (index.html + app.js)
        ↓ fetch + X-Session-Token
PHP endpoints (api_*.php, *.php)
        ↓ PDO
MariaDB (bd_redytelca)
```

## Backend

### Stack
- PHP 8.2 (XAMPP)
- PDO con `ERRMODE_EXCEPTION`
- MariaDB 10.4

### Punto de entrada único
`conexion.php` es requerido por **todos** los endpoints. Contiene:

1. Cabeceras CORS (whitelist configurable vía `REDYTELCA_ALLOWED_ORIGINS`).
2. Manejo de preflight `OPTIONS`.
3. Conexión PDO (`$pdo`).
4. Funciones de hashing: `verificarYMigrarPassword()`, `hashearPasswordNueva()`.
5. Middleware `requireStaffAuth(PDO, ?int $minRoleId)`: valida token de staff.
6. Middleware `requireClientAuth(PDO)`: valida token de cliente.
7. Middleware `requirePageAccess(PDO, string $urlPagina)`: valida visibilidad de página según rol.

### Endpoints

**Recursos CRUD** (patrón `api_<recurso>.php` con GET/POST/PUT/DELETE):

| Endpoint | Recurso | Middleware |
|----------|---------|-----------|
| `api_clientes` (implícito vía `clientes_listar.php`, `registrar_cliente.php`, `cliente_actualizar.php`, `cliente_eliminar.php`) | Clientes | `requireStaffAuth` |
| `api_servicios.php` | Servicios (N por cliente) | `requirePageAccess('clientes')` |
| `api_contratos.php` | Contratos | `requirePageAccess('contratos')` |
| `api_facturas.php` | Facturas | `requirePageAccess('facturas')` |
| `api_pagos.php` | Pagos | `requirePageAccess('pagos')` |
| `api_planes.php` | Planes | `requirePageAccess('planes')` |
| `api_nodos.php` | Nodos de red | `requirePageAccess('nodos')` |
| `api_olts.php` | OLTs | `requirePageAccess('olts')` |
| `api_naps.php` | NAPs | `requirePageAccess('naps')` |
| `api_equipos.php` | Equipos CPE | `requirePageAccess('equipos')` |
| `api_tareas.php` | Tareas | `requirePageAccess('tareas')` |
| `api_tickets.php` | Tickets | `requirePageAccess('tickets')` |
| `api_empresa.php` | Configuración de empresa | GET público; POST `requireStaffAuth($pdo, 1)` |

**Autenticación y sesión**:

| Endpoint | Función |
|----------|---------|
| `login.php` | Valida credenciales, crea token, devuelve permisos |
| `logout.php` | Invalida token (setea `expira_en = NOW()`) |
| `auth_state.php` | Polling: valida token y recalcula módulos permitidos |
| `cambiar_password.php` | Auto-cambio o reset por admin |

**Administración de usuarios y roles**:

| Endpoint | Función |
|----------|---------|
| `usuarios_listar.php`, `usuarios_crear.php`, `usuarios_editar.php`, `usuarios_eliminar.php`, `usuarios_verificar_email.php` | CRUD usuarios |
| `roles_listar.php`, `roles_editar.php`, `roles_eliminar.php` | CRUD roles |
| `permisos_listar.php`, `permisos_guardar.php` | Matriz de permisos por rol |

### Convenciones del backend

- Respuesta siempre `{status: 'success'|'error', ...}` con `Content-Type: application/json`.
- Entrada aceptada como JSON o form-data.
- `try/catch(PDOException)` con códigos `1062` (duplicate) y `1452` (FK) mapeados a mensajes legibles.
- Transacciones (`beginTransaction`/`commit`/`rollBack`) en operaciones multi-tabla.

## Frontend

### Estructura
- `index.html`: SPA de una sola página con múltiples `<div class="page">` conmutables.
- `app.js`: ~2500 líneas de JavaScript vanilla. Estado global en `window.*`.
- `styles.css`: estilos únicos, sin CSS-in-JS ni preprocesadores.

### Gestión de estado
Cachés globales (arrays) por módulo:
```
window.clientesCache, window.serviciosCache, window.contratosCache,
window.facturasCache, window.pagosCache, window.planesCache,
window.nodosCache, window.oltsCache, window.napsCache,
window.equiposCache, window.tareasCache, window.ticketsCache,
window.usuariosCache, window.rolesCache
```

Cada `loadX()` hace `fetch` al endpoint, guarda en caché, y llama al `renderXPage()` correspondiente. La paginación es **client-side** (slice sobre el array cacheado).

### Autenticación en cliente
- Login → `POST login.php` → guarda `{token, user, roleId, roleName, modules}` en `localStorage`.
- Todas las peticiones añaden header `X-Session-Token`.
- `restoreSession()` en `DOMContentLoaded` valida contra `auth_state.php`.
- Polling cada 15s revalida sesión y recalcula módulos permitidos.

### Interacción con la API
- Fetch directo, sin wrapper global. Cada función maneja su error.
- `alert()`/`confirm()` nativos para feedback (no hay sistema de notificaciones).
- Sin framework, sin React/Vue/Alpine.

## Base de datos

- 20+ tablas, todas InnoDB, charset `utf8mb4_general_ci`.
- Esquema completo en `bd_redytelca.sql` (fuente de verdad).
- Documentación detallada en `docs/BD.md`.

### Tablas principales por dominio

| Dominio | Tablas |
|---------|--------|
| Clientes | `clientes`, `clientes_credenciales` |
| Servicios y contratos | `servicios`, `contratos`, `planes` |
| Facturación | `facturas`, `pagos` |
| Infraestructura | `nodos`, `olts`, `naps`, `equipos` |
| Soporte | `tickets`, `tareas`, `ticket_respuestas` |
| Usuarios y permisos | `usuarios`, `rol`, `permisos`, `usuario_rol`, `rol_modulo`, `rol_modulo_pagina`, `rol_modulo_permiso`, `modulos`, `paginas` |
| Sesiones | `sesiones`, `sesion_rol_activo` |
| Auditoría | `logs`, `transicion_estado` |
| Configuración | `configuracion_empresa`, `notificaciones` |

## Modelo de autenticación

- **No usa `$_SESSION` nativo** (regla del proyecto). El token vive en la tabla `sesiones`.
- Token de 64 caracteres hex (`bin2hex(random_bytes(32))`).
- Duración: 30 días con renovación deslizante en cada verificación.
- Header `X-Session-Token` con fallback a query string (último es cuestionable — ver hallazgos).

### Flujo de login
1. `POST login.php {username, password}`.
2. `verificarYMigrarPassword()` verifica hash o migra texto plano a bcrypt.
3. Se crea fila en `sesiones` con token y expiración.
4. Devuelve `{token, usuario, id_rol, rol_nombre, modulos_permitidos, must_change_password}`.
5. Cliente persiste en `localStorage`.

### Flujo de verificación
1. Cliente envía `X-Session-Token` en cada petición.
2. `requireStaffAuth()` / `requireClientAuth()` buscan el token en `sesiones` con `expira_en > NOW()`.
3. Si encuentra, extiende 30 días más.
4. Los módulos permitidos se recalculan en cada llamada a `auth_state.php`.

## Modelo de autorización

**Estado actual: incompleto.**

- `requirePageAccess($pdo, 'url')` verifica existencia de fila en `rol_modulo_pagina` para el rol del usuario y la URL.
- **NO** verifica operación (leer/crear/editar/eliminar). Un rol con visibilidad de la página puede ejecutar cualquier verbo.
- Las tablas `permisos`, `rol_modulo_permiso` y `usuario_rol` existen en la BD pero **no se consultan**.
- Excepción: `api_pagos.php` PUT y `cambiar_password.php` verifican `id_rol === 1` para acciones sensibles específicas.

Hallazgos relacionados: AUD-2026-001, AUD-2026-002, AUD-2026-X01.

## Modelo de sesiones

- Tabla `sesiones` con `id_sesion`, `token`, `tipo_usuario`, `id_usuario`/`id_credencial`, `creado_en`, `expira_en`, `revocado_en`, `motivo_cierre`.
- `logout.php` solo setea `expira_en = NOW()`. No usa `revocado_en` ni `motivo_cierre`.
- Sin gestión de sesiones concurrentes (múltiples tokens por usuario son válidos).

## Modelo de auditoría (logs)

- Triggers AFTER INSERT/UPDATE/DELETE en tablas de negocio insertan fila en `logs`.
- Los triggers leen variables de sesión MySQL `@app_usuario_id`, `@app_credencial_id`, `@app_tipo_actor` (con `COALESCE(..., 'sistema')`).
- **Ningún archivo PHP setea esas variables.** Todos los logs quedan con `tipo_actor='sistema'`.
- En corrección: spec 001-fix-auditoria-logs.

Hallazgo relacionado: AUD-2026-B03.

## Estado de integraciones

| Integración | Estado |
|-------------|--------|
| Hardware de red (OLTs, routers) | No implementado. CRUD de inventario solamente. |
| Pasarela de pagos | No implementado. `pagos` se registra manualmente. |
| Portal de cliente | Stub. `portal_cliente.php` no llama a ningún endpoint. |
| Notificaciones | Tabla existe, sin uso. |
| Email | No implementado. `email_verified` se gestiona manualmente. |

## Deuda técnica conocida

- `app.js` monolítico sin separación estado/vista/lógica.
- `conexion.php` mezcla 5 responsabilidades (CORS, PDO, hashing, auth staff, auth cliente, autorización).
- Polling agresivo (5s para datos, 15s para sesión) escala O(pestañas × usuarios).
- Sin tests automatizados hasta instauración del harness actual.
- Sin CI/CD.
- Sin `opencode.json` (MCP no configurado).
- Sin backups automáticos.
- Credenciales de BD hardcodeadas en `conexion.php`.

## Referencias

- `docs/BD.md` — esquema detallado.
- `docs/ROADMAP.md` — plan de correcciones.
- `docs/AUDITORIA_2026.md` — informe completo.
- `AGENTS.md` — reglas operativas.