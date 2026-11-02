// js/controllers/CategoriaTicketController.js
(function () {
    'use strict';

    window.CategoriaTicketController = {
        _searchTimer: null,
        _dirty: false,

        init: function () {
            if (window.CategoriaTicketView) window.CategoriaTicketView._attachListeners();
            try {
                const s = sessionStorage.getItem('redytelca.categorias-ticket.search');
                const p = sessionStorage.getItem('redytelca.categorias-ticket.page');
                const input = document.getElementById('categorias-ticket-search');
                if (input && s) input.value = s;
                if (p) window.__categoriaCurrentPage = parseInt(p, 10) || 1;
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

            if (window.selectedIds) window.selectedIds['categorias-ticket'] = null;
            document.querySelectorAll('.toolbar-btn[data-toolbar="categorias-ticket"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });
            document.querySelectorAll('#categorias-ticket-table tbody tr.selected-row').forEach(r => r.classList.remove('selected-row'));

            try {
                await window.CategoriaTicketModel.load();
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar categorías');
                return;
            }
            const target = page != null ? page : (window.__categoriaCurrentPage || 1);
            this.renderPage(target);
        },

        renderPage: function (page) {
            const search = this._readSearch();
            const filtered = this._filter(window.CategoriaTicketModel.cache, search);
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;

            window.__categoriaCurrentPage = safePage;
            this._persist(search, safePage);

            window.CategoriaTicketView.renderTable(filtered.slice(start, start + size), search);
            window.CategoriaTicketView.renderPagination(safePage, totalPages);
        },

        save: async function () {
            const id = document.getElementById('categoria-id').value;
            const wasEditing = !!id;
            const currentPage = window.__categoriaCurrentPage || 1;

            const payload = {
                nombre: document.getElementById('categoria-nombre').value.trim(),
                descripcion: document.getElementById('categoria-descripcion').value.trim() || null,
                color: document.getElementById('categoria-color').value,
                activa: document.getElementById('categoria-activa').value
            };

            if (!payload.nombre) { window.Toast.warning('El nombre es obligatorio.'); return; }
            if (!/^#[0-9a-fA-F]{6}$/.test(payload.color)) { window.Toast.warning('El color debe tener formato #RRGGBB.'); return; }

            const btn = document.querySelector('#categoria-modal [data-action="save-categoria"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }

            try {
                const res = wasEditing
                    ? await window.CategoriaTicketModel.update(id, payload)
                    : await window.CategoriaTicketModel.create(payload);

                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Categoría guardada');
                    this._dirty = false;
                    window.CategoriaTicketView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.CategoriaTicketModel.cache;
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
            if (window.CategoriaTicketModel.cache.length === 0) await window.CategoriaTicketModel.load();
            const c = window.CategoriaTicketModel.getById(id);
            if (!c) return;
            window.CategoriaTicketView.toggleForm(false, false);
            window.CategoriaTicketView.fillForm(c);
        },

        confirmDelete: async function (id) {
            const c = window.CategoriaTicketModel.getById(id);
            if (!c) return;
            const currentPage = window.__categoriaCurrentPage || 1;
            const ok = await window.Modal.confirm(
                `¿Eliminar la categoría "${c.nombre}"?`,
                { danger: true, confirmText: 'Eliminar' }
            );
            if (!ok) return;

            try {
                const res = await window.CategoriaTicketModel.delete(id);
                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Categoría eliminada');
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
            const input = document.getElementById('categorias-ticket-search');
            return input ? input.value : '';
        },

        _persist: function (search, page) {
            try {
                sessionStorage.setItem('redytelca.categorias-ticket.search', search);
                sessionStorage.setItem('redytelca.categorias-ticket.page', String(page));
            } catch (e) { /* no-op */ }
        },

        _filter: function (items, search) {
            const s = window.normalizeForSearch(search || '');
            if (!s) return items;
            return items.filter(c => window.normalizeForSearch(
                (c.nombre || '') + ' ' + (c.descripcion || '')
            ).includes(s));
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.CategoriaTicketController.init());
})();