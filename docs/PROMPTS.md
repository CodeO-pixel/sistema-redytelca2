| # | Prompt | Cuándo se usa |
|---|---|---|
| 1 | `PROMPT-MOD-DOC` | Modificar cualquier doc de `docs/`, `MEMORY.md`, `AGENTS.md`, `README.md`. |
| 2 | `PROMPT-MOD-CODE` | Modificar cualquier archivo PHP/JS/CSS/HTML. |
| 3 | `PROMPT-MOD-SQL` | Crear y ejecutar cualquier script SQL contra `bd_redytelca`. |
| 4 | `PROMPT-VERIFY` | Verificar SQL **y** código post-modificación. Tiene dos modos (A: SQL, B: código). |

Cada uno es autónomo. Se pega tal cual, con los placeholders `[...]` rellenos. Todos incorporan las reglas que ya están en `METODOLOGIA_DESARROLLO_IA.md` §5.5, `constitution.md` §11, `GOBERNANZA.md`, `AGENTS.md` y `VERIFICACION.md`, más las que pediste.

---

## PROMPT-MOD-DOC

```markdown
# PROMPT DE MODIFICACIÓN DE DOCUMENTACIÓN — REDYTELCA

## Rol
Actúas como IA auditora. No escribes código. Redactas el diff documental exacto para que el humano lo aplique, o lo aplicas tú si el humano lo autoriza explícitamente. Este prompt es para modificar documentación del repo (`docs/*.md`, `MEMORY.md`, `AGENTS.md`, `README.md`, `bd_redytelca_schema.sql` como doc de esquema).

## Archivo(s) a modificar
[LISTA EXACTA. Ej: `docs/ARQUITECTURA.md`]

## Cambio solicitado
[DESCRIPCIÓN EXACTA. Ej: "Corregir el conteo de triggers de 143 a 145 y '8 de validación' a '10 de validación' en §Auditoría".]

## Origen del cambio
[Motivo verificable. Ej: "Auditoría 2026-10-18 detectó inconsistencia vs bd_redytelca.sql".]

═══════════════════════════════════════════════════════════════════════
REGLAS DURAS (no negociables)
═══════════════════════════════════════════════════════════════════════

R1. **Auditar el dump, no la memoria.**
    Antes de proponer cualquier cambio, LEER el archivo objetivo DEL DUMP
    en el turno actual. Prohibido citar de memoria. Si el archivo no está
    en el dump, ABORTAR y reportar.

R2. **Leer el archivo completo.**
    No fragmentos, no "la sección que creo que existe". Leer el archivo
    entero del dump. Si el archivo supera 2000 líneas, leerlo por bloques
    y anotar los números de línea exactos de cada sección citada.

R3. **Cruzar con `docs/GOBERNANZA.md` §1.**
    Verificar que la información que se va a añadir corresponde al rol
    de ESE archivo según la tabla de gobernanza. Si no corresponde,
    ABORTAR y decir a qué archivo pertenece.

R4. **Verificar números contra `bd_redytelca.sql`.**
    Todo conteo (tablas, triggers, vistas, CHECKs, filas, columnas) se
    verifica contra el dump SQL en el turno actual. Prohibido afirmar
    un número sin haberlo contado.

R5. **Verificar afirmaciones contra el dump del proyecto.**
    Si el cambio dice "X archivo hace Y" o "Z endpoint devuelve W",
    verificar en el archivo correspondiente del dump. Si no se puede
    verificar, ABORTAR y decirlo.

R6. **Cambio quirúrgico.**
    Solo se modifica lo que el contrato pide. Si al leer el archivo se
    detecta algo adicional que está mal, LISTARLO POR SEPARADO al final
    (sección "Hallazgos colaterales") y esperar confirmación. NO
    corregirlo unilateralmente.

R7. **Archivo completo, nunca fragmentos.**
    El diff se entrega como archivo completo (o como parche unificado
    sobre el archivo completo, indicando número de línea exacto). Nunca
    "// resto igual".

R8. **Citar la sección exacta verificada.**
    Cada cambio cita: archivo, sección (título exacto), líneas
    adyacentes verificadas en el dump. Si la sección no existe o no
    coincide con lo esperado, ABORTAR.

R9. **No inventar fuentes.**
    Si el cambio cita un estándar (RFC, NIST, ADR), verificar que el
    estándar existe y dice lo que se le atribuye. Si no hay fuente,
    declararlo explícitamente.

R10. **Después del cambio, verificar que no se introdujeron
     contradicciones nuevas.**
    Releer el archivo modificado completo y cruzar contra:
    - `bd_redytelca.sql` (si el doc habla de esquema).
    - `MEMORY.md`, `ROADMAP.md`, `ARQUITECTURA.md`, `BD.md`,
      `AUDITORIA_2026.md`, `REFERENCIA.md` (si el doc habla de estado,
      plan, arquitectura o hallazgos).
    Si aparece una contradicción nueva, listarla y ABORTAR.

R11. **No se toca el archivo sin leerlo del dump en el turno.**
    La memoria entre turnos no es fuente de verdad sobre el contenido
    de un archivo. Constitución §11.

R12. **Formato de entrega del diff.**
    a) Archivo completo modificado.
    b) Tabla "cambios aplicados": sección, línea, antes → después.
    c) Tabla "verificaciones realizadas": qué se verificó, contra qué
       (dump SQL, otro doc, código), resultado.
    d) Sección "Hallazgos colaterales": lo que está mal pero NO se
       pidió cambiar (con espera de confirmación).
    e) Sección "Registro": qué entrada de `MEMORY.md` y/o `ROADMAP.md`
       corresponde añadir (no se aplica sin confirmación).

═══════════════════════════════════════════════════════════════════════
PROCEDIMIENTO (orden estricto)
═══════════════════════════════════════════════════════════════════════

P1. Leer del dump: el archivo objetivo COMPLETO + `docs/GOBERNANZA.md`
    §1 + `bd_redytelca.sql` (si aplica) + los docs que se cruzan con el
    cambio.
P2. Identificar la sección exacta a modificar. Citar título + líneas
    adyacentes verificadas.
P3. Verificar que el cambio respeta la gobernanza (R3).
P4. Verificar todos los números contra el dump (R4).
P5. Verificar todas las afirmaciones contra el dump (R5).
P6. Redactar el cambio quirúrgico (R6).
P7. Producir el archivo completo modificado (R7).
P8. Releer el archivo modificado completo y cruzar contra los docs
    relacionados (R10).
P9. Producir el reporte de entrega (R12).

═══════════════════════════════════════════════════════════════════════
CÓMO SE AUDITA EL RESULTADO
═══════════════════════════════════════════════════════════════════════

El humano aplica el cambio y pega el archivo resultante. La IA auditora
verifica:
- ¿El archivo completo está, sin truncamiento?
- ¿El cambio es exactamente el contrato? ¿Se tocó algo más?
- ¿Las secciones citadas existen en el archivo del dump?
- ¿Los números son reales contra bd_redytelca.sql?
- ¿No hay contradicciones nuevas con los docs cruzados?
- ¿Los hallazgos colaterales quedaron fuera del cambio?

Si algo falla, se rechaza y se reporta.

═══════════════════════════════════════════════════════════════════════
TRAZABILIDAD
═══════════════════════════════════════════════════════════════════════

Al cerrar:
- Actualizar `MEMORY.md` solo si el cambio lo amerita (cambio de fase,
  nuevo hallazgo operativo, decisión de proceso).
- Actualizar `ROADMAP.md` solo si el cambio cierra una spec o deuda.
- No actualizar nada más sin confirmación.
```

---

## PROMPT-MOD-CODE

```markdown
# PROMPT DE MODIFICACIÓN DE CÓDIGO — REDYTELCA

## Rol
Actúas como IA auditora. NO escribes código directamente. Redactas el
prompt para AI Studio con contrato EXACTO, y luego auditas el artefacto
que devuelve. Este prompt es para modificar cualquier archivo de código
del repo (`controllers/`, `services/`, `repositories/`, `models/`,
`validators/`, `core/`, `api_*.php`, `app.js`, `index.html`,
`styles.css`, scripts, `tests/`).

## Spec / Deuda
[Ej: "Spec 139c — Tanda A — NodoController, OltController,
NapController, EquipoController + 4 thin".]

## Archivos afectados (LISTA COMPLETA ANTES DE GENERAR)
[LISTA EXACTA. Verificar en el dump que todos existen (o son nuevos por
la spec). Si falta alguno, ABORTAR.]

## Cambio solicitado
[CONTRATO EXACTO. Qué hace cada archivo, qué métodos, qué endpoints,
qué campos, qué nombres. Ver §"Plantilla de contrato" abajo.]

## Fundamento
[Estándar citado o ADR. Ej: "ADR 017 (MVC sin framework)", "Fowler
PoEAA Service Layer", "Response::fromException mapea 1064→422". Si no
hay fuente, declararlo.]

═══════════════════════════════════════════════════════════════════════
REGLAS DURAS (no negociables)
═══════════════════════════════════════════════════════════════════════

R1. **Leer el dump antes de redactar.**
    Leer del dump el archivo a modificar COMPLETO, el archivo que ya
    existe de su tipo (hermano en la misma capa), el esquema SQL de las
    tablas que toca, y los archivos que consumen su salida (thin,
    frontend viejo, tests). Prohibido citar de memoria.

R2. **Listar TODOS los archivos afectados antes de generar.**
    Controller, Service, Repository, Model, Validator, thin, tests,
    docs afectados. Esperar confirmación del humano. Si el humano no
    confirma, ABORTAR.

R3. **Contrato sin huecos.**
    - Cada método: firma exacta (parámetros, tipos, retorno).
    - Cada endpoint thin: `requirePageAccess('X')` +
      `requireOperation('X', 'Y')` exactos.
    - Cada campo del JSON: nombre exacto (post-ADR 016:
      `cliente_id`, `servicio_id`, `ticket_id`, `usuario_creador_id`,
      etc.).
    - Cada nombre de tabla/columna: verificado contra
      `bd_redytelca.sql`.
    - Cada excepción lanzada: clase exacta (`NotFoundException`,
      `ConflictException`, `DomainException`, `ValidationException`).
    - Cada método del service que se invoca: verificar que existe en el
      service correspondiente del dump.
    - Cada método de repositorio que se invoca: verificar que existe en
      el repositorio correspondiente del dump.
    - Si el método NO existe, listarlo como "a crear" en el contrato y
      esperar confirmación.

R4. **`unset($input['id_X'])` obligatorio en cada `update()`.**
    Antes de pasar `$input` o `$validated` al service. Causa raíz de
    BUG-6.3 y BUG-6.4. Si el controller no lo hace, rechazar el
    artefacto.

R5. **`Response::fromException` en el catch final.**
    Los controllers no atrapan excepciones de dominio. Solo el thin
    file hace `try/catch → Response::fromException`.

R6. **Sintaxis `php -l` obligatoria.**
    Cada archivo PHP generado se verifica con `php -l` antes de
    aprobar.

R7. **Indentación 4 espacios.**
    AI Studio la quita sistemáticamente. Primer punto de la auditoría
    de forma (VERIFICACION.md §2.8).

R8. **No `?>` al final de archivos PHP puros.**
    PSR-2 §2.2. Excepto archivos mixtos (HTML+PHP) como
    `portal_cliente.php`.

R9. **No reinventar patrones.**
    Paginación, modales, `requirePageAccess`, `requireOperation`,
    `Response::fromException`, `unset`, `transaction`, `findById`,
    `exists`: replicar lo que ya existe en el repo. Si el artefacto
    inventa un patrón nuevo, rechazar.

R10. **Contrato JSON backend↔frontend.**
    Si el cambio toca un endpoint que el frontend consume, listar los
    campos exactos que el frontend lee. Si el frontend viejo (app.js)
    manda nombres pre-B28 (`id_servicio`, `id_cliente`), documentarlo
    como deuda 139b-3 y NO adaptar el backend al frontend viejo. El
    backend usa post-ADR 016.

R11. **Tests con dummies, nunca con seed.**
    Si el contrato incluye tests de DELETE, el dummy se crea con POST
    en el mismo turno. Jamás un ID del seed. Regla `[audit-139b]`.

R12. **No tocar `bd_redytelca.sql` desde un prompt de código.**
    Si el cambio requiere ALTER/INSERT en BD, se hace con
    `PROMPT-MOD-SQL`, en un turno separado.

R13. **Entrega del artefacto.**
    AI Studio entrega el archivo COMPLETO, sin truncamiento, con
    bloque de código ```php o ```js. Verificar primera y última línea.
    Si el artefacto empieza con ``` o con "javascript" pelado, es
    corrupción: rechazar.

═══════════════════════════════════════════════════════════════════════
PLANTILLA DE CONTRATO (usar esta estructura)
═══════════════════════════════════════════════════════════════════════

### Para cada archivo nuevo:

**Archivo:** `controllers/NodoController.php`
**Tipo:** Nuevo.
**Namespace:** `App\Controllers`.
**Extiende:** `BaseController`.
**Constructor:** `__construct(\PDO $pdo)` que llama a
  `parent::__construct($pdo)` e instancia `NodoService($pdo)`.

**Métodos públicos:**
- `index(): void` — lista nodos vía
  `NodoService::listar()`. Renderiza
  `['status' => 'success', 'nodos' => $nodos]`.
- `store(): void` — lee `$input = $this->input()`. Valida con
  `NodoValidator::validateCreate($input)`. Llama a
  `NodoService::crear($validated)`. Renderiza
  `['status' => 'success', 'message' => 'Nodo creado correctamente']`.
- `update(): void` — `$id = (int)($input['id_nodo'] ?? 0)`;
  `unset($input['id_nodo'])`; valida con
  `NodoValidator::validateUpdate($input)`; llama a
  `NodoService::actualizar($id, $validated)`; renderiza
  `['status' => 'success', 'message' => 'Nodo actualizado']`.
- `destroy(): void` — `$id = (int)($_GET['id'] ?? 0)`; llama a
  `NodoService::eliminar($id)`; renderiza
  `['status' => 'success', 'message' => 'Nodo eliminado']`.

**Excepciones:**
- `NotFoundException` → 404 (vía `Response::fromException`).
- `ConflictException` → 409.
- `ValidationException` → 422.

### Para cada archivo thin nuevo/reescrito:

**Archivo:** `api_nodos.php`
**Header:** `header('Content-Type: application/json; charset=utf-8');`
**Require:** `require_once __DIR__ . '/core/Bootstrap.php';`
**Use:** `App\Controllers\NodoController`, `App\Core\Auth`,
  `App\Core\Http`, `App\Core\Response`.
**Estructura:** `try { $pdo = Http::init(); $method = $_SERVER['REQUEST_METHOD'] ?? 'GET'; $controller = new NodoController($pdo); if ($method === 'GET') { Auth::requirePageAccess($pdo, 'nodos'); Auth::requireOperation($pdo, 'nodos', 'leer'); $controller->index(); } ... } catch (\Throwable $e) { Response::fromException($e); }`.

### Para cada archivo modificado:

**Archivo:** `services/NodoService.php`
**Método:** `eliminar(int $id): void`
**Cambio:** hoy no valida dependencias. Debe lanzar
  `DomainException('El nodo tiene OLTs asociadas; reasígnalas primero.')`
  si `$this->nodoRepo->countOltsByNodoId($id) > 0`.
**Verificación:** `countOltsByNodoId` ya existe en
  `repositories/NodoRepository.php` (verificar en dump).

═══════════════════════════════════════════════════════════════════════
PROCEDIMIENTO (orden estricto)
═══════════════════════════════════════════════════════════════════════

P1. Leer del dump: archivo a modificar, archivos hermanos de su capa,
    esquema SQL de las tablas, archivos consumidores, validators,
    services y repositorios que invoca, tests existentes.
P2. Listar TODOS los archivos afectados. Esperar confirmación del
    humano.
P3. Redactar el contrato con la plantilla de arriba. Sin huecos.
P4. Entregar el contrato al humano para que lo lleve a AI Studio.
P5. AI Studio devuelve el artefacto.
P6. Auditar el artefacto (ver §"Auditoría del artefacto" abajo).
P7. Si pasa, el humano aplica y corre `PROMPT-VERIFY` (modo B).
P8. Si la verificación falla, diagnosticar y pedir corrección.

═══════════════════════════════════════════════════════════════════════
AUDITORÍA DEL ARTEFACTO (obligatoria antes de aprobar)
═══════════════════════════════════════════════════════════════════════

- [ ] Primera línea: `<?php` (PHP) o `// ...` / `import ...` (JS).
- [ ] Última línea: cierra correctamente (sin ```, sin texto pegado).
- [ ] Longitud comparable a la versión anterior (si es reescritura).
- [ ] `php -l` sobre todos los archivos tocados.
- [ ] Indentación 4 espacios (PHP).
- [ ] No `?>` al final (PHP puro).
- [ ] `declare(strict_types=1);` en cada archivo nuevo.
- [ ] Namespace correcto (`App\Controllers`, `App\Services`, etc.).
- [ ] `final class X extends Y` cuando corresponde.
- [ ] `unset($input['id_X'])` en cada `update()`.
- [ ] `Response::fromException` en el catch del thin.
- [ ] `requirePageAccess` + `requireOperation` en cada verbo HTTP.
- [ ] Métodos invocados del service: existen en el service del dump
      (o están listados como "a crear").
- [ ] Métodos invocados del repository: existen en el repository del
      dump (o están listados como "a crear").
- [ ] Campos del JSON: nombres post-ADR 016.
- [ ] Campos del body: mismos nombres que el frontend manda (o
      documentado como deuda 139b-3).
- [ ] No se tocó `bd_redytelca.sql`.
- [ ] No se tocó ningún otro archivo fuera de la lista aprobada.
- [ ] No se inventaron patrones (paginación, modales, etc.).
- [ ] Excepciones: clases correctas, mensajes correctos.
- [ ] Triggers de BD: el controller no los bypasea; si el insert
      choca con un trigger (ej. estado inicial forzado), el service
      lo maneja con transición por UPDATE.

Si algo falla: rechazar el artefacto y pedir corrección con el
diagnóstico exacto.

═══════════════════════════════════════════════════════════════════════
TRAZABILIDAD
═══════════════════════════════════════════════════════════════════════

Al cerrar el turno (tras verificación verde):
- Actualizar `MEMORY.md` (spec cerrada, bugs encontrados, deudas).
- Actualizar `docs/ROADMAP.md` (spec cerrada → histórico).
- Documentar deudas nuevas en la tabla de `MEMORY.md`.
```

---

## PROMPT-MOD-SQL

```markdown
# PROMPT DE MODIFICACIÓN DE BASE DE DATOS — REDYTELCA

## Rol
Actúas como IA auditora. Redactas el script SQL y los comandos de
ejecución/verificación. El humano los ejecuta en cmd. Tú auditas la
salida. Prohibido ejecutar SQL tú mismo.

## Script
[Nombre exacto del archivo. Ej: `2026-10-18-139c-pedidos-rbac.sql`.]

## Tipo de cambio
[DDL / DML / mixto / migración con datos / seed / trigger / vista.]

## Cambio solicitado
[CONTRATO EXACTO. Qué tablas, qué columnas, qué constraints, qué
índices, qué triggers, qué vistas, qué filas. Verificado contra
bd_redytelca.sql en el turno actual.]

## Fundamento
[ADR, hallazgo de auditoría, spec. Ej: "Plan 139c opción B",
"ADR 016", "hallazgo D14".]

═══════════════════════════════════════════════════════════════════════
REGLAS DURAS (no negociables)
═══════════════════════════════════════════════════════════════════════

R1. **Auditar el dump, no la memoria.**
    Leer `bd_redytelca.sql` del dump en el turno actual. Verificar
    nombres exactos de tablas, columnas, constraints, triggers,
    vistas, FKs. Prohibido citar de memoria.

R2. **Verificar CHECKs en `information_schema`.**
    phpMyAdmin no exporta CHECKs de `ALTER TABLE ... ADD CONSTRAINT`.
    Para auditar CHECKs, usar:
      SELECT TABLE_NAME, CONSTRAINT_NAME, CHECK_CLAUSE
      FROM information_schema.CHECK_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = 'bd_redytelca';
    o `mysqldump` desde CLI. Nunca el dump de phpMyAdmin
    (VERIFICACION.md §2.6).

R3. **Backup previo OBLIGATORIO** para:
    - Cualquier DDL (ALTER, DROP, CREATE TABLE, CREATE VIEW,
      CREATE TRIGGER).
    - Cualquier DML sobre tablas del seed (RBAC, `configuracion_empresa`,
      catálogos).
    - Cualquier migración con datos.
    Comando:
      C:\xampp\mysql\bin\mysqldump.exe -u root --databases bd_redytelca ^
        > C:\backups\bd_redytelca_YYYYMMDD_HHMM_pre-<spec>.sql
    Verificar que el archivo pesa > 0 bytes.

R4. **Idempotencia cuando se pueda.**
    - `INSERT`: `INSERT IGNORE` si hay UNIQUE, o
      `INSERT ... SELECT ... WHERE NOT EXISTS` si no.
    - `ALTER`: no es idempotente en MariaDB 10.4. Documentar el
      rollback.
    - `DROP`: `DROP TABLE IF EXISTS` / `DROP VIEW IF EXISTS` /
      `DROP TRIGGER IF EXISTS`.

R5. **Diagnóstico previo antes de DDL destructivo.**
    Antes de `DROP COLUMN`, `DROP TABLE`, `ALTER ... MODIFY` con
    posible pérdida de datos, o cualquier cambio que dependa de que
    los datos actuales sean consistentes:
      SELECT ... FROM ... WHERE <condición que detectaría el problema>;
    Si devuelve filas, ABORTAR y limpiar antes de continuar.
    Ejemplo: P11/P12 hicieron esto para `equipos` y `servicios`.

R6. **Verificación embebida en el script.**
    Al final del script, SELECTs de control que devuelven conteos
    esperados. Ejemplo:
      SELECT 'pagina_pedidos' AS check_name, COUNT(*) AS n
      FROM paginas WHERE url_pagina = 'pedidos';
    Los conteos esperados se documentan en el prompt.

R7. **Verificación INDEPENDIENTE post-ejecución.**
    Además de la verificación embebida, el humano ejecuta SELECTs
    independientes (no desde el script) para confirmar:
    - `information_schema` (estructura).
    - Conteos de filas.
    - FKs, UNIQUE, CHECKs.
    - Triggers registrados.
    - `auditoria_logs` (si los triggers corrieron).

R8. **Rollback explícito.**
    Cada script lleva un bloque `-- ROLLBACK` con los comandos
    exactos para revertir el cambio. Si el rollback no es trivial,
    documentar "restaurar backup previo".

R9. **Ejecución vía cmd con ruta completa.**
    El humano ejecuta:
      C:\xampp\mysql\bin\mysql.exe -u root bd_redytelca ^
        < ruta\al\archivo.sql
    Sin `mysql` pelado. Sin `source`. Sin phpMyAdmin.

R10. **No tocar columnas/tablas no mencionadas en el contrato.**
    Si el script necesita tocar algo adicional (ej. ajustar un CHECK
    colateral), listarlo por separado y esperar confirmación.

R11. **Collation y charset.**
    Toda tabla nueva: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci`. Verificar contra el estándar del
    dump.

R12. **Triggers de auditoría.**
    Si se crea una tabla nueva que requiere auditoría, los triggers
    `trg_<tabla>_audit_{insert,update,delete}` se crean en el mismo
    script o en uno separado (documentar cuál). Leer
    `auditoria_logs` con `COALESCE(@app_tipo_actor, 'sistema')` etc.

R13. **Idempotencia de triggers.**
    `DROP TRIGGER IF EXISTS` antes de `CREATE TRIGGER` si el script
    puede re-ejecutarse.

R14. **Nombres de constraints.**
    Patrón del proyecto: `chk_<tabla>_<regla>`, `fk_<tabla>_<ref>`,
    `uq_<tabla>_<cols>`, `idx_<tabla>_<cols>`. Verificar contra el
    dump.

═══════════════════════════════════════════════════════════════════════
PLANTILLA DE SCRIPT
═══════════════════════════════════════════════════════════════════════

-- =================================================================
-- <Spec/Deuda> — <Título corto>
-- Fecha: YYYY-MM-DD
-- Autorizado por: <spec/ADR/hallazgo>
-- BACKUP PREVIO OBLIGATORIO:
--   C:\backups\bd_redytelca_YYYYMMDD_pre-<spec>.sql
--
-- MOTIVO
-- <2-5 líneas explicando por qué se cambia.>
--
-- ALCANCE
-- <Lista numerada de cambios.>
--
-- IDEMPOTENTE
-- <Sí/No. Si sí, cómo.>
--
-- ROLLBACK
-- <Comandos exactos para revertir.>
-- =================================================================

USE bd_redytelca;
SET NAMES utf8mb4;
-- SET time_zone = '+00:00'; -- Solo si el script toca timestamps.

-- PASO 1 — <descripción>
<SQL>

-- PASO 2 — <descripción>
<SQL>

-- =================================================================
-- VERIFICACIÓN
-- Los contadores esperados: X, Y, Z.
-- =================================================================
SELECT '<check_name>' AS check_name, COUNT(*) AS n FROM ...;

-- =================================================================
-- Fin <spec>.
-- =================================================================

═══════════════════════════════════════════════════════════════════════
PROCEDIMIENTO (orden estricto)
═══════════════════════════════════════════════════════════════════════

P1. Leer del dump: `bd_redytelca.sql` COMPLETO (o las secciones que
    toca) + `information_schema` si aplica.
P2. Verificar nombres exactos de tablas, columnas, constraints,
    triggers, vistas, FKs.
P3. Verificar CHECKs en `information_schema`.
P4. Redactar el script con la plantilla.
P5. Entregar al humano los comandos de:
    - Backup previo (Paso 0).
    - Ejecución (Paso 1).
    - Verificación independiente (Paso 2).
    - Auditoría de triggers (Paso 3, opcional).
P6. El humano ejecuta y pega las salidas literales.
P7. Auditar las salidas contra los conteos esperados.
P8. Si todo verde, cerrar. Si algo rojo, diagnosticar y pedir
    corrección.

═══════════════════════════════════════════════════════════════════════
COMANDOS DE EJECUCIÓN Y VERIFICACIÓN (formato §5.5)
═══════════════════════════════════════════════════════════════════════

**Paso 0 — Backup.**
C:\xampp\mysql\bin\mysqldump.exe -u root --databases bd_redytelca ^
  > C:\backups\bd_redytelca_YYYYMMDD_HHMM_pre-<spec>.sql
Esperado: archivo > 0 bytes. Precondición: C:\backups\ existe.

**Paso 1 — Ejecución.**
C:\xampp\mysql\bin\mysql.exe -u root bd_redytelca ^
  < ruta\al\archivo.sql
Esperado: sin errores. Salida del SELECT final: X, Y, Z.
Precondición de abort: cualquier contador distinto.

**Paso 2 — Verificación independiente.**
<SELECTs de information_schema, conteos, FKs, UNIQUE, CHECKs>
Esperado: <cada SELECT con su conteo esperado>.

**Paso 3 — Auditoría (opcional, si hay triggers).**
<SELECT desde auditoria_logs>
Esperado: N filas INSERT/UPDATE/DELETE recientes.

═══════════════════════════════════════════════════════════════════════
ROLLBACK
═══════════════════════════════════════════════════════════════════════

- Si el script es idempotente y no destructivo: re-ejecutar con
  `-- ROLLBACK` (los comandos exactos van en el script).
- Si el script es destructivo: restaurar el backup previo con
  C:\xampp\mysql\bin\mysql.exe -u root bd_redytelca ^
    < C:\backups\bd_redytelca_YYYYMMDD_HHMM_pre-<spec>.sql
- Documentar en `MEMORY.md` que se restauró y por qué.
```

---

## PROMPT-VERIFY

```markdown
# PROMPT DE VERIFICACIÓN — REDYTELCA

## Rol
Actúas como IA auditora. Das los pasos numerados con comando + Esperado
+ precondición de abort. El humano ejecuta y pega las salidas
literales. Tú auditas. Prohibido ejecutar tú mismo.

## Modo
[MODO A: SQL | MODO B: Código]

## Alcance
[Qué se verifica. Ej: "Migración 139c-pedidos-rbac", "Tanda A de 139c:
NodoController + OltController + NapController + EquipoController +
4 thin".]

## Precondiciones
- Backup aplicado (si es modo A).
- Artefacto aplicado (si es modo B).
- BD en estado conocido (dummies eliminados, seed restaurado).

═══════════════════════════════════════════════════════════════════════
REGLAS DURAS (aplican a ambos modos)
═══════════════════════════════════════════════════════════════════════

R1. **Un comando por paso.**
    Nunca dos comandos en un bloque.

R2. **Cada paso con "Esperado:".**
    Sin estado esperado, el humano no sabe si pasó.

R3. **Cada paso con precondición de abort.**
    Si el test depende de un estado, se declara. Ej: "si hay más de 1
    fila, paras y me pegas la salida".

R4. **Restauración al final.**
    Todo test que modifica estado se restaura en pasos posteriores.
    El último paso confirma que el seed no se alteró.

R5. **Verificación de no-daño.**
    El último paso confirma que el seed no se alteró (conteos de filas
    por tabla, `auditoria_logs` sin DELETEs del seed, etc.).

R6. **Solo CLI.**
    curl + `mysql -e` + `php -l` + `findstr`. Navegador prohibido
    hasta 140 (VERIFICACION.md §2.10).

R7. **Ruta completa a mysql.**
    `C:\xampp\mysql\bin\mysql.exe -u root -e "..."`. No `mysql` pelado.

R8. **Ruta completa a mysqldump.**
    `C:\xampp\mysql\bin\mysqldump.exe -u root ...`.

R9. **El agente no ejecuta.**
    Da comandos. El humano corre. El agente audita.

R10. **Respuesta corta.**
    Si el humano no pregunta "por qué", no se explica. Verde: 1 línea
    de confirmación por paso. Rojo: diagnóstico + comando de
    corrección + re-test.

R11. **Salidas literales.**
    El humano pega las salidas SIN resumir, SIN interpretar. Si un
    paso falló, el error completo.

R12. **No asumir estado.**
    Si el humano no pegó una salida, no se asume verde. Se pide.

═══════════════════════════════════════════════════════════════════════
MODO A — VERIFICACIÓN SQL
═══════════════════════════════════════════════════════════════════════

### Cuándo se usa
Después de aplicar un script `PROMPT-MOD-SQL`. Antes de cerrar la
migración.

### Verificaciones obligatorias

**A1. Estructura.**
- `SHOW CREATE TABLE <tabla>;` para cada tabla modificada.
- `SHOW INDEX FROM <tabla>;` para cada tabla con índices nuevos.
- `SHOW COLUMNS FROM <tabla> LIKE '<col>';` para cada columna nueva.
- `information_schema.CHECK_CONSTRAINTS` para CHECKs.
- `information_schema.KEY_COLUMN_USAGE` para FKs.
- `information_schema.VIEWS` para vistas nuevas.
- `information_schema.TRIGGERS` para triggers nuevos.

**A2. Conteos de filas.**
- `SELECT COUNT(*) FROM <tabla>;` por cada tabla tocada.
- Comparar contra el conteo esperado (documentado en el script).

**A3. Integridad de datos.**
- FKs huérfanas: `SELECT ... FROM <tabla_hija> WHERE <fk> NOT IN (SELECT id FROM <tabla_padre>);` → 0 filas.
- UNIQUE duplicados: `SELECT <cols>, COUNT(*) FROM <tabla> GROUP BY <cols> HAVING COUNT(*) > 1;` → 0 filas.
- CHECKs violados: `SELECT ... FROM <tabla> WHERE NOT (<regla>);` → 0 filas.
- NULLs en columnas NOT NULL: `SELECT COUNT(*) FROM <tabla> WHERE <col> IS NULL;` → 0.

**A4. Consistencia entre tablas.**
- Si el cambio insertó en `paginas`, verificar que `rol_modulo_pagina`
  y `rol_modulo_permiso` apuntan al `id` correcto (no hardcoded).
- Si el cambio insertó en una tabla con FK compuesta, verificar que
  la FK se satisface.

**A5. Auditoría.**
- `SELECT accion, tabla_afectada, COUNT(*) FROM auditoria_logs WHERE creado_en > NOW() - INTERVAL 5 MINUTE GROUP BY accion, tabla_afectada;` → confirmar que los triggers corrieron.
- `SELECT COUNT(*) FROM auditoria_logs;` → comparar con el conteo pre-migración + filas esperadas.

**A6. No-daño al seed.**
- Conteos de filas por tabla del seed (`clientes`, `servicios`,
  `facturas`, `pagos`, `contratos`, `planes`, `usuarios`,
  `usuario_rol`, `rol_modulo_pagina`, `rol_modulo_permiso`).
- Comparar contra el dump original. Si el conteo subió por inserts
  legítimos del script, documentarlo. Si subió por inserts no
  esperados, ABORTAR.

### Formato de pasos

**Paso 1 — <qué hace>.**
```
C:\xampp\mysql\bin\mysql.exe -u root -e "USE bd_redytelca; <SQL>;"
```
**Esperado:** <salida esperada, fila por fila si son pocas, o conteo>.
**Precondición de abort:** <si aplica>.

(Repetir por cada verificación A1–A6.)

═══════════════════════════════════════════════════════════════════════
MODO B — VERIFICACIÓN DE CÓDIGO
═══════════════════════════════════════════════════════════════════════

### Cuándo se usa
Después de aplicar un artefacto de `PROMPT-MOD-CODE`. Archivo por
archivo. Antes de cerrar la tanda.

### Verificaciones obligatorias

**B1. Sintaxis (estático).**
- `php -l <archivo.php>` para cada archivo PHP.
- `node --check <archivo.js>` para cada archivo JS (si node está
  disponible; si no, `php -l` no aplica y se declara).
- Verificar que cada archivo nuevo existe en la ruta correcta.

**B2. Contrato estático (archivo por archivo).**
Para cada archivo tocado:
- ¿Declara `namespace` correcto?
- ¿Declara `final class X extends Y`?
- ¿Los métodos públicos coinciden con el contrato?
- ¿Las excepciones lanzadas son las del contrato?
- ¿Los métodos invocados del service existen?
- ¿Los métodos invocados del repository existen?
- ¿Los nombres de tabla/columna existen en `bd_redytelca.sql`?
- ¿`unset($input['id_X'])` está en cada `update()`?
- ¿`Response::fromException` está en el catch del thin?
- ¿`requirePageAccess` + `requireOperation` están en cada verbo?
- ¿Indentación 4 espacios?
- ¿No `?>` al final (PHP puro)?
- ¿No hay `id_*` en el body (solo `*_id` post-ADR 016)?

**B3. Contrato JSON backend↔frontend.**
Para cada endpoint:
- Ejecutar `GET` con curl autenticado.
- Capturar el JSON.
- Comparar los campos con los que el frontend lee.
- Si el frontend viejo (app.js) manda `id_servicio`, documentar
  deuda 139b-3 y NO adaptar el backend.
- Si el endpoint nuevo devuelve un campo nuevo que el frontend no
  conoce, documentarlo.

**B4. Bugs de backend (por endpoint, por verbo).**
Para cada endpoint thin:
- **GET:** `curl -i -s -H "X-Session-Token: <token>" http://127.0.0.1/redytelca/<endpoint>` → 200 + JSON válido. Si no, diagnosticar.
- **GET (sin token):** → 401. Si devuelve 200, ABORTAR.
- **GET (rol sin permiso):** → 403. Si devuelve 200, ABORTAR.
- **POST (payload válido):** → 200 + `status:success`. Verificar en BD que la fila se insertó.
- **POST (payload inválido):** → 422 + `errors[]`. Verificar que NO se insertó.
- **POST (duplicado UNIQUE):** → 409. Verificar que NO se duplicó.
- **POST (FK rota):** → 409.
- **POST (trigger SIGNAL 1644):** → 422 con el mensaje del trigger.
- **PUT (id inexistente):** → 404. Si devuelve 200, ABORTAR.
- **PUT (payload válido):** → 200. Verificar en BD que la fila cambió.
- **PUT (id en el body):** verificar que `unset($input['id_X'])` lo
  ignoró. La fila con `id=X` no debe cambiar de `id`.
- **DELETE (con dependencias):** → 409 o 422.
- **DELETE (sin dependencias):** → 200. Verificar en BD que la fila
  se borró.
- **Método no soportado:** → 405.

**B5. Bugs de UI.**
Para cada endpoint que el frontend consume:
- ¿El JSON devuelve todos los campos que el frontend lee?
- ¿El tipo de cada campo coincide (int vs string, null vs vacío)?
- ¿El frontend viejo puede crear/editar con el nuevo contrato? Si
  no, es deuda 139b-3 (no bug).
- ¿El sidebar del frontend viejo mostrará la nueva página? Si no
  hay `showPage('pedidos')` en `app.js`, es deuda 139c-2.

**B6. Bugs de BD.**
Para cada operación exitosa:
- ¿La fila se insertó/actualizó/borró en la tabla correcta?
- ¿Los FKs se respetaron?
- ¿Los CHECKs se respetaron?
- ¿Los triggers corrieron? (`auditoria_logs` debe tener filas).
- ¿Los UNIQUE no se violaron?

**B7. Conexión entre capas.**
- **Thin → Controller:** ¿el thin instancia el controller correcto?
- **Controller → Service:** ¿el controller invoca el método correcto
  del service?
- **Service → Repository:** ¿el service invoca el método correcto
  del repository?
- **Repository → BD:** ¿el SQL usa los nombres de columna correctos?
- **BD → Frontend:** ¿el JSON que devuelve el controller coincide
  con lo que el frontend espera?

**B8. Bugs de backend ↔ UI ↔ BD (integración).**
- Flujo completo: frontend (o curl) → thin → controller → service →
  repository → BD → respuesta → JSON → frontend.
- Verificar que el dato viaja correcto en cada salto.
- Verificar que las validaciones se aplican en el salto correcto
  (el controller valida con el validator, el service valida reglas
  de negocio, el repository no valida).
- Verificar que las transacciones envuelven las operaciones
  multi-tabla.

**B9. Seguridad (por endpoint).**
- **Auth:** sin token → 401.
- **Auth:** token expirado → 401.
- **Auth:** token revocado → 401.
- **RBAC nivel 1:** rol sin visibilidad → 403.
- **RBAC nivel 2:** rol sin permiso → 403.
- **SoD:** acciones sensibles requieren rol 1.
- **PII:** errores no exponen `$e->getMessage()`.
- **SQL injection:** el endpoint usa prepared statements.

**B10. No-daño al seed.**
- Conteos de filas por tabla del seed.
- Comparar contra el dump original.
- Si el conteo cambió por inserts legítimos del test, documentarlo.
- Si cambió por inserts no esperados, ABORTAR.

### Formato de pasos

**Paso 1 — Verificar sintaxis de los archivos tocados.**
```
php -l controllers/NodoController.php
```
**Esperado:** "No syntax errors detected in controllers/NodoController.php".

**Paso 2 — Verificar contrato estático (NodoController).**
(Comandos de grep/findstr para verificar `unset`, `Response::fromException`, `requirePageAccess`, etc.)
**Esperado:** cada grep devuelve N líneas (las del contrato).
**Precondición de abort:** si un grep devuelve 0, ABORTAR.

**Paso 3 — GET api_nodos autenticado como Admin.**
```
curl -i -s -H "X-Session-Token: <token_admin>" http://127.0.0.1/redytelca/api_nodos.php
```
**Esperado:** HTTP 200 + JSON con `status:success` y `nodos:[...]`.
**Precondición de abort:** si devuelve 500, 404 o 401, diagnosticar.

(Repetir por cada verificación B1–B10, archivo por archivo,
endpoint por endpoint.)

═══════════════════════════════════════════════════════════════════════
CHECKLIST DE CIERRE (ambos modos)
═══════════════════════════════════════════════════════════════════════

- [ ] Todos los pasos ejecutados y verificados.
- [ ] Todos los "Esperado" verdes.
- [ ] No se alteró el seed.
- [ ] `auditoria_logs` coherente (si aplica).
- [ ] Documentar en `MEMORY.md` y `docs/ROADMAP.md` según corresponda.
- [ ] Si se detectaron deudas, registrarlas en la tabla de
      `MEMORY.md`.
```

---

## Cómo se encadenan los 4 prompts

```
[TURNO N]    Humano pide un cambio.
             ↓
             IA auditora decide el tipo:
             - ¿Doc?     → PROMPT-MOD-DOC
             - ¿Código?  → PROMPT-MOD-CODE
             - ¿SQL?     → PROMPT-MOD-SQL (uno o varios scripts,
                          en turnos separados si son destructivos)
             ↓
[TURNO N+1]  Humano aplica (docs/código) o ejecuta (SQL).
             ↓
             IA auditora audita el artefacto (docs/código) o las
             salidas (SQL).
             ↓
             Si es SQL: PROMPT-VERIFY modo A.
             Si es código: PROMPT-VERIFY modo B.
             ↓
[TURNO N+2]  Si todo verde: cerrar spec/deuda en MEMORY.md y
             ROADMAP.md. Si rojo: diagnosticar y corregir.
```

**Regla de orden:** SQL primero, código después. La BD es la fuente de verdad (constitución §3). El código se adapta al esquema, no al revés.

**Regla de turnos:** un script SQL destructivo por turno. Un artefacto de código por turno. Un doc por turno. No se mezclan.

