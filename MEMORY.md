# MEMORY.md — REDYTELCA

Estado operativo entre sesiones. **RAM del proyecto, no disco.** Solo lo que un agente necesita al inicio de sesión: dónde estamos, qué sigue, qué deudas bloquean, qué reglas aplicar ahora, dónde buscar el resto.

- Specs y su estado → `docs/ROADMAP.md`.
- Detalle histórico de Fase 1 → `docs/HISTORICO_FASE1.md` (⚠️ parcial: B2 recuperado 2026-10-31; B3.0–B3.13 perdido).
- Detalle de A2-B.1/3/4/5 (backend) → `docs/ROADMAP.md` §Histórico + `docs/ARQUITECTURA.md` §Notificaciones + §Portal de cliente.
- Hallazgos canónicos → `docs/AUDITORIA_2026.md`.
- Esquema de BD → `bd_redytelca.sql` + `bd_redytelca_schema.sql` + `docs/BD.md`.
- Cómo está construido → `docs/ARQUITECTURA.md`.
- Estándares, citas de industria, frases de defensa → `docs/REFERENCIA.md`.
- Lecciones de verificación → `docs/VERIFICACION.md`.
- Reglas del repo → `AGENTS.md`.
- Reglas del proceso → `docs/METODOLOGIA_DESARROLLO_IA.md`.

---

## Estado actual

**Fase activa: Fase 2 — F2.0a + 38 specs F2.1–F2.38.**

### Cerradas

- Fase 1 (MVC) — 2026-10-30. 22 módulos MVC. `app.js` eliminado.
- Cierre documental F2.0b — 2026-10-31.
- Fase 4 (Roles + permisos en grilla) — 2026-10-31. 6 sub-fases (4.0–4.4).
- Tanda de fixes 2026-10-31 — scroll + polish matriz.
- A2-B.1 (notificaciones backend) — 2026-10-31.
- A2-B.3 (portal auth backend) — 2026-10-31.
- A2-B.4 (portal facturas/pagos/servicios backend) — 2026-11-01.
- A2-B.5 (portal tickets/respuestas backend) — 2026-11-01.
- 086c (campana staff + página `notificaciones`) — 2026-11-01.
- 096a (portal auth UI) — 2026-11-01. `portal_cliente.php` reescrito como SPA. `portal.css`, `js/portal/core/{Session,Router}.js` (forks con clave `redytelca_portal_session` y `SECTION_MAP` propio), `js/portal/controllers/AuthController.js`, `js/portal/views/AuthView.js`, `js/portal/main.js`. `ApiClient.js` modificado para elegir sesión según `window.__appContext` (`'portal'` vs default admin). Bug encontrado y corregido: `ApiClient` usaba siempre `window.Session` → 401 "No autenticado" en cambio de contraseña.
- 096e (portal servicios UI, read-only) — 2026-11-01. Página `portal-servicios` con tabla, búsqueda, paginación client-side, modal de detalle (red NAP·OLT·Nodo, equipo, coordenadas, fechas UTC→local). BOLA/IDOR cerrado (segundo cliente no ve los servicios del primero, verificado 3.6).
- 096b (portal facturas UI, read-only, `formatDual`) — 2026-11-01. Página `portal-facturas` con tabla, filtros server-side (estado, periodo), paginación server-side, modal de detalle con líneas de factura + totales (`total`, `pagado`, `saldo`) todos con `formatDual`. Fix durante verificación: el backend del portal NO devuelve `nombres`/`apellidos`/`cedula` (decisión A2-B.4: el cliente ya se conoce a sí mismo, no se repite PII). El `FacturaView.renderDetailModal` lee estos datos de `PortalSession.get().cliente` con fallback a `factura.nombres` por si el backend los incluye en el futuro. Verificado 2.1–2.10.
- 096c (portal pagos UI, list + create + upload) — 2026-11-01. Página `portal-pagos` con toolbar (filtro `estado` + botón "Registrar pago"), tabla con `formatDual`, paginación server-side. Modal "Registrar pago" con: select servicio (desde `PortalServicioModel.cache`), select factura filtrado por servicio (excluye pagadas/anuladas, opción "pago a cuenta"), monto USD, moneda (USD/VES), tasa BCV condicional (visible solo si VES), fecha (datetime-local → `Y-m-d H:i:s` al backend), método de pago, referencia bancaria condicional (si `metodo.requiere_referencia`), upload de comprobante (JPG/PNG/WEBP/PDF, máx 2 MB, SVG rechazado) con preview para imágenes. Modal de detalle con datos del cliente (leídos de `PortalSession.get().cliente` por A2-B.4) + descarga de comprobante vía `fetch` + blob (el endpoint requiere `X-Session-Token`, no se puede abrir como URL estática). "Pago a cuenta" confirmado con `Modal.confirm` antes de enviar. Archivos: `js/portal/models/MetodoPagoModel.js`, `js/portal/models/PagoModel.js`, `js/portal/views/PagoView.js`, `js/portal/controllers/PagoController.js`, `js/portal/main.js` (Router.register). Verificado 3.1–3.20.
- 096d (portal tickets UI, list + create + detail + reply) — 2026-11-02. Página `portal-tickets` con tabla (Asunto, Categoría con swatch, Prioridad, Estado, Creado, SLA vence, Acciones), filtros server-side (`estado`, `prioridad`), paginación server-side. Modal de detalle con hilo de respuestas (`autor_label` resuelto por el backend vía JOIN, timestamps UTC→local) + textarea de respuesta inline. Modal de "Nuevo ticket" con selects de categoría (desde `PortalTicketModel.categoriasCache`) y servicio (desde `PortalServicioModel.cache`). **Ticket `Cerrado` es terminal para el cliente** (Zendesk: *"Closed is forever. There's no reopening a Closed ticket"*): textarea + botón "Enviar respuesta" deshabilitados, mensaje "Crea un ticket nuevo". Backend pre-req creado en la misma spec: `api_portal_categorias_ticket.php` (thin con `requireClientAuth` sin bypass, GET solo, 401 sin token, 405 en POST) + `CategoriaTicketService::listarActivas()` + `CategoriaTicketController::indexPortal()`. **Búsqueda por texto NO implementada** (la paginación es server-side; un filtro client-side solo vería la página actual y confundiría al usuario). Deuda `PORTAL-TICKET-BUSQUEDA-AVANZADA` ampliada a Fase 2. Archivos nuevos: `js/portal/models/TicketModel.js`, `js/portal/views/TicketView.js`, `js/portal/controllers/TicketController.js`. Modificados: `portal_cliente.php` (página + 2 modales + scripts), `js/portal/main.js` (`Router.register('portal-tickets')` + `__portalCaches()` con tickets y categorías). Verificado browser: filtros, crear, ver detalle, responder, ticket cerrado deshabilitado, BOLA/IDOR cerrado (cliente B no ve tickets de A), `must_change_password` bloqueante.
- 096f (portal PDF de factura, browser print) — 2026-11-02. `FacturaPdfView.js` (nuevo) genera un popup con HTML print-friendly (`@media print` embebido, A4, márgenes 18mm, logo de empresa, datos de cliente/factura, tabla de líneas, totales, footer) y dispara `window.print()` tras 250ms. `FacturaView.renderDetailModal` añade botón "Imprimir / PDF" con listener inline (el modal se re-genera en cada apertura). **Sin librerías externas** (constitución §1): el usuario elige "Guardar como PDF" en el diálogo nativo del navegador. **El PDF es informativo, no fiscal.** SENIAT exige número de control, RIF del emisor y del receptor, y datos fiscales que el esquema actual no tiene. Footer lleva aviso explícito: *"Documento informativo. No constituye factura fiscal."* Deuda `FACTURA-FISCAL-SENIAT` a Fase 7. Patrón Splynx (facturas no fiscales vía browser print). `formatDual` reusado de `FacturaView` (misma lógica en tabla, detalle y PDF). Verificado browser: login → Facturas → detalle → "Imprimir / PDF" → diálogo de impresión → "Guardar como PDF".
- **086d (notif cliente en portal) — 2026-11-02.** Campana de notificaciones del cliente en `portal_cliente.php` (topbar + badge + dropdown + modal de detalle), `portal.css` con la sección `portal-notif-*` (30 clases: `portal-notif-bell`, `portal-notif-bell-badge`, `portal-notif-dropdown`, `portal-notif-item`, `portal-notif-unread`, `portal-notif-empty`, `portal-notif-link`, etc.; badge rojo `var(--danger)`, item no leído con fondo `#eff6ff`, dropdown responsivo a ancho completo en móvil). Archivos nuevos: `js/portal/models/NotificacionModel.js`, `js/portal/views/NotificacionView.js`, `js/portal/controllers/NotificacionController.js`. `main.js` registra el hook `window.__onPortalLoginSuccess = () => PortalNotificacionController.startPolling()` y lo invoca post-login/restore; `PortalAuthController._clearSession()` detiene el polling y limpia el modelo en logout/restore fallido. `PortalNotificacionController.startPolling()` hace un `_pollOnce()` inicial (badge visible en ≤2s, no 30s) y luego `setInterval(30s)`. `markRead(id, url)` decrementa el badge y navega a `url_destino` (`/portal/tickets?id=N`, `/portal/pagos?id=N`, `/portal/facturas?id=N`) vía `PortalRouter.showPage` + `showDetail`. **086d-fix (2026-11-02):** `PortalNotificacionController.init()` invocado en `DOMContentLoaded`. Sin esto, el listener del click en la campana nunca se ataba y el dropdown no abría (el badge sí aparecía porque el polling lo arranca `main.js`). Verificado en browser: campana, badge, dropdown, mark all read, click en item → navega + detalle. Con `Ctrl+Shift+R` y DevTools → Network abierta.

### En progreso

- **Ninguna.** Última cerrada: 086d (notif cliente en portal). Próxima: F2.0a.

### Próximos pasos

1. **F2.0a** — auditoría de usabilidad pre-Fase 2. Documento `docs/AUDITORIA_USABILIDAD_B3.md` (nuevo). Incluye `BUG-ENTER-TAREA-TICKET`, `UX-PERFIL-ESTADO-SESION`, `UX-PERFIL-LIMIT-AMBIGUO`, `UX-PAGO-RECHAZAR`, `UX-PERM-BOTONES`.
2. **Fase 2** — 38 specs de dominio (F2.1–F2.38) + deudas heredadas de MVC y Fase 5.
3. **A2-B.2, A2-B.6, A2-B.7** (backend Fase 7) antes de Fase 7.

### Conteos

- Cerradas: **154** specs (134 hasta Fase 1 + 20 post-Fase-1: F2.0b, Fase 4 ×6, tanda 2026-10-31, A2-B.1/3/4/5, 086c, 096a, 096e, 096b, 096c, 096d, 096f, 086d).
- En progreso: **0**.
- Pendientes: Fase 2 (38 specs F2.1–F2.38 + deudas heredadas), A2-B.2/A2-B.6/A2-B.7 (3), Fase 7 (~30 specs), Fase 3 (6 specs).
- Bugs abiertos Fase 1: **0**.
- Bugs abiertos Fase 4: **0**.
- Bugs abiertos Fase 5: **0** (8/8 specs cerradas: 086c, 096a, 096e, 096b, 096c, 096d, 096f, 086d).
- Bugs abiertos Fase 2 (dominio): **38** (F2.1–F2.38).

### A2-B

A2-B.1 ✅, A2-B.3 ✅, A2-B.4 ✅, A2-B.5 ✅. **Pendientes: A2-B.2, A2-B.6, A2-B.7** (Fase 7).

### 22 módulos frontend admin MVC + notificaciones + portal

**Admin:** Clientes, Planes, Nodos, OLTs, NAPs, Servicios, Equipos, Roles, Permisos, Usuarios, Facturas, Pagos, Contratos, Tickets, Auditoría, Tareas, Pedidos, Empresa, Perfil, Mapa, Dashboard, Reportes + **Notificaciones (086c)**.

**Portal (8/8 cerrados):** Auth (096a), Servicios (096e), Facturas (096b), Pagos (096c), Tickets (096d), PDF factura (096f), **Notif cliente (086d)**. Cerrado el 2026-11-02.

---

## Orden del proyecto (aprobado 2026-11-02)

| # | Bloque | Estado |
|---|---|---|
| 1 | Fase 1 — MVC | ✅ cerrada 2026-10-30 |
| 2 | Cierre documental (F2.0b) | ✅ cerrada 2026-10-31 |
| 3 | Fase 4 — Roles + permisos en grilla | ✅ cerrada 2026-10-31 |
| 3.5 | Tanda de fixes 2026-10-31 | ✅ cerrada 2026-10-31 |
| 3.6 | A2-B.1 — Notificaciones backend | ✅ cerrada 2026-10-31 |
| 3.7 | A2-B.3 — Portal auth backend | ✅ cerrada 2026-10-31 |
| 3.8 | A2-B.4 — Portal facturas/pagos/servicios backend | ✅ cerrada 2026-11-01 |
| 3.9 | A2-B.5 — Portal tickets/respuestas backend | ✅ cerrada 2026-11-01 |
| 4 | **Fase 5 — Portal + Notificaciones (UI)** | **✅ cerrada 2026-11-02** (086c, 096a, 096e, 096b, 096c, 096d, 096f, 086d) |
| 5 | **Fase 2 — F2.0a + 38 specs F2.1–F2.38** | **⏳ siguiente** |
| 6 | A2-B.2, A2-B.6, A2-B.7 (backend Fase 7) | ⏳ |
| 7 | Fase 7 — Resto | ⏳ |
| 8 | Fase 3 — UI/UX bonita | ⏳ |

---

## Deudas vivas (por fase)

### Fase 5 (resto)

| ID | Deuda | Se cierra en |
|---|---|---|
| **A2-B.4-TEST** | `src/tests/verify_a2b4.php` no existe. 096d NO lo creó (la spec 096d se enfocó en portal categorías ticket + UI tickets). La verificación de A2-B.4 se ejecutó ad-hoc (documentada en `VERIFICACION.md` §2.43–§2.45). Crear el archivo formal (~25 checks). | **Fase 2** |
| **PORTAL-TICKET-BUSQUEDA-AVANZADA** | Búsqueda por SLA, fecha, categoría, técnico. En 096d solo filtro `estado` + `prioridad` (server-side). El texto libre no se implementó porque la paginación es server-side y un filtro client-side solo vería la página actual. | Fase 2 (si aplica) |
| **FACTURA-FISCAL-SENIAT** | El PDF de factura es informativo, no fiscal. SENIAT exige número de control, RIF del emisor y del receptor, y datos fiscales que el esquema actual no tiene. Aviso explícito en el footer del PDF. | Fase 7 (si aplica) |

**Resueltas en Fase 5:**
- `PORTAL-CATEGORIAS-TICKET` — ✅ 096d (2026-11-02).
- `PORTAL-FACTURA-PDF` — ✅ 096f (2026-11-02).
- `PORTAL-PAGOS-A-CUENTA` — ✅ 096c (2026-11-01).

### Fase 2 (F2.0a + 38 specs F2.1–F2.38)

- **F2.0a** — auditoría de usabilidad pre-Fase 2.
- **F2.1–F2.38** — 38 specs de dominio. Detalle en `docs/ROADMAP.md` §Fase 2.
- **A2-B.4-TEST** — `verify_a2b4.php` (heredada de Fase 5).
- **BUG-ENTER-TAREA-TICKET** — Enter + `_saving` en `TareaController.save()` y `TicketController.save()`.
- **UX-PERFIL-ESTADO-SESION / UX-PERFIL-LIMIT-AMBIGUO / UX-PAGO-RECHAZAR / UX-PERM-BOTONES** — 4 deudas Capa 2 diferidas a F2.0a.
- **F-16** — `onclick`/`oninput` inline en `index.html`.
- **B3.8-DOC-SIDEBAR** — `categorias-ticket` y `sla-definiciones` sin nav-item (hack temporal en `main.js`).
- **B3.8-ROUTER-PARENT** — `Router.showPage('categorias-ticket')` sin nav-item.
- **AUDIT-IP-NULL** — 129 triggers de auditoría no escriben `ip_address`.
- **AUDIT-DUMMIES-SEED** — dummies de B3.5–B3.15 en `auditoria_logs` + `logo.*` huérfanos + credenciales de portal de 096a–096f.
- **LOGOS-GEMINI-RENOMBRADOS** — `public/Logos/` con nombres raros.
- **BD-REF-VERIFICACION-37-39** — `BD.md` cita `VERIFICACION.md` §2.37–§2.39, inexistentes.
- **EST-02** — `sla_definiciones` "3 de 8" combinaciones. Número a reconciliar contra `bd_redytelca.sql` (probablemente "3 de 12": 4 categorías × 3 prioridades).
- **PORTAL-URL-REWRITE** — `/portal` requiere `.htaccess` con RewriteRule en Apache. Hoy se usa `portal_cliente.php` directamente.
- **PORTAL-CREDENCIAL-UI** — Botón "Resetear credencial del portal" en la ficha del cliente. Detectado en 096e (2026-11-01).
- **PORTAL-CREDENCIAL-PASSWORD-OCULTA** — La UI descarta el `password_temporal` al crear cliente. Detectado en 096e (2026-11-01).

### Fase 7 (backend Fase 7)

- **A2-B.2** — notas crédito/débito.
- **A2-B.6** — email (`email_plantillas`, `email_log`, `MailerService`).
- **A2-B.7** — `notificar()` escribe en `email_log`.
- **PORTAL-PAGO-RATE-LIMIT / PORTAL-TICKET-RATE-LIMIT** — rate limiting.
- **PORTAL-COMPROBANTE-PURGA** — comprobantes no se purgan.
- **PORTAL-MFA** — sin segundo factor para clientes.
- **PORTAL-TICKET-ADJUNTOS** — sin adjuntos en tickets/respuestas del portal.
- **PORTAL-TICKET-REABRIR** — `Cerrado` terminal. Reabrir requiere estado `Resuelto`.
- **PORTAL-TICKET-ASIGNAR-TECNICO** — sin asignar técnico desde el portal.
- **PORTAL-TICKET-URGENCIA** — sin marcar urgencia (impacto × urgencia, ITIL).
- **NOTIF-ROL-GRANULARIDAD** — destinatarios staff = solo rol 1 activo.
- **NOTIF-ESTADO-LEGACY** — enum `estado` convive con `leida_en`.
- **NOTIF-IDEMPOTENCIA** — sin idempotencia.
- **NOTIF-TICKET-ASIGNADO** — no se notifica al técnico asignado.
- **BILLING-DIA-FACTURACION-INERTE / BILLING-GRACIA-MORA-INERTE / BILLING-CORTE-AUTOMATICO-INERTE** — 4 parámetros de `configuracion_empresa` sin usar.
- **118i** — LOGIN/LOGOUT/LOGIN_FALLIDO no se registran en `auditoria_logs`.

---

## Decisiones de proceso vigentes

Reglas operativas del repo → `AGENTS.md`. Aquí solo las decisiones de proceso específicas:

- **Orden del proyecto:** Fase 4 ✅ → tanda fixes 2026-10-31 ✅ → A2-B.1 ✅ → A2-B.3 ✅ → A2-B.4 ✅ → A2-B.5 ✅ → **Fase 5 ✅ (086c, 096a, 096e, 096b, 096c, 096d, 096f, 086d)** → **Fase 2 (F2.0a + F2.1–F2.38)** → A2-B (resto) → Fase 7 → Fase 3.
- **Siempre archivo completo, nunca fragmento.** Endurecida 2026-10-27 y 2026-10-31.
- **Replicar el fix en todos los módulos afectados en el mismo turno.**
- **No diferir un bug expuesto por el fix actual.**
- **Solo `MEMORY.md` se reduce.** Los docs de disco duro solo se amplían o modifican quirúrgicamente.
- **Verificación de aquí en adelante:** 1 comando de sintaxis (`php -l` + `node --check` todos juntos) + pruebas browser. Sin pasos CLI intermedios.
- **Al cerrar cada spec:** actualizar `MEMORY.md`. Al cerrar un punto completo (como 086d): también `ROADMAP.md`. Los demás docs (`ARQUITECTURA.md`, `BD.md`) al cerrar la fase completa.
- **`Router.showPage` cierra modales + resetea scroll.**
- **Portal: endpoints separados de los admin** (BOLA/IDOR).
- **Portal: `cliente_id` de la sesión, nunca del body.**
- **Portal: SVG prohibido en comprobantes** (XSS).
- **Portal: `must_change_password` enforced en `Auth::requireClientAuth` sin bypass.**
- **Portal: `window.__appContext = 'portal'`** seteado en `js/portal/main.js` ANTES de cualquier fetch. `ApiClient` elige `PortalSession` vs `Session` según este flag. Sin esto, el portal manda el token del admin en cada request → 401. Bug encontrado y corregido en 096a (2026-11-01). **Cualquier HTML nuevo del portal debe setear `__appContext` antes de cargar `ApiClient` o antes del primer fetch.**
- **Portal: `PortalSession` y `Session` coexisten** (claves `redytelca_portal_session` y `redytelca_session`). Admin + portal abiertos a la vez en el mismo navegador. Fundamento: Bagisto multi-guard, Yii2 multi-tenant.
- **Portal: `PortalRouter` es fork de `Router`** (SECTION_MAP propio: Servicios, Facturas, Pagos, Tickets). Misma regla de "cerrar modales + reset scroll".
- **Portal: `PortalServicioModel` usa `per_page=100`** y pagina client-side (`PAGE_SIZE=10`). Los servicios por cliente son pocos.
- **Portal: `PortalFacturaModel` usa paginación server-side** (`per_page=20`), porque el número de facturas puede crecer. Filtros `estado` + `periodo` vía query params.
- **Portal: `PortalPagoModel` pagina server-side** (`per_page=20`). Filtros `estado` vía query params.
- **Portal: `PortalTicketModel` pagina server-side** (`per_page=20`). Filtros `estado` + `prioridad` vía query params. **Búsqueda por texto NO implementada** (client-side sobre página actual confunde; server-side requeriría cambio de backend).
- **Portal: detalle de factura, pago y ticket lee nombre y cédula de `PortalSession.get().cliente`.** A2-B.4 decidió NO devolver `nombres`/`apellidos`/`cedula` en endpoints del portal (el cliente ya se conoce a sí mismo). Fallback a `factura.nombres`/`pago.nombres`/`ticket.nombres` por si el backend los añade en el futuro.
- **Portal: descarga de comprobante vía `fetch` con `X-Session-Token` → blob → download.** El endpoint `api_portal_comprobante.php` requiere auth.
- **Portal: `tipo_autor` forzado en el service, nunca desde el body.**
- **Portal tickets: `Cerrado` terminal para el cliente.** Zendesk: *"Closed is forever. There's no reopening a Closed ticket"*. El botón "Enviar respuesta" y el textarea se deshabilitan. Mensaje: *"Crea un ticket nuevo para continuar la conversación."*
- **Portal tickets: `usuario_creador_id=NULL`.**
- **Portal PDF: `window.print()` con popup HTML print-friendly, sin librerías.** El PDF es informativo, no fiscal. Footer con aviso explícito de SENIAT. Deuda `FACTURA-FISCAL-SENIAT` a Fase 7.
- **Portal notificaciones (086d): `startPolling()` hace `_pollOnce()` inicial.** Sin el fetch inicial, el badge queda vacío hasta el primer tick (30s). Se arranca desde `initApp()` post-login (staff) o `window.__onPortalLoginSuccess` post-login/restore (portal). **NO se arranca en `DOMContentLoaded` sin sesión** — el fetch inicial daría 401 y el badge quedaría vacío hasta el primer tick válido.
- **Portal notificaciones (086d): `PortalNotificacionController.init()` debe llamarse en `DOMContentLoaded`.** Sin esto, el listener del click en la campana nunca se ata y el dropdown no abre. El badge sí aparece porque el polling lo arranca `main.js`, pero la interacción con la campana queda muda. Bug encontrado en verificación de 086d (2026-11-02). Aplica también al controller del admin (086c).
- **Notificaciones: `leida_en` es la fuente de verdad.**
- **Notificaciones: `notificar()` dentro de la transacción del cambio principal.**
- **Notificaciones: destinatarios staff = admins activos (rol 1), excluyendo al generador.**
- **Notificaciones: badge numérico, oculto si `no_leidas === 0`.** `PortalNotificacionView.renderBadge(n)` con `n > 0` muestra `n` o `99+`; con `n = 0` oculta el badge (`display: none`). Patrón estándar SaaS (Slack, Gmail, Salesforce).
- **`F2.0a` al inicio de Fase 2, después de Fase 5.**
- **Rol "Técnico" (id 23) existe en BD desde 2026-10-31.** Falta asignar permisos + usuarios (F2.35).
- **Roles en modal dedicado, NO en Editar usuario** (4.2-hotfix1).
- **Matriz de permisos en tabla grid, no cards** (4.3).
- **El último admin activo no puede perder el rol Administrador** (4.2).
- **`app.js` eliminado el 2026-10-30.** No se reintroduce.

---

## Aprendizajes recientes

- **`ApiClient.js` no debe asumir `window.Session`.** Debe elegir según `window.__appContext` (`'portal'` → `PortalSession`, default → `Session`). Bug encontrado en 096a (2026-11-01): el portal mandaba el token del admin en cada request, y el backend del portal respondía 401 "No autenticado". Fix: setear `__appContext` en `js/portal/main.js` ANTES de cualquier fetch, y adaptar `ApiClient` para consultar el flag.
- **El orden de carga importa.** `js/portal/main.js` se ejecuta en `DOMContentLoaded`, pero el flag `__appContext` debe existir en el scope global antes de que cualquier módulo llame `ApiClient`. Se setea al inicio del IIFE de `main.js` (top-level, no dentro del `DOMContentLoaded`).
- **`portal_cliente.php` con `router.php` no funciona en Apache.** `router.php` solo aplica con `php -S`. En Apache, `/portal` da 404. Se resuelve con `.htaccess` (RewriteRule) o usando `http://localhost/portal_cliente.php` directamente. Deuda `PORTAL-URL-REWRITE` (Fase 2).
- **La auto-creación de credenciales del portal solo aplica a clientes NUEVOS.** Los clientes del seed (creados antes de A2-B.3, 2026-10-31) no tienen credencial y no pueden entrar al portal. Para verificar módulos del portal con datos del seed, hay que crear credencial manualmente (scripts `seed_portal.php` y `seed_segundo_cliente.php`, ya borrados). Lección de 096e (2026-11-01).
- **La UI del admin no muestra el `password_temporal` al crear cliente.** `ClienteController.save()` descarta `res.credencial.password_temporal`. Gap `PORTAL-CREDENCIAL-PASSWORD-OCULTA` (Fase 2). Mientras tanto, el `password_temporal` se ve en DevTools → Network → `registrar_cliente.php` → Response.
- **La UI del admin no tiene botón de "Resetear credencial del portal".** `api_cliente_credenciales.php` existe (A2-B.3) pero sin UI. Gap `PORTAL-CREDENCIAL-UI` (Fase 2).
- **El backend del portal NO devuelve `nombres`/`apellidos`/`cedula` en facturas ni pagos.** A2-B.4 decidió no repetir PII (el cliente ya se conoce a sí mismo). Los Views de detalle leen estos datos de `PortalSession.get().cliente` con fallback a `factura.nombres`/`pago.nombres`. Lección de 096b (2026-11-01). **Aplica a 096c (pagos) y 096d (tickets).**
- **`startPolling()` debe hacer `_pollOnce()` inicial, no solo `setInterval(30s)`.** Sin el fetch inicial, el badge queda vacío hasta el primer tick. Además, `init()` no debe llamar `startPolling()` sin sesión: se arranca desde `initApp()` post-login (admin) o `window.__onPortalLoginSuccess` post-login/restore (portal).
- **`NotificacionController.init()` (admin) y `PortalNotificacionController.init()` (portal) deben llamarse en `DOMContentLoaded`.** Sin esto, el listener del click en la campana nunca se ata y el dropdown no abre. El badge sí aparece porque el polling lo arranca `main.js`, pero la interacción con la campana queda muda. Bug encontrado en 086d-fix (2026-11-02). El controller del admin (086c) ya lo hacía.
- **Endpoints separados del portal no se reutilizan con los admin.** BOLA/IDOR.
- **Discriminador `tipo_autor` forzado en el service.** Zendesk `public`, Salesforce `IsPublished`, ServiceNow `work_notes` vs `comments`.
- **`Cerrado` es terminal para el cliente del portal.** Sonar "closed ticket cannot be reopened". Zendesk: *"Closed is forever"*. El botón "Responder" se deshabilita en la UI. El backend lanza `DomainException` 422. Doble defensa.
- **Estado del ticket NO cambia cuando el cliente responde.** Zendesk/Jira SM/Salesforce/ServiceNow/Splynx.
- **No crear tareas derivadas.** Jira SM las convierte en nota interna.
- **`usuario_creador_id=NULL` en tickets del portal.** El creador es el cliente (`cliente_id`).
- **URLs de notificación cliente vs staff.** `/portal/*` vs `/*`.
- **Notificaciones: `leida_en` (timestamp) > enum `estado`.**
- **Notificaciones: Observer síncrono en la transacción.** Transactional Outbox.
- **Portal auth: CSPRNG, no hardcoded.** OWASP ASVS 2.5.1 + 2.3.1.
- **Portal auth: `must_change_password` en el middleware.** Laravel `EnsurePasswordIsNotExpired`.
- **Portal: upload con `finfo_file()` + CSPRNG + `.htaccess`.**
- **Portal PDF: `window.print()` con popup HTML print-friendly, sin librerías.** El usuario elige "Guardar como PDF" en el diálogo nativo. Splynx usa el mismo approach para facturas no fiscales. Stripe requiere `wkhtmltopdf` (prohibido por §1). Aviso de "no fiscal" obligatorio: SENIAT exige número de control y RIF que el esquema no tiene. Lección de 096f (2026-11-02).
- **`formatDual` reusado en tabla, detalle y PDF.** Un solo helper, tres contextos. Si el formato cambia, se cambia en un solo lugar. Lección de 096f (2026-11-02).
- **`CREATE TRIGGER ... BEGIN ...; END;` en un `.sql` requiere `DELIMITER`.** Lección §2.41.
- **Tests que llaman `Auth::setAuditContext` deben resetear `@app_credencial_id` en teardown.** Lección §2.42.
- **Tests que crean facturas/pagos: `notificaciones` antes que `clientes`.** Lección §2.43.
- **`FacturaService::crear` y `PagoService::actualizar` notifican solo al cliente.** Lección §2.44.
- **`@app_credencial_id` reset antes de DELETE en `clientes_credenciales`.** Lección §2.45.

---

## Referencias rápidas

| Qué | Dónde |
|---|---|
| Specs y su estado | `docs/ROADMAP.md` |
| Detalle histórico de Fase 1 | `docs/HISTORICO_FASE1.md` (⚠️ parcial) |
| Detalle A2-B.1/3/4/5 | `docs/ROADMAP.md` §Histórico + `docs/ARQUITECTURA.md` §Notificaciones/§Portal |
| Plan del bloque activo | `PLAN DEL BLOQUE ACTIVO` (externo) |
| Cómo está construido | `docs/ARQUITECTURA.md` |
| Esquema de BD | `bd_redytelca.sql` + `bd_redytelca_schema.sql` + `docs/BD.md` |
| Hallazgos canónicos | `docs/AUDITORIA_2026.md` |
| Estándares + defensa | `docs/REFERENCIA.md` |
| Lecciones de verificación | `docs/VERIFICACION.md` |
| Reglas del repo | `AGENTS.md` |
| Reglas del proceso | `docs/METODOLOGIA_DESARROLLO_IA.md` |
| Gobernanza documental | `docs/GOBERNANZA.md` |
| Decisiones arquitectónicas | `docs/ADR/001`–`020` |
| Modelo de moneda dual | `docs/REFERENCIA.md` §6 |
| Ciclo estados contratos/servicios | `docs/BD.md` §contratos + `AGENTS.md` §Trampas |
| Notificaciones backend | `src/services/NotificacionService.php` + `ARQUITECTURA.md` §Notificaciones |
| Notificaciones UI staff (086c) | `public/js/models/NotificacionModel.js` + `views/NotificacionView.js` + `controllers/NotificacionController.js` |
| Portal auth UI (096a) | `public/portal_cliente.php` + `public/portal.css` + `public/js/portal/core/{Session,Router}.js` + `js/portal/controllers/AuthController.js` + `js/portal/views/AuthView.js` + `js/portal/main.js` |
| Portal servicios UI (096e) | `public/js/portal/models/ServicioModel.js` + `views/ServicioView.js` + `controllers/ServicioController.js`. Página `portal-servicios` en `portal_cliente.php`. |
| Portal facturas UI (096b) | `public/js/portal/models/FacturaModel.js` + `views/FacturaView.js` + `controllers/FacturaController.js`. Página `portal-facturas` + modal de detalle en `portal_cliente.php`. `FacturaView` lee nombre/cédula de `PortalSession.get().cliente`. |
| Portal pagos UI (096c) | `public/js/portal/models/MetodoPagoModel.js` + `models/PagoModel.js` + `views/PagoView.js` + `controllers/PagoController.js`. Página `portal-pagos` + 2 modales. `PortalPagoModel.downloadComprobante()` hace `fetch` + blob. |
| Portal tickets UI (096d) | `public/js/portal/models/TicketModel.js` + `views/TicketView.js` + `controllers/TicketController.js`. Página `portal-tickets` + 2 modales (detalle con hilo, form de creación). Ticket `Cerrado` deshabilita "Responder". Backend pre-req: `api_portal_categorias_ticket.php` + `CategoriaTicketService::listarActivas()` + `CategoriaTicketController::indexPortal()`. Búsqueda por texto NO implementada (paginación server-side). |
| Portal PDF UI (096f) | `public/js/portal/views/FacturaPdfView.js`. Genera popup print-friendly con `window.print()`. Sin librerías (§1). PDF informativo, no fiscal. `formatDual` reusado de `FacturaView`. Botón "Imprimir / PDF" en `FacturaView.renderDetailModal`. |
| **Portal notif cliente UI (086d)** | **`public/js/portal/models/NotificacionModel.js` + `views/NotificacionView.js` + `controllers/NotificacionController.js`. Campana en topbar + dropdown + modal de detalle. `portal.css` §`portal-notif-*`. `main.js` registra `window.__onPortalLoginSuccess` → `PortalNotificacionController.startPolling()`. `_clearSession()` detiene el polling y limpia el modelo. `init()` en `DOMContentLoaded` (086d-fix).** |
| Portal auth backend | `src/services/PortalAuthService.php` + `ClienteCredencialService.php` |
| Portal facturas/pagos/servicios backend | `src/services/PortalFacturaService.php` + `PortalPagoService.php` + `PortalServicioService.php` |
| Portal tickets backend | `src/services/PortalTicketService.php` + `TicketRespuestaService::crearRespuestaCliente` |
| Portal categorías ticket backend | `public/api_portal_categorias_ticket.php` + `CategoriaTicketService::listarActivas()` + `CategoriaTicketController::indexPortal()` |
| Portal notificaciones backend | `src/services/NotificacionService.php` + `api_portal_notificaciones.php` + `NotificacionPortalController` |

---

## Cadena de operaciones (industria)

**Confirmado 2026-10-27 (Splynx, Oracle, Entel).**

Cliente → Servicio (`pendiente`) → Contrato (`vigente`) → Pedido de instalación (`pendiente` → `asignado` → `en_curso` → `completado`) → Servicio (`activo`) → Primera factura.

- El **servicio va primero**, en `pendiente`.
- El **contrato va segundo**, antes de la instalación.
- El **pedido de instalación va tercero**.
- El **servicio pasa a `activo`** al completar el pedido. Hoy es manual; Fase 7 automatiza.

---

## Infraestructura

- **URL de la plataforma (para comandos de pruebas):** `http://localhost/` — Apache, puerto 80, DocumentRoot apuntando a `public/`.
- **Portal:** `http://localhost/portal_cliente.php` (Apache sin rewrite aún). Deuda `PORTAL-URL-REWRITE` (Fase 2): añadir `.htaccess` con RewriteRule en `public/`.
- **Fallback sin vhost:** `php -S localhost:8000 -t public public/router.php` desde la raíz. `DOC-12` cerrado 2026-10-31.
- **Tests:** `php src/tests/run.php`. 9/9 + `verify_a2b1.php` 14/14 + `verify_a2b3.php` 37/37 + `verify_a2b5.php` 30/30 (2026-11-01). **`verify_a2b4.php` NO existe** (deuda `A2-B.4-TEST`, pendiente de crear en Fase 2). 086d verificado en browser (no requiere archivo de test formal; es UI).
- **Migraciones SQL aplicadas:**
  - `BD-B3.11-01` (2026-10-29): `configuracion_empresa.logo_path_sidebar`.
  - **D3 (2026-11-01):** `UPDATE paginas SET modulo_id = 3 WHERE url_pagina = 'notificaciones'` + migración de pivotes (`rol_modulo_pagina`, `rol_modulo_permiso`) para `pagina_id=19` a `modulo_id=3`. Verificado en BD viva 2026-11-01. Backup: `C:\backups\bd_redytelca_pre-fase5-notif-modulo3.sql`.
  - **Regeneración `bd_redytelca_schema.sql` (2026-11-01):** sincronizado con la BD viva post-D3. 44 tablas, 145 triggers, 4 vistas, seed de 8 tablas de catálogo, sin PII. Backup del viejo: `bd_redytelca_schema.sql.bak-2026-11-01`.
- **Backup BD:** `C:\backups\` + `C:\backups\redytelca-migraciones\`.
- **Backup del repo:** backup manual + renombrar a `.bak`. **Sin git.**
- **Tokens de prueba:** `C:\backups\token-admin.txt`, `C:\backups\token-operador.txt`. Password admin por defecto: `admin123`.
- **Credenciales de portal creadas para verificación de Fase 5:**
  - `marcelo@gmail.com` (cliente_id=1): password `PortalTest2026!`, `must_change_password=0`. Servicios del cliente 1 (puede ser 0).
  - Segundo cliente (creado por `seed_segundo_cliente.php`, ya borrado): asignó credencial a un cliente con servicios. Username = su `correo`. Password `PortalTest2026!`. `must_change_password=0`. Anotar el `cliente_id`/`username` si se necesita para regresión.
  - `reset_credenciales.php` (ya borrado) reseteó todas las contraseñas a `PortalTest2026!` con `must_change_password=0`. Sirve para verificar 096c con un cliente que tenga facturas.
- **Entorno:** XAMPP (Apache 2.4.58 + PHP 8.2.12 + MariaDB 10.4.32).
- **Skills disponibles:** `sdd`, `redytelca-bd`, `redytelca-auth`, `redytelca-auditoria`.