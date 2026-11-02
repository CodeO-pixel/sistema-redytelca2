// js/controllers/ReportesController.js
(function () {
    'use strict';

    window.ReportesController = {
        _loading: false,

        /**
         * Inicialización. Corre UNA vez en DOMContentLoaded.
         *
         * - Ata listeners del bloque #reportes.
         * - Restaura el rango de sessionStorage si existe (U10: persistir
         *   búsqueda y página). Si no hay rango guardado, los inputs
         *   quedan vacíos y el primer load() pedirá TODOS los registros
         *   (sin filtro). Es el default de Splynx Reports: al abrir, el
         *   operador ve todo, y filtra si quiere.
         *
         * NO llama a load(). Eso lo hace `Router.register('reportes', {
         * onShow: () => ReportesController.load() })` cuando el operador
         * navega al módulo.
         */
        init: function () {
            if (window.ReportesView) window.ReportesView._attachListeners();
            this._restoreRange();
        },

        /**
         * Carga el reporte para el rango actual de los inputs.
         *
         * Flujo:
         *   1. Guard _loading: evita doble submit por Enter ×2 o click ×2.
         *   2. Lee `desde`/`hasta` de los inputs.
         *   3. Validación cliente: si ambos están llenos y desde > hasta,
         *      Toast.warning y return (sin llamar al backend).
         *   4. Deshabilita el botón "Aplicar" y muestra "Buscando…".
         *   5. Llama a ReporteModel.load(desde || null, hasta || null).
         *   6. Persiste el rango según `res.rango.con_filtro`:
         *      - con_filtro = true → guarda desde/hasta (los que puso el
         *        operador, ya normalizados por el backend si faltaba uno).
         *      - con_filtro = false → limpia la persistencia.
         *   7. renderPage().
         *   8. finally: restaura el botón y el flag _loading.
         *
         * IMPORTANTE: ya NO auto-rellena inputs con `res.rango` cuando
         * con_filtro=false. Antes se hacía, pero ahora el default es "sin
         * filtro": los inputs deben quedar vacíos para que el operador
         * entienda que está viendo todos los registros. Cuando
         * con_filtro=true (porque el operador puso fechas o usó "Mes
         * actual"), los inputs YA tienen los valores; si el backend
         * completó un extremo (ej. el operador puso solo "desde" y el
         * backend completó "hasta" con fin de mes), se refleja ese
         * completado en el input correspondiente.
         */
        load: async function () {
            if (this._loading) return;

            const desde = this._readInput('reportes-desde');
            const hasta = this._readInput('reportes-hasta');

            if (desde && hasta && desde > hasta) {
                window.Toast.warning('La fecha "desde" no puede ser posterior a "hasta".');
                return;
            }

            this._loading = true;

            const btn = document.querySelector('#reportes [data-action="apply-range"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Buscando…'; }

            try {
                const res = await window.ReporteModel.load(desde || null, hasta || null);

                if (res.status !== 'success') {
                    window.Toast.error(res.message || 'Error al cargar el reporte.');
                    return;
                }

                // Reflejar en los inputs el rango que el backend usó, pero
                // solo si efectivamente hubo filtro. Si con_filtro=false,
                // los inputs deben quedar vacíos ("Todos").
                if (res.rango && res.rango.con_filtro) {
                    const elD = document.getElementById('reportes-desde');
                    const elH = document.getElementById('reportes-hasta');
                    if (elD) elD.value = res.rango.desde || '';
                    if (elH) elH.value = res.rango.hasta || '';
                    this._persistRange(res.rango.desde, res.rango.hasta);
                } else {
                    this._persistRange('', '');
                }

                this.renderPage();
            } catch (e) {
                window.Toast.error(e.message || 'Error al cargar el reporte.');
            } finally {
                this._loading = false;
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        /**
         * "Mes actual": rellena inputs con el mes en curso y carga.
         * Atajo del operador: no tiene que tipear fechas, solo pulsar.
         */
        resetToCurrentMonth: function () {
            const hoy = new Date();
            const y = hoy.getFullYear();
            const m = String(hoy.getMonth() + 1).padStart(2, '0');
            const lastDay = new Date(y, hoy.getMonth() + 1, 0).getDate();
            const desde = `${y}-${m}-01`;
            const hasta = `${y}-${m}-${String(lastDay).padStart(2, '0')}`;

            const elD = document.getElementById('reportes-desde');
            const elH = document.getElementById('reportes-hasta');
            if (elD) elD.value = desde;
            if (elH) elH.value = hasta;

            this.load();
        },

        /**
         * "Ver todo": limpia inputs y carga sin filtro.
         * Muestra TODOS los registros (facturas no anuladas, todos los
         * pagos, todos los tickets, todas las tareas). Es el default al
         * abrir el módulo, pero el operador puede volver a este estado
         * desde cualquier filtro con este botón.
         */
        showAll: function () {
            const elD = document.getElementById('reportes-desde');
            const elH = document.getElementById('reportes-hasta');
            if (elD) elD.value = '';
            if (elH) elH.value = '';
            this.load();
        },

        renderPage: function () {
            if (window.ReportesView) window.ReportesView.render();
        },

        /**
         * Lee un input por ID. Devuelve '' si no existe o está vacío.
         * @param {string} id
         * @returns {string}
         */
        _readInput: function (id) {
            const el = document.getElementById(id);
            return el ? String(el.value || '').trim() : '';
        },

        /**
         * Persiste el rango en sessionStorage, por módulo. Clave
         * `redytelca.reportes.desde` / `redytelca.reportes.hasta`.
         *
         * Si el rango es sin filtro (ambos ''), se guardan strings vacíos.
         * Así el próximo init() los lee vacíos y no rellena inputs.
         *
         * U10 de la Capa 2: al volver al módulo, la misma vista.
         */
        _persistRange: function (desde, hasta) {
            try {
                sessionStorage.setItem('redytelca.reportes.desde', desde || '');
                sessionStorage.setItem('redytelca.reportes.hasta', hasta || '');
            } catch (e) { /* no-op */ }
        },

        /**
         * Restaura el rango guardado. Si no hay (o es vacío), deja los
         * inputs vacíos. El primer load() pedirá todos los registros.
         */
        _restoreRange: function () {
            try {
                const desde = sessionStorage.getItem('redytelca.reportes.desde');
                const hasta = sessionStorage.getItem('redytelca.reportes.hasta');
                const elD = document.getElementById('reportes-desde');
                const elH = document.getElementById('reportes-hasta');
                if (elD && desde) elD.value = desde;
                if (elH && hasta) elH.value = hasta;
            } catch (e) { /* no-op */ }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.ReportesController.init());
})();