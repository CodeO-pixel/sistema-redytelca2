# Constitución — REDYTELCA

Principios innegociables. Toda spec, plan y tarea debe cumplirlos.
Si algo los contradice, se para y se pregunta.

1. **Alcance de tesis**: stack sin frameworks, sin build, sin npm. Debe correr en
   XAMPP/cPanel sin instalación adicional. No se agregan dependencias salvo
   aprobación explícita y ADR.

2. **El bloque activo manda**: nada se implementa sin que exista un plan de
   bloque activo aprobado por el humano. El plan vive fuera del repositorio,
   junto a los documentos de continuidad (`PROMPT DE INICIO`, `PLAN DEL BLOQUE
   ACTIVO`). Si falta una decisión, se para y se pregunta. Los hallazgos
   de auditoría se convierten en entradas del roadmap y, si aplica, en un plan
   de bloque antes de tocarse. Ver `docs/GOBERNANZA.md` §3 y §6.3.

3. **`bd_redytelca.sql` es la fuente de verdad del esquema**: no se toca el DDL sin
   spec aprobada, migración de datos documentada y rollback probado.

4. **Nunca inventar nombres**: tablas, columnas, funciones, IDs de DOM y variables
   de sesión se buscan en el workspace antes de escribirse. Ante duda, se pregunta.

5. **Seguridad por defecto**: contraseñas siempre con `password_hash()` /
   `password_verify()`; autenticación siempre por token en `sesiones` (nunca
   `$_SESSION` nativo); PII nunca en respuestas de error ni en logs sin redactar.

6. **Tests como puerta**: la lógica PHP se prueba con `php tests/run.php` (harness
   casero, sin dependencias). Prohibido marcar una tarea como hecha con tests en rojo.

7. **Idioma**: código y nombres de archivo en inglés/snake_case; interfaz,
   comentarios y documentación en español.

8. **Trazabilidad**: cada corrección cierra hallazgos concretos de
   `docs/AUDITORIA_2026.md` (si es canónico) o resuelve una decisión de diseño
   registrada en un ADR. Los hallazgos operativos (fuera de auditoría general)
   viven en `MEMORY.md`, no en el canónico. Cada cierre actualiza `MEMORY.md` y
   `docs/ROADMAP.md`.

9. **Rigor de fuentes**: antes de afirmar un número (archivos afectados, tablas,
   líneas, conteos), se verifica contra `bd_redytelca.sql` y el dump del proyecto.
   Antes de proponer una decisión de diseño, se cita al menos un sistema en
   producción (AWS, ServiceNow, Microsoft, Salesforce, Google) que resuelva el
   mismo problema. Si no hay fuente, se declara explícitamente: "propuesta sin
   respaldo de industria verificado". No se inventan estándares ni se atribuyen
   prácticas a empresas sin cita comprobable.

10. **Recomendación = estándar, no comodidad**: toda recomendación del agente
    debe seguir el estándar de industria citado, incluso si implica más trabajo,
    más cambios, más riesgo o más esfuerzo de verificación. Está terminantemente
    prohibido recomendar la opción "más cómoda", "más simple", "menos disruptiva"
    o "más rápida" si no cumple el estándar. Si el estándar exige X, se recomienda
    X. La comodidad del agente, del proceso o del humano no es un criterio válido
    para desviarse del estándar. Si el estándar tiene trade-offs documentados y
    no hay una única respuesta correcta, se presentan las opciones **con su
    fundamento de industria** y se declara explícitamente cuál es la más alineada
    al estándar. Nunca se disfraza la comodidad de recomendación técnica.

11. **Los documentos se leen del dump antes de modificarse.** Ningún archivo de
    documentación se modifica sin leerlo del dump en el mismo turno en que se
    modifica. La memoria del agente no es fuente de verdad sobre el contenido de
    un archivo, una sección, un número de línea o un número de principio. Cada
    diff de documentación cita la sección exacta verificada contra el dump en ese
    turno. Si la sección no existe o no coincide con lo esperado, se aborta y se
    reporta. No hay excepciones.