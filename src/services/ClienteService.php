<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConflictException;
use App\Repositories\ClienteRepository;
use App\Repositories\DireccionRepository;
use App\Repositories\TelefonoRepository;

/**
 * Servicio para la gestión de clientes.
 * La baja de un cliente es lógica (estado='inactivo'). El borrado físico no se permite desde la aplicación.
 */
final class ClienteService extends BaseService
{
    /** @var ClienteRepository */
    private ClienteRepository $clienteRepo;

    /** @var DireccionRepository */
    private DireccionRepository $direccionRepo;

    /** @var TelefonoRepository */
    private TelefonoRepository $telefonoRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->clienteRepo = new ClienteRepository($pdo);
        $this->direccionRepo = new DireccionRepository($pdo);
        $this->telefonoRepo = new TelefonoRepository($pdo);
    }

    /**
     * Crea un nuevo cliente, incluyendo su dirección y teléfono si se proporcionan.
     *
     * @param array $datos
     * @return int
     * @throws ConflictException
     */
    public function crear(array $datos): int
    {
        if (isset($datos['cedula']) && $this->clienteRepo->findByCedula($datos['cedula'])) {
            throw new ConflictException('Ya existe un cliente registrado con esta cédula.');
        }

        return $this->transaction(function () use ($datos) {
            $datosCliente = $datos;
            unset($datosCliente['direccion'], $datosCliente['telefono']);

            if (isset($datos['direccion']) && is_array($datos['direccion'])) {
                $direccionId = $this->direccionRepo->create($datos['direccion']);
                $datosCliente['direccion_id'] = $direccionId;
            }

            $clienteId = $this->clienteRepo->create($datosCliente);

            if (isset($datos['telefono']) && is_string($datos['telefono']) && trim($datos['telefono']) !== '') {
                $telefonoId = $this->telefonoRepo->findOrCreate(trim($datos['telefono']));
                $this->clienteRepo->attachTelefono($clienteId, $telefonoId);
            }

            return $clienteId;
        });
    }

    /**
     * Actualiza los datos de un cliente existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ConflictException
     */
    public function actualizar(int $id, array $datos): void
    {
        $cliente = $this->clienteRepo->findById($id);
        if (!$cliente) {
            throw new NotFoundException('Cliente no encontrado.');
        }

        if (isset($datos['cedula']) && $datos['cedula'] !== $cliente['cedula']) {
            $existente = $this->clienteRepo->findByCedula($datos['cedula']);
            if ($existente && (int) $existente['id'] !== $id) {
                throw new ConflictException('La cédula ya está en uso por otro cliente.');
            }
        }

        $this->transaction(function () use ($id, $datos) {
            $datosCliente = $datos;
            unset($datosCliente['telefono'], $datosCliente['direccion']);

            if (!empty($datosCliente)) {
                $this->clienteRepo->update($id, $datosCliente);
            }

            if (isset($datos['telefono']) && is_string($datos['telefono']) && trim($datos['telefono']) !== '') {
                $telefonosActuales = $this->clienteRepo->getTelefonos($id);
                foreach ($telefonosActuales as $t) {
                    $this->clienteRepo->detachTelefono($id, (int) $t['id']);
                }
                $telefonoId = $this->telefonoRepo->findOrCreate(trim($datos['telefono']));
                $this->clienteRepo->attachTelefono($id, $telefonoId);
            }
        });
    }

    /**
     * Desactiva un cliente (baja lógica).
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     */
    public function desactivar(int $id): void
    {
        if (!$this->clienteRepo->exists($id)) {
            throw new NotFoundException('Cliente no encontrado.');
        }

        $this->clienteRepo->update($id, ['estado' => 'inactivo']);
    }

    /**
     * Busca un cliente por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->clienteRepo->findById($id);
    }

    /**
     * Busca un cliente por su cédula.
     *
     * @param string $cedula
     * @return array|null
     */
    public function buscarPorCedula(string $cedula): ?array
    {
        return $this->clienteRepo->findByCedula($cedula);
    }

    /**
     * Obtiene una lista paginada de clientes con soporte para búsqueda.
     *
     * @param string $filtro
     * @param int $pagina
     * @param int $porPagina
     * @return array
     */
    public function listarPaginado(string $filtro, int $pagina, int $porPagina): array
    {
        $pagina = max(1, $pagina);
        $porPagina = max(1, min(100, $porPagina));
        $offset = ($pagina - 1) * $porPagina;

        $items = $this->clienteRepo->paginateWithSearch($filtro, $porPagina, $offset);
        $total = $this->clienteRepo->countWithSearch($filtro);
        $pages = max(1, (int) ceil($total / $porPagina));

        return [
            'items' => $items,
            'total' => $total,
            'page' => $pagina,
            'pages' => $pages,
            'per_page' => $porPagina
        ];
    }
}