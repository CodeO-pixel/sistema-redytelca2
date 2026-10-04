# Spec 001 - Fix Auditoría Logs
Estado: aprobada

## Contexto y objetivo
Los triggers de auditoría en la base de datos usan variables de sesión MySQL (`@app_usuario_id`, `@app_credencial_id`, `@app_tipo_actor`) para registrar quién realizó cada operación INSERT/UPDATE/DELETE. Actualmente, ningún código PHP establece estas variables antes de ejecutar operaciones de escritura. Como resultado, todos los registros en la tabla `logs` quedan con `id_usuario=NULL`, `id_credencial=NULL` y `tipo_actor='sistema'`, perdiendo toda trazabilidad real de quién hizo qué.

El objetivo es establecer el contexto de auditoría automáticamente en cada request autenticado (staff o cliente del portal) antes de que ocurra cualquier operación de escritura, de forma que los triggers capturen al actor correcto.

## Usuarios
- **Staff interno**: usuarios autenticados vía `requireStaffAuth()` (tabla `usuarios`, identificados por `id_usuario`).
- **Clientes del portal**: usuarios autenticados vía `requireClientAuth()` (tabla `clientes_credenciales`, identificados por `id_credencial`).

## Historias de usuario
- HU-1. Como **administrador del sistema**, quiero que cada operación de escritura (INSERT/UPDATE/DELETE) realizada por personal interno quede registrada en `logs` con `tipo_actor='staff'` e `id_usuario` correspondiente, para tener trazabilidad completa de cambios internos.
- HU-2. Como **administrador del sistema**, quiero que cada operación de escritura realizada por un cliente desde el portal quede registrada en `logs` con `tipo_actor='cliente'` e `id_credencial` correspondiente, para auditar acciones del portal de clientes.
- HU-3. Como **desarrollador**, quiero que el contexto de auditoría se establezca de forma automática y centralizada al autenticar, sin que cada endpoint deba acordarse de llamarlo, para evitar omisiones y errores.

## Definiciones
- **Operación de escritura autenticada**: Cualquier INSERT, UPDATE o DELETE ejecutado en un request que haya pasado por `requireStaffAuth()` o `requireClientAuth()`.
- **Contexto de auditoría**: El conjunto de variables de sesión MySQL `@app_usuario_id`, `@app_credencial_id`, `@app_tipo_actor` que leen los triggers al insertar en `logs`.

## Requisitos funcionales (EARS, en español)
- RF-1: CUANDO un request se autentica como staff vía `requireStaffAuth()`, ENTONCES EL SISTEMA establece `@app_usuario_id = id_usuario`, `@app_tipo_actor = 'staff'`, `@app_credencial_id = NULL` en la conexión actual antes de devolver control al endpoint.
- RF-2: CUANDO un request se autentica como cliente vía `requireClientAuth()`, ENTONCES EL SISTEMA establece `@app_credencial_id = id_credencial`, `@app_tipo_actor = 'cliente'`, `@app_usuario_id = NULL` en la conexión actual antes de devolver control al endpoint.
- RF-3: MIENTRAS dura el request, EL SISTEMA mantiene las variables de sesión de auditoría con los valores establecidos al autenticar, para que cualquier trigger disparado por escrituras posteriores capture el actor correcto.
- RF-4: SI una operación de escritura ocurre sin haber pasado por `requireStaffAuth()` ni `requireClientAuth()` (scripts CLI, migraciones, cron jobs, endpoints públicos sin auth), ENTONCES EL SISTEMA deja que los triggers usen sus valores por defecto (`tipo_actor='sistema'`, `id_usuario=NULL`, `id_credencial=NULL`), sin error ni bloqueo.

## Requisitos no funcionales
- RNF-1: El establecimiento del contexto no debe agregar latencia perceptible al request (una sola query `SET` por variable, ejecutada en la misma conexión PDO ya abierta).
- RNF-2: La solución debe ser compatible con el patrón actual de una conexión PDO por request (sin conexiones persistentes).
- RNF-3: En conexion.php, junto al `new PDO(...)`, debe haber un comentario que documente que las variables @app_* son por conexión, que este proyecto NO usa PDO::ATTR_PERSISTENT, y que si algún día se habilita persistencia hay que resetear @app_usuario_id, @app_credencial_id y @app_tipo_actor al abrir la conexión.

## Casos límite
- Request autenticado como staff que luego llama a un endpoint de cliente (o viceversa): no ocurre en la arquitectura actual porque los middlewares son mutuamente excluyentes por tipo de token (`tipo_usuario` en `sesiones`).
- Request que autentica pero no hace escrituras: las variables se establecen igual; no hay efecto colateral negativo.
- Conexiones concurrentes: cada request tiene su propia conexión PDO, por tanto sus propias variables de sesión MySQL; no hay interferencia.

## Fuera de alcance
- Captura de `ip_address` en `logs` (queda `NULL`; se abordará en otra spec si se requiere).
- Auditoría de eventos `LOGIN`, `LOGOUT`, `LOGIN_FALLIDO` (quedan como `tipo_actor='sistema'`).
- Cambios en la estructura de la tabla `logs` o en los triggers existentes.
- Operaciones de lectura (SELECT) — no generan entradas en `logs`.
- Prueba end-to-end del flujo de cliente del portal (no existe todavía).

## Criterios de finalización
- Todos los endpoints que usan `requireStaffAuth()` registran `tipo_actor='staff'` e `id_usuario` correcto en `logs` tras cualquier escritura.
- Verificar por inspección de código que `requireClientAuth()` setea las variables correctas. La prueba end-to-end con un cliente real queda para cuando exista un endpoint de portal cliente.
- Operaciones sin autenticación (scripts, cron) siguen funcionando y dejan `tipo_actor='sistema'`.
- Prueba manual: hacer login como staff, crear un cliente → verificar en `logs` que aparece `tipo_actor='staff'` e `id_usuario` del admin.

## Dudas abiertas
- [NECESITA ACLARACIÓN] Ninguna — todas las dudas fueron resueltas en las preguntas previas.
