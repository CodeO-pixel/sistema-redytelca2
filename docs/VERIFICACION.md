# Verificación — Lecciones y checklists

Documento vivo. Registra lecciones aprendidas durante verificaciones
end-to-end, para que los mismos errores no se repitan. Se amplía cada
vez que se detecta un fallo evitable en el proceso de verificación.

Complementa a `docs/METODOLOGIA_DESARROLLO_IA.md` (que define el flujo)
y a `docs/GOBERNANZA.md` (que define dónde vive cada cosa).

---

## 1. Propósito

Este documento existe por una razón concreta: **durante las
verificaciones end-to-end se han descubierto problemas que debieron
detectarse antes de generar código**, no después. Cada lección de este
documento cierra un hueco concreto en el proceso.

---

## 2. Lecciones aprendidas

### 2.1 — Antes de aplicar un gate de nivel 2, verificar que la página
tiene endpoint CRUD propio

**Origen:** cierre del Bloque 17 (RBAC dos niveles) y ejecución del
Grupo C, octubre 2026.

**Qué pasó:** se aplicó visibilidad (`rol_modulo_pagina`) a las 21
páginas del sistema, incluyendo `dash`, `mapa`, `reportes`,
`notificaciones` y `password`. Al aplicar el nivel 2 (Grupo C) sobre
los CRUD, se descubrió que esas páginas **no tienen endpoint CRUD
propio** — se renderizan desde cachés ya llenadas por otros endpoints.
Sus checkboxes de operación (`leer`, `crear`, `editar`, `eliminar`,
`validar`) son huérfanos: no controlan nada.

**Por qué no se detectó:** el scope del Bloque 17 y del Grupo C listaba
las páginas del catálogo sin preguntarse si cada una tiene endpoint.

**Regla derivada:** antes de aplicar un gate de nivel 2 a una página,
verificar:

1. ¿Existe un endpoint CRUD propio para esa página? (`api_X.php` o
   `X_listar.php` / `X_crear.php` / etc.)
2. Si NO existe, la página solo participa del nivel 1 (visibilidad).
3. Si la página se renderiza desde cachés de otros módulos, el dato ya
   está protegido por los permisos de origen.

**Aplicación:** hallazgos operativos MAP-01 y EST-01 en `MEMORY.md`.

### 2.2 — Verificar el ciclo completo logout → login → cambio de rol,
no solo la sesión fresca

**Origen:** verificación end-to-end del Bloque 17 y del Grupo C,
octubre 2026.

**Qué pasó:** durante la verificación del Bloque 17 se probó
`GET /api_facturas.php` con una sesión fresca. Devolvió 200 correcto.
En la verificación del Grupo C se probó el mismo endpoint con un
cambio de rol (Admin → Operador) sin limpiar el navegador. El backend
respondió 403 correcto, pero **el frontend siguió mostrando los datos
cacheados** porque `clearSessionContext()` no limpia las cachés
`window.*Cache`.

**Por qué no se detectó:** la verificación de sesión fresca no expone
el problema. El problema solo aparece cuando se cambia de rol en el
mismo navegador.

**Regla derivada:** en cualquier verificación end-to-end de permisos:

1. Probar con sesión fresca (incógnito limpio).
2. Probar con **ciclo logout → login → cambio de rol** en el mismo
   navegador.
3. Probar con sesión de rol A → logout → sesión de rol B → verificar
   que la UI refleje el nuevo rol, no el anterior.
4. Si la UI no cambia pero el backend responde 403, el bug está en el
   frontend (manejo de 401/403 + limpieza de cachés).

**Aplicación:** refuerzo del hallazgo F08 y hallazgo operativo
"cachés no se limpian al logout" en `MEMORY.md`.

### 2.3 — En la verificación end-to-end, distinguir el fallo del backend
y el fallo del frontend

**Origen:** misma sesión.

**Qué pasó:** al probar el Grupo C, la UI mostraba datos que el backend
ya estaba rechazando con 403. Fue necesario abrir DevTools → Network
para ver el 403 real y separar "backend correcto" de "frontend
incorrecto".

**Regla derivada:** en la verificación end-to-end, siempre abrir
DevTools → Network y verificar el código HTTP real de la petición.
Nunca dar por sentado que "la UI muestra datos" significa "el backend
los autorizó".

### 2.4 — Cuando el CSS "se ve raro de golpe", sospechar del selector universal

**Origen:** cierre de spec 021, octubre 2026.

**Qué pasó:** tras aplicar los cambios de DOC-03 a `styles.css`, la UI se
vio completamente descuadrada (sidebar pegado al contenido, botón hamburguesa
sin margen, layout roto). Causa raíz: al pegar el archivo, se perdió el `*`
inicial de la regla universal:

    * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }

quedó como:

    { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }

Sin el `*`, la regla es CSS inválido. El navegador la ignora completamente.
`box-sizing: border-box` deja de aplicarse a todos los elementos, y el ancho
de cada caja se calcula sumando padding + border, lo que descuadra todo el
layout (sidebar, contenido, inputs, botones).

**Por qué no se detectó antes:** el archivo pasó por auditoría de contenido
(colores correctos, sin residuos, estructura íntegra) pero no por validación
de sintaxis. VS Code sí lo marcó en rojo, pero nadie lo abrió hasta que la UI
se vio mal.

**Regla derivada:**

1. Después de cualquier cambio a `styles.css`, abrir el archivo en VS Code y
   verificar que **no hay líneas en rojo** ni advertencias de sintaxis.
2. Verificar específicamente que la primera regla del archivo empiece con `*`,
   `:root`, `html`, `body` o un selector válido. Nunca con `{`.
3. Cuando un CSS "se ve raro de golpe" (todo descuadrado, sidebar pegado,
   inputs sin borde), el primer sospechoso es un selector universal roto o
   llaves desbalanceadas.
4. El navegador no reporta errores CSS en consola por reglas inválidas — las
   ignora silenciosamente. Solo DevTools → Elements → Styles muestra las
   reglas tachadas o ausentes.

**Aplicación:** spec 021, cierre del bloque. `styles.css` corregido a mano
por el humano (un carácter).

### 2.5 — En CSS, el orden de `<link>` importa. El archivo que debe imponer su valor va último.

**Origen:** cierre de spec 024 (branding público separado), octubre 2026.

**Qué pasó:** se creó `branding.css.php` para inyectar `--accent` desde la BD
sin FOUC. Se colocó ANTES de `styles.css` en el `<head>`, argumentando que
"gana en la cascada". Falso: a igual especificidad, en CSS gana la regla
que aparece **después**. `styles.css` sobreescribía con su default
`#104aa8`. El flash azul persistía durante ~1 segundo, hasta que `app.js`
aplicaba un inline style sobre `<html>`.

**Por qué no se detectó antes:** el argumento "va primero, por eso gana"
es contraintuitivo respecto a la lógica de otros contextos (prioridad
de reglas con `!important`, o inline styles sobre stylesheets). La
jerarquía real de aplicación de CSS es:

1. `!important`
2. Inline style (`element.style`)
3. Selector más específico
4. Orden de aparición (a igual especificidad, gana el último)

**Regla derivada:**

1. En CSS, si dos reglas tienen la misma especificidad, gana la última
   que aparece en el documento. El orden de `<link rel="stylesheet">`
   es significativo.
2. Un archivo que debe **sobrescribir** valores de otro va **después**
   del otro, no antes.
3. Un archivo que solo debe **definir defaults** va primero.
4. Los inline styles (`<style>` en el `<head>` o
   `element.style.setProperty()`) ganan a cualquier hoja externa a
   igual especificidad. Útil para overrides dinámicos.
5. Antes de afirmar "esto gana en la cascada", verificar la
   especificidad real y el orden de carga.

**Aplicación:** `index.html` → `styles.css` primero, `branding.css.php`
después. Verificado end-to-end: incógnita nueva limpia, login en rojo
desde el primer frame, sin flash.

### 2.6 — Para auditar CHECK constraints, usar `information_schema` o `mysqldump`. Nunca phpMyAdmin.

**Origen:** auditoría del bloque BD-COMPLETA, octubre 2026.

**Qué pasó:** al comparar `bd_redytelca_schema.sql` (que documentaba ~30
CHECKs) contra `bd_redytelca.sql` exportado con phpMyAdmin 5.2.1, apareció
que **la mayoría de los CHECKs faltaban en el dump**. Se reportó como
hallazgo crítico (DB-14: "P8b descartó 30 CHECK constraints"). El usuario
corrió la query a `information_schema.CHECK_CONSTRAINTS` y devolvió **39
filas**: todos los CHECKs que se creían perdidos estaban vivos en la BD.
El problema no era la BD — era la herramienta de exportación.

**Por qué no se detectó:** se asumió que el dump de phpMyAdmin era una
representación fiel del esquema. No lo es: phpMyAdmin **no exporta CHECK
constraints** añadidos con `ALTER TABLE ... ADD CONSTRAINT ... CHECK(...)`.
Solo incluye los definidos inline en la columna (ej. `auditoria_logs.old_value`
con `CHECK (json_valid(...))`). Es una limitación conocida de la
herramienta, no una pérdida de datos.

**Regla derivada:**

1. **Nunca usar un dump de phpMyAdmin para auditar CHECK constraints.**
   La herramienta los omite silenciosamente.
2. **Para verificar CHECKs**, usar una de estas dos vías:
   - `SELECT TABLE_NAME, CONSTRAINT_NAME, CHECK_CLAUSE FROM
     information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA =
     'bd_redytelca' ORDER BY TABLE_NAME, CONSTRAINT_NAME;`
   - `mysqldump -u root bd_redytelca > dump.sql` desde CLI. `mysqldump`
     **sí** exporta CHECK constraints, a diferencia de phpMyAdmin.
3. **Antes de reportar un CHECK como "perdido"**, verificar en
   `information_schema` primero.
4. **Aplicar la misma cautela a otros objetos que phpMyAdmin pueda
   omitir:** eventos, procedimientos almacenados (phpMyAdmin sí los
   exporta normalmente, pero verificar caso por caso), y `DEFINER`
   clauses (que phpMyAdmin normaliza). El dump de phpMyAdmin es útil
   para tablas, columnas, índices, FK, triggers y datos, pero no es
   exhaustivo para constraints de nivel tabla.
5. **`CONVERT TO CHARACTER SET` de MariaDB 10.4 reconstruye la tabla.**
   Aunque el dump no muestre los CHECKs, la BD los conserva. No asumir
   pérdida sin verificar con `information_schema`.

**Aplicación:** DB-14 cerrado como falso positivo. La lección se añadió
a `AGENTS.md` §"Trampas conocidas del dominio" y a `MEMORY.md`
§"Aprendizajes recientes".

### 2.7 — Un método `count*` de repositorio debe replicar el filtro del endpoint que lo consume

**Origen:** spec 136b del Bloque 29 (MVC), octubre 2026.

**Qué pasó:** `ServicioRepository::countByNapId` se implementó como
`SELECT COUNT(*) FROM servicios WHERE nap_id = ?`, sin filtrar por
`estado_comercial`. El contrato declaraba el método como “usado por
`api_naps.php` → `total_clientes`”, y ese endpoint filtra por
`estado_comercial != 'retirado'`. El repo devolvía un número mayor que
el que la UI mostraba históricamente en cuanto había servicios
retirados en la NAP.

**Regla derivada:**
1. Si un método `count*` de repositorio se declara “usado por X
   endpoint”, la query debe replicar **exactamente** el filtro del
   endpoint, no una versión más laxa.
2. Si el repo necesita una versión genérica (sin filtro) y el endpoint
   necesita una filtrada, se declaran **dos métodos distintos** con
   nombres que lo distingan (`countByNapId` vs
   `countActivosByNapId`, por ejemplo). Nunca un solo método ambiguo.
3. Antes de cerrar un repo, verificar contra el endpoint consumidor
   (en el dump) qué filtros aplica ese endpoint sobre la tabla.

**Aplicación:** `ServicioRepository::countByNapId` corregido para
incluir `AND estado_comercial != 'retirado'`. Docblock actualizado.

### 2.8 — AI Studio ignora sistemáticamente la instrucción de indentación

**Origen:** specs 136b, 136c, 136d del Bloque 29 (MVC), octubre 2026.

**Qué pasó:** en tres tandas consecutivas de repositorios, AI Studio
entregó los archivos sin indentación (0 espacios), pese a que el prompt
exigía explícitamente "Indentación 4 espacios" y, en la tercera, "NO
entregues los archivos sin indentar". El fallo es sistemático, no
puntual.

**Consecuencia:** no rompe funcionalidad, pero rompe la consistencia
con los archivos de 136a y con `BaseRepository`. Obliga a reindentar
manualmente en cada auditoría.

**Regla derivada:**
1. Asumir que AI Studio puede no indentar. Verificar siempre la
   indentación como primer punto de la auditoría de forma.
2. Reforzar la instrucción en cada prompt con la frase "si el archivo
   no queda indentado, el resultado se rechaza en auditoría".
3. Si la reincidencia continúa en 136e/136f, considerar como parte del
   proceso: pedir a AI Studio el archivo dentro de un bloque markdown
   ```php para forzar el formato, o reindentar en el paso de auditoría
   antes de devolverlo al humano.

**Aplicación:** los 4 archivos de 136d se reindentaron en la auditoría.
La instrucción del prompt se mantiene; el chequeo de indentación es
obligatorio en cada auditoría de 136.

### 2.9 — `revocarByToken` no debe usar `rowCount()` (extensión de §2.7 a repositorios)

**Origen:** spec 136f del Bloque 29 (MVC), octubre 2026.

**Qué pasó:** `SesionRepository::revocarByToken` retornaba
`rowCount() > 0`. Un segundo logout sobre el mismo token (pestaña
duplicada, doble clic, reintento de red) ejecuta el UPDATE con los
mismos valores (`expira_en = UTC_TIMESTAMP()`, `revocado_en =
UTC_TIMESTAMP()`) y `rowCount()` devuelve 0. La sesión sigue
revocada, pero el repo retorna `false`, produciendo un falso 404 en
el service (spec 137).

**Regla derivada:**
1. El fix de §2.7 (`BaseRepository::update` → `exists()`) no cubre
   los métodos custom de UPDATE en repositorios hijos. Cada método
   que haga UPDATE debe decidir explícitamente cómo verificar éxito.
2. Para operaciones idempotentes (logout, revocación, soft-delete),
   el retorno correcto es "el registro existe post-operación", no
   "se modificó alguna fila". Usar `exists($id)` o
   `findByX(...) !== null`, no `rowCount()`.
3. Solo usar `rowCount() > 0` cuando el contrato semántico sea
   explícitamente "se modificó al menos una fila" (ej. DELETE
   físico en `BaseRepository::delete`).

**Aplicación:** `SesionRepository::revocarByToken` corregido para
retornar `findByToken($token) !== null`. Docblock actualizado.

### 2.10 — Verificación por CLI, no por navegador

**Origen:** cierre de 139a, octubre 2026.

**Qué pasó:** durante la verificación de 139a se intentó probar el flujo completo en navegador (login → menú → F5 → perfil). F5 mostró el modal de login aunque `auth_state.php` devolvía `authenticated:true`. Diagnóstico: `restoreSession()` en `app.js` no oculta `#login-overlay`, bug pre-existente que se resuelve en la reescritura modular de 140. Forzar la verificación visual contra un frontend roto es perder tiempo.

**Regla derivada:**

1. **Hasta que 140 cierre, la verificación es solo por CLI:** `curl -i -s`, `mysql -e "SELECT ..."`, `php -l`, `findstr`.
2. **El navegador se prohíbe** en el ciclo de verificación de 139b/139c/139d. Los bugs de `app.js` viejo no son del backend y no se arreglan en 139.
3. **El agente da pasos numerados.** Cada paso lleva comando + Esperado + precondición de abort. El humano ejecuta y pega la salida literal.
4. **Si un test requiere modificar estado del seed** (ej. desactivar `hebert`), se declara precondición y se restaura en pasos posteriores. El último paso siempre confirma que el seed no se alteró.
5. **El agente no ejecuta comandos.** Los da. El humano los corre.

**Aplicación:** todo el ciclo 139b–139d, y por extensión cualquier spec que dependa de frontend viejo.
---

## 3. Checklist de verificación (todo bloque con backend)

Antes de declarar un bloque con backend como cerrado:

- [ ] **Seed verificado** contra la BD real (queries directas, no
      asumir el dump).
- [ ] **Prueba negativa por rol:** cada endpoint devuelve 403 cuando
      el rol no tiene el permiso.
- [ ] **Prueba positiva por rol:** cada endpoint devuelve 200 cuando
      el rol tiene el permiso.
- [ ] **Prueba sin regresión:** Admin sigue funcionando con todos los
      endpoints.
- [ ] **Ciclo completo logout → login → cambio de rol:** verificado
      (§2.2).
- [ ] **DevTools → Network abierto** durante las pruebas (§2.3).
- [ ] **Verificar que el dominio de la página tiene endpoint propio**
      antes de aplicar un gate de nivel 2 (§2.1).
- [ ] **Verificar CHECKs contra `information_schema`, no contra el
      dump de phpMyAdmin** (§2.6).
- [ ] **Documentar hallazgos operativos** (lo que no se puede cerrar
      en el bloque actual).
- [ ] **CSS sin errores de sintaxis:** VS Code no marca líneas rojas en
      `styles.css`; el selector universal (`*`) está presente; no hay llaves
      desbalanceadas.
- [ ] **Verificación solo por CLI** mientras el frontend no esté reescrito (140). Navegador prohibido (§ 2.10).
- [ ] **Cada test con precondición declarada** y **restauración** posterior al cierre.

---

## 4. Cómo se actualiza este documento

Cuando una verificación falla por un motivo evitable:

1. Se identifica la causa raíz.
2. Se añade una lección a §2 con la regla derivada.
3. Se añade el check correspondiente a §3 (si aplica).

Si la lección se vuelve regla permanente del flujo: mover a
`docs/METODOLOGIA_DESARROLLO_IA.md` y dejar referencia aquí.

---

## 5. Referencias

- `docs/METODOLOGIA_DESARROLLO_IA.md` — flujo de trabajo.
- `docs/METODOLOGIA_AUDITORIA_IA.md` — marco de auditoría.
- `docs/GOBERNANZA.md` — mapa del ecosistema documental.
- `MEMORY.md` — hallazgos operativos vivos.