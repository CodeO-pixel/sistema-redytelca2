<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Repositories\TicketRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\ServicioRepository;
use App\Repositories\CategoriaTicketRepository;
use App\Repositories\UsuarioRepository;
use App\Repositories\TareaRepository;

/**
 * Servicio para la gestión de tickets de soporte.
 */
final class TicketService extends BaseService
{
    /** @var TicketRepository */
    private TicketRepository $ticketRepo;

    /** @var ClienteRepository */
    private ClienteRepository $clienteRepo;

    /** @var ServicioRepository */
    private ServicioRepository $servicioRepo;

    /** @var CategoriaTicketRepository */
    private CategoriaTicketRepository $categoriaRepo;

    /** @var UsuarioRepository */
    private UsuarioRepository $usuarioRepo;

    /** @var TareaRepository */
    private TareaRepository $tareaRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->ticketRepo = new TicketRepository($pdo);
        $this->clienteRepo = new ClienteRepository($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
        $this->categoriaRepo = new CategoriaTicketRepository($pdo);
        $this->usuarioRepo = new UsuarioRepository($pdo);
        $this->tareaRepo = new TareaRepository($pdo);
    }

    /**
     * Crea un nuevo ticket.
     *
     * @param array $datos
     * @return int
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function crear(array $datos): int
    {
        if (!isset($datos['cliente_id']) || !$this->clienteRepo->exists((int) $datos['cliente_id'])) {
            throw new NotFoundException('El cliente especificado no existe.');
        }

        if (isset($datos['servicio_id']) && !$this->servicioRepo->exists((int) $datos['servicio_id'])) {
            throw new NotFoundException('El servicio especificado no existe.');
        }

        if (isset($datos['categoria_id']) && !$this->categoriaRepo->exists((int) $datos['categoria_id'])) {
            throw new NotFoundException('La categoría especificada no existe.');
        }

        if (isset($datos['usuario_creador_id']) && !$this->usuarioRepo->exists((int) $datos['usuario_creador_id'])) {
            throw new NotFoundException('El usuario creador especificado no existe.');
        }

        $this->validarEnums($datos);

        if (!isset($datos['estado']) || !in_array($datos['estado'], ['Abierto', 'En proceso', 'Cerrado'], true)) {
            $datos['estado'] = 'Abierto';
        }

        if (!isset($datos['origen']) || !in_array($datos['origen'], ['portal_cliente', 'backoffice'], true)) {
            $datos['origen'] = 'backoffice';
        }

        unset($datos['sla_vence_en']);

        return $this->ticketRepo->create($datos);
    }

    /**
     * Actualiza un ticket existente.
     *
     * La transición de estado la valida el trigger trg_tickets_validar_transicion.
     * Las transiciones válidas están en la tabla transicion_estado (ver migración 137g-3).
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->ticketRepo->exists($id)) {
            throw new NotFoundException('Ticket no encontrado.');
        }

        if (isset($datos['cliente_id']) && !$this->clienteRepo->exists((int) $datos['cliente_id'])) {
            throw new NotFoundException('El cliente especificado no existe.');
        }

        if (isset($datos['servicio_id']) && !$this->servicioRepo->exists((int) $datos['servicio_id'])) {
            throw new NotFoundException('El servicio especificado no existe.');
        }

        if (isset($datos['categoria_id']) && !$this->categoriaRepo->exists((int) $datos['categoria_id'])) {
            throw new NotFoundException('La categoría especificada no existe.');
        }

        if (isset($datos['usuario_creador_id']) && !$this->usuarioRepo->exists((int) $datos['usuario_creador_id'])) {
            throw new NotFoundException('El usuario creador especificado no existe.');
        }

        $this->validarEnums($datos);
        unset($datos['sla_vence_en']);

        $this->ticketRepo->update($id, $datos);
    }

    /**
     * Elimina un ticket si no tiene tareas derivadas.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function eliminar(int $id): void
    {
        if (!$this->ticketRepo->exists($id)) {
            throw new NotFoundException('Ticket no encontrado.');
        }

        $tareas = $this->tareaRepo->findByTicketId($id);
        if (!empty($tareas)) {
            throw new DomainException('El ticket tiene tareas derivadas, ciérralo en vez de eliminarlo.');
        }

        $this->ticketRepo->delete($id);
    }

    /**
     * Cierra un ticket, añadiendo un detalle a la descripción.
     *
     * @param int $id
     * @param string $detalle
     * @return void
     * @throws NotFoundException
     */
    public function cerrar(int $id, string $detalle): void
    {
        $ticket = $this->ticketRepo->findById($id);
        if (!$ticket) {
            throw new NotFoundException('Ticket no encontrado.');
        }

        if ($ticket['estado'] === 'Cerrado') {
            return;
        }

        $nuevaDescripcion = $ticket['descripcion'] ?? '';
        if (trim($detalle) !== '') {
            $nuevaDescripcion = trim($nuevaDescripcion) !== ''
                ? $nuevaDescripcion . "\n\nCierre: " . trim($detalle)
                : "Cierre: " . trim($detalle);
        }

        $this->ticketRepo->update($id, [
            'estado' => 'Cerrado',
            'descripcion' => $nuevaDescripcion
        ]);
    }

    /**
     * Busca un ticket por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->ticketRepo->findById($id);
    }

    /**
     * Obtiene todos los tickets ordenados por fecha de creación.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->ticketRepo->findAllOrderedByCreadoEnDesc();
    }

    /**
     * Obtiene los tickets de un cliente.
     *
     * @param int $clienteId
     * @return array
     */
    public function listarPorCliente(int $clienteId): array
    {
        return $this->ticketRepo->findByClienteId($clienteId);
    }

    /**
     * Obtiene los tickets de un servicio.
     *
     * @param int $servicioId
     * @return array
     */
    public function listarPorServicio(int $servicioId): array
    {
        return $this->ticketRepo->findByServicioId($servicioId);
    }

    /**
     * Cuenta los tickets abiertos de un cliente.
     *
     * @param int $clienteId
     * @return int
     */
    public function contarAbiertosPorCliente(int $clienteId): int
    {
        return $this->ticketRepo->countAbiertosByClienteId($clienteId);
    }

    /**
     * Método privado. Valida los ENUM de tickets. No se llama desde fuera del service.
     *
     * @param array $datos
     * @return void
     * @throws ValidationException
     */
    private function validarEnums(array $datos): void
    {
        if (isset($datos['estado']) && !in_array($datos['estado'], ['Abierto', 'En proceso', 'Cerrado'], true)) {
            throw new ValidationException(['estado' => 'Estado de ticket inválido.']);
        }

        if (isset($datos['prioridad']) && !in_array($datos['prioridad'], ['Alta', 'Media', 'Baja'], true)) {
            throw new ValidationException(['prioridad' => 'Prioridad de ticket inválida.']);
        }

        if (isset($datos['origen']) && !in_array($datos['origen'], ['portal_cliente', 'backoffice'], true)) {
            throw new ValidationException(['origen' => 'Origen de ticket inválido.']);
        }
    }
}