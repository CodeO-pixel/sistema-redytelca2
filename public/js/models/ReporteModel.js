// js/models/ReporteModel.js
(function () {
    'use strict';

    window.ReporteModel = {
        /**
         * Cache del reporte completo. Es un objeto compuesto, no un array,
         * porque el endpoint devuelve 6 bloques (rango, kpis, 4 listados).
         * Se reemplaza entero en cada load() — no se mergea — para que el
         * operador vea siempre el reporte del rango actual, sin residuos
         * de un rango anterior.
         *
         * El bloque `rango` ahora incluye `con_filtro` (bool):
         *   - con_filtro = false → el reporte muestra TODOS los registros.
         *   - con_filtro = true  → el reporte muestra el subconjunto del
         *     rango [desde, hasta].
         * El View usa este flag para decidir si muestra "Del X al Y" o
         * "Todos los registros".
         */
        cache: {
            rango: { desde: null, hasta: null, con_filtro: false },
            kpis: null,
            facturas: [],
            pagos: [],
            tickets: [],
            tareas: []
        },

        /**
         * Carga el reporte para el rango indicado.
         *
         * Si desde y hasta vienen null, '' o undefined, no se envían como
         * query params. El backend devuelve TODOS los registros (sin
         * filtro) y `rango.con_filtro = false`.
         *
         * Si solo viene uno, el backend completa el otro con el mes de la
         * fecha provista y `rango.con_filtro = true`.
         *
         * @param {string|null} desde YYYY-MM-DD o null.
         * @param {string|null} hasta YYYY-MM-DD o null.
         * @returns {Promise<Object>} JSON crudo del endpoint.
         */
        load: async function (desde, hasta) {
            const params = {};
            if (desde) params.desde = desde;
            if (hasta) params.hasta = hasta;

            const res = await window.ApiClient.get('api_reportes.php', params);

            if (res.status === 'success') {
                this.cache = {
                    rango: res.rango || { desde: null, hasta: null, con_filtro: false },
                    kpis: res.kpis || null,
                    facturas: Array.isArray(res.facturas) ? res.facturas : [],
                    pagos: Array.isArray(res.pagos) ? res.pagos : [],
                    tickets: Array.isArray(res.tickets) ? res.tickets : [],
                    tareas: Array.isArray(res.tareas) ? res.tareas : []
                };
            }

            return res;
        },

        /**
         * Devuelve el cache actual. Helper para que el Controller/View no
         * acceda a `ReporteModel.cache.X` directamente (permite cambiar la
         * forma del cache sin tocar consumidores).
         *
         * @returns {Object}
         */
        get: function () {
            return this.cache;
        },

        /**
         * Resetea el cache al estado vacío. Lo llama `Session.onClear` al
         * logout (vía `main.js`). Necesario porque el cache es un objeto
         * compuesto y el `Session.onClear` genérico solo vacía `.cache = []`
         * en los Models con cache de array; este necesita resetear los 6
         * bloques del objeto.
         *
         * @returns {void}
         */
        reset: function () {
            this.cache = {
                rango: { desde: null, hasta: null, con_filtro: false },
                kpis: null,
                facturas: [],
                pagos: [],
                tickets: [],
                tareas: []
            };
        }
    };
})();