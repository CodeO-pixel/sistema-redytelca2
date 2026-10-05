
---

# ARTEFACTO 3 — `specs/001-fix-auditoria-logs/tasks.md`

**📁 Ruta:** `redytelca/specs/001-fix-auditoria-logs/tasks.md`

**📋 Pegar esto:**

```markdown
# Tasks 001 — Fix Auditoría Logs

- [ ] **T1**. Añadir guard CLI a `conexion.php` + crear `tests/audit_context.test.php` (rojo).
  RNF-3, RNF-4.
  Hecho cuando: `php tests/run.php` ejecuta el nuevo test y falla con "setAuditContext() no existe" (razón correcta, no muerte por require de conexion.php).

- [ ] **T2**. Implementar `setAuditContext(PDO, string, ?int, ?int)` en `conexion.php` con `intval()` directo.
  RF-1, RF-2, RF-3.
  Hecho cuando: `php tests/run.php` pasa en verde los 5 casos del test de auditoría.

- [ ] **T3**. Invocar `setAuditContext()` en `requireStaffAuth()` y `requireClientAuth()`.
  RF-1, RF-2, RF-6.
  Hecho cuando: test sigue verde; inspección de código confirma la llamada tras la validación de sesión y antes del `return`.

- [ ] **T4**. Comentario RNF-3 junto a `new PDO(...)` en `conexion.php`.
  RNF-3.
  Hecho cuando: comentario presente con los 3 puntos (variables por conexión, NO PDO::ATTR_PERSISTENT, reset si se habilita persistencia).

- [ ] **T5**. Prueba manual end-to-end.
  Criterio de finalización.
  Hecho cuando: tras login como staff y ejecutar POST a `registrar_cliente.php` con datos válidos, `SELECT * FROM logs WHERE tabla_afectada='clientes' AND accion='INSERT' ORDER BY id_log DESC LIMIT 1` muestra `tipo_actor='staff'` e `id_usuario` coincidente con el usuario autenticado.