---
description: SDD · Genera el plan técnico de una spec aprobada
agent: plan
---

Lee `docs/constitution.md`, `AGENTS.md` y `specs/$1/spec.md`. Usa la skill sdd.

NO escribas código.

Genera `specs/$1/plan.md` con:
- Archivos que se crean o modifican y responsabilidad de cada uno.
- Funciones puras de lógica necesarias.
- Algoritmo en pseudocódigo.
- Cómo se refleja en la interfaz.
- Decisiones técnicas justificadas (con alternativa descartada).
- Estrategia de tests con `php tests/run.php`.
- Qué RF cubre cada parte.

Todo debe respetar la constitución y cubrir todos los RF. Si la spec no está aprobada o tiene dudas abiertas, para y avísame.