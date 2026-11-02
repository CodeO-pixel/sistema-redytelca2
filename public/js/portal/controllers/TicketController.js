// js/portal/controllers/TicketController.js
// Controller del portal para tickets (096d).
// Orquesta PortalTicketModel (datos) + PortalTicketView (render).
// No hace fetch directo: todo pasa por el Model.
//
// A3 (2026-11-02): `load(page = null)` con default `null` en vez de
// `undefined`. El caller (`main.js`) ahora llama `load()` sin argumento,
// y este controller usa `this._currentPage` (restaurado de
// sessionStorage en `_restore`). Antes `main.js` llamaba `load(1)` y
// forzaba página 1 en cada navegación. Consistente con el patrón del
// admin (`ContratoController.load = function (page = null)`).
(function () {
    'use strict';

    window.PortalTicketController = {
        _loading: false,
        _saving: false,
        _currentPage: 1,

        init: function () {
            if (window.PortalTicketView) window.PortalTicketView._attachListeners();
            this._restore();
        },

        load: async function (page = null) {
            if (this._loading) return;
            this._loading = true;

            const target = page != null ? page : (this._currentPage || 1);
            const filtros = this._readFilters();

            try {
                const res = await window.PortalTicketModel.load(target, 20, filtros);
                if (res.status !== 'success') {
                    if (window.Toast) window.Toast.error(res.message || 'Error al cargar tus tickets');
                    return;
                }

                const tickets = res.tickets || [];
                const safePage = res.page || 1;
                const totalPages = res.pages || 1;
                const total = res.total || 0;

                this._currentPage = safePage;
                this._persist(filtros, safePage);

                window.PortalTicketView.renderTable(tickets);
                window.PortalTicketView.renderPagination(safePage, totalPages);
                window.PortalTicketView.updateCounter(total, safePage, 20);
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar tus tickets');
            } finally {
                this._loading = false;
            }
        },

        showDetail: async function (id) {
            if (!id) return;

            const cached = window.PortalTicketModel.getById(id);
            if (cached) {
                window.PortalTicketView.renderDetailModal(cached, []);
            }

            try {
                const detalle = await window.PortalTicketModel.loadDetail(id);
                if (detalle) {
                    window.PortalTicketView.renderDetailModal(detalle.ticket, detalle.respuestas);
                } else if (!cached) {
                    if (window.Toast) window.Toast.error('Ticket no encontrado');
                }
            } catch (e) {
                if (!cached && window.Toast) {
                    window.Toast.error(e.message || 'Error al cargar el detalle');
                }
            }
        },

        save: async function () {
            if (this._saving) return;

            const fb = document.getElementById('portal-ticket-form-feedback');
            if (fb) fb.textContent = '';

            const asuntoEl = document.getElementById('portal-ticket-asunto');
            const descEl = document.getElementById('portal-ticket-descripcion');
            const prioEl = document.getElementById('portal-ticket-prioridad');
            const catEl = document.getElementById('portal-ticket-categoria');
            const srvEl = document.getElementById('portal-ticket-servicio');

            const asunto = asuntoEl ? asuntoEl.value.trim() : '';
            const descripcion = descEl ? descEl.value.trim() : '';
            const prioridad = prioEl ? prioEl.value : 'Media';
            const categoriaId = catEl ? catEl.value : '';
            const servicioId = srvEl ? srvEl.value : '';

            if (!asunto) {
                if (fb) fb.textContent = 'El asunto es obligatorio.';
                if (window.Toast) window.Toast.warning('El asunto es obligatorio.');
                return;
            }

            const btn = document.getElementById('portal-ticket-form-submit');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Creando…'; }
            this._saving = true;

            try {
                const res = await window.PortalTicketModel.create({
                    asunto: asunto,
                    descripcion: descripcion,
                    prioridad: prioridad,
                    categoria_id: categoriaId || null,
                    servicio_id: servicioId || null
                });

                if (res.status === 'success') {
                    if (window.Toast) window.Toast.success(res.message || 'Ticket creado');
                    window.PortalTicketView.closeCreateModal();
                    await this.load(1);
                } else {
                    const msg = res.message || 'Error al crear el ticket';
                    if (fb) fb.textContent = msg;
                    if (window.Toast) window.Toast.error(msg);
                }
            } catch (e) {
                const msg = (e && e.message) ? e.message : 'Error al crear el ticket';
                if (fb) fb.textContent = msg;
                if (window.Toast) window.Toast.error(msg);
            } finally {
                this._saving = false;
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        responder: async function () {
            if (this._saving) return;

            const idTicket = document.getElementById('portal-ticket-respuesta-id-ticket')?.value || '';
            const mensajeEl = document.getElementById('portal-ticket-respuesta-mensaje');
            const mensaje = mensajeEl ? mensajeEl.value.trim() : '';

            if (!idTicket) return;
            if (!mensaje) {
                if (window.Toast) window.Toast.warning('Escribe un mensaje.');
                return;
            }

            const btn = document.getElementById('portal-ticket-respuesta-submit');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Enviando…'; }
            this._saving = true;

            try {
                const res = await window.PortalTicketModel.responder(idTicket, mensaje);
                if (res.status === 'success') {
                    if (window.Toast) window.Toast.success(res.message || 'Respuesta enviada');
                    const detalle = await window.PortalTicketModel.loadDetail(idTicket);
                    if (detalle) {
                        window.PortalTicketView.renderDetailModal(detalle.ticket, detalle.respuestas);
                    }
                } else {
                    if (window.Toast) window.Toast.error(res.message || 'Error al enviar');
                }
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al enviar');
            } finally {
                this._saving = false;
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        _readFilters: function () {
            const estado = document.getElementById('portal-tickets-estado');
            const prioridad = document.getElementById('portal-tickets-prioridad');
            return {
                estado: estado ? estado.value : '',
                prioridad: prioridad ? prioridad.value : ''
            };
        },

        _persist: function (filtros, page) {
            try {
                sessionStorage.setItem('redytelca.portal.tickets.estado', filtros.estado || '');
                sessionStorage.setItem('redytelca.portal.tickets.prioridad', filtros.prioridad || '');
                sessionStorage.setItem('redytelca.portal.tickets.page', String(page || 1));
            } catch (e) { /* no-op */ }
        },

        _restore: function () {
            try {
                const estado = sessionStorage.getItem('redytelca.portal.tickets.estado');
                const prioridad = sessionStorage.getItem('redytelca.portal.tickets.prioridad');
                const page = sessionStorage.getItem('redytelca.portal.tickets.page');
                const elEstado = document.getElementById('portal-tickets-estado');
                const elPrio = document.getElementById('portal-tickets-prioridad');
                if (elEstado && estado) elEstado.value = estado;
                if (elPrio && prioridad) elPrio.value = prioridad;
                if (page) this._currentPage = parseInt(page, 10) || 1;
            } catch (e) { /* no-op */ }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.PortalTicketController.init());
})();