// js/controllers/TicketController.js
(function () {
    'use strict';

    window.TicketController = {
        _searchTimer: null,
        _dirty: false,
        _currentFilter: 'Todos',

        init: function () {
            if (window.TicketView) window.TicketView._attachListeners();
            try {
                const s = sessionStorage.getItem('redytelca.tickets.search');
                const p = sessionStorage.getItem('redytelca.tickets.page');
                const f = sessionStorage.getItem('redytelca.tickets.filter');
                const input = document.getElementById('tickets-search');
                if (input && s) input.value = s;
                if (p) window.__ticketCurrentPage = parseInt(p, 10) || 1;
                if (f) this._currentFilter = f;
                // Sincronizar pill activa
                document.querySelectorAll('#ticket-filters [data-ticket-filter]').forEach(pill => {
                    if (pill.dataset.ticketFilter === this._currentFilter) pill.classList.add('active');
                    else pill.classList.remove('active');
                });
            } catch (e) { /* no-op */ }
        },

        load: async function (page = null) {
            // Reset síncrono ANTES de await
            if (window.selectedIds) window.selectedIds['tickets'] = null;
            document.querySelectorAll('.toolbar-btn[data-toolbar="tickets"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (['edit', 'delete', 'cerrar'].includes(action)) btn.disabled = true;
            });
            document.querySelectorAll('#tickets-table tbody tr.selected-row').forEach(r => r.classList.remove('selected-row'));

            try {
                // Caches padre
                const needClientes = !window.ClienteModel || window.ClienteModel.cache.length === 0;
                const needServicios = !window.ServicioModel || window.ServicioModel.cache.length === 0;
                const needCategorias = !window.TicketModel || window.TicketModel.categoriasCache.length === 0;

                await Promise.all([
                    needClientes && window.ClienteModel ? window.ClienteModel.load() : null,
                    needServicios && window.ServicioModel ? window.ServicioModel.load() : null,
                    needCategorias && window.TicketModel ? window.TicketModel.loadCategorias() : null
                ]);

                await window.TicketModel.load();
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar tickets');
                return;
            }

            const target = page != null ? page : (window.__ticketCurrentPage || 1);
            this.renderPage(target);
        },

        renderPage: function (page) {
            const search = this._readSearch();
            const filter = this._currentFilter;
            let items = window.TicketModel.cache;

            if (filter && filter !== 'Todos') {
                items = items.filter(t => String(t.estado || '').toLowerCase() === filter.toLowerCase());
            }
            const filtered = this._filter(items, search);

            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;

            window.__ticketCurrentPage = safePage;
            this._persist(search, safePage, filter);

            window.TicketView.renderTable(filtered.slice(start, start + size), search);
            window.TicketView.renderPagination(safePage, totalPages);
            window.TicketView.updateCounter(filtered.length, safePage, size);
        },

        setFilter: function (filter) {
            this._currentFilter = filter || 'Todos';
            this.renderPage(1);
        },

        save: async function () {
            const id = document.getElementById('ticket-id').value;
            const wasEditing = !!id;
            const currentPage = window.__ticketCurrentPage || 1;

            const payload = {
                asunto: document.getElementById('ticket-subject').value.trim(),
                descripcion: document.getElementById('ticket-description').value.trim() || null,
                prioridad: document.getElementById('ticket-priority').value,
                cliente_id: document.getElementById('ticket-client').value,
                servicio_id: document.getElementById('ticket-servicio').value || null,
                categoria_id: document.getElementById('ticket-categoria').value || null
            };

            if (wasEditing) {
                payload.estado = document.getElementById('ticket-status').value;
            } else {
                payload.estado = 'Abierto';
                payload.origen = 'backoffice';
            }

            if (!payload.asunto) { window.Toast.warning('El asunto es obligatorio.'); return; }
            if (!payload.cliente_id) { window.Toast.warning('Selecciona un cliente.'); return; }

            // Defensa: Cerrado es terminal
            if (wasEditing) {
                const actual = window.TicketModel.getById(id);
                if (actual && String(actual.estado).toLowerCase() === 'cerrado' && payload.estado !== 'Cerrado') {
                    window.Toast.warning('Un ticket cerrado no puede cambiar de estado. Crea un ticket nuevo si necesitas reabrir la conversación.');
                    return;
                }
            }

            const btn = document.querySelector('#ticket-form [data-action="save-ticket"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }

            try {
                const res = wasEditing
                    ? await window.TicketModel.update(id, payload)
                    : await window.TicketModel.create(payload);

                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Ticket guardado');
                    this._dirty = false;
                    window.TicketView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.TicketModel.cache;
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
            if (window.TicketModel.cache.length === 0) await window.TicketModel.load();
            if (window.ClienteModel && window.ClienteModel.cache.length === 0) await window.ClienteModel.load();
            if (window.ServicioModel && window.ServicioModel.cache.length === 0) await window.ServicioModel.load();
            if (window.TicketModel && window.TicketModel.categoriasCache.length === 0) await window.TicketModel.loadCategorias();

            const t = window.TicketModel.getById(id);
            if (!t) return;
            window.TicketView.toggleForm(false, false);
            window.TicketView.populateSelects(t.cliente_id || null, t.servicio_id || null);
            window.TicketView.fillForm(t);
        },

        confirmDelete: async function (id) {
            const t = window.TicketModel.getById(id);
            if (!t) return;
            const currentPage = window.__ticketCurrentPage || 1;
            const ok = await window.Modal.confirm(
                `¿Eliminar el ticket "${t.asunto}"?`,
                { danger: true, confirmText: 'Eliminar' }
            );
            if (!ok) return;

            try {
                const res = await window.TicketModel.delete(id);
                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Ticket eliminado');
                    await this.load(currentPage);
                } else {
                    window.Toast.error(res.message || 'Error al eliminar');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al eliminar');
            }
        },

        abrirCierreTicket: function (id) {
            const t = window.TicketModel.getById(id);
            if (!t) return;
            const m = document.getElementById('close-ticket-modal');
            const idInput = document.getElementById('close-ticket-id');
            const details = document.getElementById('close-ticket-details');
            if (idInput) idInput.value = t.id;
            if (details) details.value = '';
            if (m) m.style.display = 'flex';
            if (details) setTimeout(() => details.focus(), 50);
        },

        confirmarCierreTicket: async function () {
            const id = document.getElementById('close-ticket-id').value;
            const details = document.getElementById('close-ticket-details').value.trim();
            if (!id) return;

            try {
                const res = await window.TicketModel.cerrar(id, details);
                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Ticket cerrado');
                    const m = document.getElementById('close-ticket-modal');
                    if (m) m.style.display = 'none';
                    await this.load(window.__ticketCurrentPage || 1);
                } else {
                    window.Toast.error(res.message || 'Error al cerrar');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al cerrar');
            }
        },

        verRespuestas: async function (id) {
            const t = window.TicketModel.getById(id);
            if (!t) return;
            try {
                const respuestas = await window.TicketModel.loadRespuestas(id);
                window.TicketView.renderRespuestasModal(t, respuestas);
            } catch (e) {
                window.Toast.error(e.message || 'Error al cargar respuestas');
            }
        },

        crearRespuesta: async function () {
            const idTicket = document.getElementById('ticket-respuesta-id-ticket').value;
            const mensaje = document.getElementById('ticket-respuesta-mensaje').value.trim();
            if (!idTicket) return;
            if (!mensaje) { window.Toast.warning('Escribe un mensaje.'); return; }

            const btn = document.querySelector('#ticket-respuestas-modal [data-action="save-respuesta"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Enviando…'; }

            try {
                const res = await window.TicketModel.createRespuesta(idTicket, mensaje);
                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Respuesta enviada');
                    const t = window.TicketModel.getById(idTicket);
                    const respuestas = await window.TicketModel.loadRespuestas(idTicket);
                    if (t) window.TicketView.renderRespuestasModal(t, respuestas);
                } else {
                    window.Toast.error(res.message || 'Error al enviar');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al enviar');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        exportClosedTicketsPdf: function () {
            const cerrados = window.TicketModel.cache.filter(t => String(t.estado).toLowerCase() === 'cerrado');
            if (!cerrados.length) {
                window.Toast.warning('No hay tickets cerrados.');
                return;
            }

            const rows = cerrados.map(t => `<tr>
                <td>${escapeHtml(t.asunto)}</td>
                <td>${escapeHtml(`${t.nombres || ''} ${t.apellidos || ''}`.trim() || 'Sin cliente')}</td>
                <td>${escapeHtml(t.cedula || '—')}</td>
                <td>${escapeHtml(t.categoria_nombre || '—')}</td>
                <td>${escapeHtml(t.creador || '—')}</td>
                <td>${escapeHtml(t.descripcion || '')}</td>
            </tr>`).join('');

            function escapeHtml(unsafe) {
                return String(unsafe ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            const win = window.open('', '_blank');
            if (!win) {
                window.Toast.error('Pop-up bloqueado. Habilita las ventanas emergentes.');
                return;
            }
            win.document.write(`<html><head><title>Tickets cerrados</title><style>
                body{font-family:sans-serif;padding:20px;}
                table{width:100%;border-collapse:collapse;margin-top:12px;}
                th,td{border:1px solid #ccc;padding:8px;text-align:left;vertical-align:top;font-size:0.9rem;}
                h2{margin:0 0 8px 0;}
            </style></head><body>
                <h2>Tickets cerrados</h2>
                <p>Total: ${cerrados.length}</p>
                <table>
                    <thead><tr><th>Asunto</th><th>Cliente</th><th>Cédula</th><th>Categoría</th><th>Creador</th><th>Descripción</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </body></html>`);
            win.document.close();
            setTimeout(() => win.print(), 250);
        },

        _onSearchCleared: function () {
            clearTimeout(this._searchTimer);
            this.renderPage(1);
        },

        _readSearch: function () {
            const input = document.getElementById('tickets-search');
            return input ? input.value : '';
        },

        _persist: function (search, page, filter) {
            try {
                sessionStorage.setItem('redytelca.tickets.search', search);
                sessionStorage.setItem('redytelca.tickets.page', String(page));
                sessionStorage.setItem('redytelca.tickets.filter', filter || 'Todos');
            } catch (e) { /* no-op */ }
        },

        _filter: function (items, search) {
            const s = window.normalizeForSearch(search || '');
            if (!s) return items;
            return items.filter(t => window.normalizeForSearch(
                (t.asunto || '') + ' ' + (t.descripcion || '') + ' ' +
                (t.nombres || '') + ' ' + (t.apellidos || '') + ' ' +
                (t.cedula || '') + ' ' + (t.categoria_nombre || '') + ' ' +
                (t.prioridad || '') + ' ' + (t.estado || '')
            ).includes(s));
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.TicketController.init());
})();