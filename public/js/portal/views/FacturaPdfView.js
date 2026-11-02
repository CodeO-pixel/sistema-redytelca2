// js/portal/views/FacturaPdfView.js
// View del portal que genera un "PDF" de la factura usando window.print().
//
// Estrategia (fundamento: Splynx, ISPs venezolanos, patrón browser-print):
//   1. Abrir un popup con HTML print-friendly (@media print CSS embebido).
//   2. Escribir los datos de la factura + líneas + totales.
//   3. Cerrar el documento.
//   4. Disparar window.print() después de 250ms (dar tiempo al layout).
//
// El usuario elige "Guardar como PDF" en el diálogo nativo del navegador.
// Sin librerías externas (constitución §1: sin dependencias).
//
// El PDF NO es fiscal. SENIAT exige número de control, RIF del emisor y
// del receptor, y datos fiscales que el esquema actual no tiene. El
// documento es informativo, con aviso explícito en el footer. Deuda
// FACTURA-FISCAL-SENIAT → Fase 7.
//
// formatDual se reusa de FacturaView (misma lógica que la tabla y el
// detalle). Si en el futuro el helper cambia, se cambia en un solo lugar.
//
// FIXES 2026-11-02 (auditoría del portal):
//   - A6: `<base href="...">` en el `<head>` del popup. El popup tiene
//     URL `about:blank`, por lo que una ruta relativa `<img src="...">`
//     no resuelve y el logo se oculta (onerror). Con `<base>` apuntando
//     al directorio del portal, `branding-logo.php?slot=login` resuelve
//     a `http://host/[dir]/branding-logo.php?slot=login`. Fundamento:
//     "Do not use relative URLs, use full URLs" + `<base>` tag pattern.
//   - A6: `logo` siempre apunta a `branding-logo.php?slot=login`. El
//     endpoint hace fallback server-side: DB → archivo → hardcoded.
//     Antes usaba `cfg.logo_path` (ruta cruda, 404 si el archivo no
//     existe).
//   - B3: si `factura.total_lineas > 0` y `lineas` está vacío (aún
//     cargando), muestra "Cargando líneas…" en vez de "Sin líneas
//     registradas". Mismo criterio que `FacturaView.renderDetailModal`.
(function () {
    'use strict';

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(val) {
        if (!val) return '—';
        return String(val).slice(0, 10);
    }

    /**
     * Devuelve {nombre, cedula} del cliente del portal.
     * Prioridad: factura.nombres → PortalSession.cliente → 'Sin cliente'.
     * Mismo criterio que FacturaView.getDatosCliente.
     */
    function getDatosCliente(factura) {
        const session = window.PortalSession ? window.PortalSession.get() : null;
        const clienteSesion = session && session.cliente ? session.cliente : null;
        const nombreFactura = `${factura.nombres || ''} ${factura.apellidos || ''}`.trim();
        const nombreSesion = clienteSesion
            ? `${clienteSesion.nombre || ''} ${clienteSesion.apellido || ''}`.trim()
            : '';
        return {
            nombre: nombreFactura || nombreSesion || 'Sin cliente',
            cedula: (factura.cedula || (clienteSesion ? clienteSesion.cedula : '') || '—')
        };
    }

    function getEmpresaData() {
        const cfg = window.ConfiguracionEmpresaModel && window.ConfiguracionEmpresaModel.cache
            ? window.ConfiguracionEmpresaModel.cache.publico
            : null;
        return {
            nombre: (cfg && cfg.nombre_empresa) || 'REDYTELCA',
            // A6: siempre el endpoint con fallback. No usar cfg.logo_path
            // (ruta cruda, 404 si el archivo no existe). `branding-logo.php`
            // valida DB → archivo → hardcoded y sirve el Content-Type real.
            logo: 'branding-logo.php?slot=login',
            color: (cfg && cfg.color_primario) || '#104aa8'
        };
    }

    /**
     * Base URL del documento actual. Funciona tanto si el portal se
     * sirve desde `http://localhost/portal_cliente.php` (base =
     * `http://localhost/`) como desde un subdirectorio
     * (`http://localhost/redytelca/public/`). El `<base>` resuelve
     * los `src`/`href` relativos del popup `about:blank`.
     */
    function getBaseHref() {
        try {
            return new URL('.', window.location.href).href;
        } catch (e) {
            return (window.location.origin || '') + '/';
        }
    }

    /**
     * Convierte un monto USD al HTML dual (mismo formato que FacturaView).
     * Si la factura es VES, muestra "$X (Bs. Y)". Si es USD, solo "$X".
     */
    function dualText(usd, factura) {
        const n = Number(usd || 0);
        if (factura && factura.moneda === 'VES' && factura.tasa_cambio && Number(factura.tasa_cambio) > 0) {
            const bs = (n * Number(factura.tasa_cambio)).toFixed(2);
            return `$${n.toFixed(2)} <small>(Bs. ${bs})</small>`;
        }
        return `$${n.toFixed(2)}`;
    }

    window.PortalFacturaPdfView = {
        /**
         * Abre el popup con la factura print-friendly y dispara window.print().
         *
         * @param {Object} factura
         * @param {Array} lineas
         */
        print: function (factura, lineas) {
            if (!factura) {
                if (window.Toast) window.Toast.error('Factura no encontrada.');
                return;
            }

            const win = window.open('', '_blank', 'width=900,height=700');
            if (!win) {
                if (window.Toast) {
                    window.Toast.error('El navegador bloqueó la ventana. Habilita las ventanas emergentes para descargar el PDF.');
                }
                return;
            }

            const empresa = getEmpresaData();
            const cliente = getDatosCliente(factura);
            const baseHref = getBaseHref();
            const monedaLabel = factura.moneda === 'VES' && factura.tasa_cambio
                ? `VES (tasa ${Number(factura.tasa_cambio).toFixed(4)} Bs/USD)`
                : 'USD';

            const lineasArr = Array.isArray(lineas) ? lineas : [];
            const totalLineas = Number(factura.total_lineas || 0);
            const linesLoading = totalLineas > 0 && lineasArr.length === 0;

            let lineasHtml;
            if (linesLoading) {
                lineasHtml = `<tr><td colspan="6" class="empty">Cargando líneas…</td></tr>`;
            } else if (lineasArr.length > 0) {
                lineasHtml = lineasArr.map(l => `
                    <tr>
                        <td>${esc(l.concepto)}</td>
                        <td class="num">${Number(l.cantidad || 0).toFixed(2)}</td>
                        <td class="num">${dualText(l.precio_unitario, factura)}</td>
                        <td class="num">${dualText(l.monto_linea, factura)}</td>
                        <td class="num">${Number(l.impuesto_porcentaje || 0).toFixed(2)}%</td>
                        <td class="num">${dualText(l.total_linea, factura)}</td>
                    </tr>
                `).join('');
            } else {
                lineasHtml = `<tr><td colspan="6" class="empty">Sin líneas registradas.</td></tr>`;
            }

            const monto = Number(factura.monto || 0);
            const pagado = Number(factura.total_pagado || 0);
            const saldo = Math.max(0, monto - pagado);

            const html = `<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<base href="${esc(baseHref)}">
<title>Factura ${esc(factura.periodo || '')} — ${esc(empresa.nombre)}</title>
<style>
    @page { size: A4; margin: 18mm; }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: -apple-system, 'Segoe UI', Roboto, sans-serif;
        font-size: 12px;
        color: #0f172a;
        background: #fff;
    }
    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding-bottom: 14px;
        border-bottom: 3px solid ${esc(empresa.color)};
        margin-bottom: 18px;
    }
    .header-left { display: flex; align-items: center; gap: 14px; }
    .header-left img { max-height: 60px; max-width: 180px; }
    .header-left .empresa-nombre { font-size: 18px; font-weight: 700; color: #0f172a; }
    .header-right { text-align: right; }
    .header-right h1 { margin: 0; font-size: 22px; color: ${esc(empresa.color)}; letter-spacing: 0.04em; }
    .header-right .periodo { font-size: 14px; color: #475569; margin-top: 2px; }

    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
    .card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 14px;
    }
    .card-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        margin-bottom: 4px;
    }
    .card-value { font-size: 12px; font-weight: 600; }
    .card-value + .card-value { margin-top: 4px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    th, td {
        padding: 8px 10px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: top;
    }
    th {
        background: #f8fafc;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        font-weight: 600;
    }
    td.num, th.num { text-align: right; white-space: nowrap; }
    td small { color: #64748b; font-size: 10px; }
    td.empty { text-align: center; color: #94a3b8; padding: 16px; }

    .totales {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 6px;
        margin-bottom: 18px;
    }
    .totales .row {
        display: flex;
        justify-content: space-between;
        width: 260px;
        padding: 6px 10px;
        font-size: 12px;
    }
    .totales .row.total {
        background: #f8fafc;
        border-top: 2px solid #cbd5e1;
        font-weight: 700;
        font-size: 13px;
    }
    .totales .row.saldo { color: ${esc(empresa.color)}; font-weight: 700; }

    .footer {
        margin-top: 24px;
        padding-top: 12px;
        border-top: 1px solid #e2e8f0;
        font-size: 10px;
        color: #64748b;
        text-align: center;
        line-height: 1.5;
    }
    .footer .aviso {
        color: #b45309;
        font-weight: 600;
        margin-bottom: 4px;
    }
</style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <img src="${esc(empresa.logo)}" alt="${esc(empresa.nombre)}" onerror="this.style.display='none'">
        <div class="empresa-nombre">${esc(empresa.nombre)}</div>
    </div>
    <div class="header-right">
        <h1>FACTURA</h1>
        <div class="periodo">Periodo ${esc(factura.periodo || '—')}</div>
    </div>
</div>

<div class="grid">
    <div class="card">
        <div class="card-label">Cliente</div>
        <div class="card-value">${esc(cliente.nombre)}</div>
        <div class="card-value">C.I. ${esc(cliente.cedula)}</div>
    </div>
    <div class="card">
        <div class="card-label">Factura</div>
        <div class="card-value">Periodo: ${esc(factura.periodo || '—')}</div>
        <div class="card-value">Estado: ${esc(factura.estado || '—')}</div>
        <div class="card-value">Emisión: ${esc(formatDate(factura.fecha_emision))}</div>
        <div class="card-value">Vencimiento: ${esc(formatDate(factura.fecha_vencimiento))}</div>
    </div>
</div>

<div class="grid">
    <div class="card">
        <div class="card-label">Servicio</div>
        <div class="card-value">${esc(factura.servicio_alias || '—')}</div>
    </div>
    <div class="card">
        <div class="card-label">Moneda de presentación</div>
        <div class="card-value">${esc(monedaLabel)}</div>
    </div>
</div>

<h3 style="margin: 0 0 8px; font-size: 13px; color: #0f172a;">Detalle de conceptos</h3>
<table>
    <thead>
        <tr>
            <th>Concepto</th>
            <th class="num">Cantidad</th>
            <th class="num">P. Unitario</th>
            <th class="num">Monto</th>
            <th class="num">Impuesto</th>
            <th class="num">Total</th>
        </tr>
    </thead>
    <tbody>
        ${lineasHtml}
    </tbody>
</table>

<div class="totales">
    <div class="row"><span>Total factura</span><span>${dualText(monto, factura)}</span></div>
    <div class="row"><span>Pagado</span><span>${dualText(pagado, factura)}</span></div>
    <div class="row total"><span>Saldo pendiente</span><span class="saldo">${dualText(saldo, factura)}</span></div>
</div>

<div class="footer">
    <div class="aviso">Documento informativo. No constituye factura fiscal.</div>
    <div>${esc(empresa.nombre)} · Factura ${esc(factura.periodo || '')} · Generada el ${esc(new Date().toLocaleString('es-VE'))}</div>
</div>

<script>
    // Auto-print después de que el layout esté listo.
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 250);
    });
</script>
</body>
</html>`;

            win.document.open();
            win.document.write(html);
            win.document.close();
        }
    };
})();