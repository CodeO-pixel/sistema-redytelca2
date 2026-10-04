# Metodología de Desarrollo Asistido por IA — REDYTELCA

Cómo se trabaja en este proyecto. Documento vivo: se actualiza cuando cambia el proceso, no cuando cambia el código.

## 1. Principio fundamental

La IA genera el volumen. El humano garantiza la arquitectura, la veracidad y la seguridad. Ninguna corrección se implementa sin spec aprobada. Ningún archivo se modifica sin que exista un plan detrás. La responsabilidad final es humana.

## 2. Artefactos operativos

| Archivo | Rol | Se actualiza |
|---------|-----|--------------|
| `AGENTS.md` | Reglas operativas permanentes para el agente | Al cambiar reglas estructurales |
| `MEMORY.md` | Estado entre sesiones: fase, decisiones, aprendizajes | Al terminar cada tarea |
| `docs/constitution.md` | Principios innegociables del proyecto | Rara vez |
| `.agents/skills/sdd/SKILL.md` | Cómo se escribe una spec/plan/tasks | Al cambiar el flujo SDD |
| `.opencode/commands/sdd-*.md` | Comandos `/sdd-*` que orquestan el flujo | Al cambiar el flujo SDD |
| `specs/NNN-nombre/` | Una carpeta por corrección o funcionalidad | Durante cada spec |
| `docs/AUDITORIA_2026.md` | Informe de auditoría estática (fuente de hallazgos) | Solo cuando se re-audita |
| `docs/ROADMAP.md` | Hallazgos → specs, con estado | Al cerrar cada spec |

## 3. Flujo Spec-Driven Development (SDD)

Toda corrección o funcionalidad recorre estas 7 fases en orden. No se salta ninguna.

```
Constitución → Spec → Clarificación → Plan → Tareas → Implementación → Validación
```

### Fase 1 — Constitución
Ya existe (`docs/constitution.md`). Es leída por cada comando del flujo. No se toca salvo decisión explícita.

### Fase 2 — Spec
Comando: `/sdd-spec NNN-nombre idea`
Salida: `specs/NNN-nombre/spec.md`
Contiene: contexto, historias de usuario, requisitos funcionales en EARS, requisitos no funcionales, casos límite, fuera de alcance, criterios de finalización.
NO contiene: stack, arquitectura, nombres de archivos.

### Fase 3 — Clarificación
Comando: `/sdd-clarify NNN-nombre`
Salida: lista de ambigüedades, contradicciones, huecos detectados por el agente como QA.
Solo detecta. No propone soluciones.

### Fase 4 — Plan
Comando: `/sdd-plan NNN-nombre`
Salida: `specs/NNN-nombre/plan.md`
Contiene: archivos a tocar, funciones puras, algoritmo en pseudocódigo, decisiones justificadas con alternativas descartadas, estrategia de tests.

### Fase 5 — Tareas
Comando: `/sdd-tasks NNN-nombre`
Salida: `specs/NNN-nombre/tasks.md`
Contiene: 5-10 tareas pequeñas (20-30 min cada una), con RFs que cubre y criterio "Hecho cuando:" verificable.

### Fase 6 — Implementación
Comando: `/sdd-implement NNN-nombre TN`
Implementa UNA tarea. Tests primero (rojo). Código después (verde). Actualiza tasks.md. Se detiene.

### Fase 7 — Validación
Comando: `/sdd-validate NNN-nombre`
Recorre la spec RF por RF. Verifica con `php tests/run.php`. Veredicto: APROBADO o CAMBIOS NECESARIOS.

## 4. Modo Plan vs Modo Build

Los comandos invocan al agente con `agent: plan` o `agent: build` según la fase.

| Fase | Agente | Puede |
|------|--------|-------|
| Spec, Clarify, Plan, Tasks, Status | plan | Leer, escribir solo en `specs/` |
| Implement, Validate | build | Leer, escribir en todo el proyecto, ejecutar tests |

Si un comando del modo `plan` intenta tocar código, está fallando. Revisar el comando.

## 5. Verificación

- **Tests**: `php tests/run.php` — harness propio en `tests/`. Sin dependencias externas.
- **Test rojo antes que verde**: en cada tarea de implementación, el test se escribe primero y debe fallar por la razón correcta.
- **Sin avance con tests en rojo**: una tarea no se marca como hecha si algún test falla.
- **Verificación manual**: cuando el cambio tiene impacto visible, se prueba en el navegador con `index.html`.
- **Chrome DevTools (cuando exista `opencode.json`)**: validación end-to-end del frontend.

## 6. Reglas de oro

1. **La spec manda.** Si algo no está en la spec, no se implementa. Si falta una decisión, se para y se pregunta.
2. **Cambios de requisitos van primero a la spec.** Nunca al código.
3. **Una spec = un objetivo.** No mezclar hallazgos.
4. **Al terminar cada fase, actualizar `MEMORY.md`.**
5. **Al cerrar cada spec, marcar en `docs/ROADMAP.md`.**
6. **Si algo sale mal, se para.** No se improvisa una solución distinta a la planificada.

## 7. Trazabilidad

Cada spec referencia su hallazgo origen (si es corrección de auditoría) con el ID exacto (`AUD-2026-XXX`). Cada cierre se registra en `docs/ROADMAP.md`. Cada decisión estructural se documenta en `docs/ADR/`.

La cadena completa:

```
Hallazgo (docs/AUDITORIA_2026.md)
    ↓
Spec (specs/NNN/spec.md)
    ↓
Plan (specs/NNN/plan.md)
    ↓
Tareas (specs/NNN/tasks.md)
    ↓
Código corregido
    ↓
Tests verdes (php tests/run.php)
    ↓
Cierre en docs/ROADMAP.md
```

## 8. Límites del agente

- Nunca inventar nombres de tablas, columnas, funciones o IDs de DOM. Buscar en el workspace.
- Nunca tocar `bd_redytelca.sql` sin spec aprobada.
- Nunca usar `$_SESSION` nativo.
- Nunca guardar contraseñas en texto plano.
- Nunca agregar dependencias sin ADR y aprobación.
- Nunca reescribir código funcional que no forme parte de la spec activa.

## 9. Extensiones futuras

- **Subagentes**: planner / implementer / reviewer con permisos granulares. Configurados en `.opencode/agents/`.
- **Skills específicas del proyecto**: `redytelca-bd`, `redytelca-auth`, `redytelca-auditoria`. Configuradas en `.agents/skills/`.
- **MCP**: Chrome DevTools y Context7. Configurado en `opencode.json`.
- **Multiagente**: coordinator que orquesta el ciclo completo sin intervención humana entre fases.

Estado de cada extensión: ver `MEMORY.md`.

## 10. Cómo se trabaja con el agente en la práctica

1. El humano abre `opencode` en la raíz del proyecto.
2. El humano lee `MEMORY.md` para saber el estado actual.
3. El humano invoca el comando de la fase que toca.
4. El agente pregunta lo que no sabe. El humano responde una por una.
5. El agente genera el artefacto. El humano lo revisa.
6. Si algo está mal, el humano corrige y el agente regenera.
7. Al aprobar, se pasa a la siguiente fase.
8. Al cerrar la spec, el humano actualiza `MEMORY.md` y `docs/ROADMAP.md`.

**Nunca se aprueba un artefacto sin leerlo. Nunca se avanza sin aprobación explícita.**

## 11. Referencias

- `docs/METODOLOGIA_AUDITORIA_IA.md` — marco de auditoría (6 pasadas × 10 capas).
- `docs/constitution.md` — principios innegociables.
- `docs/AUDITORIA_2026.md` — informe de auditoría.
- `docs/ROADMAP.md` — plan de correcciones.
- `AGENTS.md` — reglas operativas del agente.