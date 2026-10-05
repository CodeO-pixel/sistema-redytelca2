# Spec 001 — Fix Auditoría Logs

Estado: aprobada

## Contexto y objetivo

Origen: hallazgo AUD-2026-B03 de docs/AUDITORIA_2026.md.

Los triggers de auditoría (`trg_*_audit_*` en `bd_redytelca.sql`) leen las variables de sesión MySQL `@app_usuario_id`, `@app_credencial_id` y `@app_tipo_actor` para registrar quién realiza cada operación INSERT/UPDATE/DELETE. Ningún archivo PHP establece esas variables, por lo que todos los registros en `logs` quedan con `id_usuario=NULL`, `id_credencial=NULL`, `tipo_actor='sistema'`, perdiendo trazabilidad completa.

Objetivo: establecer el contexto de auditoría en cada request autenticado (staff o cliente) antes de cualquier escritura, de forma centralizada y automática, para que los triggers capturen al actor correcto.

## Usuarios

- **Staff interno**: usuarios autenticados vía `requireStaffAuth()`. Identificados por `id_usuario`.
- **Clientes del portal**: usuarios autenticados vía `requireClientAuth()`. Identificados por `id_credencial`.

## Historias de usuario

- **HU-1**. Como staff autenticado, quiero que mis escrituras queden registradas en `logs` con mi `id_usuario` y `tipo_actor='staff'`, para tener trazabilidad.
- **HU-2**. Como cliente del portal, quiero que mis escrituras queden registradas en `logs` con mi `id_credencial` y `tipo_actor='cliente'`.
- **HU-3**. Como auditor, quiero que las escrituras sin sesión autenticada queden registradas con `tipo_actor='sistema'` e IDs nulos, para distinguirlas de las autenticadas.

## Definiciones

- **Contexto de auditoría**: trío de variables de sesión MySQL `@app_usuario_id`, `@app_credencial_id`, `@app_tipo_actor`.
- **Request autenticado**: petición HTTP con header `X-Session-Token` válido que pasa por `requireStaffAuth()` o `requireClientAuth()`.
- **Endpoint público**: petición que no requiere autenticación (ej. `api_empresa.php` GET, `login.php`, `logout.php`, `auth_state.php`).

## Referencias al esquema

Los nombres `@app_usuario_id`, `@app_credencial_id`, `@app_tipo_actor` provienen de los triggers de auditoría en `bd_redytelca.sql` (ej. `trg_contratos_audit_insert`, `trg_facturas_audit_insert`, `trg_pagos_audit_insert`, `trg_servicios_audit_insert`, `trg_usuarios_audit_insert`, `trg_sesiones_audit_insert`).

Las columnas destino en `logs` son: `accion`, `tabla_afectada`, `id_registro_afectado`, `tipo_actor`, `id_usuario`, `id_credencial`, `old_value`, `new_value`.

Los valores válidos de `tipo_actor` según el ENUM existente son: `'staff'`, `'cliente'`, `'sistema'`.

## Requisitos funcionales (EARS, en español)

- **RF-1**: CUANDO un request autenticado como staff pasa por `requireStaffAuth()`, EL SISTEMA establece `@app_usuario_id = id_usuario`, `@app_credencial_id = NULL`, `@app_tipo_actor = 'staff'` en la conexión PDO actual.
- **RF-2**: CUANDO un request autenticado como cliente pasa por `requireClientAuth()`, EL SISTEMA establece `@app_usuario_id = NULL`, `@app_credencial_id = id_credencial`, `@app_tipo_actor = 'cliente'` en la conexión PDO actual.
- **RF-3**: MIENTRAS dura el request HTTP, EL SISTEMA mantiene las variables de auditoría en la conexión PDO para que cualquier trigger AFTER INSERT/UPDATE/DELETE posterior las lea correctamente.
- **RF-4**: SI una operación de escritura ocurre sin haber pasado por `requireStaffAuth()` ni `requireClientAuth()` (scripts CLI, migraciones, cron jobs, endpoints públicos sin auth, phpMyAdmin), ENTONCES EL SISTEMA deja que los triggers usen sus valores por defecto (`tipo_actor='sistema'`, `id_usuario=NULL`, `id_credencial=NULL`), sin error ni bloqueo.
- **RF-5**: SI la autenticación falla (token inválido o expirado), EL SISTEMA no establece variables de auditoría y rechaza el request con 401 (comportamiento actual de los middlewares).
- **RF-6**: CUANDO un request autenticado como staff o cliente es de solo lectura (GET sin escrituras), EL SISTEMA establece las variables igualmente, sin costo funcional relevante.

## Requisitos no funcionales

- **RNF-1**: El establecimiento del contexto no debe agregar latencia perceptible al request (una sola sentencia `SET` con tres asignaciones).
- **RNF-2**: La solución debe ser compatible con el patrón actual de una conexión PDO por request (sin conexiones persistentes).
- **RNF-3**: En `conexion.php`, junto al `new PDO(...)`, debe haber un comentario que documente que las variables `@app_*` son por conexión, que este proyecto NO usa `PDO::ATTR_PERSISTENT`, y que si algún día se habilita persistencia hay que resetear las variables al abrir la conexión.
- **RNF-4**: Debe existir al menos un test en `tests/` que valide el establecimiento del contexto, ejecutable con `php tests/run.php`.

## Casos límite

- **Conexión PDO persistente**: no aplica hoy. PHP crea nueva conexión por request. Ver RNF-3 para el comentario de deuda técnica.
- **Transacciones multi-tabla**: las variables persisten durante toda la transacción porque es la misma conexión PDO.
- **Conexiones concurrentes**: cada request tiene su propia conexión PDO y por tanto sus propias variables MySQL. No hay interferencia.
- **Polling `auth_state.php`**: no pasa por los middlewares, tiene validación inline propia. No setea contexto. No aplica a esta spec.
- **Escrituras desde `login.php`/`logout.php`**: quedan como `tipo_actor='sistema'`. Ver "Fuera de alcance".
- **`api_empresa.php` GET público**: sin auth, no escribe, no genera `logs`. No aplica.

## Fuera de alcance

- Captura de `ip_address` en `logs` (queda `NULL`; spec futura).
- Auditoría de eventos `LOGIN`, `LOGOUT`, `LOGIN_FALLIDO` (spec futura 048-logout-completo).
- Cambios en la estructura de la tabla `logs`, en sus triggers, o en `bd_redytelca.sql`.
- Refactor de `login.php` / `logout.php` para usar middlewares.
- Prueba end-to-end del flujo de cliente del portal (no existe todavía endpoint que use `requireClientAuth`).
- Rate limiting, sesión concurrente, o renovación de token.

## Criterios de finalización

- Todos los endpoints que usan `requireStaffAuth()` (directa o vía `requirePageAccess`) registran `tipo_actor='staff'` e `id_usuario` correcto en `logs` tras cualquier escritura.
- Verificación por inspección de código que `requireClientAuth()` ejecuta el `SET` correcto. La prueba end-to-end con cliente real queda para cuando exista un endpoint de portal cliente.
- Las operaciones sin autenticación (migraciones, phpMyAdmin, scripts CLI) siguen funcionando y dejan `tipo_actor='sistema'`.
- Test automatizado en `tests/audit_context.test.php` que valida que `setAuditContext()` emite los `SET` correctos en todos los casos (staff, cliente, tipo inválido).
- `php tests/run.php` pasa en verde.

## Dudas abiertas

Ninguna. Todas las decisiones fueron resueltas en la fase de clarificación.