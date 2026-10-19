# ADR 012 — Alcance del proyecto

- **Estado**: aceptado
- **Fecha**: 2026-10-12
- **Contexto**: cierre de la definición de alcance para la defensa.

## Contexto

Durante el desarrollo del proyecto se identificaron múltiples flujos y
capacidades que no estaban formalmente dentro ni fuera del alcance.
Esto generaba ambigüedad: ¿el portal de cliente es obligatorio? ¿Las
notificaciones? ¿La integración con la red del ISP?

La auditoría (AUDITORIA_2026.md) y el roadmap original no declaraban
explícitamente qué estaba fuera. Los hallazgos ISP05 (inventario HW),
ISP09 (portal cliente) e ISP11 (pedidos) existían sin decisión formal.

## Decisión

### Dentro de alcance (obligatorio para defensa)

**Plataforma web completa:**

- Panel admin (staff): clientes, servicios, facturas, pagos, planes,
  contratos, tickets, tareas, equipos, nodos, OLTs, NAPs, usuarios,
  roles, permisos, empresa.
- **Portal de cliente**: login, facturas, pagos con comprobante,
  tickets, servicios, PDF.
- **Notificaciones**: bandeja staff (campana admin) + bandeja cliente
  (campana portal), disparadas por eventos de negocio.
- **IPAM operativo**: pools, asignaciones, liberaciones (endpoints + UI).
- **Flujo de pedidos/instalaciones**: entidad `Pedido` con estados y
  asignación técnica.
- **Email transaccional**: verificación, reset password, notificaciones.
- **Autoservicio de cliente**: cambio de plan, actualización de datos.
- **Backups + monitoreo básico**: script de backup, health check.
- **Modelo de datos completo**: esquema que refleja todas las entidades
  del dominio ISP, aunque su backend/UI se implemente después.

### Fuera de alcance (exclusiones conscientes)

**1. Integración con red real del ISP.**

Incluye: provisioning automático de ONU/ONT, RADIUS, SNMP, TR-069,
CLI a OLTs, alarmas de red, rate-limiting de ancho de banda, consumo
de tráfico, monitoreo de latencia. Cualquier lectura/escritura a
hardware de red.

**Justificación:** requiere acceso a hardware real (OLTs, routers,
switches), VLAN de gestión, credenciales rotadas, monitoreo 24/7 y
laboratorio de pruebas. El alcance de tesis es el sistema de gestión,
no la integración con hardware. El inventario se gestiona manualmente
vía CRUD.

**2. App móvil técnico.**

Incluye: app nativa (React Native/Flutter), PWA dedicada, flujo de
instalación desde campo.

**Justificación:** requiere build tools (npm, bundlers) que violan la
constitución §1. El staff usa el panel admin desde navegador móvil
(responsive ya presente).

**3. Pasarela de pagos automatizada (Stripe, PayPal, Mercado Pago).**

**Justificación:** el pago manual con comprobante es el estándar en
ISPs venezolanos (Vnet, Nevacom, Power Link, WOW, TV Zamora,
Movilnet). El sistema financiero venezolano no tiene API unificada de
pagos. Pago Móvil y C2P requieren confirmación manual. Zelle no opera
en Venezuela. La validación manual de pagos ya está implementada y es
el flujo funcional completo.

**4. Field Service Management (cuadrillas, rutas, agenda).**

**Justificación:** sistema aparte del panel admin. Requiere app móvil
para técnicos (fuera de alcance) y optimización de rutas.

**5. Gestión de fibra óptica (empalmes, splitters, ductos).**

**Justificación:** requiere hardware y herramientas de campo.

**6. Energía / facilities (UPS, CCTV, sensores).**

**Justificación:** requiere sensores físicos.

**7. Facturación fiscal SENIAT.**

**Justificación:** requiere integración con ente fiscal venezolano.

**8. Cobranza en campo.**

**Justificación:** sistema aparte, requiere app móvil.

**9. Comisiones y ventas (pipeline, leads, metas).**

**Justificación:** no es core ISP.

**10. RRHH (nómina, asistencia, evaluación).**

**Justificación:** no es core ISP.

**11. Marketing / campañas (email masivo, SMS, referidos).**

**Justificación:** no es core ISP.

**12. API pública documentada (OpenAPI, API keys, webhooks).**

**Justificación:** no requerido para tesis.

**13. Workflows / motor de aprobaciones.**

**Justificación:** overkill para el alcance.

**14. BI / ML (data warehouse, predicción churn).**

**Justificación:** no es core ISP.

**15. Alta disponibilidad / failover.**

**Justificación:** infraestructura, no aplicación.

**16. Inventario avanzado (stock mínimo, movimientos de almacén, RMA).**

**Justificación:** solo el flujo de asignación/devolución de equipos va
dentro. El resto es logística.

**17. Compras y proveedores.**

**Justificación:** no es core ISP.

**18. ITIL avanzado (problemas, cambios, CMDB, capacidad).**

**Justificación:** tickets cubren incidentes. El resto es proceso
organizacional.

**19. Gestión documental avanzada (actas, firma digital, versionado).**

**Justificación:** no crítico para defensa.

**20. Multi-idioma (i18n).**

**Justificación:** el sistema es en español únicamente.

## Consecuencias

### Positivas

- Alcance cerrado y documentado.
- El jurado ve una plataforma completa (back-office + portal).
- Las exclusiones están justificadas contra el contexto venezolano.
- No hay limbo: cada flujo está dentro o fuera, con razón.

### Negativas

- El alcance es ambicioso (~22 sesiones más).
- La integración con hardware queda como deuda técnica explícita.
- La app móvil queda como trabajo futuro documentado.

### Neutras

- El roadmap se reestructura con bloques nuevos (19–27).
- Los hallazgos ISP05, ISP09, ISP11 se resuelven formalmente.

## Referencias

- `docs/ROADMAP.md` — bloques 19–27.
- `docs/REFERENCIA.md` §2 — exclusiones.
- `docs/ARQUITECTURA.md` §Integraciones.
- `docs/ADR/010-portal-cliente-separado.md`.
- `docs/ADR/011-notificaciones-servicio.md`.
- **Vnet** — métodos de pago: débito inmediato, pago móvil, tarjeta,
  C2P, efectivo.
- **Nevacom** — billetera, Pago Móvil, Zelle, USDT, transferencia.
- **Movilnet** — C2P con confirmación por SMS.
- **Splynx** — "Payment Proof" como feature estándar.