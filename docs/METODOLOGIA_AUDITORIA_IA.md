# Capítulo de Metodología de Auditoría de Software Asistida por IA

## Marco Integral por Capas, Pasadas y Contexto Operativo

---

## Resumen del Capítulo

Este capítulo define un marco metodológico integral para la auditoría de software asistida por inteligencia artificial, diseñado específicamente para plataformas de gestión de ISPs (Internet Service Providers). El marco se estructura en tres componentes interdependientes: (1) un eje de pasadas metodológicas secuenciales que garantizan rigor progresivo, (2) un eje de capas de auditoría que cubre todos los componentes del sistema, y (3) un eje de contexto operativo que integra las particularidades del negocio ISP, incluyendo líneas rojas no negociables, excepciones justificadas y gobernanza de riesgo. La novedad del marco radica en su capacidad de distinguir entre incumplimiento no negociable, excepción justificada y deuda técnica consciente, permitiendo auditorías reproducibles, trazables y alineadas con estándares internacionales como ISO/IEC 25010:2023, OWASP ASVS 5.0, ISO/IEC 27011:2024 e ITU-T X.1051.

---

## 1. Introducción y Justificación

### 1.1 El Problema de la Auditoría de Software sin Marco Formal

La auditoría de software tradicional frecuentemente adolece de tres deficiencias críticas. Primero, se realiza sin una verificación previa de conformidad estructural: se evalúa la calidad de una implementación sin confirmar que su arquitectura fundamental corresponde a la definición formal del patrón que dice implementar. Segundo, carece de un protocolo reproducible de evidencia: los hallazgos no pueden ser verificados independientemente porque no se documenta el fragmento de código, la línea exacta, la versión del sistema ni el método de detección utilizado. Tercero, ignora el contexto operativo del negocio: un software puede ser técnicamente impecable y aun así ser inadecuado para su dominio de aplicación.

### 1.2 La Necesidad de un Marco Específico para ISPs

Un ISP (Internet Service Provider) opera bajo restricciones únicas que no aplican a otros dominios de software. Maneja infraestructura crítica de red, datos personales masivos (nombres, cédulas, direcciones físicas, logs de asignación IP), facturación recurrente y regulación específica del sector de telecomunicaciones. Adicionalmente, depende de hardware heterogéneo —routers, switches, OLTs, antenas de marcas como Mikrotik, Huawei, Cisco y Ubiquiti— que frecuentemente no soporta estándares modernos de seguridad o interoperabilidad. Un marco de auditoría genérico es insuficiente porque no puede distinguir entre una vulnerabilidad corregible y una limitación real del hardware que debe ser mitigada y documentada.

### 1.3 Objetivo y Alcance del Marco

Este capítulo define un marco de auditoría que integra tres ejes ortogonales:

1. **Eje de pasadas metodológicas**: seis perspectivas de auditoría que se aplican en orden de dependencia lógica.
2. **Eje de capas de auditoría**: diez capas que cubren desde la base de datos hasta el contexto operativo y de negocio.
3. **Eje de contexto operativo ISP**: líneas rojas, dominios de impacto, proceso de excepción justificada y trazabilidad de gobernanza.

El marco está diseñado para ser ejecutado por una inteligencia artificial bajo supervisión humana, con protocolos explícitos de evidencia, muestreo, rúbrica de conformidad y declaración de limitaciones.

---

## 2. Principio Metodológico Fundamental

### 2.1 Las Dos Pasadas Obligatorias

Toda auditoría contra un estándar formal debe realizarse en dos pasadas obligatorias y en este orden, nunca al revés:

**Pasada de Conformidad Estructural**: Verifica si la arquitectura básica del sistema —cardinalidades, relaciones fundamentales, elementos obligatorios— coincide con la definición formal del estándar, elemento por elemento, antes de evaluar cualquier otra cosa. Para estándares expresados en RFC-speak, esto significa verificar los requisitos etiquetados como MUST/SHALL. Para estándares como los del NIST, significa verificar los componentes etiquetados como "Core" o "Baseline". Para el modelo RBAC de ANSI/INCITS 359, significa confirmar que la relación Usuario↔Rol es muchos-a-muchos (UA), que la relación Permiso↔Rol es muchos-a-muchos (PA), y que los permisos separan objeto (recurso) de operación (acción).

**Pasada de Consistencia Interna**: Dado que la estructura es conforme, verifica si el sistema es coherente consigo mismo en términos de normalización, integridad referencial, nomenclatura, indexación, manejo de errores, seguridad de implementación y calidad de código.

El fallo más común en auditorías es saltar directamente a la segunda fase sin completar la primera. Esto conduce a pulir la normalización de una tabla cuyo modelo relacional es estructuralmente incorrecto, o a optimizar el manejo de errores de un endpoint que no debería existir bajo el patrón arquitectónico declarado.

### 2.2 Universalidad de las Dos Pasadas

La lógica de fondo no es específica de bases de datos. Cualquier capa que implemente un patrón con nombre formal tiene, primero, una forma canónica definida por un estándar, y después, una calidad de ejecución de esa forma. El patrón se repite en cada capa:

| Capa | Pasada 1 — Conformidad Estructural | Pasada 2 — Consistencia Interna |
|---|---|---|
| Base de datos | ¿La relación Usuario↔Rol es N:M como exige ANSI/INCITS 359? ¿Los permisos separan objeto de operación? | Dado que la tabla existe, ¿tiene UNIQUE, CHECK, normalización correcta, índices? |
| Backend/API | ¿La API es REST de verdad según Richardson Maturity Model (recursos + verbos HTTP semánticos), o es RPC disfrazado de REST? | Dado que usa el verbo correcto, ¿maneja errores sin fuga de información, valida entrada, usa transacciones? |
| Autorización | ¿El sistema implementa Core RBAC completo (UA y PA muchos-a-muchos, permisos = objeto × operación)? | Dado que hay RBAC, ¿el enforcement es consistente en todos los endpoints? |
| Frontend | ¿El manejo de estado sigue un flujo unidireccional (Flux/Redux)? | Dado que hay un store, ¿está bien tipado, sin duplicación de fuente de verdad? |
| Seguridad | ¿Existe defensa en profundidad real (control en 2+ capas independientes)? | Dado que hay control de autenticación, ¿cumple NIST SP 800-63B, expira sesión correctamente? |
| Testing | ¿La suite sigue la pirámide de pruebas (muchas unitarias, pocas E2E)? | Dado que hay tests unitarios, ¿cubren casos negativos y límite? |

### 2.3 Criterio de Parada (Fail-Fast) y Gating

El principio de parada temprana es una decisión metodológica fundamental que ahorra tiempo y recursos significativos. La auditoría se ejecuta en fases con compuertas:

**Fase 1 — Conformidad Estructural y Técnica**: Si el sistema no cumple el 100% de los requisitos MUST/SHALL de los estándares invocados, la auditoría se detiene. Se emite un informe de "No Conformidad Estructural" y se devuelve al equipo de desarrollo. No se evalúa calidad interna ni se consideran excepciones de negocio sobre una base estructuralmente rota.

**Fase 2 — Consistencia Interna y Calidad Técnica**: Se audita normalización, integridad, arquitectura, seguridad, testing y demás dimensiones de calidad de implementación. Se generan hallazgos clasificados por severidad.

**Fase 3 — Contexto Operativo y de Negocio**: Se evalúa el impacto específico en el negocio ISP, se aplican líneas rojas y se gestionan excepciones justificadas. Solo llegan a esta fase los sistemas que superaron la Fase 1 y los hallazgos de Fase 2 que no constituyen líneas rojas.

---

## 3. Eje de Pasadas Metodológicas

El marco completo comprende seis pasadas metodológicas. Las dos primeras son obligatorias y secuenciales. Las cuatro restantes se aplican en orden de dependencia lógica.

### 3.1 Pasada 1 — Conformidad Estructural

**Propósito**: Verificar que la arquitectura básica coincide con la definición formal del estándar invocado.

**Pregunta central**: ¿El sistema tiene la forma canónica que el estándar exige?

**Método**: Para cada patrón con nombre formal invocado (RBAC, REST, ACID, CQRS, Event Sourcing, workflow de estados finitos, contabilidad de doble entrada), identificar el documento primario que define el patrón y verificar elemento por elemento los componentes obligatorios. Para RBAC, el documento primario es ANSI/INCITS 359. Para REST, el Richardson Maturity Model y RFC 7231. Para ACID, la definición formal de transacciones de Jim Gray.

**Criterio de salida**: Si el sistema no cumple el 100% de los elementos obligatorios, la auditoría se detiene con estado "No Conforme Estructural".

### 3.2 Pasada 2 — Consistencia Interna

**Propósito**: Evaluar la calidad de la implementación de la estructura conforme.

**Pregunta central**: Dado que la estructura es correcta, ¿está bien ejecutada?

**Método**: Auditoría detallada de cada capa según los criterios de las secciones 4.1 a 4.10.

**Criterio de salida**: Informe de hallazgos clasificados por severidad.

### 3.3 Pasada 3 — Consistencia Cruzada entre Capas

**Propósito**: Verificar que lo que el estándar exige a nivel de una capa se refleja sin degradarse en las capas superiores e inferiores.

**Pregunta central**: ¿El sistema completo es conforme, o solo una capa aislada?

**Ejemplo concreto**: Es posible migrar `usuarios.id_rol` a `usuario_rol` N:M en la base de datos, pero el backend sigue leyendo `$staffSession['id_rol']` como escalar único porque nadie actualizó `requireStaffAuth()`. La base de datos cumple el estándar, pero el sistema completo no, porque la capa superior sigue operando bajo el modelo viejo.

**Método**: Trazar el flujo de datos y decisiones desde la base de datos hasta la interfaz de usuario, verificando que cada transformación preserve las invariantes del estándar.

### 3.4 Pasada 4 — Threat Modeling / Adversarial

**Propósito**: Adoptar la perspectiva de un atacante o usuario malicioso para identificar vectores de ataque que el análisis estático no revela.

**Pregunta central**: ¿Cómo rompo esto?

**Marco de referencia**: STRIDE (Spoofing, Tampering, Repudiation, Information Disclosure, Denial of Service, Elevation of Privilege), desarrollado por Microsoft como parte de su Security Development Lifecycle. Para cada componente y flujo de datos, evaluar sistemáticamente las seis categorías de amenazas.

**Aplicación en ISPs**: Preguntas específicas como: ¿Qué puede hacer un técnico de soporte si ataca por HTTP directo, ignorando el frontend? ¿Puede un cliente ver datos de otro cambiando un ID en la URL (BOLA/IDOR)? ¿Se puede saturar la conexión con la OLT para degradar el servicio?

### 3.5 Pasada 5 — No Funcional / Calidad de Sistema

**Propósito**: Evaluar características de calidad que no son binarias (correcto/incorrecto) sino espectros de calidad.

**Marco de referencia**: ISO/IEC 25010:2023, que define nueve características de calidad de producto: adecuación funcional, eficiencia de desempeño, compatibilidad, capacidad de interacción, fiabilidad, seguridad, mantenibilidad, flexibilidad y seguridad física.

**Pregunta central**: ¿El sistema escala, se mantiene y ofrece calidad de servicio sostenible?

### 3.6 Pasada 6 — Dinámica / de Ejecución Real

**Propósito**: Confirmar mediante ejecución real que lo que el código dice hacer es lo que efectivamente hace en runtime.

**Pregunta central**: ¿Las hipótesis de las pasadas 1–5 se sostienen en ejecución?

**Método**: Pruebas dinámicas con herramientas automatizadas (OWASP ZAP, Burp Suite, k6, Postman/Newman), pruebas de penetración, medición de tiempos de respuesta, análisis de comportamiento bajo carga.

**Limitación declarada**: La auditoría estática no puede ejecutar esta pasada. Para que la auditoría esté completa, el entorno del ISP debe ejecutarla con datos reales y representativos, conforme a ISO/IEC/IEEE 29119.

### 3.7 Orden e Interdependencia de las Pasadas

El orden no es arbitrario. Existe una dependencia lógica real:

**1 (estructural) → 2 (interna) → 3 (cruzada entre capas) → 4 (adversarial) y 5 (no funcional) en paralelo → 6 (dinámica, valida todas)**

Auditar pasadas 4 o 5 sobre algo que falla en la pasada 1 es desperdiciar esfuerzo. Modelar amenazas contra un RBAC que ni siquiera tiene la cardinalidad correcta no produce hallazgos útiles. La pasada 6 es la única que confirma si las cinco anteriores se sostienen en la práctica; una auditoría estática, por rigurosa que sea, sigue siendo una hipótesis educada hasta que se ejecuta.

---

## 4. Eje de Capas de Auditoría

### 4.1 Matriz de Integración Pasadas × Capas

La siguiente matriz especifica qué pasadas se aplican a qué capas, eliminando la ambigüedad sobre la aplicabilidad de cada perspectiva:

| Capa | P1 Estructural | P2 Interna | P3 Cruzada | P4 Adversarial | P5 No Funcional | P6 Dinámica |
|---|---|---|---|---|---|---|
| 1. Datos / BD | ● | ● | ● | ● | ● | ● |
| 2. Backend / API | ● | ● | ● | ● | ● | ● |
| 3. Frontend | ○ | ● | ● | ● | ● | ● |
| 4. Seguridad | ● | ● | ● | ● | ● | ● |
| 5. Arquitectura | ● | ● | ● | ○ | ● | ○ |
| 6. Testing | ○ | ● | ● | ● | ○ | ● |
| 7. Infraestructura | ○ | ● | ● | ● | ● | ● |
| 8. Documentación | ○ | ● | ● | ○ | ○ | ○ |
| 9. Calidad Producto | ○ | ○ | ● | ○ | ● | ● |
| 10. Contexto ISP | ● | ● | ● | ● | ● | ● |

**Leyenda**: ● = aplicable obligatoriamente; ○ = aplicable opcionalmente según alcance de la auditoría.

### 4.2 Capa 1 — Datos / Base de Datos

**Estándares de referencia**: ISO/IEC 9075 (SQL Standard), formas normales de Codd (1NF–BCNF), ACID, DAMA-DMBOK2, ISO/IEC 11179 (nomenclatura de metadatos), CAP theorem (si es distribuido), NIST SP 800-57 (gestión de claves), migraciones versionadas (Flyway, Liquibase, Phinx).

**Criterios de auditoría**:

- **Conformidad estructural contra patrón de dominio**: Si el sistema implementa un patrón con nombre formal (RBAC, workflow de estados finitos, contabilidad de doble entrada), verificar primero la correspondencia elemento-por-elemento contra la especificación formal de ese patrón, antes de evaluar el DDL en sí.
- **Normalización**: 1FN (atomicidad), 2FN (dependencia total de la clave), 3FN (sin dependencias transitivas), BCNF. Justificar cualquier desnormalización deliberada (rendimiento) explícitamente, nunca por descuido.
- **Integridad de entidad**: Toda tabla con PK; naturaleza de la PK (natural vs. surrogate) justificada.
- **Integridad referencial**: Toda FK explícita y declarada (no solo enforced por aplicación); política ON DELETE/ON UPDATE (CASCADE/RESTRICT/SET NULL) decidida conscientemente y consistente entre relaciones análogas.
- **Integridad de dominio**: CHECK constraints, ENUM vs. tabla catálogo (criterio uniforme), rangos válidos, formatos (regex a nivel de aplicación como mínimo).
- **Ausencia de valores centinela ambiguos**: NULL vs. '' vs. 0 usados con semántica consistente.
- **Claves de negocio protegidas**: UNIQUE sobre toda columna que la lógica de negocio trate como identificador humano (código, email, IP, slug de URL).
- **Restricciones "exactamente uno de N" (polimorfismo débil)**: CHECK que garantice exclusividad mutua cuando dos FKs nullable modelan alternativas excluyentes.
- **Tipos de datos apropiados al dominio**: DECIMAL para dinero (nunca FLOAT/DOUBLE), precisión geoespacial adecuada, longitud de columna suficiente para hashes (bcrypt/Argon2 ≥ 255 chars), BIGINT en tablas de crecimiento no acotado.
- **Indexación**: Cobertura de columnas de filtro/orden frecuente (estado, fechas, claves foráneas de alta cardinalidad de consulta), índices compuestos cuando el patrón de consulta lo amerita, sin sobre-indexar (costo de escritura).
- **Auditoría/trazabilidad**: created_at/updated_at consistentes en todas las tablas de negocio; si existe tabla de logs, verificar si usa asociación polimórfica sin FK real (renuncia consciente documentada) y si guarda snapshot del cambio, no solo el evento.
- **Collation/Charset**: Coherente con el idioma real del dominio (unicode_ci vs general_ci para ordenamiento correcto en español u otros idiomas no ingleses).
- **Nomenclatura uniforme** (ISO/IEC 11179): Un solo criterio para nombrar FKs (singular vs. plural del nombre de tabla referenciada), tablas, columnas — aplicado sin excepciones.
- **Tablas singleton**: Si una tabla debe tener exactamente una fila (configuración global), verificar que el diseño lo garantice, no que lo asuma la aplicación.
- **Versionado de esquema**: Migraciones versionadas vs. dump estático como fuente de verdad.
- **Cifrado en reposo/tránsito**: NIST SP 800-57 para gestión de claves; qué columnas contienen PII/datos sensibles y si están cifradas o al menos hasheadas donde corresponde.
- **Multi-tenancy / aislamiento de datos** (si aplica): Partición por tenant, fuga cruzada imposible a nivel de esquema.

### 4.3 Capa 2 — Backend / API / Lógica de Negocio

**Estándares de referencia**: 12-Factor App, REST (Richardson Maturity Model), OpenAPI 3.x, RFC 7231 (HTTP semantics), Clean/Hexagonal Architecture, SOLID, SemVer.

**Criterios de auditoría**:

- **Conformidad estructural contra patrón invocado**: Si se dice "esto es RBAC", "esto es CQRS", "esto es Event Sourcing" — auditar primero contra la definición formal de ese patrón.
- **Separación de capas**: Ruta/controlador → lógica de negocio → acceso a datos, sin mezcla (anti-patrón Transaction Script disperso en controladores).
- **Autorización enforced en capa de servicio, no solo en presentación** (OWASP ASVS V4.1.1): Todo endpoint debe re-validar permisos server-side, independientemente de lo que oculte el frontend.
- **Autenticación**: Mecanismo (sesión/token/JWT/OAuth2), expiración, renovación, revocación, almacenamiento seguro de credenciales (hashing con bcrypt/Argon2, nunca reversible).
- **Gestión de secretos**: Variables de entorno vs. hardcodeado en repositorio; rotación posible sin downtime.
- **Validación de entrada exhaustiva**: Cada campo de cada endpoint —tipo, longitud, rango, formato, whitelist de valores permitidos (nunca blacklist como única defensa).
- **Prevención de inyección**: Prepared statements/ORM parametrizado en el 100% de las consultas, sin excepción.
- **Idempotencia y semántica HTTP correcta**: Verbos usados según RFC 7231 (GET sin efectos secundarios, PUT idempotente, POST no idempotente salvo diseño explícito), códigos de estado semánticamente correctos.
- **Manejo de errores sin fuga de información**: Nunca exponer stack traces, mensajes de driver de BD, ni rutas de archivo al cliente (CWE-209).
- **Transacciones e integridad a nivel de aplicación**: Operaciones multi-tabla envueltas en transacción real, rollback ante fallo parcial.
- **Concurrencia**: Condiciones de carrera en operaciones críticas (ej. validar dos veces el mismo pago simultáneamente), locking optimista/pesimista donde corresponda.
- **Separación de funciones (SoD)**: Quien crea no debería poder aprobar lo mismo que creó, en flujos financieros o de aprobación (RBAC2/NIST).
- **Versionado de API**: SemVer aplicado, estrategia de breaking changes documentada.
- **Rate limiting / protección contra abuso**: En endpoints de autenticación como mínimo.
- **Idempotencia de reintentos** en integraciones externas (pagos, notificaciones).
- **CORS**: Whitelist explícita, nunca '*' combinado con credenciales.
- **Logging estructurado**: Con niveles, sin loguear secretos ni PII sin necesidad.

### 4.4 Capa 3 — Frontend

**Estándares de referencia**: WCAG 2.1/2.2 AA, Core Web Vitals, HTML Living Standard (WHATWG), ECMAScript (TC39), CSP (W3C), Atomic Design, patrón Flux/unidirectional data flow.

**Criterios de auditoría**:

- **Accesibilidad**: Etiquetas semánticas, alt en imágenes, contraste de color mínimo, navegación completa por teclado, aria-* donde el HTML nativo no baste, foco visible.
- **Gestión de estado**: Mutación de estado centralizada y predecible (store/reducer), no variables globales dispersas sin contrato (window.* sueltas sin API definida).
- **Validación de formularios en cliente Y servidor**: La de cliente es UX, nunca seguridad.
- **Manejo de errores de red**: Todo fetch/XHR con try/catch y feedback al usuario, sin fallos silenciosos.
- **Rendimiento**: Core Web Vitals (LCP, INP, CLS), lazy loading de recursos pesados, minimización de reflow/repaint.
- **Seguridad de cliente**: Sanitización antes de insertar en el DOM (prevención de XSS), Content-Security-Policy configurado, no eval() de datos externos, no innerHTML con datos no confiables.
- **Responsive / adaptabilidad**: Comportamiento correcto en viewport móvil y desktop, unidades relativas.
- **Internacionalización** (si aplica): Separación de textos de la lógica, formato de fecha/moneda/número localizado.
- **Consistencia de patrones de UI repetidos**: Paginación, modales, manejo de carga — un único patrón replicado, no reinventado por módulo.
- **Almacenamiento en cliente**: Qué va en localStorage/sessionStorage/cookies, y por qué (nunca datos sensibles sin cifrar en localStorage).

### 4.5 Capa 4 — Seguridad (Transversal)

**Estándares de referencia**: OWASP ASVS 5.0, OWASP Top 10, CWE (MITRE), CVE (MITRE), STRIDE (Microsoft), OAuth 2.0/OIDC (IETF/OpenID Foundation), NIST SP 800-63B Rev. 4, ISO/IEC 27001.

**Criterios de auditoría**:

- **Threat modeling explícito** (STRIDE como mínimo): Evaluado por cada flujo crítico, no solo revisión de código línea por línea.
- **Principio de menor privilegio** en todo: Defaults de roles, permisos de BD del usuario de conexión, permisos de archivos en servidor.
- **Fail-safe defaults**: Todo valor por defecto debe ser el más restrictivo, nunca el más permisivo (ej. id_rol DEFAULT al rol de menor privilegio, nunca al de administrador).
- **Defensa en profundidad**: Ningún control de seguridad debe ser el único mecanismo.
- **Gestión de sesión**: Expiración razonable, invalidación real al logout (no solo del lado cliente), protección contra fijación de sesión.
- **Superficie de ataque de dependencias**: Versiones de librerías contra bases CVE conocidas.
- **Política de contraseñas conforme a NIST SP 800-63B Rev. 4**: Longitud mínima de 15 caracteres cuando la contraseña es el único autenticador, máximo de al menos 64 caracteres, eliminación de reglas de composición obligatorias (mayúsculas, números, símbolos), verificación contra listas de contraseñas comprometidas, y sin rotación forzosa arbitraria.
- **Protección contra fuerza bruta**: Bloqueo temporal tras intentos fallidos, con umbral y ventana de tiempo razonables.
- **Autorización granular real vs. "todo o nada"**: Verificar si el sistema distingue operación (leer/crear/editar/eliminar) sobre el recurso, o solo visibilidad binaria.

### 4.6 Capa 5 — Arquitectura y Diseño

**Estándares de referencia**: SOLID, GRASP, Clean/Hexagonal Architecture, patrones GoF, acoplamiento/cohesión.

**Criterios de auditoría**:

- **Acoplamiento bajo, cohesión alta**: Un módulo no debería conocer detalles de implementación de otro.
- **Responsabilidad única** por función/clase/archivo.
- **Inversión de dependencias**: La lógica de negocio no debería depender directamente del framework/motor de BD específico.
- **Escalabilidad estructural**: ¿El diseño permite crecer (nuevos roles, nuevos módulos, nuevos tipos de entidad) sin reescritura masiva, o hay techos estructurales?

### 4.7 Capa 6 — Testing y Calidad

**Estándares de referencia**: ISO/IEC/IEEE 29119, pirámide de pruebas (Cohn), TDD, Mutation Testing, Contract Testing.

**Criterios de auditoría**:

- **Cobertura de lógica crítica**: Autenticación, autorización, cálculos financieros — con tests unitarios reales, no solo pruebas manuales.
- **Tests de integración** en endpoints sensibles.
- **Tests negativos**: Qué pasa con entrada malformada, límites, valores nulos — no solo el "camino feliz".
- **Regresión**: Suite ejecutable en cada cambio (CI), no solo pruebas manuales puntuales.

### 4.8 Capa 7 — Infraestructura y Despliegue

**Estándares de referencia**: Infrastructure as Code, OCI (contenedores), CI/CD, Google SRE (SLI/SLO/SLA), OpenTelemetry.

**Criterios de auditoría**:

- **Reproducibilidad del entorno**: Configuración explícita, no "funciona en mi máquina".
- **Gestión de configuración por entorno**: Dev/staging/producción separados, sin credenciales de producción en desarrollo.
- **Backups**: Existencia, frecuencia, prueba real de restauración (no solo "se genera el backup").
- **Observabilidad**: Logs, métricas y trazas correlacionables ante un incidente.

### 4.9 Capa 8 — Documentación y Gobernanza

**Estándares de referencia**: ISO/IEC 26514 (documentación de usuario), ISO/IEC 25010 (calidad, dimensión Mantenibilidad).

**Criterios de auditoría**:

- **Trazabilidad requisito → diseño → código → prueba**: Especialmente relevante en tesis, donde cada decisión de diseño debe justificarse contra un requisito o estándar citado explícitamente.
- **ADRs (Architecture Decision Records)**: Por qué se tomó cada decisión estructural relevante, no solo qué se hizo.
- **Diagramas actualizados** (ER, casos de uso, secuencia) sincronizados con el código real, no aspiracionales.

### 4.10 Capa 9 — Calidad de Producto (Paraguas)

**Estándar de referencia**: ISO/IEC 25010:2023 (SQuaRE) — nueve características de calidad de producto: adecuación funcional, eficiencia de desempeño, compatibilidad, capacidad de interacción, fiabilidad, seguridad, mantenibilidad, flexibilidad y seguridad física.

Toda auditoría final debe mapear sus hallazgos a cuál(es) de estas características impacta cada uno. Es el criterio con el que un jurado de tesis evalúa la calidad global, más allá de los estándares puntuales por capa.

---

## 5. Capa 10 — Contexto Operativo y de Negocio (Específica del Dominio ISP)

### 5.1 Principio Rector

Un sistema para ISP no se audita solo contra estándares técnicos universales. Se audita también contra su realidad operativa: red física, hardware heredado, regulación de telecomunicaciones, facturación recurrente, PII de clientes y continuidad del servicio. La auditoría debe distinguir entre:

- **Incumplimiento técnico no negociable**: Viola una línea roja de seguridad, privacidad o integridad. No admite excepción.
- **Incumplimiento técnico con excepción justificada**: El estándar no se cumple por una limitación real del hardware, proveedor externo o restricción regulatoria. Se acepta solo con mitigación compensatoria, ADR firmado y fecha de revisión.
- **Deuda técnica consciente**: Se decide posponer una corrección por prioridad de negocio. Se documenta con plan de refactor y se revisa en cada ciclo.

La auditoría no termina en "cumple / no cumple". Termina en "cumple / no cumple / excepción documentada con riesgo aceptado y mitigado".

### 5.2 Estándares Específicos de Telecomunicaciones

**ISO/IEC 27011:2024**: Proporciona directrices para la implementación de controles de seguridad de la información en organizaciones de telecomunicaciones, basados en ISO/IEC 27002. Permite a las organizaciones de telecomunicaciones cumplir requisitos de confidencialidad, integridad, disponibilidad y otras propiedades relevantes de seguridad de la información.

**ITU-T X.1051**: Recomendación de la Unión Internacional de Telecomunicaciones que establece directrices y principios generales para iniciar, implementar, mantener y mejorar la gestión de seguridad de la información en organizaciones de telecomunicaciones. Cubre áreas específicas como gestión de riesgos, gestión de activos, gobernanza y gestión de incidentes.

**PCI-DSS**: Si el ISP almacena o transmite datos de tarjetas de pago, debe cumplir con el Estándar de Seguridad de Datos de la Industria de Tarjetas de Pago. Si no está certificado, no debe tocar datos de tarjeta; debe delegar en pasarela certificada.

### 5.3 Clasificación de Hallazgos por Impacto en el Negocio ISP

Todo hallazgo debe mapearse a uno o más de estos dominios:

| Dominio | Pregunta de Auditoría | Ejemplos de Impacto |
|---|---|---|
| Disponibilidad del servicio | ¿Puede tumbar la red o impedir que clientes naveguen? | Caída de OLT, backend sin timeout, agotamiento de conexiones |
| Seguridad y privacidad de datos (PII) | ¿Expone cédulas, direcciones, teléfonos, IPs, logs? | Fuga por endpoint sin autorización, logs con datos personales |
| Integridad de facturación | ¿Puede alterar cobros, planes, pagos o conciliación? | Doble cobro, modificación de plan sin auditoría |
| Cumplimiento regulatorio | ¿Viola normas del ente regulador? | Retención de logs, protección de datos, calidad de servicio |
| Aislamiento de clientes / multi-tenancy | ¿Un cliente puede ver datos de otro? | BOLA/IDOR en portal de clientes |
| Control de acceso interno / SoD | ¿Un técnico puede hacer más de lo que debe? | Soporte modificando planes o borrando facturas |
| Continuidad operativa | ¿Hay backup, restauración y plan de contingencia? | Pérdida de configuración de red o base de datos |
| Reputación y confianza | ¿Afecta la imagen del ISP? | Correos filtrados, portal caído, cobros erróneos |

Cada hallazgo debe llevar: severidad técnica, dominio(s) de impacto, si toca línea roja, y decisión (corregir / excepción / deuda técnica).

### 5.4 Líneas Rojas No Negociables en un ISP

Estos puntos no admiten "Aceptación de Riesgo" sin escalar a dirección y, en muchos casos, no admiten excepción:

- **Protección de PII de clientes**: Cifrado en tránsito y en reposo para datos personales, credenciales de acceso, logs de asignación IP y cualquier dato que identifique al cliente.
- **Aislamiento de clientes**: Ningún usuario puede acceder a datos de otro cambiando un ID en URL, body o token. Prevención obligatoria de BOLA/IDOR.
- **RBAC formal y Separación de Funciones (SoD)**: El personal técnico de calle no puede modificar planes, borrar facturas, alterar configuración crítica de red ni ver credenciales de routers/OLTs. La estructura debe ser N:M usuario-rol y separar permiso (operación) de página/recurso (objeto).
- **Integridad de facturación**: Toda operación que afecte cobros debe ser transaccional, auditable e idempotente cuando dependa de terceros.
- **Credenciales de red**: Nunca en texto plano, nunca hardcodeadas, nunca accesibles desde el portal de clientes.
- **Disponibilidad mínima del servicio**: El backend no puede quedar sin timeouts, sin límites de recursos ni sin degradación controlada.

Si un hallazgo toca una línea roja, no se aprueba excepción. Se corrige antes de continuar.

### 5.5 Integraciones con Hardware de Red y Sistemas Legacy

Los ISPs operan con routers, switches, OLTs, antenas y equipos de marcas como Mikrotik, Huawei, Cisco, Ubiquiti, etc. Muchos no soportan estándares modernos.

**Auditar**:

- **Protocolo de conexión**: ¿Telnet, SSHv1, API propietaria, SNMPv1/v2? Si es inseguro, verificar mitigación: VLAN de gestión aislada, jump host, ACLs, monitoreo, rotación de credenciales.
- **Credenciales por equipo vs. credenciales centralizadas**: ¿Cómo se almacenan y rotan?
- **Actualizaciones de firmware**: ¿Hay proceso? ¿Se audita versión contra CVE?
- **Acceso desde backend**: ¿Está aislado de la red pública? ¿El backend puede hablar con los equipos sin exponerlos?
- **Registro de cambios**: ¿Queda auditoría de quién cambió qué configuración y cuándo?

**Excepción típica justificable**: Usar protocolo legacy porque el hardware no soporta otro.
**Mitigación obligatoria**: Aislar ese tráfico en VLAN de gestión, sin acceso a internet, con monitoreo y registro.
**No justificable**: Dejar el protocolo legacy expuesto a internet.

### 5.6 Integraciones con Billing, Pasarelas de Pago y Sistemas Externos

**Auditar**:

- **Idempotencia en cobros**: ¿Si se reintenta una petición, se puede cobrar dos veces?
- **Reintentos y colas**: ¿Cómo se manejan fallos de red con la pasarela?
- **Conciliación**: ¿Existe proceso que compare lo cobrado con lo facturado?
- **Validación de respuestas**: ¿Se confía ciegamente en el JSON del proveedor? Debe validarse esquema y tipos.
- **Contratos de datos**: ¿Qué campos viajan? ¿Se envían datos sensibles de más?
- **Cumplimiento PCI-DSS**: Si se almacenan o transmiten datos de tarjeta. Si el ISP no es PCI-DSS, no debe tocar datos de tarjeta; debe delegar en pasarela certificada.

**Excepción típica justificable**: La única pasarela local no soporta idempotencia.
**Mitigación**: Control de reintentos del lado del ISP, conciliación manual/automática, bloqueo de doble cobro.
**No justificable**: Guardar números de tarjeta en la BD del ISP.

### 5.7 Migración de Datos Históricos (Data Refactoring)

Cuando se cambia el esquema —por ejemplo, de `usuarios.id_rol` 1:N a `usuario_rol` N:M— no basta con el DDL. Auditar el plan de migración de datos vivos:

- **Respaldo completo y verificable** antes de migrar.
- **Script idempotente** que pueda re-ejecutarse sin duplicar ni corromper.
- **Transformación de datos**: Mapeo explícito de roles viejos a nuevos, valores por defecto seguros, registros huérfanos.
- **Validación pre y post**: Conteos, integridad referencial, muestras de datos críticos, conciliación de facturación.
- **Rollback probado**: No basta con decir "se restaura el backup"; debe probarse en staging.
- **Ventana de mantenimiento y comunicación**: En ISP, afectar facturación o portal de clientes requiere coordinación.
- **Auditoría de la migración**: Quién ejecutó, cuándo, qué versión del script, resultado.

**Criterio de aceptación**: La migración no se aprueba si no hay evidencia de prueba en staging con datos representativos y rollback exitoso.

### 5.8 Resiliencia, Timeouts y Límites Operativos

**Auditar en backend y en integraciones**:

- Timeouts explícitos en consultas a BD, llamadas HTTP a equipos de red, pasarelas y APIs externas.
- Circuit breakers o degradación controlada cuando un servicio externo falla.
- Throttling y rate limiting en endpoints críticos: login, portal de clientes, APIs de facturación.
- Colas para operaciones no inmediatas: notificaciones, aprovisionamiento, conciliación.
- Límites de recursos: conexiones por usuario, tamaño de payload, tiempo máximo de ejecución.
- Monitoreo y alertas: ¿Cómo se detecta que un equipo de red no responde o que la cola crece?

**No justificable**: Backend que espera indefinidamente a un equipo de red.
**Excepción justificable**: Equipo legacy que no soporta conexión persistente; se mitiga con timeout agresivo, reintentos y cola.

### 5.9 Dependencias, Licencias y Cadena de Suministro

**Auditar**:

- Versiones de librerías contra CVE conocidas (SCA).
- Licencias: GPL, AGPL, LGPL, propietarias. Si el ISP distribuye software o lo integra con su plataforma, una licencia restrictiva puede ser problema legal.
- Bloqueo en CI: Fallar el build si aparece una dependencia con vulnerabilidad crítica o licencia no aprobada.
- **SBOM (Software Bill of Materials)**: Inventario de componentes, idealmente en formato estándar (SPDX, CycloneDX).
- Dependencias abandonadas: Sin mantenimiento, sin parches de seguridad.

**Excepción justificable**: Dependencia con licencia no ideal pero sin alternativa funcional; se documenta y se aísla.
**No justificable**: Dependencia con CVE crítico conocido y parche disponible.

### 5.10 Validación de Fronteras y Contratos

**Auditar**:

- **Content-Type estricto en APIs**: Rechazar si no es application/json cuando corresponde.
- **Validación de esquema en respuestas de terceros**: No confiar en que el proveedor siempre devuelve lo mismo.
- **Contratos versionados**: Si cambia la API externa, ¿cómo se entera el ISP?
- **Límites de tamaño y tasa**: Evitar que un tercero o un cliente sature el backend.
- **Datos de entrada y salida**: Whitelist, no blacklist; validación de tipos, rangos y formatos.

### 5.11 Proceso de Excepción Justificada y ADR

Cuando un hallazgo no puede corregirse por limitación real, se abre un ADR (Architecture Decision Record) con contenido mínimo:

- **ID y fecha**
- **Hallazgo técnico**: Qué estándar o control no se cumple.
- **Alcance**: Qué componente, endpoint, integración o tabla afecta.
- **Causa raíz**: Limitación de hardware, proveedor, regulatoria, presupuestaria o de plazo.
- **Impacto en negocio**: Disponibilidad, PII, facturación, cumplimiento, reputación.
- **Riesgo aceptado**: Descripción explícita del riesgo.
- **Mitigación compensatoria**: Control alternativo que reduce probabilidad o impacto.
- **Responsables**: Tech Lead, Operaciones de Red, Seguridad, Gerencia.
- **Fecha de revisión / caducidad**: No puede ser indefinida.
- **Plan de salida**: Cómo y cuándo se espera eliminar la excepción.
- **Firma / aprobación**: Quién acepta el riesgo.

**Regla de oro**: Una excepción sin mitigación, sin responsable y sin fecha de revisión no es una excepción; es una vulnerabilidad aceptada por negligencia.

### 5.12 Matriz de Decisión para Excepciones

| Situación | ¿Excepción? | Acción |
|---|---|---|
| Afecta línea roja (PII, aislamiento, SoD, facturación, credenciales) | No | Corregir antes de continuar |
| Limitación de hardware de red no actualizable | Sí, con mitigación | ADR + VLAN aislada + monitoreo + revisión |
| Proveedor externo único sin idempotencia | Sí, con mitigación | ADR + conciliación + control de reintentos |
| Deuda técnica por plazo de negocio | Sí, temporal | ADR + plan de refactor + fecha |
| Vulnerabilidad crítica con parche disponible | No | Corregir |
| Licencia restrictiva sin alternativa | Sí, documentada | ADR + aislamiento legal/técnico |
| Falta de test en lógica crítica | No | Corregir |
| Bajo impacto, costo alto, sin riesgo de línea roja | Sí, documentada | ADR simple + revisión |

---

## 6. Rúbrica de Conformidad

### 6.1 Jerarquía de Requisitos

Para cada ítem auditado, se debe clasificar su nivel de obligatoriedad:

| Nivel | Etiqueta | Significado | Consecuencia de Incumplimiento |
|---|---|---|---|
| MUST / SHALL | Obligatorio | Requisito absoluto del estándar | Bloqueante: no se aprueba la fase |
| SHOULD | Recomendado | Buena práctica, no obligatoria | Penalización en puntuación, no bloqueo |
| MAY | Opcional | Mejora discrecional | No penaliza, se registra como observación |
| N/A | No aplicable | El requisito no aplica al sistema auditado | Requiere justificación explícita |

### 6.2 Escala de Puntuación por Capa

Cada capa se puntúa de 0 a 100 según el porcentaje de requisitos MUST cumplidos, ponderado por la criticidad de los requisitos SHOULD:

| Rango | Clasificación | Significado |
|---|---|---|
| 95–100 | Excelente | Conformidad total, sin hallazgos críticos ni altos |
| 85–94 | Bueno | Conformidad sustancial, hallazgos medios menores |
| 70–84 | Aceptable con reservas | Hallazgos altos que requieren plan de corrección |
| 50–69 | Deficiente | Hallazgos críticos o múltiples altos; requiere corrección antes de producción |
| 0–49 | No aceptable | No conformidad estructural o líneas rojas comprometidas |

### 6.3 Umbral de Aceptación Global

El sistema se considera conforme si y solo si:

1. **Fase 1 (Estructural)**: 100% de requisitos MUST cumplidos en todos los patrones formales invocados. Cualquier incumplimiento MUST es bloqueante.
2. **Fase 2 (Interna)**: Puntuación ≥ 70 en cada capa auditada, sin hallazgos críticos sin resolver.
3. **Fase 3 (Contexto ISP)**: Ninguna línea roja comprometida sin corrección. Las excepciones justificadas deben tener ADR completo con mitigación, responsable y fecha de revisión.

---

## 7. Protocolo de Evidencia

### 7.1 Principios de Evidencia

Toda evidencia debe ser **suficiente** (cantidad adecuada para sustentar el hallazgo), **apropiada** (relevante y confiable), **reproducible** (otro auditor puede llegar a la misma conclusión) y **trazable** (vinculada al hallazgo específico). Estos principios son consistentes con ISO 19011, que establece que los auditores deben recopilar evidencia utilizando métodos de muestreo apropiados y triangular la información de al menos tres fuentes cuando sea posible.

### 7.2 Formato de Registro de Hallazgo

Cada hallazgo debe documentarse con la siguiente estructura:

```
ID del Hallazgo: [AUD-YYYY-NNN]
Fecha de Detección: [YYYY-MM-DD]
Auditor: [Nombre/Herramienta]
Capa Afectada: [1-10]
Pasada Metodológica: [1-6]

ESTÁNDAR VIOLADO:
- Documento: [Nombre completo del estándar]
- Edición/Año: [Ej. ISO/IEC 25010:2023]
- Sección/Cláusula: [Ej. §8.3.2]
- Requisito: [MUST/SHOULD/MAY]

EVIDENCIA:
- Archivo: [Ruta exacta del archivo]
- Línea(s): [Rango de líneas]
- Fragmento de código: [Código relevante]
- Hash del commit: [SHA-256 del commit auditado]
- Método de detección: [Análisis estático / Prueba dinámica / Revisión manual]
- Herramienta utilizada: [Nombre y versión]

CLASIFICACIÓN:
- Severidad: [Crítico / Alto / Medio / Bajo]
- Dominio ISP: [Disponibilidad / PII / Facturación / Cumplimiento / Aislamiento / SoD / Continuidad / Reputación]
- Característica ISO 25010: [Adecuación funcional / Eficiencia / Compatibilidad / Interacción / Fiabilidad / Seguridad / Mantenibilidad / Flexibilidad]
- ¿Toca línea roja?: [Sí / No]

ANÁLISIS:
- Descripción del hallazgo: [Explicación técnica]
- Impacto potencial: [Consecuencias si no se corrige]
- Causa raíz: [Por qué ocurrió]

DECISIÓN:
- Acción: [Corregir / Excepción justificada / Deuda técnica]
- ADR asociado: [ID del ADR si aplica]
- Responsable: [Quién debe corregir]
- Fecha límite: [YYYY-MM-DD]
```

### 7.3 Anonimización de PII en Reportes

Los reportes de auditoría que se compartan con terceros o se incluyan en la tesis deben anonimizar cualquier PII encontrada en el código o en los datos de prueba. Los fragmentos de código que contengan datos de clientes (nombres, cédulas, direcciones, teléfonos, IPs) deben reemplazarse con marcadores como `[PII_REDACTED]`. Esta práctica es consistente con las directrices de protección de datos aplicables a organizaciones de telecomunicaciones.

### 7.4 Versionado del Informe

Cada informe de auditoría debe especificar la versión exacta del sistema auditado (hash del commit, tag de release o número de versión). Si el sistema cambia después de la auditoría, se requiere una re-auditoría completa o parcial según el alcance del cambio.

---

## 8. Estrategia de Muestreo

### 8.1 Justificación del Muestreo

No es práctico ni costo-efectivo auditar la totalidad de un sistema. ISO 19011 establece que el muestreo es requerido cuando no es costo-efectivo o práctico examinar toda la información disponible durante una auditoría, y que el número de muestras depende de la complejidad del proceso auditado y de la calidad de la información recibida.

### 8.2 Criterios de Selección de Muestra

La muestra debe seleccionarse por **criticidad**, no por conveniencia. Los criterios de priorización son:

| Prioridad | Criterio | Ejemplo |
|---|---|---|
| 1 (Máxima) | Endpoints que tocan líneas rojas | Autenticación, autorización, facturación, datos PII |
| 2 | Endpoints con acceso a BD sin parametrizar | Consultas SQL crudas, concatenación de strings |
| 3 | Integraciones con hardware de red | Conexiones a OLTs, routers, switches |
| 4 | Integraciones con sistemas externos | Pasarelas de pago, APIs de terceros |
| 5 | Endpoints de alto tráfico | Portal de clientes, panel de administración |
| 6 | Módulos con historial de bugs | Código con deuda técnica documentada |
| 7 | Funcionalidad nueva sin auditoría previa | Features recién implementadas |
| 8 | Resto del sistema | Muestreo aleatorio estratificado |

### 8.3 Métodos de Muestreo

Se admiten dos métodos, consistentes con las prácticas de auditoría de sistemas de información:

- **Muestreo estadístico**: Cuando la población es homogénea y se requiere inferencia cuantitativa. Incluye muestreo aleatorio simple, sistemático y estratificado.
- **Muestreo no estadístico (por juicio)**: Cuando el auditor utiliza su experiencia, criterio y conocimiento del sistema para seleccionar la muestra. Es el método predominante en auditorías de software, donde la criticidad de los endpoints varía significativamente.

### 8.4 Declaración de Alcance

El informe de auditoría debe declarar explícitamente:

- **Universo auditado**: Total de archivos, endpoints, tablas y componentes del sistema.
- **Muestra seleccionada**: Cantidad y criterio de selección.
- **Cobertura**: Porcentaje del universo representado en la muestra.
- **Justificación**: Por qué la muestra es representativa o por qué se priorizó ciertos componentes.
- **Limitaciones**: Qué componentes no fueron auditados y por qué.

---

## 9. Taxonomía de Severidad

### 9.1 Criterios de Clasificación

| Severidad | Criterio Técnico | Criterio de Negocio ISP | Tiempo de Corrección |
|---|---|---|---|
| **Crítico** | Toca línea roja; explotable sin autenticación; inyección SQL; bypass de autorización; PII expuesta | Compromete datos de clientes, facturación o disponibilidad del servicio | Inmediato (bloqueante) |
| **Alto** | Explotable con autenticación básica; afecta integridad de datos; falta de enforcement de permisos; sin transacciones en operaciones críticas | Puede causar fraude interno, cobros erróneos o degradación del servicio | ≤ 7 días |
| **Medio** | Degrada mantenibilidad; viola defensa en profundidad; falta de validación en campos no críticos; logging insuficiente | Impacto operativo menor; no afecta líneas rojas | ≤ 30 días |
| **Bajo** | Estilo, nomenclatura, documentación faltante, código muerto | Sin impacto operativo directo | Próximo ciclo de mantenimiento |

### 9.2 Proceso de Clasificación

La clasificación debe realizarse en dos dimensiones independientes: severidad técnica (basada en explotabilidad e impacto técnico) y severidad de negocio (basada en impacto operativo ISP). Un hallazgo con severidad técnica "Medio" pero que afecta un dominio de línea roja se eleva a "Crítico" para efectos de priorización.

---

## 10. Protocolo de la Pasada Dinámica (Ejecución Real)

### 10.1 Justificación

La auditoría estática, por rigurosa que sea, es una hipótesis hasta que se confirma en ejecución. La pasada dinámica valida las conclusiones de las pasadas 1–5 mediante la ejecución controlada del sistema en un entorno representativo.

### 10.2 Entorno de Ejecución

El entorno de pruebas debe replicar las condiciones de producción en la medida de lo posible:

- **Datos representativos**: Volumen y variedad similares a producción, anonimizados.
- **Configuración idéntica**: Misma versión de motor de BD, PHP/Node, servidor web, sistema operativo.
- **Conectividad a hardware**: Acceso a equipos de red simulados o reales en entorno de laboratorio.
- **Aislamiento**: El entorno de pruebas no debe afectar producción ni datos reales de clientes.

### 10.3 Herramientas y Técnicas

| Tipo de Prueba | Herramientas Sugeridas | Estándar de Referencia |
|---|---|---|
| Análisis dinámico de seguridad | OWASP ZAP, Burp Suite, Nikto | OWASP ASVS 5.0, OWASP Testing Guide |
| Pruebas de carga y estrés | k6, JMeter, Locust | ISO/IEC 25010 (Eficiencia de desempeño) |
| Pruebas de API | Postman/Newman, REST Assured | OpenAPI 3.x, RFC 7231 |
| Pruebas de penetración | Metasploit, SQLMap, custom scripts | PTES (Penetration Testing Execution Standard) |
| Monitoreo de runtime | OpenTelemetry, Prometheus, Grafana | Google SRE (SLI/SLO/SLA) |

### 10.4 Casos de Prueba Adversariales Mínimos

- Intento de acceso a endpoint sin autenticación.
- Intento de acceso a recurso de otro usuario cambiando ID (BOLA/IDOR).
- Inyección SQL en cada campo de entrada.
- XSS reflejado y almacenado en cada campo que se renderice.
- Modificación de parámetros de facturación sin autorización.
- Saturación de endpoint de login con intentos fallidos.
- Timeout de conexión a equipo de red simulando fallo.
- Reintento de petición de pago para verificar idempotencia.
- Envío de Content-Type incorrecto a API JSON.
- Modificación de payload JWT (si aplica).

### 10.5 Criterio de Aceptación de Runtime

La pasada dinámica se considera superada si:

- No se reproduce ningún hallazgo crítico de las pasadas estáticas.
- Los tiempos de respuesta cumplen los SLI definidos (ej. < 500ms para endpoints de consulta).
- El sistema se degrada controladamente ante fallos de servicios externos.
- Los logs de auditoría registran correctamente cada operación sensible.
- Las mitigaciones de excepciones justificadas funcionan según lo documentado en el ADR.

---

## 11. Auditoría Asistida por Inteligencia Artificial

### 11.1 Modelo de Auditoría Asistida

El marco está diseñado para ser ejecutado por una inteligencia artificial bajo supervisión humana. La metodología de la industria se conoce como **AICR (AI-assisted Code Review)** o **DevSecOps con agentes de IA**, y se divide en fases secuenciales:

**Fase 1 — Ingesta y Contextualización**: Se cargan en la herramienta las políticas de la organización (estándares a cumplir, arquitectura objetivo, reglas de negocio específicas del ISP) y se indexa el repositorio completo para entender dependencias y contexto.

**Fase 2 — Análisis Estático y Semántico**: La IA ejecuta análisis AST (Abstract Syntax Tree) para detectar patrones estructurales, y análisis semántico con LLMs para comprender la intención del código y detectar violaciones arquitectónicas o de lógica de negocio.

**Fase 3 — Generación de Reporte de Hallazgos**: La IA clasifica los hallazgos por severidad, tipo (bug, vulnerabilidad, deuda técnica) y dominio de negocio impactado, y propone correcciones cuando es posible.

**Fase 4 — Gobernanza Humana**: El Tech Lead o auditor de seguridad revisa el reporte, descarta falsos positivos, aprueba correcciones y firma las excepciones justificadas.

### 11.2 Diseño de Prompts e Instrucciones por Pasada

La IA debe recibir instrucciones específicas para cada pasada metodológica. El diseño de prompts debe incluir:

- **Contexto del dominio**: Explicación de qué es un ISP, cuáles son sus líneas rojas, qué equipos utiliza.
- **Estándares aplicables**: Lista de estándares con número de edición y secciones relevantes.
- **Formato de salida**: Estructura exacta del registro de hallazgo (sección 7.2).
- **Criterios de clasificación**: Taxonomía de severidad y dominios de impacto.
- **Instrucción de evidencia**: Que cada hallazgo incluya archivo, línea, fragmento y hash.

### 11.3 Verificación de Hallazgos

Los hallazgos de la IA deben verificarse contra fuentes primarias:

- **Código fuente**: El fragmento reportado debe existir en el archivo y línea indicados.
- **Estándar citado**: La sección y edición del estándar deben ser correctas y verificables.
- **Explotabilidad**: Un hallazgo de seguridad debe ser reproducible en entorno controlado.

### 11.4 Gestión de Falsos Positivos

La literatura reporta tasas de falsos positivos en revisión de código con IA que oscilan entre el 5% y el 15%, y estudios sistemáticos demuestran que los LLMs frecuentemente clasifican código correcto como no conforme, especialmente cuando se les pide que proporcionen explicaciones y correcciones detalladas. Los modos de fallo más comunes incluyen:

- **Alucinación de vulnerabilidades**: Reportar problemas que no existen en el código.
- **Inventar números CVE**: Referenciar vulnerabilidades que no corresponden a la librería auditada.
- **Sobre-corrección**: Clasificar como defectuoso código que es correcto en el contexto específico del sistema.

Cada falso positivo debe registrarse con la misma estructura que un hallazgo válido, pero con clasificación "Falso Positivo" y una explicación de por qué la IA erró. Esto alimenta la mejora continua del prompt y del contexto proporcionado.

### 11.5 Limitaciones de la Auditoría Asistida por IA

| Limitación | Descripción | Mitigación |
|---|---|---|
| Alucinación | La IA puede inventar vulnerabilidades o CVE que no existen | Verificar cada hallazgo contra código real y bases CVE oficiales |
| Caja negra | El razonamiento interno del modelo no es transparente | Exigir que cada hallazgo cite evidencia verificable |
| Sesgo de sobre-corrección | La IA tiende a reportar más problemas de los reales cuando se le pide explicaciones | Prompts más específicos; validación humana de falsos positivos |
| Dependencia del prompt | La calidad del resultado varía significativamente según cómo se formula la instrucción | Estandarizar plantillas de prompt por pasada y capa |
| Falta de juicio ético | La IA no puede evaluar el impacto moral o legal de una excepción | La firma del ADR siempre es humana |
| Datos desactualizados | El conocimiento de la IA puede no incluir la última edición de un estándar | Proporcionar el texto del estándar en el contexto del prompt |

---

## 12. Limitaciones y Sesgos de la Auditoría

### 12.1 Limitaciones de la Auditoría Estática

Todo lo que precede a la pasada 6 es auditoría estática: se lee código y esquema, se razona sobre ellos, no se ejecuta nada. Las conclusiones de las pasadas 1–5 son hipótesis educadas hasta que la pasada 6 las confirma en runtime. Esta limitación debe declararse explícitamente en cada informe.

### 12.2 Sesgos del Auditor

| Sesgo | Descripción | Mitigación |
|---|---|---|
| **Sesgo de confirmación** | El auditor busca evidencia que confirme lo que ya espera encontrar | Auditorías ciegas; revisión por pares; uso de múltiples pasadas |
| **Sesgo de experiencia** | El auditor sobrevalora tecnologías que conoce y subestima las que no | Declarar explícitamente las áreas de competencia del auditor |
| **Sesgo de automatización** | El auditor favorece los resultados de la herramienta incluso cuando hay inconsistencias visibles | Validación humana obligatoria; triangulación de fuentes |
| **Sesgo de familiaridad** | La cercanía con el equipo auditado reduce la objetividad | Auditor independiente del equipo de desarrollo |

### 12.3 Independencia del Auditor

Si el auditor es la misma persona que programó el sistema, la independencia se reduce significativamente. La literatura señala que la independencia de auditoría se ve afectada cuando el auditor emplea programadores del equipo auditado o utiliza herramientas compradas por el auditado. En el contexto de una tesis, esto debe declararse explícitamente y mitigarse mediante:

- Revisión por pares académicos.
- Uso de herramientas automatizadas que reduzcan la subjetividad.
- Declaración de conflictos de interés en el informe.

### 12.4 Limitaciones Específicas de la IA como Auditor

| Limitación | Implicación |
|---|---|
| No puede ejecutar el sistema | La pasada 6 queda fuera de su alcance directo |
| No puede firmar excepciones | El ADR requiere firma humana |
| No puede evaluar consecuencias legales | Las líneas rojas regulatorias requieren juicio humano |
| Puede inventar evidencia | Cada hallazgo debe verificarse contra fuente primaria |
| No tiene memoria entre sesiones | El contexto debe reconstruirse en cada auditoría |

---

## 13. Trazabilidad y Gobernanza

### 13.1 Cadena de Trazabilidad

Cada hallazgo y cada excepción deben quedar vinculados a:

- **Requisito o estándar citado**: Documento, edición, sección y nivel de obligatoriedad.
- **Capa afectada**: 1–10 según la taxonomía de este marco.
- **Pasada metodológica**: 1–6 según la taxonomía de este marco.
- **Dominio de negocio ISP impactado**: Disponibilidad, PII, facturación, cumplimiento, aislamiento, SoD, continuidad, reputación.
- **Característica ISO/IEC 25010 impactada**: Una o más de las nueve características.
- **Decisión**: Corregir, excepción justificada, deuda técnica.
- **ADR asociado**: Si aplica, con ID y enlace al documento.

### 13.2 Registro de Auditoría

El informe final debe incluir un registro de auditoría que documente:

- **Fecha de inicio y fin**: Duración total de la auditoría.
- **Alcance**: Universo auditado y muestra seleccionada.
- **Metodología aplicada**: Pasadas y capas cubiertas.
- **Herramientas utilizadas**: Versiones exactas.
- **Auditores**: Humanos y/o IA que participaron.
- **Limitaciones declaradas**: Qué no se pudo auditar y por qué.
- **Versión del sistema**: Hash del commit o tag de release.

### 13.3 Gobernanza del Riesgo

El marco establece que el riesgo no se elimina, se gestiona. La gobernanza del riesgo incluye:

- **Identificación**: Todo hallazgo debe clasificarse por dominio de impacto.
- **Evaluación**: Severidad técnica + severidad de negocio.
- **Tratamiento**: Corregir, mitigar (excepción con ADR), aceptar (deuda técnica con plan) o transferir (ej. seguro cibernético, si aplica).
- **Monitoreo**: Revisión periódica de excepciones y deuda técnica.
- **Comunicación**: Reporte a stakeholders según criticidad.

---

## 14. Actualización del Protocolo de Aplicación

Cuando se audite un sistema, el protocolo será:

1. **Identificar el patrón o estándar formal invocado** (RBAC, REST, ACID, OWASP ASVS, etc.) y verificar conformidad estructural contra su definición primaria. Si no pasa, se detiene la auditoría (Fail-Fast).

2. **Recorrer las capas relevantes** de la metodología (datos, backend, frontend, seguridad, arquitectura, testing, infraestructura, documentación, calidad) y auditar consistencia interna y calidad técnica, aplicando la matriz 6×10 de la sección 4.1.

3. **Evaluar contexto operativo ISP**: Clasificar cada hallazgo por impacto en disponibilidad, PII, facturación, cumplimiento, aislamiento de clientes, SoD y continuidad.

4. **Aplicar líneas rojas**: Si el hallazgo toca una línea roja, no hay excepción; se corrige.

5. **Gestionar excepciones justificadas**: Si hay limitación real, documentar ADR con mitigación, responsable y fecha de revisión.

6. **Citar el estándar exacto** (documento, edición, sección) de cada hallazgo, no una referencia genérica.

7. **Clasificar cada hallazgo contra ISO/IEC 25010:2023** para que quede trazable en el marco de calidad global.

8. **Entregar matriz de cumplimiento + roadmap priorizado**, separando: correctivo obligatorio, excepción con mitigación, deuda técnica planificada.

9. **Declarar limitaciones de la auditoría estática** y recomendar la pasada dinámica (testing real, ISO/IEC/IEEE 29119) que el entorno del ISP debe ejecutar.

10. **Documentar evidencia completa**: Cada hallazgo con archivo, línea, hash, método de detección y herramienta utilizada.

---

## 15. Referencias Normativas

- **ISO/IEC 25010:2023** — Systems and software Quality Requirements and Evaluation (SQuaRE) — Product quality model.
- **ISO/IEC 27011:2024** — Information security controls based on ISO/IEC 27002 for telecommunications organizations.
- **ISO/IEC 27001** — Information security management systems.
- **ISO/IEC/IEEE 29119** — Software and systems engineering — Software testing.
- **ISO 19011** — Guidelines for auditing management systems.
- **ITU-T X.1051** — Information security management guidelines for telecommunications organizations based on ISO/IEC 27002.
- **ANSI/INCITS 359** — Role-Based Access Control (RBAC).
- **NIST SP 800-63B Rev. 4** — Digital Identity Guidelines: Authentication and Lifecycle Management.
- **NIST SP 800-57** — Recommendation for Key Management.
- **OWASP ASVS 5.0** — Application Security Verification Standard.
- **OWASP Top 10** — Top 10 Web Application Security Risks.
- **CWE (MITRE)** — Common Weakness Enumeration.
- **CVE (MITRE)** — Common Vulnerabilities and Exposures.
- **STRIDE (Microsoft)** — Threat modeling methodology.
- **RFC 7231** — Hypertext Transfer Protocol (HTTP/1.1): Semantics and Content.
- **RFC 6749** — The OAuth 2.0 Authorization Framework.
- **PCI-DSS** — Payment Card Industry Data Security Standard.
- **12-Factor App** — Methodology for building software-as-a-service apps.
- **DAMA-DMBOK2** — Data Management Body of Knowledge.

---

*Fin del Capítulo de Metodología de Auditoría*