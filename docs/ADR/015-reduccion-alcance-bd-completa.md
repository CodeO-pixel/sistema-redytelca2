# ADR 015 — Reducción de alcance del bloque BD-COMPLETA

- **Estado**: aceptado
- **Fecha**: 2026-10-16
- **Contexto**: revisión del bloque BD-COMPLETA tras indicación del
  profesor.
- **Revisa parcialmente**: ADR 013 (bd-completa-refactor).

## Contexto

ADR 013 agregó 17 tablas al esquema (P2, P3) como parte del refactor
consolidado. El 2026-10-16 el profesor revisó el esquema y marcó
varias como innecesarias (ejemplos: `email_plantillas`, `email_log`,
`notificaciones_staff`). Tras analizar la función de cada tabla, se
determinó cuáles son realmente esenciales para el flujo del sistema
y cuáles no.

## Decisión

**Tablas eliminadas (8):**

| Tabla | Motivo |
|---|---|
| `equipo_movimientos` | El equipo es del cliente, no comodato con devolución |
| `cliente_contactos` | Redundante con `telefono` |
| `cliente_notas` | No esencial |
| `etiquetas` | Calculable desde otras tablas |
| `cliente_etiquetas` | Depende de `etiquetas` |
| `plan_historial_precios` | No esencial, `auditoria_logs` cubre |
| `ip_pools` | IPAM fuera de alcance (ADR 012) |
| `ip_asignaciones` | IPAM fuera de alcance (ADR 012) |

**Tablas conservadas del bloque (sí cubren funciones que el profesor
pidió):**

- `notificaciones_staff` — avisos al staff (portal).
- `email_plantillas`, `email_log` — verificación de correo, reset de
  contraseña.
- `categorias_ticket`, `ticket_respuestas`, `sla_definiciones` —
  soporte.
- `pedidos`, `pedido_materiales`, `pedido_asignaciones` — trabajo de
  campo.
- `notas_credito`, `notas_debito` — ajustes contables.
- `metodos_pago` — catálogo de métodos.

## Consecuencias

**Positivas**: cumple indicación docente, elimina estructura sin uso,
reconoce que las "tablas de relleno" sí eran un problema real.

**Negativas**: reduce el alcance del bloque BD-COMPLETA. Elimina la
posibilidad de IPAM y de historial de equipos.

**Neutras**: los ADRs 004–008 (RBAC) no se tocan.

## Referencias

- ADR 013 (parcialmente revisado).
- ADR 012 (alcance del proyecto, IPAM fuera).
- Diagrama del profesor (2026-10-16).