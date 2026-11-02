// js/portal/controllers/PagoController.js
// Controller del portal para "Mis pagos" (096c).
// Orquesta: PortalPagoModel (datos) + PortalPagoView (render) +
// PortalMetodoPagoModel (catálogo) + PortalFacturaModel (dropdown facturas)
// + PortalServicioModel (dropdown servicios).
//
// FIXES 2026-11-02 (auditoría del portal):
//   - A3: `load(page = null)` con default `null` en vez de `undefined`.
//     El caller (`main.js`) ahora llama `load()` sin argumento, y este
//     controller usa `window.__portalPagoCurrentPage` restaurado de
//     sessionStorage. Antes `main.js` llamaba `load(1)` y forzaba
//     página 1 en cada navegación (la paginación server-side se
//     reseteaba). Consistente con el patrón del admin
//     (`ContratoController.load = function (page = null)`).
//   - A2: `showDetail` ahora es async y pide `PortalPagoModel.loadDetail(id)`
//     si el pago no está en cache. Sin esto, las notificaciones del
//     tipo `/portal/pagos?id=N` que apuntan a un pago fuera de la
//     página 1 devolvían "Pago no encontrado". Replica el patrón de
//     `FacturaController.showDetail` y `TicketController.showDetail`.
(function () {
    'use strict';

    window.PortalPagoController = {
        _loading: false,
        _saving: false,

        init: function () {
            if (window.PortalPagoView) window.PortalPagoView._attachListeners();
            this._restore();
        },

        load: async function (page = null) {
            if (this._loading) return;
            this._loading = true;

            const target = page != null ? page : (window.__portalPagoCurrentPage || 1);
            const filtros = this._readFilters();

            try {
                const res = await window.PortalPagoModel.load(target, 20, filtros);
                if (res.status !== 'success') {
                    if (window.Toast) window.Toast.error(res.message || 'Error al cargar tus pagos');
                    return;
                }

                const pagos = res.pagos || [];
                const safePage = res.page || 1;
                const totalPages = res.pages || 1;
                const total = res.total || 0;

                window.__portalPagoCurrentPage = safePage;
                this._persist(filtros, safePage);

                window.PortalPagoView.renderTable(pagos);
                window.PortalPagoView.renderPagination(safePage, totalPages);
                window.PortalPagoView.updateCounter(total, safePage, 20);
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar tus pagos');
            } finally {
                this._loading = false;
            }
        },

        clearFilters: function () {
            const el = document.getElementById('portal-pagos-estado');
            if (el) el.value = '';
            this.load(1);
        },

        /**
         * A2: abre el modal de detalle. Si el pago está en cache,
         * muestra de inmediato. En paralelo (o después), pide el
         * detalle al backend para enriquecerlo si el backend
         * devuelve campos adicionales. Si no está en cache y el
         * backend responde 404, muestra "Pago no encontrado".
         *
         * @param {number|string} id
         */
        showDetail: async function (id) {
            if (!id) return;

            const cached = window.PortalPagoModel.getById(id);
            if (cached) {
                window.PortalPagoView.renderDetailModal(cached);
            }

            try {
                const detalle = await window.PortalPagoModel.loadDetail(id);
                if (detalle) {
                    window.PortalPagoView.renderDetailModal(detalle);
                } else if (!cached) {
                    if (window.Toast) window.Toast.error('Pago no encontrado');
                }
            } catch (e) {
                if (!cached && window.Toast) {
                    window.Toast.error(e.message || 'Error al cargar el detalle');
                }
            }
        },

        downloadComprobante: async function (id) {
            try {
                await window.PortalPagoModel.downloadComprobante(id);
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'No se pudo descargar el comprobante');
            }
        },

        save: async function () {
            if (this._saving) return;

            const fb = document.getElementById('portal-pago-feedback');
            if (fb) fb.textContent = '';

            const srvEl = document.getElementById('portal-pago-servicio');
            const facEl = document.getElementById('portal-pago-factura');
            const montoEl = document.getElementById('portal-pago-monto');
            const monEl = document.getElementById('portal-pago-moneda');
            const tasaEl = document.getElementById('portal-pago-tasa');
            const fechaEl = document.getElementById('portal-pago-fecha');
            const metEl = document.getElementById('portal-pago-metodo');
            const refEl = document.getElementById('portal-pago-referencia');
            const fileEl = document.getElementById('portal-pago-comprobante');

            const servicioId = srvEl ? srvEl.value : '';
            const facturaId = facEl ? facEl.value : '';
            const monto = montoEl ? montoEl.value : '';
            const moneda = monEl ? monEl.value : 'USD';
            const tasa = tasaEl ? tasaEl.value : '';
            const fechaLocal = fechaEl ? fechaEl.value : '';
            const metodoId = metEl ? metEl.value : '';
            const referencia = refEl ? refEl.value.trim() : '';
            const file = fileEl && fileEl.files ? fileEl.files[0] : null;

            const fail = (msg) => {
                if (fb) fb.textContent = msg;
                if (window.Toast) window.Toast.warning(msg);
            };

            if (!servicioId) return fail('Selecciona un servicio.');
            if (!metodoId) return fail('Selecciona un método de pago.');
            const montoNum = Number(monto);
            if (!montoNum || montoNum <= 0) return fail('El monto debe ser mayor a 0.');
            if (moneda === 'VES') {
                const tasaNum = Number(tasa);
                if (!tasaNum || tasaNum <= 0) return fail('Ingresa la tasa BCV para moneda VES.');
            }
            if (!fechaLocal) return fail('La fecha del pago es obligatoria.');
            if (!file) return fail('El comprobante es obligatorio.');

            const metodo = window.PortalMetodoPagoModel.getById(metodoId);
            if (metodo && metodo.requiere_referencia && !referencia) {
                return fail('La referencia bancaria es obligatoria para este método.');
            }

            if (!facturaId) {
                const ok = await window.Modal.confirm(
                    'Este pago no se aplicará a ninguna factura. ¿Deseas continuar como pago a cuenta?',
                    { title: 'Pago a cuenta', confirmText: 'Continuar', danger: false }
                );
                if (!ok) return;
            }

            const fechaMysql = String(fechaLocal).replace('T', ' ');
            const fechaFinal = fechaMysql.length === 16 ? fechaMysql + ':00' : fechaMysql;

            const fd = new FormData();
            fd.append('servicio_id', servicioId);
            if (facturaId) fd.append('factura_id', facturaId);
            fd.append('monto', String(montoNum));
            fd.append('moneda_pago', moneda);
            if (moneda === 'VES') fd.append('tasa_cambio', tasa);
            fd.append('fecha_pago', fechaFinal);
            fd.append('metodo_id', metodoId);
            if (referencia) fd.append('referencia_bancaria', referencia);
            fd.append('comprobante', file);

            const btn = document.getElementById('portal-pago-submit');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Registrando…'; }
            this._saving = true;

            try {
                const res = await window.PortalPagoModel.create(fd);
                if (res.status === 'success') {
                    if (window.Toast) window.Toast.success(res.message || 'Pago registrado');
                    window.PortalPagoView.toggleForm(true);
                    await this.load(1);
                } else {
                    const msg = res.message || 'Error al registrar el pago';
                    if (fb) fb.textContent = msg;
                    if (window.Toast) window.Toast.error(msg);
                }
            } catch (e) {
                const msg = (e && e.message) ? e.message : 'Error al registrar el pago';
                if (fb) fb.textContent = msg;
                if (window.Toast) window.Toast.error(msg);
            } finally {
                this._saving = false;
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        _readFilters: function () {
            const est = document.getElementById('portal-pagos-estado');
            return { estado: est ? est.value : '' };
        },

        _persist: function (filtros, page) {
            try {
                sessionStorage.setItem('redytelca.portal.pagos.estado', filtros.estado || '');
                sessionStorage.setItem('redytelca.portal.pagos.page', String(page || 1));
            } catch (e) { /* no-op */ }
        },

        _restore: function () {
            try {
                const estado = sessionStorage.getItem('redytelca.portal.pagos.estado');
                const page = sessionStorage.getItem('redytelca.portal.pagos.page');
                const elEstado = document.getElementById('portal-pagos-estado');
                if (elEstado && estado) elEstado.value = estado;
                if (page) window.__portalPagoCurrentPage = parseInt(page, 10) || 1;
            } catch (e) { /* no-op */ }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.PortalPagoController.init());
})();