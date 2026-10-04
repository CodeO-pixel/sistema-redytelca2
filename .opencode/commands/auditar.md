---
description: Aplica el marco de auditoría a un componente específico de REDYTELCA
agent: plan
---

Aplica la metodología de `docs/METODOLOGIA_AUDITORIA_IA.md` a: $ARGUMENTS

Antes de empezar, lee:
- `docs/METODOLOGIA_AUDITORIA_IA.md` (marco completo)
- `docs/AUDITORIA_2026.md` (hallazgos ya conocidos, para no duplicar)
- `docs/ARQUITECTURA.md`
- `docs/BD.md`
- La skill `redytelca-auditoria`
- El código del componente a auditar

Sigue las 6 pasadas en orden. Para cada hallazgo detectado usa el formato de registro de la skill `redytelca-auditoria`:

- ID (siguiente número disponible)
- Severidad (Crítico / Alto / Medio / Bajo)
- Capa (1-10)
- Línea roja (Sí / No)
- Evidencia (archivo, línea, fragmento)
- Impacto
- Dominio ISP
- Estado (Nuevo / Duplicado de AUD-2026-XXX)

Clasifica con la taxonomía del marco. Identifica si toca línea roja ISP.

**NO propongas correcciones.** Solo audita y entrega el informe.

**NO modifiques `docs/AUDITORIA_2026.md`.** Solo devuelve el informe en el chat. Si el usuario aprueba, se añade después con una spec específica.

Espera aprobación antes de hacer cualquier otra cosa.