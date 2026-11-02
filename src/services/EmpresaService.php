<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\ValidationException;
use App\Repositories\ConfiguracionEmpresaRepository;

/**
 * Servicio para la gestión de la configuración de la empresa.
 */
final class EmpresaService extends BaseService
{
    /** @var ConfiguracionEmpresaRepository */
    private ConfiguracionEmpresaRepository $configuracionRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->configuracionRepo = new ConfiguracionEmpresaRepository($pdo);
    }

    /**
     * Obtiene la configuración global de la empresa.
     *
     * @return array|null
     */
    public function obtener(): ?array
    {
        return $this->configuracionRepo->findSingleton();
    }

    /**
     * Obtiene únicamente los datos públicos de branding de la empresa.
     *
     * @return array|null
     */
    public function obtenerBranding(): ?array
    {
        return $this->configuracionRepo->findPublicBranding();
    }

    /**
     * Actualiza la configuración de la empresa.
     *
     * @param array $datos
     * @return void
     * @throws ValidationException
     */
    public function actualizar(array $datos): void
    {
        $this->validarConfiguracion($datos);

        if (!empty($datos)) {
            $this->configuracionRepo->update(1, $datos);
        }
    }

    /**
     * Método privado. Valida los rangos de configuración antes de persistir.
     * No se llama desde fuera del service.
     *
     * @param array $datos
     * @return void
     * @throws ValidationException
     */
    private function validarConfiguracion(array $datos): void
    {
        if (isset($datos['impuesto_porcentaje'])) {
            if (!is_numeric($datos['impuesto_porcentaje']) || $datos['impuesto_porcentaje'] < 0 || $datos['impuesto_porcentaje'] > 100) {
                throw new ValidationException(['impuesto_porcentaje' => 'El impuesto debe estar entre 0 y 100.']);
            }
        }

        if (isset($datos['dia_facturacion'])) {
            $dia = (int) $datos['dia_facturacion'];
            if (!is_numeric($datos['dia_facturacion']) || $dia < 1 || $dia > 31) {
                throw new ValidationException(['dia_facturacion' => 'El día de facturación debe estar entre 1 y 31.']);
            }
        }

        if (isset($datos['dias_gracia_mora']) && $datos['dias_gracia_mora'] < 0) {
            throw new ValidationException(['dias_gracia_mora' => 'Los días de gracia no pueden ser negativos.']);
        }

        if (isset($datos['monto_recargo_mora']) && $datos['monto_recargo_mora'] < 0) {
            throw new ValidationException(['monto_recargo_mora' => 'El recargo por mora no puede ser negativo.']);
        }

        if (isset($datos['dias_corte_automatico']) && $datos['dias_corte_automatico'] < 0) {
            throw new ValidationException(['dias_corte_automatico' => 'Los días de corte no pueden ser negativos.']);
        }

        if (isset($datos['color_primario'])) {
            $color = trim((string) $datos['color_primario']);
            if ($color !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                throw new ValidationException(['color_primario' => 'El color debe tener formato #RRGGBB.']);
            }
        }
    }
}