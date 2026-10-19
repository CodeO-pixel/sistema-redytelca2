<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Repositories\MetodoPagoRepository;

/**
 * Controlador para la gestión de métodos de pago.
 */
final class MetodoPagoController extends BaseController
{
    /** @var MetodoPagoRepository */
    private MetodoPagoRepository $metodoPagoRepo;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->metodoPagoRepo = new MetodoPagoRepository($pdo);
    }

    /**
     * Lista los métodos de pago activos.
     *
     * @return void
     */
    public function index(): void
    {
        $metodos = $this->metodoPagoRepo->findActivos();
        $this->render(['status' => 'success', 'metodos' => $metodos], 200);
    }
}