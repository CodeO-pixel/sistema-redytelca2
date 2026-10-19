<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConflictException;
use App\Repositories\ContratoRepository;
use App\Repositories\ServicioRepository;

/**
 * Servicio para la gestión de contratos.
 */
final class ContratoService extends BaseService
{
    /** @var ContratoRepository */
    private ContratoRepository $contratoRepo;

    /** @var ServicioRepository */
    private ServicioRepository $servicioRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->contratoRepo = new ContratoRepository($pdo);
        $this->servicioRepo = new ServicioRepository($pdo);
    }

    /**
     * Crea un nuevo contrato.
     *
     * @param array $datos
     * @return int
     * @throws NotFoundException
     * @throws ConflictException
     */
    public function crear(array $datos): int
    {
        if (!isset($datos['servicio_id']) || !$this->servicioRepo->exists((int) $datos['servicio_id'])) {
            throw new NotFoundException('Servicio no encontrado.');
        }

        $vigente = $this->contratoRepo->findVigenteByServicioId((int) $datos['servicio_id']);
        if ($vigente) {
            throw new ConflictException('Ya existe un contrato vigente para este servicio.');
        }

        $datos['estado'] = 'vigente';

        return $this->contratoRepo->create($datos);
    }

    /**
     * Actualiza un contrato existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ConflictException
     */
    public function actualizar(int $id, array $datos): void
    {
        $contrato = $this->contratoRepo->findById($id);
        if (!$contrato) {
            throw new NotFoundException('Contrato no encontrado.');
        }

        $this->transaction(function () use ($id, $datos, $contrato) {
            if (isset($datos['estado']) && $datos['estado'] === 'rescindido' && $contrato['estado'] === 'vigente') {
                $this->servicioRepo->update((int) $contrato['servicio_id'], ['estado_comercial' => 'retirado']);
            }

            if (isset($datos['estado']) && $datos['estado'] === 'vigente' && $contrato['estado'] !== 'vigente') {
                $vigente = $this->contratoRepo->findVigenteByServicioId((int) $contrato['servicio_id']);
                if ($vigente && (int) $vigente['id'] !== $id) {
                    throw new ConflictException('Ya existe otro contrato vigente para este servicio.');
                }
            }

            $this->contratoRepo->update($id, $datos);
        });
    }

    /**
     * Elimina un contrato.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function eliminar(int $id): void
    {
        if (!$this->contratoRepo->exists($id)) {
            throw new NotFoundException('Contrato no encontrado.');
        }

        $this->contratoRepo->delete($id);
    }

    /**
     * Materializa los vencimientos de los contratos vigentes cuya fecha de fin ya pasó.
     *
     * @return int Número de contratos actualizados.
     */
    public function materializarVencimientos(): int
    {
        $vigentes = $this->contratoRepo->findByEstado('vigente');
        $hoy = date('Y-m-d');
        $count = 0;

        foreach ($vigentes as $contrato) {
            if (!empty($contrato['fecha_fin']) && $contrato['fecha_fin'] < $hoy) {
                $this->contratoRepo->update((int) $contrato['id'], ['estado' => 'vencido']);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Busca un contrato por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->contratoRepo->findById($id);
    }

    /**
     * Obtiene los contratos de un servicio.
     *
     * @param int $servicioId
     * @return array
     */
    public function listarPorServicio(int $servicioId): array
    {
        return $this->contratoRepo->findByServicioId($servicioId);
    }
}