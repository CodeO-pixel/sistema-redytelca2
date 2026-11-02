// js/controllers/RolController.js
(function () {
    'use strict';

    window.RolController = {
        _searchTimer: null,
        _dirty: false,

        init: function () {
            if (window.RolView) window.RolView._attachListeners();
            try {
                const s = sessionStorage.getItem('redytelca.roles.search');
                const p = sessionStorage.getItem('redytelca.roles.page');
                const input = document.getElementById('roles-search');
                if (input && s) input.value = s;
                if (p) window.__rolCurrentPage = parseInt(p, 10) || 1;
            } catch (e) { /* no-op */ }
        },

        load: async function (page = null) {
            // Reset síncrono ANTES del primer await (E1 + BUG-B3.4-C5)
            if (window.selectedIds) window.selectedIds['roles'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="roles"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });
            document.querySelectorAll('#roles-list tr.selected-row').forEach(r => r.classList.remove('selected-row'));

            try {
                await window.RolModel.load();
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar roles');
                return;
            }
            const target = page != null ? page : (window.__rolCurrentPage || 1);
            this.renderPage(target);
        },

        renderPage: function (page) {
            const search = this._readSearch();
            const filtered = this._filter(window.RolModel.cache, search);
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;

            window.__rolCurrentPage = safePage;
            this._persist(search, safePage);

            window.RolView.renderTable(filtered.slice(start, start + size), search);
            window.RolView.renderPagination(safePage, totalPages);
            window.RolView.updateCounter(filtered.length, safePage, size);
        },

        save: async function () {
            const id = document.getElementById('role-id').value;
            const wasEditing = !!id;
            const currentPage = window.__rolCurrentPage || 1;
            const nombre = document.getElementById('role-name').value.trim();

            if (!nombre) {
                window.Toast.warning('El nombre del rol es obligatorio.');
                return;
            }

            const saveBtn = document.querySelector('#role-form [data-action="save-role"]');
            if (saveBtn && saveBtn.disabled) return;
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.dataset.originalText = saveBtn.textContent;
                saveBtn.textContent = 'Guardando…';
            }

            const payload = { nombre_rol: nombre };

            try {
                const res = wasEditing
                    ? await window.RolModel.update(id, payload)
                    : await window.RolModel.create(payload);

                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    this._dirty = false;
                    window.RolView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.RolModel.cache;
                        const size = window.pageSize || 8;
                        this.renderPage(Math.max(1, Math.ceil(cache.length / size)));
                    }
                } else {
                    window.Toast.error(res.message);
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al guardar');
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    if (saveBtn.dataset.originalText) saveBtn.textContent = saveBtn.dataset.originalText;
                }
            }
        },

        edit: async function (id) {
            if (String(id) === '1') {
                window.Toast.warning('El rol Administrador no se puede editar.');
                return;
            }
            if (window.RolModel.cache.length === 0) await window.RolModel.load();
            const r = window.RolModel.getById(id);
            if (!r) return;
            window.RolView.toggleForm(false, false);
            window.RolView.fillForm(r);
        },

        confirmDelete: async function (id) {
            if (String(id) === '1') {
                window.Toast.warning('El rol Administrador no se puede eliminar.');
                return;
            }
            const r = window.RolModel.getById(id);
            if (!r) return;

            const currentPage = window.__rolCurrentPage || 1;
            const ok = await window.Modal.confirm(
                `¿Eliminar el rol "${r.nombre_rol}"?`,
                { danger: true, confirmText: 'Eliminar' }
            );
            if (!ok) return;

            try {
                const res = await window.RolModel.delete(id);
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

        _onSearchCleared: function () {
            clearTimeout(this._searchTimer);
            this.renderPage(1);
        },

        _readSearch: function () {
            const input = document.getElementById('roles-search');
            return input ? input.value : '';
        },

        _persist: function (search, page) {
            try {
                sessionStorage.setItem('redytelca.roles.search', search || '');
                sessionStorage.setItem('redytelca.roles.page', String(page || 1));
            } catch (e) { /* no-op */ }
        },

        _filter: function (items, search) {
            const s = window.normalizeForSearch(search || '');
            if (!s) return items;
            return items.filter(r => window.normalizeForSearch(
                (r.nombre_rol || '') + ' ' + (r.descripcion || '')
            ).includes(s));
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.RolController.init());
})();