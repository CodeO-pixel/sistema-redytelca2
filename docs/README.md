# docs/ — Índice de documentación

Documentación de REDYTELCA. Cada archivo tiene un rol único. Si dos archivos dicen lo mismo, uno está de más.

## Documentos fundacionales

| Archivo | Rol |
|---------|-----|
| `constitution.md` | Principios innegociables del proyecto. Ley superior. |
| `METODOLOGIA_DESARROLLO_IA.md` | Cómo se trabaja en este proyecto (SDD, fases, verificación). |
| `METODOLOGIA_AUDITORIA_IA.md` | Marco de auditoría: 6 pasadas × 10 capas × contexto ISP. |

## Estado y planificación

| Archivo | Rol |
|---------|-----|
| `AUDITORIA_2026.md` | Informe de auditoría estática. Fuente de hallazgos. |
| `ROADMAP.md` | Hallazgos → specs, con estado. Plan de correcciones. |

## Contexto del proyecto

| Archivo | Rol |
|---------|-----|
| `ARQUITECTURA.md` | Cómo está construido el sistema (real, no aspiracional). |
| `BD.md` | Esquema de base de datos, relaciones y decisiones. |
| `DIAGRAMA_CASOS_USO.md` | Actores, casos de uso, flujo de la aplicación. |

## Decisiones

| Carpeta/Archivo | Rol |
|-----------------|-----|
| `ADR/` | Architecture Decision Records. Por qué se tomó cada decisión estructural. |
| `ADR/001-tokens-vs-jwt.md` | Por qué tokens en BD en lugar de JWT. |
| `ADR/002-sin-frameworks.md` | Por qué PHP/JS puro sin frameworks. |
| `ADR/003-servicios-1n.md` | Por qué un cliente tiene N servicios. |

## Archivo histórico

| Carpeta | Rol |
|---------|-----|
| `archive/` | Documentos obsoletos conservados por trazabilidad de tesis. No consultar para trabajo actual. |

## Fuera de esta carpeta

- `../AGENTS.md` — reglas operativas para el agente.
- `../MEMORY.md` — estado entre sesiones.
- `../specs/` — specs activas y cerradas.
- `../tests/` — harness de tests.