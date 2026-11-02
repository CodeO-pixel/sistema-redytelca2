<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Repositories\FacturaLineaRepository;

/**
 * Controlador para la gestión de líneas de factura.
 */
final class FacturaLineaController extends BaseController
{
    /** @var FacturaLineaRepository */
    private FacturaLineaRepository $facturaLineaRepo;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->facturaLineaRepo = new FacturaLineaRepository($pdo);
    }

    /**
     * Lista las líneas de una factura específica.
     *
     * @return void
     */
    public function index(): void
    {
        $idFactura = isset($_GET['id_factura']) ? (int) $_GET['id_factura'] : 0;

        if ($idFactura <= 0) {
            $this->render(['status' => 'error', 'message' => 'El parámetro id_factura es obligatorio.'], 400);
            return;
        }

        $lineas = $this->facturaLineaRepo->findByFacturaId($idFactura);
        $this->render(['status' => 'success', 'lineas' => $lineas], 200);
    }
}