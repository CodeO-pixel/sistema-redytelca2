# Gobernanza del proyecto — REDYTELCA

Mapa del ecosistema documental. Define **dónde vive cada tipo de
información**, **qué debe y no debe contener cada archivo**, **cuándo se
actualiza**, y **quién puede modificarlo**.

Este documento es la **ley de la documentación**. Se sigue siempre. No
hay excepciones.

**Este documento NO define el ciclo de trabajo.** Eso vive en
`docs/METODOLOGIA_DESARROLLO_IA.md`. Aquí solo se dice **dónde va cada
cosa**.

---

## 0. Regla base

**Cada hecho vive en un solo lugar.** Si dos documentos dicen lo mismo,
uno está mal y hay que corregirlo.

**Estas reglas se siguen SIEMPRE.** No hay excepción "por esta vez",
"porque es rápido", "porque el humano lo pidió sin darse cuenta". Si
alguien pide algo que viola estas reglas, se para y se pregunta.

---

## 1. Leyes duras

La siguiente tabla dice **el único archivo que puede contener cada tipo
de información**. Ningún otro archivo puede tener esa información.

| Tipo de información | Único archivo que la contiene |
|---|---|
| Specs y su estado (pendiente / en progreso / cerrado / diferido / absorbido) | `docs/ROADMAP.md` |
| Estado operativo (fase activa, último cierre, próximo paso) | `MEMORY.md` |
| Hallazgos operativos vivos | `MEMORY.md` |
| Hallazgos canónicos de auditoría (D/B/F/S/AR/T/I/ISP) | `docs/AUDITORIA_2026.md` |
| Esquema de la BD (fuente de verdad) | `bd_redytelca.sql` |
| Esquema de la BD (prosa) | `docs/BD.md` |
| Cómo está construido el sistema (endpoints, middleware, flujos) | `docs/ARQUITECTURA.md` |
| Estándares aplicados, frases de defensa, industria descartada, aprendizajes, referencias de mercado | `docs/REFERENCIA.md` |
| Lecciones de verificación y checklists | `docs/VERIFICACION.md` |
| Decisiones arquitectónicas históricas | `docs/ADR/*.md` |
| Ciclo de trabajo, roles, reglas con AI Studio, rigor de fuentes, forma de trabajar, límites del agente | `docs/METODOLOGIA_DESARROLLO_IA.md` |
| Marco de auditoría (6 pasadas × 10 capas × contexto ISP) | `docs/METODOLOGIA_AUDITORIA_IA.md` |
| Principios innegociables del proyecto | `docs/constitution.md` |
| Reglas del repo (stack, comandos, convenciones de código, trampas del dominio, límites duros) | `AGENTS.md` |
| Dónde vive qué (este mapa) | `docs/GOBERNANZA.md` |
| Fuente de verdad del flujo de auth (token, sesiones) | `core/Auth.php` + `core/Bootstrap.php` + `login.php` + `auth_state.php` + `docs/ARQUITECTURA.md` |
| Fuente de verdad del RBAC (visibilidad + operación) | `bd_redytelca.sql` + `core/Auth.php` + `docs/ARQUITECTURA.md` + `docs/ADR/004,005,008` |

**Regla operativa:** si estás por añadir información a un archivo, y esa
información no corresponde al tipo que ese archivo contiene por esta
tabla, NO lo hagas. Busca el archivo correcto.

**Regla operativa 2:** si ves un dato duplicado, uno de los dos está de
más. Se corrige eliminando el que no corresponde a su archivo según la
tabla, no añadiendo más texto al otro.

---

## 2. Ficha de cada archivo

Para cada archivo: **rol único**, **qué debe tener**, **qué NO debe
tener**, **cuándo se actualiza** (y solo en esas ocasiones), **quién
puede modificarlo**.

---

### `docs/constitution.md`

- **Rol único:** ley superior. Principios innegociables.
- **Debe tener:** lista numerada de principios. Nada más.
- **NO debe tener:** estado del proyecto, specs, hallazgos, cómo funciona
  el sistema, flujo de trabajo, convenciones de código, referencias de
  industria.
- **Cuándo se actualiza:** solo si cambia un principio estructural. Casi
  nunca.
- **Quién modifica:** el humano, con aprobación explícita.
- **Quién lee:** todos. Primero.

---

### `AGENTS.md`

- **Rol único:** hoja de arranque del repo. Reglas del código y del
  repositorio.
- **Debe tener:** stack, comandos, convenciones de código (nombres,
  hashing, sesiones, PHP puro sin `?>`), trampas del dominio (1:N, ejes
  de estado, guard SoD), límites duros del repo (siempre / preguntar
  antes / nunca), verificación, memoria (cuándo leer/escribir
  `MEMORY.md`).
- **NO debe tener:** ciclo de trabajo, roles, reglas con AI Studio,
  rigor de fuentes, forma de trabajar (viven en
  `METODOLOGIA_DESARROLLO_IA.md`), estado del proyecto (vive en
  `MEMORY.md`), specs y su estado (viven en `ROADMAP.md`), gobernanza
  de hallazgos (vive en este doc §1), hallazgos canónicos (viven en
  `AUDITORIA_2026.md`).
- **Cuándo se actualiza:** cuando cambia el stack, un comando, una
  convención de código, una trampa del dominio o un límite duro del
  repo.
- **Quién modifica:** el humano o el agente.
- **Quién lee:** el agente, al inicio de cada sesión.

**Distinción clave:**

- **Reglas del repo** (stack, comandos, convenciones de código, trampas
  del dominio, límites duros) → viven en `AGENTS.md`.
- **Reglas del proceso** (ciclo, roles, AI Studio, rigor de fuentes,
  forma de trabajar, límites del agente) → viven en
  `METODOLOGIA_DESARROLLO_IA.md`.

---

### `docs/METODOLOGIA_DESARROLLO_IA.md`

- **Rol único:** el **único archivo** que define cómo se trabaja.
- **Debe tener:** principio fundamental, ciclo de 6 fases, rol de cada
  actor (humano / IA auditora / AI Studio / BD), reglas con AI Studio,
  reglas de auditoría de artefactos, rigor de fuentes, forma de trabajar
  (reglas del repo aplicadas a código y documentación), límites de la
  IA auditora, estándares de referencia del proceso.
- **NO debe tener:** dónde vive cada tipo de información (vive en este
  doc, §1), estado del proyecto, specs y su estado, hallazgos.
- **Cuándo se actualiza:** cuando cambia el proceso (no cuando cambia el
  código). Rara vez.
- **Quién modifica:** el humano o el agente.
- **Quién lee:** humano y agente, al inicio.

---

### `docs/METODOLOGIA_AUDITORIA_IA.md`

- **Rol único:** marco de auditoría. Cómo se audita un sistema.
- **Debe tener:** 6 pasadas metodológicas, 10 capas de auditoría,
  contexto operativo ISP (líneas rojas, excepciones justificadas,
  gobernanza de riesgo), rúbrica de conformidad, protocolo de
  evidencia, estrategia de muestreo, taxonomía de severidad, protocolo
  de la pasada dinámica, auditoría asistida por IA, limitaciones y
  sesgos, referencias normativas.
- **NO debe tener:** estado del proyecto, specs, hallazgos concretos de
  este sistema (los hallazgos viven en `AUDITORIA_2026.md`), cómo está
  construido el sistema, dónde vive cada información.
- **Cuándo se actualiza:** casi nunca. Solo si cambia el marco
  metodológico de auditoría (nuevas pasadas, nuevas capas, nuevos
  estándares normativos). No se actualiza porque cambió el sistema
  auditado.
- **Quién modifica:** el humano con aprobación explícita.
- **Quién lee:** el agente cuando ejecuta una auditoría.

---

### `docs/GOBERNANZA.md` (este doc)

- **Rol único:** mapa del ecosistema documental.
- **Debe tener:** leyes duras (qué archivo tiene qué información), ficha
  por archivo, reglas de modificación, errores conocidos a evitar.
- **NO debe tener:** ciclo de trabajo (vive en `METODOLOGIA_DESARROLLO_IA.md`),
  estado del proyecto, specs, hallazgos, reglas de código (viven en
  `AGENTS.md`).
- **Cuándo se actualiza:** cuando cambia la estructura documental (se
  añade un doc, se elimina, cambia su rol).
- **Quién modifica:** el humano.
- **Quién lee:** humano y agente.

---

### `MEMORY.md`

- **Rol único:** estado operativo entre sesiones.
- **Debe tener:** estado actual del proyecto, **fase activa**, próximo
  paso, hallazgos operativos vivos (con prefijos `RBAC-NN`, `CODE-NN`,
  `DB-NN`, `PLAN-NN`, `DOC-NN`, `MAP-NN`, `EST-NN`, `BUG-NN`,
  `139b-NN`, `139c-NN`, `Overlay-F5`, `136f-bis`, `ABS-NN`),
  decisiones de proceso vigentes, aprendizajes recientes, referencias
  rápidas a docs clave, infraestructura local.
- **NO debe tener:** specs y su estado (viven en `ROADMAP.md`), hallazgos
  canónicos (viven en `AUDITORIA_2026.md`), tablas por fase (viven en
  `ROADMAP.md`), estándares (viven en `REFERENCIA.md`), cómo funciona el
  sistema (vive en `ARQUITECTURA.md`), ciclo de trabajo (vive en
  `METODOLOGIA_DESARROLLO_IA.md`).
- **Cuándo se actualiza:** al cerrar cada spec (añadir el cierre, borrar
  lo que ya no aplica, actualizar **fase activa**). **No se actualiza en
  cada cambio pequeño.**
- **Quién modifica:** el humano o el agente.
- **Quién lee:** humano y agente, al inicio de cada sesión.

---

### `docs/ROADMAP.md`

- **Rol único:** única fuente de specs y su estado.
- **Debe tener:** estados válidos, Estado actual (fase activa +
  siguiente + conteos), **7 fases secuenciales**, cada fase con su
  tabla de specs (una fila por spec: número + nombre + qué hace +
  estado), Histórico cerradas, Histórico absorbidas — Specs, Histórico
  absorbidas — Bloques, Cómo se actualiza este archivo, Referencias.
  Nota de huecos de numeración (104, 107, 111).
- **NO debe tener:** hallazgos canónicos (solo referencias por ID), el
  cuerpo del análisis de cada hallazgo (vive en `AUDITORIA_2026.md`),
  estado operativo (vive en `MEMORY.md`), estándares (viven en
  `REFERENCIA.md`), decisiones de industria descartadas (viven en
  `REFERENCIA.md`). Categorías temporales ("post-defensa", "pre-defensa",
  "obligatoria"). Una spec repetida en dos fases.
- **Cuándo se actualiza:** al crear una spec (`pendiente` → `en
  progreso`), al cerrar una spec (`en progreso` → `✅` + mover a
  histórico cerradas), al absorber una spec (mover a histórico
  absorbidas). **En esos eventos, no en otros.**
- **Quién modifica:** el humano o el agente, al cerrar spec.
- **Quién lee:** humano y agente.

---

### `docs/AUDITORIA_2026.md`

- **Rol único:** foto con fecha de corte de los hallazgos de auditoría.
- **Debe tener:** fecha de corte explícita, resumen por capa con scores,
  capas 1–10 con sus hallazgos, matriz consolidada de hallazgos, cierre
  del documento.
- **NO debe tener:** estado de cada hallazgo (abierto/cerrado). Eso vive
  en `ROADMAP.md`. Referencias a fases o grupos posteriores al corte.
  Texto "actualizado al día X".
- **Cuándo se actualiza:** solo con auditoría o reauditoría general (6
  pasadas × 10 capas). Requiere spec aprobada. Es un evento raro y grande.
- **Quién modifica:** el humano con aprobación explícita.
- **Quién lee:** consulta.

---

### `docs/ARQUITECTURA.md`

- **Rol único:** cómo está construido el sistema (real, no aspiracional).
- **Debe tener:** visión general, stack, kernel, tabla de endpoints con
  sus gates, flujo de login, modelo de autenticación, modelo de
  autorización, modelo de sesiones, modelo de auditoría, integraciones.
- **NO debe tener:** conteos que envejecen (número de líneas, número de
  tablas, número de gates, número de archivos afectados), referencias a
  fases o grupos con fecha, deuda técnica (vive en `AUDITORIA_2026.md`),
  "cierres documentados" (viven en `ROADMAP.md`), decisiones de industria
  (viven en `REFERENCIA.md`), estado del proyecto (vive en `MEMORY.md`),
  cabeceras tipo "Actualización (fecha): sincronizado tras...".
- **Cuándo se actualiza:** al cerrar una fase grande que cambie la
  estructura (nuevo endpoint, cambio de middleware, cambio de flujo).
  No en cada cambio.
- **Quién modifica:** el humano o el agente.
- **Quién lee:** consulta.

---

### `docs/BD.md`

- **Rol único:** esquema de base de datos en prosa.
- **Debe tener:** motor/charset, listado de tablas por dominio, tablas
  clave con columnas y restricciones, relaciones principales,
  convenciones de nombres, triggers, decisiones de diseño documentadas.
- **NO debe tener:** cabeceras "Actualización (fecha)", "Estado
  post-spec X", "resueltos en 001a", "Hallazgos de BD abiertos" (viven en
  `AUDITORIA_2026.md`), notas de fases con fecha, "uso actual de X".
- **Cuándo se actualiza:** cuando cambia el esquema (`bd_redytelca.sql`
  cambió) y el cambio es significativo. No en cada ALTER.
- **Quién modifica:** el humano o el agente.
- **Quién lee:** consulta.

---

### `docs/REFERENCIA.md`

- **Rol único:** material atemporal que no vive en ningún otro lugar.
- **Debe tener:** estándares aplicados (software, ISP, UX/UI, industria),
  frases de defensa para la tesis, decisiones de industria consideradas y
  no aplicadas, aprendizajes técnicos/de proceso/de ciclo, referencias de
  mercado.
- **NO debe tener:** estado del proyecto, specs, hallazgos, fechas de
  "actualización", conteos que envejecen.
- **Cuándo se actualiza:** cuando se añade un estándar, una frase de
  defensa, una industria descartada, un aprendizaje, o una referencia de
  mercado. **Es un archivo aditivo:** crece, no se reescribe.
- **Quién modifica:** el humano o el agente.
- **Quién lee:** humano y agente al escribir tesis.

---

### `docs/VERIFICACION.md`

- **Rol único:** lecciones de verificación y checklists.
- **Debe tener:** lecciones aprendidas con origen/regla derivada, checklist
  de verificación de fases, cómo se actualiza este doc, referencias.
- **NO debe tener:** estado del proyecto, specs, hallazgos (los cita por
  ID pero no los contiene), ciclo de trabajo (vive en
  `METODOLOGIA_DESARROLLO_IA.md`).
- **Cuándo se actualiza:** cuando se detecta un fallo evitable en el
  proceso de verificación.
- **Quién modifica:** el humano o el agente.
- **Quién lee:** humano y agente durante verificaciones.

---

### `docs/ADR/*.md`

- **Rol único:** una decisión arquitectónica histórica por archivo.
- **Debe tener:** estado (aceptado / propuesto / reemplazado), fecha,
  contexto, decisión, alternativas descartadas, consecuencias, referencias.
- **NO debe tener:** estado del proyecto, specs, hallazgos, versiones
  actuales del sistema (una decisión es histórica).
- **Cuándo se actualiza:** al tomar una decisión arquitectónica. Se
  cambia el estado a `reemplazado` si una decisión posterior la anula, y
  se apunta al nuevo ADR.
- **Quién modifica:** el humano o el agente.
- **Quién lee:** consulta.

---

### `bd_redytelca.sql`

- **Rol único:** fuente de verdad del esquema.
- **Debe tener:** DDL completo + triggers + seed.
- **NO debe tener:** nada más.
- **Cuándo se actualiza:** solo con spec aprobada, migración de datos
  documentada y rollback probado (constitución §3).
- **Quién modifica:** el humano.
- **Quién lee:** todos. Siempre.

---

### `docs/README.md`

- **Rol único:** índice de la carpeta `docs/`.
- **Debe tener:** una fila por archivo de `docs/`, con su rol en una
  línea.
- **NO debe tener:** descripciones largas, estado, hallazgos.
- **Cuándo se actualiza:** cuando se añade o elimina un archivo de `docs/`.
- **Quién modifica:** el humano o el agente.
- **Quién lee:** alguien que llega nuevo a `docs/`.

---

### `PROMPT DE INICIO DE SESIÓN` (externo, fuera del repo)

- **Rol único:** fuerza lectura y análisis de coherencia al inicio de
  cada chat.
- **Debe tener:** lista de archivos a leer, análisis obligatorio,
  rol del agente, reglas de revisión de artefactos, rigor de fuentes,
  próximo paso.
- **NO debe tener:** estado completo del proyecto (vive en `MEMORY.md`),
  specs (viven en `ROADMAP.md`), ciclo de trabajo (vive en
  `METODOLOGIA_DESARROLLO_IA.md`).
- **Cuándo se actualiza:** si cambia la lista de archivos a leer.
- **Quién modifica:** el humano.
- **Quién lee:** el agente, primero.

---

### `PLAN DE LA FASE ACTIVA` (externo, fuera del repo)

- **Rol único:** el "cómo" de la fase/spec en curso.
- **Debe tener:** fundamento, modelo, archivos afectados, orden de
  ejecución, verificación end-to-end, rollback, riesgos, nota para la
  defensa.
- **NO debe tener:** estado de otras fases (vive en `ROADMAP.md`),
  estado operativo general (vive en `MEMORY.md`).
- **Cuándo se actualiza:** durante la fase.
- **Quién modifica:** el humano o el agente.
- **Quién lee:** el agente durante la fase.

**Nota:** el archivo externo puede seguir llamándose `PLAN DEL BLOQUE
ACTIVO`. Su **rol** es el de la fase/spec activa, independientemente del
nombre del archivo.

---

## 3. Reglas de modificación

### 3.1 Antes de añadir cualquier línea a cualquier archivo

Preguntarse:

1. ¿Este tipo de información pertenece a este archivo según la tabla de
   §1?
2. Si NO, ¿dónde vive?
3. Si vive en otro archivo, ¿ya está ahí? Si sí, no añadir aquí.
4. Si vive en otro archivo y NO está, ¿por qué? ¿Falta añadirlo? ¿O ya
   no aplica?

Si la respuesta a (1) es NO, no se toca el archivo.

### 3.2 Antes de modificar cualquier línea

Preguntarse:

1. ¿La línea que voy a cambiar sigue siendo cierta?
2. ¿La línea que voy a cambiar pertenece a este archivo?
3. ¿La línea que voy a cambiar está en el formato correcto?

**Cambios quirúrgicos:** si el cambio es un typo, corregir el typo. No
reescribir la frase. No añadir contexto. No mejorar el estilo. Solo el
typo.

### 3.3 Cuando se detecta algo que está mal

Según `AGENTS.md`:

- Si está mal y es parte del contrato del cambio → corregir.
- Si está mal y NO es parte del contrato → **listarlo por separado y
  esperar confirmación.** No cambiar unilateralmente.

### 3.4 Cómo se cierra una spec

1. Se marca en el plan.
2. Se actualiza `MEMORY.md` (fase activa, último cierre).
3. Se actualiza `docs/ROADMAP.md` (spec cerrada: cambiar `🔄` a `✅` y
   mover la fila a Histórico cerradas).

**No se actualiza nada más.** `ARQUITECTURA.md`, `BD.md`,
`AUDITORIA_2026.md` se actualizan **al cerrar una fase grande** que
cambie la estructura, no en cada cierre de spec.

---

## 4. Errores conocidos a evitar

Estos errores ya ocurrieron. Este documento existe para prevenirlos.

1. **El agente escribió código en lugar de dar un prompt.**
   - Regla: dar prompts, no escribir código. Documentación es la única
     excepción.

2. **Se cerró una línea roja de seguridad solo en UI, no en backend.**
   - Regla: SoD es efectivo solo si está en backend. UI es UX, no
     seguridad.

3. **El agente se confundió por contradicciones entre documentos.**
   - Regla: cada hecho vive en un solo lugar. Sin duplicación.

4. **Se referenció `docs/PLAN-*.md` cuando los planes viven fuera del
   repo.**
   - Regla: no referenciar rutas que no existen en el dump.

5. **El agente aprobó un artefacto sin auditar flujos completos.**
   - Regla: auditar flujos completos, no solo el cambio pedido.

6. **El agente asumió números sin verificar contra la BD.**
   - Regla: rigor de fuentes.

7. **Se mezclaron hallazgos operativos con el informe canónico.**
   - Regla: gobernanza de hallazgos. Canónico solo con auditoría formal.

8. **Se mantuvieron números que envejecen** (conteo de líneas, conteo de
   tablas, conteo de tests) en docs que no son fuente de esos hechos.
   - Regla: un número que el dump ya contiene no se replica.

9. **Se mantuvieron cabeceras "Actualización (fecha): sincronizado
   tras..."** que envejecen solas.
   - Regla: los docs no llevan fecha de actualización. Si algo cambia,
     se cambia la línea; no se anota que se cambió.

10. **Se duplicó la misma información en varios docs.**
    - Regla: cada hecho vive en un solo lugar. Si está en dos, uno se
      elimina.

11. **Se dejaron secciones "Estado post-X"** dentro de docs que no son
    `MEMORY.md`.
    - Regla: el estado vive en `MEMORY.md`. Los demás docs son atemporales
      o describen su objeto sin estado.

12. **Se actualizaron docs "porque toca"** sin que hubiera un cambio
    real que lo justifique.
    - Regla: solo se modifica cuando se cumple la condición de
      "cuándo se actualiza" de su ficha.

13. **Se usaron "bloques" y "fases" indistintamente** para referirse a la
    estructura del ROADMAP. El ROADMAP ahora organiza en **fases
    secuenciales (1–7)**. "Bloque" es terminología obsoleta. Los bloques
    30 y 31 están absorbidos y documentados en el histórico.
    - Regla: el ROADMAP se lee por fases. Si un doc menciona "bloque X",
      es obsoleto y se corrige.

14. **Se buscaron specs en huecos de numeración (104, 107, 111).**
    Esos números nunca existieron. Un AI puede "inventar" trabajo al
    buscar un hueco que no está.
    - Regla: los huecos de numeración están documentados en el ROADMAP.
      No se buscan.

---

## 5. Cómo se usa este documento

- **Al inicio de sesión:** el agente lo lee si hay dudas sobre dónde
  vive algo.
- **Al escribir o modificar documentación:** el agente lo consulta para
  saber dónde va cada cosa.
- **Al cerrar una spec:** el agente verifica con §3.4.
- **Si algo se vuelve regla permanente:** se añade aquí o al doc
  correspondiente, según su tipo.

**Este documento es la referencia de "dónde va qué".** Si algo no está
claro, se pregunta. No se inventa.