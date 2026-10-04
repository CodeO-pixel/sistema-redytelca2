---
name: redytelca-bd
description: Úsala siempre que escribas, modifiques o revises código que lea o escriba en la base de datos de REDYTELCA (cualquier archivo PHP que use $pdo, cualquier SQL, cualquier referencia a tablas/columnas del proyecto). Evita inventar nombres y previene errores de esquema.
---

# Base de datos REDYTELCA — nombres exactos y trampas

La causa raíz #1 de bugs históricos del proyecto es asumir nombres "estándar". Los nombres reales están en `bd_redytelca.sql`. Consulta aquí antes de escribir SQL.

## Nombres que suelen escribirse mal

| Correcto | Incorrecto frecuente |
|----------|---------------------|
| `naps` (tabla) | `nap` |
| `olts` (tabla) | `olt` |
| `nodos` (tabla) | `nodo` |
| `id_naps` (columna FK) | `id_nap` |
| `id_olts` (columna FK) | `id_olt` |
| `id_nodos` (columna FK) | `id_nodo` |
| `id_nap` (PK en `naps`) | `id_naps` |
| `id_olt` (PK en `olts`) | `id_olts` |
| `id_nodo` (PK en `nodos`) | `id_nodos` |

**Regla**: PK es singular (`id_nap`), FK es plural (`id_naps`). Inconsistente, pero real.

## Relaciones que NO son obvias

- **`clientes` 1:N `servicios`** — un cliente tiene N servicios. Nunca asumir 1:1.
- **`contratos.estado`** (legal: vigente/vencido/rescindido) y **`servicios.estado_comercial`** (operativo: activo/suspendido/retirado/pendiente) son **ejes distintos**. No son redundantes. Se sincronizan con `sincronizarEstadoServicio()` en `api_contratos.php`.
- **`equipos.id_servicio` es UNIQUE** — un servicio solo puede tener un equipo activo. Limitación conocida (AUD-2026-D10).
- **`facturas` y `pagos`** cuelgan de `id_servicio`, no de `id_cliente`.

## Convenciones de columnas

- **Timestamp**: `creado_en`, `actualizado_en` (con `ON UPDATE current_timestamp`).
- **Estado**: enums en minúsculas EXCEPTO `usuarios.estado` que usa `'Activo'/'Inactivo'/'Bloqueado'`.
- **Booleans**: `tinyint(1)` con 0/1.
- **Dinero**: `decimal(10,2)`. Nunca `float` ni `double`.
- **Coordenadas**: `decimal(10,8)` latitud, `decimal(11,8)` longitud.
- **Password**: `varchar(255)` para bcrypt. Nunca `varchar(60)`.

## Tablas muertas (no las uses)

Existen en el esquema pero **ningún endpoint las consulta**:
- `usuario_rol`
- `rol_modulo_permiso`
- `permisos`
- `sesion_rol_activo`
- `clientes_credenciales`
- `notificaciones`
- `ticket_respuestas`
- `rol_modulo`

No las uses en código nuevo sin antes verificar en `MEMORY.md` si ya se decidió su eliminación o activación.

## Tablas que SÍ se usan para autorización

- `rol_modulo_pagina` — única tabla consultada por `requirePageAccess()`.
- `usuarios.id_rol` — FK simple, no N:M.

## Transiciones de estado

Validadas por triggers BEFORE UPDATE en `transicion_estado`. **No se validan en INSERT** (hallazgo AUD-2026-B07).

Tablas con transiciones definidas:
- `facturas`: pendiente ↔ pagada ↔ parcial ↔ vencida ↔ anulada
- `pagos`: pendiente ↔ validado ↔ rechazado
- `contratos`: vigente ↔ vencido ↔ rescindido
- `servicios`: pendiente ↔ activo ↔ suspendido ↔ retirado

## Esquema de auditoría

Triggers AFTER INSERT/UPDATE/DELETE en tablas de negocio escriben en `logs`. Leen variables MySQL:
- `@app_usuario_id`
- `@app_credencial_id`
- `@app_tipo_actor`

Con `COALESCE(..., 'sistema')` como fallback.

**Estado**: en corrección por spec 001. Antes de esa spec, todos los logs quedan con `tipo_actor='sistema'`.

## Checklist antes de escribir SQL

- [ ] ¿Verifiqué el nombre exacto de la tabla en `bd_redytelca.sql`?
- [ ] ¿Verifiqué el nombre exacto de cada columna?
- [ ] ¿Los JOINs usan la FK correcta (con el plural o singular correcto)?
- [ ] ¿Respeté las políticas `ON DELETE` existentes?
- [ ] ¿Si es una operación de escritura, pasa por el middleware de auth (para que se registre en logs)?
- [ ] ¿Si toco `bd_redytelca.sql`, hay spec aprobada que lo autorice?

## Referencias

- `bd_redytelca.sql` — fuente de verdad del esquema.
- `docs/BD.md` — documentación humana del esquema.
- `docs/AUDITORIA_2026.md` — hallazgos de BD.
- `MEMORY.md` — decisiones vigentes sobre RBAC y tablas muertas.