# ADR 003 — Relación 1:N entre clientes y servicios

- **Estado**: aceptado
- **Fecha**: Fase 2.5 (2026)
- **Contexto**: corrección de bug histórico en el modelo de datos.

## Contexto

En las fases tempranas del proyecto, el código asumía que cada cliente tenía exactamente un servicio (una dirección, un plan, una NAP). El formulario de "Nuevo cliente" capturaba datos de instalación y el endpoint `registrar_cliente.php` creaba un servicio. Cuando se editaba el cliente (`cliente_actualizar.php`), el código buscaba el "servicio más reciente" del cliente y lo sobreescribía con los datos del formulario.

Esto producía dos bugs:
1. **Silencioso**: si un cliente tenía varios servicios, editar el cliente sobrescribía el servicio equivocado (el más reciente, no el que correspondía).
2. **Estructural**: era imposible dar de alta un segundo servicio a un cliente existente.

Sin embargo, la base de datos nunca tuvo esa restricción. `servicios.id_cliente` es una FK simple (sin UNIQUE). El bug era puramente de capa de aplicación.

## Decisión

Modelar la relación como **1 cliente : N servicios**. Cada servicio tiene:
- Su propia dirección (`direccion_texto`, `latitud_instalacion`, `longitud_instalacion`).
- Su propio plan (`id_plan`).
- Su propia NAP asignada (`id_naps`).
- Su propio estado comercial (`estado_comercial`).

La gestión de servicios pasa a un endpoint dedicado (`api_servicios.php`) con CRUD independiente. Los endpoints de cliente (`cliente_actualizar.php`, `registrar_cliente.php`) solo tocan la entidad `clientes`.

## Alternativas descartadas

### Mantener 1:1 con cliente
- **Ventaja**: simplicidad. Un cliente, una dirección, un servicio.
- **Razón de descarte**: no refleja la realidad del ISP. Un cliente puede tener:
  - Casa principal + oficina.
  - Servicio residencial + IP fija para trabajo remoto.
  - Servicio activo + servicio suspendido temporalmente.
- Forzar 1:1 habría requerido múltiples "clientes" para la misma persona física, duplicando cédula, teléfono, correo. Peor diseño.

### Tabla intermedia `cliente_servicio` (N:M)
- **Ventaja**: máxima flexibilidad.
- **Razón de descarte**: overkill. Un servicio **siempre** pertenece a exactamente un cliente. No hay casos donde un servicio se comparta entre clientes. N:M aquí añade complejidad sin beneficio.

### Mover la dirección a tabla separada `direcciones`
- **Ventaja**: normalización. Una dirección podría reutilizarse entre servicios.
- **Razón de descarte**: fue el diseño original (Fase 0) pero se eliminó. En la práctica, cada instalación tiene coordenadas específicas. Reutilizar direcciones entre clientes es raro y añade joins innecesarios. La dirección vive inline en `servicios`.

## Consecuencias

### Positivas
- Un cliente puede tener varios servicios reales.
- El listado de clientes (`clientes_listar.php`) trae el servicio "principal" (menor `id_servicio`) como referencia y añade `total_servicios` para indicar "+N más".
- La búsqueda en `clientes_listar.php` usa `EXISTS` contra TODOS los servicios, no solo el principal. Un cliente se encuentra por dirección de cualquier servicio suyo.
- La UI cambió: el modal de cliente ya no captura dirección/NAP. Se accede a servicios vía "Ficha del cliente" → "+ Añadir servicio".

### Negativas
- Complejidad en la UI: los operadores deben entender la distinción cliente vs servicio.
- El endpoint `api_servicios.php` se convirtió en una entidad de primera clase con su propio CRUD.
- Las facturas, contratos y equipos ahora cuelgan del `id_servicio`, no del `id_cliente`. Esto es correcto pero requiere joins más elaborados.

### Neutras
- `registrar_cliente.php` sigue creando el PRIMER servicio en el mismo flujo de alta si el formulario aporta datos de instalación. Es una concesión para no forzar dos pasos en el alta.
- Los servicios adicionales se crean desde `api_servicios.php`, no desde el endpoint de cliente.

## Referencias

- `docs/BD.md` — esquema de `servicios` y `clientes`.
- `docs/ARQUITECTURA.md` — endpoints.
- `api_servicios.php` — CRUD de servicios.
- `clientes_listar.php` — subquery de servicio principal.
- `cliente_actualizar.php` — solo toca `clientes`.
- Hallazgo AUD-2026-D02 (falta `actualizado_en` en `clientes`).