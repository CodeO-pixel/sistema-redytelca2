# Referencia — REDYTELCA

Documento atemporal. Material de consulta que no vive en ningún otro lugar: estándares aplicados, decisiones de industria descartadas, frases de defensa para la tesis, aprendizajes y referencias de mercado.

**No lleva estado.** Qué spec existe y en qué estado vive en `ROADMAP.md`. Dónde vamos vive en `MEMORY.md`. El esquema vive en `bd_redytelca.sql`. Cómo está construido el sistema vive en `ARQUITECTURA.md`.

---

## 1. Estándares aplicados

### 1.1 Software

- **ANSI/INCITS 359** — RBAC Core.
- **RFC 7231** — Semántica HTTP (Safe Methods §4.2.1).
- **ACID** — Transacciones.
- **OWASP ASVS 5.0 / Top 10** — Seguridad.
- **NIST SP 800-53** — AC-5 (SoD) y AC-6 (Mínimo Privilegio).
- **NIST SP 800-63B Rev. 4** — Autenticación.
- **ISO/IEC 25010:2023** — Calidad de producto.
- **ISO/IEC 9075** — SQL Standard.
- **ISO/IEC 11179** — Nomenclatura de metadatos.
- **ISO/IEC/IEEE 29119** — Testing.
- **12-Factor App** — Arquitectura.
- **CWE-650** — GET sin side-effects.
- **CWE-209** — Fuga de información por mensajes de error.

### 1.2 ISP / Telecomunicaciones

- **TM Forum SID** — Information Framework (conceptual).
- **TM Forum eTOM** — Business Process Framework (conceptual).
- **ISO/IEC 27011:2024** — Controles de seguridad para telecom.
- **ITU-T X.1051** — Gestión de seguridad de información en telecom.

### 1.3 UX/UI

- **WCAG 2.1 AA** — Accesibilidad.
- **Core Web Vitals** — LCP, INP, CLS.
- **Atomic Design** — Composición de componentes.
- **Flux / unidirectional data flow** — Gestión de estado.

### 1.4 Referencias de industria

- **Splynx** — servicios como entidad de primer nivel en pestaña dentro del cliente + herramienta "Migrate services" separada.
- **Omnitouch** — tabla global de servicios con columna Customer.
- **PortaOne** — catálogo de Service types and services.
- **Salesforce** — Dashboards: Viewer / Editor / Manager.
- **ServiceNow** — `read` y `report_view` como ACLs separadas.
- **AWS QuickSight** — Reader / Author / Admin.
- **SAP** — DISP / ALL / ADMIN.
- **Microsoft Azure** — RBAC del recurso.
- **Odoo** — Menús y botones ocultos según permisos.
- **Google Cloud Scheduler** — on-demand execution.
- **AWS EventBridge Scheduler** — invocación recurrente o puntual.

**Fundamento del módulo Servicios:** Splynx, Omnitouch y PortaOne tratan el servicio como entidad independiente del cliente. El modelo de datos de REDYTELCA ya lo tenía (1:N desde el ADR 003). Faltaba exponerlo como entidad de primer nivel en la UI.

---

## 2. Decisiones de industria consideradas y no aplicadas

Tecnologías evaluadas conscientemente y descartadas por no encajar con el alcance de una tesis. **No son trabajo futuro planificado.**

| Tecnología | Motivo de descarte |
|---|---|
| **OAuth 2.0 / OIDC** | El sistema de tokens propios cumple. |
| **GraphQL** | REST cubre el caso (92% adopción). |
| **NoSQL** | ACID para datos financieros. |
| **OpenTelemetry** | Auditoría inmutable cubre el negocio. |
| **Feature Flags** | No aplica a tesis. |
| **Idempotency-Key** | Solo si se integra pasarela de pago. |
| **Rate Limiting IETF headers** | Estándar inmaduro. |
| **Microservicios** | Monolito modular es la práctica recomendada. |
| **TMMi / ISTQB** | Modelos para organizaciones grandes. |
| **CI/CD** | Fuera del alcance de tesis. |
| **cron real** | Hosting de tesis sin cron. Se usa on-demand execution. |
| **Integración con red real del ISP** | Fuera de alcance de tesis. Provisioning automático de ONU/ONT, RADIUS, SNMP, TR-069, CLI a OLTs, alarmas, rate-limiting, monitoreo de latencia — todo requiere acceso a hardware real, VLAN de gestión, credenciales rotadas y laboratorio. El inventario se gestiona manualmente vía CRUD. Ver ADR 012. |
| **App móvil técnico** | Fuera de alcance de tesis. Requiere build tools (React Native/Flutter) que violan la constitución §1. El staff usa el panel admin responsive desde navegador móvil. Ver ADR 012. |
| **Pasarela de pagos automatizada** | Fuera de alcance de tesis. El pago manual con comprobante es el estándar en ISPs venezolanos (Vnet, Nevacom, Power Link, WOW, TV Zamora, Movilnet). El sistema financiero venezolano no tiene API unificada de pagos. Ver ADR 012. |
| **Field Service Management** | Fuera de alcance de tesis. Cuadrillas, rutas, agenda de técnicos, órdenes de trabajo, check-in en sitio, firma del cliente. Sistema aparte, no core del panel admin. |
| **Gestión de fibra óptica** | Fuera de alcance. Empalmes, splitters, ductos, pérdida dB, certificación. Requiere hardware y herramientas de campo. |
| **Energía / facilities** | Fuera de alcance. UPS, CCTV, temperatura, generadores en nodos. Requiere sensores. |
| **Facturación fiscal SENIAT** | Fuera de alcance. Formato fiscal, libro de ventas, IGTF, retenciones. Requiere integración con ente fiscal. |
| **Cobranza en campo** | Fuera de alcance. Rutas de cobrador, rendición de cuentas, acuerdos de pago. Sistema aparte. |
| **Comisiones y ventas** | Fuera de alcance. Comisiones, metas, pipeline, leads, cotizaciones. No es core ISP. |
| **RRHH** | Fuera de alcance. Nómina, asistencia, vacaciones, evaluación. No es core ISP. |
| **Marketing / campañas** | Fuera de alcance. Campañas email/SMS masivos, promociones, referidos. No es core ISP. |
| **API pública documentada** | Fuera de alcance. API keys para terceros, webhooks, OAuth2, sandbox, OpenAPI. No requerido para tesis. |
| **Workflows / aprobaciones** | Fuera de alcance. Motor de workflows, reglas sin código, feature flags. Overkill para el alcance. |
| **BI / ML** | Fuera de alcance. Data warehouse, ETL, predicción churn, análisis de cohortes. No es core ISP. |
| **Alta disponibilidad / failover** | Fuera de alcance. Réplica BD, balanceo de carga, APM. Infraestructura, no aplicación. |
| **Inventario avanzado** | Fuera de alcance. Stock mínimo, movimientos de almacén, proveedores, RMA, garantía. Solo el flujo asignación/devolución de equipos va dentro. |
| **Compras y proveedores** | Fuera de alcance. Órdenes de compra, cuentas por pagar, facturas de proveedor. No es core ISP. |
| **ITIL avanzado** | Fuera de alcance. Gestión de problemas, cambios, CMDB, capacidad, disponibilidad. Tickets cubren incidentes. |
| **Gestión documental avanzada** | Fuera de alcance. Actas, firma digital, versionado de documentos, búsqueda. |
| **Multi-idioma (i18n)** | No aplica a tesis. Sistema en español únicamente. |

---

## 3. Frases de defensa para la tesis

### 3.1 RBAC Core

"El sistema implementa RBAC Core según ANSI/INCITS 359, con relación N:M entre usuarios y roles (`usuario_rol`) y permisos por operación sobre páginas (`rol_modulo_permiso`). El enforcement es en backend vía `requireOperation()`. Adicionalmente, se implementa el modelo de dos niveles con `rol_modulo_pagina`."

### 3.2 Separación de Funciones

"El sistema aplica Separación de Funciones según NIST SP 800-53 AC-5. Acciones sensibles como validar pagos o resetear contraseñas requieren el rol Administrador. La matriz de permisos sobre el módulo Configuración está protegida en tres capas independientes: UI, datos y backend (guard en `requirePageAccess`). El guard sigue el patrón de Google Cloud IAM Deny Policies."

### 3.3 Mínimo Privilegio

"El sistema aplica el Principio de Mínimo Privilegio (NIST SP 800-53 AC-6). Los roles no-Admin tienen restringido el acceso al módulo Configuración en UI, en datos y en backend. El kernel rechaza con 403 cualquier intento de acceder al módulo Configuración si el usuario no tiene el rol Administrador."

### 3.4 Auditoría

"Cada escritura queda registrada en `logs` vía triggers AFTER INSERT/UPDATE/DELETE. El contexto del actor se establece en `setAuditContext()`. Los logs son inmutables."

### 3.5 PII

"Dump fuera del webroot con `.htaccess` 403. `password_hash` bcrypt. Tokens opacos en BD, nunca `$_SESSION`. `must_change_password` enforced."

### 3.6 Integridad de facturación

"`facturas` con UNIQUE `(id_servicio, periodo)`. Máquina de estados validada en INSERT y UPDATE vía triggers. Los pagos validados solo pueden ser revertidos por administradores."

### 3.7 Red

"Jerarquía `nodos → olts → naps → servicios` con IPAM básico."

### 3.8 Alcance

"El sistema no compite con soluciones comerciales en integración real con hardware. El foco es conformidad con estándares formales."

### 3.9 Referencia de mercado

"La UI tomó como referencia SaaS Gestión, SmartOLT y Splynx."

### 3.10 Calidad de producto

"Evaluado contra ISO/IEC 25010:2023. Scores por capa en `AUDITORIA_2026.md`."

### 3.11 Máquina de estados

"`transicion_estado` define las transiciones válidas por tabla. Triggers BEFORE INSERT y BEFORE UPDATE las validan."

### 3.12 Transacciones

"Operaciones multi-tabla usan `beginTransaction`/`commit`/`rollBack`."

### 3.13 Deuda técnica consciente

"SoD hardcoded en lugar de RBAC2 SSD/DSD modelado. Documentado en ADRs."

### 3.14 Arquitectura

"Monolito modular. Práctica recomendada para sistemas de este tamaño."

### 3.15 RBAC de dos niveles

"El sistema implementa RBAC de dos niveles siguiendo el patrón de AWS VAMS. Nivel 1: visibilidad de página (`rol_modulo_pagina`). Nivel 2: operación sobre la página (`rol_modulo_permiso`). Ambos son independientes y ambos son obligatorios. La verificación end-to-end se hizo con HTTP: sin visibilidad → 403, con visibilidad → 200."

### 3.16 Nivel 2 aplicado a los CRUD

"Los 11 endpoints CRUD + los 3 endpoints de clientes verifican el permiso por operación a través de `requireOperation()`. El verbo HTTP determina la operación: GET = leer, POST = crear, PUT = editar, DELETE = eliminar. El patrón es equivalente al de Google Cloud IAM, donde cada método API tiene su propio permiso requerido."

### 3.17 Modelo de permisos por página

"Cada página de la UI mostrará solo los permisos que le corresponden según su naturaleza. Las páginas con entidad persistente y endpoint CRUD muestran los 5 checkboxes de operación. Las páginas de visualización pura (Dashboard, Mapa, Reportes) solo muestran el checkbox 'Ver'. Este modelo es el que usan Salesforce, ServiceNow, AWS QuickSight, SAP, Azure y Odoo. No se inventa: se sigue la práctica de la industria."

### 3.18 GET sin side-effects

"El vencimiento de facturas y contratos no está en un cron ni en un trigger. El hosting de tesis (XAMPP/cPanel) no soporta cron. La materialización se hace vía endpoint POST explícito, idempotente, disparado por el frontend antes de cada lectura. Es el patrón de 'on-demand execution' de Google Cloud Scheduler y AWS EventBridge Scheduler, adaptado a un entorno sin scheduler. La alternativa —un GET con side-effect— viola RFC 7231 §4.2.1 y MITRE lo cataloga como CWE-650."

### 3.19 Servicio como entidad de primer nivel

"El servicio se modela como entidad de primer nivel en la UI, no como sub-atributo del cliente. Coherente con el estándar TM Forum SID (`CustomerFacingService` como entidad independiente) y con la práctica de Splynx, Omnitouch y PortaOne. El modelo de datos ya lo reflejaba desde el ADR 003 (relación 1:N)."

### 3.20 Mensajes de error genéricos

"Exponer `$e->getMessage()` de PDO al cliente filtra rutas de archivo, nombres de tabla y columnas. Es CWE-209. OWASP ASVS 5.0 V7.4.1 exige que los errores internos no se devuelvan al cliente. Los mensajes genéricos con 'Contacta al administrador si persiste' cumplen."

### 3.21 Portal de cliente
"El sistema incluye un portal de cliente separado del panel admin. El cliente se autentica contra `clientes_credenciales`, sesión con `tipo_usuario='cliente'`, y accede únicamente a sus propios datos. El `id_cliente` se toma siempre de la sesión autenticada, nunca del body HTTP, previniendo BOLA/IDOR (OWASP API Security Top 10 #1). Es el patrón de Splynx, Sonar y SmartOLT."

### 3.22 Notificaciones
"El sistema implementa notificaciones como servicio transversal con dos bandejas separadas: staff (campana en panel admin) y cliente (campana en portal). Cada evento de negocio dispara una notificación a los destinatarios correspondientes. El patrón es el mismo que usan Splynx y Sonar."

### 3.23 Flujo end-to-end cliente↔staff
"El sistema cierra el ciclo cliente↔staff completamente: el cliente entra al portal, abre un ticket, sube comprobante de pago, responde mensajes. Cada acción dispara notificación al staff. El staff responde desde el panel admin, valida el pago, cierra el ticket. Cada respuesta dispara notificación al cliente."

### 3.24 Pago manual con comprobante
"El pago con comprobante manual es el estándar en ISPs venezolanos. El sistema financiero del país no tiene API unificada de pagos. Pago Móvil y C2P requieren confirmación manual. Zelle no opera en Venezuela. El sistema valida el comprobante manualmente, igual que Splynx, Vnet, Nevacom y Movilnet. Una pasarela automatizada sería una integración externa fuera del alcance de tesis."

### 3.25 Modelo de datos completo
"El esquema de base de datos refleja el modelo completo de un sistema ISP profesional. Incluye entidades de CRM (clientes, contactos, notas, etiquetas), Fulfillment (pedidos, materiales, asignaciones), Assurance (tickets, categorías, SLA, respuestas), Resource Management (nodos, OLTs, NAPs, equipos, IPAM, movimientos), Billing (facturas, pagos, notas crédito/débito, ciclos), Notificaciones (staff + cliente) y Email transaccional (plantillas + log). El modelo se diseñó completo desde el inicio (Database-First), con triggers de auditoría inmutable en todas las tablas de negocio."
---

## 4. Aprendizajes

### 4.1 Técnicos

- **Hardcodeo de rol 1 contradice RBAC.**
- **`requireOperation` y el catálogo de páginas.**
- **No asumir 1 cliente = 1 servicio.**
- **Buscar funciones fantasmas.**
- **El guard SoD va en `requirePageAccess`.**
- **Las cachés `window.*Cache` no se limpian al logout** (F08).
- **Las páginas de visualización pura no tienen endpoint CRUD.**
- **Los triggers BEFORE INSERT son trampa para INSERTs directos desde endpoints de flujo compuesto** (CODE-01).
- **Los generados + UNIQUE en MySQL no restringen si la columna generada queda NULL** (CODE-02).
- **Al probar un endpoint aislado, cerrar todas las pestañas de la app.** `initApp()` dispara cargas que interfieren. Usar curl.
- **El GET sin side-effect se prueba con curl y la app cerrada.**
- **Los controladores de materialización de estado son POST, no GET.** (B08, patrón on-demand execution).
- **Un endpoint de materialización de estado no requiere `requireOperation`:** no lee datos controlados por el usuario. Solo `requirePageAccess` basta.
- **La restricción UNIQUE de la BD es la fuente de verdad para el mapeo de errores 1062.** Cada rama del ternario debe corresponder a una UNIQUE real.
- **La industria separa tareas internas de pedidos de campo.** Sonar:
  Tasks vs Jobs. BSS/OSS: Sales Order → Work Order. Modelo: `tareas`
  para el operador (oficina), `pedidos` para el técnico (campo).
- **Todo pedido de campo nace de un servicio.** Splynx, Sonar, UISP,
  BSS/OSS: Servicio → Pedido → Técnico. `pedidos.servicio_id NOT NULL`.
- **`propiedad` de equipos no es siempre derivable de `tipo`.** ONU
  siempre empresa, ROUTER siempre cliente por defecto (pero puede
  cambiar si el ISP lo presta), COMBO empresa por defecto (pero el
  cliente puede pagar por él).

### 4.2 De proceso

- **Documentación desincronizada es peligrosa.**
- **Filtro de Grupo E va en DOS puntos.**
- **UI ≠ backend.** Verificar ambas capas por separado.
- **Verificar el ciclo completo logout → login → cambio de rol.**
- **Los planes viven fuera del repo.**
- **Verificar que el dominio de la página tiene endpoint propio.**
- **Las revisiones de coherencia cruzada detectan bugs que una lectura lineal no ve.**
- **Las pruebas de endpoints con la app abierta pueden dar falsos positivos** (la app dispara POSTs de materialización al loguearte).

### 4.3 Del ciclo de trabajo

- **El humano supervisa, la IA auditora investiga y audita, AI Studio genera, el humano valida.**
- **Nada se aprueba sin auditoría.**
- **Rigor de fuentes.**

**Lecciones de verificación:** viven en `docs/VERIFICACION.md`. No se duplican aquí.

---

## 5. Referencias de mercado

### 5.1 SaaS Gestión (`saasgestion.com`)

Módulos CRM/OSS/BSS. Navegación agrupada por dominio. Vista 360° del cliente. KPIs accionables.

### 5.2 SmartOLT

Tablas densas. Badges de estado. Pestañas de detalle. Tema oscuro.

### 5.3 Splynx

Servicios como entidad de primer nivel. Herramienta "Migrate services" separada con permiso dedicado.

### 5.4 Omnitouch

Tabla global de servicios con columna Customer clickable.

### 5.5 PortaOne

Catálogo de Service types and services. Ratings y billing sobre el servicio, no sobre el cliente.

### 5.6 Modelo de permisos por página

Salesforce, ServiceNow, AWS QuickSight, SAP, Azure, Odoo.

### 5.7 Patrón on-demand execution

Google Cloud Scheduler ("gcloud scheduler jobs run"). AWS EventBridge Scheduler.

### 5.8 Regla de uso

**No copiar pixel a pixel.** Inspirarse en patrones. Atribuir la referencia en la tesis.

### 5.9 Regla de rigor

Antes de proponer una decisión de diseño, citar al menos un sistema en producción que resuelva el mismo problema. Si no hay fuente, declararlo explícitamente.

### 5.10 ISPs venezolanos — referencias obligatorias

Fuentes primarias para decisiones de facturación, moneda o normativa fiscal:

| Fuente | Uso |
|--------|-----|
| **SENIAT** | Obligación fiscal, formato de factura, IGTF, IVA. |
| **CONATEL** | Regulación de telecomunicaciones. |
| **Casetel** | Cámara del sector, postura sobre facturación y tasas. |
| **CANTV** | Operador estatal — facturación USD/Bs. |
| **Netuno** | ISP privado — 90% facturado en USD, cobrado en Bs. |
| **Wispro** | Software de facturación ISP con configuración Venezuela. |
| **Splynx** | ERP ISP con módulo multi-moneda. |

**Regla práctica:** ningún campo, cálculo o flujo financiero se implementa sin al menos una referencia verificable de esta lista.

### 5.11 Sistemas de referencia para el flujo Servicio → Pedido

- **Splynx** — Field Service: tareas se crean desde ticket, siempre
  vinculadas a un servicio.
- **Sonar** — separa Tasks (interno) de Jobs (campo). Un Job siempre
  cuelga de un Service.
- **UISP (Ubiquiti)** — "Subscribers are paired with services defined
  in the CRM". El trabajo de campo se gestiona sobre el servicio.
- **BSS/OSS (Broadband Forum TR-311)** — Sales Order se descompone en
  Work Orders. El Work Order es la ejecución técnica de un Service
  Order.