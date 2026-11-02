<?php
// services/NotificacionService.php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\NotificacionRepository;
use App\Repositories\NotificacionStaffRepository;
use App\Repositories\UsuarioRolRepository;

/**
 * Servicio transversal de notificaciones.
 *
 * Escribe en dos bandejas independientes:
 *   - `notificaciones`       → bandeja del cliente (portal).
 *   - `notificaciones_staff` → bandeja del staff (campana del panel admin).
 *
 * Patrón: Observer síncrono dentro de la transacción del cambio principal.
 * Si `notificar()` falla, el cambio principal hace rollback. Es el
 * equivalente simplificado del Transactional Outbox Pattern (AWS
 * Prescriptive Guidance) sin cola externa, coherente con la constitución
 * §1 (sin dependencias).
 *
 * Fuente de verdad de "leída": `leida_en IS NULL` (timestamp), no el enum
 * `estado`. El enum se mantiene sincronizado por retrocompatibilidad con
 * ADR 011. Deuda NOTIF-ESTADO-LEGACY a Fase 7.
 *
 * Destinatarios staff: todos los usuarios activos con rol Administrador
 * (rol 1), excluyendo al generador del evento (Krayin: "Nobody is told
 * about something they did themselves"). Deuda NOTIF-ROL-GRANULARIDAD
 * a Fase 7.
 *
 * URLs de destino (A2-B.5.d, 2026-11-01):
 *   - Cliente → `/portal/tickets?id=N`, `/portal/pagos?id=N`,
 *     `/portal/facturas?id=N`. Nunca `/tickets`, `/pagos`, `/facturas`
 *     (esas son rutas del staff; el cliente no tiene acceso a ellas).
 *     Patrón Zendesk (`/hc/requests/{id}` vs `/agent/tickets/{id}`),
 *     Salesforce (Experience Cloud vs consola interna), ServiceNow
 *     (`/sp` vs `/nav_to.do`), Splynx (`/portal/` vs `/admin/`).
 *   - Staff → `/tickets?id=N`, `/pagos?id=N`, `/facturas?id=N`.
 *     Sin cambios.
 *
 * A2-B.5.b (2026-11-01):
 *   - Nuevo evento `EVENTO_TICKET_RESPONDIDO_POR_CLIENTE` cuando el
 *     cliente responde un ticket. Notifica SOLO a admins (rol 1).
 *     NO notifica al cliente (ya sabe que respondió). Patrón
 *     Zendesk/Jira SM/Salesforce/ServiceNow/Splynx.
 */
final class NotificacionService extends BaseService
{
    public const EVENTO_TICKET_CREADO       = 'ticket_creado';
    public const EVENTO_TICKET_RESPONDIDO   = 'ticket_respondido';
    public const EVENTO_TICKET_CERRADO      = 'ticket_cerrado';
    public const EVENTO_TICKET_RESPONDIDO_POR_CLIENTE = 'ticket_respondido_por_cliente';
    public const EVENTO_PAGO_SUBIDO         = 'pago_subido';
    public const EVENTO_PAGO_VALIDADO       = 'pago_validado';
    public const EVENTO_PAGO_RECHAZADO      = 'pago_rechazado';
    public const EVENTO_FACTURA_EMITIDA     = 'factura_emitida';
    public const EVENTO_FACTURA_VENCIDA     = 'factura_vencida';

    private const ROL_ADMIN_ID = 1;

    /** @var NotificacionRepository */
    private NotificacionRepository $notifClienteRepo;

    /** @var NotificacionStaffRepository */
    private NotificacionStaffRepository $notifStaffRepo;

    /** @var UsuarioRolRepository */
    private UsuarioRolRepository $usuarioRolRepo;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->notifClienteRepo = new NotificacionRepository($pdo);
        $this->notifStaffRepo   = new NotificacionStaffRepository($pdo);
        $this->usuarioRolRepo   = new UsuarioRolRepository($pdo);
    }

    /**
     * Escribe una notificación en la bandeja indicada.
     *
     * @param string $tipoEvento       Una de las constantes EVENTO_*.
     * @param string $destinatarioTipo 'staff' | 'cliente'.
     * @param int    $destinatarioId   usuario_id (staff) o cliente_id (cliente).
     * @param array  $datos            ['titulo' => string, 'mensaje' => string, 'url_destino' => ?string]
     * @return int ID de la notificación creada.
     * @throws \InvalidArgumentException Si el tipo de destinatario no es válido.
     */
    public function notificar(
        string $tipoEvento,
        string $destinatarioTipo,
        int $destinatarioId,
        array $datos = []
    ): int {
        if ($destinatarioTipo !== 'staff' && $destinatarioTipo !== 'cliente') {
            throw new \InvalidArgumentException("destinatarioTipo debe ser 'staff' o 'cliente'");
        }

        if ($destinatarioId <= 0) {
            throw new \InvalidArgumentException('destinatarioId debe ser mayor a 0');
        }

        $titulo     = trim((string) ($datos['titulo'] ?? ''));
        $mensaje    = trim((string) ($datos['mensaje'] ?? ''));
        $urlDestino = isset($datos['url_destino']) && $datos['url_destino'] !== ''
            ? (string) $datos['url_destino']
            : null;

        if ($titulo === '' || $mensaje === '') {
            throw new \InvalidArgumentException('titulo y mensaje son obligatorios');
        }

        if ($destinatarioTipo === 'staff') {
            return $this->notifStaffRepo->create([
                'titulo'      => $titulo,
                'mensaje'     => $mensaje,
                'tipo_evento' => $tipoEvento,
                'url_destino' => $urlDestino,
                'estado'      => 'no leido',
                'usuario_id'  => $destinatarioId,
            ]);
        }

        return $this->notifClienteRepo->create([
            'titulo'      => $titulo,
            'mensaje'     => $mensaje,
            'tipo_evento' => $tipoEvento,
            'url_destino' => $urlDestino,
            'estado'      => 'no leido',
            'cliente_id'  => $destinatarioId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Eventos de negocio (métodos de conveniencia)
    // ─────────────────────────────────────────────────────────────

    /**
     * Cliente abre ticket (portal). Notifica al cliente y a todos los
     * admins activos excepto al generador (si el ticket lo creó un admin).
     *
     * $urlCliente: ruta del portal de Fase 5 (`/portal/*`).
     * $urlStaff:   ruta del panel admin (`/*`).
     */
    public function eventoTicketCreado(int $ticketId, int $clienteId, string $asunto, ?int $staffGeneradorId = null): void
    {
        $urlCliente = '/portal/tickets?id=' . $ticketId;
        $urlStaff   = '/tickets?id=' . $ticketId;

        // Bandeja del cliente
        $this->notificar(self::EVENTO_TICKET_CREADO, 'cliente', $clienteId, [
            'titulo'      => 'Ticket abierto',
            'mensaje'     => 'Tu ticket "' . $asunto . '" fue registrado. Te responderemos pronto.',
            'url_destino' => $urlCliente,
        ]);

        // Bandeja del staff (todos los admins activos, menos el generador)
        $this->notificarStaffAdmins(
            self::EVENTO_TICKET_CREADO,
            'Nuevo ticket de cliente',
            'El cliente abrió el ticket "' . $asunto . '".',
            $urlStaff,
            $staffGeneradorId
        );
    }

    /**
     * Staff responde ticket. Notifica al cliente dueño del ticket.
     */
    public function eventoTicketRespondido(int $ticketId, int $clienteId, string $asunto, string $previewMensaje, int $staffId): void
    {
        $preview = mb_substr(trim($previewMensaje), 0, 120);
        $this->notificar(self::EVENTO_TICKET_RESPONDIDO, 'cliente', $clienteId, [
            'titulo'      => 'Respuesta a tu ticket',
            'mensaje'     => 'Respondimos tu ticket "' . $asunto . '": ' . $preview,
            'url_destino' => '/portal/tickets?id=' . $ticketId,
        ]);
    }

    /**
     * Ticket cerrado. Notifica al cliente.
     */
    public function eventoTicketCerrado(int $ticketId, int $clienteId, string $asunto): void
    {
        $this->notificar(self::EVENTO_TICKET_CERRADO, 'cliente', $clienteId, [
            'titulo'      => 'Ticket cerrado',
            'mensaje'     => 'El ticket "' . $asunto . '" fue cerrado. Gracias por contactarnos.',
            'url_destino' => '/portal/tickets?id=' . $ticketId,
        ]);
    }

    /**
     * Cliente responde ticket desde el portal. Notifica SOLO a los
     * admins activos (rol 1). NO notifica al cliente (ya sabe que
     * respondió). NO cambia el estado del ticket.
     *
     * Patrón Zendesk/Jira SM/Salesforce/ServiceNow/Splynx: el cliente
     * responde → notificación al staff → el staff decide si mueve el
     * estado. La URL destino es `/tickets?id=N` (ruta del admin, no
     * `/portal/tickets?id=N`; el staff no usa el portal del cliente).
     *
     * A2-B.5.b (2026-11-01).
     *
     * @param int    $ticketId
     * @param int    $clienteId       Dueño del ticket (para logging y contexto).
     * @param string $asunto
     * @param string $previewMensaje  Se trunca a 120 chars.
     * @param int    $idCredencial    Credencial del cliente que respondió.
     *                                Se usa solo para logging (no se persiste
     *                                en la notificación; la tabla
     *                                `notificaciones_staff` no tiene
     *                                `credencial_id`).
     */
    public function eventoTicketRespondidoPorCliente(
        int $ticketId,
        int $clienteId,
        string $asunto,
        string $previewMensaje,
        int $idCredencial
    ): void {
        $preview = mb_substr(trim($previewMensaje), 0, 120);
        $urlStaff = '/tickets?id=' . $ticketId;

        $this->notificarStaffAdmins(
            self::EVENTO_TICKET_RESPONDIDO_POR_CLIENTE,
            'Respuesta de cliente en ticket',
            'El cliente respondió el ticket "' . $asunto . '": ' . $preview,
            $urlStaff,
            null
        );
    }

    /**
     * Cliente sube comprobante de pago (portal). Notifica al cliente y a
     * todos los admins activos excepto al generador.
     *
     * $urlCliente: ruta del portal de Fase 5 (`/portal/*`).
     * $urlStaff:   ruta del panel admin (`/*`).
     */
    public function eventoPagoSubido(int $pagoId, int $clienteId, float $monto, string $moneda, ?int $staffGeneradorId = null): void
    {
        $montoFmt  = number_format($monto, 2);
        $urlCliente = '/portal/pagos?id=' . $pagoId;
        $urlStaff   = '/pagos?id=' . $pagoId;

        $this->notificar(self::EVENTO_PAGO_SUBIDO, 'cliente', $clienteId, [
            'titulo'      => 'Pago registrado',
            'mensaje'     => 'Recibimos tu pago de ' . $moneda . ' ' . $montoFmt . '. Está pendiente de validación.',
            'url_destino' => $urlCliente,
        ]);

        $this->notificarStaffAdmins(
            self::EVENTO_PAGO_SUBIDO,
            'Comprobante de pago pendiente',
            'El cliente subió un comprobante de ' . $moneda . ' ' . $montoFmt . '.',
            $urlStaff,
            $staffGeneradorId
        );
    }

    /**
     * Admin valida pago. Notifica al cliente.
     */
    public function eventoPagoValidado(int $pagoId, int $clienteId, float $monto, string $moneda, int $adminId): void
    {
        $montoFmt = number_format($monto, 2);
        $this->notificar(self::EVENTO_PAGO_VALIDADO, 'cliente', $clienteId, [
            'titulo'      => 'Pago validado',
            'mensaje'     => 'Tu pago de ' . $moneda . ' ' . $montoFmt . ' fue validado. ¡Gracias!',
            'url_destino' => '/portal/pagos?id=' . $pagoId,
        ]);
    }

    /**
     * Admin rechaza pago. Notifica al cliente con el motivo.
     */
    public function eventoPagoRechazado(int $pagoId, int $clienteId, float $monto, string $moneda, int $adminId, string $motivo): void
    {
        $montoFmt = number_format($monto, 2);
        $this->notificar(self::EVENTO_PAGO_RECHAZADO, 'cliente', $clienteId, [
            'titulo'      => 'Pago rechazado',
            'mensaje'     => 'Tu pago de ' . $moneda . ' ' . $montoFmt . ' fue rechazado. Motivo: ' . $motivo,
            'url_destino' => '/portal/pagos?id=' . $pagoId,
        ]);
    }

    /**
     * Factura emitida. Notifica al cliente.
     */
    public function eventoFacturaEmitida(int $facturaId, int $clienteId, string $periodo, float $monto, string $moneda): void
    {
        $montoFmt = number_format($monto, 2);
        $this->notificar(self::EVENTO_FACTURA_EMITIDA, 'cliente', $clienteId, [
            'titulo'      => 'Nueva factura',
            'mensaje'     => 'Se emitió tu factura del periodo ' . $periodo . ' por ' . $moneda . ' ' . $montoFmt . '.',
            'url_destino' => '/portal/facturas?id=' . $facturaId,
        ]);
    }

    /**
     * Factura vencida. Notifica al cliente y a todos los admins activos
     * excepto al generador (materializarVencimientos corre como sistema,
     * sin generador humano).
     *
     * $urlCliente: ruta del portal de Fase 5 (`/portal/*`).
     * $urlStaff:   ruta del panel admin (`/*`).
     */
    public function eventoFacturaVencida(int $facturaId, int $clienteId, string $periodo, float $monto, string $moneda): void
    {
        $montoFmt  = number_format($monto, 2);
        $urlCliente = '/portal/facturas?id=' . $facturaId;
        $urlStaff   = '/facturas?id=' . $facturaId;

        $this->notificar(self::EVENTO_FACTURA_VENCIDA, 'cliente', $clienteId, [
            'titulo'      => 'Factura vencida',
            'mensaje'     => 'Tu factura del periodo ' . $periodo . ' por ' . $moneda . ' ' . $montoFmt . ' está vencida.',
            'url_destino' => $urlCliente,
        ]);

        $this->notificarStaffAdmins(
            self::EVENTO_FACTURA_VENCIDA,
            'Factura vencida',
            'La factura del periodo ' . $periodo . ' del cliente #' . $clienteId . ' venció.',
            $urlStaff,
            null
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────

    /**
     * Notifica a todos los admins activos, excluyendo al generador.
     *
     * @param string   $tipoEvento
     * @param string   $titulo
     * @param string   $mensaje
     * @param string   $urlDestino
     * @param int|null $excluirUsuarioId Usuario que generó el evento (no se notifica a sí mismo).
     */
    private function notificarStaffAdmins(
        string $tipoEvento,
        string $titulo,
        string $mensaje,
        string $urlDestino,
        ?int $excluirUsuarioId
    ): void {
        $adminIds = $this->usuarioRolRepo->findActivosByRolId(self::ROL_ADMIN_ID, $excluirUsuarioId);

        foreach ($adminIds as $adminId) {
            $this->notificar($tipoEvento, 'staff', (int) $adminId, [
                'titulo'      => $titulo,
                'mensaje'     => $mensaje,
                'url_destino' => $urlDestino,
            ]);
        }
    }
}