// js/controllers/PagoController.js
(function () {
    'use strict';

    function toMysqlDatetime(val) {
        if (!val) return '';
        const norm = String(val).replace('T', ' ');
        return norm.length === 16 ? norm + ':00' : norm;
    }

    window.PagoController = {
        _searchTimer: null,
        _dirty: false,

        init: function () {
            if (window.PagoView) window.PagoView._attachListeners();
            try {
                const s = sessionStorage.getItem('redytelca.pagos.search');
                const p = sessionStorage.getItem('redytelca.pagos.page');
                const input = document.getElementById('pagos-search');
                if (input && s) input.value = s;
                if (p) window.__pagoCurrentPage = parseInt(p, 10) || 1;
            } catch (e) { /* no-op */ }
        },

        load: async function (page = null) {
            if (window.selectedIds) window.selectedIds['pagos'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="pagos"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete' || action === 'validar' || action === 'rechazar') {
                    btn.disabled = true;
                }
            });

            try {
                if (window.ServicioModel && window.ServicioModel.cache.length === 0) {
                    await window.ServicioModel.load();
                }
                if (window.FacturaModel && window.FacturaModel.cache.length === 0) {
                    await window.FacturaModel.load();
                }
                if (window.MetodoPagoModel && window.MetodoPagoModel.cache.length === 0) {
                    await window.MetodoPagoModel.load();
                }
                await window.PagoModel.load();
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar pagos');
                return;
            }
            const target = page != null ? page : (window.__pagoCurrentPage || 1);
            this.renderPage(target);
        },

        renderPage: function (page) {
            const search = this._readSearch();
            const filtered = this._filter(window.PagoModel.cache, search);
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;
            window.__pagoCurrentPage = safePage;
            this._persist(search, safePage);
            window.PagoView.renderTable(filtered.slice(start, start + size), search);
            window.PagoView.renderPagination(safePage, totalPages);
            window.PagoView.updateCounter(filtered.length, safePage, size);
        },

        save: async function () {
            const id = document.getElementById('pago-id').value;
            const wasEditing = !!id;
            const currentPage = window.__pagoCurrentPage || 1;

            const srv = document.getElementById('pago-servicio');
            const srvOpt = srv ? srv.options[srv.selectedIndex] : null;
            const clienteId = srvOpt ? srvOpt.getAttribute('data-cliente') : '';

            const payload = {
                cliente_id: clienteId,
                servicio_id: srv ? srv.value : '',
                factura_id: document.getElementById('pago-factura').value || '',
                monto: document.getElementById('pago-monto').value,
                moneda_pago: document.getElementById('pago-moneda').value || 'USD',
                tasa_cambio: document.getElementById('pago-tasa-cambio').value || '',
                fecha_pago: toMysqlDatetime(document.getElementById('pago-fecha').value),
                metodo_id: document.getElementById('pago-metodo').value || '',
                referencia_bancaria: document.getElementById('pago-referencia').value.trim(),
                origen: 'backoffice'
            };

            if (!payload.cliente_id || !payload.servicio_id) { window.Toast.warning('Selecciona un servicio'); return; }
            if (!payload.monto || Number(payload.monto) <= 0) { window.Toast.warning('El monto debe ser mayor a 0'); return; }
            if (!payload.fecha_pago) { window.Toast.warning('La fecha de pago es obligatoria'); return; }
            if (payload.moneda_pago === 'VES' && (!payload.tasa_cambio || Number(payload.tasa_cambio) <= 0)) {
                window.Toast.warning('Ingresa la tasa BCV para moneda VES');
                return;
            }

            if (!payload.factura_id) {
                const ok = await window.Modal.confirm(
                    'Este pago no se aplicará a ninguna factura. El excedente no se reintegrará automáticamente (F2.3). ¿Deseas continuar?',
                    { title: 'Pago a cuenta', confirmText: 'Continuar', danger: false }
                );
                if (!ok) return;
            }

            const btn = document.querySelector('#pago-form [data-action="save-pago"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }

            try {
                const res = wasEditing
                    ? await window.PagoModel.update(id, payload)
                    : await window.PagoModel.create(payload);

                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    this._dirty = false;
                    window.PagoView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.PagoModel.cache;
                        const size = window.pageSize || 8;
                        this.renderPage(Math.max(1, Math.ceil(cache.length / size)));
                    }
                } else {
                    window.Toast.error(res.message);
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al guardar');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        edit: async function (id) {
            if (window.PagoModel.cache.length === 0) await window.PagoModel.load();
            if (window.ServicioModel && window.ServicioModel.cache.length === 0) await window.ServicioModel.load();
            if (window.FacturaModel && window.FacturaModel.cache.length === 0) await window.FacturaModel.load();
            if (window.MetodoPagoModel && window.MetodoPagoModel.cache.length === 0) await window.MetodoPagoModel.load();
            const p = window.PagoModel.getById(id);
            if (!p) return;
            window.PagoView.toggleForm(false, false);
            window.PagoView.fillForm(p);
        },

        confirmDelete: async function (id) {
            const p = window.PagoModel.getById(id);
            if (!p) return;
            const currentPage = window.__pagoCurrentPage || 1;
            const ok = await window.Modal.confirm('¿Eliminar este pago?', { danger: true });
            if (!ok) return;
            try {
                const res = await window.PagoModel.delete(id);
                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    await this.load(currentPage);
                } else {
                    window.Toast.error(res.message);
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al eliminar');
            }
        },

        updateEstado: async function (id, estado) {
            const p = window.PagoModel.getById(id);
            if (!p) return;
            if (!window.Session || !window.Session.isAdmin()) {
                window.Toast.error('Solo un Administrador puede validar o rechazar pagos');
                return;
            }
            const verbo = estado === 'validado' ? 'validar' : 'rechazar';
            const ok = await window.Modal.confirm(`¿Deseas ${verbo} este pago?`, { danger: estado === 'rechazado' });
            if (!ok) return;
            try {
                const res = await window.PagoModel.updateEstado(id, estado);
                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    await this.load(window.__pagoCurrentPage || 1);
                } else {
                    window.Toast.error(res.message);
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al actualizar el estado');
            }
        },

        _onServicioChange: function () {
            const srv = document.getElementById('pago-servicio');
            const id = srv ? srv.value : '';
            if (!id) {
                const fac = document.getElementById('pago-factura');
                if (fac) fac.innerHTML = '<option value="">Sin factura asociada (a cuenta)</option>';
                return;
            }
            window.PagoView.populateFacturas(id, null);
        },

        _onSearchCleared: function () {
            clearTimeout(this._searchTimer);
            this.renderPage(1);
        },

        _readSearch: function () {
            const input = document.getElementById('pagos-search');
            return input ? input.value : '';
        },

        _persist: function (search, page) {
            try {
                sessionStorage.setItem('redytelca.pagos.search', search);
                sessionStorage.setItem('redytelca.pagos.page', String(page));
            } catch (e) { /* no-op */ }
        },

        _filter: function (items, search) {
            const s = window.normalizeForSearch(search || '');
            if (!s) return items;
            return items.filter(p => window.normalizeForSearch(
                (p.nombres || '') + ' ' + (p.apellidos || '') + ' ' + (p.cedula || '') + ' ' +
                (p.factura_periodo || '') + ' ' + (p.metodo_nombre || '') + ' ' + (p.estado || '')
            ).includes(s));
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.PagoController.init());
})();