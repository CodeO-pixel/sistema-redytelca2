// js/controllers/TareaController.js
(function () {
    'use strict';

    window.TareaController = {
        _searchTimer: null,
        _dirty: false,

        init: function () {
            if (window.TareaView) window.TareaView._attachListeners();
            try {
                const s = sessionStorage.getItem('redytelca.tareas.search');
                const p = sessionStorage.getItem('redytelca.tareas.page');
                const input = document.getElementById('tareas-search');
                if (input && s) input.value = s;
                if (p) window.__tareaCurrentPage = parseInt(p, 10) || 1;
            } catch (e) { /* no-op */ }
        },

        load: async function (page = null) {
            // Reset síncrono ANTES del primer await (E1 + BUG-B3.4-C5)
            if (window.selectedIds) window.selectedIds['tareas'] = null;
            document.querySelectorAll('.toolbar-btn[data-toolbar="tareas"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });
            document.querySelectorAll('#tareas-list tr.selected-row').forEach(r => r.classList.remove('selected-row'));

            try {
                // E7: caches padre
                if (window.ClienteModel && window.ClienteModel.cache.length === 0) {
                    await window.ClienteModel.load();
                }
                if (window.TicketModel && window.TicketModel.cache.length === 0) {
                    await window.TicketModel.load();
                }
                if (window.ServicioModel && window.ServicioModel.cache.length === 0) {
                    await window.ServicioModel.load();
                }
                await window.TareaModel.load();
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar tareas');
                return;
            }

            const target = page != null ? page : (window.__tareaCurrentPage || 1);
            this.renderPage(target);
        },

        renderPage: function (page) {
            const search = this._readSearch();
            const filtered = this._filter(window.TareaModel.cache, search);
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;

            window.__tareaCurrentPage = safePage;
            this._persist(search, safePage);

            window.TareaView.renderTable(filtered.slice(start, start + size), search);
            window.TareaView.renderPagination(safePage, totalPages);
            window.TareaView.updateCounter(filtered.length, safePage, size);
        },

        save: async function () {
            const id = document.getElementById('task-id').value;
            const wasEditing = !!id;
            const currentPage = window.__tareaCurrentPage || 1;

            const clienteVal = document.getElementById('task-client').value;
            const ticketVal = document.getElementById('task-ticket').value;
            const servicioVal = document.getElementById('task-servicio').value;

            const payload = {
                titulo: document.getElementById('task-title').value.trim(),
                descripcion: document.getElementById('task-description').value.trim() || null,
                estado: document.getElementById('task-status').value,
                prioridad: document.getElementById('task-priority').value,
                cliente_id: clienteVal || null,
                ticket_id: ticketVal || null,
                servicio_id: servicioVal || null
            };

            if (!payload.titulo) {
                window.Toast.warning('El título es obligatorio.');
                return;
            }

            const btn = document.querySelector('#task-form [data-action="save-tarea"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }

            try {
                const res = wasEditing
                    ? await window.TareaModel.update(id, payload)
                    : await window.TareaModel.create(payload);

                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Tarea guardada');
                    this._dirty = false;
                    window.TareaView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.TareaModel.cache;
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
            if (window.TareaModel.cache.length === 0) await window.TareaModel.load();
            if (window.ClienteModel && window.ClienteModel.cache.length === 0) await window.ClienteModel.load();
            if (window.TicketModel && window.TicketModel.cache.length === 0) await window.TicketModel.load();
            if (window.ServicioModel && window.ServicioModel.cache.length === 0) await window.ServicioModel.load();

            const t = window.TareaModel.getById(id);
            if (!t) return;
            window.TareaView.toggleForm(false, false);
            window.TareaView.fillForm(t);
        },

        confirmDelete: async function (id) {
            const t = window.TareaModel.getById(id);
            if (!t) return;
            const currentPage = window.__tareaCurrentPage || 1;
            const ok = await window.Modal.confirm(
                `¿Eliminar la tarea "${t.titulo}"?`,
                { danger: true, confirmText: 'Eliminar' }
            );
            if (!ok) return;

            try {
                const res = await window.TareaModel.delete(id);
                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Tarea eliminada');
                    await this.load(currentPage);
                } else {
                    window.Toast.error(res.message || 'Error al eliminar');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al eliminar');
            }
        },

        _onClienteChange: function () {
            const cli = document.getElementById('task-client');
            const clienteId = cli ? cli.value : '';
            window.TareaView.populateSelects(clienteId || null, null, null);
        },

        openNewFromTicket: async function (ticket) {
            if (!ticket) return;
            if (window.ClienteModel && window.ClienteModel.cache.length === 0) await window.ClienteModel.load();
            if (window.TicketModel && window.TicketModel.cache.length === 0) await window.TicketModel.load();
            if (window.ServicioModel && window.ServicioModel.cache.length === 0) await window.ServicioModel.load();

            window.TareaView.toggleForm(false, true);
            window.TareaView.populateSelects(
                ticket.cliente_id || null,
                ticket.id || null,
                ticket.servicio_id || null
            );

            // Bloquear cliente/ticket/servicio (readonly): la tarea nace del ticket
            const cli = document.getElementById('task-client');
            const tkt = document.getElementById('task-ticket');
            const srv = document.getElementById('task-servicio');
            if (cli) cli.disabled = true;
            if (tkt) tkt.disabled = true;
            if (srv) srv.disabled = true;

            const title = document.getElementById('task-title');
            if (title) {
                title.value = 'Seguimiento: ' + (ticket.asunto || 'Ticket');
                setTimeout(() => title.focus(), 60);
            }
            this._dirty = true;
        },

        _onSearchCleared: function () {
            clearTimeout(this._searchTimer);
            this.renderPage(1);
        },

        _readSearch: function () {
            const input = document.getElementById('tareas-search');
            return input ? input.value : '';
        },

        _persist: function (search, page) {
            try {
                sessionStorage.setItem('redytelca.tareas.search', search);
                sessionStorage.setItem('redytelca.tareas.page', String(page));
            } catch (e) { /* no-op */ }
        },

        _filter: function (items, search) {
            const s = window.normalizeForSearch(search || '');
            if (!s) return items;
            return items.filter(t => window.normalizeForSearch(
                (t.titulo || '') + ' ' + (t.descripcion || '') + ' ' +
                (t.nombres || '') + ' ' + (t.apellidos || '') + ' ' +
                (t.cedula || '') + ' ' + (t.creador || '') + ' ' +
                (t.prioridad || '') + ' ' + (t.estado || '')
            ).includes(s));
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.TareaController.init());
})();