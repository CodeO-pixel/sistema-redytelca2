// js/views/DashboardView.js
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

    function setHtml(id, html) {
        const el = document.getElementById(id);
        if (el) el.innerHTML = html;
    }

    function accentRgb() {
        return getComputedStyle(document.documentElement)
            .getPropertyValue('--accent-rgb')
            .trim() || '16, 74, 168';
    }

    function accentColor() {
        return getComputedStyle(document.documentElement)
            .getPropertyValue('--accent')
            .trim() || '#104aa8';
    }

    window.DashboardView = {
        /**
         * Pinta la fecha del header con la fecha local del navegador.
         * Mismo criterio que Auditoría y Perfil.
         */
        renderDate: function () {
            const d = new Date();
            setText('dashboard-date', d.toLocaleDateString('es-ES', {
                day: '2-digit', month: 'long', year: 'numeric'
            }));
        },

        /**
         * Pinta los KPIs de conteo y breakdowns.
         *
         * - "Total clientes": usa window.__dashboardTotals.clientes (que el
         *   Controller obtiene de res.total del endpoint paginado). NO usa
         *   cache.length, porque ClienteModel.load() pagina a 10 registros.
         * - "Servicios activos", "Contratos", "Tickets abiertos",
         *   "Tareas pendientes", "OLTs", "NAPs", "Facturas vencidas",
         *   "Pagos pendientes": cache.length de cada Model. Esos endpoints
         *   NO son paginados, devuelven todos los registros.
         */
        renderKPIs: function () {
            const clientes = window.ClienteModel ? window.ClienteModel.cache : [];
            const servicios = window.ServicioModel ? window.ServicioModel.cache : [];
            const contratos = window.ContratoModel ? window.ContratoModel.cache : [];
            const tickets = window.TicketModel ? window.TicketModel.cache : [];
            const tareas = window.TareaModel ? window.TareaModel.cache : [];
            const olts = window.OltModel ? window.OltModel.cache : [];
            const naps = window.NapModel ? window.NapModel.cache : [];
            const facturas = window.FacturaModel ? window.FacturaModel.cache : [];
            const pagos = window.PagoModel ? window.PagoModel.cache : [];

            const totals = window.__dashboardTotals || {};

            // Total clientes (total real del backend, no la página actual)
            const totalClientes = (typeof totals.clientes === 'number' && totals.clientes > 0)
                ? totals.clientes
                : clientes.length;
            setText('preview-clientes-count', totalClientes);

            // Servicios activos / total
            const serviciosActivos = servicios.filter(s =>
                String(s.estado_comercial || '').toLowerCase() === 'activo'
            ).length;
            setText('preview-servicios-count', `${serviciosActivos}/${servicios.length}`);

            const srvBreakdown = servicios.reduce((acc, s) => {
                const e = String(s.estado_comercial || 'pendiente').toLowerCase();
                acc[e] = (acc[e] || 0) + 1;
                return acc;
            }, {});
            setHtml('preview-servicios-breakdown', Object.entries(srvBreakdown).map(([k, v]) =>
                `<div><span>${esc(k)}</span> <strong>${v}</strong></div>`
            ).join(''));

            // Contratos: vigentes vs otros
            const contratosVigentes = contratos.filter(c =>
                String(c.estado || '').toLowerCase() === 'vigente'
            ).length;
            setText('preview-contratos-count', contratos.length);
            setHtml('preview-contratos-breakdown',
                `<div><span>Vigentes</span> <strong>${contratosVigentes}</strong></div>` +
                `<div><span>Otros</span> <strong>${contratos.length - contratosVigentes}</strong></div>`
            );

            // Tickets abiertos (todo lo que no está Cerrado)
            const ticketsAbiertos = tickets.filter(t =>
                String(t.estado || '').toLowerCase() !== 'cerrado'
            ).length;
            setText('preview-tickets-count', ticketsAbiertos);

            // Tareas pendientes (todo lo que no está Completada)
            const tareasPendientes = tareas.filter(t =>
                String(t.estado || '').toLowerCase() !== 'completada'
            ).length;
            setText('preview-tareas-count', tareasPendientes);

            // OLTs: con NAPs vs sin NAPs
            setText('preview-olts-count', olts.length);
            const oltsConNaps = olts.filter(o => Number(o.total_naps || 0) > 0).length;
            setHtml('preview-olts-breakdown',
                `<div><span>Con NAPs</span> <strong>${oltsConNaps}</strong></div>` +
                `<div><span>Sin NAPs</span> <strong>${olts.length - oltsConNaps}</strong></div>`
            );

            // NAPs: clientes / ocupación
            setText('preview-naps-count', naps.length);
            const totalClientesNap = naps.reduce((acc, n) => acc + Number(n.total_clientes || 0), 0);
            const totalPuertos = naps.reduce((acc, n) => acc + Number(n.cantidad_puertos_max || 0), 0);
            const ocupacion = totalPuertos > 0 ? Math.round((totalClientesNap / totalPuertos) * 100) : 0;
            setHtml('preview-naps-breakdown',
                `<div><span>Clientes</span> <strong>${totalClientesNap}</strong></div>` +
                `<div><span>Ocupación</span> <strong>${ocupacion}%</strong></div>`
            );

            // Facturas vencidas
            const facturasVencidas = facturas.filter(f =>
                String(f.estado || '').toLowerCase() === 'vencida'
            ).length;
            setText('preview-facturas-vencidas-count', facturasVencidas);
            const pill = document.getElementById('preview-facturas-vencidas-pill');
            if (pill) {
                pill.className = facturasVencidas > 0 ? 'stat-pill negative' : 'stat-pill positive';
                pill.textContent = facturasVencidas > 0 ? 'Requiere seguimiento' : 'Sin incidencias';
            }

            // Pagos pendientes
            const pagosPendientes = pagos.filter(p =>
                String(p.estado || '').toLowerCase() === 'pendiente'
            ).length;
            setText('preview-pagos-pendientes-count', pagosPendientes);
        },

        /**
         * Pinta los 6 gráficos.
         *
         * Regla Chart.js: reutilizar la instancia y llamar .update().
         * Solo se crea una instancia nueva si no existe o si el canvas
         * cambió de tipo. Origen: F07 de AUDITORIA_2026 y API de Chart.js.
         */
        renderCharts: function () {
            if (typeof Chart === 'undefined') return;

            window.dashboardCharts = window.dashboardCharts || {};

            const servicios = window.ServicioModel ? window.ServicioModel.cache : [];
            const facturas = window.FacturaModel ? window.FacturaModel.cache : [];
            const pagos = window.PagoModel ? window.PagoModel.cache : [];
            const olts = window.OltModel ? window.OltModel.cache : [];
            const naps = window.NapModel ? window.NapModel.cache : [];
            const tickets = window.TicketModel ? window.TicketModel.cache : [];

            const rgb = accentRgb();
            const accent = accentColor();

            // 1. Ingresos por periodo (últimos 6)
            const periodos = Array.from(new Set([
                ...facturas.map(f => f.periodo),
                ...pagos.map(p => p.factura_periodo)
            ].filter(Boolean))).sort().slice(-6);

            const ingresos = periodos.map(per =>
                pagos
                    .filter(p => String(p.estado || '').toLowerCase() === 'validado' && p.factura_periodo === per)
                    .reduce((sum, p) => sum + Number(p.monto || 0), 0)
            );

            this._upsertChart('dashboard-income-chart', 'line', periodos, [{
                label: 'Ingresos',
                data: ingresos,
                borderColor: accent,
                backgroundColor: `rgba(${rgb}, 0.18)`,
                fill: true,
                tension: 0.3
            }]);

            // 2. Estado de facturación (doughnut)
            const facturaEstados = facturas.reduce((acc, f) => {
                const e = String(f.estado || 'pendiente').toLowerCase();
                acc[e] = (acc[e] || 0) + 1;
                return acc;
            }, {});
            this._upsertChart('dashboard-billing-chart', 'doughnut',
                Object.keys(facturaEstados),
                [{
                    data: Object.values(facturaEstados),
                    backgroundColor: Object.keys(facturaEstados).map(k =>
                        k === 'pagada' ? 'var(--success)' :
                        k === 'vencida' ? 'var(--danger)' :
                        'var(--warning)'
                    )
                }]
            );

            // 3. Estado de servicios (doughnut)
            const servicioEstados = servicios.reduce((acc, s) => {
                const e = String(s.estado_comercial || 'pendiente').toLowerCase();
                acc[e] = (acc[e] || 0) + 1;
                return acc;
            }, {});
            this._upsertChart('dashboard-services-chart', 'doughnut',
                Object.keys(servicioEstados),
                [{
                    data: Object.values(servicioEstados),
                    backgroundColor: Object.keys(servicioEstados).map(k =>
                        k === 'activo' ? 'var(--success)' :
                        k === 'retirado' ? 'var(--danger)' :
                        'var(--warning)'
                    )
                }]
            );

            // 4. NAPs por OLT (bar)
            const oltLabels = olts.slice(0, 8).map(o => o.codigo || `OLT #${o.id}`);
            const oltData = olts.slice(0, 8).map(o => Number(o.total_naps || 0));
            this._upsertChart('dashboard-olts-chart', 'bar', oltLabels, [{
                label: 'NAPs',
                data: oltData,
                backgroundColor: accent
            }]);

            // 5. Ocupación de NAPs (bar: clientes vs puertos)
            const napLabels = naps.slice(0, 8).map(n => n.codigo || `NAP #${n.id}`);
            const napClientes = naps.slice(0, 8).map(n => Number(n.total_clientes || 0));
            const napPuertos = naps.slice(0, 8).map(n => Number(n.cantidad_puertos_max || 0));
            this._upsertChart('dashboard-naps-chart', 'bar', napLabels, [
                { label: 'Clientes', data: napClientes, backgroundColor: accent },
                { label: 'Puertos', data: napPuertos, backgroundColor: 'var(--warning)' }
            ]);

            // 6. Tickets por prioridad y estado (bar)
            const ticketKeys = tickets.reduce((acc, t) => {
                const k = `${t.prioridad || 'Media'}:${t.estado || 'Abierto'}`;
                acc[k] = (acc[k] || 0) + 1;
                return acc;
            }, {});
            this._upsertChart('dashboard-tickets-chart', 'bar',
                Object.keys(ticketKeys),
                [{
                    label: 'Tickets',
                    data: Object.values(ticketKeys),
                    backgroundColor: Object.keys(ticketKeys).map(k =>
                        k.startsWith('Alta') ? 'var(--danger)' :
                        k.startsWith('Media') ? 'var(--warning)' :
                        'var(--success)'
                    )
                }]
            );
        },

        /**
         * Crea o actualiza un chart.
         *
         * - Si no existe instancia: new Chart().
         * - Si existe y el tipo coincide: reemplaza data y llama .update().
         * - Si existe y el tipo cambió: destroy() + new Chart().
         * - Si los datos están vacíos: destroy() y muestra "Sin datos".
         *
         * @param {string} canvasId
         * @param {string} type
         * @param {Array} labels
         * @param {Array} datasets
         */
        _upsertChart: function (canvasId, type, labels, datasets) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            const existing = window.dashboardCharts[canvasId];

            const hasData = labels.length > 0 && datasets.some(d =>
                Array.isArray(d.data) && d.data.some(v => Number(v) !== 0)
            );
            if (!hasData) {
                if (existing) {
                    existing.destroy();
                    delete window.dashboardCharts[canvasId];
                }
                this._renderEmptyHint(canvas);
                return;
            }

            this._clearEmptyHint(canvas);

            if (existing && existing.config.type === type) {
                existing.data.labels = labels;
                existing.data.datasets = datasets;
                existing.update();
                return;
            }

            if (existing) {
                existing.destroy();
                delete window.dashboardCharts[canvasId];
            }

            window.dashboardCharts[canvasId] = new Chart(canvas.getContext('2d'), {
                type: type,
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: type === 'doughnut' }
                    }
                }
            });
        },

        _renderEmptyHint: function (canvas) {
            const parent = canvas.parentElement;
            if (!parent) return;
            if (parent.querySelector('.dashboard-chart-empty')) return;
            const p = document.createElement('p');
            p.className = 'stat-label dashboard-chart-empty';
            p.style.marginTop = '8px';
            p.textContent = 'Sin datos suficientes';
            parent.appendChild(p);
        },

        _clearEmptyHint: function (canvas) {
            const parent = canvas.parentElement;
            if (!parent) return;
            parent.querySelectorAll('.dashboard-chart-empty').forEach(el => el.remove());
        },

        /**
         * Destruye todas las instancias. Se llama en logout
         * (Session.onClear) para evitar memory leaks y que el próximo
         * login reutilice instancias con datos del usuario anterior.
         */
        destroyCharts: function () {
            Object.keys(window.dashboardCharts || {}).forEach(id => {
                const chart = window.dashboardCharts[id];
                if (chart && typeof chart.destroy === 'function') chart.destroy();
            });
            window.dashboardCharts = {};
        },

        /**
         * Listeners de los botones "Ver X" del header de cada KPI.
         * Los botones usan data-action="goto" + data-page="<pageId>".
         * Sin onclick inline en #dash.
         */
        _attachListeners: function () {
            document.querySelectorAll('#dash [data-action="goto"]').forEach(btn => {
                btn.removeAttribute('onclick');
                btn.addEventListener('click', () => {
                    const page = btn.dataset.page;
                    if (page && window.Router) window.Router.showPage(page);
                });
            });
        }
    };
})();