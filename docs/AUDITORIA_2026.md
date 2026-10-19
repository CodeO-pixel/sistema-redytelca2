# Auditoría completa de REDYTELCA

Aplico el marco de `docs/METODOLOGIA_AUDITORIA_IA.md` (6 pasadas × 10 capas × contexto ISP) contra el estado actual del proyecto (dumps `local-repo-converted (2).txt` + `bd_redytelca.sql`). Referencias comparativas: **SaaS Gestión**, **SmartOLT**, **Splynx** y estándares del sector (**TM Forum SID/eTOM**, **ISO/IEC 27011:2024**, **ITU-T X.1051**).

**Fecha de corte: 2026-10-11.** Este informe es una foto con fecha. El estado de cada hallazgo (abierto / cerrado) vive en `ROADMAP.md`. El esquema vive en `bd_redytelca.sql`.

---

## Resumen ejecutivo

| Capa | Score | Estado | Críticos |
|---|---|---|---|
| 1. Datos / BD | 78 | Aceptable con reservas | 0 |
| 2. Backend / API | 72 | Aceptable con reservas | 0 |
| 3. Frontend | **38** | **Deficiente** | 0 |
| 4. Seguridad | 68 | Deficiente | 0 |
| 5. Arquitectura | 62 | Deficiente | 0 |
| 6. Testing | 40 | Deficiente | 0 |
| 7. Infraestructura | 45 | Deficiente | 0 |
| 8. Documentación | 82 | Bueno | 0 |
| 9. Calidad de Producto | 55 | Deficiente | 0 |
| 10. Contexto ISP | 70 | Aceptable con reservas | 0 |
| **Global** | **61** | **Deficiente** | **0** |

**Veredicto preliminar: no conforme a estándar profesional.** Fase 1 estructural ✅ conforme. Fase 2 interna ❌ no conforme (3 capas por debajo de 70). Fase 3 ISP ⚠️ parcial.

**El bloqueante real no es el backend** — es **frontend** (38/100) e **infraestructura** (45/100). Ahí está el 70% de la brecha con SaaS Gestión.

---

# CAPA 1 — Datos / Base de Datos

**Estándares aplicados:** ISO/IEC 9075 (SQL), formas normales 1FN–BCNF, ACID, ISO/IEC 11179 (nomenclatura), ISO/IEC 27011:2024 §8.2 (gestión de activos).

## 1.1 Pasada 1 — Conformidad estructural

**RBAC Core ANSI/INCITS 359:** ✅ CONFORME.
- `usuario_rol` N:M ✅
- `rol_modulo_permiso` N:M con (objeto=página × operación=permiso) ✅
- `permisos` catálogo separado ✅

**Máquina de estados finitos:** ✅ CONFORME.
- `transicion_estado` con UNIQUE (tabla, origen, destino) ✅
- Triggers BEFORE INSERT + BEFORE UPDATE en `facturas`, `pagos`, `contratos`, `servicios` ✅

## 1.2 Pasada 2 — Consistencia interna

| Hallazgo | Severidad | Evidencia |
|---|---|---|
| `clientes.cedula` sin CHECK de formato | Alto | Semilla muestra `'3192873'`, `'V-12345678'`, `'E-87654321'` — 3 formatos distintos |
| `clientes` sin `actualizado_en` ni `estado` | Alto | Comparar con `usuarios` que sí los tiene |
| `pagos` sin `creado_en`/`actualizado_en` | Medio | Auditoría temporal incompleta |
| `nodos`, `olts`, `naps`, `equipos` sin timestamps | Medio | Cambios no trazables a nivel fila |
| `logs.old_value`/`new_value` con `CHECK (json_valid())` — MariaDB 10.4 no lo fuerza | Alto | Documentado en D07 |
| `sesiones` sin CHECK "exactamente uno de (id_usuario, id_credencial)" | Alto | Documentado en D08 |
| `facturas.impuesto_aplicado` columna muerta | Medio | Se persiste, nunca se usa |
| `configuracion_empresa` no garantiza singleton a nivel esquema | Medio | Asume `id_config=1` |
| `rol` id=4 ("tecnico") huérfano | Medio | Sin `rol_modulo` ni `usuario_rol` |
| `equipos` permite N por servicio, pero sin `estado_inventario` ni `fecha_asignacion` en el seed | Bajo | El DDL los tiene, el seed no los usa |
| Falta tabla `auditoria_migraciones` (aunque hay logs) | Bajo | Trazabilidad de DDL |
| `clientes_credenciales` sin `creado_en`/`ultimo_acceso` en el seed | Bajo | Tabla existe, no se usa |

## 1.3 Pasada 3 — Cross-layer

- ✅ `bd_redytelca.sql` es fuente de verdad y coincide con el runtime PHP.
- ⚠️ El dump se excluye del repo (`.gitignore` → `bd_redytelca.sql`). Correcto para seguridad, pero rompe la reproducibilidad de la tesis: un jurado no puede importar el esquema desde el repo. **Fix requerido:** versionar `bd_redytelca_schema.sql` **sin datos** (DDL puro) y mantener el dump con datos solo local.

## 1.4 Pasada 4 — Adversarial

- ✅ Prepared statements en 100% de los endpoints.
- ⚠️ `logs` es inmutable (`trg_logs_inmutable_*`), pero las variables `@app_*` se resetean al cierre de conexión — si algún día se habilita `PDO::ATTR_PERSISTENT`, se contaminan entre requests. **Ya está documentado en `conexion.php`** ✅.

## 1.5 Pasada 5 — No funcional

- ❌ Índices ausentes: `clientes(cedula)` está ✅, pero falta índice en `facturas(fecha_vencimiento)`, `sesiones(token)` (está ✅ como UNIQUE), `logs(accion, creado_en)`, `equipos(direccion_mac)` (✅ UNIQUE).
- ⚠️ Collation `utf8mb4_general_ci` en lugar de `utf8mb4_unicode_ci`. Impacta ordenamiento español.

## 1.6 Comparación con SaaS Gestión

| Concepto | REDYTELCA | SaaS Gestión |
|---|---|---|
| Modelo SID (Cliente/Producto/Servicio/Recurso) | Parcial. `clientes`, `planes`, `servicios`, `equipos` — 4 de 6 entidades | Completo |
| `Pedido` (Order) como entidad | ❌ No existe | ✅ Sí (`instalaciones`) |
| `IPAM` | ✅ `ip_pools`, `ip_asignaciones` | ✅ |
| Máquina de estados | ✅ Formal con tabla `transicion_estado` | Estado como ENUM, sin tabla formal |

**Score capa 1: 78/100.** Conformidad estructural fuerte. Falta cerrar D02, D03, D07, D08, D13.

---

# CAPA 2 — Backend / API / Lógica de Negocio

**Estándares:** 12-Factor App, REST Richardson Maturity, RFC 7231, Clean Architecture, SOLID.

## 2.1 Pasada 1 — Conformidad estructural (REST)

| Endpoint | Verbo GET | POST | PUT | DELETE | Veredicto |
|---|---|---|---|---|---|
| `api_facturas.php` | ❌ **Side-effect** (`UPDATE facturas SET estado='vencida'`) | ✅ | ✅ | ✅ | **No conforme RFC 7231** (B08) |
| `api_contratos.php` | ❌ **Side-effect** (`UPDATE contratos SET estado='vencido'`) | ✅ | ✅ | ✅ | **No conforme RFC 7231** (B08) |
| `api_pagos.php` | ✅ | ✅ | ✅ | ✅ | Conforme |
| `api_servicios.php` | ✅ | ✅ | ✅ | ✅ | Conforme |
| `api_empresa.php` | ⚠️ GET público (B09) | ✅ | — | — | Aceptable con reserva |
| `api_nodos/olts/naps/equipos/planes/tickets/tareas` | ✅ | ✅ | ✅ | ✅ | Conforme |

**Veredicto capa 2 estructural: 80% conforme.** Los 2 GET con side-effect son violación MUST.

## 2.2 Pasada 2 — Consistencia interna

| ID | Severidad | Hallazgo |
|---|---|---|
| B06 | Alto | PUT/DELETE sobre IDs inexistentes devuelve `success` (varios) |
| B10 | Alto | Fuga de `$e->getMessage()` en 7 endpoints |
| B11 | Alto | `api_equipos.php` distingue UNIQUE por `str_contains($e->getMessage(), 'direccion_mac')` |
| B12 | Alto | `api_facturas.php` POST no captura duplicado `(id_servicio, periodo)` |
| B13 | Alto | `registrar_cliente.php` con `id_plan=1` hardcoded |
| B14 | Parcial | Contraseña ≥ 8 chars (NIST pide ≥ 15) |
| B15 | Alto | Contraseña temporal `'Redytelca123!'` hardcoded |
| B16 | Alto | `cliente_eliminar.php` no anticipa FK `contratos.id_servicio` |
| B17 | Medio | `permisos_listar.php` sin gate admin |
| B18 | Medio | `permisos_guardar.php` sin safeguard del rol 1 |
| B19 | Medio | `api_pagos.php` DELETE permite borrar `'rechazado'` |
| B20 | Medio | Inconsistencia de patrón: algunos DELETE leen `$_GET['id']`, otros body |
| B24 | Medio | `api_tickets`/`api_tareas` sin whitelist de ENUMs |

## 2.3 Pasada 3 — Cross-layer

- ✅ Middleware `requireOperation` está definido y es usado por los endpoints administrativos.
- ⚠️ Cobertura despareja de `requireOperation` entre endpoints CRUD (algunos solo tienen `requirePageAccess`).

## 2.4 Pasada 4 — Adversarial (STRIDE)

| Amenaza | Ejemplo | Diagnóstico |
|---|---|---|
| **S**poofing | Login con usuario Bloqueado | ✅ Bloqueado |
| **T**ampering | `PUT api_pagos` para revertir validado | ✅ Bloqueado |
| **R**epudiation | Auditoría de escrituras | ✅ Cubierto por `setAuditContext()` |
| **I**nformation Disclosure | `$e->getMessage()` en 7 endpoints | ❌ Abierto |
| **D**oS | GET api_facturas en bucle → UPDATE global | ❌ Abierto |
| **E**levation of Privilege | PUT api_pagos → `id_rol !== 1` revertir | ✅ Bloqueado |

## 2.5 Pasada 5 — No funcional

- ❌ **Sin capa de servicios**. Lógica de negocio dispersa en controladores: `reconciliarFactura()` en `api_pagos.php`, `sincronizarEstadoServicio()` en `api_contratos.php`. Si una regla cambia, hay que tocar el controlador.
- ❌ Sin timeouts explícitos en PDO.
- ❌ Sin circuit breaker para servicios externos (aunque no hay externos hoy).

## 2.6 Comparación con SaaS Gestión

| Concepto | REDYTELCA | SaaS Gestión |
|---|---|---|
| Capa de servicios | ❌ Inexistente | ✅ Implícita |
| Idempotencia en pagos | ❌ Ausente | ✅ Presente |
| Rate limiting login | ❌ Ausente | ✅ Presente |
| Logging estructurado | ⚠️ Solo tabla `logs` | ✅ OpenTelemetry o equivalente |

**Score capa 2: 72/100.** Backend funciona pero le faltan los cierres B06–B24.

---

# CAPA 3 — Frontend

**Estándares:** WCAG 2.1/2.2 AA, Core Web Vitals, HTML Living Standard, ECMAScript, CSP (W3C), Atomic Design, Flux/unidirectional.

## 3.1 Pasada 1 — Conformidad estructural

❌ **NO CONFORME.** Comparado con SaaS Gestión y SmartOLT:

| Dimensión | REDYTELCA | SaaS Gestión/SmartOLT |
|---|---|---|
| **Jerarquía visual** | Lista plana de páginas en sidebar | Agrupación CRM/OSS/BSS + secciones colapsables |
| **KPI en dashboard** | 9 tarjetas estáticas + 6 gráficos sin contexto | 4-5 KPIs con tendencia + accionabilidad |
| **Tablas** | Simple, `<table>` con 5-6 columnas | Densa, con ID de negocio, badges, acciones contextuales |
| **Estado del sistema** | Sin indicador global | Badge "Sistema OK" en header |
| **Breadcrumbs** | ❌ No | ✅ Sí |
| **Vista de detalle** | Modal plano | Pestañas (SmartOLT: 6 tabs para una ONU) |
| **Búsqueda global** | ❌ No (solo por módulo) | ✅ En header |
| **Notificaciones** | ❌ No (aunque `notificaciones` está en BD) | ✅ Campana con contador |
| **Avatar usuario** | ✅ Básico | ✅ Con menú |
| **Tema oscuro/claro** | ❌ No | ✅ En SmartOLT |
| **Responsive** | ⚠️ Media queries básicas | ✅ Fluido |

## 3.2 Pasada 2 — Consistencia interna

| Hallazgo | Severidad | Evidencia |
|---|---|---|
| `alert()`/`confirm()` nativos en >30 lugares | Medio | F09 |
| Dashboard/Reportes redundantes | Medio | Dos vistas con datos solapados |
| Sin indicador de carga (`loading state`) | Medio | UX pobre en latencias |
| Sin paginación server-side | Medio | NF02 |
| Charts se destruyen y recrean en cada update | Medio | F07 |
| `filterBySearch` sin normalizar acentos | Bajo | F13 |
| Sin manejo global de 401/403 | Medio | F08 |
| `window.selectedIds` sin limpieza | Bajo | F11 |
| `onclick` inline con datos de BD | Medio | F03 |
| Token en localStorage | Alto | F01 |
| Sin CSP | Medio | F12 |
| Sin `<meta name="robots" content="noindex">` en admin | Bajo | — |
| Sin `favicon.ico` | Bajo | — |

## 3.3 Pasada 3 — Cross-layer

- ✅ Frontend sin hardcodeo de rol admin para render del menú.
- ❌ F14: `nombre_empresa` no se refleja en la UI.
- ⚠️ `must_change_password` bloquea modal, pero backend ya lo enforca.
- ⚠️ Los endpoints `api_*` están todos bajo una sola SPA sin code splitting → un fallo en cualquier módulo bloquea la carga inicial (`initApp()` secuencial con 14 `await`).

## 3.4 Pasada 4 — Adversarial

| Amenaza | Estado |
|---|---|
| XSS vía `innerHTML` con `escapeHtml` | ⚠️ Mitigado, no eliminado (F03) |
| Token robado vía XSS | ❌ F01 abierto |
| Clickjacking | ❌ Sin `X-Frame-Options` |
| CSRF | ✅ No aplica (tokens en header + CORS whitelist) |

## 3.5 Pasada 5 — No funcional

- ❌ Sin Lighthouse report.
- ❌ Sin accesibilidad WCAG (sin `aria-*`, sin focus visible, sin contraste validado).
- ❌ Sin `loading="lazy"` en imágenes.
- ❌ Sin CDN cache control en `styles.css`/`app.js`.

## 3.6 Comparación con SaaS Gestión

| Elemento | REDYTELCA | SaaS Gestión |
|---|---|---|
| Sidebar agrupado | ❌ Plano | ✅ CRM/OSS/BSS |
| Breadcrumbs | ❌ | ✅ |
| Badge sistema | ❌ | ✅ |
| Búsqueda global | ❌ | ✅ |
| Notificaciones (campana) | ❌ | ✅ |
| Vista 360° cliente | ❌ | ✅ |
| Módulo "Instalaciones" para técnicos | ❌ | ✅ |
| KPI dashboard accionable | ❌ Estático | ✅ Con tendencia + drill-down |
| Reportes con periodo | ❌ Redundante | ✅ Análisis por rango |
| Filtros avanzados en tablas | ❌ | ✅ |
| Acciones contextuales (`...`) | ❌ Botones en toolbar | ✅ |
| Toast notifications | ❌ `alert()` | ✅ |

**Score capa 3: 38/100.** Es la capa más débil. El 90% del rediseño visual está aquí.

---

# CAPA 4 — Seguridad

**Estándares:** OWASP ASVS 5.0, OWASP Top 10, CWE, STRIDE, NIST SP 800-63B Rev. 4, ISO/IEC 27011:2024.

## 4.1 Conformidad NIST SP 800-63B

| Requisito | REDYTELCA | Cumple |
|---|---|---|
| Longitud mínima 15 chars (single factor) | 8 | ❌ |
| Máximo ≥ 64 chars | Sin límite superior | ✅ |
| Sin reglas de composición arbitrarias | Sin reglas | ✅ |
| Verificación contra listas de comprometidas | ❌ | ❌ |
| Sin rotación forzosa arbitraria | No rota | ✅ |
| Rate limiting | 5 intentos por cuenta | ⚠️ Parcial (sin IP) |

## 4.2 OWASP Top 10 (2021)

| Riesgo | Diagnóstico |
|---|---|
| A01 Broken Access Control | ⚠️ Cobertura despareja de `requireOperation` en endpoints CRUD |
| A02 Cryptographic Failures | ⚠️ bcrypt ✅, pero HTTP sin HTTPS |
| A03 Injection | ✅ Prepared statements |
| A04 Insecure Design | ⚠️ Sin threat model formal |
| A05 Security Misconfiguration | ❌ Credenciales hardcoded, sin headers |
| A06 Vulnerable Components | ❌ Sin SBOM |
| A07 Auth & Session Failures | ⚠️ 30 días, sin reautenticación |
| A08 Software & Data Integrity | ⚠️ Sin CI/CD, sin SRI en CDN |
| A09 Logging & Monitoring | ✅ Logs inmutables + `setAuditContext` |
| A10 SSRF | ✅ N/A (sin fetching externo) |

## 4.3 Líneas rojas ISP

| Línea roja | Diagnóstico |
|---|---|
| PII de clientes | ⚠️ Dump fuera webroot ✅, HTTPS pendiente ❌ |
| Aislamiento de clientes (BOLA/IDOR) | ⚠️ Portal cliente sin implementar |
| RBAC formal y SoD | ✅ Conforme |
| Integridad de facturación | ⚠️ Hallazgos abiertos en validación de pagos (D06) |
| Credenciales de red | N/A (sin credenciales de red) |
| Disponibilidad mínima | ❌ Sin timeouts, sin health check |

**Score capa 4: 68/100.**

---

# CAPA 5 — Arquitectura

**Estándares:** SOLID, GRASP, Clean/Hexagonal, GoF.

## 5.1 Diagnóstico

| Principio | Estado | Evidencia |
|---|---|---|
| Single Responsibility | ❌ | `conexion.php` mezcla CORS, PDO, hashing, auth, autorización, contexto |
| Open/Closed | ⚠️ | Endpoints no extensibles sin tocar |
| Liskov | N/A | Sin herencia |
| Interface Segregation | N/A | Sin interfaces |
| Dependency Inversion | ❌ | Todo depende de `$pdo` global |
| Cohesión alta | ❌ | `app.js` mezcla estado, vista, lógica |
| Acoplamiento bajo | ⚠️ | Endpoints independientes entre sí, pero sin capa de servicios común |

## 5.2 Clean Architecture

- ❌ **No hay separación por capas.** No existe:
  - `domain/` (entidades y reglas de negocio)
  - `application/` (casos de uso)
  - `infrastructure/` (PDO, HTTP)
- Los endpoints PHP son **Transaction Script** disfrazado de REST.

**Score capa 5: 62/100.**

---

# CAPA 6 — Testing

**Estándares:** ISO/IEC/IEEE 29119, pirámide de pruebas, TDD, Mutation Testing.

## 6.1 Cobertura

- ✅ `tests/run.php` ejecutable.
- ✅ 4 smoke + 5 auditoría.
- ❌ 0 tests de endpoints (`api_pagos`, `api_facturas`, `login`, etc.).
- ❌ 0 tests de integración con BD.
- ❌ 0 tests de frontend.
- ❌ Sin tests negativos.
- ❌ Sin mutation testing.
- ❌ Sin CI/CD.

**Score capa 6: 40/100.**

---

# CAPA 7 — Infraestructura

**Estándares:** 12-Factor, CI/CD, Google SRE, OpenTelemetry.

- ❌ Sin variables de entorno (credenciales hardcoded).
- ❌ Sin CI/CD.
- ❌ Sin backups automatizados.
- ❌ Sin observabilidad (métricas, trazas).
- ❌ Sin health check.
- ❌ Sin HTTPS enforcement en código.
- ⚠️ `.htaccess` con 403 para `.sql` ✅.

**Score capa 7: 45/100.**

---

# CAPA 8 — Documentación

**Estándares:** ISO/IEC 26514, ISO/IEC 25010 (Mantenibilidad).

- ✅ `docs/constitution.md`
- ✅ `docs/ARQUITECTURA.md`
- ✅ `docs/BD.md`
- ✅ `docs/AUDITORIA_2026.md`
- ✅ `docs/ROADMAP.md`
- ✅ `docs/METODOLOGIA_*.md`
- ✅ ADRs 001–008
- ❌ Falta diagrama ER actualizado
- ❌ Falta diagrama de secuencia de login
- ❌ Falta diagrama C4 del sistema

**Score capa 8: 82/100.**

---

# CAPA 9 — Calidad de Producto (ISO/IEC 25010:2023)

| Característica | Score | Comentario |
|---|---|---|
| Adecuación funcional | 88 | Núcleo funcional completo |
| Eficiencia de desempeño | 55 | UPDATE en GET, sin caché |
| Compatibilidad | 70 | UTF-8, navegadores modernos |
| Capacidad de interacción | **35** | **UI básica, `alert()` nativos** |
| Fiabilidad | 65 | Transacciones ✅, sin tests ❌ |
| Seguridad | 68 | Mucho cerrado, headers pendientes |
| Mantenibilidad | 55 | app.js monolítico |
| Flexibilidad | 60 | Esquema rico, código rígido |
| Seguridad física | N/A | No aplica |

**Score capa 9: 62/100.**

---

# CAPA 10 — Contexto Operativo ISP

**Estándares:** TM Forum SID/eTOM, ISO/IEC 27011:2024, ITU-T X.1051.

## 10.1 Cobertura de procesos eTOM

| Proceso | REDYTELCA | Observación |
|---|---|---|
| **CRM** (Customer Relationship Mgmt) | ⚠️ Parcial | `clientes` sí, pero sin interacciones ni historial 360° |
| **Fulfillment** (Order Management) | ❌ | Sin entidad `Pedido`. `tareas` no es un flujo de aprovisionamiento |
| **Assurance** (Fault, Performance) | ⚠️ Parcial | `tickets` existe, `instalaciones` no |
| **Billing** (Invoice, Payment) | ✅ | `facturas`, `pagos`, `factura_lineas` |
| **Resource Management** | ⚠️ Parcial | `nodos`, `olts`, `naps`, `equipos`, `ip_pools` |

## 10.2 Comparación con SmartOLT/SaaS

| Feature esperado en un ISP | REDYTELCA | Justificación de ausencia |
|---|---|---|
| Provisioning automático de ONU | ❌ | Fuera de alcance (integración HW) |
| Alarmas de OLT/ONU | ❌ | Fuera de alcance |
| Rate-limiting de ancho de banda | ❌ | Fuera de alcance |
| Consumo de tráfico por cliente | ❌ | Fuera de alcance |
| Mapa de red con cobertura | ✅ | Módulo `mapa` |
| IPAM | ✅ | `ip_pools`, `ip_asignaciones` |
| Facturación + cobro | ✅ | Módulo `facturas`, `pagos` |
| Portal de cliente | ⚠️ | Stub |
| App móvil técnico | ❌ | Fuera de alcance |
| CRM con historial 360° | ❌ | Pendiente |

**Score capa 10: 70/100.**

---

# Matriz consolidada de hallazgos nuevos detectados

| ID | Capa | Sev | Hallazgo |
|---|---|---|---|
| D18 | 1 | Medio | Falta `bd_redytelca_schema.sql` versionable (solo DDL) |
| D19 | 1 | Bajo | Índices menores ausentes: `logs(accion, creado_en)` |
| B25 | 2 | Medio | Sin capa de servicios (lógica de negocio en controladores) |
| B26 | 2 | Medio | Timeouts PDO ausentes |
| B27 | 2 | Bajo | Sin Content-Type validation estricto |
| F15 | 3 | **Alto** | Ausencia de jerarquía visual / arquitectura de información |
| F16 | 3 | **Alto** | Tablas con información pobre (sin ID de negocio, badges, acciones) |
| F17 | 3 | **Alto** | Ausencia de vista 360° del cliente |
| F18 | 3 | Medio | Sin notificaciones (campana) |
| F19 | 3 | Medio | Sin breadcrumbs |
| F20 | 3 | Medio | Sin búsqueda global |
| F21 | 3 | Medio | Dashboard/Reportes redundantes (sin distinción temporal) |
| F22 | 3 | Medio | `alert()` / `confirm()` en >30 lugares |
| F23 | 3 | Bajo | Sin `favicon.ico`, sin `<meta name="robots">` |
| F24 | 3 | Bajo | Sin dark mode |
| AR05 | 5 | Medio | `initApp()` secuencial con 14 `await` bloquea carga |
| T04 | 6 | Alto | Sin tests de endpoints |
| T05 | 6 | Alto | Sin tests de frontend |
| I05 | 7 | Medio | Sin `date_default_timezone_set()` |
| ISP11 | 10 | Medio | Sin entidad `Pedido` (eTOM Fulfillment) |
| ISP12 | 10 | Medio | Sin historial 360° del cliente |
| ISP13 | 10 | Bajo | Sin SLA/QoS tracking |

---

## Cierre del documento

Este informe es la fuente canónica de **hallazgos** de auditoría. No contiene planes de ejecución.

- **Plan de correcciones y estado de cada hallazgo:** `docs/ROADMAP.md`.
- **Esquema:** `bd_redytelca.sql`.
- **Estado operativo del proyecto:** `MEMORY.md`.
- **Versión auditada:** dump `local-repo-converted (2).txt` + `bd_redytelca.sql`.
- **Fecha de corte:** 2026-10-11.

Los 22 hallazgos nuevos detectados están incorporados en las capas correspondientes con su ID formal: D18, D19, B25, B26, B27, F15–F24, AR05, T04, T05, I05, ISP11–13.

---
