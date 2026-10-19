# ADR 013 — Refactor completo de BD previo a revisión

- **Estado**: aceptado
- **Fecha**: 2026-10-12
- **Contexto**: revisión de BD inminente. Se requiere que el modelo de datos
  refleje el dominio completo de un sistema ISP profesional.

## Contexto

`bd_redytelca.sql` cubre hoy el núcleo funcional (clientes, servicios,
facturas, pagos, tickets, tareas, infraestructura, RBAC), pero carece de
entidades que la industria considera estándar: pedidos/instalaciones,
notificaciones staff, historial de movimientos de equipos, notas de
crédito/débito, categorías de tickets, SLA, contactos múltiples, etiquetas,
email transaccional.

Además, existen hallazgos canónicos abiertos de auditoría sobre integridad
de la BD (D02, D03, D08, D13, D16, D19, D21) que deben cerrarse.

## Decisión

Ejecutar un refactor consolidado de `bd_redytelca.sql` con los siguientes
bloques:

### Bloque A — Correcciones a tablas existentes

1. `clientes`: +`actualizado_en`, +`estado`, +`segmento`, CHECK cédula,
   +2 índices.
2. `pagos`: +`creado_en`, +`actualizado_en`, +`idx_pago_fecha`.
3. `nodos`: +`creado_en`, +`actualizado_en`.
4. `olts`: +`creado_en`, +`actualizado_en`.
5. `naps`: +`creado_en`, +`actualizado_en`.
6. `equipos`: +`creado_en`, +`actualizado_en`, +`idx_equipo_estado`.
7. `facturas`: +CHECK periodo, +`idx_factura_periodo`.
8. `sesiones`: +CHECK exactamente uno (id_usuario XOR id_credencial).
9. `configuracion_empresa`: +CHECK `id_config = 1`.
10. `logs`: +`idx_log_accion_fecha`.
11. `tickets`: +`id_categoria` FK, +`sla_vence_en`, +2 índices.
12. `notificaciones`: +`tipo_evento`, +`url_destino`, +`leida_en`, +índice.
13. `ip_asignaciones`: +UNIQUE `uq_ip_activa (ip, estado)`.
14. `servicios`: +`creado_en`, +`actualizado_en`.   ← añadido 2026-10-13
15. `planes`: +`creado_en`, +`actualizado_en`.      ← añadido 2026-10-13
16. `contratos`: +`actualizado_en`.                 ← añadido 2026-10-13

### Bloque B — Tablas nuevas (17)

De ADR 010–012 y huecos identificados:

- `notificaciones_staff`
- `pedidos`
- `pedido_materiales`
- `pedido_asignaciones`
- `equipo_movimientos`
- `notas_credito`
- `notas_debito`
- `categorias_ticket`
- `sla_definiciones`
- `cliente_contactos`
- `cliente_notas`
- `etiquetas`
- `cliente_etiquetas`
- `email_plantillas`
- `email_log`
- `metodos_pago` (de la spec 113, hallazgo D37)
- `plan_historial_precios` (de la spec 105, hallazgo D26)

### Bloque C — Triggers (15 sets + 2 especiales)

13 sets para las tablas nuevas del Bloque B (excepto `email_log` y
`notificaciones_staff`, que se auditan vía aplicación). Más:

- `trg_contratos_unico_vigente_insert` y `trg_contratos_unico_vigente_update`
  (resuelve D27, spec 106).
- `trg_logs_validar_json` — **descartado**. Ver §Resolución P2.

### Bloque D — Vistas (5)

- `v_estado_cuenta_cliente`
- `v_clientes_360`
- `v_equipos_asignados`
- `v_ip_asignadas`
- `v_facturas_con_saldo`

### Bloque E — Seed (5 catálogos)

- `categorias_ticket` (4 filas)
- `sla_definiciones` (3 filas)
- `email_plantillas` (6 filas)
- `etiquetas` (5 filas)
- `metodos_pago` (4 filas)

### Bloque F — Modificaciones estructurales

1. **Collation global** a `utf8mb4_unicode_ci` (todas las tablas y columnas
   varchar/text). Resuelve D14 (spec 015).
2. **DELETE `rol` id=4 "tecnico"** huérfano. Resuelve D17 (spec 075).
3. `equipos.num_puerto_nap` de `tinyint(4)` a `smallint(5)`. Resuelve D33
   (spec 109).
4. Añadir `factura_lineas.id_servicio` FK → `servicios`. Resuelve D34
   (spec 110).
5. **`ON UPDATE RESTRICT` explícito** en todas las FKs que apuntan a PKs
   mutables. Ver §Resolución P3.
6. Deprecar `servicios.ip_asignada` marcándola como legacy y dejando FK
   opcional a `ip_asignaciones.id_asignacion`. Resuelve parcialmente D23
   (spec 103).

### Bloque G — Archivo nuevo fuera de la BD

- `bd_redytelca_schema.sql` — DDL puro sin datos, versionable, para que
  el jurado pueda importar el esquema sin PII. **Vive en la raíz del
  repo, junto a `bd_redytelca.sql`.** Resuelve D18 (spec 089).

## Resoluciones P1–P4

### P1 — Estrategia de migración: Big Bang con backup

**Decisión:** ejecutar todos los cambios en una sola operación sobre la BD
de desarrollo, con backup previo obligatorio.

**Fundamento:** el patrón Expand-Contract (Parallel Change) es el estándar
de industria para migraciones sin downtime en sistemas en producción
(OpenStack, equipos de ingeniería de datos en O'Reilly). Requiere
compatibilidad bidireccional código↔esquema durante el periodo de
transición. Es innecesario en este contexto: la BD es de desarrollo, no hay
usuarios concurrentes, el jurado revisa el modelo completo. El backup es el
rollback.

**Alternativa descartada:** Expand-Contract. Razón: overhead innecesario
sin tráfico en vivo.

### P2 — Validación JSON en logs: no tocar

**Decisión:** el CHECK `JSON_VALID()` existente en `logs.old_value` y
`logs.new_value` funciona correctamente en MariaDB 10.4.3+. No se requiere
cambio.

**Fundamento:** verificado empíricamente. Insertar `'{invalid}'` en una
columna `LONGTEXT CHECK (JSON_VALID(...))` produce `ERROR 4025 CONSTRAINT
failed`. Documentación oficial de MariaDB confirma que el CHECK se aplica
desde 10.4.3.

**Consecuencia:** el hallazgo D07 (spec 009) se cierra como **falso
positivo**. No era un hallazgo. Se retira del roadmap.

### P3 — `ON UPDATE RESTRICT` explícito

**Decisión:** todas las FKs que apuntan a PKs mutables llevan `ON UPDATE
RESTRICT` explícito.

**Fundamento:**
- Oracle **no permite** `ON UPDATE CASCADE` en absoluto; los PKs se
  consideran inmutables por diseño.
- MySQL recomienda "if in doubt, RESTRICT".
- MariaDB **no dispara triggers de auditoría** cuando hay cascadas. Con
  `CASCADE`, cambiar un PK actualizaría silenciosamente todas las filas
  hijas sin dejar rastro en `logs`, violando el principio de auditoría
  inmutable (ADR 006).

**Alternativa descartada:** `ON UPDATE CASCADE`. Razón: rompe auditoría.

### P4 — UNIQUE parcial en contratos: trigger, no columna generada

**Decisión:** implementar `trg_contratos_unico_vigente_insert` y
`trg_contratos_unico_vigente_update` que rechacen con `SIGNAL SQLSTATE
'45000'` la creación o transición a `vigente` si ya existe otro contrato
`vigente` para el mismo `id_servicio`.

**Fundamento:**
- MySQL/MariaDB **no soportan partial indexes**. No hay `WHERE` en
  `CREATE UNIQUE INDEX`.
- El truco de columna generada `vigente_unico = CASE WHEN estado='vigente'
  THEN id_servicio END` + UNIQUE **no funciona**: MariaDB permite múltiples
  `NULL` en una columna UNIQUE. Los contratos no-vigentes tendrían
  `vigente_unico = NULL` y no colisionarían entre sí. Esto es exactamente
  el hallazgo CODE-02 ya documentado con `equipos.es_principal`.
- El patrón estándar de industria cuando se necesita unicidad condicional en
  MySQL/MariaDB es trigger BEFORE INSERT/UPDATE con `SIGNAL`.

**Alternativa descartada:** columna generada + UNIQUE. Razón: no restringe
(CODE-02).

## Consecuencias

### Positivas

- Modelo de datos completo para la revisión.
- Cierra hallazgos D02, D03, D08, D13, D14, D16, D17, D19, D21, D26, D27,
  D33, D34, D37.
- Backend posterior encuentra el esquema listo.
- Decisiones P1–P4 alineadas con industria y documentadas.

### Negativas

- Cambio masivo de una sola vez. Rollback requiere restauración completa
  del backup.
- Tablas nuevas sin uso inmediato hasta que el backend se implemente.
- El cambio global de collation reescribe todas las filas. Requiere
  verificación previa de no-colisión de índices UNIQUE bajo la nueva
  collation.
- El cambio global de collation reescribe todas las filas. Requiere
  verificación previa de no-colisión de índices UNIQUE bajo la nueva
  collation. Se ejecuta tabla por tabla, con parada ante el primer
  fallo (ver prompt de AI Studio, cláusula R7): el refactor puede quedar
  parcialmente aplicado si una tabla rechaza la conversión. El humano
  decide rollback o reintento caso por caso.
  
### Neutras

- Los datos existentes se preservan.
- Los endpoints actuales no se ven afectados.
- El hallazgo D07 se cierra como falso positivo. No era un hallazgo.

## Referencias

- MySQL Reference Manual — `ON UPDATE` semantics.
- Oracle Database SQL Reference — Foreign key constraints (PKs inmutables).
- MariaDB Documentation — JSON data type and CHECK constraints (10.4.3+).
- OpenStack — Expand-Contract migration pattern.
- O'Reilly — "Evolutionary Database Design".
- `docs/ROADMAP.md` — FASE A + Bloques 19–27.
- `docs/AUDITORIA_2026.md` — hallazgos canónicos.
- `docs/ADR/006-auditoria-inmutable.md` — razón para descartar CASCADE.