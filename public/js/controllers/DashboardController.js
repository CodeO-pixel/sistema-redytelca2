// js/controllers/DashboardController.js
(function () {
    'use strict';

    window.DashboardController = {
        _loading: false,

        init: function () {
            if (window.DashboardView) window.DashboardView._attachListeners();
        },

        /**
         * Carga las caches que el Dashboard necesita.
         *
         * - ClienteModel: paginado. Se carga SIEMPRE fresco, y se guarda
         *   res.total en window.__dashboardTotals.clientes para que el
         *   View pinte el conteo real, no el de la página (10).
         * - Resto de Models: no paginados. Se cargan solo si están vacíos
         *   (patrón E7 — no re-fetchear si ya están en cache).
         *
         * Se hace en paralelo con Promise.all. Cada task va envuelto en
         * try/catch para que un fallo (ej. endpoint caído) no tumbe el
         * resto del dashboard.
         */
        load: async function () {
            if (this._loading) return;
            this._loading = true;

            window.__dashboardTotals = window.__dashboardTotals || { clientes: 0 };

            const loadIfEmpty = async (Model) => {
                if (!Model) return;
                if (Array.isArray(Model.cache) && Model.cache.length > 0) return;
                try { await Model.load(); } catch (e) { /* no-op */ }
            };

            // Clientes: siempre fresco para obtener el total real.
            const tareaClientes = (async () => {
                if (!window.ClienteModel) return;
                try {
                    const res = await window.ClienteModel.load('', 1);
                    const total = Number(res && res.total);
                    window.__dashboardTotals.clientes = Number.isFinite(total)
                        ? total
                        : (window.ClienteModel.cache ? window.ClienteModel.cache.length : 0);
                } catch (e) {
                    window.__dashboardTotals.clientes =
                        (window.ClienteModel.cache ? window.ClienteModel.cache.length : 0);
                }
            })();

            await Promise.all([
                tareaClientes,
                loadIfEmpty(window.ServicioModel),
                loadIfEmpty(window.ContratoModel),
                loadIfEmpty(window.TicketModel),
                loadIfEmpty(window.TareaModel),
                loadIfEmpty(window.OltModel),
                loadIfEmpty(window.NapModel),
                loadIfEmpty(window.FacturaModel),
                loadIfEmpty(window.PagoModel)
            ]);

            this._loading = false;
            this.renderPage();
        },

        /**
         * Pinta el dashboard: fecha + KPIs + charts.
         * El View lee los datos de las caches y de window.__dashboardTotals.
         */
        renderPage: function () {
            if (window.DashboardView) {
                window.DashboardView.renderDate();
                window.DashboardView.renderKPIs();
                window.DashboardView.renderCharts();
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.DashboardController.init());
})();