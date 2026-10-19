# MEMORY.md — REDYTELCA

Estado operativo entre sesiones. **Único documento de estado del proyecto.**

- Qué spec existe y en qué estado → `docs/ROADMAP.md`.
- Hallazgos canónicos → `docs/AUDITORIA_2026.md`.
- Cómo está construido → `docs/ARQUITECTURA.md`.
- Esquema → `bd_redytelca.sql`.
- Estándares, frases de defensa, industria descartada → `docs/REFERENCIA.md`.
- Reglas del ecosistema documental → `docs/GOBERNANZA.md`.

---

## Estado actual

- **Fase 1 — MVC CERRADA** (excepto 140).
  - Cerradas: 134, 135, 136, 137, 138, 139a, 139b, 139c, **139d**, **139e**.
  - En progreso: ninguna.
  - Pendientes: **140** (frontend modular).
- **BD cerrada (2026-10-16).** 44 tablas. Convención `PK=id` / `FK=tabla_id`. `logs`→`auditoria_logs`. 145 triggers, 4 vistas, 41 CHECKs. Vive en el histórico del ROADMAP.
- **0 endpoints caídos.** Los 13 endpoints de la Fase 1 quedaron reactivados: 5 de roles/permisos en Tanda C, 2 de empresa en Tanda D, y los 6 de infra/soporte en 139c. `conexion.php` **eliminado** en Tanda E.
- **`app.js` e `index.html` son de 140** (frontend viejo, no tocado en 139). Frontend viejo manda `id_*` (pre-B28), backend nuevo espera `*_id` (post-ADR 016) — frontend viejo no puede crear ni editar. Deuda 139b-3.
- **2 bugs corregidos en runtime durante verificación de 139b:** BUG-6.3 (`PagoController::update` pasaba `id_pago`) y BUG-6.4 (`FacturaController::update` pasaba `id_factura`). Ambos resueltos.
- **BUG-6.2 resuelto (2026-10-18, Tanda B):** `Auth::requireStaffAuth` con tercer parámetro `$bypassMustChangePassword` + `cambiar_password.php` con bypass=true. Verif. 6/6 + no-daño verde.

## Fase activa

**FASE 1 — MVC.**

MVC completo sin framework. Namespaces PSR-4, DI por constructor, repositorios, servicios, controladores, validadores, excepciones de dominio. Page Controller. Vista JSON para admin, HTML para portal (Fase 5). ADR 017.

| Spec | Nombre | Estado |
|---|---|---|
| 134 | `core/` (kernel MVC) | ✅ cerrada |
| 135 | `models/` (35 entidades) | ✅ cerrada |
| 136 | `repositories/` (35 repos) | ✅ cerrada |
| 137 | `services/` (18 servicios) | ✅ cerrada |
| 138 | `validators/` (16 validadores) | ✅ cerrada |
| 139a | Auth, Perfil, Usuario, Cliente + 14 thin endpoints | ✅ cerrada (12/12 curl+SQL) |
| 139b | Servicio, Contrato, Plan, Factura, Pago, MetodoPago + 8 thin endpoints | ✅ cerrada (26/26 curl+SQL, 2 bugs fixed) |
| 139c | Nodo, Olt, Nap, Equipo, Ticket, Tarea, Pedido + 7 thin endpoints (1 nuevo) + migración pedidos-rbac | ✅ cerrada (39/39 curl+SQL, migración verificada) |
| 139d | Rol, Permiso, Empresa + 7 thin + `branding.css.php` reescrito + migrar `tests/audit_context.test.php` + borrar `conexion.php`. **5 tandas, 59/59 verde.** Cierra Fase 1 MVC. | ✅ cerrada |
| 139e | Estructura de despliegue: mover `core/`, `controllers/`, `services/`, `repositories/`, `models/`, `validators/`, `tests/` a `src/`; mover thin files + `index.html` + `app.js` + `styles.css` + `Logos/` + `uploads/` a `public/`; actualizar `require` y `Bootstrap.php`; `.htaccess` fallback. Cierra la exposición del código fuente al webroot. ADR 018 al cerrar Tanda E. | ✅ cerrada |
| 140 | frontend modular (`js/`) + borrar `app.js` | ⏳ pendiente |

**Orden:** 134 → 135 → 136 → 137 → 138 → 139a → 139b → 139c → 139d → **139e** → 140.

**Cierre de 139a:** 12/12 verde en curl+SQL. BUG-6.2 documentado (dead-end `must_change_password`, se resuelve en 139d).

**Cierre de 139b:** 26/26 verde en curl+SQL. 2 bugs encontrados y corregidos en runtime:
- BUG-6.3: `PagoController::update` pasaba `id_pago` al service → 500. Fix: `unset($input['id_pago'])`.
- BUG-6.4: `FacturaController::update` pasaba `id_factura` al service → 500. Fix: `unset($input['id_factura'])`.

Deudas documentadas: 139b-1 (4 endpoints sin JOINs), 139b-2 (Servicio update sin direccion), 139b-3 (frontend viejo manda `id_*`, backend nuevo espera `*_id`). Las 3 se resuelven en 140.

**Cierre de 139c:** 39/39 verde en curl+SQL (19 Tanda A + 20 Tanda B). 0 bugs runtime. Migración `2026-10-18-139c-pedidos-rbac.sql` aplicada: página `pedidos` (id=23, modulo_id=3), Admin 4 permisos (leer, crear, editar, eliminar), Operador 3 permisos (leer, crear, editar). Verificada con triggers de auditoría (1 INSERT paginas, 2 rol_modulo_pagina, 7 rol_modulo_permiso).

Deudas documentadas: 139c-1 (7 index sin JOINs), 139c-2 (sidebar pedidos sin showPage), 139c-3 (EquipoController::index sin filtro devuelve solo stock), 139c-4 (Operador sin `leer` en tickets/tareas), 139c-5 (PedidoService::asignarTecnico no expuesto). 139c-1, 139c-2, 139c-3, 139c-5 se resuelven en 140. 139c-4 se resuelve en spec RBAC de Fase 2.

**Cierre de 139d Tanda B (2026-10-18):** BUG-6.2 cerrado. `Auth::requireStaffAuth` con tercer parámetro `$bypassMustChangePassword` (default `false`). `cambiar_password.php` es el ÚNICO thin que pasa `true`. Verif. `tests/verify_139d_b.php` 6/6 + no-daño verde.

**Cierre de 139d Tanda C (2026-10-18):** 5 endpoints reactivados como thin (`roles_listar`, `roles_editar`, `roles_eliminar`, `permisos_listar`, `permisos_guardar`). 2 controllers nuevos (`RolController`, `PermisoController`). 2 métodos nuevos en repos de pivots (`RolModuloPaginaRepository::findByRolId`, `RolModuloPermisoRepository::findByRolId`). 1 método nuevo en `PermisoService` (`listarMatriz`). Cierra G1 (roles sin semilla) y 136f-bis para `rol_modulo*`. Verif. `tests/verify_139d_c.php` 22/22 + no-daño verde (`usuarios=4`, `rol_modulo=13`, `rmpag=39`, `rmperm=137`). 0 bugs runtime. AI Studio intentó reescribir 3 archivos con regresión (borró `findModulosPermitidosByRoles`, revirtió `guardarMatriz` a SQL inline, cambió `insertIgnore` a array); rechazados, aplicados los 3 correctos del dump + Tanda A.

**Cierre de 139d Tanda D (2026-10-18):** `EmpresaController` (nuevo) + `api_empresa.php` + `api_empresa_publica.php` + `branding.css.php` reescritos. E2E D1–D8 verde: GET Admin 200 con config completo, GET Operador 403 (SoD), POST sin logo 200 + persistencia en BD, POST con PNG 200 + `logo_path=uploads/empresa/logo.png` + archivo en disco, POST con .txt 422 (`errors.logo`), API pública 200 con 3 campos (B09 cerrado), branding.css 200 con `Content-Type: text/css` + `:root`, corrupción de color → fallback `#104aa8`. Restore R1–R5: `configuracion_empresa` = `REDYTELCA | Logos/logo_transparent.png | #104aa8`, RBAC seed intacto (`rol=2, rol_modulo=13, rmpag=39, rmperm=137`). Deuda 139d-2 implementada (`@unlink` en catch).

**Cierre de 139d Tanda E (2026-10-18):** `tests/audit_context.test.php` migrado de `conexion.php` a `core/Auth.php` (usa `Bootstrap::init()` local + `Auth::setAuditContext` + `use ($pdo)` en closures). `conexion.php` eliminado tras verificar 0 refs ejecutables (4× `findstr` de patrones `require __DIR__...`, `require_once __DIR__...`, `require '...'`, `require_once '...'` → 0 líneas). `dir conexion.php` → File Not Found. 5× `php -l` (EmpresaController, api_empresa, api_empresa_publica, branding.css, test migrado) → sin errores. Harness final `php tests/run.php` → 9/9.

**Nota crítica:** el código ya no tiene endpoints caídos. `app.js` e `index.html` son de 140 (frontend modular). Las 3 deudas de contrato (139b-1, 139b-3, 139c-1) se resuelven en 140. `conexion.php` ya no existe.

**Reglas nuevas heredadas de 139b:**
- Verificar `unset($input['id_X'])` en cada `update()` de cada controlador (causa de BUG-6.3 y BUG-6.4).
- Tests de DELETE solo con dummies creados en el turno, nunca con seed.
- Verificar cada endpoint contra el esquema post-B28 antes de fusionar.

**Reglas nuevas heredadas de 139c:**
- Aplicar migración SQL primero (pedidos-rbac), verificar con triggers antes de escribir controllers.
- Los dummies de verificación se borran en el mismo turno; no tocar el seed.
- Los 5 deletes bloqueados por dependencias se testean contra el seed (nodo 1, OLT 1, NAP 1, ticket 10) sin borrarlos.
- Verificar trigger 1644 con transición inválida (`Cerrado→Abierto`) y transición válida (`Cerrado→En proceso`).

**Reglas nuevas heredadas de 139d Tanda C:**
- Cuando AI Studio entrega un archivo "modificado", verificar que NO perdió métodos existentes. En Tanda C, AI Studio reescribió `RolModuloPermisoRepository` sin `findModulosPermitidosByRoles` (rompe login). Rechazar y corregir partiendo del dump.
- Los tests de escritura RBAC usan rol dummy creado en el turno (`test_139d_rol_perm`), no rol 2 (seed). Cascada limpia `rol_modulo*` al borrar el rol.
- Body JSON en subproceso CLI viaja por `base64_encode`/`base64_decode` para evitar corrupción de `escapeshellarg` en Windows.

**Reglas nuevas heredadas de 139d Tanda D/E:**
- Body JSON en CLI de Windows: `Set-Content -Encoding utf8` de PowerShell 5.1 agrega BOM (síntoma: `json_decode` devuelve null → backend cae a `$_POST` vacío → 400 "Credenciales incompletas"). Usar `[System.IO.File]::WriteAllText` sin BOM.
- Todo archivo PHP nuevo debe empezar con `<?php` en la línea 1. Comentario `// filename` en línea 2. **Nunca** al revés. Síntoma: `header()` falla con "headers already sent" y JSON/CSS llega con prefijo de texto.
- `findstr /S /C:"require" *.php | findstr /C:"conexion.php"` produce falsos positivos (el prefijo `archivo:` del output matchea el segundo filtro). Verificar "0 refs ejecutables" con strings literales completos (`require_once __DIR__ . '/conexion.php'`), no con pipes.

## Próximos pasos

1. **Spec 140** — Fase 1. Frontend modular (`js/`) + borrar `app.js`.
3. **Fase 2 — Deudas heredadas de MVC** (BUG-6.1, DOC-04/05/08/09, EST-02/03, 060, deudas de contrato).
4. **Fase 3 — UI/UX bonita** (078–085).
5. **Fase 4 — Botón Roles + permisos en grilla** (084 + tareas sin número).
6. **Fase 5 — Portal + Notificaciones** (096a–f + 086a–h).
7. **Fase 6 — Título del proyecto**.
8. **Fase 7 — Resto** (~61 specs).

---

## Registro maestro de deudas

**Regla:** toda deuda detectada entra aquí con ID único, origen, fase asignada y flag "bloquea defensa". Ninguna deuda sale de esta tabla sin su fase cerrada. Si algo no está aquí, no existe.

**Origen:**
- `[conv]` — estaba en la conversación pegada.
- `[plan]` — estaba en el plan original de 139.
- `[audit-turno]` — detectada en auditoría de turnos previos.
- `[audit-139b]` — detectada al auditar y verificar 139b (2026-10-18).
- `[audit-139c]` — detectada al auditar y verificar 139c (2026-10-18).
- `[audit-139d]` — detectada al auditar y verificar 139d (2026-10-18).

### A. Fase 1 — MVC (bloqueantes)

| ID | Sev | Descripción | Origen | Spec | ¿Bloquea defensa? |
|---|---|---|---|---|---|

**Cerradas al cierre de 139e (histórico):**
- **139e-webroot** — código movido a `src/`, thin files a `public/`. `core/Config.php` por URL -> 404. `.htaccess` raíz conservado como defensa en profundidad. ADR 018. Verificado E2E (6.10, 6.11, 6.12).

**Cerradas al cierre de 139d (histórico):**
- **139d-endpoints** — 2 endpoints con columnas viejas post-B28 (`api_empresa`, `api_empresa_publica`). ✅ cerrada en Tanda D.
- **A3** — `branding.css.php` requería `conexion.php`. ✅ cerrada en Tanda D (reescrito como thin CSS).

### B. Deudas de contrato (heredadas de MVC, resueltas en Fase 1/2)

| ID | Sev | Descripción | Origen | Fase | ¿Bloquea defensa? |
|---|---|---|---|---|---|
| **139b-1** | Media | `ServicioController::index`, `ContratoController::index`, `FacturaController::index`, `PagoController::index` usan `Repository::findAll()` sin JOINs. Campos `servicio_alias`, `cliente_nombres`, `cliente_apellidos`, `plan_nombre`, `nap_codigo`, `direccion_texto` desaparecen del JSON. **Cubre 4 endpoints, no 3.** | [audit-139b] | 140 | **No** |
| **139b-2** | Baja | `ServicioController::update` valida `direccion_texto` pero no persiste cambios en `direccion_servicio`. | [audit-139b] | 139-bis o 140 | **No** |
| **139b-3** | Media | Frontend viejo manda `id_servicio`, `id_cliente`, `id_factura`, `id_metodo`, `id_plan`, `id_naps` (pre-B28). Backend nuevo espera `servicio_id`, `cliente_id`, `factura_id`, `metodo_id`, `plan_id`, `nap_id` (post-ADR 016). **El frontend viejo no puede crear ni editar nada.** | [audit-139b] | 140 | **No** |
| **139c-1** | Media | 7 `index()` de infra/soporte (Nodo, Olt, Nap, Equipo, Ticket, Tarea, Pedido) sin JOINs. Mismos campos enriquecidos que 139b-1 (`total_olts`, `total_naps`, `clientes_conectados`, `nap_codigo`, `cliente_nombres`, `servicio_alias`, `direccion_texto`, `equipo_count`) desaparecen del JSON. | [audit-139c] | 140 | **No** |
| **139c-2** | Media | Sidebar del frontend viejo muestra "Pedidos de campo" (porque `renderMenu` lee `rol_modulo_pagina`) pero `app.js` no tiene `showPage('pedidos')`. Clic sin efecto visible. | [audit-139c] | 140 | **No** |
| **139c-3** | Media | `EquipoController::index()` sin filtro devuelve solo `estado_inventario='stock'`. El endpoint viejo devolvía todos. El frontend nuevo debe pedir filtro explícito. | [audit-139c] | 140 | **No** |
| **139c-4** | Media | Operador (rol 2) no tiene `leer` en `rol_modulo_permiso` para `tickets` (pagina_id=5) ni `tareas` (pagina_id=4). GET como Operador → 403. El seed RBAC del Operador está incompleto. | [audit-139c] | Fase 2 | **No** |
| **139c-5** | Baja | `PedidoService::asignarTecnico` no se expone en `PedidoController`. `update()` acepta `tecnico_asignado_id` sin validar que el pedido no esté completado/cancelado. | [audit-139c] | 140 | **No** |

### C. Deudas de fases futuras (Fases 2–7)

| ID | Sev | Descripción | Origen | Fase | ¿Bloquea defensa? |
|---|---|---|---|---|---|
| **BUG-6.1** | Crítico | Permisos no se aplican en tiempo real. `restoreSession()` no limpia `window.*Cache` cuando la sesión es válida. | [conv] | Fase 2 | **No** (curl funciona; demo puede mostrar caché stale) |
| **F08** | Medio | Cachés `window.*Cache` no se limpian al logout. Overlap con BUG-6.1. | [conv] | Fase 2 (con BUG-6.1) | **No** |
| **Overlay-F5** | Medio | `restoreSession()` no oculta `#login-overlay` tras restauración válida. En F5 con sesión activa, reaparece el modal. | [conv] | Fase 2 | **No** (solo UX) |
| **MAP-01** | Medio | Página `mapa` no tiene endpoint propio. Checkboxes de operación huérfanos. | [conv] | Fase 4 | **No** |
| **EST-01** | Medio | Páginas sin CRUD propio (`dash`, `mapa`, `reportes`, `notificaciones`, `password`). Nivel 2 huérfano. | [conv] | Fase 4 | **No** |
| **UX-01** | Bajo | `<select id="contrato-estado">` en modal "Nuevo contrato" ofrece `vencido` y `rescindido`, pero el backend siempre fuerza `vigente`. | [conv] | Fase 4 | **No** |
| **B15** | Medio | Password temporal `'Redytelca123!'` hardcoded en `UsuarioController::store`. | [plan] | Fase 7 (028) | **No** |
| **B13** | Medio | `id_plan=1` hardcoded en `registrar_cliente.php` si no viene plan. | [plan] | Fase 7 (027) | **No** |
| **B14** | Medio | Password ≥8 chars; NIST SP 800-63B pide ≥15 (single-factor). | [plan] | Fase 7 (028) | **No** |
| **139d-1** | Bajo | `roles_eliminar.php` mantiene POST por compatibilidad con el frontend viejo. En 140 el frontend nuevo puede usar DELETE. Se unifica en 140. | [audit-139d] | 140 | **No** |
| **139d-2** | Bajo | `EmpresaController::store` sube archivo antes de validar campos. Si el service falla, se hace `@unlink($logoPath)`. Implementado en 139d; se verifica end-to-end en 140 si la UI cambia. | [audit-139d] | 139d (impl) / 140 (verif) | **No** |
| **139d-3** | Bajo | Quedan 5 métodos con SQL crudo en `AuthService` y `UsuarioService`: `cargarRolesUsuario`, `cargarModulosPermitidos`, `asignarRoles`, `listarRoles`, `contarOtrosAdminsActivos`, `desactivar`. Cierran 136f-bis en Fase 2. | [audit-139d] | Fase 2 | **No** |

### D. Deudas documentales

| ID | Sev | Descripción | Origen | Fase | Estado |
|---|---|---|---|---|---|
| **DOC-04** | Media | `ARQUITECTURA.md` y `BD.md` dicen `utf8mb4_general_ci`; la BD es `utf8mb4_unicode_ci`. | [conv] | Fase 2 | ⏳ |
| **DOC-05** | Media | `ARQUITECTURA.md` y `BD.md` no listan las 17 tablas nuevas de BD-COMPLETA. | [conv] | Fase 2 | ⏳ |
| **DOC-07** | Media | `ROADMAP.md` listaba specs absorbidas por BD-COMPLETA como pendientes. | [conv] | — | ✅ auto-resuelta 2026-10-18 |
| **DOC-08** | Baja | ADR 013 dice que `notificaciones_staff` no tendría triggers de auditoría; P4 sí los creó. | [conv] | Fase 2 | ⏳ |
| **DOC-09** | Baja | `bd_redytelca_schema.sql` desactualizado (pre-P14/P15, dice 48 tablas; la BD tiene 44). Regenerar. | [conv] | Fase 2 | ⏳ |
| **DOC-10** | Media | `MEMORY.md` y `ROADMAP.md` stale sobre 135–139b. | [audit-turno] | — | ✅ auto-resuelta 2026-10-18 |
| **DOC-11** | Baja | `ROADMAP.md` tenía el bloque `BLOQUE 0` duplicado. | [audit-turno] | — | ✅ auto-resuelta 2026-10-18 |
| **DOC-12** | Media | `ROADMAP.md` contradicción sobre 096 (diferido vs reactivado). | [audit-turno] | — | ✅ auto-resuelta 2026-10-18 |
| **DOC-13** | Media | Bloques 30/31 absorbidos por 139/140 no documentado formalmente. | [audit-turno] | — | ✅ auto-resuelta 2026-10-18 |
| **EST-02** | Media | `sla_definiciones` solo tiene 3 de 8 combinaciones (falta Instalación y Baja). | [conv] | Fase 2 | ⏳ |
| **EST-03** | Baja | `email_plantillas` solo tiene 6 de ~9 eventos (falta ticket_creado, pago_pendiente, pago_rechazado). | [conv] | Fase 2 | ⏳ |

**Nota:** DOC-07, DOC-10, DOC-11, DOC-12 y DOC-13 se auto-resolvieron al reescribir `ROADMAP.md` el 2026-10-18. No aparecen como deudas vivas; se conservan aquí para trazabilidad.

### Bugs detectados y su estado

| ID | Sev | Estado | Fix |
|---|---|---|---|
| **BUG-6.2** | Alto | ✅ resuelto (2026-10-18, Tanda B) | `Auth::requireStaffAuth($pdo, $minRoleId, $bypassMustChangePassword)` + `cambiar_password.php` con bypass=true. Verif. 6/6 + no-daño verde. |
| **BUG-6.3** | Alta | ✅ resuelto (2026-10-18) | `unset($input['id_pago'])` en `PagoController::update`. |
| **BUG-6.4** | Alta | ✅ resuelto (2026-10-18) | `unset($input['id_factura'])` en `FacturaController::update`. |

### Resumen ejecutivo

| Categoría | Total | Bloquean defensa |
|---|---|---|
| A. Fase 1 (bloqueantes) | 0 | **0** |
| B. Contrato (heredadas de MVC) | 9 | 0 |
| C. Fases futuras (2–7) | 13 | 0 |
| D. Documentales vivas | 5 | 0 |
| D. Documentales auto-resueltas | 5 | — |
| Bugs (3 fixed) | 3 | 0 |
| **Total deudas vivas** | **27** | **0** |

---

## Hallazgos operativos cerrados (histórico)

Deudas cerradas en bloques/fases previas. Se conservan por trazabilidad.

### Absorciones estructurales (2026-10-18)

| ID | Descripción | Absorbido por |
|---|---|---|
| **ABS-30-31** | Bloques 30 (backend adaptado) y 31 (frontend adaptado + UI) absorbidos por Fase 1 (139) y Fase 3 (UI/UX). El trabajo de "UI más bonita" del viejo Bloque 31 vive en Fase 3 (specs 078–085). | Fase 1 + Fase 3 |
| **ABS-32** | Bloque 32 (Portal + Notificaciones) desaparece como bloque separado. Su contenido vive en Fase 5 (096a–f + 086a–h). | Fase 5 |
| **ABS-33** | Bloque 33 (Botón Roles + permisos grilla) desaparece como bloque separado. Su contenido vive en Fase 4 (084 + tareas). | Fase 4 |
| **ABS-portal** | Deuda `Portal-stub` (portal_cliente.php sin endpoints). Absorbida por Fase 5 (096a). | Fase 5 |
| **ABS-notif** | Deudas `Notif-vacía` (notificaciones vacía) y `Portal-creds` (clientes_credenciales vacía). Absorbidas por Fase 5 (086a–d + 096a). | Fase 5 |

### Deudas cerradas en 139d Tanda A (2026-10-18)

| ID | Descripción | Verificación |
|---|---|---|
| **136f-bis (parcial)** | 8 métodos de services con SQL crudo migrados a 4 repos de pivots: `AuthService::cargarRolesUsuario`, `AuthService::cargarModulosPermitidos`, `UsuarioService::asignarRoles`, `UsuarioService::listarRoles`, `UsuarioService::contarOtrosAdminsActivos`, `UsuarioService::desactivar`, `RolService::eliminar`, `PermisoService::eliminar`. | `tests/verify_139d_a.php`: 23/23 verde. |
| **139d-A-1** | `revocarByToken`/`revocarTodasByUsuarioId` fallaban con `chk_sesion_expira` si login y logout caían en el mismo segundo. Fix aplicado: `GREATEST(UTC_TIMESTAMP(), DATE_ADD(creado_en, INTERVAL 1 SECOND))`. | Segunda pasada de `verify_139d_a.php`: 23/23 (antes: 21/22 con race). |

### Deudas cerradas en 139d Tanda C (2026-10-18)

| ID | Descripción | Verificación |
|---|---|---|
| **G1** | `roles_listar.php` ya no inserta semilla cuando `rol` está vacía. La migración `2026-10-18-139c-pedidos-rbac.sql` ya sembró los 2 roles base. | `tests/verify_139d_c.php` C1/C4: sin semilla, id_rol > 2. |
| **136f-bis (rol_modulo*)** | `PermisoService::guardarMatriz` ya no tiene SQL inline. Delega en `RolModuloRepository`, `RolModuloPaginaRepository`, `RolModuloPermisoRepository` (con `findByRolId`, `deleteByRolModuloId`, `insertIgnore`, `ensure`). Cierra la deuda 136f-bis para los pivots de RBAC. | `tests/verify_139d_c.php` C11: escritura verificada en `rol_modulo_pagina` y `rol_modulo_permiso`. |

### Deudas cerradas en 139d Tanda D (2026-10-18)

| ID | Descripción | Verificación |
|---|---|---|
| **139d-endpoints (parte D)** | `api_empresa.php` y `api_empresa_publica.php` reescritos como thin, con `EmpresaController` (nuevo) que delega en `EmpresaService`. Ya no usan `conexion.php` ni SQL inline. | E2E D1–D3.verif: GET Admin 200 (config completo), GET Operador 403 (SoD), POST sin logo 200 + persistencia en BD. |
| **A3** | `branding.css.php` reescrito como thin CSS con `Http::init()` + `ConfiguracionEmpresaRepository::findPublicBranding()`. Ya no requiere `conexion.php`. | E2E D7: 200 con `Content-Type: text/css` y `:root { --accent: #104aa8; ... }`. E2E D8: fallback a `#104aa8` con color corrupto. |
| **139d-2 (impl)** | `EmpresaController::store` implementa `@unlink($logoPath)` en el catch si el service falla. Cleanup de archivo huérfano. | E2E D5: upload .txt → 422 con `errors.logo`, sin archivo en `uploads/empresa/`. Verificación end-to-end en 140 cuando la UI suba archivos reales. |
| **B09 (parcial)** | `api_empresa_publica.php` devuelve solo 3 campos (`nombre_empresa`, `logo_path`, `color_primario`). Config sensible (`impuesto_porcentaje`, `dia_facturacion`, `dias_gracia_mora`, `monto_recargo_mora`, `dias_corte_automatico`) ya no se expone sin auth. | E2E D6: 200 con solo 3 campos, sin `impuesto_porcentaje` ni días. |

### Deudas cerradas en 139d Tanda E (2026-10-18)

| ID | Descripción | Verificación |
|---|---|---|
| **51 / 087 / A2 (cierre)** | `conexion.php` eliminado. Sus 9 funciones deprecadas ya no existen. Todos los thin files usan `core/Bootstrap.php` + `core/Auth.php` + `core/Http.php`. | 4× `findstr` de patrones de `require` a `conexion.php` → 0 líneas. `dir conexion.php` → File Not Found. `php tests/run.php` → 9/9. |
| **tests/audit_context.test.php** | Migrado de `require_once conexion.php` + `global $pdo` + `setAuditContext(...)` a `require_once core/Bootstrap.php` + `Bootstrap::init()` local + `Auth::setAuditContext(...)` + `use ($pdo)` en closures. | `php -l` sin errores. `php tests/run.php` → 5/5 tests de auditoría + 4/4 smoke = 9/9. |

### Deudas nuevas Tanda A (139d)

| ID | Sev | Descripción | Cierre |
|---|---|---|---|
| **139d-A-2** | Bajo | `Auth::requireStaffAuth` no filtra `revocado_en IS NULL`. Un token revocado en el mismo segundo del logout puede ser aceptado ≤1s por `requirePageAccess`/`requireOperation`. `validateSession` sí rechaza. | Spec RBAC-auth o Tanda E. |
| **139d-A-3** | Bajo | `RolService::eliminar` confía en `ON DELETE CASCADE` de `rol_modulo*` sin comentario. Funciona (FKs verificadas), pero el comportamiento no es explícito en el código. | Documentar en ADR 008 o Tanda C. |

### Deudas nuevas Tanda B (139d)

| ID | Sev | Descripción | Cierre |
|---|---|---|---|
| **139d-B-1** | Bajo | `Auth::jsonError()` serializa sin `JSON_UNESCAPED_UNICODE`; los mensajes de error con acentos salen como `\u00f1`. Inconsistente con `Response::json()`, que sí usa la flag. Rompe asserts textuales contra mensajes de Auth. | Fase 2. |

### Hallazgos operativos cerrados (previos)

| ID | Sev | Descripción | Resuelto con |
|---|---|---|---|
| RBAC-01 | Bajo | Plan RBAC 099 desactualizado. | Corregido en `PLAN-RBAC-DOS-NIVELES.md`. |
| RBAC-02 | Crítico | SoD de Configuración solo en UI. | Bloque 18 (spec 100). |
| RBAC-03 | Medio | Endpoints de clientes sin RBAC nivel 2. | Fase 2 del Rediseño de Servicios. |
| CODE-01 | Alto | `registrar_cliente.php` insertaba `'activo'` violando trigger. | Cambio a `'pendiente'`. |
| CODE-02 | Medio | `api_equipos.php` asumía UNIQUE obsoleto. | Retiro de `es_principal`. |
| CODE-03 | Alto | GET `api_empresa.php` con side-effect. | Spec 021. |
| CODE-04 | Medio | PUT `api_facturas.php` sin validar periodo ni capturar 1062. | Spec 021. |
| CODE-05 | Medio | POST `api_contratos.php` aceptaba estados inválidos. | Spec 021. |
| CODE-06 | Medio | PUT `api_naps.php` no exigía lat/lon. | Spec 021. |
| CODE-07 | Medio | PUT `api_nodos.php` no exigía `nombre`. | Spec 021. |
| CODE-08 | Medio | PUT `api_olts.php` no exigía campos. | Spec 021. |
| CODE-09 | Medio | PUT `api_nodos.php` no exigía lat/lon. | Spec 021. |
| CODE-12 | Medio | `api_pagos.php` POST con `'status' => 'ok'` literal. | Corregido en P13 Tanda 2. |
| DOC-03 | Bajo | Color `#104aa8` inconsistente. | Spec 021. |
| UI-01 | Bajo | Header de `clientes-table` desalineado. | Spec 021. |
| UI-02 | Bajo | Header de `contratos-table` desalineado. | Spec 021. |
| UI-03 | Bajo | Fix aplicado. | 2026-10-13. |
| UI-04 | Bajo | Mensajes genéricos cuando trigger 45000 (MySQL 1644). | ✅ Cerrado por H1 (139b, runtime confirmado). |
| DB-01 | Bajo | UNIQUE redundante en `sesiones`. | `DROP INDEX`. |
| DB-08 | Medio | Confusión `monto`+`moneda`+`tasa_cambio`. | P13b: Opción B con COMMENTs + UI. |
| DB-09 | — | `v_estado_cuenta_cliente` con saldo incorrecto. | P10. |
| DB-10 | — | `tickets.sla_vence_en` nunca se poblaba. | P12. |
| DB-11 | Alta | `equipos` sin `chk_equipo_estado_servicio`. | P12. |
| DB-12 | Media | `planes` sin CHECKs de velocidad. | P14-bis. |
| DB-13 | Baja | `sesiones.creado_en` no era `datetime`. | P9. |
| DB-14 | Alta | P8b habría descartado 6 CHECKs. | Falso positivo: verificados con `information_schema`. P15 los repuso. |
| DB-15 | Baja | `v_facturas_con_saldo` sin `GREATEST(...,0)`. | P15 PASO 6. |

---

## Decisiones de proceso vigentes

### Frameworks — `[PENDIENTE]`

El docente podría haber mencionado frameworks como permitidos a un compañero. **No hay confirmación directa.** Si se confirma: evaluar reescritura vs. mantener monolito modular. **Recomendación:** NO reescribir. Documentar como decisión de alcance.

### Reasignación de cliente en un servicio — fuera de alcance

El cliente de un servicio NO se puede cambiar desde el formulario de edición. La reasignación es una operación administrativa separada (patrón Splynx "Migrate services"), con su propio permiso y auditoría.

### 136f-bis — opción B confirmada, cerrada para rol_modulo*

SQL crudo en 8 métodos de services. Tanda A migró los 8 a repos. Tanda C añadió `listarMatriz` al service, reemplazando el SQL inline de `permisos_listar.php`. La deuda 136f-bis queda cerrada para `rol_modulo*` (los 4 repos de pivots tienen todos los métodos necesarios). Quedan 5 métodos con SQL crudo en `AuthService` y `UsuarioService` que se cierran en Fase 2 (deuda 139d-3).

### Tests de DELETE — solo con dummies

Regla nueva (2026-10-18). Cada test de DELETE positivo usa un dummy creado con POST en el mismo turno. **Nunca IDs del seed.** Origen: durante la verificación de 139b se borró contrato 10 (seed real) por error de diseño del test D2. Restaurado desde `auditoria_logs`.

### `unset($input['id_X'])` en cada `update()`

Regla nueva (2026-10-18). Cada `update()` de controller debe hacer `unset($input['id_X'])` antes de pasar `$input` o `$validated` al service. Origen: BUG-6.3 y BUG-6.4, ambos por pasar el ID al `BaseRepository::update` que genera `SET id_X = ?`.

### Migración RBAC primero, código después (139c)

Regla nueva (2026-10-18). Antes de escribir controllers que dependen de una página en `paginas`, se aplica la migración SQL (insertar página + roles + permisos) y se verifica con triggers de auditoría. Origen: 139c opción B (pedidos en RBAC).

### AI Studio puede regresar archivos sin métodos existentes (139d Tanda C)

Regla nueva (2026-10-18). Cuando AI Studio entrega un archivo "modificado", verificar que conserva todos los métodos del dump. En Tanda C, AI Studio:
- Reescribió `RolModuloPermisoRepository` sin `findModulosPermitidosByRoles` (rompe login).
- Revirtió `PermisoService::guardarMatriz` a SQL inline (viola 136f-bis).
- Cambió `insertIgnore` de 3 ints a array (rompe Tanda A).

Rechazar la entrega y corregir partiendo del dump + Tanda A, no de la entrega de AI Studio.

### Tests RBAC con rol dummy (139d Tanda C)

Regla nueva (2026-10-18). Los tests que escriben en `rol_modulo*` usan un rol dummy creado en el turno (`test_139d_rol_perm`), no el rol 2 (seed). Cascada limpia las tablas hijas al borrar el rol. Cero residuo en `rmpag`/`rmperm`.

### Body JSON en subproceso CLI (139d Tanda C)

Regla nueva (2026-10-18). En `cmd` de Windows, `escapeshellarg` corrompe JSON con comillas. El body viaja por `base64_encode`/`base64_decode`. Los tests que simulan HTTP en CLI usan este patrón.

### Body JSON en CLI de Windows — BOM (139d Tanda D/E)

Regla nueva (2026-10-18). `Set-Content -Encoding utf8` en PowerShell 5.1 agrega BOM. Para JSONs enviados a `php://input`, usar `[System.IO.File]::WriteAllText` (sin BOM). Síntoma: `json_decode` devuelve `null`, backend cae a `$_POST` vacío, responde 400 "Credenciales incompletas". Detectable con `[System.IO.File]::ReadAllBytes` → primeros 3 bytes `239,187,191` = BOM presente.

### 139e — estructura `public/` + `src/` aprobada (2026-10-18)

Decisión de estándar, no de comodidad. Hoy el código fuente (`core/`, `controllers/`, `services/`, etc.) está dentro del webroot. Cualquier petición a `http://127.0.0.1/redytelca/core/Config.php` ejecuta el archivo por URL. La mitigación correcta no es `.htaccess` frágil, es sacar el código del webroot. Patrón de Laravel, Symfony, CodeIgniter y el manual oficial de PHP: `public/` como DocumentRoot, `src/` para el código. Se ejecuta entre 139d y 140, con ADR 018. Constitución §10: el estándar manda, no la comodidad.

### ADRs pendientes

Dos decisiones arquitectónicas implementadas sin ADR formal:
- Servicio como entidad de primer nivel en la UI. Fundamento: Splynx, Omnitouch, PortaOne, TM Forum SID.
- GET sin side-effects vía endpoint POST dedicado. Fundamento: CWE-650, RFC 7231, Google Cloud Scheduler, AWS EventBridge.

---

## Aprendizajes recientes

- **La industria separa tareas internas de pedidos de campo.** Sonar: Tasks vs Jobs. BSS/OSS: Sales Order → Work Order.
- **Todo pedido de campo nace de un servicio.** Splynx, Sonar, UISP, BSS/OSS.
- **`propiedad` de equipos no es siempre derivable de `tipo`.**
- **UI ≠ backend.** Verificar ambas capas por separado.
- **El guard SoD va en `requirePageAccess`, no en `requireOperation`.**
- **Verificar el ciclo completo logout → login → cambio de rol.**
- **Abrir DevTools → Network** durante verificaciones.
- **Verificar que el dominio de la página tiene endpoint propio** antes de aplicar un gate de nivel 2.
- **Los triggers BEFORE INSERT son trampa para INSERTs directos** (CODE-01).
- **Los generados + UNIQUE en MySQL no restringen si la columna generada queda NULL** (CODE-02).
- **Al probar un endpoint aislado, cerrar todas las pestañas de la app.** Usar `curl`.
- **Auditoría uno por uno > auditoría en lote.**
- **Un número que el dump ya contiene no se replica en un doc.**
- **Un documento híbrido (hallazgos + estado) miente desde el día siguiente.**
- **Un solo carácter puede romper todo el layout.** El `*` faltante en `styles.css`.
- **En CSS, a igual especificidad, gana el archivo que aparece último.**
- **phpMyAdmin no exporta CHECK constraints.** Verificar con `information_schema` o `mysqldump`.
- **El mensaje del SIGNAL 45000 (MySQL 1644) es texto de negocio, no PII.**
- **Un shim Strangler Fig permite migrar sin romper el contrato.** Fowler.
- **Escribir la capa de modelos antes que repositorios** ordena las dependencias.
- **Un método `count*` de repositorio debe replicar el filtro del endpoint que lo consume.** `ServicioRepository::countByNapId` (VERIFICACION.md §2.7).
- **AI Studio ignora sistemáticamente la instrucción de indentación.** Verificar indentación como primer punto de auditoría de forma (VERIFICACION.md §2.8).
- **`revocarByToken` no debe usar `rowCount()`.** Verificar con `findByToken !== null` (VERIFICACION.md §2.9).
- **Los endpoints fuera del alcance de 139a/139b deben verificarse contra el esquema post-B28 antes de declarar el sistema estable.**
- **`must_change_password` es un flag de ida y vuelta.** Si el backend lo enforca en `requireStaffAuth`, hay que ofrecer una ruta de escape. BUG-6.2 (cerrado en Tanda B).
- **Cada `update()` de controller debe hacer `unset($input['id_X'])`** antes de pasar al service. Causa de BUG-6.3 y BUG-6.4.
- **Tests de DELETE solo con dummies, nunca con seed.** Mi error con contrato 10.
- **El contrato del frontend viejo (app.js) manda `id_*`, el backend nuevo espera `*_id`.** Cambio de contrato, no bug. Se resuelve en 140.
- **Los bloques 30 y 31 eran redundantes con 139 y 140.** Absorbidos 2026-10-18.
- **Los bloques 32 y 33 eran marcadores vacíos.** Absorbidos en Fases 5 y 4.
- **Verificación por CLI, no por navegador, hasta 140.** `app.js` viejo tiene bugs pre-existentes (`restoreSession` no oculta `#login-overlay`; F5 pide login aunque `auth_state.php` devuelva `authenticated:true`). Forzar verificación visual contra frontend roto es perder tiempo. Toda verificación 139b–139d es `curl` + `mysql -e` + `php -l` + `findstr`.
- **Formato de verificación obligatorio:** pasos numerados + comando + Esperado + precondición de abort. El humano ejecuta, pega salidas literales. El agente audita. Ver `docs/METODOLOGIA_DESARROLLO_IA.md` § 5.5.
- **El trigger 1644 se mapea correctamente a 422.** Confirmado en runtime en Tanda B: `PUT /api_tickets.php` con `Cerrado→Abierto` → 422 con mensaje exacto del trigger. La rama 1644 de `Response::fromException` funciona.
- **El seed RBAC del Operador está incompleto.** Tiene `crear`/`editar` pero no `leer`/`eliminar` en tickets y tareas. Deuda 139c-4.
- **Los `index()` de Tanda A/B no tienen JOINs.** Los campos enriquecidos (`total_olts`, `nap_codigo`, `servicio_alias`, `cliente_nombres`, etc.) desaparecen del JSON. Deuda 139c-1.
- **Verificar el conteo esperado contra la BD real, no contra la memoria.** En Tanda A el conteo `paginas` era 22 (no 23, por el hueco en id=3). En Tanda B la auditoría tenía 8 filas (no 7, porque son 2 UPDATE). En Tanda C el script tiene 22 checks, no 12 (los checks binarios suman más).
- **`Auth::jsonError()` no usa `JSON_UNESCAPED_UNICODE`.** Los mensajes con acentos salen como `\u00f1`. Rompe asserts textuales. Deuda 139d-B-1.
- **El código fuente dentro del webroot es una clase entera de vulnerabilidades.** La mitigación correcta es `public/` + `src/`, no `.htaccess`. Estándar de Laravel, Symfony, CodeIgniter, manual de PHP. Spec 139e.
- **AI Studio reescribe archivos "modificados" perdiendo métodos.** En Tanda C borró `findModulosPermitidosByRoles` (rompe login), revirtió `guardarMatriz` a SQL inline, cambió `insertIgnore` a array. Verificar contra el dump antes de aplicar.
- **Body JSON en subproceso CLI de Windows se corrompe con `escapeshellarg`.** Usar `base64_encode`/`base64_decode`.
- **`Set-Content -Encoding utf8` en PowerShell 5.1 agrega BOM.** Para JSONs enviados a `php://input`, usar `[System.IO.File]::WriteAllText` (sin BOM). Síntoma: `json_decode` devuelve `null`, backend cae a `$_POST` vacío, responde 400 "Credenciales incompletas". Detectable leyendo los primeros 3 bytes del archivo (`239,187,191` = BOM).
- **`findstr /S /C:"require" *.php | findstr /C:"conexion.php"` produce falsos positivos.** El prefijo `<archivo>:` del output de `findstr /S` matchea el segundo `findstr` por la parte `conexion.php:` del prefijo. Para verificar "0 requires vivos", buscar el string literal completo (`findstr /C:"require_once __DIR__ . '/conexion.php'"`), no con pipes.
- **`<?php` debe ser la primera línea del archivo (byte 0).** Comentario `// filename` va en línea 2, no antes. Un `// filename` antes de `<?php` se emite como output literal antes del parseo; `header()` falla con "headers already sent" y el body JSON/CSS llega con prefijo de texto. En Tanda D, AI Studio devolvió los 4 archivos con `// filename` en línea 1; rechazados, reordenados manualmente.
- **Sobrescribir archivo completo, no solo la línea a cambiar.** En Tanda E, se aplicó el `<?php` corregido del test migrado pero no se sobrescribió el resto del archivo: los 2 comentarios obsoletos (`// Depende de que conexion.php tenga el guard CLI`) y el `require_once conexion.php` original seguían en su lugar. Síntoma: `findstr` seguía devolviendo 3 hits. Lección: al reescribir un archivo, `Ctrl+A` + `Supr` + pegar completo, nunca editar por partes.
- **`tests/audit_context.test.php` era el único bloqueante del borrado de `conexion.php`.** `AGENTS.md` ya lo declaraba como "se migra a `core/Auth.php` en 139d (ítem explícito de esa spec)". El plan de Tanda E (§6, paso 7) asumía `php tests/run.php` verde sin mencionar la migración. Lección: cruzar el plan contra las deudas conocidas del dump antes de ejecutar el borrado. El orden correcto es: (1) migrar el archivo, (2) verificar 0 refs, (3) borrar `conexion.php`, (4) correr harness.

- **Bug latente del router con `php -S`.** `public/router.php` tenia `$path = __DIR__ . $uri;` sin slash. Con Apache no se ejecuta (`.htaccess` con `RewriteCond !-f` solo invoca el router para rutas no existentes). Con `php -S` + router script, el router se ejecuta siempre y el fallback devolvia 404 a `/login.php`. Fix: `return false;` en el fallback. `php -S` sirve el archivo y lo ejecuta como PHP. Ver ADR 018.

- **`debug_login.php` colateral.** El script temporal con credenciales hardcodeadas (`admin` / `admin123`) se colo al primer commit de 139e. Borrado en commit siguiente. Regla: los scripts de diagnostico que contienen credenciales se borran ANTES del `git add -A`, o se crean fuera de `src/tests/`.

- **MariaDB se apaga solo.** Durante la verificacion E2E, MariaDB se detuvo sin razon aparente. El 500 de `/login.php` con `"Error de base de datos"` era el PDOException 2002 mapeado a 500 generico. Leccion: cuando aparezca 500 en runtime y el codigo no cambio, verificar `netstat -ano | findstr :3306` ANTES de tocar codigo.

- **`Set-Content -Encoding UTF8` en PowerShell 5.1 agrega BOM.** Tambien corrompe caracteres no-ASCII que vienen de cmd (CP850) → mojibake doble. Para archivos UTF-8 sin BOM: `[System.IO.File]::WriteAllText($p, $c, (New-Object System.Text.UTF8Encoding $false))` con `$c` ya construido desde strings ASCII. Si el contenido necesita acentos, construir via `[char]0xNNNN` para cada caracter no-ASCII. Aplicado al ADR 018.

Aprendizajes completos: `docs/REFERENCIA.md` §4.
Lecciones de verificación: `docs/VERIFICACION.md`.

---

## Documentación clave (referencia rápida)

- `docs/GOBERNANZA.md` — mapa del ecosistema documental.
- `docs/constitution.md` — principios innegociables.
- `AGENTS.md` — reglas operativas del agente.
- `docs/METODOLOGIA_DESARROLLO_IA.md` — ciclo de trabajo.
- `docs/METODOLOGIA_AUDITORIA_IA.md` — marco de auditoría.
- `docs/AUDITORIA_2026.md` — hallazgos canónicos (foto al 2026-10-11).
- `docs/ROADMAP.md` — plan por fases y estado de specs.
- `docs/ARQUITECTURA.md` — cómo está construido.
- `docs/BD.md` — esquema de base de datos.
- `docs/REFERENCIA.md` — estándares, frases de defensa, industria descartada.
- `docs/VERIFICACION.md` — lecciones de verificación.
- `docs/ADR/` — decisiones arquitectónicas (001–017).

---

## Infraestructura

- **Tests:** `php tests/run.php`. Smoke + auditoría. Además: `verify_136f.php`, `verify_137g.php` (13/13), `verify_138.php` (89/89), `verify_139d_a.php` (23/23), `verify_139d_b.php` (6/6), `verify_139d_c.php` (22/22). **`verify_139.php` consolidado NO se creó** (el plan de 139d lo mencionaba como criterio de cierre, pero las tandas se verificaron por separado: A 23/23, B 6/6, C 22/22, D 8/8 + restore 5/5, E 0-refs + del + 5× php -l + harness 9/9). Deuda menor, no bloquea 139e.
- **Backup:** `C:\backups\` + `app.js.bak` en raíz. SQL con fecha movidos a `C:\backups\redytelca-migraciones\`.
- **Entorno de desarrollo:** XAMPP (Apache 2.4.58 + PHP 8.2.12 + MariaDB 10.4.32). `http://127.0.0.1/redytelca/`.
- **Skills disponibles:** `sdd`, `redytelca-bd`, `redytelca-auth`, `redytelca-auditoria`.