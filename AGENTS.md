# AGENTS.md — REDYTELCA

Sistema de gestión ISP (tesis de grado). Backend PHP 8.2 puro + PDO + MariaDB 10.4.
Frontend vanilla JS sin build tools. Uso interno + portal de clientes.

## Stack y estructura
- Backend: PHP 8.2 + PDO + MariaDB 10.4. Sin frameworks, sin Composer.
- Frontend: HTML/CSS/JS puro. Sin npm, sin bundler.
- BD: `bd_redytelca.sql` es la fuente de verdad del esquema.
- Endpoints: `api_*.php`. Middleware: `conexion.php` (PDO + auth staff/cliente).
- Frontend: `index.html` + `app.js` + `styles.css`.
- Docs: ver `docs/README.md`. Specs activas: ver `specs/`.

## Comandos
- Arranque local: `php -S localhost:8000` desde la raíz.
- Importar BD: `mysql -u root bd_redytelca < bd_redytelca.sql`
- Tests: `php tests/run.php`

## Convenciones
- Textos de UI, comentarios y documentación en español.
- Nombres de tablas/columnas en snake_case. **Buscar el nombre exacto antes de inventar.**
- Patrones existentes (paginación, modales, fetch) se replican, no se reinventan.
- Contraseñas: `password_hash()` / `password_verify()`. Nunca texto plano.
- Sesiones: token en tabla `sesiones`, header `X-Session-Token`. Nunca `$_SESSION` nativo.

## Trampas conocidas del dominio
- `servicios` es 1:N con `clientes`. No asumir 1:1.
- `naps`/`olts`/`nodos` en plural; columnas `id_naps`/`id_olts`/`id_nodos`.
- `contratos.estado` (legal) y `servicios.estado_comercial` (operativo) son ejes distintos.
- Transiciones de estado validadas en `transicion_estado` (BEFORE UPDATE, no INSERT).
- Ver `docs/constitution.md` y la spec activa antes de tocar código.

## Forma de trabajar
- Cambios pequeños y enfocados. No reescribir lo que funciona.
- **Antes de generar código: listar TODOS los archivos afectados. Esperar confirmación.**
- Archivos completos, nunca fragmentos (`// resto igual` prohibido).
- Al terminar: comentario breve explicando qué se corrigió y por qué.
- Al terminar cada tarea: actualizar `MEMORY.md`.

## Límites
- Siempre: buscar el nombre exacto; replicar patrones; explicar el porqué.
- Pregunta antes: tocar >1 archivo, dependencias nuevas, cambios de esquema, archivos nuevos.
- Nunca: reestructurar la BD sin pedido; `$_SESSION` nativo; contraseñas en texto plano; inventar nombres.

## Verificación
- Sin suite automatizada todavía. Después de cada cambio: ejecutar `php tests/run.php`
  (cuando exista), probar manualmente el flujo end-to-end, revisar `DevTools > Network`
  para las llamadas `api_*.php`.

## Memoria
- Al empezar: leer `MEMORY.md`.
- Al terminar: actualizar `MEMORY.md` (estado, decisiones, aprendizajes).
- Si algo se vuelve regla permanente: moverlo a este archivo o a `docs/constitution.md`.