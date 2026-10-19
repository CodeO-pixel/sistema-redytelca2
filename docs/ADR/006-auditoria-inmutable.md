# ADR 006 — Auditoría inmutable vía triggers

- **Estado**: aceptado
- **Fecha**: Fase 0 (2026-10-05), reforzado en spec 019
- **Contexto**: trazabilidad de operaciones de negocio.

## Contexto

REDYTELCA necesita trazabilidad de quién hizo qué, cuándo, sobre qué
registro y con qué valores antes/después. Esto es un requisito de
línea roja ISP (auditabilidad de operaciones) y un elemento de defensa
ante el jurado.

Existen tres estrategias comunes:

1. **Auditoría en aplicación**: cada endpoint decide qué loguear.
2. **Auditoría por triggers de BD**: la BD registra automáticamente.
3. **Auditoría por ORM/eventos**: framework-specific.

REDYTELCA no usa ORM (stack sin frameworks, ADR 002). La auditoría en
aplicación es frágil: depende de que cada endpoint se acuerde de
llamar a `logWrite()`.

## Decisión

Auditoría **en base de datos, vía triggers** AFTER INSERT/UPDATE/DELETE
sobre las tablas de negocio. Los triggers escriben en `logs`.

Los triggers leen variables de sesión MySQL `@app_usuario_id`,
`@app_credencial_id` y `@app_tipo_actor`. Estas variables son seteadas
por `setAuditContext()` en `conexion.php`, invocada desde
`requireStaffAuth()` y `requireClientAuth()`.

La tabla `logs` es **inmutable**: triggers BEFORE UPDATE y BEFORE
DELETE bloquean cualquier modificación (SQLSTATE 45000).

## Alternativas descartadas

### Auditoría en aplicación (cada endpoint llama a un logger)

- **Ventaja**: control total sobre el formato y el contenido.
- **Razón de descarte**: frágil. Cualquier endpoint que se olvide de
  llamar al logger deja un hueco. En Fase 1 la auditoría estaba
  inoperante (todos los logs con `tipo_actor='sistema'`) precisamente
  por esto: los triggers existían pero nadie seteaba las variables.

### Auditoría con middleware centralizado

- **Ventaja**: centralizado.
- **Razón de descarte**: complica el middleware, requiere conocer todas
  las tablas antes/después, y rompe la separación de responsabilidades
  (el middleware de auth no debería saber de tablas de negocio).

### Sin auditoría (solo logs de acceso)

- **Ventaja**: simplicidad.
- **Razón de descarte**: viola la línea roja ISP "Auditabilidad de
  operaciones". Bloqueante para la defensa.

## Consecuencias

### Positivas

- Auditoría automática: imposible olvidarse.
- Inmutable: los logs no se pueden modificar ni eliminar, ni siquiera
  por un admin a través de la aplicación.
- Snapshot completo: cada log guarda `old_value` y `new_value` como
  JSON con los campos relevantes.
- Coherente con la lógica de negocio en PHP: la BD se encarga de la
  trazabilidad, no de las reglas.

### Negativas

- Los triggers escriben en cada operación, aunque nadie esté auditando.
  Costo de escritura pequeño pero real.
- Requiere que `setAuditContext()` se invoque correctamente. Spec 019
  lo resolvió y lo verificó end-to-end.
- Las variables `@app_*` son por conexión. Si se habilitara
  `PDO::ATTR_PERSISTENT`, se contaminarían entre requests. Documentado
  en `conexion.php` (RNF-3 de spec 019).
- Los logs crecen indefinidamente. No hay política de retención todavía.

### Neutras

- `logs.ip_address` está siempre NULL. `user_agent` no se registra.
- Los eventos LOGIN/LOGOUT/LOGIN_FALLIDO están en el ENUM de `accion`
  pero no se registran todavía desde `login.php`/`logout.php`.

## Referencias

- `bd_redytelca.sql` — tabla `logs`, triggers `trg_*_audit_*`,
  `trg_logs_inmutable_update`, `trg_logs_inmutable_delete`.
- `conexion.php` — `setAuditContext()`, `requireStaffAuth()`,
  `requireClientAuth()`.
- `specs/019-fix-auditoria-logs/spec.md` — spec que cerró la
  inoperancia de la auditoría.
- `docs/BD.md` — esquema de auditoría.