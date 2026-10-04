# Auditoría REDYTELCA 2026

Informe estático de auditoría sobre el sistema de gestión ISP REDYTELCA.
Fecha: octubre 2026. Método: 6 pasadas × 10 capas × contexto ISP.
Estándares: ANSI/INCITS 359, OWASP ASVS 5.0, ISO/IEC 25010:2023, ISO/IEC 27011:2024, ITU-T X.1051, NIST SP 800-63B Rev. 4.

## Resumen ejecutivo

- Críticos: 8
- Altos: 40
- Medios: 24
- Bajos: 8
- Líneas rojas comprometidas: 6

Veredicto global: **NO CONFORME EN FASE 1 (Estructural)**. El esquema declara RBAC (ANSI/INCITS 359) pero el código no lo implementa.

---

## LÍNEAS ROJAS COMPROMETIDAS

1. Protección de PII (S01, F01, F02)
2. Aislamiento de clientes (B04)
3. RBAC formal y SoD (001, 002)
4. Integridad de facturación (B05, B07, B12, B13, ISP04)
5. Auditabilidad de operaciones (B03, ISP02)
6. Credenciales nunca en texto plano (B15, S07)

---

## HALLAZGOS CRÍTICOS (8)

### AUD-2026-001 — RBAC no conforme a ANSI/INCITS 359
- Capa: Autorización
- Estándar: ANSI/INCITS 359 (RBAC Core)
- Evidencia: tabla `usuario_rol` (N:M) existe en `bd_redytelca.sql` pero ningún PHP la consulta. `conexion.php` (`requireStaffAuth`), `login.php`, `auth_state.php` leen `usuarios.id_rol` (escalar 1:N).
- Impacto: el sistema no implementa RBAC Core. `usuario_rol` es artefacto muerto.
- Línea roja: sí (RBAC formal).

### AUD-2026-002 — Sin gate por operación
- Capa: Autorización
- Evidencia: `conexion.php` → `requirePageAccess()` verifica existencia en `rol_modulo_pagina`. No consulta `rol_modulo_permiso` ni `permisos`.
- Impacto: un rol con visibilidad de "Pagos" puede crear/editar/eliminar. Técnico con acceso a Clientes puede borrar clientes.
- Línea roja: sí (SoD).

### AUD-2026-B01 — `$_SESSION` nativo en login/logout
- Capa: Backend
- Evidencia: `login.php` usa `session_start()` y `$_SESSION['auth_token']`. `logout.php` igual.
- Viola: regla 7 de `copilot-instructions.md`.
- Impacto: mecanismo de auth paralelo al de tokens.

### AUD-2026-B02 — `usuarios.estado` no verificado en login
- Capa: Backend
- Evidencia: `login.php` solo valida `bloqueado_hasta`. Ignora `estado` ('Activo'/'Inactivo'/'Bloqueado').
- Impacto: usuario Inactivo o Bloqueado puede loguearse.

### AUD-2026-B03 — Auditoría inoperante
- Capa: Backend
- Evidencia: triggers `trg_*_audit_*` usan `COALESCE(@app_usuario_id, NULL)`. Búsqueda de `@app_usuario_id` en los 25 PHP: 0 resultados.
- Impacto: todos los logs con `id_usuario=NULL, tipo_actor='sistema'`.
- Línea roja: sí (auditabilidad).
- Estado: en corrección (spec 001-fix-auditoria-logs).

### AUD-2026-B04 — `must_change_password` solo enforced en cliente
- Capa: Backend
- Evidencia: `auth_state.php` devuelve el flag. `app.js` bloquea el modal. Ningún endpoint backend verifica el flag.
- Impacto: usuario con contraseña temporal puede llamar directamente a `api_*.php`.
- Línea roja: sí (aislamiento).

### AUD-2026-B05 — Revertir pago validado por no-admin
- Capa: Backend
- Evidencia: `api_pagos.php` PUT. El gate `$cambioDeEstadoSensible && id_rol !== 1` solo aplica a `['validado','rechazado']`. `'pendiente'` no está.
- Impacto: Operador puede revertir pagos validados.
- Línea roja: sí (facturación).

### AUD-2026-S01 — Dump SQL en webroot
- Capa: Infraestructura
- Evidencia: `bd_redytelca.sql` en raíz pública. Sin bloqueo `.htaccess` ni regla de servidor.
- Impacto: PII de clientes, hashes bcrypt, topología de red expuestos.
- Línea roja: sí (PII).

### AUD-2026-F01 — Token en localStorage
- Capa: Frontend
- Evidencia: `app.js` → `persistSessionContext()` guarda el token en localStorage.
- Impacto: XSS puede robar token de 30 días.
- Línea roja: sí (PII).

### AUD-2026-F02 — Token en query string
- Capa: Frontend
- Evidencia: `app.js` → `restoreSession()` envía token en `?token=...`.
- Impacto: access logs del servidor capturan el token.
- Línea roja: sí (PII).

---

## HALLAZGOS ALTOS (40)

### Backend

### AUD-2026-B06 — PUT devuelve success con 0 filas afectadas
- Evidencia: patrón `$ok = $stmt->execute([...]); 'message' => $ok ? 'success' : 'error'`.
- Archivos: `api_tareas.php`, `api_tickets.php`, `api_nodos.php`, `api_olts.php`, `api_naps.php`, `api_planes.php`, `api_contratos.php`.
- Impacto: cliente cree que actualizó algo que no existía.

### AUD-2026-B07 — INSERT bypassa validación de transiciones
- Evidencia: triggers `trg_*_validar_transicion` son BEFORE UPDATE. No hay BEFORE INSERT.
- Archivos: `bd_redytelca.sql` + todos los POST.
- Impacto: POST con `estado='pagada'` crea factura pagada sin pago.

### AUD-2026-B08 — UPDATE global en GET
- Evidencia: `api_contratos.php` y `api_facturas.php` ejecutan `UPDATE ... SET estado=...` en cada GET.
- Impacto: viola RFC 7231 (GET seguro). O(N) por request.

### AUD-2026-B09 — `api_empresa.php` GET público
- Evidencia: GET sin `requireStaffAuth`.
- Impacto: info disclosure (`impuesto_porcentaje`, `dia_facturacion`, `dias_gracia_mora`, `monto_recargo_mora`).

### AUD-2026-B10 — Errores con fuga de información
- Evidencia: `'Error ...: ' . $e->getMessage()` expone mensajes de MariaDB al cliente.
- Archivos: `api_planes.php`, `api_nodos.php`, `api_olts.php`, `api_naps.php`, `api_servicios.php`, `api_facturas.php`, `api_contratos.php`, `api_pagos.php`.

### AUD-2026-B11 — UNIQUE violations detectadas con `str_contains`
- Evidencia: `api_equipos.php` distingue UNIQUE por `str_contains($e->getMessage(), 'direccion_mac')`.
- Impacto: frágil ante cambios de versión MariaDB.

### AUD-2026-B12 — `api_facturas.php` POST sin verificar duplicado
- Evidencia: no hay UNIQUE `(id_servicio, periodo)` ni validación PHP.
- Impacto: dos facturas del mismo mes al mismo servicio.

### AUD-2026-B13 — `registrar_cliente.php` id_plan=1 hardcodeado
- Evidencia: `VALUES ('activo', ?, 1, 'Principal', ...)`.
- Impacto: cliente queda en Plan Básico hasta cambio manual.

### AUD-2026-B14 — `cambiar_password.php` sin validación de fortaleza
- Evidencia: sin longitud mínima, sin verificación contra listas comprometidas.
- Viola: NIST SP 800-63B §5.1.1.2 (mínimo 15 chars cuando es único factor).

### AUD-2026-B15 — `usuarios_crear.php` password temporal predecible
- Evidencia: `$defaultPassword = 'Redytelca123!'` hardcoded.
- Línea roja: sí (credenciales).

### AUD-2026-B16 — `cliente_eliminar.php` sin anticipar FK contratos
- Evidencia: FK `contratos.id_servicio` RESTRICT no considerada.
- Impacto: operación falla con error genérico, rollback sin mensaje claro.

### AUD-2026-B17 — `permisos_listar.php` sin admin
- Evidencia: no usa `$minRoleId`. Cualquier staff puede listar la matriz completa de páginas/roles.

### AUD-2026-B18 — `permisos_guardar.php` reemplaza sin confirmación
- Evidencia: DELETE completo de `rol_modulo_pagina` + INSERT. Sin safeguard para rol 1.

### AUD-2026-B19 — `api_pagos.php` DELETE permite borrar rechazados
- Evidencia: solo bloquea `estado === 'validado'`.
- Impacto: trazabilidad de rechazos perdida.

### Base de datos

### AUD-2026-D01 — `facturas.impuesto_aplicado` nunca usado
- Evidencia: columna persiste snapshot pero ningún cálculo la lee.
- Impacto: columna muerta.

### AUD-2026-D02 — `clientes` sin `actualizado_en` ni `estado`
- Impacto: no hay forma de saber si un cliente cambió o está inactivo.

### AUD-2026-D03 — `clientes.cedula` sin CHECK de formato
- Evidencia: semilla con formatos `'3192873'`, `'V-12345678'`, `'E-87654321'`.

### AUD-2026-D06 — `pagos` no valida cliente/servicio vs factura
- Evidencia: `api_pagos.php` POST permite pagar factura de cliente B con id_cliente A.
- Línea roja: sí (facturación).

### AUD-2026-D07 — CHECK `json_valid` no forzado
- Evidencia: MariaDB 10.4 ignora CHECK. Validación real ausente.
- Archivos: `bd_redytelca.sql` (`logs.old_value`, `new_value`).

### AUD-2026-D08 — `sesiones` sin CHECK "exactamente uno"
- Evidencia: `id_usuario` e `id_credencial` ambos nullable sin restricción mutua.

### AUD-2026-D09 — Tablas muertas
- Evidencia: `usuario_rol`, `rol_modulo_permiso`, `permisos`, `sesion_rol_activo`, `clientes_credenciales`, `notificaciones`, `ticket_respuestas`, `rol_modulo`.
- Impacto: 8 tablas sin endpoints que las usen.

### AUD-2026-D10 — `equipos.id_servicio` UNIQUE
- Impacto: impide tener ONT + Router en el mismo servicio.

### AUD-2026-D11 — Falta UNIQUE `facturas(id_servicio, periodo)`
- Línea roja: sí (facturación).

### AUD-2026-D15 — Índices faltantes
- Evidencia: `facturas(estado, fecha_vencimiento)`, `pagos(estado)`, `pagos(id_factura, estado)`, `logs(creado_en)`, `sesiones(expira_en)` sin índice.

### AUD-2026-D16 — `configuracion_empresa` no garantiza singleton
- Evidencia: asume que la app siempre usa `id_config=1`.

### Frontend

### AUD-2026-F03 — `onclick` construido con datos de BD
- Evidencia: `app.js` en `renderUsersPage`, `renderRoleList`, `renderTasksPage`, `renderTicketsPage`.
- Impacto: `&#39;` se decodifica a `'` antes de que JS lo lea. Frágil.

### AUD-2026-F04 — `window.permissionContext.token` expuesto
- Evidencia: `app.js` → `login()`.
- Impacto: cualquier script del mismo origen lo lee.

### AUD-2026-F05 — Polling 5s/15s por pestaña
- Evidencia: `setInterval(loadClientes, 5000)`, `setInterval(restoreSession, 15000)`.
- Impacto: con 10 pestañas = 50 conexiones cada 5s.

### AUD-2026-F06 — `renderMenu()` re-ejecutado cada 15s
- Evidencia: `restoreSession(false)` redibuja el sidebar.
- Impacto: flicker visual.

### AUD-2026-F07 — Charts destruidos y recreados
- Evidencia: `updateDashboardData()` invocado desde múltiples loaders.

### Seguridad

### AUD-2026-S02 — `Logos/convert_logo.php` accesible
- Evidencia: script PHP en webroot, ejecutable sin auth.

### AUD-2026-S03 — SVG permitido en upload de logo
- Evidencia: `api_empresa.php` acepta `image/svg+xml`.
- Impacto: XSS si el SVG se sirve directo.

### AUD-2026-S04 — Sin rate limiting por IP en login
- Evidencia: solo lockout por cuenta. Distributed password spraying no mitigado.

### Contexto ISP

### AUD-2026-ISP04 — Sin idempotencia en facturación
- Evidencia: no hay token de idempotencia en `pagos`.
- Línea roja: sí (facturación).

### AUD-2026-ISP05 — Sin integración real con hardware
- Evidencia: no hay SNMP, TR-069, SSH a OLTs/routers.

### AUD-2026-ISP06 — Sin timeouts en integraciones externas
- Evidencia: `api_*.php` sin `PDO::ATTR_TIMEOUT` ni timeouts HTTP.

### No funcionales

### AUD-2026-NF01 — Sin caché de sesiones
- Evidencia: cada request hace `SELECT FROM sesiones JOIN usuarios`.

### AUD-2026-NF02 — Sin paginación server-side en 10 endpoints
- Archivos: `api_tareas.php`, `api_tickets.php`, `api_equipos.php`, `api_nodos.php`, `api_olts.php`, `api_naps.php`, `api_planes.php`, `api_contratos.php`, `api_facturas.php`, `api_pagos.php`.
- Impacto: frontend pagina client-side sobre lista completa.

### AUD-2026-NF04 — Polling agresivo escala O(pestañas × usuarios)
- Ver F05.

### AUD-2026-NF05 — `app.js` sin minificación
- Impacto: 2500+ líneas parseadas por carga.

### AUD-2026-NF06 — `styles.css` sin minificación

### AUD-2026-NF07 — Sin Cache-Control en respuestas

### AUD-2026-NF08 — Índices faltantes (duplicado con D15 en código)

---

## HALLAZGOS MEDIOS (24)

### AUD-2026-D04 — `usuarios.estado` en mayúsculas
- Evidencia: 'Activo' vs resto en minúsculas.

### AUD-2026-D05 — `tareas.estado` y `tickets.estado` VARCHAR(30)
- Impacto: valores arbitrarios permitidos.

### AUD-2026-D12 — `pagos.fecha_validacion` no se limpia al revertir

### AUD-2026-D13 — Tablas sin `creado_en`/`actualizado_en`
- Archivos: `pagos`, `contratos`, `naps`, `nodos`, `olts`, `equipos`.

### AUD-2026-D14 — Charset `utf8mb4_general_ci`
- Impacto: ordenamiento de tildes/ñ incorrecto.

### AUD-2026-B20 — Inconsistencia lectura `id` en DELETE
- Evidencia: `$_GET['id']` vs body.

### AUD-2026-B21 — `logout.php` no usa `revocado_en` ni `motivo_cierre`
- Impacto: auditoría de cierres de sesión inoperante.

### AUD-2026-B22 — Timing attack en login
- Evidencia: rama de fallo actualiza `intentos_fallidos` solo si usuario existe.

### AUD-2026-F08 — Sin manejo global de 401
- Impacto: cuando expira el token, fetches fallan silenciosamente.

### AUD-2026-F09 — `alert()` y `confirm()` nativos
- Evidencia: >30 ubicaciones en `app.js`.

### AUD-2026-F10 — `app.js` monolítico de ~2500 líneas
- Impacto: mantenibilidad comprometida.

### AUD-2026-AR01 — Sin separación de capas
- Evidencia: `sincronizarEstadoServicio()` en `api_contratos.php`, `reconciliarFactura()` en `api_pagos.php`.

### AUD-2026-AR02 — `conexion.php` mezcla 5 responsabilidades
- CORS + PDO + hashing + auth staff + auth cliente + autorización por página.

### AUD-2026-AR03 — `app.js` mezcla estado/vista/negocio

### AUD-2026-T03 — Sin linting
- Evidencia: ni ESLint, ni PHPStan, ni PHP_CodeSniffer.

### AUD-2026-I03 — Sin HTTPS enforcement
- Evidencia: `conexion.php` no verifica `$_SERVER['HTTPS']`.

### AUD-2026-I04 — Sin health check

### AUD-2026-DOC01 — Documentación aspiracional
- Evidencia: `DOCUMENTACION.md`, `README.md`, `IMPROVEMENTS.md` describen JWT/refresh/2FA que no existen.

### AUD-2026-ISP08 — Sin plan de migración de datos vivos

### AUD-2026-ISP09 — `origen='portal_cliente'` inalcanzable
- Evidencia: `portal_cliente.php` es stub.

### AUD-2026-ISP10 — `notificaciones` no usada

### AUD-2026-S05 — Duración de sesión 30 días
- Viola: NIST SP 800-63B recomienda reautenticación periódica.

### AUD-2026-S06 — Sin cabeceras de seguridad
- Falta CSP, HSTS, X-Frame-Options, X-Content-Type-Options.

### AUD-2026-X01 — RBAC schema vs runtime mismatch
- Consistencia cruzada: BD declara N:M, código usa 1:N.

### AUD-2026-X02 — `must_change_password` enforce en 1 de 4 capas

### AUD-2026-X03 — `usuarios.estado` decorativa en todas las capas

### AUD-2026-X04 — `equipos` sin flujo end-to-end

### AUD-2026-X05 — `transicion_estado` cubre UPDATE, no INSERT

---

## HALLAZGOS BAJOS (8)

### AUD-2026-F11 — `window.selectedIds` sin limpieza

### AUD-2026-F12 — Sin CSP/X-Content-Type-Options/X-Frame-Options/HSTS

### AUD-2026-S07 — Credenciales hardcodeadas en `conexion.php`
- `user='root', pass=''`.

### AUD-2026-S08 — `PDO::ATTR_EMULATE_PREPARES` no seteado a `false`

### AUD-2026-AR04 — `router.php` dead code
- Sin `.htaccess` que lo invoque.

### AUD-2026-I01 — Sin backups visibles ni política de retención

### AUD-2026-I02 — Sin observabilidad (logs estructurados, métricas, trazas)

### AUD-2026-T01 — Cero tests automatizados

### AUD-2026-T02 — Sin CI/CD

### AUD-2026-DOC02 — Sin ADRs

### AUD-2026-D01 (dup) — ver Críticos D01

---

## ROADMAP DE CORRECCIÓN

Los hallazgos se atacan vía specs SDD, en orden de severidad.

- Spec 001: AUD-2026-B03 (auditoría inoperante) — en curso
- Spec 002: limpiar documentación obsoleta
- Spec 003+: los 7 críticos restantes (001, 002, B01, B02, B04, B05, S01, F01/F02)
- Spec 00X+: los 40 altos
- Spec 00X+: los 24 medios
- Spec 00X+: los 8 bajos

Cada spec referencia su hallazgo origen en `spec.md`. Cada cierre se marca en `docs/ROADMAP.md`.

---

## NOTAS DE ALCANCE

- Este informe es estático. La Pasada 6 (dinámica) no se ejecutó.
- Los hallazgos se clasificaron por severidad técnica y por dominio ISP.
- Línea roja = no admite excepción sin aprobación formal.
- Independencia del auditor: reducida (autor = auditor). Ver §12.3 del marco.