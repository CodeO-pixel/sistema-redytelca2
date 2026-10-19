<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Repositorio para la entidad DireccionServicio.
 */
final class DireccionServicioRepository extends BaseRepository
{
    /** @var string */
    protected string $table = 'direccion_servicio';

    /** @var string */
    protected string $primaryKey = 'id';
}