<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\TicketService;
use App\Validators\TicketValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\DomainException;
use App\Exceptions\ConflictException;
use App\Exceptions\ValidationException;

final class TicketController extends BaseController
{
    private TicketService $ticketService;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->ticketService = new TicketService($pdo);
    }

    public function index(): void
    {
        $tickets = $this->ticketService->listar();
        $this->render(['status' => 'success', 'tickets' => $tickets], 200);
    }

    public function store(): void
    {
        $input = $this->input();
        $validated = TicketValidator::validateCreate($input);
        $this->ticketService->crear($validated);
        $this->render(['status' => 'success', 'message' => 'Ticket creado correctamente'], 200);
    }

    public function update(): void
    {
        $input = $this->input();
        $id = (int) ($input['id_ticket'] ?? 0);
        unset($input['id_ticket']);

        if ($id <= 0) {
            $this->render(['status' => 'error', 'message' => 'Ticket inválido'], 400);
            return;
        }

        $validated = TicketValidator::validateUpdate($input);
        $this->ticketService->actualizar($id, $validated);
        $this->render(['status' => 'success', 'message' => 'Ticket actualizado'], 200);
    }

    public function destroy(): void
    {
        $input = $this->input();
        $id = (int) ($_GET['id'] ?? $input['id_ticket'] ?? 0);
        $this->ticketService->eliminar($id);
        $this->render(['status' => 'success', 'message' => 'Ticket eliminado'], 200);
    }
}