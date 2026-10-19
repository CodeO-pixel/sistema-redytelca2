# ADR 014 — Normalización de direcciones y teléfonos

- **Estado**: aceptado
- **Fecha**: 2026-10-16
- **Contexto**: indicación docente durante asesoría.
- **Supera parcialmente**: ADR 003 (relación 1:N cliente-servicio) en lo
  relativo a mantener la dirección inline en `servicios`.

## Contexto

ADR 003 descartó la tabla `direcciones` separada:

> "fue el diseño original (Fase 0) pero se eliminó. Reutilizar
> direcciones entre clientes es raro y añade joins innecesarios."

El 2026-10-16 el profesor revisó el modelo y exigió la normalización:
direcciones de cliente y de servicio en tablas separadas con sus
propias coordenadas, y teléfonos en tabla aparte (varios por cliente).

## Decisión

Crear cuatro tablas nuevas:

- `direccion` — dirección del cliente. 1:1 con `clientes`.
  Campos: `id`, `calle`, `carrera`, `avenida`, `latitud`, `longitud`,
  `creado_en`, `actualizado_en`.
- `direccion_servicio` — dirección de instalación del servicio. 1:1 con
  `servicios`.
  Campos: `id`, `casa`, `calle`, `avenida`, `latitud`, `longitud`,
  `creado_en`, `actualizado_en`.
- `telefono` — catálogo de teléfonos.
  Campos: `id`, `numero`, `creado_en`.
- `r_cliente_telefono` — relación N:M entre cliente y teléfono.
  Campos: `id`, `cliente_id`, `telefono_id`, `creado_en`.

Cambios en tablas existentes:

- `clientes.direccion_id` — FK a `direccion` (NULL-able).
- `servicios.direccion_id` — FK a `direccion_servicio` (NULL-able).

Migración de datos:

- `clientes.num_telefono` → `telefono` + `r_cliente_telefono`.
- `servicios.direccion_texto`, `servicios.latitud_instalacion`,
  `servicios.longitud_instalacion` → `direccion_servicio`.
- Se eliminan las columnas viejas tras la migración.

## Relaciones

- `cliente` 1:1 `direccion`.
- `cliente` N:M `telefono` vía `r_cliente_telefono`.
- `servicio` 1:1 `direccion_servicio`.
- `pedido` N:1 `servicio` (hereda la dirección del servicio).

## Alternativas descartadas

- **Mantener el diseño actual (ADR 003)**: rechazado por indicación
  docente.
- **Una sola tabla `direccion` polimórfica (cliente|servicio)**: FK
  polimórfica rompe integridad referencial.
- **Teléfonos en JSON dentro de `clientes`**: no es 1FN.

## Consecuencias

**Positivas**: cumple indicación docente, normalización correcta,
prepara el terreno para el portal de pago.

**Negativas**: refactor de ~35 endpoints PHP, `app.js`, `index.html`,
~40 triggers y 4 vistas. Migración de datos con riesgo.

**Neutras**: no afecta RBAC ni auditoría.

## Referencias

- ADR 003 (parcialmente superado).
- Diagrama del profesor (2026-10-16).
- Guía del profesor sobre normalización.