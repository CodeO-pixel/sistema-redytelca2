<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Repositories\TransicionEstadoRepository;

/**
 * Controlador para la consulta de transiciones de estado válidas.
 */
final class TransicionEstadoController extends BaseController
{
    /** @var TransicionEstadoRepository */
    private TransicionEstadoRepository $transicionRepo;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->transicionRepo = new TransicionEstadoRepository($pdo);
    }

    /**
     * Lista las transiciones de estado, opcionalmente filtradas por tabla.
     *
     * @return void
     */
    public function index(): void
    {
        $tabla = trim($_GET['tabla'] ?? '');

        if ($tabla !== '') {
            $transiciones = $this->transicionRepo->findByTabla($tabla);
        } else {
            $transiciones = $this->transicionRepo->findAllOrdered();
        }

        $this->render([
            'status' => 'success',
            'transiciones' => $transiciones
        ], 200);
    }
}