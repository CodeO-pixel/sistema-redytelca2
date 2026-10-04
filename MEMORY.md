# MEMORY.md — REDYTELCA

Memoria entre sesiones. Máx ~60 líneas. Actualizar al terminar cada tarea.

## Estado actual

- Fase 0 (esquema): completa. `bd_redytelca.sql` es fuente de verdad.
- Fase 1 (passwords/sesiones): completa. `password_hash()` + tokens en `sesiones`.
- Fase 2.5 (servicios N por cliente): completa. `api_servicios.php` implementado.
- Fase 3 (equipos): completa. `api_equipos.php` implementado.
- Fase 7 (UI avatar/toggles/matriz): completa.
- **Auditoría estática 2026: completa.** Informe en `docs/AUDITORIA_2026.md`.
- **Metodología de desarrollo SDD: instalada.** Ver `docs/METODOLOGIA_DESARROLLO_IA.md`.
- **Correcciones post-auditoría: pendientes.** Plan en `docs/ROADMAP.md`.
- **Spec 001 (fix-auditoria-logs): en fase de plan.** Spec y plan existen pero hay que regenerar el plan con la auditoría expandida.
- **Git: inicializado.** 3 commits. Rama `main`.

## Infraestructura de trabajo

- Harness de tests: `tests/run.php` (funciona, 4/4 smoke tests verdes).
- Comandos `/sdd-*`: 8 instalados en `.opencode/commands/`.
- Skill `sdd`: en `.agents/skills/sdd/SKILL.md`.
- Skills `redytelca-bd`, `redytelca-auth`, `redytelca-auditoria`: instaladas.
- Subagentes `coordinator`, `planner`, `implementer`, `reviewer`: instalados en `.opencode/agents/`.
- Comando `auditar`: instalado en `.opencode/commands/auditar.md`.
- MCP `chrome-devtools` y `context7`: en `opencode.json` (deshabilitados por ahora).
- Modelo activo: Nemotron 3 Ultra Free (OpenCode Zen).

## Decisiones y por qué

- Sin frameworks: alcance de tesis; portabilidad XAMPP/cPanel. Ver ADR 002.
- Tokens en BD (no JWT): revocación inmediata. Ver ADR 001.
- `clientes` 1:N `servicios`: un cliente, varios servicios reales. Ver ADR 003.
- Lógica de negocio en PHP, no en triggers: patrón consistente.
- Documentos obsoletos archivados en `docs/archive/` pero **fuera de git** (Opción A).

## Aprendizajes / errores a evitar

- No asumir 1 cliente = 1 servicio (bug histórico, corregido en Fase 2.5).
- `$_SESSION` nativo persiste en `login.php`/`logout.php` pese a la regla 7 (AUD-2026-B01).
- Triggers de auditoría usan `@app_usuario_id` pero ningún PHP lo setea (AUD-2026-B03, en corrección).
- `usuarios.estado` no se verifica en login (AUD-2026-B02).
- Al mover archivos con git en Windows: verificar que el `.gitignore` no matchee por patrón genérico (bug resuelto con `metodologia*.md`).

## Próximos pasos

- Regenerar `specs/001-fix-auditoria-logs/spec.md` para que cite evidencia concreta del `AUDITORIA_2026.md`.
- Regenerar `plan.md` con el contexto completo.
- Generar `tasks.md`.
- Implementar T1 (crear test rojo + guard CLI en `conexion.php`).
- Cerrar spec 001.
- Continuar con las specs 002, 003, 004, ...