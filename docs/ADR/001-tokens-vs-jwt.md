# ADR 001 — Tokens de sesión en BD en lugar de JWT

- **Estado**: aceptado
- **Fecha**: Fase 1 (2026)
- **Contexto**: autenticación de staff y cliente del portal.

## Contexto

REDYTELCA necesitaba un mecanismo de sesión que permitiera:
- Autenticación stateless o stateful.
- Revocación inmediata de sesiones.
- Funcionar sin dependencias externas (regla de la constitución).
- Ser comprensible para un jurado de tesis.

Las alternativas estándar son JWT (JSON Web Tokens) o tokens opacos persistidos en BD.

## Decisión

Usar **tokens opacos de 64 caracteres** (`bin2hex(random_bytes(32))`) persistidos en la tabla `sesiones`.

El cliente envía el token por header `X-Session-Token` (con fallback a query string, hallazgo AUD-2026-F02).

## Alternativas descartadas

### JWT con firma HMAC
- **Ventaja**: stateless, sin consulta a BD en cada request.
- **Razón de descarte**: la revocación inmediata requiere lista negra en BD, que anula la ventaja principal. Además, manejar refresh tokens añade complejidad que no se justifica para el tamaño del proyecto. El requisito de "revocación inmediata" viene de auditoría: si un admin revoca a un operador, debe ser efectivo en el siguiente request, no en la expiración del JWT.

### OAuth2 / OpenID Connect
- **Ventaja**: estándar industrial.
- **Razón de descarte**: overkill. Requiere servidor de identidad, gestión de clientes, redirects. Fuera del alcance de tesis.

### `$_SESSION` nativo de PHP
- **Ventaja**: cero código, funciona por defecto.
- **Razón de descarte**: no permite API stateless (frontend vanilla JS + fetch), no escala más allá de un servidor, y complica la trazabilidad. Además viola la regla 7 del proyecto.

## Consecuencias

### Positivas
- Revocación inmediata: `UPDATE sesiones SET expira_en = NOW() WHERE token = ?`.
- Duración controlable por configuración (30 días con renovación deslizante).
- Trazabilidad total: cada request queda con la sesión que lo ejecutó.
- Sin dependencias externas.

### Negativas
- Consulta a BD en cada request (`SELECT ... FROM sesiones JOIN usuarios ...`). Mitigable con caché (spec 069-cache-sesiones).
- La tabla `sesiones` crece indefinidamente (purga de expiradas >60 días en login).

### Neutras
- El token viaja en claro por HTTP. Requiere HTTPS en producción (spec 056-https-enforcement).

## Referencias

- `conexion.php` — `requireStaffAuth()`, `requireClientAuth()`.
- `login.php`, `logout.php`, `auth_state.php`.
- `bd_redytelca.sql` — tabla `sesiones`.
- Hallazgo AUD-2026-F02 (token en query string).