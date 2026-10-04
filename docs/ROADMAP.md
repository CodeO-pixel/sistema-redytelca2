# Roadmap — REDYTELCA

Plan de correcciones derivado de `docs/AUDITORIA_2026.md`. Cada hallazgo se ataca mediante una spec SDD en `specs/NNN-nombre/`.

## Estados posibles

- **pendiente**: hallazgo sin spec asignada todavía.
- **en progreso**: spec creada, en alguna fase del flujo SDD.
- **cerrado**: spec validada, hallazgo resuelto.
- **diferido**: hallazgo aceptado como deuda técnica consciente, con ADR.

## Convenciones

- Cada spec se numera secuencialmente: `NNN-nombre-corto`.
- Una spec puede cubrir varios hallazgos si están relacionados y comparten archivos.
- Al cerrar una spec, actualizar estado aquí y en `MEMORY.md`.

---

## Bloqueantes de tesis — Línea roja

Estos hallazgos comprometen líneas rojas (§5.4 del marco de auditoría) y deben resolverse antes de la defensa.

| ID | Severidad | Descripción corta | Spec | Estado |
|----|-----------|-------------------|------|--------|
| AUD-2026-B03 | Crítico | Auditoría de operaciones inoperante | 001-fix-auditoria-logs | en progreso |
| AUD-2026-001 | Crítico | RBAC no conforme a ANSI/INCITS 359 | 003-decision-rbac | pendiente |
| AUD-2026-002 | Crítico | Sin gate por operación | 003-decision-rbac | pendiente |
| AUD-2026-B01 | Crítico | `$_SESSION` nativo en login/logout | 004-eliminar-session-nativo | pendiente |
| AUD-2026-B02 | Crítico | `usuarios.estado` no verificado en login | 005-login-estado-usuario | pendiente |
| AUD-2026-B04 | Crítico | `must_change_password` solo en cliente | 006-enforce-must-change-password | pendiente |
| AUD-2026-B05 | Crítico | Revertir pago validado por no-admin | 007-pagos-gate-admin | pendiente |
| AUD-2026-S01 | Crítico | Dump SQL en webroot | 008-mover-dump-fuera-webroot | pendiente |
| AUD-2026-F01 | Crítico | Token en localStorage | 009-token-fuera-localstorage | pendiente |
| AUD-2026-F02 | Crítico | Token en query string | 009-token-fuera-localstorage | pendiente |

## Correcciones estructurales

| ID | Severidad | Descripción corta | Spec | Estado |
|----|-----------|-------------------|------|--------|
| AUD-2026-DOC01 | Medio | Documentación aspiracional | 002-limpiar-documentacion | pendiente |
| AUD-2026-D09 | Alto | Tablas muertas (8 tablas sin uso) | 003-decision-rbac (parcial) | pendiente |

## Hallazgos Altos por dominio

### Backend

| ID | Descripción | Spec | Estado |
|----|-------------|------|--------|
| AUD-2026-B06 | PUT devuelve success con 0 filas | 010-put-idempotencia | pendiente |
| AUD-2026-B07 | INSERT bypassa transiciones | 011-validar-insert-transiciones | pendiente |
| AUD-2026-B08 | UPDATE global en GET | 012-sacar-update-de-get | pendiente |
| AUD-2026-B09 | `api_empresa.php` GET público | 013-proteger-api-empresa | pendiente |
| AUD-2026-B10 | Errores con fuga de información | 014-silenciar-errores-sql | pendiente |
| AUD-2026-B11 | UNIQUE detectado con `str_contains` | 015-errores-unique-por-errorinfo | pendiente |
| AUD-2026-B12 | Facturas duplicadas por periodo | 016-unique-factura-servicio-periodo | pendiente |
| AUD-2026-B13 | `id_plan` hardcodeado en registro | 017-quitar-id-plan-hardcoded | pendiente |
| AUD-2026-B14 | Sin validación de fortaleza de password | 018-fortaleza-passwords | pendiente |
| AUD-2026-B15 | Password temporal predecible | 018-fortaleza-passwords | pendiente |
| AUD-2026-B16 | FK contratos no anticipada | 019-eliminar-cliente-con-contratos | pendiente |
| AUD-2026-B17 | `permisos_listar.php` sin admin | 020-permisos-gate-admin | pendiente |
| AUD-2026-B18 | `permisos_guardar.php` sin confirmación | 020-permisos-gate-admin | pendiente |
| AUD-2026-B19 | DELETE de pagos rechazados | 021-preservar-trazabilidad-pagos | pendiente |

### Base de datos

| ID | Descripción | Spec | Estado |
|----|-------------|------|--------|
| AUD-2026-D01 | `facturas.impuesto_aplicado` sin uso | 022-limpiar-columnas-muertas | pendiente |
| AUD-2026-D02 | `clientes` sin `actualizado_en`/`estado` | 023-timestamps-clientes | pendiente |
| AUD-2026-D03 | `cedula` sin CHECK de formato | 024-validar-cedula | pendiente |
| AUD-2026-D06 | Pagos cruzados entre clientes | 025-validar-pago-pertenencia | pendiente |
| AUD-2026-D07 | CHECK `json_valid` no forzado | 026-validar-json-logs | pendiente |
| AUD-2026-D08 | `sesiones` sin CHECK exclusividad | 027-check-sesion-exclusiva | pendiente |
| AUD-2026-D10 | `equipos.id_servicio` UNIQUE | 028-permitir-multiples-equipos | pendiente |
| AUD-2026-D11 | Falta UNIQUE facturas | 016-unique-factura-servicio-periodo | pendiente |
| AUD-2026-D15 | Índices faltantes | 029-indices-faltantes | pendiente |
| AUD-2026-D16 | Singleton sin garantía de esquema | 030-configuracion-singleton | pendiente |

### Frontend

| ID | Descripción | Spec | Estado |
|----|-------------|------|--------|
| AUD-2026-F03 | `onclick` con datos de BD | 031-sacar-onclick-inline | pendiente |
| AUD-2026-F04 | `window.permissionContext.token` expuesto | 009-token-fuera-localstorage | pendiente |
| AUD-2026-F05 | Polling 5s/15s | 032-reducir-polling | pendiente |
| AUD-2026-F06 | `renderMenu()` re-ejecutado | 032-reducir-polling | pendiente |
| AUD-2026-F07 | Charts destruidos y recreados | 033-optimizar-charts | pendiente |

### Seguridad

| ID | Descripción | Spec | Estado |
|----|-------------|------|--------|
| AUD-2026-S02 | `convert_logo.php` accesible | 034-mover-scripts-fuera-webroot | pendiente |
| AUD-2026-S03 | SVG permitido en upload | 035-validar-uploads-imagenes | pendiente |
| AUD-2026-S04 | Sin rate limiting por IP | 036-rate-limiting-login | pendiente |

### Contexto ISP

| ID | Descripción | Spec | Estado |
|----|-------------|------|--------|
| AUD-2026-ISP04 | Sin idempotencia en facturación | 037-idempotencia-pagos | pendiente |
| AUD-2026-ISP05 | Sin integración hardware | 038-inventario-hw (parcial) | diferido |
| AUD-2026-ISP06 | Sin timeouts externos | 039-timeouts-pdo-http | pendiente |

### No funcionales

| ID | Descripción | Spec | Estado |
|----|-------------|------|--------|
| AUD-2026-NF01 | Sin caché de sesiones | 040-cache-sesiones | pendiente |
| AUD-2026-NF02 | Sin paginación server-side | 041-paginacion-server-side | pendiente |
| AUD-2026-NF04 | Polling O(pestañas × usuarios) | 032-reducir-polling | pendiente |
| AUD-2026-NF05 | `app.js` sin minificar | 042-minificar-assets | pendiente |
| AUD-2026-NF06 | `styles.css` sin minificar | 042-minificar-assets | pendiente |
| AUD-2026-NF07 | Sin Cache-Control | 042-minificar-assets | pendiente |
| AUD-2026-NF08 | Índices faltantes (dup D15) | 029-indices-faltantes | pendiente |

---

## Hallazgos Medios

| ID | Descripción | Spec | Estado |
|----|-------------|------|--------|
| AUD-2026-D04 | `usuarios.estado` en mayúsculas | 043-normalizar-enums | pendiente |
| AUD-2026-D05 | `tareas.estado`/`tickets.estado` VARCHAR | 043-normalizar-enums | pendiente |
| AUD-2026-D12 | `pagos.fecha_validacion` no se limpia | 044-revertir-pago-completo | pendiente |
| AUD-2026-D13 | Tablas sin timestamps | 045-timestamps-faltantes | pendiente |
| AUD-2026-D14 | Charset `general_ci` | 046-charset-unicode | pendiente |
| AUD-2026-B20 | Inconsistencia lectura `id` en DELETE | 047-unificar-delete-params | pendiente |
| AUD-2026-B21 | `logout.php` no usa `revocado_en` | 048-logout-completo | pendiente |
| AUD-2026-B22 | Timing attack en login | 049-login-timing-constante | pendiente |
| AUD-2026-F08 | Sin manejo global de 401 | 050-manejo-401-global | pendiente |
| AUD-2026-F09 | `alert()`/`confirm()` nativos | 051-modales-propios | pendiente |
| AUD-2026-F10 | `app.js` monolítico | 052-modularizar-app-js | pendiente |
| AUD-2026-AR01 | Sin separación de capas | 053-capa-servicios | pendiente |
| AUD-2026-AR02 | `conexion.php` mezcla responsabilidades | 054-separar-conexion | pendiente |
| AUD-2026-AR03 | `app.js` mezcla estado/vista/lógica | 052-modularizar-app-js | pendiente |
| AUD-2026-T03 | Sin linting | 055-linting | pendiente |
| AUD-2026-I03 | Sin HTTPS enforcement | 056-https-enforcement | pendiente |
| AUD-2026-I04 | Sin health check | 057-health-check | pendiente |
| AUD-2026-ISP08 | Sin plan de migración de datos | 058-plan-migracion | pendiente |
| AUD-2026-ISP09 | `portal_cliente` inalcanzable | 059-portal-cliente-real | diferido |
| AUD-2026-ISP10 | `notificaciones` sin uso | 060-notificaciones | diferido |
| AUD-2026-S05 | Sesión 30 días | 061-expiracion-sesion | pendiente |
| AUD-2026-S06 | Sin cabeceras de seguridad | 062-headers-seguridad | pendiente |
| AUD-2026-X01 | RBAC schema vs runtime mismatch | 003-decision-rbac | pendiente |
| AUD-2026-X02 | `must_change_password` en 1 de 4 capas | 006-enforce-must-change-password | pendiente |
| AUD-2026-X03 | `usuarios.estado` decorativa | 005-login-estado-usuario | pendiente |
| AUD-2026-X04 | `equipos` sin flujo end-to-end | 063-equipos-flujo-completo | pendiente |
| AUD-2026-X05 | Transiciones en UPDATE, no INSERT | 011-validar-insert-transiciones | pendiente |

---

## Hallazgos Bajos

| ID | Descripción | Spec | Estado |
|----|-------------|------|--------|
| AUD-2026-F11 | `window.selectedIds` sin limpieza | 064-limpiar-selected-ids | pendiente |
| AUD-2026-F12 | Sin CSP/HSTS/X-Frame-Options | 062-headers-seguridad | pendiente |
| AUD-2026-S07 | Credenciales hardcodeadas | 065-env-vars-bd | pendiente |
| AUD-2026-S08 | `EMULATE_PREPARES` no seteado | 065-env-vars-bd | pendiente |
| AUD-2026-AR04 | `router.php` dead code | 066-eliminar-dead-code | pendiente |
| AUD-2026-I01 | Sin backups | 067-backups-automatizados | pendiente |
| AUD-2026-I02 | Sin observabilidad | 068-observabilidad | pendiente |
| AUD-2026-T01 | Cero tests | 069-ampliar-cobertura-tests | pendiente |
| AUD-2026-T02 | Sin CI/CD | 070-ci-cd | pendiente |
| AUD-2026-DOC02 | Sin ADRs | 071-adrs-faltantes | pendiente |

---

## Specs planificadas

Listado completo de specs, en orden de ejecución recomendado.

| NNN | Nombre | Hallazgos cubiertos | Bloqueante |
|-----|--------|---------------------|-----------|
| 001 | fix-auditoria-logs | B03 | Sí |
| 002 | limpiar-documentacion | DOC01 | No |
| 003 | decision-rbac | 001, 002, D09, X01 | Sí |
| 004 | eliminar-session-nativo | B01 | Sí |
| 005 | login-estado-usuario | B02, X03 | Sí |
| 006 | enforce-must-change-password | B04, X02 | Sí |
| 007 | pagos-gate-admin | B05 | Sí |
| 008 | mover-dump-fuera-webroot | S01 | Sí |
| 009 | token-fuera-localstorage | F01, F02, F04 | Sí |
| 010 | put-idempotencia | B06 | No |
| 011 | validar-insert-transiciones | B07, X05 | No |
| 012 | sacar-update-de-get | B08 | No |
| 013 | proteger-api-empresa | B09 | No |
| 014 | silenciar-errores-sql | B10 | No |
| 015 | errores-unique-por-errorinfo | B11 | No |
| 016 | unique-factura-servicio-periodo | B12, D11 | No |
| 017 | quitar-id-plan-hardcoded | B13 | No |
| 018 | fortaleza-passwords | B14, B15 | No |
| 019 | eliminar-cliente-con-contratos | B16 | No |
| 020 | permisos-gate-admin | B17, B18 | No |
| 021 | preservar-trazabilidad-pagos | B19 | No |
| 022 | limpiar-columnas-muertas | D01 | No |
| ... | (resto de specs hasta 071) | ... | ... |

---

## Cómo se actualiza este archivo

1. Al crear una spec: cambiar estado de `pendiente` a `en progreso`.
2. Al cerrar una spec: cambiar estado a `cerrado`.
3. Al diferir: cambiar estado a `diferido` y crear ADR en `docs/ADR/`.
4. Al añadir hallazgos nuevos (re-auditoría): añadir fila con spec asignada.

## Referencias

- `docs/AUDITORIA_2026.md` — informe completo con evidencia.
- `docs/METODOLOGIA_DESARROLLO_IA.md` — flujo SDD.
- `docs/constitution.md` — principios.
- `specs/` — carpeta con las specs.