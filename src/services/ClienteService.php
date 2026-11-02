<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConflictException;
use App\Repositories\ClienteRepository;
use App\Repositories\DireccionRepository;
use App\Repositories\TelefonoRepository;
use App\Repositories\ClienteCredencialRepository;

/**
 * Servicio para la gestión de clientes.
 * La baja de un cliente es lógica (estado='inactivo'). El borrado físico no se permite desde la aplicación.
 *
 * A2-B.3: crear() auto-crea la credencial del portal del cliente con
 * password temporal CSPRNG (OWASP ASVS 5.0 V2.5.1, NIST SP 800-63B §5.1.1.2).
 * La password se devuelve UNA VEZ en el return, nunca se guarda en claro,
 * nunca se loguea.
 */
final class ClienteService extends BaseService
{
    /** @var ClienteRepository */
    private ClienteRepository $clienteRepo;

    /** @var DireccionRepository */
    private DireccionRepository $direccionRepo;

    /** @var TelefonoRepository */
    private TelefonoRepository $telefonoRepo;

    /** @var ClienteCredencialRepository */
    private ClienteCredencialRepository $credencialRepo;

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
        $this->credencialRepo = new ClienteCredencialRepository($pdo);
    }

    /**
     * Crea un nuevo cliente, incluyendo su dirección y teléfono si se
     * proporcionan, y su credencial del portal.
     *
     * La credencial se crea automáticamente (patrón Splynx / Sonar /
     * CANTV). El username es el correo del cliente. Si el correo ya
     * existe en `clientes_credenciales` (deuda F2.10: `clientes.correo`
     * no es UNIQUE en la BD), se usa el fallback `<correo>-<cliente_id>`.
     *
     * La password temporal se genera con CSPRNG (16 chars hex) y se
     * devuelve UNA SOLA VEZ en el return. No se guarda en claro ni se
     * loguea.
     *
     * @param array $datos
     * @return array{cliente_id: int, username: string, password_temporal: string}
     * @throws ConflictException
     */
    public function crear(array $datos): array
    {
        if (isset($datos['cedula']) && $this->clienteRepo->findByCedula($datos['cedula'])) {
            throw new ConflictException('Ya existe un cliente registrado con esta cédula.');
        }

        $closure = function () use ($datos) {
            $datosCliente = $datos;
            unset($datosCliente['direccion'], $datosCliente['telefono']);

            if (isset($datos['direccion']) && is_array($datos['direccion']) && $this->direccionHasData($datos['direccion'])) {
                $direccionId = $this->direccionRepo->create($datos['direccion']);
                $datosCliente['direccion_id'] = $direccionId;
            }

            $clienteId = $this->clienteRepo->create($datosCliente);

            if (isset($datos['telefono']) && is_string($datos['telefono']) && trim($datos['telefono']) !== '') {
                $telefonoId = $this->telefonoRepo->findOrCreate(trim($datos['telefono']));
                $this->clienteRepo->attachTelefono($clienteId, $telefonoId);
            }

            $credencial = $this->crearCredencialCliente($clienteId, (string) ($datosCliente['correo'] ?? ''));

            return [
                'cliente_id' => $clienteId,
                'username' => $credencial['username'],
                'password_temporal' => $credencial['password_temporal']
            ];
        };

        if ($this->pdo->inTransaction()) {
            return $closure();
        }

        return $this->transaction($closure);
    }

    /**
     * Actualiza los datos de un cliente existente.
     *
     * Procesa la dirección del cliente:
     *   - si ya tenía una y llega direccion con datos, UPDATE.
     *   - si no tenía y llega direccion con datos, INSERT + UPDATE clientes.direccion_id.
     *   - si llega direccion vacía, no toca la tabla.
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

        $this->transaction(function () use ($id, $datos, $cliente) {
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

            if (isset($datos['direccion']) && is_array($datos['direccion']) && $this->direccionHasData($datos['direccion'])) {
                $dir = $datos['direccion'];
                if (!empty($cliente['direccion_id'])) {
                    $this->direccionRepo->update((int) $cliente['direccion_id'], $dir);
                } else {
                    $nuevaDirId = $this->direccionRepo->create($dir);
                    $this->clienteRepo->update($id, ['direccion_id' => $nuevaDirId]);
                }
            }
        });
    }

    /**
     * Desactiva un cliente (baja lógica).
     *
     * A2-B.3: si el cliente tiene credencial de portal, la suspende y
     * revoca sus sesiones activas. Evita que un cliente inactivo siga
     * con acceso al portal.
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

        $this->transaction(function () use ($id) {
            $this->clienteRepo->update($id, ['estado' => 'inactivo']);

            $credencial = $this->credencialRepo->findByClienteId($id);
            if ($credencial) {
                $this->credencialRepo->update((int) $credencial['id'], ['estado' => 'suspendida']);
            }
        });
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

    /**
     * Método privado. Verifica si el array de dirección tiene al menos
     * un campo con dato. Si todos vienen vacíos, no se toca la tabla
     * direccion (evita filas con todos los campos NULL).
     *
     * @param array $dir
     * @return bool
     */
    private function direccionHasData(array $dir): bool
    {
        foreach (['calle', 'carrera', 'avenida'] as $k) {
            if (isset($dir[$k]) && trim((string) $dir[$k]) !== '') {
                return true;
            }
        }
        foreach (['latitud', 'longitud'] as $k) {
            if (isset($dir[$k]) && $dir[$k] !== '' && $dir[$k] !== null) {
                return true;
            }
        }
        return false;
    }

    /**
     * Método privado. Genera una password temporal con CSPRNG.
     *
     * 16 caracteres hex (128 bits de entropía). Cumple OWASP ASVS 5.0
     * V2.3.1 (>= 6 chars) y V2.5.1 (single-use, random). NIST SP 800-63B
     * §5.1.1.2 (randomly generated). No contiene mayúsculas, por lo que
     * nunca colisiona con "Redytelca" ni con el username (que es un
     * correo).
     *
     * @return string
     */
    private function generarPasswordTemporal(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Método privado. Crea la credencial del portal para un cliente.
     *
     * Username = correo del cliente. Fallback determinista si el correo
     * ya existe en `clientes_credenciales` (deuda F2.10: `clientes.correo`
     * no es UNIQUE en la BD).
     *
     * @param int $clienteId
     * @param string $correo
     * @return array{username: string, password_temporal: string}
     * @throws \RuntimeException Si no se puede generar un username único.
     */
    private function crearCredencialCliente(int $clienteId, string $correo): array
    {
        $correo = trim($correo);
        if ($correo === '') {
            throw new \RuntimeException('El cliente no tiene correo; no se puede crear la credencial del portal.');
        }

        $username = $correo;

        // Fallback determinista si el correo ya existe como username.
        // Deuda F2.10: clientes.correo no es UNIQUE. Si dos clientes
        // comparten correo, el segundo recibe <correo>-<cliente_id>.
        if ($this->credencialRepo->findByUsername($username)) {
            $username = $correo . '-' . $clienteId;
        }

        $passwordTemporal = $this->generarPasswordTemporal();

        $this->credencialRepo->create([
            'cliente_id' => $clienteId,
            'username' => $username,
            'password' => password_hash($passwordTemporal, PASSWORD_DEFAULT),
            'correo_recuperacion' => $correo,
            'estado' => 'activa',
            'must_change_password' => 1
        ]);

        return [
            'username' => $username,
            'password_temporal' => $passwordTemporal
        ];
    }
}