// js/controllers/SlaDefinicionController.js
(function () {
    'use strict';

    window.SlaDefinicionController = {
        _searchTimer: null,
        _dirty: false,

        init: function () {
            if (window.SlaDefinicionView) window.SlaDefinicionView._attachListeners();
            try {
                const s = sessionStorage.getItem('redytelca.sla-definiciones.search');
                const p = sessionStorage.getItem('redytelca.sla-definiciones.page');
                const input = document.getElementById('sla-definiciones-search');
                if (input && s) input.value = s;
                if (p) window.__slaCurrentPage = parseInt(p, 10) || 1;
            } catch (e) { /* no-op */ }
        },

        load: async function (page = null) {
            // HACK temporal (deuda B3.8-DOC-SIDEBAR): mantener "Tickets" activo
            // en el sidebar mientras estamos en una sub-página sin nav-item.
            // Se elimina cuando las páginas se agreguen al sidebar.
            const navTickets = document.querySelector('.nav-item[data-page="tickets"]');
            if (navTickets) {
                document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
                navTickets.classList.add('active');
            }

            if (window.selectedIds) window.selectedIds['sla-definiciones'] = null;
            document.querySelectorAll('.toolbar-btn[data-toolbar="sla-definiciones"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });
            document.querySelectorAll('#sla-definiciones-table tbody tr.selected-row').forEach(r => r.classList.remove('selected-row'));

            try {
                if (window.CategoriaTicketModel && window.CategoriaTicketModel.cache.length === 0) {
                    await window.CategoriaTicketModel.load();
                }
                await window.SlaDefinicionModel.load();
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar definiciones SLA');
                return;
            }
            const target = page != null ? page : (window.__slaCurrentPage || 1);
            this.renderPage(target);
        },

        renderPage: function (page) {
            const search = this._readSearch();
            const filtered = this._filter(window.SlaDefinicionModel.cache, search);
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;

            window.__slaCurrentPage = safePage;
            this._persist(search, safePage);

            window.SlaDefinicionView.renderTable(filtered.slice(start, start + size), search);
            window.SlaDefinicionView.renderPagination(safePage, totalPages);
        },

        save: async function () {
            const id = document.getElementById('sla-id').value;
            const wasEditing = !!id;
            const currentPage = window.__slaCurrentPage || 1;

            const payload = {
                categoria_id: document.getElementById('sla-categoria').value,
                prioridad: document.getElementById('sla-prioridad').value,
                horas_respuesta: document.getElementById('sla-horas-respuesta').value,
                horas_resolucion: document.getElementById('sla-horas-resolucion').value,
                descripcion: document.getElementById('sla-descripcion').value.trim() || null
            };

            if (!payload.categoria_id) { window.Toast.warning('Selecciona una categoría.'); return; }
            if (!payload.prioridad) { window.Toast.warning('Selecciona una prioridad.'); return; }

            const hr = Number(payload.horas_respuesta);
            const hres = Number(payload.horas_resolucion);
            if (!hr || hr < 1 || hr > 999) { window.Toast.warning('Horas de respuesta: entero entre 1 y 999.'); return; }
            if (!hres || hres < 1 || hres > 999) { window.Toast.warning('Horas de resolución: entero entre 1 y 999.'); return; }

            const btn = document.querySelector('#sla-modal [data-action="save-sla"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }

            try {
                const res = wasEditing
                    ? await window.SlaDefinicionModel.update(id, payload)
                    : await window.SlaDefinicionModel.create(payload);

                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Definición SLA guardada');
                    this._dirty = false;
                    window.SlaDefinicionView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.SlaDefinicionModel.cache;
                        const size = window.pageSize || 8;
                        this.renderPage(Math.max(1, Math.ceil(cache.length / size)));
                    }
                } else {
                    window.Toast.error(res.message || 'Error al guardar');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al guardar');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        edit: async function (id) {
            if (window.SlaDefinicionModel.cache.length === 0) await window.SlaDefinicionModel.load();
            if (window.CategoriaTicketModel && window.CategoriaTicketModel.cache.length === 0) {
                await window.CategoriaTicketModel.load();
            }
            const s = window.SlaDefinicionModel.getById(id);
            if (!s) return;
            window.SlaDefinicionView.toggleForm(false, false);
            window.SlaDefinicionView.fillForm(s);
        },

        confirmDelete: async function (id) {
            const s = window.SlaDefinicionModel.getById(id);
            if (!s) return;
            const currentPage = window.__slaCurrentPage || 1;
            const catNombre = s.categoria_nombre || 'categoría';
            const ok = await window.Modal.confirm(
                `¿Eliminar la definición SLA "${catNombre} — ${s.prioridad}"?`,
                { danger: true, confirmText: 'Eliminar' }
            );
            if (!ok) return;

            try {
                const res = await window.SlaDefinicionModel.delete(id);
                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Definición SLA eliminada');
                    await this.load(currentPage);
                } else {
                    window.Toast.error(res.message || 'Error al eliminar');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al eliminar');
            }
        },

        _onSearchCleared: function () {
            clearTimeout(this._searchTimer);
            this.renderPage(1);
        },

        _readSearch: function () {
            const input = document.getElementById('sla-definiciones-search');
            return input ? input.value : '';
        },

        _persist: function (search, page) {
            try {
                sessionStorage.setItem('redytelca.sla-definiciones.search', search);
                sessionStorage.setItem('redytelca.sla-definiciones.page', String(page));
            } catch (e) { /* no-op */ }
        },

        _filter: function (items, search) {
            const s = window.normalizeForSearch(search || '');
            if (!s) return items;
            return items.filter(sla => window.normalizeForSearch(
                (sla.categoria_nombre || '') + ' ' +
                (sla.prioridad || '') + ' ' +
                (sla.descripcion || '')
            ).includes(s));
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.SlaDefinicionController.init());
})();