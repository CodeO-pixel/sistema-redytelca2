# AGENTS.md — REDYTELCA

Hoja de arranque técnica del repo. Sistema de gestión ISP (tesis de grado). Backend PHP 8.2 puro + PDO + MariaDB 10.4. Frontend vanilla JS sin build tools. Uso interno + portal de clientes.

**Este archivo describe el repo: stack, comandos, convenciones de código, trampas del dominio y reglas operativas vigentes.**

No lleva:
- Ciclo de trabajo, roles, reglas con AI Studio, rigor de fuentes, forma de trabajar → `docs/METODOLOGIA_DESARROLLO_IA.md`.
- Dónde vive cada tipo de información → `docs/GOBERNANZA.md`.
- Estado del proyecto → `MEMORY.md`.
- Specs y su estado → `docs/ROADMAP.md`.
- **Origen histórico de cada regla de Fase 1 (qué bug la motivó, cuándo)** → `docs/HISTORICO_FASE1.md` §6 (reconstruido parcial, F2.0b).
- Lecciones de verificación → `docs/VERIFICACION.md`.

---

## Stack y estructura

- Backend: PHP 8.2 + PDO + MariaDB 10.4. Sin frameworks, sin Composer.
- Frontend: HTML/CSS/JS puro. Sin npm, sin bundler.
- BD: `bd_redytelca.sql` es la fuente de verdad del esquema (dump con datos). `bd_redytelca_schema.sql` es el DDL puro versionable (regenerado 2026-10-31 en F2.0b, incluye `logo_path_sidebar` y seed de catálogos).
- **Estructura:** `public/` (DocumentRoot, único servido por HTTP) + `src/` (código: core, controllers, services, repositories, models, validators, tests) + raíz (BD, docs, config). ADR 018.
- Endpoints: `public/api_*.php` (thin, patrón 139a–e) y `public/api_portal_*.php` (thin, patrón A2-B.3/A2-B.4/A2-B.5 — gate `Auth::requireClientAuth` solamente, sin `requirePageAccess`/`requireOperation`). `public/` también contiene `login.php`, `logout.php`, `auth_state.php`, `cambiar_password.php`, `clientes_listar.php`, `registrar_cliente.php`, `cliente_*.php`, `roles_*.php`, `permisos_*.php`, `usuarios_*.php`, `usuarios_roles.php`, `branding.css.php`, `branding-logo.php`, `portal_cliente.php`, `router.php`, `index.html`, `styles.css`, `js/`, `Logos/`, `uploads/`.
- **Kernel MVC: `src/core/`** (Bootstrap, Config, Database, Response, Validator, Auth, Exceptions, bases). Inicialización vía `Http::init()`. Auth vía `Auth::requireStaffAuth` / `requireClientAuth` / `requirePageAccess` / `requireOperation`.
- **`conexion.php` fue eliminado en 139d Tanda E (2026-10-18).**
- **Frontend MVC cerrado el 2026-10-30 (Fase 1).** `public/app.js` **eliminado** en B5.2.a. Los 22 módulos funcionan sobre `public/js/core/helpers.js` + `public/js/models/*` + `public/js/views/*` + `public/js/controllers/*` + `public/js/main.js`.
- **`public/js/main.js`** es el entry point del frontend (wiring, `Router.register`, `Session.onClear`, drawer mobile). **No vive en `core/`**; es único y orquesta el bootstrap.
- **`public/js/core/helpers.js`** expone las 6 utilidades globales del frontend: `window.escapeHtml`, `window.normalizeForSearch`, `window.getStatusClass`, `window.pageSize`, `window.selectedIds`, `window.currentClienteSearch`. Cargado en `index.html` después de `Pagination.js` y antes de `AuthController.js`.
- **22 módulos migrados a MVC:** Clientes, Planes, Nodos, OLTs, NAPs, Servicios, Equipos, Roles, Permisos, Usuarios, Facturas, Pagos, Contratos, Tickets, Auditoría, Tareas, Pedidos, Empresa, Perfil, Mapa, Dashboard, Reportes.
- **`opencode.json`** en la raíz: configuración del asistente IA local (OpenCode). No forma parte del runtime del sistema. Solo la usa el humano durante el desarrollo.
- Docs: ver `docs/README.md`. Estado actual y fase activa: ver `MEMORY.md`.

---

## Comandos

- **Arranque local:** `php -S localhost:8000 -t public public/router.php` desde la raíz. El router script es necesario para `/cliente`, `/portal`, `/pagos`, `/pago` → `portal_cliente.php` y `/admin`, `/` → `index.html`. **Esta es la URL canónica de dev según este documento.** **Nota F2.0b:** `MEMORY.md` documenta Apache (`http://localhost/redytelca/public/`) como entorno real del usuario. Ambos coexisten válidamente. `php -S` es el fallback sin vhost. `DOC-12` cerrado 2026-10-31.
- Importar BD: `mysql -u root bd_redytelca < bd_redytelca.sql`
- Importar solo schema (sin datos): `mysql -u root bd_redytelca < bd_redytelca_schema.sql` (incluye seed de catálogos: `rol`, `modulos`, `paginas`, `permisos`, `rol_modulo`, `rol_modulo_pagina`, `rol_modulo_permiso`, `configuracion_empresa`).
- Tests: `php src/tests/run.php`
- Lint: `php -l <archivo.php>` y `node --check <archivo.js>` antes de proponer cualquier cambio.

---

## Convenciones de código

- Textos de UI, comentarios y documentación en español.
- Nombres de tablas/columnas en snake_case. **Buscar el nombre exacto antes de inventar.**
  - **Convención de claves (ADR 016):** PK siempre `id`. FK siempre `[tabla_singular]_id`. Aplica a todas las tablas, incluidas las del RBAC. Los nombres de tabla se quedan en plural (`clientes`, `servicios`), excepto `auditoria_logs`.
- **Tabla de auditoría:** `auditoria_logs` (no `logs`). Los triggers escriben ahí. Es inmutable.
- Patrones existentes (paginación, modales, fetch) se replican, no se reinventan.
- **Tablas pivot sin modelo propio:** `r_cliente_telefono` es N:M puro. Se maneja desde el repositorio (`ClienteRepository`), no desde un modelo.
- Contraseñas: `password_hash()` / `password_verify()`. Nunca texto plano.
- Sesiones: token en tabla `sesiones`, header `X-Session-Token`. Nunca `$_SESSION` nativo.
- Archivos PHP puros **no llevan `?>` al final**. PSR-2 §2.2. Todos los archivos del proyecto siguen este patrón.
- **`<?php` debe ser la primera línea del archivo (byte 0).** Comentario `// filename` va en línea 2. Un `// filename` antes de `<?php` se emite como output literal; `header()` falla con "headers already sent" y el body JSON/CSS llega con prefijo de texto. AI Studio invierte el orden sistemáticamente en archivos nuevos. Verificar en cada entrega.
- **Endpoints thin (patrón 139a–e):**
  - Cada verbo HTTP tiene su `requirePageAccess` + `requireOperation`.
  - **Excepción: los `api_portal_*.php` usan solo `Auth::requireClientAuth($pdo)`.** No tienen `requirePageAccess`/`requireOperation` porque el cliente no tiene rol ni módulo; la autorización es por ownership del recurso (`cliente_id` de la sesión). Gate único, defensa BOLA/IDOR en el service.
  - El controller inyecta `$pdo` por constructor; el service hace la lógica; el repository contiene el SQL.
  - **`unset($input['id_X'])` en cada `update()`** antes de pasar `$input` o `$validated` al service. Causa raíz de BUG-6.3 y BUG-6.4.
  - Catch final siempre `Response::fromException($e)`. **No reimplementar el mapeo a HTTP.**
  - Indentación 4 espacios. AI Studio la quita sistemáticamente (VERIFICACION.md §2.8).
- **Transacciones anidadas en PDO:** `PDO::beginTransaction()` lanza `PDOException` si ya hay una transacción activa. Los services que puedan ser invocados dentro de una transacción externa deben detectar `$this->pdo->inTransaction()` y ejecutar el closure sin abrir otra. Ejemplo canónico: `ClienteService::crear()` (fix A2.9).
- **SQL con acentos desde `cmd` de Windows:** `mysql -e "..."` corrompe UTF-8 (CP850 vs utf8mb4). Para cualquier DML/DDL con acentos, crear un archivo `.sql` UTF-8 y ejecutar con `mysql -u root bd_redytelca < archivo.sql`. El `-e` solo para queries ASCII. Para `mysqldump` con acentos, usar `--default-character-set=utf8mb4`. Lección A2.7.
- **`ON UPDATE RESTRICT` explícito** en FKs que apuntan a PKs mutables (ADR 013 P3). `ON UPDATE CASCADE` rompe auditoría (MariaDB no dispara triggers en cascada).
- **`PDOException` SQL error 1644** (SIGNAL de trigger) → 422 con el mensaje del trigger vía `Response::fromException`. **`PDOException` SQL error 4025** (CHECK violado) → 422 con el mensaje del CHECK (fix B4). **No reimplementar.**

---

## Patrón de módulo frontend (Fase 1 — obligatorio para Fase 2+)

Cada módulo sigue este patrón. **Origen histórico de cada regla:** `docs/HISTORICO_FASE1.md` §6 (reconstruido parcial). Aquí solo las reglas operativas.

### Archivos por módulo

- `public/js/models/<Modulo>Model.js` — cache, load, getById, create, update, delete.
- `public/js/views/<Modulo>View.js` — renderTable, renderPagination, toggleForm, fillForm, populateSelects, `_attachListeners`.
- `public/js/controllers/<Modulo>Controller.js` — init, load, renderPage, save, edit, confirmDelete.
- `<script>` de los 3 en `index.html`.
- `Router.register` en `main.js`.
- HTML sin `onclick`/`oninput` inline (excepto deuda `F-16` pendiente en Fase 2).
- `<select>` con ENUMs verificados contra `bd_redytelca.sql`.

### Controller — patrón obligatorio

- `load(page = 1)`: **reset síncrono de `selectedIds` + `disabled` de botones ANTES del primer `await`** → carga caches padre si vacíos (`cache.length === 0`) → `Model.load()` → `renderPage(page)`.
- `renderPage(page)`: filtra por búsqueda normalizada → `totalPages`/`safePage` → `window.__<modulo>CurrentPage = safePage` → `renderTable` → `renderPagination`.
- `save()`: captura `wasEditing`/`currentPage` → POST o PUT → `load(wasEditing ? currentPage : 1)` → si creación, navega a última página.
- `edit(id)`: async defensivo (recarga caches padre si vacíos) → `toggleForm(false, false)` → `populateSelects` → `fillForm`.
- `confirmDelete(id)`: captura `currentPage` → `Modal.confirm` → `delete` → `load(currentPage)`.
- **`_saving` guard** en todos los métodos que hacen POST/PUT/DELETE: `if (this._saving) return;` al inicio, `this._saving = true` antes del try, `this._saving = false` en el `finally`. Bloquea doble submit por Enter ×3 rápido o doble click. Origen: BUG-B3.10-ENTER-DUPLICADOS.

### View — patrón obligatorio

- `renderTable` con click handler por fila (`selectedIds`, `.selected-row`, habilita edit/delete). Empty state con CTA si no hay resultados vs no hay datos.
- `renderPagination` con Anterior/Siguiente/contador.
- `_attachListeners` con disabled inicial de edit/delete. Listeners con `removeAttribute('onclick')` + `addEventListener`.
- `populateSelects` con labels legibles (código + contexto, nombres, no IDs). **Si el valor actual no está en cache, inyectar `<option>` con datos del JOIN del backend.**
- `fillForm` con todos los campos, incluidos los del JOIN. Re-habilita los selects que un flujo anterior haya deshabilitado.

### Helpers heredados

Todo módulo hereda según aplique:

- **`_searchTimer`**: debounce de búsqueda 250ms.
- **`_dirty`**: dirty tracking para confirmación de cierre con cambios sin guardar.
- **`_persist(search, page)`**: sessionStorage por módulo. Clave `redytelca.<modulo>.search` y `.page`. Se restaura en `init()`.
- **`_montoTouched`**: flag que impide que el autocompletar sobrescriba un campo editado manualmente.
- **`formatDual(usd, factura)`**: devuelve `$X (Bs. Y)` cuando `moneda='VES'`.
- **Estados terminales**: el `<select>` de estado se deshabilita cuando el valor actual es terminal (`rescindido`, `pagada`, `anulada`, `rechazado`, `Cerrado`). El controller bloquea el cambio incluso si se desbloquea el select por DevTools.

### Caches y limpieza

- **`Session.onClear` (en `main.js`)** limpia los 22 Models, `window.selectedIds`, `window.permissionContext`, `window.__dashboardTotals`, y destruye las charts. Se dispara al logout.
- **`AuthController._clearCaches()`** replica el `Session.onClear` para el caso de borde "cambio de módulos entre dos sesiones del mismo navegador sin logout". Limpia los 22 Models.
- **`window.*Cache` (B2) fue eliminado en B5.2.a.** Los 22 Models tienen su propio `.cache`. No se reintroducen.

### Checks obligatorios por módulo

- **10 checks comunes:** sidebar → módulo → click fila → Nuevo/Guardar/Editar/Eliminar → F5 → logout (`cache.length === 0`) → consola limpia. **Check 7:** la fila desactivada DEBE desaparecer sin F5. **Check 5:** al navegar fuera y volver, los botones edit/delete/reset DEBEN quedar disabled.
- **3 transversales:**
  - **Re-login:** login → módulo → seleccionar fila → logout → login → **Dashboard** → click módulo → tabla poblada → editar funciona.
  - **Paginación doble:** crear en p1 → navega a última; editar en p2 → vuelve a p2.
  - **Acceso directo:** login → click módulo directo → `populateSelects` poblado.

### Reglas operativas vigentes (aplicar en Fase 2+)

Las reglas emergidas de Fase 1 y Fase 4, consolidadas. Origen de cada una: `HISTORICO_FASE1.md` §6, `VERIFICACION.md` §2 y `MEMORY.md` §Fase 4.

**Sobre archivos y turnos.**

- **Siempre archivo completo, nunca fragmento.** Un fragmento deja funciones sin definir. Endurecida 2026-10-27 tras reincidencia en B3.5. **Re-endurecida 2026-10-31 tras reincidencia en F2.0b.**
- **Replicar el fix en todos los módulos afectados en el mismo turno.**
- **No diferir un bug introducido o expuesto por el fix actual.** Se resuelve en el mismo cambio o se declara "no sé cómo" y se espera instrucción.
- **No borrar comentarios ni líneas no relacionadas al reescribir un archivo completo.**

**Sobre contratos backend ↔ frontend.**

- **Select con valor fuera de cache:** inyectar `<option>` con datos del JOIN. `populateSelects(selectedId, labelFallback)`.
- **Usar el JOIN del backend, no recomponer con `Model.cache`.** Si el backend ya devuelve `nombres`, `apellidos`, `cedula`, `plan_nombre`, `plan_precio`, `nap_codigo`, `olt_codigo`, `servicio_estado`, `servicio_alias`, `servicio_direccion`, el View los usa directamente. Fallback al cache solo si el campo viene `null`.
- **La tabla debe exponer los joins que el modal permite elegir.**
- **Soft delete invisible a la UI:** todo `*_listar.php` filtra `estado <> 'inactivo'/'Inactivo'`.
- **Cédula en tablas que listan clientes.** El repo expone `+c.cedula AS cedula` y el View pinta columna "Cédula" + el select incluye `(C.I. V-XXX)`.

**Sobre UI y modales.**

- **`Router.showPage` cierra modales abiertos** (`.modal-overlay` con `display:flex`/`block`). No cerrar `.core-modal-overlay` (el `Modal.confirm` de `public/js/core/Modal.js`). Origen: BUG-B3.7-MODAL-PERSISTE.
- **`Router.showPage` y `AuthController._resetToDashboard` resetean el scroll.** `window.scrollTo(0, 0)` + `document.querySelector('.main-content').scrollTop = 0` justo después de activar la `.page` (en `showPage`) o al final de la función (en `_resetToDashboard`). Sin esto, el scroll del documento se conserva entre páginas y la nueva aparece "ya scrolleada" (bug Dashboard ↔ Reportes, 2026-10-31). Estándar Inertia.js / Vue Router / Angular. Cualquier wrapper de navegación nuevo debe replicarlo.
- **Modales que se abren desde otra página viven a nivel de `main`, fuera de cualquier `.page`.** Un hijo con `display: flex` dentro de un padre `display: none` no se renderiza. Origen: BUG-B3.9-MODAL-DISPLAY-NONE.
- **Un flag `disabled` de UI activado en un flujo debe resetearse al iniciar otro flujo.** `fillForm` re-habilita los selects antes de poblar. Origen: BUG-B3.9-FILLFORM-DISABLED.
- **Enter en cualquier campo del form guarda, excepto en `<textarea>`.** `e.key === 'Enter' && e.target.tagName !== 'TEXTAREA'`. El `btn.disabled` NO bloquea el listener de Enter; se necesita el flag `_saving`. Origen: BUG-B3.10-ENTER-DUPLICADOS.
- **Escape cierra TODOS los modales, no solo el form principal.** Listener global de `keydown` en `document` dentro de `_attachListeners`. Respeta el dirty check del form principal (`_requestClose()`). Modales secundarios se cierran directo. Origen: BUG-B3.10-ESCAPE.
- **Identidad y permisos son acciones separadas.** "Editar usuario" (username, email) y "Editar roles" (checkboxes) en modales distintos. Patrón Salesforce "Edit User" / "Edit Permission Set Assignments", Azure AD "Properties" / "Assigned roles", Splynx "Edit user" / "Edit user permissions". Regla emergida en Fase 4.2-hotfix1 (2026-10-31).

**Sobre datos y fechas.**

- **El backend devuelve timestamps en UTC.** El frontend convierte a hora local (VET, UTC-4) en tablas con `formatDateTime`. Origen: BUG-B3.8.5-FECHA-UTC.
- **"Historial de sesiones" ≠ "sesiones vivas".** `SesionRepository::findByUsuarioId` trae las últimas 20 sin filtrar por `revocado_en`/`expira_en`. Es historial, no sesiones activas. `UX-PERFIL-ESTADO-SESION` (F2.0a).

**Sobre caches y "existe/no existe".**

- **Antes de verificar "existe/no existe" en un cache, cargar el Model si está vacío.** `if (window.XModel && window.XModel.cache.length === 0) await window.XModel.load();`. La función se vuelve `async`. Si la carga falla, `Toast.error` y `return`. Origen: BUG-B3.10-VER-TICKET-FRIO.

**Sobre historial y auditoría visual.**

- **Historial de asignaciones (o cualquier tabla de auditoría visual) expone usernames vía JOIN, no IDs crudos.** `LEFT JOIN usuarios` + fallback a `'Técnico #N'` si el usuario fue eliminado. Origen: BUG-B3.10-HISTORIAL-IDS.

**Sobre branding.**

- **El branding pre-login debe ser render-blocking.** Color por `branding.css.php` (CSS render-blocking). Logo por `branding-logo.php` (img src render-blocking). No fetch JS.
- **2 logos independientes por contexto de fondo:** `logo_path` (login, fondo claro) + `logo_path_sidebar` (sidebar, fondo oscuro). No reutilizar un logo. CSS `invert()` se ve mal con colores de marca.
- **`<input type="file">` no se puede prellenar por JavaScript.** "No file chosen" es estándar del navegador. Lo que muestra el logo actual son los `<img>` de preview.
- **Los file inputs necesitan nombres canónicos por field:** `logo_login.{ext}` y `logo_sidebar.{ext}`.

**Sobre auth y contraseñas.**

- **Cambio de contraseña fuerza logout total.** Backend (A3) revoca todas las sesiones del usuario en la transacción. Frontend llama `AuthController.logout()` explícitamente. Cumple OWASP ASVS V7.4.3 (Nivel 1).
- **`_requestCloseModal` respeta `must_change_password`.** Si el flag está a 1, el modal no se cierra (ni Escape ni ×). Única salida: cambiar la contraseña.
- **El último admin activo no puede perder el rol Administrador.** `UsuarioController::saveRoles` lanza `DomainException` 422 si el usuario tenía rol 1 y se intenta quitar siendo el único admin activo. `UsuarioService::actualizar` mantiene la misma protección cuando el body del update manda `roles`. `UsuarioService::desactivar` mantiene la misma protección. Regla emergida en Fase 4.2 (2026-10-31).

**Sobre matriz de permisos (Fase 4.3).**

- **La matriz de permisos es tabla, no cards.** Filas=páginas agrupadas por módulo, columnas=Ver/Leer/Crear/Editar/Eliminar/Validar. Patrón Salesforce Profiles / Splynx Roles / SAP Authorization / Odoo Access Rights. Regla emergida en Fase 4.3 (2026-10-31).
- **Un permiso que no aplica a una página no debe ofrecerse como checkbox.** `PermisoService::PERMISOS_POR_PAGINA` mapea url_pagina → IDs aplicables. Celda `—` para no aplicables. AWS IAM no ofrece `s3:PutObject` sobre un bucket de solo lectura. Regla emergida en Fase 4.3 (2026-10-31).

**Sobre notificaciones (A2-B.1, 2026-10-31).**

- **`leida_en` (timestamp) es la fuente de verdad de "leída".** La UI lee `leida_en IS NULL` para "no leída". El enum `estado` se mantiene sincronizado en el mismo UPDATE (retrocompatibilidad). Permite "leída hace 2 horas". Deuda `NOTIF-ESTADO-LEGACY` (eliminar `estado`) a Fase 7.
- **`notificar()` corre dentro de la transacción del cambio principal.** Si la notificación falla, el cambio principal hace rollback. Transactional Outbox Pattern (AWS Prescriptive Guidance) sin cola. Datos consistentes > resiliencia. Deuda `NOTIF-IDEMPOTENCIA` a Fase 7.
- **Destinatarios staff = todos los admins activos (rol 1), excluyendo al generador del evento.** Query `UsuarioRolRepository::findActivosByRolId(1, $excluirUsuarioId)`. Deuda `NOTIF-ROL-GRANULARIDAD` (por área/equipo) a Fase 7.
- **El `cliente_id` del portal se toma de la sesión, NUNCA del body** (BOLA/IDOR, OWASP API Security Top 10 #1). Marcar una notificación de otro cliente devuelve **404** (no 403, para no filtrar existencia).
- **Al añadir un evento nuevo:** constante `EVENTO_*` en `NotificacionService`, método de conveniencia, integración en el service de negocio (dentro de la transacción), y actualizar la tabla de eventos de `ARQUITECTURA.md` §Notificaciones. El endpoint `api_notificaciones.php` / `api_portal_notificaciones.php` **no cambia** (ya lista todo).
- **Endpoints:** `api_notificaciones.php` (staff) usa `requireStaffAuth`, NO `requirePageAccess`/`requireOperation` (la campana vive en el header, no en una página del sidebar). `api_portal_notificaciones.php` (cliente) usa `requireClientAuth`.

**Sobre portal de cliente — facturas, pagos, servicios (A2-B.4, 2026-11-01).**

- **5 endpoints separados de los admin** (`api_portal_servicios.php`, `api_portal_facturas.php`, `api_portal_pagos.php`, `api_portal_metodos_pago.php`, `api_portal_comprobante.php`). Reutilizar los admin (`api_facturas.php`, `api_pagos.php`, `api_servicios.php`) requeriría un `cliente_id` en la query que el cliente podría cambiar → BOLA/IDOR. El portal SIEMPRE filtra por `cliente_id` de la sesión. Patrón Splynx (`/customer/api/*`), Sonar (`/portal/*`), SmartOLT (auto-login portal), Stripe (customer portal).
- **`cliente_id` de la sesión, nunca del body.** `findByIdAndClienteId($id, $clienteId)` devuelve `null` si el recurso no pertenece al cliente → 404 (no 403, para no filtrar existencia). OWASP API Security Top 10 #1.
- **Upload de comprobante:** `finfo_file()` sobre `tmp_name` (no `$_FILES['type']`), whitelist JPG/PNG/WEBP/PDF, **SVG prohibido** (CWE-79 XSS), `bin2hex(random_bytes(16))` (CSPRNG), 2 MB máximo, `.htaccess` con `Require all denied` en `public/uploads/pagos/`, cleanup del archivo si la transacción falla. OWASP File Upload Cheat Sheet.
- **`total_pagado` calculado en batch** con `FacturaRepository::sumValidadosByFacturaIds(array $ids)` (1 query, no N+1). Cierra `REPORTES-N+1` en `ReporteService::calcularKPIs`. OWASP ASVS 5.0 §V1.4.3 / §V11.1.4 prohíben queries dentro de loops (CWE-400).
- **`saldo = max(0, monto - total_pagado)`.** Nunca negativo. Patrón Stripe `invoice.amount_remaining`.
- **`must_change_password` enforced en `Auth::requireClientAuth` sin bypass.** Los 5 endpoints del portal rechazan con 403 si el cliente tiene contraseña temporal. Bypass solo en `api_portal_auth_state.php` y `api_portal_cambiar_password.php`. OWASP ASVS V2.4.
- **`POST api_portal_pagos.php` fuerza `origen='portal_cliente'`, `estado='pendiente'`, `cliente_id` de la sesión.** Ignora esos campos del body si vienen. Dispara `eventoPagoSubido` (A2-B.1) dentro de la transacción.
- **`api_portal_comprobante.php` valida path traversal.** Comprueba que el path real esté dentro de `public/uploads/pagos/` con `realpath()` + `strpos() === 0`. Sirve con `Content-Type` real y `Content-Disposition: attachment`. Patrón Stripe.
- **`verify_a2b4.php` no está en el dump** (deuda `A2-B.4-TEST`). La verificación de A2-B.4 se ejecutó ad-hoc (documentada en `VERIFICACION.md` §2.43–§2.45). Crear el archivo formal en Fase 5.

**Sobre portal de cliente — tickets, respuestas (A2-B.5, 2026-11-01).**

- **2 endpoints separados de los admin** (`api_portal_tickets.php`, `api_portal_ticket_respuestas.php`). Reutilizar `api_tickets.php` / `api_ticket_respuestas.php` requeriría un `cliente_id` en la query que el cliente podría cambiar → BOLA/IDOR. Patrón Splynx (`/customer/api/tickets/*`), Sonar (`/portal/tickets/*`), Zendesk (Help Center vs Agent), Jira SM (Customer Portal vs Agent).
- **`cliente_id` de la sesión, nunca del body.** `TicketRepository::findByIdAndClienteId($id, $clienteId)` devuelve `null` si el ticket no pertenece al cliente → 404. `POST api_portal_tickets.php` valida ownership de `servicio_id` (fix A2-B.5-5). `POST api_portal_ticket_respuestas.php` valida ownership del ticket antes de insertar.
- **Discriminador `tipo_autor` forzado en el service, nunca desde el body.** `TicketRespuestaService::crearRespuestaCliente($idTicket, $idCredencial, $mensaje)` hardcodea `tipo_autor='cliente'`, `credencial_id=$idCredencial`, `usuario_id=NULL`, `adjunto_path=NULL`. El CHECK `chk_ticket_respuestas_discriminador` garantiza la exclusividad. Patrón Zendesk `public: true/false`, Salesforce `CaseComment.IsPublished`, ServiceNow `work_notes` vs `comments`.
- **`usuario_creador_id=NULL` en tickets del portal.** El creador es el cliente (`cliente_id`), no un staff. `TicketService::crear` acepta `?int $usuarioId = null` (fix A2-B.5-1). El caller staff (`TicketController::store`) sigue pasando `(int) $staffSession['id_usuario']`. Sin regresión.
- **Estado `Cerrado` es terminal para el cliente.** `PortalTicketService::responder()` lanza `DomainException` 422. Reabrir = crear ticket nuevo. Patrón Sonar ("closed ticket cannot be reopened; create new"). Deuda `PORTAL-TICKET-REABRIR` (Fase 7, si se añade `Resuelto` al ENUM).
- **Notificación al staff cuando el cliente responde.** `eventoTicketRespondidoPorCliente` (`ticket_respondido_por_cliente`) → admins activos (rol 1). NO notifica al cliente (ya sabe que respondió). NO cambia el estado del ticket. Patrón Zendesk/Jira SM/Salesforce/ServiceNow/Splynx. Deuda `NOTIF-TICKET-ASIGNADO` (notificar al técnico asignado) → Fase 7.
- **URLs de notificación cliente vs staff.** Cliente → `/portal/tickets?id=N`. Staff → `/tickets?id=N`. Patrón Zendesk (`/hc/requests/{id}` vs `/agent/tickets/{id}`), Salesforce (Experience Cloud vs consola interna), ServiceNow (`/sp` vs `/nav_to.do`), Splynx (`/portal/` vs `/admin/`).
- **Sin adjuntos en A2-B.5.** `ticket_respuestas.adjunto_path` existe en el schema, pero el upload no se implementa hasta Fase 7 (`PORTAL-TICKET-ADJUNTOS`). Reusar patrón A2-B.4.
- **Sin tareas derivadas (086f absorbida por 086e).** Decisión de industria: el comentario del cliente es nota interna en el hilo, no tarea paralela. Patrón Jira SM ("Customer comment creates internal note"). Si se necesita field service, va por `pedidos`, no por `tareas`.
- **`must_change_password` enforced en `Auth::requireClientAuth` sin bypass.** Los 2 endpoints del portal rechazan con 403 si el cliente tiene contraseña temporal. OWASP ASVS V2.4.
- **`verify_a2b5.php` (30 checks) verificado 2026-11-01.** Cubre crear, listar, detalle, responder, notificaciones, CHECK discriminador, integridad. Test formal completo.

**Sobre mapa (B3.13).**

- **`window.mapaFocus` acepta `{lat, lon, label, markerId}`.** Si el caller pasa `markerId`, el mapa centra en las coordenadas REALES del marcador (importante para OLTs, que viven en offset). Se consume una sola vez.
- **Pane ordering en Leaflet:** clientes encima de la red (nodos/OLTs/NAPs), OLTs/NAPs debajo.
- **Las OLTs no tienen lat/lon en el esquema.** El mapa las posiciona en un círculo de radio 0.0012° alrededor de su nodo.

**Sobre helpers y main.js.**

- **`helpers.js` en `public/js/core/`** expone las 6 utilidades globales. Cargado en `index.html` después de `Pagination.js` y antes de `AuthController.js`.
- **`main.js` en `public/js/main.js`** es el entry point. No vive en `core/`. No confundir con `helpers.js`.

---

## Trampas conocidas del dominio

- `servicios` es 1:N con `clientes`. No asumir 1:1.
- `naps`/`olts`/`nodos` en plural (nombres de tabla); columnas FK siguen ADR 016: `nap_id`, `olt_id`, `nodo_id`.
- **`contratos.estado` (legal) y `servicios.estado_comercial` (operativo) son ejes distintos.** Regla de negocio en `ContratoService::actualizar`:
  - `vigente → rescindido` → retira el servicio (`estado_comercial='retirado'`).
  - `vigente → vencido` → **NO** retira el servicio. Transición `vencido → vigente` permitida (renovación tácita).
  - `rescindido` es **terminal**. La UI lo bloquea. El backend todavía permite la transición (`F2.6bis`).
  - Reactivar servicio = `retirado → activo` + crear contrato `vigente` nuevo.
- Transiciones de estado validadas en `transicion_estado` (BEFORE UPDATE e INSERT). Endpoint de lectura: `api_transiciones_estado.php` (A2.8), gate `empresa` (módulo 4, Admin-only por guard SoD).
- RBAC: dos niveles (visibilidad `rol_modulo_pagina` + operación `rol_modulo_permiso`). Middleware en `src/core/Auth.php`.
- **Guard SoD del módulo Configuración vive en `Auth::requirePageAccess()`,** no en `requireOperation()`. Rechaza con 403 si la página pertenece al módulo 4 (Configuración) y el usuario no tiene rol 1 (Administrador). Aplica también a `auditoria` y `empresa`.
- **`Response::fromException` es el único punto de mapeo de excepciones a HTTP.**
  - `ValidationException` → 422 con `errors[]`.
  - `AppException` → su código (`NotFoundException` 404, `ConflictException` 409, `DomainException` 422, `AuthorizationException` 403, `AuthenticationException` 401).
  - `PDOException 1062` (UNIQUE) → 409.
  - `PDOException 1452` (FK) → 409.
  - `PDOException 1451` (FK en DELETE) → 409.
  - `PDOException 1644` (SIGNAL de trigger) → 422 con el mensaje del trigger.
  - `PDOException 4025` (CHECK) → 422 con el mensaje del CHECK (fix B4).
  - Resto → 500 genérico.
  Los controllers **no atrapan excepciones de dominio**. Solo el thin file hace `try/catch → Response::fromException`. **Excepción documentada:** los controllers de clientes, usuarios, empresas y contratos (en `store`/`update`) usan `catch` explícito por compatibilidad con el frontend viejo. Se migrarán al patrón general al cerrar ADR 016 completo (Fase 2).
- **La PK en `update()` es legacy; la FK es nueva.** Los controllers leen `$input['id_cliente']`, `$input['id_servicio']`, `$input['id_contrato']`, etc. Las FKs del body son `cliente_id`, `servicio_id`, etc. **Coexisten dos convenciones.** No se unifican hasta el cierre de ADR 016 completo (Fase 2).
- **`phpMyAdmin` no exporta CHECK constraints en el dump SQL.** Solo los definidos inline. **Para verificar CHECKs:** `information_schema.CHECK_CONSTRAINTS` o `mysqldump` desde CLI. Ver `VERIFICACION.md` §2.6.
- **`php -S` con router script.** El router (`public/router.php`) usa `return false;` en el fallback para delegar a `php -S` y que sirva el archivo (ejecutándolo si es PHP). Sin el `return false;`, el router intentaba `readfile()` con concatenación sin slash y devolvía 404. Ver ADR 018.
- **`/cliente`, `/portal`, `/pagos`, `/pago` → `portal_cliente.php`** y **`/admin`, `/` → `index.html`** solo funcionan si el router script se pasa a `php -S` como último argumento. Ver §Comandos.
- **Soft delete ≠ hard delete.** `ClienteService::desactivar` marca `estado='inactivo'`; `UsuarioService::desactivar` marca `estado='Inactivo'`. Los endpoints de listado DEBEN filtrar.
- **`factura_lineas` no se crea con la factura.** `facturas.monto` es el total. `factura_lineas` es el desglose opcional. POST `api_facturas.php` solo inserta en `facturas`. Las facturas creadas desde la UI nacen con `total_lineas=0`. El botón "Ver líneas" solo aparece si `total_lineas > 0`. **"Sin líneas" no es bug, es el estado real del esquema.** El gap va a `F2.30`.
- **`plan_precio` es el precio del plan, no el monto de la factura.** El plan tiene `precio_mensual` fijo. La factura puede tener un monto mayor (reparación, visita, ajuste) o diferente (descuento). `_montoTouched` preserva la edición manual.
- **La URL canónica de dev es `php -S localhost:8000 -t public public/router.php`.** Apache con `http://localhost/redytelca/public/` es el entorno real del usuario. Ambos coexisten válidamente (F2.0b, `DOC-12` cerrado 2026-10-31).
- **La cédula es el identificador natural del cliente.** Toda tabla que liste clientes debe mostrar la cédula. El repo expone `+c.cedula AS cedula`; el View pinta columna "Cédula" y el select incluye `(C.I. V-XXX)`. Sin ella, dos clientes con el mismo nombre son indistinguibles.
- **Modelo de moneda dual (facturación ISP venezolano).** El catálogo está en USD. La factura se emite en USD por defecto (`moneda='USD'`, `tasa_cambio=NULL`). El pago se registra en la moneda que el cliente elija: USD (`tasa_cambio=NULL`) o VES (`moneda_pago='VES'`, `tasa_cambio` = BCV del día del pago). La reconciliación es en USD. **Cada pago va a su tasa del día, no a la tasa de la factura.** Comparar la tasa del pago con la de la factura es un error de modelo. La factura en VES con tasa congelada es un caso opt-in (gobierno, empresas con contabilidad en Bs.); la diferencia de Bs. es FX gain/loss (`F2.32-fx-gain-loss`, opcional). El catálogo `tasas_bcv` (`F2.31`, obligatorio) evita tasas absurdas. Detalle y citas: `REFERENCIA.md` §6.
- **`tareas` del esquema está en el mínimo funcional, no en el estándar de la industria.** Tiene título, descripción, estado, prioridad, cliente, ticket, servicio, creador. NO tiene assignee, fechas de ciclo de vida, `tipo`, checklist/subtareas, worklogs, comentarios ni adjuntos. Va a `F2.33` (Fase 2).
- **`pedidos` del esquema está en el mínimo funcional, no en el estándar.** Tiene cliente, servicio, tipo, estado, prioridad, descripción, creador, técnico asignado, fechas. NO tiene `numero_pedido` (correlativo de negocio), relación N:M con `tickets`, validación de equipo reutilizado en materiales, ni activación automática del servicio al completar. Va a `F2.34`–`F2.38` (Fase 2).
- **Modales que se abren desde otra página deben vivir a nivel de `main`.** Los modales globales (`#servicio-modal`, `#edit-user-modal`, `#password-modal`, `#nap-detail-modal`, `#task-modal`, `#pedido-modal`, `#asignar-tecnico-modal`, `#materiales-modal`, `#historial-asignaciones-modal`, `#ubicacion-modal`, `#equipo-modal`, `#user-roles-modal`) viven todos al nivel de `main`. Origen: BUG-B3.9-MODAL-DISPLAY-NONE.
- **La tabla debe exponer las entidades que el modal permite elegir.** Si el modal de Tareas permite seleccionar `#task-servicio`, la tabla debe mostrar la columna Servicio. Origen: BUG-B3.9-SERVICIO-NO-EXPUESTO.
- **El rol "Técnico" (id 23) existe en la BD desde 2026-10-31.** Creado por el usuario con descripción "Personal de campo. Cierra pedidos y asigna equipos al servicio." `F2.35` sigue abierta: falta asignar permisos al rol y asignar usuarios al rol.
- **Roles en modal dedicado, NO en Editar usuario.** Fase 4.2-hotfix1 (2026-10-31). `#edit-user-modal` solo tiene identidad (username + email). Los roles se editan desde `#user-roles-modal` (botón "Roles" en el toolbar). `UsuarioController::update` no procesa roles aunque el body los traiga.
- **La matriz de permisos solo ofrece los permisos aplicables por página.** `PermisoService::PERMISOS_POR_PAGINA` (Fase 4.3). `dash`/`mapa`/`reportes`/`auditoria` solo `[leer]`. `pagos` incluye `validar`. `password`/`empresa`/`notificaciones` solo `[leer, editar]`. Fallback a `[leer, crear, editar, eliminar]` para páginas no listadas.
- **Notificaciones: `leida_en` es la fuente de verdad de "leída"**, no el enum `estado`. El enum se mantiene sincronizado en el mismo UPDATE. Deuda `NOTIF-ESTADO-LEGACY` a Fase 7.
- **Notificaciones: `notificar()` corre dentro de la transacción del cambio principal.** Rollback si falla. Transactional Outbox Pattern (AWS Prescriptive Guidance). Deuda `NOTIF-IDEMPOTENCIA` a Fase 7.
- **Notificaciones: destinatarios staff = todos los admins activos (rol 1), excluyendo al generador del evento.** Deuda `NOTIF-ROL-GRANULARIDAD` a Fase 7.
- **`api_portal_notificaciones.php` toma `cliente_id` de la sesión, NUNCA del body.** Marcar una notificación de otro cliente devuelve **404** (no 403, para no filtrar existencia). BOLA/IDOR.
- **Portal facturas/pagos/servicios: 5 endpoints separados de los admin.** Reutilizar los admin (`api_facturas.php`, `api_pagos.php`, `api_servicios.php`) requeriría un `cliente_id` en la query que el cliente podría cambiar → BOLA/IDOR. Patrón Splynx `/customer/api/*`, Sonar `/portal/*`, SmartOLT auto-login, Stripe customer portal. A2-B.4.
- **Portal upload de comprobante: `finfo_file()` sobre `tmp_name` (no `$_FILES['type']`), whitelist JPG/PNG/WEBP/PDF, SVG prohibido (XSS), `bin2hex(random_bytes(16))` (CSPRNG), 2 MB máximo, `.htaccess` con `Require all denied` en `public/uploads/pagos/`, cleanup del archivo si la transacción falla.** OWASP File Upload Cheat Sheet. A2-B.4.
- **Portal `total_pagado`: calculado en batch con `sumValidadosByFacturaIds`.** 1 query, no N+1. Cierra `REPORTES-N+1`. OWASP ASVS 5.0 §V1.4.3 / §V11.1.4 (CWE-400). A2-B.4.
- **Portal `saldo`: `max(0, monto - total_pagado)`.** Nunca negativo. Patrón Stripe `invoice.amount_remaining`. A2-B.4.
- **Portal `cliente_id`: de la sesión, nunca del body.** `findByIdAndClienteId($id, $clienteId)` → `null` si no pertenece → 404 (no 403). BOLA/IDOR. A2-B.4.
- **Portal `must_change_password`: enforced en `Auth::requireClientAuth` sin bypass.** Los 5 endpoints del portal rechazan con 403 si el cliente tiene contraseña temporal. Bypass solo en `api_portal_auth_state.php` y `api_portal_cambiar_password.php`. A2-B.4.
- **`api_portal_comprobante.php` valida path traversal con `realpath()` + `strpos() === 0`.** Solo sirve archivos dentro de `public/uploads/pagos/`. `Content-Disposition: attachment`. Patrón Stripe. A2-B.4.
- **Portal tickets: 2 endpoints separados de los admin.** `api_portal_tickets.php`, `api_portal_ticket_respuestas.php`. Reutilizar `api_tickets.php` / `api_ticket_respuestas.php` requeriría un `cliente_id` en la query que el cliente podría cambiar → BOLA/IDOR. Patrón Splynx `/customer/api/tickets/*`, Sonar `/portal/tickets/*`, Zendesk Help Center vs Agent, Jira SM Customer Portal vs Agent. A2-B.5.
- **Portal tickets: discriminador `tipo_autor` forzado en el service, nunca desde el body.** `TicketRespuestaService::crearRespuestaCliente` hardcodea `tipo_autor='cliente'`, `credencial_id` de la sesión, `usuario_id=NULL`, `adjunto_path=NULL`. El CHECK `chk_ticket_respuestas_discriminador` valida. Patrón Zendesk `public: true/false`, Salesforce `CaseComment.IsPublished`, ServiceNow `work_notes` vs `comments`. A2-B.5.
- **Portal tickets: `usuario_creador_id=NULL`.** El creador es el cliente (`cliente_id`), no un staff. `TicketService::crear` acepta `?int $usuarioId = null` (fix A2-B.5-1). Caller staff sigue pasando `(int) $staffSession['id_usuario']`. Sin regresión. A2-B.5.
- **Portal tickets: `Cerrado` es terminal para el cliente.** `PortalTicketService::responder()` lanza `DomainException` 422. Reabrir = crear ticket nuevo. Patrón Sonar. A2-B.5.
- **Portal tickets: notificar al staff cuando el cliente responde.** `eventoTicketRespondidoPorCliente` (`ticket_respondido_por_cliente`) → admins activos (rol 1). NO notifica al cliente. NO cambia el estado del ticket. Patrón Zendesk/Jira SM/Salesforce/ServiceNow/Splynx. A2-B.5.
- **URLs de notificación cliente vs staff.** Cliente → `/portal/tickets?id=N`, `/portal/pagos?id=N`, `/portal/facturas?id=N`. Staff → `/tickets?id=N`, `/pagos?id=N`, `/facturas?id=N`. Patrón Zendesk/Salesforce/ServiceNow/Splynx. A2-B.5.
- **Sin tareas derivadas en respuestas del cliente.** Jira SM convierte la respuesta en nota interna, no en tarea paralela. Tarea paralela duplica fuente de verdad. Si se necesita field service → `pedidos`, no `tareas`. A2-B.5.
- **Ver `docs/constitution.md` y el plan de la fase activa antes de tocar código.**

---

## Límites duros del repo

- **Siempre:** buscar el nombre exacto; replicar patrones; explicar el porqué. Verificar el estado del código con `curl`, `php -l`, `node --check`, `mysql -e`, `findstr` antes de proponer un cambio.
- **Preguntar antes de:** tocar >1 archivo, agregar dependencias nuevas, cambiar el esquema, crear archivos nuevos.
- **Nunca:** reestructurar la BD sin pedido; `$_SESSION` nativo; contraseñas en texto plano; inventar nombres; modificar un doc sin leerlo del dump en el turno; usar la memoria como fuente sobre el contenido de un archivo.
- **No mezclar capas en una sesión.** Backend y frontend van en sesiones separadas.
- **No asumir el contrato del backend.** Verificar con `curl` antes de escribir el frontend.
- **Nunca diferir un bug introducido o expuesto por el fix actual.**
- **Encoding UTF-8 en SQL desde `cmd`:** los acentos van por archivo `.sql`, no por `mysql -e "..."`. Para `mysqldump`, usar `--default-character-set=utf8mb4`.
- **`<?php` byte 0.**
- **Siempre archivo completo, nunca fragmento.**
- **Replicar el fix en todos los módulos afectados en el mismo turno.**
- **Solo `MEMORY.md` se reduce. Los docs de disco duro solo se amplían.**
- **`Router.showPage` debe cerrar modales abiertos.**
- **`Router.showPage` y `AuthController._resetToDashboard` deben resetear el scroll.**
- **Los estados terminales se deshabilitan en la UI.**
- **Los modales que se abren desde otra página viven a nivel de `main`, fuera de cualquier `.page`.**
- **La tabla de un módulo debe exponer las entidades que el modal permite elegir.**
- **F2.0a (auditoría de usabilidad) se ejecuta al inicio de Fase 2, después de Fase 5.**
- **`app.js` fue eliminado en B5.2.a (2026-10-30).** No se reintroduce.
- **No se usan `git tag` para rollback.** Backup manual + renombrar archivos a `.bak`. (`DOC-TAGS-INEXISTENTES`, cerrado F2.0b 2026-10-31.)
- **El último admin activo no puede perder el rol Administrador.** Backend 422. Fase 4.2 (2026-10-31).
- **Roles en modal dedicado, NO en Editar usuario.** Fase 4.2-hotfix1 (2026-10-31).
- **Matriz de permisos en tabla grid, no cards.** Filas=páginas, columnas=Ver/Leer/Crear/Editar/Eliminar/Validar. Solo permisos aplicables. Fase 4.3 (2026-10-31).
- **Notificaciones: `leida_en` es la fuente de verdad; `estado` se mantiene sincronizado.** Deuda `NOTIF-ESTADO-LEGACY` a Fase 7.
- **Notificaciones: `notificar()` nunca fuera de la transacción del cambio principal.** Rollback si falla.
- **Portal: endpoints separados de los admin.** No reutilizar `api_facturas.php`/`api_pagos.php`/`api_servicios.php`/`api_tickets.php`/`api_ticket_respuestas.php` para el portal. BOLA/IDOR. A2-B.4 y A2-B.5.
- **Portal: SVG prohibido en comprobantes.** CWE-79 (XSS). A2-B.4.
- **Portal: `.htaccess` en `public/uploads/pagos/`.** Acceso directo → 403. Solo vía `api_portal_comprobante.php`. A2-B.4.
- **Portal: `cliente_id` de la sesión, nunca del body.** BOLA/IDOR. A2-B.4 y A2-B.5.
- **Portal tickets: discriminador `tipo_autor` forzado en el service.** No desde el body. A2-B.5.
- **Portal tickets: `Cerrado` es terminal para el cliente.** `DomainException` 422. A2-B.5.
- **Portal tickets: `usuario_creador_id=NULL`.** `TicketService::crear` acepta `?int $usuarioId = null`. A2-B.5.
- **Portal tickets: sin tareas derivadas.** Si se necesita field service → `pedidos`. A2-B.5.

---

## Verificación

- **Verificación CLI + browser.** Backend → CLI (`curl -i -s`, `mysql -e`, `php -l`, `node --check`). Frontend → browser (DevTools, consola limpia, Network con payload post-ADR 016, sin `undefined`). Cada módulo declara qué verifica por CLI y qué por browser.
- **Formato de verificación obligatorio:** pasos numerados + comando + Esperado + precondición de abort. El humano ejecuta, pega salidas literales, el agente audita. El agente no ejecuta. Ver `METODOLOGIA_DESARROLLO_IA.md` §5.5.
- **Harness casero:** `src/tests/run.php` ejecuta `src/tests/smoke.test.php` y `src/tests/audit_context.test.php`. Ambos usan `src/core/Bootstrap.php` + `src/core/Auth.php`. Tests por tanda: `verify_a2b1.php` (14 checks), `verify_a2b3.php` (37 checks), `verify_a2b5.php` (30 checks). `verify_a2b4.php` **no existe** (deuda `A2-B.4-TEST`).
- **Sin CI/CD.** Sin tests de endpoints ni de frontend. Las specs se verifican con curl + SQL + browser.
- **Después de cada cambio:** `php -l` sobre todos los archivos tocados, `php src/tests/run.php`, y la verificación curl+SQL+browser específica de la spec.
- **Baseline verde por módulo, no global.**
- **10 checks comunes + 3 transversales por módulo.**
- **Pruebas aisladas de backend:** cerrar todas las pestañas antes.
- **`Ctrl+Shift+R` + Disable cache antes de re-testear.**
- Ver `VERIFICACION.md` para lecciones y checklists.

---

## Memoria

- **Al empezar una sesión:** leer `MEMORY.md`.
- **Al terminar una fase:** actualizar `MEMORY.md` (estado, decisiones, aprendizajes) y `ROADMAP.md` (spec cerrada).
- **Si algo se vuelve regla permanente del repo:** moverlo a este archivo o a `constitution.md`.
- **Si algo se vuelve regla del proceso:** moverlo a `METODOLOGIA_DESARROLLO_IA.md`.
- **Si algo se vuelve lección de verificación:** moverlo a `VERIFICACION.md`.
- **Si algo es el origen histórico de una regla vigente:** moverlo a `HISTORICO_FASE1.md` §6.

---

## Hallazgos colaterales (pendientes de reconciliar)

**Ninguno abierto al 2026-11-01 (A2-B.5 cerrada).**

Los 6 findings de F2.0b (`DOC-12`, `DOC-TAGS-INEXISTENTES`, `HISTORICO_FASE1` roto, `bd_redytelca_schema.sql` stale, `debe`/`esperado`/`verificar`, `opencode.json`) quedaron cerrados el 2026-10-31. Los 6 findings de Fase 4 (R1–R6, P1–P2, U1, UX-ROLES-DUP-ROLES, EST-01) cerrados el 2026-10-31. Los 2 fixes de la tanda 2026-10-31 (scroll + matriz) cerrados el 2026-10-31. A2-B.1 cerrada 2026-10-31. A2-B.3 cerrada 2026-10-31. A2-B.4 cerrada 2026-11-01. A2-B.5 cerrada 2026-11-01.

**Deuda de Fase 5:**

- **`A2-B.4-TEST`.** `src/tests/verify_a2b4.php` no está en el dump. La verificación de A2-B.4 se ejecutó ad-hoc (documentada en `VERIFICACION.md` §2.43–§2.45). Crear el archivo formal en Fase 5 (misma estructura que `verify_a2b1.php`, `verify_a2b3.php` y `verify_a2b5.php`, ~25 checks).

**Deudas menores pendientes (no bloquean Fase 5):**

1. **Seed de `configuracion_empresa` con paths reales.** `logo_path='uploads/empresa/logo_login.png'`, `logo_path_sidebar='uploads/empresa/logo_sidebar.svg'`. Funcional en el entorno actual. En una instalación limpia, `branding-logo.php` cae al fallback hasta que se suban logos. Deuda menor.
2. **`HISTORICO_FASE1.md` reconstruido parcial.** Detalle de bugs B3.0–B3.13 perdido. Se recupera si aparece backup pre-B3.14.
3. **EST-01 sin descripción recuperable.** Cerrada por 4.3 con hipótesis de familia. Si era otra cosa, pérdida documental definitiva.
4. **`BD-REF-VERIFICACION-37-39`.** `docs/BD.md` §Referencias cita `docs/VERIFICACION.md` §2.37–§2.39. Esas 3 lecciones no existen en el dump de `VERIFICACION.md` (hay gap entre §2.36 y §2.40; §2.40–§2.45 sí existen). Referencias rotas. Deuda detectada 2026-10-31.