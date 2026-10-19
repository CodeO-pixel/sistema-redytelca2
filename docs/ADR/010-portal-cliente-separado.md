# ADR 010 — Portal de cliente separado del panel admin

- **Estado**: aceptado
- **Fecha**: 2026-10-12
- **Contexto**: la defensa exige plataforma funcional completa. El portal
  de cliente es la mitad faltante del sistema.

## Contexto

REDYTELCA nació como back-office: un panel admin donde el staff registra
manualmente lo que el cliente comunica por teléfono, WhatsApp o en
persona. Los módulos `tickets`, `tareas`, `pagos` y `clientes` funcionan,
pero no tienen entrada real del cliente final:

- El cliente no puede abrir tickets. El staff los crea en su nombre.
- El cliente no puede ver sus facturas. El staff las consulta.
- El cliente no puede subir comprobantes de pago. El staff los ingresa.
- El cliente no puede autenticarse. `clientes_credenciales` existe pero
  está vacía y no se usa.

La tabla `ticket_respuestas` tiene FK a `clientes_credenciales` para
permitir respuestas del cliente, pero es una columna muerta.
`pagos.origen` tiene un ENUM con `'portal_cliente'` que nunca se usa.
`sesiones.tipo_usuario` acepta `'cliente'` pero no hay flujo que lo cree.

## Decisión

Implementar un **portal de cliente separado** del panel admin,
accesible en `/cliente`, `/portal`, `/pagos` o `/pago` (router.php ya
soporta esos paths).

El portal es una aplicación distinta, no una vista del panel admin:

- Frontend propio (`portal_cliente.php` + `portal.js` + `portal.css`).
- Endpoints propios (`api_portal_*.php`) con `requireClientAuth`.
- Autenticación contra `clientes_credenciales`, sesión con
  `tipo_usuario='cliente'`.
- **Aislamiento por sesión**: el `id_cliente` NUNCA se toma del body
  HTTP. Siempre de la sesión. Previene BOLA/IDOR.

Alcance funcional:

- Login/logout.
- Ver facturas propias + estado + saldo.
- Registrar pago con comprobante (`origen='portal_cliente'`).
- Ver historial de pagos.
- Abrir tickets + ver historial + responder.
- Ver servicios y contratos propios.
- Descargar facturas en PDF (vía `window.print()`, sin librería).
- Recibir notificaciones.

## Alternativas descartadas

### Reusar `index.html` con vista diferenciada por tipo de usuario

- **Ventaja**: un solo frontend, menos archivos.
- **Razón de descarte**: `app.js` ya tiene ~2500 líneas sin modularizar
  (hallazgo F10). Añadir una segunda vista condicional lo empeora.
  Además, el portal debe estar aislado por seguridad: si un XSS afecta
  al panel admin, no debe afectar al portal, y viceversa. Son
  superficies de ataque distintas.

### Crear SPA en React/Vue

- **Razón de descarte**: viola constitución §1 (sin frameworks, sin
  build tools). El proyecto entero es vanilla JS. El portal también.

### Portal como página estática sin login

- **Razón de descarte**: expone datos de clientes sin autenticación
  (violación de la línea roja ISP de aislamiento de clientes). Un
  atacante podría cambiar `?cedula=X` y ver facturas de otros.

## Consecuencias

### Positivas

- Cierra la mitad "cliente" del sistema.
- `tickets`, `tareas`, `pagos`, `notificaciones` dejan de ser tablas
  sin entrada real.
- `clientes_credenciales` pasa a usarse.
- `pagos.origen='portal_cliente'` pasa a tener sentido.
- La demo de defensa puede mostrar el ciclo completo: cliente entra →
  abre ticket → staff responde → cliente recibe notificación → cliente
  responde → ticket cerrado.
- Alineado con el estándar de industria (Splynx, Sonar, SmartOLT,
  ISP Gestión).

### Negativas

- Aumenta la superficie de ataque. Hay que auditar todos los endpoints
  `api_portal_*.php` contra BOLA/IDOR con el mismo rigor que los
  endpoints staff.
- Requiere `clientes_credenciales` funcional: hoy está vacía. Hay que
  definir cómo se crean y se validan credenciales de cliente.
- `requireClientAuth()` existe pero nunca se ha ejercitado en runtime.
  Hay que probarlo end-to-end.
- Añade ~4.5 sesiones al cierre del proyecto.

### Neutras

- El panel admin sigue funcionando igual para el staff.
- Los reportes que agregan datos de clientes siguen funcionando.
- La política de CORS no cambia (mismo dominio, mismos headers).

## Referencias

- `docs/ROADMAP.md` — Bloque 19.
- `docs/ARQUITECTURA.md` — modelo de autorización.
- `conexion.php` — `requireClientAuth()`.
- `bd_redytelca.sql` — `clientes_credenciales`, `sesiones.tipo_usuario`,
  `pagos.origen`, `ticket_respuestas.id_credencial`.
- **Splynx** — "Customer Portal": portal de cliente con login, ver y pagar
  facturas, gestionar tickets, ver servicios.
- **Sonar Software** — "Self-hosted Customer Portal": submit new support
  tickets, view ticket history, check status of open requests.
- **SmartOLT** — "Auto-Login Portal del Cliente" + "Ver Historial de Pagos".
- **ISP Gestión** — "Consulta Tickets por el cliente".