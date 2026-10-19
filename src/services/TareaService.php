<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\TareaRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\TicketRepository;
use App\Repositories\ServicioRepository;
use App\Repositories\UsuarioRepository;

/**
 * Servicio para la gestión de tareas.
 */
final class TareaService extends BaseService
{
    /** @var TareaRepository */
    private TareaRepository $tareaRepo;

    /** @var ClienteRepository */
    private ClienteRepository $clienteRepo;

    /** @var TicketRepository */
    private TicketRepository $ticketRepo;

    /** @var ServicioRepository */
    private ServicioRepository $servicioRepo;

    /** @var UsuarioRepository */
    private UsuarioRepository $usuarioRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->tareaRepo = new TareaRepository($pdo);
        $this->clienteRepo = new ClienteRepository($pdo);
        $this->ticketRepo = new TicketRepository($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
        $this->usuarioRepo = new UsuarioRepository($pdo);
    }

    /**
     * Crea una nueva tarea.
     *
     * @param array $datos
     * @return int
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function crear(array $datos): int
    {
        if (isset($datos['cliente_id']) && !$this->clienteRepo->exists((int) $datos['cliente_id'])) {
            throw new NotFoundException('El cliente especificado no existe.');
        }

        if (isset($datos['ticket_id']) && !$this->ticketRepo->exists((int) $datos['ticket_id'])) {
            throw new NotFoundException('El ticket especificado no existe.');
        }

        if (isset($datos['servicio_id']) && !$this->servicioRepo->exists((int) $datos['servicio_id'])) {
            throw new NotFoundException('El servicio especificado no existe.');
        }

        if (isset($datos['usuario_creador_id']) && !$this->usuarioRepo->exists((int) $datos['usuario_creador_id'])) {
            throw new NotFoundException('El usuario creador especificado no existe.');
        }

        $this->validarEnums($datos);

        if (!isset($datos['estado']) || !in_array($datos['estado'], ['Pendiente', 'En curso', 'Completada'], true)) {
            $datos['estado'] = 'Pendiente';
        }

        return $this->tareaRepo->create($datos);
    }

    /**
     * Actualiza una tarea existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->tareaRepo->exists($id)) {
            throw new NotFoundException('Tarea no encontrada.');
        }

        if (isset($datos['cliente_id']) && !$this->clienteRepo->exists((int) $datos['cliente_id'])) {
            throw new NotFoundException('El cliente especificado no existe.');
        }

        if (isset($datos['ticket_id']) && !$this->ticketRepo->exists((int) $datos['ticket_id'])) {
            throw new NotFoundException('El ticket especificado no existe.');
        }

        if (isset($datos['servicio_id']) && !$this->servicioRepo->exists((int) $datos['servicio_id'])) {
            throw new NotFoundException('El servicio especificado no existe.');
        }

        if (isset($datos['usuario_creador_id']) && !$this->usuarioRepo->exists((int) $datos['usuario_creador_id'])) {
            throw new NotFoundException('El usuario creador especificado no existe.');
        }

        $this->validarEnums($datos);

        $this->tareaRepo->update($id, $datos);
    }

    /**
     * Elimina una tarea.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function eliminar(int $id): void
    {
        if (!$this->tareaRepo->exists($id)) {
            throw new NotFoundException('Tarea no encontrada.');
        }

        $this->tareaRepo->delete($id);
    }

    /**
     * Marca una tarea como completada.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function completar(int $id): void
    {
        $tarea = $this->tareaRepo->findById($id);
        if (!$tarea) {
            throw new NotFoundException('Tarea no encontrada.');
        }

        if ($tarea['estado'] === 'Completada') {
            return;
        }

        $this->tareaRepo->update($id, ['estado' => 'Completada']);
    }

    /**
     * Busca una tarea por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->tareaRepo->findById($id);
    }

    /**
     * Obtiene todas las tareas ordenadas por fecha de creación.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->tareaRepo->findAllOrderedByCreadoEnDesc();
    }

    /**
     * Obtiene las tareas de un cliente.
     *
     * @param int $clienteId
     * @return array
     */
    public function listarPorCliente(int $clienteId): array
    {
        return $this->tareaRepo->findByClienteId($clienteId);
    }

    /**
     * Obtiene las tareas derivadas de un ticket.
     *
     * @param int $ticketId
     * @return array
     */
    public function listarPorTicket(int $ticketId): array
    {
        return $this->tareaRepo->findByTicketId($ticketId);
    }

    /**
     * Obtiene las tareas de un servicio.
     *
     * @param int $servicioId
     * @return array
     */
    public function listarPorServicio(int $servicioId): array
    {
        return $this->tareaRepo->findByServicioId($servicioId);
    }

    /**
     * Obtiene las tareas creadas por un usuario.
     *
     * @param int $usuarioId
     * @return array
     */
    public function listarPorUsuarioCreador(int $usuarioId): array
    {
        return $this->tareaRepo->findByUsuarioCreadorId($usuarioId);
    }

    /**
     * Método privado. Valida los ENUM de tareas. No se llama desde fuera del service.
     *
     * @param array $datos
     * @return void
     * @throws ValidationException
     */
    private function validarEnums(array $datos): void
    {
        if (isset($datos['estado']) && !in_array($datos['estado'], ['Pendiente', 'En curso', 'Completada'], true)) {
            throw new ValidationException(['estado' => 'Estado de tarea inválido.']);
        }

        if (isset($datos['prioridad']) && !in_array($datos['prioridad'], ['Alta', 'Media', 'Baja'], true)) {
            throw new ValidationException(['prioridad' => 'Prioridad de tarea inválida.']);
        }
    }
}