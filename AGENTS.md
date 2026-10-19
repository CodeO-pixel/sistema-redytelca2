# AGENTS.md — REDYTELCA

Hoja de arranque técnica del repo. Sistema de gestión ISP (tesis de
grado). Backend PHP 8.2 puro + PDO + MariaDB 10.4. Frontend vanilla JS
sin build tools. Uso interno + portal de clientes.

**Este archivo describe el repo: stack, comandos, convenciones de
código y trampas del dominio.**

No lleva:
- Ciclo de trabajo, roles, reglas con AI Studio, rigor de fuentes,
  forma de trabajar → `docs/METODOLOGIA_DESARROLLO_IA.md`.
- Dónde vive cada tipo de información → `docs/GOBERNANZA.md`.
- Estado del proyecto → `MEMORY.md`.
- Specs y su estado → `docs/ROADMAP.md`.

---

## Stack y estructura

- Backend: PHP 8.2 + PDO + MariaDB 10.4. Sin frameworks, sin Composer.
- Frontend: HTML/CSS/JS puro. Sin npm, sin bundler.
- BD: `bd_redytelca.sql` es la fuente de verdad del esquema.
- Endpoints: `api_*.php` (thin, patrón 139a–d).
- **Kernel MVC: `core/`** (Bootstrap, Config, Database, Response,
  Validator, Auth, Exceptions, bases). Inicialización vía `Http::init()`.
  Auth vía `Auth::requireStaffAuth` / `Auth::requireClientAuth` /
  `Auth::requirePageAccess` / `Auth::requireOperation`.
- **`conexion.php` fue eliminado en 139d Tanda E (2026-10-18).** La
  fuente de verdad del auth y del PDO es `core/`.
- Frontend: `index.html` + `app.js` + `styles.css`. **`app.js` se
  reemplaza por `js/core|models|views|controllers/` en 140.** Hasta
  entonces, conviven ambos.
- Docs: ver `docs/README.md`. Estado actual y fase activa: ver
  `MEMORY.md`.

---

## Comandos

- Arranque local: `php -S localhost:8000` desde la raíz.
- Importar BD: `mysql -u root bd_redytelca < bd_redytelca.sql`
- Tests: `php tests/run.php`

---

## Convenciones de código

- Textos de UI, comentarios y documentación en español.
- Nombres de tablas/columnas en snake_case. **Buscar el nombre exacto
  antes de inventar.**
  - **Convención de claves (ADR 016):** PK siempre `id`. FK siempre
    `[tabla_singular]_id`. Aplica a todas las tablas, incluidas las del
    RBAC. Los nombres de tabla se quedan en plural (`clientes`,
    `servicios`), excepto `logs` que es `auditoria_logs`.
- **Tabla de auditoría:** `auditoria_logs` (no `logs`). Los triggers escriben ahí. Es inmutable.
- Patrones existentes (paginación, modales, fetch) se replican, no se
  reinventan.
- **Tablas pivot sin modelo propio:** `r_cliente_telefono` es N:M puro. Se
  maneja desde el repositorio (`ClienteRepository`), no desde un modelo.
- Contraseñas: `password_hash()` / `password_verify()`. Nunca texto plano.
- Sesiones: token en tabla `sesiones`, header `X-Session-Token`. Nunca
  `$_SESSION` nativo.
- Archivos PHP puros **no llevan `?>` al final**. Razón: PSR-2 (PHP-FIG)
  §2.2 — el tag de cierre puede introducir whitespace accidental y
  problemas de headers. Todos los archivos del proyecto siguen este
  patrón.
- **`<?php` debe ser la primera línea del archivo (byte 0).** Comentario
  `// filename` va en línea 2, nunca antes. Un `// filename` antes de
  `<?php` se emite como output literal antes del parseo; `header()`
  falla con "headers already sent" y el body JSON/CSS llega con prefijo
  de texto. AI Studio invierte el orden sistemáticamente en archivos
  nuevos. Verificar en cada entrega.
- **Endpoints thin (patrón 139a–d):**
  - Cada verbo HTTP tiene su `requirePageAccess` + `requireOperation`.
  - El controller inyecta `$pdo` por constructor; el service hace la
    lógica de negocio; el repository contiene el SQL.
  - **`unset($input['id_X'])` en cada `update()`** antes de pasar
    `$input` o `$validated` al service. Causa raíz de BUG-6.3 y
    BUG-6.4 (el ID viajaba al `BaseRepository::update` que generaba
    `SET id_X = ?` → `Unknown column`).
  - Catch final siempre `Response::fromException($e)`. **No
    reimplementar el mapeo a HTTP.**
  - Indentación 4 espacios. AI Studio la quita sistemáticamente; se
    verifica en la auditoría de forma (ver `docs/VERIFICACION.md`
    §2.8).

---

## Trampas conocidas del dominio

- `servicios` es 1:N con `clientes`. No asumir 1:1.
- `naps`/`olts`/`nodos` en plural (nombres de tabla); columnas FK siguen
  ADR 016: `nap_id`, `olt_id`, `nodo_id`. El nombre de tabla en plural no
  implica el nombre de columna en plural.
- `contratos.estado` (legal) y `servicios.estado_comercial` (operativo)
  son ejes distintos.
- Transiciones de estado validadas en `transicion_estado` (BEFORE UPDATE
  e INSERT).
- RBAC: dos niveles (visibilidad `rol_modulo_pagina` + operación
  `rol_modulo_permiso`). Middleware en `core/Auth.php`.
- **Guard SoD del módulo Configuración vive en `Auth::requirePageAccess()`,**
  no en `requireOperation()`. Rechaza con 403 si la página pertenece al
  módulo 4 (Configuración) y el usuario no tiene rol 1 (Administrador).
- **`Response::fromException` es el único punto de mapeo de excepciones
  a HTTP.**
  - `ValidationException` → 422 con `errors[]`.
  - `AppException` → su código (`NotFoundException` 404, `ConflictException`
    409, `DomainException` 422, `AuthorizationException` 403,
    `AuthenticationException` 401).
  - `PDOException 1062` (UNIQUE) → 409.
  - `PDOException 1452` (FK) → 409.
  - `PDOException 1451` (FK en DELETE) → 409.
  - `PDOException 1644` (SIGNAL de trigger) → 422 con el mensaje del
    trigger.
  - Resto → 500 genérico.
  Los controllers **no atrapan excepciones de dominio**. Solo el thin
  file hace `try/catch → Response::fromException`.
- **Frontend viejo (`app.js`) manda `id_servicio`, `id_cliente`, etc.
  (pre-B28). Backend nuevo espera `servicio_id`, `cliente_id`, etc.
  (post-ADR 016).** Cambio de contrato pendiente de 140. No es bug; es
  deuda heredada.
- **phpMyAdmin no exporta CHECK constraints en el dump SQL.** El dump
  de phpMyAdmin 5.2.1 lista las tablas, columnas, índices, FK y triggers,
  pero **omite los `CHECK` constraints** añadidos con `ALTER TABLE ...
  ADD CONSTRAINT chk_xxx CHECK(...)`. Solo exporta los que están
  definidos inline en la columna (ej. `logs.old_value` con
  `CHECK (json_valid(...))`). Consecuencia: auditar CHECKs contra un
  dump de phpMyAdmin produce falsos positivos masivos. **Para verificar
  CHECKs:** consultar `information_schema.CHECK_CONSTRAINTS` o usar
  `mysqldump` desde CLI. Ver `docs/VERIFICACION.md` §2.6.
- **`CONVERT TO CHARACTER SET` de MariaDB 10.4 reconstruye la tabla y
  puede descartar CHECKs.** Aunque el dump no los muestre, la BD los
  conserva en `information_schema`. No asumir pérdida sin verificar.
- Ver `docs/constitution.md` y el plan de la fase activa antes de tocar
  código.

---

## Límites duros del repo

- **Siempre:** buscar el nombre exacto; replicar patrones; explicar el
  porqué.
- **Preguntar antes de:** tocar >1 archivo, agregar dependencias nuevas,
  cambiar el esquema, crear archivos nuevos.
- **Nunca:** reestructurar la BD sin pedido; `$_SESSION` nativo;
  contraseñas en texto plano; inventar nombres; modificar un doc sin
  leerlo del dump en el turno; usar la memoria como fuente sobre el
  contenido de un archivo.

---

## Verificación

- **Verificación por CLI hasta 140.** Mientras `app.js` no se reescriba
  (spec 140), la verificación es solo por `curl` + `mysql -e` + `php -l`
  + `findstr`. Navegador prohibido: `app.js` viejo tiene bugs
  pre-existentes (`restoreSession` no oculta `#login-overlay`, cachés no
  se limpian al logout) que no son del backend y no se arreglan en 139.
  Ver `docs/VERIFICACION.md` § 2.10.
- **Formato de verificación obligatorio:** pasos numerados + comando +
  Esperado + precondición de abort. El humano ejecuta, pega salidas
  literales, el agente audita. El agente no ejecuta. Ver
  `docs/METODOLOGIA_DESARROLLO_IA.md` § 5.5.
- **Harness casero:** `tests/run.php` ejecuta `tests/smoke.test.php` y
  `tests/audit_context.test.php`. Ambos usan `core/Bootstrap.php` +
  `core/Auth.php` (migrado de `conexion.php` en 139d Tanda E).
- **Sin CI/CD.** Sin tests de endpoints ni de frontend. Las specs
  139a–139d se verifican con curl + SQL. Se documenta en `MEMORY.md`.
- **Después de cada cambio:** `php -l` sobre todos los archivos tocados,
  `php tests/run.php`, y la verificación curl+SQL específica de la spec.
- **Pruebas aisladas de backend:** si en algún momento (post-140) se
  prueba con curl y la app está abierta, cerrar todas las pestañas
  antes: `initApp()` dispara cargas en segundo plano que interfieren.
- Ver `docs/VERIFICACION.md` para lecciones y checklists.

---

## Memoria

- **Al empezar una sesión:** leer `MEMORY.md`.
- **Al terminar una fase:** actualizar `MEMORY.md` (estado, decisiones,
  aprendizajes) y `docs/ROADMAP.md` (spec cerrada).
- **Si algo se vuelve regla permanente del repo:** moverlo a este archivo
  o a `docs/constitution.md`.
- **Si algo se vuelve regla del proceso:** moverlo a
  `docs/METODOLOGIA_DESARROLLO_IA.md`.