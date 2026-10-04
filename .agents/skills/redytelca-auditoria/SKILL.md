---
name: redytelca-auditoria
description: Úsala siempre que necesites auditar, re-auditar o verificar el cumplimiento de un componente de REDYTELCA contra el marco de auditoría del proyecto (endpoint, tabla, módulo de UI, flujo completo). También úsala al validar que una corrección cerró efectivamente su hallazgo origen.
---

# Auditoría REDYTELCA — marco aplicado

Marco completo en `docs/METODOLOGIA_AUDITORIA_IA.md`. Esta skill es la versión operativa para REDYTELCA: cómo auditar sin perder tiempo, qué buscar, dónde está cada cosa.

## Cuándo usar esta skill

- **Re-auditar** un componente después de una corrección (verificar que el hallazgo cerró).
- **Auditar** un componente nuevo que no estaba en la auditoría original.
- **Validar** que una spec cumplió sus requisitos funcionales.
- **Detectar** hallazgos nuevos al tocar código existente.

## Marco de referencia (resumen)

6 pasadas × 10 capas × contexto ISP. Aplicado a REDYTELCA:

| Pasada | Aplica a REDYTELCA |
|--------|-------------------|
| P1 — Conformidad estructural | RBAC (ANSI/INCITS 359), REST (RFC 7231), máquina de estados |
| P2 — Consistencia interna | Normalización, integridad referencial, manejo de errores |
| P3 — Cruzada entre capas | BD ↔ PHP ↔ JS ↔ HTML |
| P4 — Adversarial (STRIDE) | Endpoints de auth, pagos, facturación |
| P5 — No funcional | Rendimiento, mantenibilidad, seguridad |
| P6 — Dinámica | Chrome DevTools (cuando esté configurado el MCP) |

## Fuentes de verdad del proyecto

Antes de auditar, lee:

1. `bd_redytelca.sql` — esquema real.
2. `docs/ARQUITECTURA.md` — cómo está construido.
3. `docs/BD.md` — esquema documentado.
4. `docs/AUDITORIA_2026.md` — hallazgos previos (para no duplicar).
5. `docs/ROADMAP.md` — qué está ya asignado a spec.

**Regla**: si detectas un hallazgo que ya está en `AUDITORIA_2026.md`, no lo dupliques. Referencia el ID existente.

## Qué buscar en cada capa (aplicado a REDYTELCA)

### Base de datos
- ¿La tabla tiene PK?
- ¿Las FK están declaradas?
- ¿Falta UNIQUE en columnas de negocio? (ej. `facturas(id_servicio, periodo)`)
- ¿ENUM vs VARCHAR consistente con el resto?
- ¿Timestamps `creado_en`/`actualizado_en` presentes?
- ¿Índices para queries frecuentes?
- ¿CHECK constraints donde aplique (formato, exclusividad)?

### Backend PHP
- ¿Usa prepared statements? (100% sí, sin excepción)
- ¿El error se propaga sin fuga de información? (`$e->getMessage()` al cliente es hallazgo)
- ¿Usa transacción en operaciones multi-tabla?
- ¿El verbo HTTP es correcto? (GET sin side-effects)
- ¿El endpoint verifica autorización en el backend, no solo en el frontend?
- ¿El `id_usuario`/`id_credencial`/`id_usuario_valido` vienen de la sesión, no del body?

### Autorización
- ¿Pasa por `requireStaffAuth`/`requireClientAuth`/`requirePageAccess`?
- ¿Si es acción sensible, verifica `id_rol === 1`?
- ¿El hallazgo 002 (sin gate por operación) aplica aquí?

### Frontend
- ¿Se usa `escapeHtml()` antes de insertar datos en el DOM?
- ¿El `onclick` inline interpola datos de BD? (hallazgo F03)
- ¿Los errores de red se manejan con try/catch?
- ¿Hay polling innecesario?

### Auditoría / logs
- ¿La operación de escritura pasa por middleware? (si no, queda como `'sistema'` en logs)
- ¿El cambio de estado es una transición válida en `transicion_estado`?

## Formato de hallazgo (obligatorio)

Cada hallazgo detectado debe registrarse así:

```
ID: AUD-2026-XXX (siguiente número disponible o letra+XXX)
Severidad: Crítico / Alto / Medio / Bajo
Capa: [1-10]
Línea roja: Sí / No
Evidencia:
  - Archivo: [ruta exacta]
  - Línea(s): [rango]
  - Fragmento: [código]
Impacto: [descripción del problema]
Dominio ISP: [PII / Facturación / Aislamiento / SoD / Continuidad / Reputación]
Estado: Nuevo / Duplicado de AUD-2026-YYY
```

## Líneas rojas (no admiten excepción)

1. Protección de PII de clientes.
2. Aislamiento de clientes (BOLA/IDOR).
3. RBAC formal y separación de funciones.
4. Integridad de facturación.
5. Auditabilidad de operaciones.
6. Credenciales nunca en texto plano.

Si un hallazgo toca una línea roja y no tiene spec asignada, **escalar a "bloqueante de tesis"**.

## Cómo reportar

Al terminar una auditoría:
1. Añadir hallazgos nuevos a `docs/AUDITORIA_2026.md` (si la spec lo autoriza).
2. Añadir filas correspondientes a `docs/ROADMAP.md` con spec asignada.
3. Actualizar `MEMORY.md` con el estado.

**Nunca** modificar `docs/AUDITORIA_2026.md` sin que exista una spec para eso. Es un documento histórico.

## Re-auditoría post-corrección

Cuando una spec cierra, verificar:

- [ ] Los RF del `spec.md` están cubiertos por tests verdes.
- [ ] El hallazgo origen ya no es reproducible con el mismo método de detección.
- [ ] No se introdujeron hallazgos nuevos en el mismo archivo.
- [ ] `docs/ROADMAP.md` marca el hallazgo como `cerrado`.
- [ ] `MEMORY.md` refleja el cierre.

Si algo falla, la spec **no está cerrada**.

## Qué NO hace esta skill

- No implementa correcciones. Solo audita.
- No modifica `docs/AUDITORIA_2026.md` sin spec.
- No declara un hallazgo como `cerrado` sin re-auditoría explícita.

## Referencias

- `docs/METODOLOGIA_AUDITORIA_IA.md` — marco completo (6 pasadas, 10 capas).
- `docs/AUDITORIA_2026.md` — informe de auditoría actual.
- `docs/ROADMAP.md` — hallazgos → specs.
- `docs/constitution.md` — principios.
- `.agents/skills/redytelca-bd/SKILL.md` — nombres de BD.
- `.agents/skills/redytelca-auth/SKILL.md` — reglas de auth.