# Base de Datos — REDYTELCA

Esquema, relaciones y decisiones. Fuente de verdad: `bd_redytelca.sql`
(dump con datos) y `bd_redytelca_schema.sql` (DDL puro, regenerar).

- Motor: MariaDB 10.4
- Charset: `utf8mb4_unicode_ci`
- Motor de tablas: InnoDB
- **44 tablas** (tras la migración de BD, 2026-10-16)

**Convención de claves (ADR 016):** PK = `id` en todas las tablas. FK = `[tabla_singular]_id`. Los nombres de tabla se quedan en plural, excepto `auditoria_logs`.

**Este documento describe el esquema.** No lleva:
- Estado de specs / fases → `ROADMAP.md`.
- Hallazgos abiertos → `AUDITORIA_2026.md`.
- Cómo está construido el sistema → `ARQUITECTURA.md`.
- Convenciones de código → `AGENTS.md`.

---

## Listado de tablas

### Dominio: Clientes (5)
- `clientes`
- `clientes_credenciales`
- `direccion`
- `telefono`
- `r_cliente_telefono`

### Dominio: Servicios y contratos (4)
- `servicios`
- `direccion_servicio`
- `contratos`
- `planes`

### Dominio: Facturación (6)
- `facturas`
- `pagos`
- `factura_lineas`
- `notas_credito`
- `notas_debito`
- `metodos_pago`

### Dominio: Infraestructura (4)
- `nodos`
- `olts`
- `naps`
- `equipos`

### Dominio: Soporte (5)
- `tickets`
- `ticket_respuestas`
- `tareas`
- `categorias_ticket`
- `sla_definiciones`

### Dominio: Fulfillment (3)
- `pedidos`
- `pedido_materiales`
- `pedido_asignaciones`

### Dominio: Notificaciones (2)
- `notificaciones`
- `notificaciones_staff`

### Dominio: Email transaccional (2)
- `email_plantillas`
- `email_log`

### Dominio: Usuarios y permisos — RBAC dos niveles (9)
- `usuarios`
- `rol`
- `usuario_rol`
- `modulos`
- `paginas`
- `permisos`
- `rol_modulo`
- `rol_modulo_pagina`
- `rol_modulo_permiso`

### Dominio: Sistema (4)
- `sesiones`
- `auditoria_logs`
- `transicion_estado`
- `configuracion_empresa`

---

## Tablas clave

### `clientes`
Persona que contrata servicios.

| Columna | Tipo | Restricción |
|---|---|---|
| `id` | int | PK |
| `nombre` | varchar(65) | NOT NULL |
| `apellido` | varchar(65) | NOT NULL |
| `cedula` | varchar(15) | UNIQUE, CHECK formato |
| `correo` | varchar(80) | NOT NULL |
| `fecha_registro` | datetime | DEFAULT current_timestamp |
| `actualizado_en` | timestamp | ON UPDATE current_timestamp |
| `estado` | enum('activo','suspendido','inactivo') | DEFAULT 'activo' |
| `segmento` | varchar(30) | NULL |
| `direccion_id` | int | FK → `direccion.id`, NULL-able |

### `direccion`
Dirección del cliente. `clientes.direccion_id` → `direccion.id` (N:1 nullable: un cliente tiene 0 o 1 dirección; no hay UNIQUE en `clientes.direccion_id`, pero en la práctica cada cliente tiene su propia fila).

| Columna | Tipo |
|---|---|
| `id` | int PK |
| `calle` | varchar(150) |
| `carrera` | varchar(150) |
| `avenida` | varchar(150) |
| `latitud` | decimal(10,8) |
| `longitud` | decimal(11,8) |
| `creado_en` | timestamp |
| `actualizado_en` | timestamp |

CHECKs: `latitud` entre -90 y 90, `longitud` entre -180 y 180.

### `telefono` / `r_cliente_telefono`
Relación N:M. Un cliente puede tener varios teléfonos.

- `telefono`: `id`, `numero` (UNIQUE), `creado_en`.
- `r_cliente_telefono`: `id`, `cliente_id` (FK), `telefono_id` (FK), `creado_en`. UNIQUE (cliente_id, telefono_id).

### `servicios`
Instalación física de un cliente. **Relación 1:N con `clientes`.**

| Columna | Tipo | Restricción |
|---|---|---|
| `id` | int | PK |
| `cliente_id` | int | FK → `clientes.id` |
| `alias` | varchar(50) | NULL |
| `estado_comercial` | enum('activo','suspendido','retirado','pendiente') | DEFAULT 'pendiente' |
| `plan_id` | int | FK → `planes.id`, NOT NULL |
| `nap_id` | int | FK → `naps.id`, NULL |
| `direccion_id` | int | FK → `direccion_servicio.id`, NULL |
| `creado_en` | timestamp | |
| `actualizado_en` | timestamp | |

### `direccion_servicio`
Dirección de instalación del servicio. `servicios.direccion_id` → `direccion_servicio.id` (N:1 nullable: un servicio tiene 0 o 1 dirección de instalación).

| Columna | Tipo |
|---|---|
| `id` | int PK |
| `casa` | varchar(50) |
| `calle` | varchar(150) |
| `avenida` | varchar(150) |
| `latitud` | decimal(10,8) |
| `longitud` | decimal(11,8) |
| `creado_en` | timestamp |
| `actualizado_en` | timestamp |

### `contratos`
Acuerdo legal sobre un servicio.

- `id`, `servicio_id` (FK), `fecha_inicio`, `fecha_fin`, `tipo_contrato`, `estado`, `observaciones`.
- Trigger `trg_contratos_unico_vigente_insert/update`: solo un contrato vigente por servicio.

**Nota:** `contratos.estado` (legal) y `servicios.estado_comercial` (operativo) son ejes distintos. Se sincronizan con regla de negocio en el service (`ContratoService::actualizar`).

### `facturas`
Cobro periódico de un servicio.

| Columna | Tipo | Restricción |
|---|---|---|
| `id` | int | PK |
| `servicio_id` | int | FK → `servicios.id` |
| `periodo` | varchar(7) | formato `AAAA-MM` |
| `monto` | decimal(10,2) | USD |
| `moneda` | varchar(3) | DEFAULT 'USD' |
| `tasa_cambio` | decimal(10,4) | NULL si USD |
| `impuesto_aplicado` | decimal(5,2) | |
| `fecha_emision` | date | |
| `fecha_vencimiento` | date | |
| `estado` | enum('pendiente','pagada','parcial','vencida','anulada') | |

**UNIQUE:** `(servicio_id, periodo)` — impide doble facturación.

### `pagos`
Pago registrado por un cliente.

- `id`, `factura_id` (FK), `cliente_id` (FK), `servicio_id` (FK), `metodo_id` (FK).
- `monto` (USD), `moneda_pago` (USD/VES), `tasa_cambio`.
- `fecha_pago`, `referencia_bancaria`, `comprobante_path`.
- `estado` (pendiente/validado/rechazado), `motivo_rechazo`, `fecha_validacion`, `usuario_valido_id`.
- `origen` (portal_cliente/backoffice).

### `usuarios`
Staff interno del ISP.

- `id`, `username` (UNIQUE), `password` (bcrypt), `email` (UNIQUE).
- `must_change_password`, `email_verified`, `estado` (Activo/Inactivo/Bloqueado).
- `intentos_fallidos`, `bloqueado_hasta`.

**No tiene `id_rol`.** Los roles se asignan vía `usuario_rol` (N:M).

### Modelo de permisos — RBAC dos niveles

- `usuario_rol`: `id`, `usuario_id` (FK), `rol_id` (FK). N:M.
- `modulos`: catálogo. `id`, `nombre_modulo`.
- `paginas`: `id`, `nombre_pagina`, `url_pagina`, `modulo_id` (FK).
- `permisos`: catálogo. `id`, `nombre`. Valores: leer, crear, editar, eliminar, validar.
- `rol_modulo`: N:M entre rol y modulos. `id`, `rol_id`, `modulo_id`.
- `rol_modulo_pagina`: **nivel 1** — visibilidad. `id`, `rol_modulo_id`, `modulo_id`, `pagina_id`.
- `rol_modulo_permiso`: **nivel 2** — operación. `id`, `rol_modulo_id`, `modulo_id`, `pagina_id`, `permiso_id`.

`Auth::requirePageAccess()` consulta `rol_modulo_pagina`. `Auth::requireOperation()` consulta `rol_modulo_permiso`. Detalle → `ARQUITECTURA.md`.

### `auditoria_logs`
Tabla de auditoría inmutable. Renombrada de `logs` en la migración de BD (2026-10-16).

| Columna | Tipo |
|---|---|
| `id` | bigint PK |
| `creado_en` | timestamp |
| `accion` | enum(INSERT, UPDATE, DELETE, LOGIN, LOGOUT, LOGIN_FALLIDO) |
| `tabla_afectada` | varchar(50) |
| `id_registro_afectado` | int |
| `tipo_actor` | enum(staff, cliente, sistema) |
| `usuario_id` | int, FK → usuarios.id |
| `credencial_id` | int, FK → clientes_credenciales.id |
| `ip_address` | varchar(45) |
| `old_value` | longtext JSON |
| `new_value` | longtext JSON |

**Inmutable:** `trg_auditoria_logs_inmutable_update` y `_delete` bloquean cualquier modificación.

### `transicion_estado`
Máquina de estados finitos.

- `id`, `tabla`, `estado_origen`, `estado_destino`, `descripcion`.
- UNIQUE (`tabla`, `estado_origen`, `estado_destino`).
- Tablas con transiciones: `facturas`, `pagos`, `contratos`, `servicios`, `tickets`.

### `equipos`
Inventario de CPE (ONU, COMBO, ROUTER).

- `id`, `tipo` ENUM('ONU','COMBO','ROUTER'), `marca`, `modelo`.
- `direccion_mac` (UNIQUE), `serie` (UNIQUE), `puerto_nap`.
- `estado_fisico`, `estado_inventario`, `propiedad` (cliente/empresa).
- `nap_id` (FK), `servicio_id` (FK, NULL).
- CHECKs:
  - `chk_equipo_tipo_propiedad`: ONU siempre `propiedad='empresa'`. COMBO y ROUTER libres.
  - `chk_equipo_estado_servicio`: `stock` ↔ `servicio_id IS NULL`; `asignado` ↔ `servicio_id NOT NULL`; `devuelto`/`averiado`/`baja` sin restricción.
  - `chk_equipo_puerto`: `puerto_nap > 0`.
  - `chk_equipo_mac`: formato `AA:BB:CC:DD:EE:FF`.

### `sesiones`
Token de autenticación activo.

- `id`, `token` (UNIQUE), `tipo_usuario` (staff/cliente).
- `usuario_id` / `credencial_id` (uno de los dos, CHECK `chk_sesiones_un_tipo`).
- `creado_en`, `expira_en`, `revocado_en`, `motivo_cierre`.

### `configuracion_empresa`
Singleton (`id=1`).

- `nombre_empresa`, `logo_path`, `color_primario`, `impuesto_porcentaje`, `dia_facturacion`, `dias_gracia_mora`, `monto_recargo_mora`, `dias_corte_automatico`.

---

## Relaciones principales
clientes (1) ──< (1) direccion
clientes (N) ──< (N) telefono (vía r_cliente_telefono)
clientes (1) ──< (N) servicios
servicios (1) ──< (1) direccion_servicio
servicios (N) ──< (1) planes
servicios (N) ──< (1) naps
servicios (1) ──< (N) contratos
servicios (1) ──< (N) facturas
servicios (1) ──< (N) equipos

nodos (1) ──< (N) olts ──< (N) naps ──< (N) servicios

facturas (1) ──< (N) pagos
facturas (1) ──< (N) factura_lineas
facturas (1) ──< (N) notas_credito
facturas (1) ──< (N) notas_debito

pedidos (N) ──< (1) servicios
pedidos (N) ──< (1) clientes
pedidos (1) ──< (N) pedido_materiales
pedidos (1) ──< (N) pedido_asignaciones

usuarios (N) ──< (N) rol (vía usuario_rol)
rol (1) ──< (N) rol_modulo ──< (N) rol_modulo_pagina
└──< (N) rol_modulo_permiso

text

---

## Triggers activos (145)

| Tipo | Cantidad | Descripción |
|---|---|---|
| Auditoría (`trg_*_audit_*`) | 129 | AFTER INSERT/UPDATE/DELETE en 43 tablas |
| SLA tickets | 2 | `trg_tickets_sla_insert`, `trg_tickets_sla_update` |
| Unicidad contrato vigente | 2 | `trg_contratos_unico_vigente_insert/update` |
| Validación de transiciones | 10 | `facturas`, `pagos`, `contratos`, `servicios`, `tickets` (insert + transición por tabla) |
| Inmutabilidad auditoria_logs | 2 | `trg_auditoria_logs_inmutable_update/delete` |

**Nota:** `auditoria_logs` no tiene audit triggers (es el destino). Tiene los 2 de inmutabilidad.

---

## Vistas (4)

- `v_clientes_360` — resumen de cliente con servicios, tickets y facturas.
- `v_equipos_asignados` — equipos con NAP, OLT, nodo, servicio y cliente.
- `v_estado_cuenta_cliente` — saldo pendiente por cliente.
- `v_facturas_con_saldo` — saldo de cada factura no pagada.

---

## Datos operativos pendientes

Estas tablas están vacías o con datos parciales. **No se llenan desde la migración.** Se llenan desde la UI cuando la fase correspondiente cierre.

| Tabla | Estado | Fase en que se llena |
|---|---|---|
| `direccion` | Vacía | Fase 1 (139a/b) — el controller crea la dirección al dar de alta cliente |
| `clientes_credenciales` | Vacía | Fase 5 (096a) |
| `notificaciones` | Vacía | Fase 5 (086a–d) |
| `notificaciones_staff` | Vacía | Fase 5 (086a–d) |
| `email_log` | Vacía | Fase 7 (116a–d) |
| `pedidos`, `pedido_materiales`, `pedido_asignaciones` | Vacías | Fase 7 (115a–d) |
| `notas_credito`, `notas_debito` | Vacías | Fase 7 (118d) |
| `ticket_respuestas` | Vacía | Fase 5 (096d) |
| `sla_definiciones` | 3 filas (parcial) | Fase 2 (EST-02) |
| `email_plantillas` | 6 filas (parcial) | Fase 2 (EST-03) / Fase 7 (116b) |

**No es un problema del esquema.** La estructura está completa. Los datos faltantes se generan por uso.

---

## Decisiones de diseño documentadas

| Decisión | Razón | ADR |
|---|---|---|
| Dos tablas de dirección separadas (`direccion` para cliente, `direccion_servicio` para servicio) | Dirección de cliente y de servicio son cosas distintas. Indicación docente 2026-10-16. | 014 |
| `PK=id` / `FK=tabla_id` en todas las tablas | Nomenclatura profesional y consistente. Indicación docente. | 016 |
| `equipos.tipo` con `COMBO` | Los dispositivos que estaban como `ONT` (ej. Huawei HG8245) son ONT+router en un solo aparato. Se migraron a `COMBO`. `ONU` = solo terminal de fibra. `ROUTER` = solo router. | — |
| `equipos.propiedad` se conserva | No derivable de tipo (COMBO y ROUTER pueden ser de cliente o empresa). | — |
| RBAC dos niveles (`rol_modulo_pagina` + `rol_modulo_permiso`) | Visibilidad separada de operación. Docente lo confirmó. Patrón AWS VAMS. | 008 |
| `facturas(servicio_id, periodo)` UNIQUE | Impide doble facturación. | — |
| `auditoria_logs` inmutable | Trazabilidad no manipulable. | 006 |
| `pedidos.servicio_id` nullable | En la práctica todo pedido se crea desde un servicio, pero el DDL permite `NULL` (ON DELETE SET NULL si el servicio se borra). | — |
| IPAM fuera de alcance (tablas `ip_pools` e `ip_asignaciones` eliminadas) | Reducción de alcance. Ver ADR 015. Se reintroduce en Fase 7 (114a–c) si aplica. | 015 |

---

## Referencias

- `bd_redytelca.sql` — dump con datos.
- `bd_redytelca_schema.sql` — DDL puro sin datos (regenerar).
- `docs/ARQUITECTURA.md` — vista general del sistema.
- `docs/ROADMAP.md` — plan por fases.
- `docs/ADR/003-servicios-1n.md`
- `docs/ADR/006-auditoria-inmutable.md`
- `docs/ADR/008-rbac-dos-niveles.md`
- `docs/ADR/014-normalizacion-direcciones-telefonos.md`
- `docs/ADR/015-reduccion-alcance-bd-completa.md`
- `docs/ADR/016-convencion-claves.md`