// js/views/ReportesView.js
(function () {
    'use strict';

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setText(id, txt) {
        const el = document.getElementById(id);
        if (el) el.textContent = txt;
    }

    /**
     * Convierte un timestamp UTC naive ('YYYY-MM-DD HH:MM:SS') a hora local
     * del navegador. Mismo patrón que AuditoriaLogView.formatDateTime
     * (VERIFICACION.md §2.32). El backend fija time_zone='+00:00' y PHP a
     * UTC, por lo que todas las fechas TIMESTAMP/DATETIME vienen en UTC.
     *
     * Solo se usa para columnas TIMESTAMP/DATETIME (pagos.fecha_pago,
     * tickets.creado_en, tareas.creado_en). Las columnas DATE puros
     * (facturas.fecha_emision, facturas.fecha_vencimiento) se muestran
     * tal cual, sin conversión.
     */
    function formatDateTimeLocal(val) {
        if (!val) return '—';
        const s = String(val).replace('T', ' ').trim();
        const looksNaive = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(s);
        const iso = looksNaive ? s.replace(' ', 'T') + 'Z' : s;
        const d = new Date(iso);
        if (isNaN(d.getTime())) return s.slice(0, 16);
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} `
             + `${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function formatDateOnly(val) {
        if (!val) return '—';
        const s = String(val).slice(0, 10);
        return /^\d{4}-\d{2}-\d{2}$/.test(s) ? s : '—';
    }

    function formatUsd(val) {
        const n = Number(val || 0);
        return '$' + n.toFixed(2);
    }

    function statusClass(status) {
        const s = String(status || '').toLowerCase();
        if (['pagada', 'validado', 'cerrado', 'completada', 'activo'].includes(s)) return 'status-ok';
        if (['pendiente', 'parcial', 'en proceso', 'en curso', 'vencida'].includes(s)) return 'status-warning';
        return 'status-alert';
    }

    /**
     * Escapa un valor para CSV. Reglas del RFC 4180:
     *   - si el valor contiene coma, comilla doble, salto de línea o
     *     comienzo/espacio en blanco, se envuelve en comillas dobles.
     *   - las comillas dobles internas se duplican ("" en lugar de ").
     *
     * Separador ';' para compatibilidad con Excel en configuración regional
     * de Venezuela/España (Excel es-ES trata ',' como decimal). RFC 4180
     * sugiere ',' pero no es obligatorio, y el estándar de facto en
     * LatAm/UE es ';'. Sonar, Hubsoft y Splynx exportan con ';' en sus
     * locales regionales.
     */
    function csvEscape(val) {
        const s = String(val == null ? '' : val);
        if (/[;"\n\r]/.test(s) || s !== s.trim()) {
            return '"' + s.replace(/"/g, '""') + '"';
        }
        return s;
    }

    /**
     * Descarga un CSV con BOM UTF-8. El BOM (\uFEFF) es necesario para
     * que Excel en Windows abra el archivo con la codificación correcta
     * y no muestre "Ã±" en lugar de "ñ". Sin BOM, Excel interpreta el
     * archivo como ANSI/Latin-1 y rompe los acentos.
     *
     * Se usa Blob + URL.createObjectURL + <a download>. No hay librería
     * externa (constitución §1: sin dependencias).
     */
    function downloadCsv(filename, headers, rows) {
        const lines = [];
        lines.push(headers.map(csvEscape).join(';'));
        rows.forEach(row => {
            lines.push(row.map(csvEscape).join(';'));
        });
        const csv = '\uFEFF' + lines.join('\r\n') + '\r\n';
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    window.ReportesView = {
        /**
         * Pinta el reporte completo. Se llama desde el Controller después
         * de un load() exitoso. Los datos vienen del cache del Model.
         */
        render: function () {
            const data = window.ReporteModel ? window.ReporteModel.get() : null;
            if (!data) return;

            this.renderRango(data.rango);
            this.renderKPIs(data.kpis);
            this.renderFacturas(data.facturas);
            this.renderPagos(data.pagos);
            this.renderTickets(data.tickets);
            this.renderTareas(data.tareas);
        },

        /**
         * Muestra el rango activo en el header del reporte.
         *
         * Tres estados:
         *   - sin filtro (con_filtro=false) → "Todos los registros"
         *   - con filtro (con_filtro=true)  → "Del YYYY-MM-DD al YYYY-MM-DD"
         *   - fallback (rango incompleto)   → "—"
         *
         * Las fechas son DATE puros (no TIMESTAMP). No se convierten de
         * zona horaria. Se muestran tal cual.
         */
        renderRango: function (rango) {
            const el = document.getElementById('reportes-rango');
            if (!el) return;

            if (!rango) {
                el.textContent = '—';
                return;
            }

            if (rango.con_filtro === false || (!rango.desde && !rango.hasta)) {
                el.textContent = 'Todos los registros';
                return;
            }

            if (rango.desde && rango.hasta) {
                el.textContent = `Del ${esc(rango.desde)} al ${esc(rango.hasta)}`;
                return;
            }

            el.textContent = '—';
        },

        /**
         * KPIs del reporte.
         *
         * Bloque de facturación (los tres primeros SUMAN entre sí):
         *   facturado_usd = cobrado_aplicado_usd + pendiente_cobro_usd
         *
         * Bloque de pagos a cuenta (independiente, NO suma a los anteriores):
         *   pagos_a_cuenta_usd = pagos validados sin factura
         *
         * Bloque de tickets y tareas: "creados / abiertos hoy" y
         * "creadas / pendientes hoy".
         */
        renderKPIs: function (kpis) {
            if (!kpis) {
                setText('report-facturado-usd', '$0.00');
                setText('report-cobrado-aplicado-usd', '$0.00');
                setText('report-pendiente-usd', '$0.00');
                setText('report-pagos-a-cuenta-usd', '$0.00');
                setText('report-tickets-resumen', '0 / 0');
                setText('report-tareas-resumen', '0 / 0');
                return;
            }

            setText('report-facturado-usd', formatUsd(kpis.facturado_usd));
            setText('report-cobrado-aplicado-usd', formatUsd(kpis.cobrado_aplicado_usd));
            setText('report-pendiente-usd', formatUsd(kpis.pendiente_cobro_usd));
            setText('report-pagos-a-cuenta-usd', formatUsd(kpis.pagos_a_cuenta_usd));

            setText('report-tickets-resumen',
                `${Number(kpis.tickets_creados || 0)} / ${Number(kpis.tickets_abiertos_del_periodo || 0)}`);
            setText('report-tareas-resumen',
                `${Number(kpis.tareas_creadas || 0)} / ${Number(kpis.tareas_pendientes_del_periodo || 0)}`);
        },

        /**
         * Tabla de facturas. Empty state contextual.
         */
        renderFacturas: function (facturas) {
            const tbody = document.getElementById('report-facturas-list');
            if (!tbody) return;

            if (!facturas || facturas.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:16px;color:#64748b;">Sin facturas.</td></tr>';
                return;
            }

            tbody.innerHTML = facturas.map(f => {
                const nombre = `${f.nombres || ''} ${f.apellidos || ''}`.trim() || 'Sin cliente';
                const estado = String(f.estado || '').toLowerCase();
                return `<tr>
                    <td>${esc(nombre)}</td>
                    <td>${esc(f.cedula || '—')}</td>
                    <td>${esc(f.periodo || '—')}</td>
                    <td>${formatUsd(f.monto)}</td>
                    <td><span class="status ${statusClass(estado)}">${esc(f.estado || '—')}</span></td>
                    <td>${formatDateOnly(f.fecha_vencimiento)}</td>
                </tr>`;
            }).join('');
        },

        /**
         * Tabla de pagos. La columna "Factura" muestra el periodo de la
         * factura asociada (o "A cuenta" si factura_id es null). La
         * fecha_pago es DATETIME, se convierte a local.
         */
        renderPagos: function (pagos) {
            const tbody = document.getElementById('report-pagos-list');
            if (!tbody) return;

            if (!pagos || pagos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:16px;color:#64748b;">Sin pagos.</td></tr>';
                return;
            }

            tbody.innerHTML = pagos.map(p => {
                const nombre = `${p.nombres || ''} ${p.apellidos || ''}`.trim() || 'Sin cliente';
                const estado = String(p.estado || '').toLowerCase();
                return `<tr>
                    <td>${esc(nombre)}</td>
                    <td>${esc(p.cedula || '—')}</td>
                    <td>${esc(p.factura_periodo || 'A cuenta')}</td>
                    <td>${formatUsd(p.monto)}</td>
                    <td>${formatDateTimeLocal(p.fecha_pago)}</td>
                    <td>${esc(p.metodo_nombre || '—')}</td>
                    <td><span class="status ${statusClass(estado)}">${esc(p.estado || '—')}</span></td>
                </tr>`;
            }).join('');
        },

        /**
         * Tabla de tickets. Muestra categoría con swatch de color si el
         * backend la trae (JOIN con categorias_ticket).
         */
        renderTickets: function (tickets) {
            const tbody = document.getElementById('report-tickets-list');
            if (!tbody) return;

            if (!tickets || tickets.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:16px;color:#64748b;">Sin tickets.</td></tr>';
                return;
            }

            tbody.innerHTML = tickets.map(t => {
                const nombre = `${t.nombres || ''} ${t.apellidos || ''}`.trim() || 'Sin cliente';
                const estado = String(t.estado || '').toLowerCase();
                const prioridad = String(t.prioridad || '').toLowerCase();
                const prioClass = prioridad === 'alta' ? 'status-alert' : (prioridad === 'baja' ? 'status-ok' : 'status-warning');
                const catSwatch = t.categoria_color
                    ? `<span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:${esc(t.categoria_color)};border:1px solid #cbd5e1;margin-right:6px;vertical-align:middle;"></span>`
                    : '';
                return `<tr>
                    <td>${esc(t.asunto || '—')}</td>
                    <td>${esc(nombre)}</td>
                    <td>${esc(t.cedula || '—')}</td>
                    <td>${catSwatch}${esc(t.categoria_nombre || '—')}</td>
                    <td><span class="status ${prioClass}">${esc(t.prioridad || '—')}</span></td>
                    <td><span class="status ${statusClass(estado)}">${esc(t.estado || '—')}</span></td>
                    <td>${formatDateTimeLocal(t.creado_en)}</td>
                </tr>`;
            }).join('');
        },

        /**
         * Tabla de tareas.
         */
        renderTareas: function (tareas) {
            const tbody = document.getElementById('report-tareas-list');
            if (!tbody) return;

            if (!tareas || tareas.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:16px;color:#64748b;">Sin tareas.</td></tr>';
                return;
            }

            tbody.innerHTML = tareas.map(t => {
                const nombre = `${t.nombres || ''} ${t.apellidos || ''}`.trim() || 'Sin cliente';
                const estado = String(t.estado || '').toLowerCase();
                const prioridad = String(t.prioridad || '').toLowerCase();
                const prioClass = prioridad === 'alta' ? 'status-alert' : (prioridad === 'baja' ? 'status-ok' : 'status-warning');
                return `<tr>
                    <td>${esc(t.titulo || '—')}</td>
                    <td>${esc(nombre)}</td>
                    <td>${esc(t.cedula || '—')}</td>
                    <td><span class="status ${prioClass}">${esc(t.prioridad || '—')}</span></td>
                    <td><span class="status ${statusClass(estado)}">${esc(t.estado || '—')}</span></td>
                    <td>${formatDateTimeLocal(t.creado_en)}</td>
                </tr>`;
            }).join('');
        },

        /**
         * Listeners del bloque #reportes. Sin onclick inline (F-16).
         *
         * Acciones:
         *   - apply-range: aplica el rango de fechas de los inputs.
         *   - current-month: rellena los inputs al mes actual y aplica.
         *   - show-all: limpia el filtro y muestra todos los registros.
         *   - export-facturas, export-pagos, export-tickets, export-tareas:
         *     descarga la tabla correspondiente a CSV.
         *   - export-all: descarga un CSV consolidado con las 4 tablas
         *     separadas por líneas en blanco y encabezado.
         */
        _attachListeners: function () {
            document.querySelectorAll('#reportes [data-action="apply-range"]').forEach(btn => {
                btn.removeAttribute('onclick');
                btn.addEventListener('click', () => {
                    if (window.ReportesController) window.ReportesController.load();
                });
            });

            document.querySelectorAll('#reportes [data-action="current-month"]').forEach(btn => {
                btn.removeAttribute('onclick');
                btn.addEventListener('click', () => {
                    if (window.ReportesController) window.ReportesController.resetToCurrentMonth();
                });
            });

            document.querySelectorAll('#reportes [data-action="show-all"]').forEach(btn => {
                btn.removeAttribute('onclick');
                btn.addEventListener('click', () => {
                    if (window.ReportesController) window.ReportesController.showAll();
                });
            });

            document.querySelectorAll('#reportes [data-action="export-facturas"]').forEach(btn => {
                btn.removeAttribute('onclick');
                btn.addEventListener('click', () => this._exportFacturas());
            });
            document.querySelectorAll('#reportes [data-action="export-pagos"]').forEach(btn => {
                btn.removeAttribute('onclick');
                btn.addEventListener('click', () => this._exportPagos());
            });
            document.querySelectorAll('#reportes [data-action="export-tickets"]').forEach(btn => {
                btn.removeAttribute('onclick');
                btn.addEventListener('click', () => this._exportTickets());
            });
            document.querySelectorAll('#reportes [data-action="export-tareas"]').forEach(btn => {
                btn.removeAttribute('onclick');
                btn.addEventListener('click', () => this._exportTareas());
            });
            document.querySelectorAll('#reportes [data-action="export-all"]').forEach(btn => {
                btn.removeAttribute('onclick');
                btn.addEventListener('click', () => this._exportAll());
            });

            const form = document.getElementById('reportes-range-form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    if (window.ReportesController) window.ReportesController.load();
                });
            }
        },

        _filename: function (prefix) {
            const data = window.ReporteModel ? window.ReporteModel.get() : null;
            const r = (data && data.rango) || {};
            if (r.con_filtro === false || (!r.desde && !r.hasta)) {
                return `reporte-${prefix}-todos.csv`;
            }
            const desde = r.desde || 'inicio';
            const hasta = r.hasta || 'fin';
            return `reporte-${prefix}-${desde}_${hasta}.csv`;
        },

        _exportFacturas: function () {
            const data = window.ReporteModel ? window.ReporteModel.get() : null;
            if (!data || data.facturas.length === 0) {
                window.Toast.warning('No hay facturas para exportar.');
                return;
            }
            const headers = ['Cliente', 'Cédula', 'Periodo', 'Monto USD', 'Estado', 'Vencimiento'];
            const rows = data.facturas.map(f => [
                `${f.nombres || ''} ${f.apellidos || ''}`.trim(),
                f.cedula || '',
                f.periodo || '',
                Number(f.monto || 0).toFixed(2),
                f.estado || '',
                f.fecha_vencimiento || ''
            ]);
            downloadCsv(this._filename('facturas'), headers, rows);
            window.Toast.success(`Exportadas ${rows.length} facturas a CSV.`);
        },

        _exportPagos: function () {
            const data = window.ReporteModel ? window.ReporteModel.get() : null;
            if (!data || data.pagos.length === 0) {
                window.Toast.warning('No hay pagos para exportar.');
                return;
            }
            const headers = ['Cliente', 'Cédula', 'Factura', 'Monto USD', 'Fecha de pago', 'Método', 'Estado'];
            const rows = data.pagos.map(p => [
                `${p.nombres || ''} ${p.apellidos || ''}`.trim(),
                p.cedula || '',
                p.factura_periodo || 'A cuenta',
                Number(p.monto || 0).toFixed(2),
                p.fecha_pago || '',
                p.metodo_nombre || '',
                p.estado || ''
            ]);
            downloadCsv(this._filename('pagos'), headers, rows);
            window.Toast.success(`Exportados ${rows.length} pagos a CSV.`);
        },

        _exportTickets: function () {
            const data = window.ReporteModel ? window.ReporteModel.get() : null;
            if (!data || data.tickets.length === 0) {
                window.Toast.warning('No hay tickets para exportar.');
                return;
            }
            const headers = ['Asunto', 'Cliente', 'Cédula', 'Categoría', 'Prioridad', 'Estado', 'Creado'];
            const rows = data.tickets.map(t => [
                t.asunto || '',
                `${t.nombres || ''} ${t.apellidos || ''}`.trim(),
                t.cedula || '',
                t.categoria_nombre || '',
                t.prioridad || '',
                t.estado || '',
                t.creado_en || ''
            ]);
            downloadCsv(this._filename('tickets'), headers, rows);
            window.Toast.success(`Exportados ${rows.length} tickets a CSV.`);
        },

        _exportTareas: function () {
            const data = window.ReporteModel ? window.ReporteModel.get() : null;
            if (!data || data.tareas.length === 0) {
                window.Toast.warning('No hay tareas para exportar.');
                return;
            }
            const headers = ['Título', 'Cliente', 'Cédula', 'Prioridad', 'Estado', 'Creado'];
            const rows = data.tareas.map(t => [
                t.titulo || '',
                `${t.nombres || ''} ${t.apellidos || ''}`.trim(),
                t.cedula || '',
                t.prioridad || '',
                t.estado || '',
                t.creado_en || ''
            ]);
            downloadCsv(this._filename('tareas'), headers, rows);
            window.Toast.success(`Exportadas ${rows.length} tareas a CSV.`);
        },

        /**
         * Export consolidado. Genera un solo CSV con las 4 tablas
         * separadas por un encabezado de sección y una línea en blanco.
         * Útil para adjuntar a un correo o imprimir de una vez.
         *
         * La sección de KPIs ahora incluye "Cobrado aplicado" y "Pagos a
         * cuenta" como filas separadas, con nota explicativa de que
         * "Pagos a cuenta" es independiente y no suma a las otras.
         */
        _exportAll: function () {
            const data = window.ReporteModel ? window.ReporteModel.get() : null;
            if (!data || !data.kpis) {
                window.Toast.warning('No hay datos para exportar.');
                return;
            }

            const r = data.rango || {};
            const rangoTxt = (r.con_filtro === false || (!r.desde && !r.hasta))
                ? 'Todos los registros'
                : `${r.desde || ''} al ${r.hasta || ''}`;

            const parts = [];
            parts.push(['REPORTE OPERATIVO']);
            parts.push([`Rango: ${rangoTxt}`]);
            parts.push([]);
            parts.push(['KPIs de facturación']);
            parts.push(['Facturado USD', Number(data.kpis.facturado_usd || 0).toFixed(2)]);
            parts.push(['Cobrado aplicado USD', Number(data.kpis.cobrado_aplicado_usd || 0).toFixed(2)]);
            parts.push(['Pendiente de cobro USD', Number(data.kpis.pendiente_cobro_usd || 0).toFixed(2)]);
            parts.push(['Facturas', String(data.kpis.facturas_count || 0)]);
            parts.push([]);
            parts.push(['KPIs de pagos a cuenta (independiente)']);
            parts.push(['Pagos a cuenta USD', Number(data.kpis.pagos_a_cuenta_usd || 0).toFixed(2)]);
            parts.push(['Pagos totales', String(data.kpis.pagos_count || 0)]);
            parts.push([]);
            parts.push(['KPIs de tickets y tareas']);
            parts.push(['Tickets creados', String(data.kpis.tickets_creados || 0)]);
            parts.push(['Tickets abiertos hoy', String(data.kpis.tickets_abiertos_del_periodo || 0)]);
            parts.push(['Tareas creadas', String(data.kpis.tareas_creadas || 0)]);
            parts.push(['Tareas pendientes hoy', String(data.kpis.tareas_pendientes_del_periodo || 0)]);
            parts.push([]);

            parts.push(['FACTURAS']);
            parts.push(['Cliente', 'Cédula', 'Periodo', 'Monto USD', 'Estado', 'Vencimiento']);
            data.facturas.forEach(f => parts.push([
                `${f.nombres || ''} ${f.apellidos || ''}`.trim(),
                f.cedula || '',
                f.periodo || '',
                Number(f.monto || 0).toFixed(2),
                f.estado || '',
                f.fecha_vencimiento || ''
            ]));
            parts.push([]);

            parts.push(['PAGOS']);
            parts.push(['Cliente', 'Cédula', 'Factura', 'Monto USD', 'Fecha de pago', 'Método', 'Estado']);
            data.pagos.forEach(p => parts.push([
                `${p.nombres || ''} ${p.apellidos || ''}`.trim(),
                p.cedula || '',
                p.factura_periodo || 'A cuenta',
                Number(p.monto || 0).toFixed(2),
                p.fecha_pago || '',
                p.metodo_nombre || '',
                p.estado || ''
            ]));
            parts.push([]);

            parts.push(['TICKETS']);
            parts.push(['Asunto', 'Cliente', 'Cédula', 'Categoría', 'Prioridad', 'Estado', 'Creado']);
            data.tickets.forEach(t => parts.push([
                t.asunto || '',
                `${t.nombres || ''} ${t.apellidos || ''}`.trim(),
                t.cedula || '',
                t.categoria_nombre || '',
                t.prioridad || '',
                t.estado || '',
                t.creado_en || ''
            ]));
            parts.push([]);

            parts.push(['TAREAS']);
            parts.push(['Título', 'Cliente', 'Cédula', 'Prioridad', 'Estado', 'Creado']);
            data.tareas.forEach(t => parts.push([
                t.titulo || '',
                `${t.nombres || ''} ${t.apellidos || ''}`.trim(),
                t.cedula || '',
                t.prioridad || '',
                t.estado || '',
                t.creado_en || ''
            ]));

            const lines = parts.map(row => row.map(csvEscape).join(';'));
            const csv = '\uFEFF' + lines.join('\r\n') + '\r\n';
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = this._filename('completo');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(() => URL.revokeObjectURL(url), 1000);

            window.Toast.success('Reporte completo exportado a CSV.');
        }
    };
})();