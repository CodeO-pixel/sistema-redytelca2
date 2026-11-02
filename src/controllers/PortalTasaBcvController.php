<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Exceptions\ServiceUnavailableException;
use App\Services\TasaBcvService;

final class PortalTasaBcvController extends BaseController
{
    private TasaBcvService $tasaService;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->tasaService = new TasaBcvService($pdo);
    }

    public function index(): void
    {
        $tasa = $this->tasaService->obtenerDisponible();
        if ($tasa === null) {
            throw new ServiceUnavailableException(
                'La tasa oficial BCV no está disponible. Los pagos en VES están temporalmente deshabilitados.'
            );
        }

        $this->render([
            'status' => 'success',
            'tasa' => $tasa,
        ]);
    }
}
