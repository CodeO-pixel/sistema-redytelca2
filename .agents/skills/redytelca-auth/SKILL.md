---
name: redytelca-auth
description: Úsala siempre que escribas, modifiques o revises código de autenticación, autorización, sesiones, tokens, login/logout, contraseñas o cualquier verificación de identidad en REDYTELCA (archivos login.php, logout.php, auth_state.php, cambiar_password.php, conexion.php, api_pagos.php, y cualquier endpoint que use requireStaffAuth/requireClientAuth/requirePageAccess).
---

# Autenticación y autorización en REDYTELCA

Reglas no negociables. Si el código nuevo las viola, está mal.

## Reglas de oro

1. **Nunca `$_SESSION` nativo de PHP.** El mecanismo real es token en tabla `sesiones` + header `X-Session-Token`.
   - **Excepción activa**: `login.php` y `logout.php` todavía usan `session_start()`. Es un bug conocido (AUD-2026-B01). No lo repliques.
2. **Nunca comparar contraseñas en texto plano.** Siempre `password_verify()`.
3. **Nunca guardar contraseñas sin hash.** Siempre `password_hash($pwd, PASSWORD_DEFAULT)`.
4. **Nunca tomar `id_usuario_valido` desde el body HTTP.** Siempre de la sesión autenticada.
5. **Nunca `$_SERVER['HTTP_X_SESSION_TOKEN']` sin trim.** El código ya lo hace; replícalo.

## Middlewares disponibles (`conexion.php`)

| Función | Verifica | Devuelve |
|---------|----------|----------|
| `requireStaffAuth(PDO, ?int $minRoleId)` | Token de staff válido y no expirado. Opcionalmente `id_rol <= $minRoleId`. | `['id_usuario', 'id_rol', 'username']` |
| `requireClientAuth(PDO)` | Token de cliente del portal. | `['id_credencial', 'id_cliente']` |
| `requirePageAccess(PDO, string $urlPagina)` | Como `requireStaffAuth` + que el rol tenga la página asignada en `rol_modulo_pagina`. | Igual que `requireStaffAuth` |

**Uso típico al inicio de un endpoint**:
```php
require 'conexion.php';
$staffSession = requirePageAccess($pdo, 'facturas');
```

## Modelo de autorización (estado actual)

- **1 rol por usuario** (`usuarios.id_rol`, FK simple).
- **Autorización por página**, no por operación.
- Un rol con visibilidad de "Facturas" puede crear, editar, eliminar facturas. No hay gate por verbo.
- **Excepciones**: `api_pagos.php` PUT y `cambiar_password.php` verifican `id_rol === 1` para acciones sensibles.
- **Hallazgos activos**: AUD-2026-001 (RBAC no conforme), AUD-2026-002 (sin gate por operación), AUD-2026-X01 (mismatch schema/runtime).

## Tablas de autorización

- **`rol_modulo_pagina`** — la única que `requirePageAccess()` consulta. Columnas: `id_rol`, `id_modulo`, `id_pagina`.
- **`usuarios.id_rol`** — rol único del usuario.
- **NO usar**: `usuario_rol`, `rol_modulo_permiso`, `permisos`, `rol_modulo`. Existen pero están muertas.

## Modelo de sesiones

- Tabla `sesiones`. Columnas clave: `token`, `tipo_usuario` (`'staff'`/`'cliente'`), `id_usuario`, `id_credencial`, `expira_en`, `revocado_en`, `motivo_cierre`.
- Token: `bin2hex(random_bytes(32))` → 64 caracteres hex.
- Duración: 30 días con renovación deslizante en cada verificación válida.
- Revocación: `UPDATE sesiones SET expira_en = NOW() WHERE token = ?`.
- `logout.php` **no usa** `revocado_en` ni `motivo_cierre` (hallazgo AUD-2026-B21).

## Flujo de login (login.php)

1. `SELECT id_usuario, username, password, id_rol, must_change_password, intentos_fallidos, bloqueado_hasta`.
2. Verificar `bloqueado_hasta > NOW()`. Si está bloqueado: HTTP 403.
3. `verificarYMigrarPassword($pdo, $pwd, $hash, $idUsuario)`.
4. Si falla: incrementar `intentos_fallidos`. A los 5, bloquear 15 min.
5. Si pasa: resetear intentos, generar token, insertar en `sesiones`, cargar módulos permitidos.
6. **Bug conocido**: no verifica `usuarios.estado` (AUD-2026-B02).

## Flujo de cambio de contraseña (cambiar_password.php)

Dos modos:
- **Auto-cambio**: el usuario cambia su propia contraseña. Requiere `password_actual` y verifica con `password_verify()`. Solo puede hacerlo sobre su propio username.
- **Reset por admin**: `id_rol === 1` puede resetear la contraseña de otro usuario. Marca `must_change_password = 1`.

## `must_change_password`

- Columna en `usuarios`.
- `login.php` la devuelve en la respuesta.
- `auth_state.php` la devuelve en cada verificación.
- `app.js` bloquea el modal si es `true`.
- **Ningún endpoint backend la verifica.** Un usuario con contraseña temporal puede llamar directamente a `api_*.php`. Hallazgo AUD-2026-B04.

## Header de autenticación

- Nombre: `X-Session-Token`.
- Valor: el token de 64 caracteres.
- Fallback histórico: query string `?token=...` (hallazgo AUD-2026-F02, a eliminar).
- **En cliente**: `app.js` → `buildSessionFetchHeaders()` lo añade automáticamente.

## Contraseñas

- Hash: `bcrypt` vía `password_hash($pwd, PASSWORD_DEFAULT)`.
- Verificación: `password_verify($pwd, $hash)`.
- Migración lazy: si la contraseña guardada está en texto plano, se migra a hash en el primer login exitoso (`verificarYMigrarPassword()` en `conexion.php`).
- **Contraseña temporal en `usuarios_crear.php`**: `'Redytelca123!'` — predecible (AUD-2026-B15).
- **Sin validación de fortaleza** (AUD-2026-B14).

## Bloqueo por fuerza bruta

- 5 intentos fallidos → `bloqueado_hasta = NOW() + 15 min`.
- Verificación en login: `bloqueado_hasta > NOW()`.
- **Sin rate limiting por IP** (AUD-2026-S04).

## Checklist antes de escribir código de auth

- [ ] ¿Estoy usando `requireStaffAuth` / `requireClientAuth` / `requirePageAccess`, no reinventando la validación?
- [ ] ¿Si el endpoint lee el body, la autorización real vive en el backend, no solo en el frontend?
- [ ] ¿NO estoy introduciendo `$_SESSION` nuevo?
- [ ] ¿NO estoy tomando `id_usuario` del body HTTP?
- [ ] ¿Los hashes de contraseña van con `password_hash` / `password_verify`?
- [ ] ¿Si el cambio es sensible (validar pagos, cambiar roles), verifiqué `id_rol === 1`?
- [ ] ¿Si estoy tocando `login.php` / `logout.php` / `cambiar_password.php`, hay spec aprobada?

## Referencias

- `conexion.php` — implementación de los middlewares.
- `login.php`, `logout.php`, `auth_state.php`, `cambiar_password.php`.
- `bd_redytelca.sql` — tablas `usuarios`, `sesiones`, `rol`, `rol_modulo_pagina`.
- `docs/AUDITORIA_2026.md` — hallazgos B01, B02, B04, B05, S04, F01, F02, X02.
- `docs/ARQUITECTURA.md` — §modelo de autenticación.