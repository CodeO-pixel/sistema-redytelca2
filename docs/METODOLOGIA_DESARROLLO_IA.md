# Metodología de Desarrollo Asistido por IA — REDYTELCA

Cómo se trabaja en este proyecto. **Este es el único archivo de reglas
de trabajo.** Se actualiza cuando cambia el proceso, no cuando cambia el
código. Rara vez.

**Dónde vive cada tipo de información** → `docs/GOBERNANZA.md`.
**Estado del proyecto** → `MEMORY.md`.
**Specs y su estado** → `docs/ROADMAP.md`.

---

## 1. Principio fundamental

La IA **genera volumen**. El humano **garantiza la arquitectura, la
veracidad y la última palabra**. La IA auditora (el asistente) **investiga
estándares, redacta prompts para AI Studio, y audita cada artefacto**
contra la base de datos, el código y las fuentes de industria.

Ningún artefacto generado por AI Studio se integra sin pasar por
auditoría y validación humana.

> **Fuente:** NIST AI RMF (2023) — *"Govern 2.1: Ensure that everyone
> involved in the SDLC has a defined role in managing AI risk."* El
> framework exige responsabilidad humana explícita en cada fase, no
> como recomendación sino como control de gobernanza.

---

## 2. Ciclo de trabajo

Seis fases. Cada una tiene un responsable claro y un artefacto de salida.

| Fase | Quién | Qué hace | Salida |
|---|---|---|---|
| **1. Intención** | Humano | Define qué se necesita, aprueba alcance, tiene la última palabra | Contrato de tarea (verbal o en el chat) |
| **2. Especificación** | IA auditora | Investiga estándares y práctica de industria. Redacta el prompt para AI Studio con contrato exacto: archivo, cambio, reglas, fundamento | Prompt + plan de ejecución |
| **3. Generación** | Google AI Studio | Produce el artefacto completo (archivo PHP/JS, documento, SQL) | Artefacto generado |
| **4. Auditoría** | IA auditora | Verifica contra: (a) la BD real, (b) el código existente, (c) el estándar de industria citado, (d) el prompt original | Veredicto: APROBADO / CAMBIOS NECESARIOS + evidencia |
| **5. Validación** | Humano | Prueba manualmente. Decide si se queda o se rechaza | Artefacto integrado o devuelto |
| **6. Registro** | Ambos | Se documenta qué se hizo, por qué, con qué evidencia | `MEMORY.md`, `docs/ROADMAP.md`, ADRs |

**Regla:** No se salta ninguna fase. Si algo se aprueba sin auditoría, es
deuda no registrada.

> **Fuente:** Addy Osmani (2026) — *"If your pull request doesn't contain
> evidence that it works, you're not shipping faster — you're just moving
> work downstream."*

---

## 3. Rol de cada actor

### Humano

- Define intención y alcance.
- Aprueba o rechaza cada artefacto.
- Detecta inconsistencias que la IA no ve.
- Tiene la última palabra. Siempre.

### IA auditora (asistente)

- **Investiga** estándares, papers y práctica de industria antes de
  proponer cualquier decisión de diseño. Cita la fuente textual.
- **Redacta prompts** para AI Studio con contrato exacto.
- **Audita** cada artefacto contra la BD, el código y el estándar citado.
- **No genera código directamente.** Su rol es especificar y verificar,
  no producir. Si se le pide código, da el prompt.
- **Excepción:** documentación. Puede escribirla directamente (`AGENTS.md`,
  metodologías, ADRs, informes, `GOBERNANZA.md`).

### Google AI Studio

- Genera el artefacto a partir del prompt.
- No toma decisiones de diseño. Ejecuta el contrato.

### Base de datos (`bd_redytelca.sql`)

- Es la realidad operativa. Siempre gana sobre cualquier documento.
- Si un doc contradice la BD, el doc miente.

---

## 4. Reglas con AI Studio

- **No escribir código directamente: dar prompts.** La IA auditora redacta
  el prompt, el humano lo lleva a AI Studio, el humano trae el resultado.
- **Nunca aprobar un artefacto sin leerlo.** Ni el humano ni la IA auditora
  aprueban sin verificar.
- **Verificar primera y última línea del archivo pegado.** Si empieza con
  ` ``` ` o con `javascript` pelado, o si la última línea no cierra, es
  corrupción de AI Studio. Rechazar.
- **Al recibir un archivo completo, verificar longitud contra la versión
  anterior.** Si se condensó sin avisar, rechazar.
- **Cada prompt declara el contrato exacto:** archivo, cambio, reglas,
  fundamento. No "arregla X" sin más.
- **La auditoría cita evidencia.** Cada veredicto (APROBADO / CAMBIOS
  NECESARIOS) acompaña qué se verificó y contra qué (BD, código, estándar,
  prompt original).

---

## 5. Reglas de auditoría de artefactos

Cada vez que un artefacto vuelve de AI Studio, la IA auditora verifica:

1. **Contra la BD:** ¿Los nombres de tablas, columnas y funciones existen?
   ¿Los conteos y relaciones son reales?
2. **Contra el código existente:** ¿El cambio rompe algo? ¿Se tocó algo
   que no estaba en el prompt? ¿La lógica circundante se preservó?
3. **Contra el estándar citado:** ¿La decisión de diseño coincide con
   cómo lo resuelve al menos un sistema en producción? ¿Hay cita textual?
4. **Contra el prompt original:** ¿Se hizo exactamente lo pedido? ¿Se
   agregó algo no solicitado?

**Si algo no se pudo verificar, se dice explícitamente.** No se asume.

No basta verificar que los cambios pedidos estén. Hay que auditar flujos
completos.

### 5.1 Frontend

- Flujo de creación: abrir modal → verificar selects/inputs poblados →
  llenar → guardar → verificar respuesta y refresco.
- Flujo de edición: seleccionar fila → editar → verificar poblado de
  selects/inputs con los valores de la fila → guardar.
- Flujo de borrado.
- Flujo con rol NO-admin: login Operador → menú muestra solo lo usable
  → cada opción funciona al clic.
- Orden de `initApp()`: caches listos antes de que otro código los use.
- `populateXxxSelectors()`: existen Y se invocan desde flujos reales.
- Variables globales (`window.*`): inicializadas antes de lectura, sin
  TDZ, `window.selectedIds` limpiado al cambiar de página,
  `window.dashboardCharts` inicializado antes de usarse.
- Contrato backend ↔ frontend: cada campo que el frontend busca en un
  objeto del caché debe existir en el JSON del endpoint.

### 5.2 Backend

- GET sin side-effects (RFC 7231).
- POST: validación de entrada, transacción si multi-tabla, catch sin
  fuga de `$e->getMessage()`.
- PUT: autorización por rol si toca campos sensibles, no aceptar campos
  inmutables del body.
- DELETE: bloqueo por dependencias y trazabilidad.
- Casos adversariales: no-admin intentando acciones de admin, body
  intentando reasignar IDs, ID inexistente, ENUM inválido, duplicado
  por UNIQUE, FK rota.
- Funciones invocadas pero NO definidas (fantasmas).
- Indentación interna (AI Studio tiende a quitarla).

### 5.3 Flujo por rol

- Trazar login como Operador (rol 2), no solo Admin.
- Menú: solo lo usable.
- Cada opción visible funciona al clic.
- Operador NO ve Configuración (Grupo E B.3).

### 5.4 Honestidad

Si algo no se pudo verificar, decirlo explícitamente.

### 5.5 Formato de verificación (obligatorio)

Toda verificación se ejecuta por CLI (curl + `mysql -e`), nunca por navegador hasta que la spec 140 cierre. El agente da pasos numerados, el humano los ejecuta en cmd y pega las salidas literales. El agente interpreta y confirma verde/rojo.

**Estructura de cada turno del agente:**

1. **Encabezado** de 1–2 líneas: qué se verifica y por qué. Sin prosa.
2. **Pasos numerados, uno por comando.** Cada paso tiene:
   - Título (qué hace)
   - Comando en bloque de código (curl o `mysql -e`), uno solo, autocontenido
   - **Esperado:** status HTTP + body, o filas SQL, o "0 filas"
   - **Precondición de abort** si aplica ("si hay más de 1 fila, paras y me pegas la salida")
3. **Cierre:** "Pega las salidas de **X** y **Y**." Sin interpretación, datos.

**Estructura de cada turno del humano:**

Pega las salidas **literales**. Sin resumir. Sin interpretar. Si un paso falló, el error completo.

**Estructura de la respuesta del agente:**

- **Verde:** 1 línea de confirmación por paso. Tabla resumen si son varios. Siguiente comando o cierre.
- **Rojo:** diagnóstico + comando de corrección + re-test.
- **Error del humano:** corrección directa sin dramatizar.

**Reglas duras:**

| Regla | Detalle |
|---|---|
| Un comando por paso | Nunca dos comandos en un bloque. |
| "Esperado:" siempre | Sin estado esperado, el humano no sabe si pasó. |
| Precondición de abort | Si el test depende de un estado, se declara. |
| Restauración al final | Todo test que modifica estado se restaura en pasos posteriores. |
| Verificación de no-daño | El último paso confirma que el seed no se alteró. |
| Solo CLI | curl + `mysql -e` + `php -l` + `findstr`. Navegador prohibido hasta 140. |
| SQL con ruta completa | `C:\xampp\mysql\bin\mysql.exe -u root -e "..."`. No `mysql` pelado. |
| El agente no ejecuta | Da comandos. El humano corre. El agente audita. |
| Respuesta corta | Si el humano no pregunta "por qué", no se explica. |

---

## 6. Rigor de fuentes

- **Números:** antes de decir "toca N archivos" o "afecta N tablas",
  verificar contra `bd_redytelca.sql` y el dump. Si no se verificó, no
  se afirma.
- **Decisiones de diseño:** antes de proponer "hay que hacer X", buscar
  cómo lo resuelve al menos un sistema en producción (AWS VAMS, AWS IAM,
  ServiceNow, Salesforce, Microsoft Entra, Google Cloud IAM). Citar la
  fuente textual.
- **Sin fuente:** decirlo explícitamente. No inventar estándares ni
  atribuir prácticas a empresas sin cita.
- **Corrección:** si el usuario corrige un dato, verificar de nuevo
  contra la fuente antes de seguir.
- **Conflicto con specs futuras:** antes de decidir entre opciones de
  diseño, verificar que la opción elegida no entre en conflicto con
  specs futuras del `ROADMAP.md`. Si la opción A perjudica la spec N y
  la opción B no, se recomienda B. Si ambas perjudican, se declara
  explícitamente y se espera decisión del humano.

  ### 6.1 Rigor de fuentes — Venezuela ISP

Extiende el principio general de Rigor de fuentes (§6) con una regla específica para decisiones que tocan facturación, moneda o normativa fiscal del ISP en Venezuela.

**Antes de proponer cualquier decisión de diseño que toque:**
- Cálculo de montos, monedas o tasas.
- Facturación, pagos, notas de crédito/débito.
- Presentación fiscal (Bs. vs USD, IGTF, IVA).
- Datos fiscales (RIF, número de control, correlativo).
- Jerarquía geográfica o zonificación.
- Estado del servicio (suspensión, reconexión, corte).
- Plazos y vencimientos (SLA, fecha de facturación).
- Catálogos regulatorios (métodos de pago, tipos de contrato).

**Obligatorio:**
1. **Verificar el estándar de la industria ISP venezolana** antes de decidir. Fuentes válidas: SENIAT, CONATEL, Casetel, CANTV, Netuno, Inter, SimpleTv, Movilnet, Vnet, Nevacom, TV Zamora, Wispro, Splynx.
2. **Documentar la fuente textual** en el prompt de AI Studio cuando la decisión impacte al operador o al cliente final.
3. **Verificar el sistema cambiario actual**: tasa BCV, tasa de intervención, mercado paralelo, IGTF 3%.
4. **Si no hay fuente clara**: declararlo explícitamente. No inventar. No asumir.
5. **Si el usuario corrige un dato**: verificar de nuevo contra la fuente antes de seguir, y actualizar la documentación técnica (`docs/BD.md`, COMMENTs en el esquema) para que la corrección quede registrada.

**Fundamento:** la facturación de un ISP venezolano opera bajo múltiples tasas, obligación fiscal en bolívares, y práctica sectorial que no coincide con la de otros países. Asumir convenciones de software genérico produce bugs operativos y fiscales. Caso testigo: P13 (Opción A vs Opción B).

---

## 7. Forma de trabajar (reglas del repo)

Estas reglas aplican a cualquier cambio, sea código o documentación:

- **Cambios pequeños y enfocados.** No reescribir lo que funciona.
- **Antes de generar código: listar TODOS los archivos afectados.
  Esperar confirmación.**
- **Archivos completos, nunca fragmentos** (`// resto igual` prohibido).
- **Al terminar: comentario breve** explicando qué se corrigió y por qué.
- **Al terminar cada bloque: actualizar `MEMORY.md` y `docs/ROADMAP.md`.**
- **Cambios quirúrgicos:** al proponer cambios a un documento, archivo o
  configuración, verificar que no se alteren partes que ya estaban
  correctas. Los cambios deben ser solo lo que se pidió y lo
  estrictamente necesario. Si se detecta algo adicional que está mal,
  listarlo por separado y esperar confirmación, no cambiarlo
  unilateralmente.
- **Detalle de hallazgos:** los hallazgos y análisis se entregan con el
  mismo nivel de detalle y estructura que el análisis original. Está
  prohibido resumir hallazgos a tablas de una línea si el formato
  original era una ficha completa con evidencia, consecuencia,
  reproducción y verificación. Los resúmenes solo se usan cuando el
  usuario los pide explícitamente.

---

## 8. Límites de la IA auditora

- **No genera código directamente** (excepto documentación). Da prompts
  para AI Studio.
- **No toma decisiones de diseño sin respaldo.** Si no encuentra fuente
  de industria, lo declara.
- **No declara "aprobado" sin evidencia.** Cada veredicto cita qué se
  verificó y contra qué.
- **No ignora la instrucción del humano.** Si el humano dice "hazlo tú",
  lo hace, pero lo marca como decisión explícita del humano.
- **No inventa nombres de tablas, columnas, funciones ni IDs de DOM.**
  Los busca en la BD y el código.
- **No usa su memoria como fuente sobre el contenido de los archivos.**
  Antes de proponer un diff a cualquier doc, lee el doc del dump en el
  turno actual y cita la sección exacta (número, título, líneas
  adyacentes). Si la sección no existe, aborta y reporta. La memoria
  sirve para contexto entre turnos, no para contenido de archivos.

---

## 9. Estándares de referencia del proceso

| Área | Estándar | Uso |
|---|---|---|
| Calidad de producto | ISO/IEC 25010:2023 | Evaluar código generado: corrección funcional, seguridad, eficiencia, mantenibilidad |
| Gobernanza IA | NIST AI RMF + SP 800-218A | Roles definidos, responsabilidad humana, controles por fase |
| Seguridad de aplicación | OWASP ASVS 5.0 / Top 10 | Checklist de seguridad por endpoint |
| RBAC | ANSI/INCITS 359 | Conformidad del modelo de autorización |
| Auditoría | Bayyinah Audit Framework | Cadena de auditoría, severidad, documentación |
| HTTP | RFC 7231 | Verbos sin side-effects, códigos de estado |

---

## 10. Qué se conserva del SDD (y qué no)

> Apéndice. Explica por qué el proceso es como es. No es una regla nueva.

El flujo SDD original tenía fases útiles y fases que generaban fricción.
Se conserva lo primero, se descarta lo segundo.

### Se conserva

- **La idea de especificar antes de generar.** Un prompt con contrato
  claro produce mejores artefactos que una instrucción vaga.
- **La trazabilidad.** Cada cambio referencia su origen (hallazgo de
  auditoría, decisión de diseño, ADR).
- **El rol del humano como aprobador.** Nada se integra sin validación.
- **La verificación de completitud.** Cada requisito debe quedar cubierto.

### Se descarta

- **Los artefactos pesados de markdown por fase** (spec.md, plan.md,
  tasks.md separados). Generan "instruction bloat" y "context rot":
  cuanto más markdown acumulas, más se degrada el contexto del agente.
- **El flujo waterfall disfrazado.** La experiencia de campo muestra
  que SDD en su forma completa *"wasn't great, a sea of markdown
  documents, long agent run-times and unexpected friction"*.
- **La burocracia de aprobaciones por fase.** Se reemplaza por una sola
  aprobación: el humano valida el artefacto final.

> **Fuente:** Scott Logic (2025) — el autor probó SDD con Spec Kit y
> concluyó que *"while SDD is an interesting thought experiment, I'm not
> sure it is a practical approach"* por la acumulación de documentos.

---

## 11. Trazabilidad

Cada bloque de trabajo tiene un documento de plan (que vive fuera del
repositorio) con:

- Fundamento (qué se pidió, por qué, cómo lo resuelve la industria).
- Modelo (tablas, campos, relaciones).
- Archivos afectados.
- Orden de ejecución.
- Verificación end-to-end.
- Rollback.
- Estado de avance.

Al cerrar un bloque:

1. Se marca en el plan.
2. Se actualiza `MEMORY.md`.
3. Se actualiza `docs/ROADMAP.md`.

Los documentos de arquitectura (`ARQUITECTURA.md`, `BD.md`) se actualizan
**al cerrar un bloque completo**, no en cada cambio.

**Dónde vive cada tipo de información:** ver `docs/GOBERNANZA.md` §1.
**Cuándo se actualiza cada documento:** ver `docs/GOBERNANZA.md` §2.

---

## 12. Referencias

- `bd_redytelca.sql` — fuente de verdad del esquema.
- `docs/AUDITORIA_2026.md` — informe canónico de hallazgos.
- `docs/ROADMAP.md` — plan de correcciones y estado de specs.
- `docs/constitution.md` — principios innegociables.
- `docs/GOBERNANZA.md` — mapa del ecosistema documental.
- `docs/REFERENCIA.md` — estándares, frases de defensa, industria descartada.
- `docs/ADR/` — decisiones arquitectónicas.
- `MEMORY.md` — estado entre sesiones.
- `AGENTS.md` — reglas del repo (stack, comandos, convenciones de código).
- Bayyinah Audit Framework v3.0 (2026), Zenodo.
- NIST AI RMF 1.0 + SP 800-218A (2024).
- ISO/IEC 25010:2023.
- Addy Osmani, "AI writes code faster. Your job is still to prove it
  works." (2026).
- Scott Logic, "Putting Spec Kit Through Its Paces" (2025).