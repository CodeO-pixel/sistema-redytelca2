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
use App\Repositories\PedidoAsignacionRepository;
use App\Repositories\PedidoMaterialRepository;
use App\Repositories\EquipoRepository;

/**
 * Servicio para la gestión de pedidos de campo.
 *
 * Flujo F2.35 (reserva → asignación al completar):
 *   - crear(): el pedido nace pendiente. No asigna equipo.
 *   - asignarTecnico(): registra historial y asigna técnico.
 *   - completar(): cierra el pedido. Asigna el equipo reservado al
 *     servicio, devuelve el anterior si era reemplazo, y activa el
 *     servicio. Todo en transacción.
 *
 * Patrón de industria: Splynx, Sonar, NowaCRM, Dynamics 365 FS,
 * TM Forum eTOM.
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

    /** @var PedidoAsignacionRepository */
    private PedidoAsignacionRepository $asignacionRepo;

    /** @var PedidoMaterialRepository */
    private PedidoMaterialRepository $materialRepo;

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
        $this->pedidoRepo = new PedidoRepository($pdo);
        $this->clienteRepo = new ClienteRepository($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
        $this->usuarioRepo = new UsuarioRepository($pdo);
        $this->asignacionRepo = new PedidoAsignacionRepository($pdo);
        $this->materialRepo = new PedidoMaterialRepository($pdo);
        $this->equipoRepo = new EquipoRepository($pdo);
    }

    /**
     * Crea un nuevo pedido.
     *
     * @param array $datos
     * @param int $usuarioId
     * @return int
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function crear(array $datos, int $usuarioId): int
    {
        unset($datos['usuario_creador_id']);
        $datos['usuario_creador_id'] = $usuarioId;

        if (!isset($datos['cliente_id']) || !$this->clienteRepo->exists((int) $datos['cliente_id'])) {
            throw new NotFoundException('El cliente especificado no existe.');
        }

        if (isset($datos['servicio_id']) && !$this->servicioRepo->exists((int) $datos['servicio_id'])) {
            throw new NotFoundException('El servicio especificado no existe.');
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
     * No permite cambiar el estado a 'completado' por esta vía: el
     * cierre va por completar(). Esto evita que un PUT genérico
     * complete el pedido sin asignar el equipo reservado.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ValidationException
     * @throws DomainException
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

        if (isset($datos['estado']) && $datos['estado'] === 'completado') {
            throw new DomainException('Para completar un pedido usa la acción "Completar" (PUT api_pedidos_completar.php).');
        }

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
     * Asigna un técnico a un pedido y registra el historial de asignación.
     *
     * @param int $id
     * @param int $tecnicoId
     * @param int|null $usuarioAsignadorId
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function asignarTecnico(int $id, int $tecnicoId, ?int $usuarioAsignadorId = null): void
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

        $this->transaction(function () use ($id, $tecnicoId, $usuarioAsignadorId) {
            $activa = $this->asignacionRepo->findActivaByPedidoId($id);
            if ($activa) {
                $this->asignacionRepo->update((int) $activa['id'], [
                    'estado' => 'completada',
                    'cerrado_en' => date('Y-m-d H:i:s')
                ]);
            }

            $this->asignacionRepo->create([
                'pedido_id' => $id,
                'tecnico_id' => $tecnicoId,
                'usuario_asignador_id' => $usuarioAsignadorId,
                'estado' => 'activa'
            ]);

            $this->pedidoRepo->update($id, [
                'tecnico_asignado_id' => $tecnicoId,
                'estado' => 'asignado'
            ]);
        });
    }

    /**
     * Completa un pedido y asigna formalmente el equipo reservado al
     * servicio.
     *
     * En transacción:
     *   1. Verifica que el pedido exista y no esté completado/cancelado.
     *   2. Si el pedido es de instalación y tiene servicio_id:
     *      a. Busca el equipo reservado (primer `pedido_materiales.equipo_id`).
     *      b. Devuelve a `devuelto` cualquier equipo anterior del servicio.
     *      c. Asigna el reservado: servicio_id, estado_inventario='asignado',
     *         fecha_asignacion=HOY.
     *      d. Si el servicio está `pendiente`, lo pasa a `activo`.
     *   3. Marca el pedido como `completado` con fecha_completado=NOW().
     *
     * Idempotente: si ya está `completado`, no hace nada.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function completar(int $id): void
    {
        $pedido = $this->pedidoRepo->findById($id);
        if (!$pedido) {
            throw new NotFoundException('Pedido no encontrado.');
        }

        if ($pedido['estado'] === 'completado') {
            return;
        }

        if ($pedido['estado'] === 'cancelado') {
            throw new DomainException('No se puede completar un pedido cancelado.');
        }

        $this->transaction(function () use ($id, $pedido) {
            $servicioId = isset($pedido['servicio_id']) && $pedido['servicio_id'] !== null
                ? (int) $pedido['servicio_id']
                : null;
            $esInstalacion = ((string) ($pedido['tipo'] ?? '')) === 'instalacion';

            if ($esInstalacion && $servicioId !== null) {
                // Buscar el equipo reservado (el primero con equipo_id).
                $materiales = $this->materialRepo->findByPedidoId($id);
                $equipoReservadoId = null;
                foreach ($materiales as $m) {
                    if (!empty($m['equipo_id'])) {
                        $equipoReservadoId = (int) $m['equipo_id'];
                        break;
                    }
                }

                if ($equipoReservadoId !== null) {
                    // Devolver a `devuelto` los equipos actualmente asignados
                    // al servicio que no sean el reservado (reasignación).
                    $equiposActuales = $this->equipoRepo->findByServicioId($servicioId);
                    foreach ($equiposActuales as $eq) {
                        if ((int) $eq['id'] === $equipoReservadoId) {
                            continue;
                        }
                        $this->equipoRepo->update((int) $eq['id'], [
                            'servicio_id' => null,
                            'estado_inventario' => 'devuelto',
                            'fecha_devolucion' => date('Y-m-d')
                        ]);
                    }

                    // Asignar el equipo reservado al servicio.
                    $this->equipoRepo->update($equipoReservadoId, [
                        'servicio_id' => $servicioId,
                        'estado_inventario' => 'asignado',
                        'fecha_asignacion' => date('Y-m-d')
                    ]);
                }

                // Activar el servicio si estaba pendiente.
                $servicio = $this->servicioRepo->findById($servicioId);
                if ($servicio && (string) ($servicio['estado_comercial'] ?? '') === 'pendiente') {
                    $this->servicioRepo->update($servicioId, [
                        'estado_comercial' => 'activo'
                    ]);
                }
            }

            $this->pedidoRepo->update($id, [
                'estado' => 'completado',
                'fecha_completado' => date('Y-m-d H:i:s')
            ]);
        });
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
     * Fix B3.13 — usa findAllByTecnicoId (con joins) en lugar de
     * findByTecnicoAsignadoId (SELECT plano), para que el listado del
     * técnico traiga el mismo contrato enriquecido que el de Admin.
     *
     * @param int $tecnicoId
     * @return array
     */
    public function listarPorTecnico(int $tecnicoId): array
    {
        return $this->pedidoRepo->findAllByTecnicoId($tecnicoId);
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
     * Método privado. Valida los ENUM de pedidos.
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