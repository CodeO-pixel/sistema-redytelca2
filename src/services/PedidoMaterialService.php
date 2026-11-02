<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\PedidoMaterialRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\EquipoRepository;

/**
 * Servicio para la gestión de materiales de pedidos.
 *
 * Flujo F2.35 (reserva → asignación al completar):
 *   - Al crear un material con `equipo_id`, el equipo queda RESERVADO
 *     (se crea `pedido_materiales.equipo_id`) pero NO se toca
 *     `equipos.servicio_id`. Sigue en `estado_inventario = 'stock'`.
 *   - La asignación formal al servicio ocurre cuando el pedido se
 *     marca como `completado` (ver PedidoService::completar()).
 *
 * Patrón de industria: Splynx, Sonar, NowaCRM, Dynamics 365 FS,
 * TM Forum eTOM.
 */
final class PedidoMaterialService extends BaseService
{
    /** @var PedidoMaterialRepository */
    private PedidoMaterialRepository $materialRepo;

    /** @var PedidoRepository */
    private PedidoRepository $pedidoRepo;

    /** @var EquipoRepository */
    private EquipoRepository $equipoRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->materialRepo = new PedidoMaterialRepository($pdo);
        $this->pedidoRepo = new PedidoRepository($pdo);
        $this->equipoRepo = new EquipoRepository($pdo);
    }

    /**
     * Obtiene los materiales de un pedido.
     *
     * @param int $idPedido
     * @return array
     * @throws NotFoundException
     */
    public function listarPorPedido(int $idPedido): array
    {
        if (!$this->pedidoRepo->exists($idPedido)) {
            throw new NotFoundException('El pedido especificado no existe.');
        }

        return $this->materialRepo->findByPedidoId($idPedido);
    }

    /**
     * Crea un nuevo material para un pedido.
     *
     * Si el pedido es de instalación y viene `equipo_id`, verifica que
     * el equipo esté en stock o ya asignado al mismo servicio (para
     * permitir reservar el que ya estaba, aunque no debería pasar).
     * NO asigna el equipo al servicio: eso ocurre al completar el pedido.
     *
     * Para pedidos NO de instalación (reparación, retiro, cambio_plan,
     * traslado), el equipo ya está asignado al servicio. El material se
     * crea como consumible y, si viene `equipo_id`, se registra como
     * referencia, pero no se toca `equipos.servicio_id`.
     *
     * @param array $datos
     * @return int
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function crear(array $datos): int
    {
        if (!isset($datos['pedido_id']) || !$this->pedidoRepo->exists((int) $datos['pedido_id'])) {
            throw new NotFoundException('El pedido especificado no existe.');
        }

        $pedido = $this->pedidoRepo->findById((int) $datos['pedido_id']);
        $esInstalacion = ((string) ($pedido['tipo'] ?? '')) === 'instalacion';
        $servicioId = isset($pedido['servicio_id']) && $pedido['servicio_id'] !== null
            ? (int) $pedido['servicio_id']
            : null;

        $equipoId = null;
        if (isset($datos['equipo_id']) && $datos['equipo_id'] !== null && $datos['equipo_id'] !== '') {
            $equipoId = (int) $datos['equipo_id'];
            $equipo = $this->equipoRepo->findById($equipoId);
            if (!$equipo) {
                throw new NotFoundException('El equipo especificado no existe.');
            }

            if ($esInstalacion && $servicioId !== null) {
                $estadoInv = (string) ($equipo['estado_inventario'] ?? '');
                $servicioDelEquipo = $equipo['servicio_id'] !== null ? (int) $equipo['servicio_id'] : null;

                // Aceptar equipos en stock (reserva normal) o ya asignados
                // al mismo servicio (reenvío idempotente).
                if ($servicioDelEquipo === null && $estadoInv !== 'stock') {
                    throw new ValidationException(['equipo_id' => 'El equipo no está disponible en stock (estado actual: ' . $estadoInv . ').']);
                }
                if ($servicioDelEquipo !== null && $servicioDelEquipo !== $servicioId) {
                    throw new ValidationException(['equipo_id' => 'El equipo ya está asignado a otro servicio.']);
                }
            }
        }

        $datos['equipo_id'] = $equipoId;

        // El equipo queda RESERVADO. No se toca equipos.servicio_id.
        return $this->materialRepo->create($datos);
    }

    /**
     * Actualiza un material de pedido existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->materialRepo->exists($id)) {
            throw new NotFoundException('Material no encontrado.');
        }

        if (isset($datos['pedido_id']) && !$this->pedidoRepo->exists((int) $datos['pedido_id'])) {
            throw new NotFoundException('El pedido especificado no existe.');
        }

        if (isset($datos['equipo_id']) && $datos['equipo_id'] !== null && $datos['equipo_id'] !== '') {
            if (!$this->equipoRepo->exists((int) $datos['equipo_id'])) {
                throw new NotFoundException('El equipo especificado no existe.');
            }
        }

        $this->materialRepo->update($id, $datos);
    }

    /**
     * Elimina un material de pedido.
     *
     * No revierte la asignación del equipo al servicio: si el pedido
     * ya está completado, el equipo asignado no se toca (esa decisión
     * es explícita del operador: si se equivocó, debe reasignarlo).
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function eliminar(int $id): void
    {
        if (!$this->materialRepo->exists($id)) {
            throw new NotFoundException('Material no encontrado.');
        }

        $this->materialRepo->delete($id);
    }
}