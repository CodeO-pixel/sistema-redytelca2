<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad Direccion.
 */
final class DireccionRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'direccion';

    /** @var string */
    protected string $primaryKey = 'id';
}