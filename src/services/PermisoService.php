<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConflictException;
use App\Exceptions\DomainException;
use App\Repositories\PermisoRepository;
use App\Repositories\RolRepository;
use App\Repositories\PaginaRepository;
use App\Repositories\RolModuloRepository;
use App\Repositories\RolModuloPaginaRepository;
use App\Repositories\RolModuloPermisoRepository;
use App\Repositories\ModuloRepository;

/**
 * Servicio para la gestión de permisos.
 */
final class PermisoService extends BaseService
{
    /** @var PermisoRepository */
    private PermisoRepository $permisoRepo;

    /** @var RolRepository */
    private RolRepository $rolRepo;

    /** @var PaginaRepository */
    private PaginaRepository $paginaRepo;

    /** @var RolModuloRepository */
    private RolModuloRepository $rolModuloRepo;

    /** @var RolModuloPaginaRepository */
    private RolModuloPaginaRepository $rolModuloPaginaRepo;

    /** @var RolModuloPermisoRepository */
    private RolModuloPermisoRepository $rolModuloPermisoRepo;

    /** @var ModuloRepository */
    private ModuloRepository $moduloRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->permisoRepo = new PermisoRepository($pdo);
        $this->rolRepo = new RolRepository($pdo);
        $this->paginaRepo = new PaginaRepository($pdo);
        $this->rolModuloRepo = new RolModuloRepository($pdo);
        $this->rolModuloPaginaRepo = new RolModuloPaginaRepository($pdo);
        $this->rolModuloPermisoRepo = new RolModuloPermisoRepository($pdo);
        $this->moduloRepo = new ModuloRepository($pdo);
    }

    /**
     * Crea un nuevo permiso.
     *
     * @param array $datos
     * @return int
     * @throws ConflictException
     */
    public function crear(array $datos): int
    {
        if (isset($datos['nombre']) && $this->permisoRepo->findByNombre($datos['nombre'])) {
            throw new ConflictException('Ya existe un permiso con ese nombre.');
        }

        return $this->permisoRepo->create($datos);
    }

    /**
     * Actualiza un permiso existente.
     *
     * @param int $id
     * @param array $datos
     * @return void
     * @throws NotFoundException
     * @throws ConflictException
     */
    public function actualizar(int $id, array $datos): void
    {
        if (!$this->permisoRepo->exists($id)) {
            throw new NotFoundException('Permiso no encontrado.');
        }

        if (isset($datos['nombre'])) {
            $existente = $this->permisoRepo->findByNombre($datos['nombre']);
            if ($existente && (int) $existente['id'] !== $id) {
                throw new ConflictException('Ya existe otro permiso con ese nombre.');
            }
        }

        $this->permisoRepo->update($id, $datos);
    }

    /**
     * Elimina un permiso si no está asignado a ningún rol.
     *
     * @param int $id
     * @return void
     * @throws NotFoundException
     * @throws DomainException
     */
    public function eliminar(int $id): void
    {
        if (!$this->permisoRepo->exists($id)) {
            throw new NotFoundException('Permiso no encontrado.');
        }

        if ($this->rolModuloPermisoRepo->countByPermisoId($id) > 0) {
            throw new DomainException('El permiso está asignado a roles; quítalo antes de eliminarlo.');
        }

        $this->permisoRepo->delete($id);
    }

    /**
     * Busca un permiso por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->permisoRepo->findById($id);
    }

    /**
     * Busca un permiso por su nombre.
     *
     * @param string $nombre
     * @return array|null
     */
    public function buscarPorNombre(string $nombre): ?array
    {
        return $this->permisoRepo->findByNombre($nombre);
    }

    /**
     * Obtiene todos los permisos ordenados por ID.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->permisoRepo->findAllOrderedById();
    }

    /**
     * Guarda la matriz de permisos de un rol en una transacción.
     *
     * Reemplaza la lógica inline de permisos_guardar.php (versión pre-139d).
     * Escribe en rol_modulo, rol_modulo_pagina y rol_modulo_permiso.
     *
     * Los permisos se insertan SIEMPRE, independiente de 'visible'.
     * Desmarcar visibilidad deja los permisos inertes: requirePageAccess
     * rechaza antes de llegar a requireOperation (ADR 008).
     *
     * @param int $idRol
     * @param array $paginas Array de ['id_pagina' => int, 'visible' => bool, 'permisos' => int[]]
     * @return void
     * @throws NotFoundException
     */
    public function guardarMatriz(int $idRol, array $paginas): void
    {
        if ($idRol <= 0) {
            throw new NotFoundException('Rol inválido.');
        }
        if (!$this->rolRepo->exists($idRol)) {
            throw new NotFoundException('Rol no encontrado.');
        }

        $this->transaction(function () use ($idRol, $paginas) {
            $rolModulos = $this->rolModuloRepo->findByRolId($idRol);
            foreach ($rolModulos as $rm) {
                $idRolModulo = (int) $rm['id'];
                $this->rolModuloPaginaRepo->deleteByRolModuloId($idRolModulo);
                $this->rolModuloPermisoRepo->deleteByRolModuloId($idRolModulo);
            }

            foreach ($paginas as $pagina) {
                $idPagina = (int) ($pagina['id_pagina'] ?? 0);
                if ($idPagina <= 0) {
                    continue;
                }
                $paginaRow = $this->paginaRepo->findById($idPagina);
                if (!$paginaRow) {
                    continue;
                }
                $idModulo = (int) $paginaRow['modulo_id'];
                $idRolModulo = $this->rolModuloRepo->ensure($idRol, $idModulo);

                if (!empty($pagina['visible'])) {
                    $this->rolModuloPaginaRepo->insertIgnore($idRolModulo, $idModulo, $idPagina);
                }

                $permisos = is_array($pagina['permisos'] ?? null) ? $pagina['permisos'] : [];
                foreach ($permisos as $idPermisoRaw) {
                    $idPermiso = (int) $idPermisoRaw;
                    if ($idPermiso <= 0) {
                        continue;
                    }
                    $this->rolModuloPermisoRepo->insertIgnore($idRolModulo, $idModulo, $idPagina, $idPermiso);
                }
            }
        });
    }

    /**
     * Devuelve la matriz de permisos de un rol: módulos con páginas,
     * visibilidad (rol_modulo_pagina) y permisos asignados
     * (rol_modulo_permiso), más el catálogo de permisos.
     *
     * Reemplaza el SQL inline de permisos_listar.php (versión pre-139d).
     *
     * @param int $idRol
     * @return array
     */
    public function listarMatriz(int $idRol): array
    {
        $catalogo = $this->permisoRepo->findAllOrderedById();

        $visibles = [];
        $asignados = [];
        if ($idRol > 0) {
            foreach ($this->rolModuloPaginaRepo->findByRolId($idRol) as $row) {
                $visibles[(int) $row['pagina_id']] = true;
            }
            foreach ($this->rolModuloPermisoRepo->findByRolId($idRol) as $row) {
                $idPag = (int) $row['pagina_id'];
                $idPer = (int) $row['permiso_id'];
                if (!isset($asignados[$idPag])) {
                    $asignados[$idPag] = [];
                }
                $asignados[$idPag][$idPer] = true;
            }
        }

        $modulos = $this->moduloRepo->findAllOrderedById();
        $paginas = $this->paginaRepo->findAllOrderedByModulo();

        $porModulo = [];
        foreach ($modulos as $m) {
            $porModulo[(int) $m['id']] = [
                'id_modulo' => (int) $m['id'],
                'nombre_modulo' => $m['nombre_modulo'],
                'vistas' => []
            ];
        }

        foreach ($paginas as $p) {
            $idModulo = (int) $p['modulo_id'];
            $idPag = (int) $p['id'];
            if (!isset($porModulo[$idModulo])) {
                continue;
            }
            $permisosPag = [];
            foreach ($catalogo as $perm) {
                $idPer = (int) $perm['id'];
                $permisosPag[] = [
                    'id_permiso' => $idPer,
                    'nombre' => $perm['nombre'],
                    'asignado' => isset($asignados[$idPag][$idPer])
                ];
            }
            $porModulo[$idModulo]['vistas'][] = [
                'id_pagina' => $idPag,
                'nombre_pagina' => $p['nombre_pagina'],
                'url_pagina' => $p['url_pagina'],
                'visible' => isset($visibles[$idPag]),
                'permisos' => $permisosPag
            ];
        }

        return [
            'modulos' => array_values($porModulo),
            'permisos_catalogo' => array_map(
                fn($p) => ['id_permiso' => (int) $p['id'], 'nombre' => $p['nombre']],
                $catalogo
            )
        ];
    }
}