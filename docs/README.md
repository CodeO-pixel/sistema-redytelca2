# docs/ — Índice de documentación

Documentación de REDYTELCA. Cada archivo tiene un rol único. Si dos archivos dicen lo mismo, uno está de más.

**Regla base:** cada hecho vive en un solo lugar. El mapa completo del ecosistema documental está en `GOBERNANZA.md`.

## Documentos fundacionales

| Archivo | Rol |
|---------|-----|
| `constitution.md` | Principios innegociables del proyecto. Ley superior. |
| `METODOLOGIA_DESARROLLO.md` | Cómo se trabaja en este proyecto (ciclo, roles, verificación). |
| `METODOLOGIA_AUDITORIA.md` | Marco de auditoría: 6 pasadas × 10 capas × contexto ISP. |
| `GOBERNANZA.md` | Mapa del ecosistema documental. Dónde vive cada cosa, reglas de conformidad. |

## Estado y planificación

| Archivo | Rol |
|---------|-----|
| `ROADMAP.md` | **Única fuente de specs y su estado.** Plan de correcciones por bloques. |
| `AUDITORIA_2026.md` | Foto con fecha de corte de los hallazgos de auditoría. |

## Contexto del proyecto

| Archivo | Rol |
|---------|-----|
| `ARQUITECTURA.md` | Cómo está construido el sistema (real, no aspiracional). |
| `BD.md` | Esquema de base de datos, relaciones y decisiones. |

## Material de consulta

| Archivo | Rol |
|---------|-----|
| `REFERENCIA.md` | Material atemporal: estándares aplicados, frases de defensa para la tesis, decisiones de industria descartadas, aprendizajes, referencias de mercado. |
| `VERIFICACION.md` | Lecciones de verificación y checklists. Lo que no debe repetirse. |

## Decisiones

| Carpeta/Archivo | Rol |
|-----------------|-----|
| `ADR/` | Architecture Decision Records. Por qué se tomó cada decisión estructural. |
| `ADR/001-tokens-vs-jwt.md` | Por qué tokens en BD en lugar de JWT. |
| `ADR/002-sin-frameworks.md` | Por qué PHP/JS puro sin frameworks. |
| `ADR/003-servicios-1n.md` | Por qué un cliente tiene N servicios. |
| `ADR/004-rbac-migracion-001a.md` | Migración al modelo RBAC Core N:M. |
| `ADR/005-extension-rol-modulo.md` | Extensión `rol_modulo` + `rol_modulo_permiso`. |
| `ADR/006-auditoria-inmutable.md` | Auditoría inmutable vía triggers. |
| `ADR/007-config-admin-only.md` | Config Admin-only (Grupo E, Diseño B.3). |
| `ADR/008-rbac-dos-niveles.md` | RBAC dos niveles con `rol_modulo_pagina`. |
| `ADR/009-branding-publico-separado.md` | Por qué hay dos endpoints de empresa (público para branding, protegido para config). |
| `ADR/010-portal-cliente-separado.md` | Por qué el portal de cliente es una app separada del panel admin. |
| `ADR/011-notificaciones-servicio.md` | Por qué notificaciones es un servicio transversal con dos bandejas. |
| `ADR/012-alcance-proyecto.md` | Qué está dentro y qué fuera del alcance de tesis. |
| `ADR/013-bd-completa-refactor.md` | Refactor consolidado de BD previo a revisión. |
| `ADR/014-normalizacion-direcciones-telefonos.md` | Normalización de direcciones y teléfonos (indicación docente). |
| `ADR/015-reduccion-alcance-bd-completa.md` | Reducción del alcance del bloque BD-COMPLETA. |
| `ADR/016-convencion-claves.md` | Convención `PK=id` / `FK=tabla_id`. |
| `ADR/017-mvc-sin-framework.md` | MVC completo sin frameworks externos. |

## Archivo histórico

| Carpeta | Rol |
|---------|-----|
| `archive/` | Documentos obsoletos conservados por trazabilidad de tesis. No consultar para trabajo actual. |

## Fuera de esta carpeta

- `../AGENTS.md` — reglas operativas para el agente.
- `../MEMORY.md` — estado del proyecto entre sesiones.
- `../bd_redytelca.sql` — fuente de verdad del esquema.
- `../tests/` — harness de tests.
