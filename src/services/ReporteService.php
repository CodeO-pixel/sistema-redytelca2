<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BaseService;
use App\Exceptions\ValidationException;
use App\Repositories\FacturaRepository;
use App\Repositories\PagoRepository;
use App\Repositories\TicketRepository;
use App\Repositories\TareaRepository;

/**
 * Servicio para el reporte operativo con filtro de periodo opcional.
 *
 * Diseño (Splynx Aging Report, Sonar BI, Hubsoft Relatórios):
 *
 *   - Sin filtro por defecto. Al abrir Reportes, el operador ve TODOS
 *     los registros: todas las facturas (no anuladas), todos los pagos,
 *     todos los tickets, todas las tareas. Es lo que ve en el módulo
 *     Facturación / Pagos / Tickets / Tareas. Sin filtro no hay sorpresas
 *     del tipo "veo 10 facturas allá y 6 acá".
 *
 *   - Filtro opcional. Si el operador pone desde/hasta, las 4 tablas se
 *     filtran por fecha y los KPIs se recalculan. El operador ve el
 *     subconjunto del rango.
 *
 *   - KPIs que suman. La regla dura:
 *       facturado = cobrado_aplicado + pendiente
 *     Siempre. Sin residuo. El "cobrado_aplicado" es la suma de pagos
 *     validados que apuntan a las facturas del reporte, sin importar
 *     cuándo se hicieron esos pagos. El "pendiente" es el saldo real
 *     de esas facturas. Así los tres números son coherentes.
 *
 *   - "pagos_a_cuenta" es un KPI SEPARADO. No suma ni resta a los otros
 *     tres. Son los pagos validados del rango que no tienen factura
 *     asociada (factura_id IS NULL). Antes se mezclaban con "cobrado",
 *     produciendo el "$5 fantasma". Ahora tienen su propio lugar.
 *
 *   - KPIs de tickets y tareas: "creados en el rango" y "abiertos hoy
 *     de los creados en el rango". El esquema no guarda histórico de
 *     estado; reconstruir el estado al cierre del rango requeriría
 *     parsear auditoria_logs, costoso y frágil. Splynx/Sonar muestran
 *     "open tickets in period" como "de los creados en el periodo,
 *     cuántos siguen abiertos hoy". Refleja eso.
 */
final class ReporteService extends BaseService
{
    /** @var FacturaRepository */
    private FacturaRepository $facturaRepo;

    /** @var PagoRepository */
    private PagoRepository $pagoRepo;

    /** @var TicketRepository */
    private TicketRepository $ticketRepo;

    /** @var TareaRepository */
    private TareaRepository $tareaRepo;

    /**
     * Constructor del servicio.
     *
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->facturaRepo = new FacturaRepository($pdo);
        $this->pagoRepo = new PagoRepository($pdo);
        $this->ticketRepo = new TicketRepository($pdo);
        $this->tareaRepo = new TareaRepository($pdo);
    }

    /**
     * Genera el reporte completo.
     *
     * Si $desde y $hasta son ambos null o vacíos, NO se filtra: devuelve
     * todos los registros (facturas no anuladas, todos los pagos, todos
     * los tickets, todas las tareas).
     *
     * Si $desde y $hasta vienen ambos, se filtra por fecha.
     *
     * Si solo viene uno, el otro se completa con el mes de la fecha
     * provista (primer día del mes a último día del mes). Es el atajo
     * "puse solo el mes, dame el mes completo" de Splynx.
     *
     * @param string|null $desde YYYY-MM-DD (inclusive). Null = sin filtro.
     * @param string|null $hasta YYYY-MM-DD (inclusive). Null = sin filtro.
     * @return array
     * @throws ValidationException Si el rango es inválido.
     */
    public function generar(?string $desde, ?string $hasta): array
    {
        [$desdeNorm, $hastaNorm, $conFiltro] = $this->normalizarRango($desde, $hasta);

        if ($conFiltro) {
            $facturas = $this->facturaRepo->findAllByFechaEmisionRange($desdeNorm, $hastaNorm);
            $pagos = $this->pagoRepo->findAllByFechaPagoRange($desdeNorm, $hastaNorm);
            $tickets = $this->ticketRepo->findAllByCreadoEnRange($desdeNorm, $hastaNorm);
            $tareas = $this->tareaRepo->findAllByCreadoEnRange($desdeNorm, $hastaNorm);
        } else {
            $facturas = $this->facturaRepo->findAll();
            $pagos = $this->pagoRepo->findAll();
            $tickets = $this->ticketRepo->findAllOrderedByCreadoEnDesc();
            $tareas = $this->tareaRepo->findAllOrderedByCreadoEnDesc();
        }

        $kpis = $this->calcularKPIs($facturas, $pagos, $tickets, $tareas);

        return [
            'rango' => [
                'desde' => $desdeNorm,
                'hasta' => $hastaNorm,
                'con_filtro' => $conFiltro,
            ],
            'kpis' => $kpis,
            'facturas' => $facturas,
            'pagos' => $pagos,
            'tickets' => $tickets,
            'tareas' => $tareas,
        ];
    }

    /**
     * Normaliza y valida el rango.
     *
     * Reglas:
     *   - ambos null/vacíos → sin filtro.
     *   - solo uno provisto → el otro se completa con el mes del provisto.
     *   - ambos provistos → se usan tal cual.
     *   - formato YYYY-MM-DD estricto, fecha real, desde <= hasta.
     *
     * @param string|null $desde
     * @param string|null $hasta
     * @return array{0: ?string, 1: ?string, 2: bool} [desde, hasta, conFiltro]
     * @throws ValidationException
     */
    private function normalizarRango(?string $desde, ?string $hasta): array
    {
        $desdeVacio = ($desde === null || $desde === '');
        $hastaVacio = ($hasta === null || $hasta === '');

        if ($desdeVacio && $hastaVacio) {
            return [null, null, false];
        }

        // Si solo viene uno, completar el otro con el mes del provisto.
        if ($desdeVacio && !$hastaVacio) {
            $h = \DateTimeImmutable::createFromFormat('!Y-m-d', $hasta);
            if ($h === false || $h->format('Y-m-d') !== $hasta) {
                throw new ValidationException(['hasta' => 'Fecha inválida.']);
            }
            return [$h->format('Y-m-01'), $hasta, true];
        }

        if (!$desdeVacio && $hastaVacio) {
            $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $desde);
            if ($d === false || $d->format('Y-m-d') !== $desde) {
                throw new ValidationException(['desde' => 'Fecha inválida.']);
            }
            return [$desde, $d->format('Y-m-t'), true];
        }

        // Ambos provistos: validar.
        $errores = [];

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $errores['desde'] = 'Formato esperado YYYY-MM-DD.';
        } else {
            $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $desde);
            if ($d === false || $d->format('Y-m-d') !== $desde) {
                $errores['desde'] = 'Fecha inválida.';
            }
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $errores['hasta'] = 'Formato esperado YYYY-MM-DD.';
        } else {
            $h = \DateTimeImmutable::createFromFormat('!Y-m-d', $hasta);
            if ($h === false || $h->format('Y-m-d') !== $hasta) {
                $errores['hasta'] = 'Fecha inválida.';
            }
        }

        if (empty($errores) && $desde > $hasta) {
            $errores['desde'] = 'La fecha "desde" no puede ser posterior a "hasta".';
        }

        if (!empty($errores)) {
            throw new ValidationException($errores);
        }

        return [$desde, $hasta, true];
    }

    /**
     * Calcula los KPIs del reporte.
     *
     * KPIs de facturación (los tres primeros SUMAN entre sí):
     *   - facturado_usd: suma de facturas no anuladas.
     *   - cobrado_aplicado_usd: suma de pagos validados que apuntan a
     *     las facturas del reporte. Sin importar cuándo se hicieron.
     *     Garantiza la identidad: facturado = cobrado + pendiente.
     *   - pendiente_cobro_usd: facturado − cobrado_aplicado.
     *
     * KPI de pagos a cuenta (independiente, NO suma a los anteriores):
     *   - pagos_a_cuenta_usd: pagos validados del rango sin factura
     *     (factura_id IS NULL). Antes se mezclaba con "cobrado",
     *     produciendo el "$5 fantasma". Ahora tiene su propio lugar.
     *
     * REGLA DE RENDIMIENTO (A2-B.4.c, fix REPORTES-N+1):
     *   Antes se hacía N+1: una query a sumValidadosByFacturaId() por
     *   cada factura. Ahora se hace UNA SOLA query batch con
     *   sumValidadosByFacturaIds() que devuelve [factura_id => total].
     *   OWASP ASVS 5.0 §V1.4.3 / §V11.1.4 prohíben queries dentro de
     *   loops (CWE-400 Uncontrolled Resource Consumption).
     *
     * @param array $facturas
     * @param array $pagos
     * @param array $tickets
     * @param array $tareas
     * @return array
     */
    private function calcularKPIs(array $facturas, array $pagos, array $tickets, array $tareas): array
    {
        // FACTURADO + COBRADO APLICADO (batch, sin N+1)
        $facturadoUsd = 0.0;
        $cobradoAplicadoUsd = 0.0;
        $facturasValidas = 0;
        $idsFacturasValidas = [];

        foreach ($facturas as $f) {
            if (strtolower((string) ($f['estado'] ?? '')) === 'anulada') {
                continue;
            }
            $facturadoUsd += (float) ($f['monto'] ?? 0);
            $facturasValidas++;
            $idsFacturasValidas[] = (int) $f['id'];
        }

        // UNA SOLA query para todos los totales validados.
        $totalesPorFactura = $this->pagoRepo->sumValidadosByFacturaIds($idsFacturasValidas);

        foreach ($idsFacturasValidas as $idFactura) {
            $cobradoAplicadoUsd += $totalesPorFactura[$idFactura] ?? 0.0;
        }

        $pendienteUsd = max(0.0, $facturadoUsd - $cobradoAplicadoUsd);

        // PAGOS A CUENTA (independiente): pagos validados del rango sin factura.
        $pagosACuentaUsd = 0.0;
        foreach ($pagos as $p) {
            if (strtolower((string) ($p['estado'] ?? '')) !== 'validado') {
                continue;
            }
            if ($p['factura_id'] === null) {
                $pagosACuentaUsd += (float) ($p['monto'] ?? 0);
            }
        }

        // TICKETS: creados en el rango y abiertos hoy.
        $ticketsAbiertos = 0;
        foreach ($tickets as $t) {
            if (strtolower((string) ($t['estado'] ?? '')) !== 'cerrado') {
                $ticketsAbiertos++;
            }
        }

        // TAREAS: creadas en el rango y pendientes hoy.
        $tareasPendientes = 0;
        foreach ($tareas as $t) {
            if (strtolower((string) ($t['estado'] ?? '')) !== 'completada') {
                $tareasPendientes++;
            }
        }

        return [
            'facturado_usd' => round($facturadoUsd, 2),
            'cobrado_aplicado_usd' => round($cobradoAplicadoUsd, 2),
            'pendiente_cobro_usd' => round($pendienteUsd, 2),
            'pagos_a_cuenta_usd' => round($pagosACuentaUsd, 2),
            'facturas_count' => $facturasValidas,
            'pagos_count' => count($pagos),
            'tickets_creados' => count($tickets),
            'tickets_abiertos_del_periodo' => $ticketsAbiertos,
            'tareas_creadas' => count($tareas),
            'tareas_pendientes_del_periodo' => $tareasPendientes,
        ];
    }
}