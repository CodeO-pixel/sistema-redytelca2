---
description: SDD · Valida la spec RF por RF (tests + prueba manual)
agent: build
---

Recorre `specs/$1/spec.md` requisito por requisito. Para cada RF indica qué test lo cubre y el resultado de ejecutarlo con `php tests/run.php`.

Los RF de interfaz que no se puedan testear con PHP, verifícalos manualmente: abre `index.html`, prueba el flujo, revisa la consola del navegador.

Si algún RF no está cubierto o falla, dilo claramente. NO arregles nada todavía.

Después comprueba los criterios de finalización y dame un veredicto: ¿la spec está cumplida?

Empieza siempre con una de estas dos líneas:
- VEREDICTO: APROBADO
- VEREDICTO: CAMBIOS NECESARIOS