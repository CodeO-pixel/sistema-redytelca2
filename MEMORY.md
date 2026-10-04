# MEMORY.md — REDYTELCA

Memoria entre sesiones. Máx ~50 líneas. Actualizar al terminar cada tarea.

## Estado actual
- Fase 0 (esquema): completa. `bd_redytelca.sql` es fuente de verdad.
- Fase 1 (passwords/sesiones): completa. `password_hash()` + tokens en `sesiones`.
- Fase 2.5 (servicios N por cliente): completa. `api_servicios.php` implementado.
- Fase 3 (equipos): completa. `api_equipos.php` implementado.
- Fase 7 (UI avatar/toggles/matriz): completa.
- **Auditoría estática 2026: ejecutada. Informe en `docs/AUDITORIA_2026.md`.**
- **Correcciones post-auditoría: pendientes. Plan en `docs/ROADMAP.md`.**
- **Metodología SDD: en instalación.** Foundation (`AGENTS.md`, `MEMORY.md`,
  `docs/constitution.md`) creada. Falta: harness de tests, comandos `.opencode/`,
  primera spec.

## Decisiones y por qué
- Sin frameworks: alcance de tesis; portabilidad XAMPP/cPanel.
- Tokens en BD (no JWT): simplicidad + revocación inmediata.
- Lógica de negocio en PHP, no en triggers: patrón consistente del proyecto.
- RBAC por páginas (`rol_modulo_pagina`): el esquema prevé permisos granulares
  (`rol_modulo_permiso`) pero el código no los usa. Decisión pendiente — ver ROADMAP.
- Harness OpenCode + Nemotron 3 Ultra (OpenCode Zen): sin coste, calidad suficiente
  para tesis.

## Aprendizajes / errores a evitar
- No asumir 1 cliente = 1 servicio (bug histórico, corregido en Fase 2.5).
- `$_SESSION` nativo persiste en `login.php`/`logout.php` pese a la regla 7 (bug activo).
- Triggers de auditoría usan `@app_usuario_id` pero ningún PHP lo setea (bug activo).
- Comillas Unicode corruptas en `login.php` (ya corregido, no reintroducir).

## Próximos pasos
- Instalar harness de tests PHP (spec 000-test-harness).
- Configurar `.opencode/commands/` con los comandos `sdd-*`.
- Ejecutar specs de corrección siguiendo `docs/ROADMAP.md`, empezando por los 8 críticos.