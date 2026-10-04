# Instrucciones para GitHub Copilot — Proyecto REDYTELCA

Proyecto: sistema de gestión ISP (tesis). Stack: PHP puro + PDO + MySQL/MariaDB
en el backend, frontend vanilla JS sin build tools (app.js + index.html +
styles.css, sin frameworks, sin npm).

## Reglas obligatorias

1. **Nunca inventes nombres.** No asumas nomenclatura "estándar" de tabla,
   columna, función JS o id de elemento DOM. Antes de escribir código, busca
   el nombre EXACTO en el workspace. Si una tabla se llama `naps` (no
   `nap`), una columna `id_naps` (no `id_nap`), respétalo tal cual.

2. **Archivos completos, nunca fragmentos.** Cada archivo modificado se
   entrega de principio a fin, listo para reemplazar el original. Prohibido
   `// resto del código igual` o bloques parciales.

3. **Explica el porqué.** Al final de cada archivo modificado, agrega un
   comentario breve explicando qué corrección resuelve y por qué
   mecánicamente.

4. **Alcance acotado.** No toques nada fuera de lo que te pida en el
   mensaje puntual, aunque veas "mejoras" posibles en otro lado. Si una
   corrección requiere tocar más de un archivo, dime la lista completa de
   archivos afectados ANTES de generar el primero.

5. **Patrones existentes por encima de "buenas prácticas" genéricas.** Este
   proyecto tiene patrones ya establecidos y repetidos (ej. cómo se pagina
   una tabla, cómo se abre/cierra un modal, cómo se hace un fetch con
   manejo de error). Replica el patrón ya usado en el archivo antes de
   proponer uno "más moderno" o distinto.

6. **No reestructures el esquema de base de datos** sin que se te pida
   explícitamente. Las tablas y columnas actuales son la fuente de verdad.

7. **Contraseñas y sesiones:** este proyecto ya migró a `password_hash()`/
   `password_verify()` (ver `conexion.php`) y usa tokens en la tabla
   `sesiones` (header `X-Session-Token` con fallback a query string). No
   reintroduzcas comparación de contraseña en texto plano ni `$_SESSION`
   nativo de PHP como mecanismo de autenticación real.