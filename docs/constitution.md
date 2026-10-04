# Constitución — REDYTELCA

Principios innegociables. Toda spec, plan y tarea debe cumplirlos.
Si algo los contradice, se para y se pregunta.

1. **Alcance de tesis**: stack sin frameworks, sin build, sin npm. Debe correr en
   XAMPP/cPanel sin instalación adicional. No se agregan dependencias salvo
   aprobación explícita y ADR.

2. **La spec manda**: nada se implementa sin spec activa en `specs/`. Si falta una
   decisión, se para y se pregunta. Los hallazgos de auditoría se convierten en
   specs antes de tocarse.

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

8. **Trazabilidad**: cada corrección es una spec; cada spec cierra hallazgos
   concretos de `docs/AUDITORIA_2026.md`; cada cierre actualiza `MEMORY.md` y
   `docs/ROADMAP.md`.