# ADR 011 — Notificaciones como servicio transversal

- **Estado**: aceptado
- **Fecha**: 2026-10-12
- **Contexto**: la defensa exige plataforma funcional. Las notificaciones
  son parte del flujo cliente↔staff, no decoración.

## Contexto

La tabla `notificaciones` existe desde Fase 0 pero nunca se ha usado:

```sql
CREATE TABLE notificaciones (
  id_notificacion int PK,
  titulo varchar(100),
  mensaje varchar(255),
  fecha_envio datetime DEFAULT CURRENT_TIMESTAMP,
  estado enum('leido','no leido') DEFAULT 'no leido',
  id_cliente int NOT NULL,   -- apunta a clientes
  id_usuario int NULL
);
Está diseñada para notificar a clientes (id_cliente NOT NULL). No
modela notificaciones al staff.

En paralelo, el hallazgo F18 (AUDITORIA_2026.md) señala que el panel
admin no tiene una campana de notificaciones para el staff.

El roadmap tenía esto a medias:

Spec 066 → diferido (movido a 085).

Spec 085 → FASE B, alcance declarado como UI (campana, breadcrumbs,
búsqueda global).

Spec 108 → FASE C, agrega timestamp de lectura.

Ninguna spec modelaba el flujo real de eventos → notificación →
destinatario.

Decisión
Las notificaciones se tratan como servicio transversal, con dos
bandejas separadas:

Bandeja	Destinatario	Dónde se ve	Tabla
Staff inbox	Staff	Campana en header del panel admin	notificaciones_staff (nueva)
Client inbox	Cliente	Campana en portal_cliente.php	notificaciones (existente, se puebla)
Servicio central:

Función notificar(PDO $pdo, string $tipoEvento, int $idDestinatario, array $datos)
en conexion.php o servicios/notificaciones.php (según Fase). Decide
a qué bandeja va según el tipo de evento y persiste la notificación.

Eventos soportados:

Evento	Al staff	Al cliente
Cliente abre ticket	✅	✅
Staff responde ticket	❌	✅
Ticket cerrado	❌	✅
Cliente sube comprobante	✅	✅
Admin valida pago	❌	✅
Admin rechaza pago	❌	✅
Factura emitida	❌	✅
Factura vence	✅	✅
Tarea asignada a staff	✅	❌
Migración de notificaciones:

Añadir tipo_evento varchar(50).

Añadir url_destino varchar(255) — a dónde lleva el click.

Añadir leida_en datetime NULL — reemplaza estado enum (más preciso).
Se conserva estado por retrocompatibilidad hasta que Fase C
(spec 108) lo elimine.

UI:

Admin: campana en index.html header. Badge con contador de no leídas.
Dropdown con últimas 10. Click → navega al url_destino y marca
como leída.

Cliente: mismo patrón en portal_cliente.php.

Endpoints:

api_notificaciones.php (staff) — requireStaffAuth.

api_portal_notificaciones.php (cliente) — requireClientAuth.

Alternativas descartadas
Email/SMS como única vía
Razón de descarte: email y SMS requieren integración externa
(SMTP, Twilio, pasarela SMS). Fuera de alcance de tesis (aunque ver
Bloque 24 para email). La bandeja in-app cubre el flujo
funcional y es demostrable.

Solo notificaciones al cliente (sin bandeja staff)
Razón de descarte: el staff necesita enterarse de eventos
relevantes (ticket nuevo, pago pendiente, factura vencida). Sin
bandeja staff, el staff tiene que refrescar manualmente cada módulo.
Es lo que la industria resuelve con la campana.

Solo notificaciones al staff (sin bandeja cliente)
Razón de descarte: el cliente debe enterarse de la respuesta a su
ticket, de la validación de su pago, del vencimiento de su factura.
Sin portal + notificaciones, el cliente tiene que llamar. Es
antiautomatización.

Usar una única tabla con tipo_destinatario
Razón de descarte: la tabla notificaciones tiene FK a
clientes. Añadir polimorfismo (tipo_destinatario='staff'|'cliente')
rompe la integridad referencial. Dos tablas con FK real es la
práctica estándar (Splynx, Sonar).

Consecuencias
Positivas
notificaciones deja de ser tabla muerta.

El staff recibe avisos operativos sin refrescar manualmente.

El cliente recibe feedback del estado de su ticket/pago/factura.

Alineado con Splynx, Sonar, ISPsystem.

Habilita el flujo completo end-to-end que la defensa puede demostrar.

Negativas
Añade ~3 sesiones al cierre.

Cada endpoint que dispara un evento (tickets, pagos, facturas,
tareas) debe llamar a notificar(). Cobertura despareja sería un bug.

Purga no definida: las notificaciones leídas crecen indefinidamente.
Se difiere a Fase C.

Neutras
No hay cambio en los módulos existentes salvo llamadas a notificar().

La lógica de negocio sigue en capa de aplicación (patrón del proyecto).

Referencias
docs/ROADMAP.md — Bloque 20.

bd_redytelca.sql — tabla notificaciones.

docs/AUDITORIA_2026.md — hallazgo F18.

Splynx — sistema de mensajes: email, SMS, tickets, "News in
customer portal".

Sonar — "Triggered Messages" automáticos sobre due dates y
eventos operativos.

ISPsystem/BILLmanager — notificaciones a usuarios por eventos
importantes (email, SMS, messenger).