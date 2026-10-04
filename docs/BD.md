# Base de Datos — REDYTELCA

Esquema, relaciones y decisiones. Fuente de verdad: `bd_redytelca.sql`.

- Motor: MariaDB 10.4
- Charset: `utf8mb4_general_ci`
- Colación: `utf8mb4_general_ci`
- Motor de tablas: InnoDB

## Listado de tablas (30)

### Dominio: Clientes
- `clientes`
- `clientes_credenciales`

### Dominio: Servicios y contratos
- `servicios`
- `contratos`
- `planes`

### Dominio: Facturación
- `facturas`
- `pagos`

### Dominio: Infraestructura
- `nodos`
- `olts`
- `naps`
- `equipos`

### Dominio: Soporte
- `tickets`
- `ticket_respuestas`
- `tareas`

### Dominio: Usuarios y permisos
- `usuarios`
- `rol`
- `usuario_rol`
- `modulos`
- `paginas`
- `rol_modulo`
- `rol_modulo_pagina`
- `rol_modulo_permiso`
- `permisos`

### Dominio: Sesiones
- `sesiones`
- `sesion_rol_activo`

### Dominio: Auditoría
- `logs`
- `transicion_estado`

### Dominio: Configuración
- `configuracion_empresa`
- `notificaciones`

---

## Tablas clave

### `clientes`
Persona que contrata servicios.

| Columna | Tipo | Restricción |
|---------|------|-------------|
| `id_cliente` | int | PK, auto_increment |
| `nombres` | varchar(65) | NOT NULL |
| `apellidos` | varchar(65) | NOT NULL |
| `cedula` | varchar(15) | NOT NULL, UNIQUE |
| `num_telefono` | varchar(25) | NOT NULL |
| `correo` | varchar(80) | NOT NULL |
| `fecha_registro` | datetime | DEFAULT current_timestamp |

**Nota**: no tiene `actualizado_en` ni `estado` (hallazgo AUD-2026-D02).

### `servicios`
Instalación física de un cliente. **Relación 1:N con `clientes`** (un cliente puede tener varias direcciones/instalaciones).

| Columna | Tipo | Restricción |
|---------|------|-------------|
| `id_servicio` | int | PK, auto_increment |
| `id_cliente` | int | FK → `clientes.id_cliente` |
| `alias` | varchar(50) | NULL |
| `estado_comercial` | enum('activo','suspendido','retirado','pendiente') | DEFAULT 'pendiente' |
| `id_plan` | int | FK → `planes.id_plan`, NOT NULL |
| `id_naps` | int | FK → `naps.id_nap`, NULL |
| `direccion_texto` | varchar(255) | NOT NULL DEFAULT '' |
| `ip_asignada` | varchar(45) | NULL |
| `latitud_instalacion` | decimal(10,8) | NULL |
| `longitud_instalacion` | decimal(11,8) | NULL |

**Nota**: la tabla `direcciones` que existía antes fue eliminada. La dirección vive inline en `servicios`.

### `contratos`
Acuerdo legal sobre un servicio.

| Columna | Tipo | Restricción |
|---------|------|-------------|
| `id_contrato` | int | PK, auto_increment |
| `id_servicio` | int | FK → `servicios.id_servicio` |
| `fecha_inicio` | date | NOT NULL |
| `fecha_fin` | date | NULL |
| `tipo_contrato` | enum('indefinido','plazo_fijo','promocional') | DEFAULT 'indefinido' |
| `estado` | enum('vigente','vencido','rescindido') | DEFAULT 'vigente' |
| `observaciones` | text | NULL |
| `creado_en` | timestamp | DEFAULT current_timestamp |

**Importante**: `contratos.estado` (legal) y `servicios.estado_comercial` (operativo) son **ejes distintos**, no redundantes. Se sincronizan con regla de negocio en PHP (`sincronizarEstadoServicio()` en `api_contratos.php`).

### `facturas`
Cobro periódico de un servicio.

| Columna | Tipo | Restricción |
|---------|------|-------------|
| `id_factura` | int | PK, auto_increment |
| `id_servicio` | int | FK → `servicios.id_servicio` |
| `periodo` | varchar(7) | NOT NULL, formato `AAAA-MM` |
| `monto` | decimal(10,2) | NOT NULL |
| `impuesto_aplicado` | decimal(5,2) | NOT NULL DEFAULT 0.00 |
| `fecha_emision` | date | NOT NULL |
| `fecha_vencimiento` | date | NOT NULL |
| `estado` | enum('pendiente','pagada','parcial','vencida','anulada') | DEFAULT 'pendiente' |
| `creado_en` | timestamp | DEFAULT current_timestamp |
| `actualizado_en` | timestamp | ON UPDATE current_timestamp |

**Falta**: UNIQUE `(id_servicio, periodo)` (hallazgo AUD-2026-D11).

### `pagos`
Pago registrado por un cliente, asociado o no a una factura.

| Columna | Tipo | Restricción |
|---------|------|-------------|
| `id_pago` | int | PK, auto_increment |
| `id_factura` | int | FK → `facturas.id_factura`, NULL |
| `id_cliente` | int | FK → `clientes.id_cliente` |
| `id_servicio` | int | FK → `servicios.id_servicio` |
| `monto` | decimal(10,2) | NOT NULL |
| `moneda_pago` | enum('USD','VES') | DEFAULT 'USD' |
| `tasa_cambio` | decimal(10,4) | NULL |
| `fecha_pago` | datetime | NOT NULL |
| `metodo_pago` | enum('transferencia','pago_movil','zelle','efectivo') | NULL |
| `referencia_bancaria` | varchar(255) | NULL |
| `comprobante_path` | varchar(255) | NULL |
| `estado` | enum('pendiente','validado','rechazado') | DEFAULT 'pendiente' |
| `motivo_rechazo` | varchar(255) | NULL |
| `fecha_validacion` | datetime | NULL |
| `id_usuario_valido` | int | FK → `usuarios.id_usuario`, NULL |
| `origen` | enum('portal_cliente','backoffice') | DEFAULT 'backoffice' |

**Falta**: `creado_en`, `actualizado_en` (hallazgo AUD-2026-D13).

### `usuarios`
Staff interno (empleados del ISP).

| Columna | Tipo | Restricción |
|---------|------|-------------|
| `id_usuario` | int | PK, auto_increment |
| `username` | varchar(50) | NOT NULL, UNIQUE |
| `password` | varchar(255) | NOT NULL (hash bcrypt) |
| `id_rol` | int | FK → `rol.id_rol`, DEFAULT 1 |
| `email` | varchar(100) | NOT NULL, UNIQUE |
| `must_change_password` | tinyint(1) | DEFAULT 0 |
| `email_verified` | tinyint(1) | DEFAULT 0 |
| `estado` | enum('Activo','Inactivo','Bloqueado') | DEFAULT 'Activo' |
| `intentos_fallidos` | int | DEFAULT 0 |
| `bloqueado_hasta` | datetime | NULL |

**Importante**: `id_rol` es FK simple (relación 1:N). Existe `usuario_rol` (N:M) pero no se usa. Hallazgos AUD-2026-001 y AUD-2026-D09.

### `rol` y `permisos`
- `rol`: `id_rol`, `nombre_rol` (UNIQUE), `descripcion`.
- `permisos`: `id_permiso`, `nombre` (UNIQUE). Valores seed: `crear`, `editar`, `eliminar`, `leer`, `validar`.

### Modelo de permisos (parcialmente implementado)
- `modulos`: catálogo de módulos de la aplicación.
- `paginas`: páginas/URLs dentro de cada módulo. FK → `modulos`.
- `rol_modulo`: qué módulos ve un rol (N:M). Sin uso en código.
- `rol_modulo_pagina`: qué páginas ve un rol (N:M). **Sí se usa** por `requirePageAccess()`.
- `rol_modulo_permiso`: qué operaciones puede hacer un rol sobre una página (N:M). **No se usa**.

### `sesiones`
Token de autenticación activo.

| Columna | Tipo | Restricción |
|---------|------|-------------|
| `id_sesion` | int | PK, auto_increment |
| `token` | varchar(64) | NOT NULL, UNIQUE |
| `tipo_usuario` | enum('staff','cliente') | DEFAULT 'staff' |
| `id_usuario` | int | FK → `usuarios.id_usuario`, NULL |
| `id_credencial` | int | FK → `clientes_credenciales.id_credencial`, NULL |
| `ip_address` | varchar(45) | NULL |
| `user_agent` | text | NULL |
| `creado_en` | timestamp | DEFAULT current_timestamp |
| `expira_en` | datetime | NOT NULL |
| `revocado_en` | datetime | NULL |
| `motivo_cierre` | enum('logout','cambio_password','revocada_por_admin','seguridad') | NULL |

**Falta**: CHECK que garantice "exactamente uno" entre `id_usuario` e `id_credencial` (hallazgo AUD-2026-D08).

### `logs`
Auditoría inmutable. Triggers AFTER INSERT/UPDATE/DELETE escriben aquí.

| Columna | Tipo | Restricción |
|---------|------|-------------|
| `id_log` | bigint | PK, auto_increment |
| `creado_en` | timestamp | DEFAULT current_timestamp |
| `accion` | enum('INSERT','UPDATE','DELETE','LOGIN','LOGOUT','LOGIN_FALLIDO') | NOT NULL |
| `tabla_afectada` | varchar(50) | NULL |
| `id_registro_afectado` | int | NULL |
| `tipo_actor` | enum('staff','cliente','sistema') | NOT NULL |
| `id_usuario` | int | FK → `usuarios.id_usuario`, NULL |
| `id_credencial` | int | FK → `clientes_credenciales.id_credencial`, NULL |
| `ip_address` | varchar(45) | NULL |
| `old_value` | longtext JSON | CHECK (json_valid) |
| `new_value` | longtext JSON | CHECK (json_valid) |

**Triggers inmutables**: `trg_logs_inmutable_update` y `trg_logs_inmutable_delete` bloquean cualquier modificación.

**Problema**: los triggers de las otras tablas leen `@app_usuario_id`, `@app_credencial_id`, `@app_tipo_actor` que **nunca se setean**. Todos los logs quedan con `tipo_actor='sistema'`. En corrección (spec 001).

### `transicion_estado`
Máquina de estados finitos validada por triggers BEFORE UPDATE.

| Columna | Tipo | Restricción |
|---------|------|-------------|
| `id_transicion` | int | PK, auto_increment |
| `tabla` | varchar(50) | NOT NULL |
| `estado_origen` | varchar(30) | NOT NULL |
| `estado_destino` | varchar(30) | NOT NULL |
| `descripcion` | varchar(255) | NULL |

UNIQUE `(tabla, estado_origen, estado_destino)`.

**Tablas con transiciones definidas**: `facturas`, `pagos`, `contratos`, `servicios`.

**Falta**: no valida transiciones en INSERT (triggers solo BEFORE UPDATE). Hallazgo AUD-2026-B07.

### `configuracion_empresa`
Singleton con datos del ISP.

| Columna | Tipo |
|---------|------|
| `id_config` | int PK (siempre 1) |
| `nombre_empresa` | varchar(100) |
| `logo_path` | varchar(255) |
| `color_primario` | char(7) |
| `impuesto_porcentaje` | decimal(5,2) |
| `dia_facturacion` | tinyint(2) |
| `dias_gracia_mora` | tinyint(2) |
| `monto_recargo_mora` | decimal(10,2) |
| `dias_corte_automatico` | tinyint(2) |
| `creado_en`, `actualizado_en` | timestamp |

## Relaciones principales

```
clientes (1) ──< (N) servicios
                     │
                     ├── (N) contratos
                     ├── (N) facturas ──< (N) pagos
                     ├── (1) equipos (UNIQUE id_servicio)
                     └── (1) nap

nodos (1) ──< (N) olts ──< (N) naps ──< (N) servicios

usuarios (N) ──> (1) rol
usuarios (1) ──< (N) sesiones
usuarios (1) ──< (N) logs
```

## Convenciones de nombres

- Tablas en plural: `clientes`, `naps`, `olts`, `nodos`, `equipos`, `pagos`.
- PK: `id_<tabla_singular>` (ej. `id_cliente`, `id_nap`, `id_olt`).
- FK: `id_<tabla_referenciada_plural>` (ej. `id_naps`, `id_olts`, `id_nodos`). **Ojo: las columnas FK sí llevan plural** aunque la PK sea singular. Esto es inconsistente pero es el esquema real.
- Enum en minúsculas, excepto `usuarios.estado` que usa mayúsculas ('Activo'/'Inactivo'/'Bloqueado'). Hallazgo AUD-2026-D04.

## Triggers

### Auditoría (por tabla de negocio)
- `trg_<tabla>_audit_insert` — AFTER INSERT
- `trg_<tabla>_audit_update` — AFTER UPDATE
- `trg_<tabla>_audit_delete` — AFTER DELETE

Tablas con triggers: `contratos`, `facturas`, `pagos`, `servicios`, `usuarios`, `usuario_rol`, `rol_modulo_permiso`, `sesiones`, `logs` (protección).

### Validación de transiciones
- `trg_<tabla>_validar_transicion` — BEFORE UPDATE
- `trg_logs_inmutable_update` — BEFORE UPDATE
- `trg_logs_inmutable_delete` — BEFORE DELETE

## Decisiones de diseño documentadas

| Decisión | Razón |
|----------|-------|
| Sin tabla `direcciones` separada | Migración en Fase 0: la dirección vive inline en `servicios` |
| `equipos.id_servicio` UNIQUE | Un solo equipo activo por servicio (limitación: no permite ONT + Router simultáneos — hallazgo AUD-2026-D10) |
| `logs` con triggers inmutables | Trazabilidad no manipulable |
| Lógica de negocio en PHP, no en triggers | Patrón consistente del proyecto |
| `usuarios.id_rol` como FK simple | Modelo 1:N, no N:M. `usuario_rol` existe pero no se usa |

## Hallazgos de BD

Ver `docs/AUDITORIA_2026.md` para detalle. Categoría "Base de datos":
- AUD-2026-D01 a D16 (16 hallazgos)
- AUD-2026-X05 (transiciones en UPDATE pero no INSERT)

## Referencias

- `bd_redytelca.sql` — fuente de verdad.
- `docs/ARQUITECTURA.md` — vista general.
- `docs/AUDITORIA_2026.md` — hallazgos.