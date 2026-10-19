<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Repositories\PedidoRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\ServicioRepository;
use App\Repositories\UsuarioRepository;

/**
 * Servicio para la gestión de pedidos de campo.
 */
final class PedidoService extends BaseService
{
    /** @var PedidoRepository */
    private PedidoRepository $pedidoRepo;

    /** @var ClienteRepository */
    private ClienteRepository $clienteRepo;

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
        $this->pedidoRepo = new PedidoRepository($pdo);
        $this->clienteRepo = new ClienteRepository($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
        $this->usuarioRepo = new UsuarioRepository($pdo);
    }

    /**
     * Crea un nuevo pedido.
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

        if (isset($datos['usuario_creador_id']) && !$this->usuarioRepo->exists((int) $datos['usuario_creador_id'])) {
            throw new NotFoundException('El usuario creador especificado no existe.');
        }

        if (isset($datos['tecnico_asignado_id']) && !$this->usuarioRepo->exists((int) $datos['tecnico_asignado_id'])) {
            throw new NotFoundException('El técnico asignado especificado no existe.');
        }

        $this->validarEnums($datos);

        if (!isset($datos['estado']) || !in_array($datos['estado'], ['pendiente', 'asignado', 'en_curso', 'completado', 'cancelado'], true)) {
            $datos['estado'] = 'pendiente';
        }

        return $this->pedidoRepo->create($datos);
    }

    /**
     * Actualiza un pedido existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->pedidoRepo->exists($id)) {
            throw new NotFoundException('Pedido no encontrado.');
        }

        if (isset($datos['cliente_id']) && !$this->clienteRepo->exists((int) $datos['cliente_id'])) {
            throw new NotFoundException('El cliente especificado no existe.');
        }

        if (isset($datos['servicio_id']) && !$this->servicioRepo->exists((int) $datos['servicio_id'])) {
            throw new NotFoundException('El servicio especificado no existe.');
        }

        if (isset($datos['usuario_creador_id']) && !$this->usuarioRepo->exists((int) $datos['usuario_creador_id'])) {
            throw new NotFoundException('El usuario creador especificado no existe.');
        }

        if (isset($datos['tecnico_asignado_id']) && !$this->usuarioRepo->exists((int) $datos['tecnico_asignado_id'])) {
            throw new NotFoundException('El técnico asignado especificado no existe.');
        }

        $this->validarEnums($datos);

        $this->pedidoRepo->update($id, $datos);
    }

    /**
     * Elimina un pedido.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function eliminar(int $id): void
    {
        if (!$this->pedidoRepo->exists($id)) {
            throw new NotFoundException('Pedido no encontrado.');
        }

        $this->pedidoRepo->delete($id);
    }

    /**
     * Asigna un técnico a un pedido.
     *
     * @param int $id
     * @param int $tecnicoId
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function asignarTecnico(int $id, int $tecnicoId): void
    {
        $pedido = $this->pedidoRepo->findById($id);
        if (!$pedido) {
            throw new NotFoundException('Pedido no encontrado.');
        }

        if (in_array($pedido['estado'], ['completado', 'cancelado'], true)) {
            throw new DomainException('No se puede asignar técnico a un pedido completado o cancelado.');
        }

        if (!$this->usuarioRepo->exists($tecnicoId)) {
            throw new NotFoundException('El técnico especificado no existe.');
        }

        $this->pedidoRepo->update($id, [
            'tecnico_asignado_id' => $tecnicoId,
            'estado' => 'asignado'
        ]);
    }

    /**
     * Busca un pedido por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->pedidoRepo->findById($id);
    }

    /**
     * Obtiene todos los pedidos ordenados por fecha de solicitud.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->pedidoRepo->findAllOrderedByFechaSolicitudDesc();
    }

    /**
     * Obtiene los pedidos de un cliente.
     *
     * @param int $clienteId
     * @return array
     */
    public function listarPorCliente(int $clienteId): array
    {
        return $this->pedidoRepo->findByClienteId($clienteId);
    }

    /**
     * Obtiene los pedidos de un servicio.
     *
     * @param int $servicioId
     * @return array
     */
    public function listarPorServicio(int $servicioId): array
    {
        return $this->pedidoRepo->findByServicioId($servicioId);
    }

    /**
     * Obtiene los pedidos asignados a un técnico.
     *
     * @param int $tecnicoId
     * @return array
     */
    public function listarPorTecnico(int $tecnicoId): array
    {
        return $this->pedidoRepo->findByTecnicoAsignadoId($tecnicoId);
    }

    /**
     * Obtiene los pedidos filtrados por estado.
     *
     * @param string $estado
     * @return array
     */
    public function listarPorEstado(string $estado): array
    {
        return $this->pedidoRepo->findByEstado($estado);
    }

    /**
     * Obtiene los pedidos filtrados por tipo.
     *
     * @param string $tipo
     * @return array
     */
    public function listarPorTipo(string $tipo): array
    {
        return $this->pedidoRepo->findByTipo($tipo);
    }

    /**
     * Método privado. Valida los ENUM de pedidos. No se llama desde fuera del service.
     *
     * @param array $datos
     * @return void
     * @throws ValidationException
     */
    private function validarEnums(array $datos): void
    {
        if (isset($datos['tipo']) && !in_array($datos['tipo'], ['instalacion', 'reparacion', 'retiro', 'cambio_plan', 'traslado'], true)) {
            throw new ValidationException(['tipo' => 'Tipo de pedido inválido.']);
        }

        if (isset($datos['estado']) && !in_array($datos['estado'], ['pendiente', 'asignado', 'en_curso', 'completado', 'cancelado'], true)) {
            throw new ValidationException(['estado' => 'Estado de pedido inválido.']);
        }

        if (isset($datos['prioridad']) && !in_array($datos['prioridad'], ['Alta', 'Media', 'Baja'], true)) {
            throw new ValidationException(['prioridad' => 'Prioridad de pedido inválida.']);
        }
    }
}