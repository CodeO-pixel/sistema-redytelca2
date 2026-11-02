<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Repositories\BancoRepository;

final class BancoService extends BaseService
{
    private BancoRepository $bancoRepo;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->bancoRepo = new BancoRepository($pdo);
    }

    public function listar(): array
    {
        return $this->bancoRepo->findAllOrderedByNombre();
    }

    public function listarActivos(): array
    {
        return $this->bancoRepo->findActivos();
    }

    public function crear(string $nombre): int
    {
        if ($this->bancoRepo->findByNombre($nombre) !== null) {
            throw new ConflictException('Ese banco ya está registrado.');
        }

        return $this->bancoRepo->create(['nombre' => $nombre, 'activo' => 1]);
    }

    public function actualizarEstado(int $id, int $activo): void
    {
        if (!$this->bancoRepo->exists($id)) {
            throw new NotFoundException('Banco no encontrado.');
        }

        $this->bancoRepo->update($id, ['activo' => $activo]);
    }
}
