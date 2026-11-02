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
 *
 * Fase 4.3: la matriz expone permisos_aplicables por vista.
 */
final class PermisoService extends BaseService
{
    private const PERMISOS_POR_PAGINA = [
        'dash'           => [1],
        'clientes'       => [1, 2, 3, 4],
        'servicios'      => [1, 2, 3, 4],
        'tareas'         => [1, 2, 3, 4],
        'tickets'        => [1, 2, 3, 4],
        'mapa'           => [1],
        'reportes'       => [1],
        'nodos'          => [1, 2, 3, 4],
        'olts'           => [1, 2, 3, 4],
        'naps'           => [1, 2, 3, 4],
        'equipos'        => [1, 2, 3, 4],
        'facturas'       => [1, 2, 3, 4],
        'pagos'          => [1, 2, 3, 4, 5],
        'planes'         => [1, 2, 3, 4],
        'contratos'      => [1, 2, 3, 4],
        'pedidos'        => [1, 2, 3, 4],
        'notificaciones' => [1, 3],
        'usuarios'       => [1, 2, 3, 4],
        'roles'          => [1, 2, 3, 4],
        'permisos'       => [1, 2, 3, 4],
        'password'       => [1, 3],
        'empresa'        => [1, 3],
        'auditoria'      => [1],
    ];

    private PermisoRepository $permisoRepo;
    private RolRepository $rolRepo;
    private PaginaRepository $paginaRepo;
    private RolModuloRepository $rolModuloRepo;
    private RolModuloPaginaRepository $rolModuloPaginaRepo;
    private RolModuloPermisoRepository $rolModuloPermisoRepo;
    private ModuloRepository $moduloRepo;

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

    public function crear(array $datos): int
    {
        if (isset($datos['nombre']) && $this->permisoRepo->findByNombre($datos['nombre'])) {
            throw new ConflictException('Ya existe un permiso con ese nombre.');
        }
        return $this->permisoRepo->create($datos);
    }

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

    public function buscarPorId(int $id): ?array
    {
        return $this->permisoRepo->findById($id);
    }

    public function buscarPorNombre(string $nombre): ?array
    {
        return $this->permisoRepo->findByNombre($nombre);
    }

    public function listar(): array
    {
        return $this->permisoRepo->findAllOrderedById();
    }

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
            $urlPag = (string) ($p['url_pagina'] ?? '');
            $aplicables = self::PERMISOS_POR_PAGINA[$urlPag] ?? [1, 2, 3, 4];

            $permisosPag = [];
            foreach ($catalogo as $perm) {
                $idPer = (int) $perm['id'];
                if (!in_array($idPer, $aplicables, true)) {
                    continue;
                }
                $permisosPag[] = [
                    'id_permiso' => $idPer,
                    'nombre' => $perm['nombre'],
                    'asignado' => isset($asignados[$idPag][$idPer])
                ];
            }

            $porModulo[$idModulo]['vistas'][] = [
                'id_pagina' => $idPag,
                'nombre_pagina' => $p['nombre_pagina'],
                'url_pagina' => $urlPag,
                'visible' => isset($visibles[$idPag]),
                'permisos' => $permisosPag,
                'permisos_aplicables' => $aplicables
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