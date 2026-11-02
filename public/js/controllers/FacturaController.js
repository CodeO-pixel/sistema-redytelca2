// js/controllers/FacturaController.js
(function () {
    'use strict';

    window.FacturaController = {
        _searchTimer: null,
        _dirty: false,
        _montoTouched: false,

        init: function () {
            if (window.FacturaView) window.FacturaView._attachListeners();
            // Restaurar búsqueda y página de la sesión
            try {
                const s = sessionStorage.getItem('redytelca.facturas.search');
                const p = sessionStorage.getItem('redytelca.facturas.page');
                const input = document.getElementById('facturas-search');
                if (input && s) input.value = s;
                if (p) window.__facturaCurrentPage = parseInt(p, 10) || 1;
            } catch (e) { /* no-op */ }
        },

        load: async function (page = null) {
            // Reset síncrono ANTES de await (patrón BUG-B3.4-C5)
            if (window.selectedIds) window.selectedIds['facturas'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="facturas"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });

            try {
                if (window.ServicioModel && window.ServicioModel.cache.length === 0) {
                    await window.ServicioModel.load();
                }
                await window.FacturaModel.loadVencer();
                await window.FacturaModel.load();
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar facturas');
                return;
            }
            const target = page != null ? page : (window.__facturaCurrentPage || 1);
            this.renderPage(target);
        },

        renderPage: function (page) {
            const search = this._readSearch();
            const filtered = this._filter(window.FacturaModel.cache, search);
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;
            window.__facturaCurrentPage = safePage;
            this._persist(search, safePage);
            window.FacturaView.renderTable(filtered.slice(start, start + size), search);
            window.FacturaView.renderPagination(safePage, totalPages);
            window.FacturaView.updateCounter(filtered.length, safePage, size);
        },

        save: async function () {
            const id = document.getElementById('factura-id').value;
            const wasEditing = !!id;
            const currentPage = window.__facturaCurrentPage || 1;

            const payload = {
                servicio_id: document.getElementById('factura-servicio').value,
                periodo: document.getElementById('factura-periodo').value.trim(),
                monto: document.getElementById('factura-monto').value,
                moneda: document.getElementById('factura-moneda').value,
                tasa_cambio: document.getElementById('factura-tasa-cambio').value || null,
                fecha_emision: document.getElementById('factura-fecha-emision').value,
                fecha_vencimiento: document.getElementById('factura-fecha-vencimiento').value,
                estado: document.getElementById('factura-estado').value
            };

            if (!payload.servicio_id) { window.Toast.warning('Selecciona un servicio'); return; }
            if (!/^\d{4}-\d{2}$/.test(payload.periodo)) { window.Toast.warning('El periodo debe ser AAAA-MM (ej. 2026-07)'); return; }
            if (!payload.monto || Number(payload.monto) <= 0) { window.Toast.warning('El monto debe ser mayor a 0'); return; }
            if (payload.moneda === 'VES' && (!payload.tasa_cambio || Number(payload.tasa_cambio) <= 0)) {
                window.Toast.warning('Ingresa la tasa BCV para moneda VES');
                return;
            }
            if (!payload.fecha_emision || !payload.fecha_vencimiento) {
                window.Toast.warning('Fechas de emisión y vencimiento son obligatorias');
                return;
            }

            const btn = document.querySelector('#factura-form [data-action="save-factura"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }

            try {
                const res = wasEditing
                    ? await window.FacturaModel.update(id, payload)
                    : await window.FacturaModel.create(payload);

                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    this._dirty = false;
                    window.FacturaView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.FacturaModel.cache;
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
            if (window.FacturaModel.cache.length === 0) await window.FacturaModel.load();
            if (window.ServicioModel && window.ServicioModel.cache.length === 0) await window.ServicioModel.load();
            const f = window.FacturaModel.getById(id);
            if (!f) return;
            window.FacturaView.toggleForm(false, false);
            window.FacturaView.fillForm(f);
        },

        confirmDelete: async function (id) {
            const currentPage = window.__facturaCurrentPage || 1;
            const ok = await window.Modal.confirm('¿Eliminar esta factura?', { danger: true });
            if (!ok) return;
            try {
                const res = await window.FacturaModel.delete(id);
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

        showLineas: async function (id) {
            const factura = window.FacturaModel.getById(id);
            if (!factura) { window.Toast.error('Factura no encontrada'); return; }
            try {
                const lineas = await window.FacturaModel.loadLineas(id);
                window.FacturaView.renderLineasModal(factura, lineas);
            } catch (e) {
                window.Toast.error(e.message || 'Error al cargar líneas');
            }
        },

        // Autocompletar #factura-monto con plan_precio al cambiar servicio,
        // solo si el usuario no lo tocó manualmente.
        _onServicioChange: function () {
            if (this._montoTouched) return;
            const sel = document.getElementById('factura-servicio');
            const id = sel ? sel.value : '';
            if (!id) return;
            const s = window.ServicioModel
                ? window.ServicioModel.cache.find(x => String(x.id) === String(id))
                : null;
            if (!s || s.plan_precio == null) return;
            const monto = document.getElementById('factura-monto');
            if (monto) {
                monto.value = Number(s.plan_precio).toFixed(2);
                window.FacturaView.updateEquivalenteBs();
            }
        },

        _onSearchCleared: function () {
            clearTimeout(this._searchTimer);
            this.renderPage(1);
        },

        _readSearch: function () {
            const input = document.getElementById('facturas-search');
            return input ? input.value : '';
        },

        _persist: function (search, page) {
            try {
                sessionStorage.setItem('redytelca.facturas.search', search);
                sessionStorage.setItem('redytelca.facturas.page', String(page));
            } catch (e) { /* no-op */ }
        },

        _filter: function (items, search) {
            const s = window.normalizeForSearch(search || '');
            if (!s) return items;
            return items.filter(f => window.normalizeForSearch(
                (f.nombres || '') + ' ' + (f.apellidos || '') + ' ' + (f.cedula || '') + ' ' +
                (f.periodo || '') + ' ' + (f.servicio_alias || '') + ' ' + (f.estado || '') + ' ' + (f.moneda || '')
            ).includes(s));
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.FacturaController.init());
})();