<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\FacturaService;
use App\Repositories\FacturaRepository;
use App\Repositories\PagoRepository;
use App\Validators\FacturaValidator;

/**
 * Controlador para la gestión de facturas.
 */
final class FacturaController extends BaseController
{
    /** @var FacturaService */
    private FacturaService $facturaService;

    /**
     * Constructor del controlador.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->facturaService = new FacturaService($pdo);
    }

    /**
     * Lista las facturas.
     *
     * @return void
     */
    public function index(): void
    {
        $facturas = (new FacturaRepository($this->pdo))->findAll();
        $pagoRepo = new PagoRepository($this->pdo);

        foreach ($facturas as &$factura) {
            $factura['total_pagado'] = $pagoRepo->sumValidadosByFacturaId((int) $factura['id']);
        }
        unset($factura);

        $this->render(['status' => 'success', 'facturas' => $facturas], 200);
    }

    /**
     * Crea una nueva factura.
     *
     * @return void
     */
    public function store(): void
    {
        $input = $this->input();
        $validated = FacturaValidator::validateCreate($input);
        $this->facturaService->crear($validated);
        $this->render(['status' => 'success', 'message' => 'Factura creada correctamente'], 200);
    }

    /**
     * Actualiza una factura existente.
     *
     * @return void
     */
    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_factura'] ?? 0);
        unset($input['id_factura']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Factura inválida'], 400);
            return;
        }

        $validated = FacturaValidator::validateUpdate($input);
        $this->facturaService->actualizar($id, $validated);
        $this->render(['status' => 'success', 'message' => 'Factura actualizada'], 200);
    }

    /**
     * Elimina una factura.
     *
     * @return void
     */
    public function destroy(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $this->facturaService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Factura eliminada'], 200);
    }

    /**
     * Materializa los vencimientos de las facturas.
     *
     * @return void
     */
    public function materializarVencimientos(): void
    {
        $count = $this->facturaService->materializarVencimientos();
        $this->render(['status' => 'success', 'actualizadas' => $count], 200);
    }
}