// js/controllers/PedidoController.js
(function () {
    'use strict';

    window.PedidoController = {
        _searchTimer: null,
        _dirty: false,
        _saving: false,

        init: function () {
            if (window.PedidoView) window.PedidoView._attachListeners();
            try {
                const s = sessionStorage.getItem('redytelca.pedidos.search');
                const p = sessionStorage.getItem('redytelca.pedidos.page');
                const input = document.getElementById('pedidos-search');
                if (input && s) input.value = s;
                if (p) window.__pedidoCurrentPage = parseInt(p, 10) || 1;
            } catch (e) { /* no-op */ }
        },

        load: async function (page = null) {
            if (window.selectedIds) window.selectedIds['pedidos'] = null;
            document.querySelectorAll('.toolbar-btn[data-toolbar="pedidos"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (['edit', 'delete', 'asignar', 'materiales', 'historial', 'ver-en-mapa'].includes(action)) {
                    btn.disabled = true;
                }
            });
            document.querySelectorAll('#pedidos-list tr.selected-row').forEach(r => r.classList.remove('selected-row'));

            try {
                await window.PedidoModel.load();
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar pedidos');
                return;
            }

            const safeLoad = async (model) => {
                if (!model || model.cache.length > 0) return;
                try { await model.load(); } catch (e) { /* opcional, no rompe */ }
            };
            await safeLoad(window.ClienteModel);
            await safeLoad(window.ServicioModel);
            await safeLoad(window.UsuarioModel);
            await safeLoad(window.EquipoModel);

            const target = page != null ? page : (window.__pedidoCurrentPage || 1);
            this.renderPage(target);
        },

        renderPage: function (page) {
            const search = this._readSearch();
            const filtered = this._filter(window.PedidoModel.cache, search);
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;

            window.__pedidoCurrentPage = safePage;
            this._persist(search, safePage);

            window.PedidoView.renderTable(filtered.slice(start, start + size), search);
            window.PedidoView.renderPagination(safePage, totalPages);
            window.PedidoView.updateCounter(filtered.length, safePage, size);
        },

        save: async function () {
            if (this._saving) return;

            const id = document.getElementById('pedido-id').value;
            const wasEditing = !!id;
            const currentPage = window.__pedidoCurrentPage || 1;

            const clienteVal = document.getElementById('pedido-cliente').value;
            const servicioVal = document.getElementById('pedido-servicio').value;

            const payload = {
                cliente_id: clienteVal,
                servicio_id: servicioVal || null,
                tipo: document.getElementById('pedido-tipo').value,
                prioridad: document.getElementById('pedido-prioridad').value,
                descripcion: document.getElementById('pedido-descripcion').value.trim() || null,
                fecha_programada: document.getElementById('pedido-fecha-programada').value || null
            };

            if (wasEditing) {
                payload.estado = document.getElementById('pedido-estado').value;
            }

            if (!payload.cliente_id) {
                window.Toast.warning('Selecciona un cliente.');
                return;
            }
            if (!payload.tipo) {
                window.Toast.warning('Selecciona un tipo de pedido.');
                return;
            }

            this._saving = true;
            const btn = document.querySelector('#pedido-form [data-action="save-pedido"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }

            try {
                const res = wasEditing
                    ? await window.PedidoModel.update(id, payload)
                    : await window.PedidoModel.create(payload);

                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Pedido guardado');
                    this._dirty = false;
                    window.PedidoView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.PedidoModel.cache;
                        const size = window.pageSize || 8;
                        this.renderPage(Math.max(1, Math.ceil(cache.length / size)));
                    }
                } else {
                    window.Toast.error(res.message || 'Error al guardar');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al guardar');
            } finally {
                this._saving = false;
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        completarPedido: async function (id) {
            if (this._saving) return;
            const p = window.PedidoModel.getById(id);
            if (!p) return;
            if (String(p.estado || '').toLowerCase() === 'completado') {
                window.Toast.info('Este pedido ya está completado.');
                return;
            }
            if (String(p.estado || '').toLowerCase() === 'cancelado') {
                window.Toast.warning('No se puede completar un pedido cancelado.');
                return;
            }

            const esInstalacion = String(p.tipo || '').toLowerCase() === 'instalacion';
            const tieneReserva = !!p.equipo_reservado_id;
            let mensaje = `¿Marcar el pedido #${p.id} como completado?`;
            if (esInstalacion) {
                if (tieneReserva) {
                    mensaje += ' Se asignará el equipo reservado al servicio y el servicio pasará a activo.';
                } else {
                    mensaje += ' El pedido es de instalación pero no tiene equipo reservado. El servicio NO tendrá equipo asignado.';
                }
            }
            const ok = await window.Modal.confirm(mensaje, { confirmText: 'Completar', danger: false });
            if (!ok) return;

            this._saving = true;
            const btn = document.querySelector(`.btn-completar-pedido[data-id="${id}"]`);
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Completando…'; }

            try {
                const res = await window.PedidoModel.completar(id);
                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Pedido completado.');
                    await this.load(window.__pedidoCurrentPage || 1);
                    // Refrescar caches afectados: equipos y servicios.
                    try { await window.EquipoModel.load(); } catch (e) { /* opcional */ }
                    try { await window.ServicioModel.load(); } catch (e) { /* opcional */ }
                } else {
                    window.Toast.error(res.message || 'Error al completar.');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al completar.');
            } finally {
                this._saving = false;
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        edit: async function (id) {
            if (window.PedidoModel.cache.length === 0) await window.PedidoModel.load();
            if (window.ClienteModel && window.ClienteModel.cache.length === 0) await window.ClienteModel.load();
            if (window.ServicioModel && window.ServicioModel.cache.length === 0) await window.ServicioModel.load();
            const p = window.PedidoModel.getById(id);
            if (!p) return;
            window.PedidoView.toggleForm(false, false);
            window.PedidoView.fillForm(p);
        },

        confirmDelete: async function (id) {
            const p = window.PedidoModel.getById(id);
            if (!p) return;
            const currentPage = window.__pedidoCurrentPage || 1;
            const ok = await window.Modal.confirm(
                `¿Eliminar el pedido #${p.id}?`,
                { danger: true, confirmText: 'Eliminar' }
            );
            if (!ok) return;
            try {
                const res = await window.PedidoModel.delete(id);
                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Pedido eliminado');
                    await this.load(currentPage);
                } else {
                    window.Toast.error(res.message || 'Error al eliminar');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al eliminar');
            }
        },

        _onClienteChange: function () {
            const cli = document.getElementById('pedido-cliente');
            const clienteId = cli ? cli.value : '';
            window.PedidoView._onClienteChange(clienteId);
        },

        openNewFromTicket: async function (ticket) {
            if (!ticket) return;
            if (window.ClienteModel && window.ClienteModel.cache.length === 0) await window.ClienteModel.load();
            if (window.ServicioModel && window.ServicioModel.cache.length === 0) await window.ServicioModel.load();
            if (window.UsuarioModel && window.UsuarioModel.cache.length === 0) await window.UsuarioModel.load();

            window.PedidoView.toggleForm(false, true);
            window.PedidoView.populateSelects(ticket.cliente_id || null, ticket.servicio_id || null, null);

            const cli = document.getElementById('pedido-cliente');
            const srv = document.getElementById('pedido-servicio');
            if (cli) cli.disabled = true;
            if (srv) srv.disabled = true;

            const desc = document.getElementById('pedido-descripcion');
            if (desc) {
                desc.value = 'Seguimiento del ticket: ' + (ticket.asunto || 'Ticket');
                setTimeout(() => desc.focus(), 60);
            }
            this._dirty = true;
        },

        abrirAsignarTecnico: async function (id) {
            const p = window.PedidoModel.getById(id);
            if (!p) return;
            if (window.UsuarioModel && window.UsuarioModel.cache.length === 0) {
                await window.UsuarioModel.load();
            }
            window.PedidoView.renderAsignarTecnicoModal(p);
        },

        confirmarAsignacion: async function () {
            if (this._saving) return;

            const idInput = document.getElementById('asignar-tecnico-id-pedido');
            const selTec = document.getElementById('asignar-tecnico-select');
            const idPedido = idInput ? idInput.value : '';
            const tecnicoId = selTec ? selTec.value : '';

            if (!idPedido) return;
            if (!tecnicoId) {
                window.Toast.warning('Selecciona un técnico.');
                return;
            }

            this._saving = true;
            const btn = document.querySelector('#asignar-tecnico-modal [data-action="confirm-asignar"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Asignando…'; }

            try {
                const res = await window.PedidoModel.asignarTecnico(idPedido, tecnicoId);
                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Técnico asignado');
                    const m = document.getElementById('asignar-tecnico-modal');
                    if (m) m.style.display = 'none';
                    await this.load(window.__pedidoCurrentPage || 1);
                } else {
                    window.Toast.error(res.message || 'Error al asignar');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al asignar');
            } finally {
                this._saving = false;
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        abrirMateriales: async function (id) {
            const p = window.PedidoModel.getById(id);
            if (!p) return;
            // Recargar equipos frescos para que el select de material
            // refleje el estado actual de stock.
            try { await window.EquipoModel.load(); } catch (e) { /* opcional */ }
            try {
                const materiales = await window.PedidoModel.loadMateriales(id);
                window.PedidoView.renderMaterialesModal(p, materiales);
            } catch (e) {
                window.Toast.error(e.message || 'Error al cargar materiales');
            }
        },

        agregarMaterial: async function () {
            if (this._saving) return;

            const idInput = document.getElementById('materiales-id-pedido');
            const idPedido = idInput ? idInput.value : '';
            if (!idPedido) return;

            const payload = {
                pedido_id: Number(idPedido),
                descripcion: (document.getElementById('material-descripcion')?.value || '').trim(),
                cantidad: document.getElementById('material-cantidad')?.value || '1',
                unidad: (document.getElementById('material-unidad')?.value || '').trim() || 'unidad',
                equipo_id: document.getElementById('material-equipo')?.value || null,
                notas: (document.getElementById('material-notas')?.value || '').trim() || null
            };

            if (!payload.descripcion) {
                window.Toast.warning('La descripción del material es obligatoria.');
                return;
            }

            this._saving = true;
            const btn = document.querySelector('#materiales-modal [data-action="add-material"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Añadiendo…'; }

            try {
                const res = await window.PedidoModel.createMaterial(payload);
                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Material añadido');
                    // Recargar pedidos y equipos: el equipo reservado
                    // ya no debe aparecer en stock para otros pedidos.
                    try { await window.PedidoModel.load(); } catch (e) { /* opcional */ }
                    try { await window.EquipoModel.load(); } catch (e) { /* opcional */ }
                    const p = window.PedidoModel.getById(idPedido);
                    const materiales = await window.PedidoModel.loadMateriales(idPedido);
                    if (p) window.PedidoView.renderMaterialesModal(p, materiales);
                    this.renderPage(window.__pedidoCurrentPage || 1);
                } else {
                    window.Toast.error(res.message || 'Error al añadir material');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al añadir material');
            } finally {
                this._saving = false;
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        eliminarMaterial: async function (idPedido, idMaterial) {
            if (this._saving) return;

            const ok = await window.Modal.confirm(
                '¿Eliminar este material del pedido?',
                { danger: true, confirmText: 'Eliminar' }
            );
            if (!ok) return;

            this._saving = true;
            try {
                const res = await window.PedidoModel.deleteMaterial(idMaterial);
                if (res.status === 'success') {
                    window.Toast.success(res.message || 'Material eliminado');
                    try { await window.PedidoModel.load(); } catch (e) { /* opcional */ }
                    try { await window.EquipoModel.load(); } catch (e) { /* opcional */ }
                    const p = window.PedidoModel.getById(idPedido);
                    const materiales = await window.PedidoModel.loadMateriales(idPedido);
                    if (p) window.PedidoView.renderMaterialesModal(p, materiales);
                } else {
                    window.Toast.error(res.message || 'Error al eliminar');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al eliminar');
            } finally {
                this._saving = false;
            }
        },

        abrirReservarEquipo: async function (idPedido) {
            const p = window.PedidoModel.getById(idPedido);
            if (!p) return;
            try { await window.PedidoModel.loadEquiposStock(idPedido); } catch (e) { /* fallback */ }
            window.PedidoView.renderReservarEquipoModal(p);
        },

        recargarEquiposStock: async function () {
            const idInput = document.getElementById('asignar-equipo-id-pedido');
            const idPedido = idInput ? idInput.value : null;
            try {
                await window.PedidoModel.loadEquiposStock(idPedido);
                window.PedidoView._renderEquiposStockSelect();
                const equipos = window.PedidoModel.equiposStockCache || [];
                if (equipos.length === 0) {
                    window.Toast.warning('No hay equipos en stock disponibles.');
                } else {
                    window.Toast.success(`${equipos.length} equipo(s) disponible(s).`);
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al recargar equipos');
            }
        },

        confirmarReservarEquipo: async function () {
            if (this._saving) return;

            const idInput = document.getElementById('asignar-equipo-id-pedido');
            const select = document.getElementById('asignar-equipo-select');
            const idPedido = idInput ? idInput.value : '';
            const equipoId = select ? select.value : '';

            if (!idPedido) return;
            if (!equipoId) {
                window.Toast.warning('Selecciona un equipo.');
                return;
            }

            const pedido = window.PedidoModel.getById(idPedido);
            if (!pedido) return;

            const equipo = (window.PedidoModel.equiposStockCache || []).find(eq => String(eq.id) === String(equipoId));
            const tipoLabel = equipo ? `${equipo.tipo || 'Equipo'} ${equipo.marca || ''} ${equipo.modelo || ''}`.trim() : `Equipo #${equipoId}`;

            this._saving = true;
            const btn = document.querySelector('#asignar-equipo-modal [data-action="confirm-asignar-equipo"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Reservando…'; }

            try {
                const res = await window.PedidoModel.createMaterial({
                    pedido_id: Number(idPedido),
                    descripcion: `Equipo reservado: ${tipoLabel}`,
                    cantidad: 1,
                    unidad: 'equipo',
                    equipo_id: Number(equipoId),
                    notas: null
                });
                if (res.status === 'success') {
                    window.Toast.success('Equipo reservado. Se asignará al servicio cuando el pedido se complete.');
                    const m = document.getElementById('asignar-equipo-modal');
                    if (m) m.style.display = 'none';
                    try { await window.PedidoModel.load(); } catch (e) { /* opcional */ }
                    try { await window.EquipoModel.load(); } catch (e) { /* opcional */ }
                    const p = window.PedidoModel.getById(idPedido);
                    if (p) {
                        const materiales = await window.PedidoModel.loadMateriales(idPedido);
                        window.PedidoView.renderMaterialesModal(p, materiales);
                    }
                    this.renderPage(window.__pedidoCurrentPage || 1);
                } else {
                    window.Toast.error(res.message || 'Error al reservar equipo');
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al reservar equipo');
            } finally {
                this._saving = false;
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        abrirCrearEquipo: async function () {
            if (window.NapModel && window.NapModel.cache.length === 0) {
                try { await window.NapModel.load(); } catch (e) { /* opcional */ }
            }
            if (window.ServicioModel && window.ServicioModel.cache.length === 0) {
                try { await window.ServicioModel.load(); } catch (e) { /* opcional */ }
            }
            if (window.EquipoView && typeof window.EquipoView.toggleForm === 'function') {
                window.EquipoView.toggleForm();
                const est = document.getElementById('equipo-estado');
                if (est) est.value = 'stock';
                const srv = document.getElementById('equipo-servicio');
                if (srv) srv.value = '';
            } else if (window.Router) {
                window.Router.showPage('equipos');
            }
        },

        abrirHistorial: async function (id) {
            const p = window.PedidoModel.getById(id);
            if (!p) return;
            try {
                const asignaciones = await window.PedidoModel.loadAsignaciones(id);
                window.PedidoView.renderHistorialModal(p, asignaciones);
            } catch (e) {
                window.Toast.error(e.message || 'Error al cargar historial');
            }
        },

        verUbicacion: function (id) {
            const p = window.PedidoModel.getById(id);
            if (!p) return;
            window.PedidoView.renderUbicacionModal(p);
        },

        verTicketRelacionado: async function (id) {
            const p = window.PedidoModel.getById(id);
            if (!p) return;
            if (!p.servicio_id) {
                window.Toast.warning('Este pedido no tiene servicio asociado.');
                return;
            }

            if (window.TicketModel && window.TicketModel.cache.length === 0) {
                try {
                    await window.TicketModel.load();
                } catch (e) {
                    window.Toast.error(e.message || 'Error al cargar tickets');
                    return;
                }
            }

            const tickets = window.TicketModel && window.TicketModel.cache ? window.TicketModel.cache : [];
            const relacionados = tickets.filter(t => String(t.servicio_id) === String(p.servicio_id));

            if (relacionados.length === 0) {
                window.Toast.info('No hay tickets asociados a este servicio.');
                return;
            }

            if (relacionados.length === 1) {
                window.Router.showPage('tickets');
                if (window.TicketController) window.TicketController.edit(relacionados[0].id);
                return;
            }

            const asuntos = relacionados.slice(0, 3).map(t => `#${t.id} ${t.asunto || ''}`).join(', ');
            const extra = relacionados.length > 3 ? ` y ${relacionados.length - 3} más` : '';
            window.Toast.info(`Este servicio tiene ${relacionados.length} tickets: ${asuntos}${extra}. Abre el módulo Tickets para verlos.`);
        },

        _onSearchCleared: function () {
            clearTimeout(this._searchTimer);
            this.renderPage(1);
        },

        _readSearch: function () {
            const input = document.getElementById('pedidos-search');
            return input ? input.value : '';
        },

        _persist: function (search, page) {
            try {
                sessionStorage.setItem('redytelca.pedidos.search', search);
                sessionStorage.setItem('redytelca.pedidos.page', String(page));
            } catch (e) { /* no-op */ }
        },

        _filter: function (items, search) {
            const s = window.normalizeForSearch(search || '');
            if (!s) return items;
            return items.filter(p => window.normalizeForSearch(
                (p.nombres || '') + ' ' + (p.apellidos || '') + ' ' +
                (p.cedula || '') + ' ' + (p.servicio_alias || '') + ' ' +
                (p.servicio_direccion || '') + ' ' + (p.tecnico_username || '') + ' ' +
                (p.tipo || '') + ' ' + (p.estado || '') + ' ' + (p.prioridad || '')
            ).includes(s));
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.PedidoController.init());
})();