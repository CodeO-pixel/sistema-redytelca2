# ADR 016 — Convención de claves (PK=id, FK=tabla_id)

- **Estado**: aceptado
- **Fecha**: 2026-10-16
- **Contexto**: guía del profesor sobre modelado de datos.

## Contexto

La guía del profesor define una convención explícita:

> "Clave Primaria nombrada estrictamente `id`. Claves Foráneas
> nombradas como `[tabla]_id`."

El esquema actual usa `id_cliente`, `id_servicio`, `id_factura`, etc.,
para PKs y `id_cliente` como nombre de FK en otras tablas. No cumple
la convención.

## Decisión

Aplicar la convención a **todas las tablas**, incluidas las del RBAC.

**PKs**: todas → `id`.
**FKs**: todas → `[tabla_singular]_id`.

Ejemplos:

| Antes | Después |
|---|---|
| `clientes.id_cliente` | `clientes.id` |
| `servicios.id_servicio` | `servicios.id` |
| `servicios.id_cliente` (FK) | `servicios.cliente_id` |
| `facturas.id_servicio` (FK) | `facturas.servicio_id` |
| `pagos.id_usuario_valido` (FK) | `pagos.usuario_valido_id` |
| `usuario_rol.id_usuario` (FK) | `usuario_rol.usuario_id` |

**Nombres de tabla**: se quedan, excepto `logs` → `auditoria_logs`.

**Columnas no PK ni FK**: se aplican estándares de industria (singular,
sin redundancia):

- `clientes.nombres` → `nombre`
- `clientes.apellidos` → `apellido`
- `equipos.num_puerto_nap` → `puerto_nap`
- `equipos.numero_serie` → `serie`

**RBAC**: la estructura se queda (dos niveles, aprobada por el
profesor). Los nombres de tabla se quedan. Solo cambian PKs y FKs de
sus columnas.

## Alternativas descartadas

- **No aplicar la convención**: rechazado por indicación docente.
- **Aplicar solo a las tablas nuevas**: rechazado, la guía dice
  "todas".

## Consecuencias

**Positivas**: cumple la guía, nomenclatura profesional y consistente.

**Negativas**: refactor masivo. ~40 PKs renombradas, ~65 FKs
renombradas, ~35 endpoints, ~40 triggers, 4 vistas, `app.js` completo.
Alto riesgo temporal de romper la app durante la migración.

**Neutras**: los nombres de tabla se quedan, lo que reduce el alcance
del refactor.

## Referencias

- Guía del profesor (2026-10-16).
- ISO/IEC 11179 (nomenclatura de metadatos).