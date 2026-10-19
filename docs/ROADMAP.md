# Roadmap — REDYTELCA

Única fuente del estado de specs. **Cada spec aparece una sola vez, con una línea de qué hace.**
Para actualizar: cambia el estado en su fila. Nada más.
Auditoría de hallazgos: `AUDITORIA_2026.md`. Estado operativo: `MEMORY.md`.

**Estados:** `⏳ pendiente` · `🔄 en progreso` · `✅ cerrado` · `💤 diferido`.
`bloqueado` NO es un estado válido.

**Todas las fases se ejecutan en orden secuencial (1 → 7).** El orden importa; la etiqueta "obligatoria" sobra.

**Huecos de numeración:** 104, 107, 111 nunca existieron como specs. No buscar.

---

## Estado actual

- **Fase activa:** 1 — MVC
- **Siguiente:** 140 (frontend modular)
- **Cerradas:** 53 · **En progreso:** 0 · **Pendientes:** ~100 filas (~65 padres)

---

## Fase 1 — MVC

Cierra el Bloque 29. Reactiva 13 endpoints caídos. Al terminar: 20 controllers, 36 thin files, `conexion.php` y `app.js` eliminados, 0 endpoints caídos, estructura `public/` + `src/` (139e).

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 134 | core/ | Kernel MVC: Bootstrap, Config, Database, Response, Validator, Auth, Exceptions, bases. Autoloading PSR-4. | ✅ |
| 135 | models/ | 35 entidades de dominio (atributos + getters/setters, sin SQL). | ✅ |
| 136 | repositories/ | 35 repositorios con todo el SQL (PDO). Heredan de `BaseRepository`. | ✅ |
| 137 | services/ | 18 servicios con lógica de negocio y transacciones. | ✅ |
| 138 | validators/ | 16 validadores de entrada (validateCreate + validateUpdate). | ✅ |
| 139 (padre) | MVC controllers | Agrupa 139a–d. Cierra cuando 139d cierre. | ✅ |
| 139a | controllers auth | Auth, Perfil, Usuario, Cliente + 14 thin endpoints. | ✅ |
| 139b | controllers finanzas | Servicio, Contrato, Plan, Factura, Pago, MetodoPago + 8 thin. | ✅ |
| 139c | controllers infra/soporte | Nodo, Olt, Nap, Equipo, Ticket, Tarea, Pedido + 7 thin (6 reescritos + `api_pedidos` nuevo). Migración `2026-10-18-139c-pedidos-rbac.sql`. | ✅ |
| 139d | controllers config | Rol, Permiso, Empresa + 7 thin + `branding.css.php` reescrito + migrar `tests/audit_context.test.php` + borrar `conexion.php`. 5 tandas, 59/59 verde. Cierra Fase 1 MVC. | ✅ |
| 139e | estructura despliegue | Mover `core/`, `controllers/`, `services/`, `repositories/`, `models/`, `validators/`, `tests/` a `src/`. Mover thin files + `index.html` + `app.js` + `styles.css` + `Logos/` + `uploads/` a `public/`. Actualizar `require`, `Bootstrap.php`, `router.php`, `.htaccess` fallback. Cierra la exposición del código fuente al webroot. ADR 018. | ✅ |
| 140 | frontend js/ | Reescribir `app.js` en `js/core|models|views|controllers/`. Adaptar `index.html`. Borrar `app.js`. | ⏳ |

**Reglas heredadas de 139b:**
- `unset($input['id_X'])` en cada `update()` (causa de BUG-6.3/BUG-6.4).
- Tests de DELETE solo con dummies creados en el turno, nunca con seed.
- Verificar cada endpoint contra el esquema post-B28 antes de fusionar.

**Reglas heredadas de 139c:**
- Migración SQL primero, verificar con triggers antes de escribir controllers.
- Los 5 deletes bloqueados por dependencias se testean contra el seed sin borrarlos.

**Reglas heredadas de 139d Tanda C:**
- Verificar que los archivos de AI Studio conservan todos los métodos del dump. En Tanda C, AI Studio borró `findModulosPermitidosByRoles`, revirtió `guardarMatriz` a SQL inline, cambió `insertIgnore` a array. Rechazado.
- Tests de escritura RBAC con rol dummy (`test_139d_rol_perm`), nunca con seed. Cascada limpia `rol_modulo*`.
- Body JSON en CLI de Windows viaja por base64.

**Reglas heredadas de 139e:**
- Se ejecuta entre 139d y 140, con ADR 018.
- `git tag pre-139e` + backup BD antes de mover archivos.
- Criterio de cierre: `grep -r "conexion.php"` → 0, `core/Config.php` por URL → 403/404, E2E login + api_* + branding + portal → 200.
- `Logos/convert_logo.php` eliminado (script de un solo uso, logo ya convertido).
- `public/router.php` requiere `return false;` en el fallback para servir thin files con `php -S`.
- Comando de arranque dev: `php -S localhost:8000 -t public public/router.php`.

---

## Fase 2 — Deudas heredadas de MVC

| Spec / Deuda | Qué hace | Estado |
|---|---|---|
| BUG-6.1 + F08 | Al cambiar permisos, la UI sigue mostrando datos viejos hasta logout+login. Limpiar `window.*Cache` al cambiar rol. | ⏳ |
| Overlay-F5 | Al recargar con sesión activa, reaparece `#login-overlay`. `restoreSession()` debe ocultarlo. | ⏳ |
| 139b-1 | 4 `index()` sin JOINs (Servicio, Contrato, Factura, Pago) → frontend los resuelve con APIs cruzadas. | ⏳ |
| 139b-2 | `ServicioController::update` no persiste `direccion_servicio`. Añadir `actualizarDireccion()`. | ⏳ |
| 139b-3 | Frontend viejo manda `id_servicio`, backend espera `servicio_id`. Contrato roto. | ⏳ |
| 139c-1 | 7 `index()` de infra/soporte sin JOINs. Igual que 139b-1. | ⏳ |
| 139c-2 | Sidebar muestra "Pedidos de campo" sin `showPage('pedidos')`. | ⏳ |
| 139c-3 | `EquipoController::index` sin filtro devuelve solo stock. | ⏳ |
| 139c-4 | Operador sin `leer` en tickets/tareas. Seed RBAC incompleto. | ⏳ |
| 139c-5 | `PedidoService::asignarTecnico` no expuesto. | ⏳ |
| 139d-1 | `roles_eliminar.php` mantiene POST por compatibilidad. Se unifica con DELETE en 140. | ⏳ |
| 139d-2 | `EmpresaController::store` sube archivo antes de validar campos; `@unlink` en catch. Re-verificar end-to-end en 140 si la UI cambia. | ⏳ |
| 139d-3 | 5 métodos con SQL crudo en `AuthService` y `UsuarioService` (`cargarRolesUsuario`, `cargarModulosPermitidos`, `asignarRoles`, `listarRoles`, `contarOtrosAdminsActivos`, `desactivar`). Cierra 136f-bis. | ⏳ |
| 139d-B-1 | `Auth::jsonError()` sin `JSON_UNESCAPED_UNICODE`; acentos salen como `\u00f1`. | ⏳ |
| 060 | Limpiar documentación obsoleta de `docs/` (archivar, corregir referencias). | ⏳ |
| DOC-04 | `ARQUITECTURA.md` y `BD.md` dicen `utf8mb4_general_ci`; la BD es `utf8mb4_unicode_ci`. | ⏳ |
| DOC-05 | `ARQUITECTURA.md` y `BD.md` no listan las 17 tablas nuevas de BD-COMPLETA. | ⏳ |
| DOC-08 | ADR 013 dice que `notificaciones_staff` no tendría triggers; P4 sí los creó. | ⏳ |
| DOC-09 | `bd_redytelca_schema.sql` desactualizado (pre-P14/P15, dice 48 tablas; la BD tiene 44). Regenerar. | ⏳ |
| EST-02 | `sla_definiciones` solo tiene 3 de 8 combinaciones (falta Instalación y Baja). | ⏳ |
| EST-03 | `email_plantillas` solo tiene 6 de ~9 eventos (falta ticket_creado, pago_pendiente, pago_rechazado). | ⏳ |

**Nota:** DOC-07, DOC-10, DOC-11, DOC-12 y DOC-13 se **auto-resolvieron al reescribir este archivo el 2026-10-18**. No aparecen como deudas porque el ROADMAP ya no las viola.

---

## Fase 3 — UI/UX bonita

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 078 | Design system | Tokens de diseño, componentes base, guía visual. Requisito de las demás. Absorbe 077. | ⏳ |
| 079 | Navegación agrupada | Sidebar por secciones (Análisis, Operación, Infraestructura, Finanzas, Configuración) + colapsables. | ⏳ |
| 080 | Dashboard accionable | KPIs con tendencia y drill-down, reemplaza los 9 estáticos actuales. | ⏳ |
| 082 | Reportes con periodo | Filtros por rango de fechas, distinto del dashboard. | ⏳ |
| 083 | Vista 360° cliente | Pestañas (datos, servicios, facturas, tickets, pagos) en una sola pantalla. Absorbe 095. | ⏳ |
| 085 | Breadcrumbs + búsqueda global | Breadcrumbs de navegación + buscador global en header. | ⏳ |

**Dependencias:** 078 es requisito de 079–085. Todo el bloque depende de 140. Modelo de permisos por página aprobado (Salesforce, ServiceNow, AWS QuickSight, SAP, Azure, Odoo). Vive en `js/controllers/roles.js` con `PERMISOS_POR_PAGINA`.

---

## Fase 4 — Botón Roles + permisos en grilla

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 084 | mejoras-tablas (permisos en grilla) | Reemplazar las cards de permisos por una grilla tabla (filas=páginas, columnas=operaciones). | ⏳ |
| — | Botón Roles en Usuario | Botón en la ficha de usuario que abre el editor de roles del usuario. | ⏳ |
| — | Cierra MAP-01, EST-01, UX-01 | Ajustes de UI pendientes: página `mapa` sin endpoint propio, páginas sin CRUD, `<select>` de contrato con opciones inválidas. | ⏳ |

---

## Fase 5 — Portal + Notificaciones

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 096a | Portal: auth | Login de cliente contra `clientes_credenciales`. Sesión `tipo_usuario='cliente'`. `requireClientAuth`. | ⏳ |
| 096b | Portal: facturas | Ver facturas propias + estado + saldo. `id_cliente` siempre de sesión, nunca del body. | ⏳ |
| 096c | Portal: pagos | Subir comprobante, registrar pago con `origen='portal_cliente'`. | ⏳ |
| 096d | Portal: tickets | Abrir ticket, ver historial, responder. | ⏳ |
| 096e | Portal: servicios | Ver servicios y contratos propios. | ⏳ |
| 096f | Portal: PDF | Descargar factura en PDF vía `window.print()`. | ⏳ |
| 086a | Notif: esquema | Servicio transversal `notificar()`. Migración de `notificaciones` + `notificaciones_staff`. | ⏳ |
| 086b | Notif: eventos | Disparar notificación desde tickets, pagos, facturas, tareas. | ⏳ |
| 086c | Notif: UI staff | Campana en header del admin con badge y dropdown. | ⏳ |
| 086d | Notif: UI cliente | Campana en portal del cliente. | ⏳ |
| 086e | api-tickets-autoria-cliente | Conectar tickets del portal con el panel admin. | ⏳ |
| 086f | api-tareas-derivadas | Tareas creadas desde tickets del cliente. | ⏳ |
| 086g | api-pagos-origen-portal | Pagos del portal visibles en admin. | ⏳ |
| 086h | api-facturas-notificar-emision | Notificar al cliente cuando se emite una factura. | ⏳ |

**Fundamento:** Splynx, Sonar, SmartOLT, ISPsystem. ADRs 010 y 011.

---

## Fase 6 — Título del proyecto

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| — | Título definitivo | Documental. | ⏳ |

---

## Fase 7 — Resto (secuencial)

Continúa el orden después de Fase 6. Sin pre-categorización. Se ejecutan en el orden en que aparecen.

### BD residual

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 006 | limpiar columnas muertas | Eliminar columnas que no se usan (`facturas.impuesto_aplicado`, etc.). Verificar contra dump. | ⏳ |
| 014 | revertir pago completo | Al revertir un pago validado, la factura debe volver a estado correcto. Verificar contra dump. | ⏳ |

### Backend residual

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 027 | quitar id_plan hardcoded | `registrar_cliente.php` usa `id_plan=1` hardcoded. Parametrizar. | ⏳ |
| 028 | fortaleza passwords | B14: mínimo ≥8 chars → NIST pide ≥15. B15: password temporal hardcodeada. (parcial: B14 a 8 chars, B15 pendiente) | ⏳ |
| 029 | eliminar cliente con contratos | `cliente_eliminar.php` no verifica FK de contratos antes del DELETE. | ⏳ |
| 031 | preservar trazabilidad pagos | DELETE de pagos `rechazado` borra historial. Bloquear o marcar como anulado. | ⏳ |
| 074 | validar enums tickets/tareas | `api_tickets` y `api_tareas` aceptan cualquier string en estado/prioridad. | ⏳ |

### Frontend residual

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 035 | token fuera localStorage | Mover token a cookie HttpOnly o equivalente. (parcial: F02 resuelto, F01/F04 pendientes) | ⏳ |
| 036 | sacar onclick inline | Reemplazar `onclick="..."` por addEventListener. | ⏳ |
| 038 | optimizar charts | Reutilizar instancias de Chart.js en lugar de destruir y recrear. | ⏳ |
| 040 | modales propios | Reemplazar `alert()`/`confirm()` por modales custom. | ⏳ |
| 042 | limpiar selectedIds | `window.selectedIds` no se limpia al cambiar de página. | ⏳ |

### Seguridad

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 044 | mover scripts fuera webroot | `Logos/convert_logo.php` y otros scripts no deben ser accesibles por URL. | ⏳ |
| 045 | validar uploads imágenes | Validar MIME de imágenes subidas en `api_empresa.php`. | ⏳ |
| 046 | rate limiting login | Limitar intentos fallidos por IP además de por cuenta. | ⏳ |
| 047 | expiración sesión | Renovación deslizante de 30 días → evaluar reducir a 7. | ⏳ |
| 048 | headers de seguridad | `X-Frame-Options`, `X-Content-Type-Options`, CSP básico. | ⏳ |
| 049 | config entorno | Mover credenciales BD a `.env`. Fijar timezone UTC. Absorbe 088. | ⏳ |

### Arquitectura y docs

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 052 | eliminar dead code | Eliminar archivos no usados. | ⏳ |
| 089 | schema SQL versionable | Regenerar `bd_redytelca_schema.sql` con el esquema actual. | ⏳ |

### Testing

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 053 | tests completos | Tests de endpoints + frontend. Absorbe 090 y 091. (parcial: harness con 4 smoke + 5 auditoría; endpoints y frontend pendientes) | ⏳ |
| 054 | CI/CD | Pipeline de build, test, deploy. Absorbe 092. | ⏳ |
| 055 | linting | PHP_CodeSniffer + ESLint. | ⏳ |

### Infraestructura

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 056 | HTTPS enforcement | Forzar HTTPS en producción. | ⏳ |
| 057 | health check | Endpoint `/health` con chequeo de BD + servicios. Absorbe parte de 093. | ⏳ |
| 058 | backups automatizados | Script de backup diario + retención. Absorbe parte de 093. | ⏳ |
| 059 | observabilidad | Logs estructurados + métricas + trazas. | ⏳ |

### Contexto ISP

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 062 | idempotencia pagos | Evitar doble validación de un pago con el mismo comprobante. | ⏳ |
| 063 | timeouts PDO/HTTP | Timeouts explícitos en PDO y llamadas externas. | ⏳ |
| 064 | plan de migración | Procedimiento documentado para migraciones BD en producción. | ⏳ |
| 067 | inventario HW | Fuera de alcance (ver ADR 012). | 💤 |
| 068 | equipos flujo completo | Equipo → NAP → Servicio → Cliente con trazabilidad completa. | ⏳ |

### Performance

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 069 | cache sesiones | Cache de sesiones en Redis/Memcached (evaluar si necesario). | ⏳ |
| 070 | paginación server-side | Paginación en SQL (LIMIT/OFFSET) en lugar de client-side. | ⏳ |
| 071 | minificar assets | Minificar CSS/JS en build. | ⏳ |

### Módulos de negocio

| Spec | Nombre | Qué hace | Estado |
|---|---|---|---|
| 098 | SLA/QoS tracking | Fuera de alcance actual (ver ADR 012). | 💤 |
| 114a | IPAM endpoints | CRUD de pools y asignaciones IP. | ⏳ |
| 114b | IPAM UI | Interfaz de gestión de IPs. | ⏳ |
| 114c | IPAM migrar servicios | Mover `servicios.ip_asignada` a `ip_asignaciones`. | ⏳ |
| 115a | Pedidos CRUD | CRUD de pedidos (instalación, reparación, retiro, traslado). | ⏳ |
| 115b | Pedidos asignación técnico | Asignar pedido a técnico + estados. | ⏳ |
| 115c | Pedidos materiales | Materiales usados en cada pedido. | ⏳ |
| 115d | Pedidos UI | Interfaz de pedidos. Absorbe 081. | ⏳ |
| 116a | Email SMTP | Configurar envío de correos vía SMTP. | ⏳ |
| 116b | Email plantillas | CRUD de plantillas de correo. | ⏳ |
| 116c | Email verificación/reset | Flujos de verificación de email y recuperación de contraseña. | ⏳ |
| 116d | Email facturas/notif | Notificaciones por email de facturas y otros eventos. | ⏳ |
| 117a | Autoservicio cambio plan | Cliente puede cambiar de plan desde el portal. | ⏳ |
| 117b | Autoservicio datos | Cliente puede actualizar sus datos. | ⏳ |
| 117c | Autoservicio password | Cliente puede cambiar su contraseña. | ⏳ |
| 118a | Categorías tickets | CRUD de categorías de tickets. | ⏳ |
| 118b | Adjuntos tickets | Permitir adjuntar archivos a tickets. | ⏳ |
| 118c | SLA tickets | Tiempos de respuesta/resolución por categoría y prioridad. | ⏳ |
| 118d | Notas crédito/débito | Emitir y aplicar notas de crédito/débito. | ⏳ |
| 118e | Recargo mora automático | Calcular y aplicar recargo por mora. | ⏳ |
| 118f | Corte/suspensión lote | Suspender servicios en lote por mora. | ⏳ |
| 118g | Estado cuenta cliente | Vista consolidada de saldo y facturas por cliente. | ⏳ |
| 118h | Asignación/devolución equipos | Ciclo completo de equipos: stock → asignado → devuelto/baja. | ⏳ |
| 118i | Logs auth explícitos | Registrar LOGIN, LOGOUT, LOGIN_FALLIDO en `auditoria_logs`. | ⏳ |
| 118j | Revocación sesión admin | Admin puede revocar sesiones de otros usuarios. | ⏳ |
| 118k | Reportes avanzados | Filtros, drill-down, exportar CSV. | ⏳ |

---

## Histórico cerradas

| Spec | Nombre | Cerrada en |
|---|---|---|
| **139e** | **Estructura de despliegue (`public/` + `src/`) + ADR 018. Cierra Fase 1 MVC. 6.0a-d + 6.1-6.12 verde. 39 thin files (37 Bootstrap + router + portal), 154 PHP en `src/`, BD sin daño. Fix latente del router (`return false;`).** | **2026-10-19** |
| **139d** | **Controllers config (Rol, Permiso, Empresa) + 7 thin + `branding.css.php` + migrar `tests/audit_context.test.php` + borrar `conexion.php`. 5 tandas, 59/59 verde (A 23 + B 6 + C 22 + D 8 + E 0-refs + harness 9/9). Cierra Fase 1 MVC.** | **2026-10-18** |
| 28 | BD — Base de datos (44 tablas, migración completa) | 2026-10-16 |
| 139c | Controllers infra/soporte (7 controllers + 7 thin, 39/39 verde) | 2026-10-18 |
| 001, 001a | decision-rbac, rbac-migracion-esquema | Fase 0 |
| 002 | unique-factura-servicio-periodo | Grupo F |
| 003 | indices-faltantes | Grupo F |
| 005 | check-sesion-exclusiva | BD-COMPLETA |
| 007 | timestamps-clientes | BD-COMPLETA |
| 008 | validar-cedula | BD-COMPLETA |
| 009 | validar-json-logs | BD-COMPLETA (falso positivo) |
| 010 | permitir-multiples-equipos | 001a |
| 011 | configuracion-singleton | BD-COMPLETA |
| 012 | normalizar-enums | 001a |
| 013 | timestamps-faltantes | BD-COMPLETA |
| 015 | charset-unicode | BD-COMPLETA |
| 016 | login-estado-usuario | Bloque 2 |
| 017 | eliminar-session-nativo | Bloque 2 |
| 018 | enforce-must-change-password | Bloque 2 |
| 019 | fix-auditoria-logs | Bloque 2 |
| 020 | pagos-gate-admin | Bloque 2 |
| 021 | put-idempotencia | 2026-10-12 |
| 022 | validar-insert-transiciones | vía triggers |
| 023 | sacar-update-de-get | Grupo F |
| 024 | proteger-api-empresa | 2026-10-12 |
| 033 | logout-completo | Bloque 2 |
| 034 | login-timing-constante | Bloque 2 |
| 037 | reducir-polling | Bloque 3 |
| 043 | mover-dump-fuera-webroot | Bloque 4 |
| 061 | adrs-faltantes | Bloque 8 |
| 072 | sincronizar-auditoria | Bloque 12 |
| 073 | pagos-restringir-put | Bloque 12 |
| 075 | limpiar-rol-huerfano | BD-COMPLETA |
| 076 | filterBySearch-acentos | 2026-10-13 |
| 097 | indices-logs | BD-COMPLETA |
| 099 | rbac-dos-niveles | 2026-10-11 |
| 100 | rbac-02-sod-config-backend | 2026-10-11 |
| 101 | facturas-monto-pagado-saldo | BD-COMPLETA |
| 102 | facturas-periodo-check | BD-COMPLETA |
| 105 | historial-cambios-plan | BD-COMPLETA |
| 106 | contratos-unico-vigente | BD-COMPLETA |
| 108 | notificaciones-leido-en | BD-COMPLETA |
| 109 | equipos-puerto-tinyint | BD-COMPLETA |
| 110 | factura-lineas-id-servicio | BD-COMPLETA |
| 112 | fks-on-update-cascade | BD-COMPLETA |
| 113 | pagos-metodo-pago-tabla | BD-COMPLETA |

---

## Histórico absorbidas — Specs

| Spec | Nombre | Absorbida por | Cuándo | Nota |
|---|---|---|---|---|
| 004 | validar-pago-pertenencia | 137 (PagoService ya tiene BOLA check) | 2026-10-18 | — |
| 025 | silenciar-errores-sql | 139c/139d (Response::fromException) | 2026-10-18 | — |
| 026 | errores-unique-por-errorinfo | 139c (ConflictException) | 2026-10-18 | — |
| 030 | permisos-gate-admin | 100 (SoD guard aplicado) | 2026-10-18 | — |
| 032 | unificar-delete-params | 139a-d ($_GET['id'] consistente) | 2026-10-18 | — |
| 039 | manejo-401-global | BUG-6.1 (parcial) | 2026-10-18 | Absorción parcial. Si queda interceptor de fetch residual, se añade como nueva spec al cierre de 140. |
| 041 | modularizar-app-js | 140 | 2026-10-18 | — |
| 050 | capa-servicios | 137 (absorbe 086) | 2026-10-18 | — |
| 051 | separar-conexion | 139d (absorbe 087) | 2026-10-18 | — |
| 065 | portal-cliente-real | 096a–096f | 2026-10-12 | — |
| 066 | notificaciones | 086a–086d | 2026-10-12 | — |
| 077 | reflejar-nombre-empresa | 078 (Design System) | 2026-10-18 | — |
| 081 | modulo-instalaciones | 115d (UI pedidos) | 2026-10-18 | — |
| 086 | capa-servicios (duplicado) | 050 | 2026-10-18 | — |
| 087 | separar-conexion (duplicado) | 051 | 2026-10-18 | — |
| 088 | env-vars-timezone | 049 | 2026-10-18 | — |
| 090 | tests-endpoints | 053 | 2026-10-18 | — |
| 091 | tests-frontend | 053 | 2026-10-18 | — |
| 092 | ci-cd (duplicado) | 054 | 2026-10-18 | — |
| 093 | health-check-backups | 057 + 058 | 2026-10-18 | — |
| 094 | entidad-pedido | B28 (tabla `pedidos` en dump) | 2026-10-16 | — |
| 095 | historial-360-cliente | 083 | 2026-10-18 | — |
| 096 | portal-cliente-completo (Bloque 16) | 096a–096f | 2026-10-12 | — |
| 103 | deprecar `servicios.ip_asignada` | 114c (migrar a `ip_asignaciones`) | 2026-10-18 | Spec de ADR 013 (P5). |

---

## Histórico absorbidas — Bloques (marcadores sin número de spec)

| Bloque | Nombre | Absorbido por | Cuándo |
|---|---|---|---|
| 030 (bloque) | Backend adaptado a BD + MVC | 139 (todos) | 2026-10-18 |
| 031 (bloque) | Frontend adaptado + UI | 140 + Fase 3 | 2026-10-18 |

---

## Cómo se actualiza

**Una spec, un lugar, una línea de qué hace.** Al cerrar una spec:

1. Cambia `⏳`/`🔄` → `✅` en su fila.
2. Mueve la fila a "Histórico cerradas". **Un solo movimiento.**
3. Actualiza la línea "Estado actual" al inicio (conteo + siguiente).

Nada más. No hay otro lugar del archivo donde aparezca esa spec.

---

## Referencias

- `AUDITORIA_2026.md` — auditoría canónica (foto 2026-10-11).
- `BD.md` — esquema de BD.
- `ARQUITECTURA.md` — cómo está construido.
- `GOBERNANZA.md` — mapa documental.
- `MEMORY.md` — estado entre sesiones y hallazgos operativos.
- `VERIFICACION.md` — lecciones de verificación.
- `ADR/` — decisiones arquitectónicas (001–018).