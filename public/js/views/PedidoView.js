// js/views/PedidoView.js
(function () {
    'use strict';

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function mark(txt, search) {
        const s = window.normalizeForSearch(search || '');
        if (!s) return esc(txt);
        const raw = String(txt ?? '');
        const idx = window.normalizeForSearch(raw).indexOf(s);
        if (idx < 0) return esc(raw);
        return esc(raw.slice(0, idx)) + '<mark>' + esc(raw.slice(idx, idx + s.length)) + '</mark>' + esc(raw.slice(idx + s.length));
    }

    function getStatusClass(estado) {
        const s = String(estado || '').toLowerCase();
        if (s === 'completado') return 'status-ok';
        if (s === 'en_curso' || s === 'asignado') return 'status-warning';
        return 'status-alert';
    }

    function getPriorityClass(prioridad) {
        const s = String(prioridad || '').toLowerCase();
        if (s === 'alta') return 'status-alert';
        if (s === 'baja') return 'status-ok';
        return 'status-warning';
    }

    function formatTipo(tipo) {
        const map = {
            'instalacion': 'Instalación',
            'reparacion': 'Reparación',
            'retiro': 'Retiro',
            'cambio_plan': 'Cambio plan',
            'traslado': 'Traslado'
        };
        return map[String(tipo || '').toLowerCase()] || tipo || '—';
    }

    function formatEstado(estado) {
        const map = {
            'pendiente': 'Pendiente',
            'asignado': 'Asignado',
            'en_curso': 'En curso',
            'completado': 'Completado',
            'cancelado': 'Cancelado'
        };
        return map[String(estado || '').toLowerCase()] || estado || '—';
    }

    function labelServicioForPedido(pedido) {
        const alias = pedido.servicio_alias ? String(pedido.servicio_alias).trim() : '';
        const dir = pedido.servicio_direccion ? String(pedido.servicio_direccion).trim() : '';
        return alias || dir || '';
    }

    function labelEquipoForPedido(pedido) {
        const estado = String(pedido.estado || '').toLowerCase();
        const esCompletado = estado === 'completado';

        if (esCompletado && pedido.equipo_tipo) {
            const tipo = pedido.equipo_tipo || '';
            const mac = pedido.equipo_mac || '';
            const est = pedido.equipo_estado ? ` (${pedido.equipo_estado})` : '';
            return `${tipo} ${mac}${est}`.trim();
        }
        if (!esCompletado && pedido.equipo_reservado_tipo) {
            const tipo = pedido.equipo_reservado_tipo || '';
            const mac = pedido.equipo_reservado_mac || '';
            return `Reservado: ${tipo} ${mac}`.trim();
        }
        if (pedido.equipo_tipo) {
            const tipo = pedido.equipo_tipo || '';
            const mac = pedido.equipo_mac || '';
            return `${tipo} ${mac}`.trim();
        }
        return '';
    }

    /**
     * Etiqueta de un equipo para select y tabla de materiales.
     * Formato: "TIPO marca modelo (serie)".
     */
    function labelEquipoOption(eq) {
        if (!eq) return '';
        const tipo = eq.tipo || 'Equipo';
        const marcaModelo = [eq.marca, eq.modelo].filter(Boolean).join(' ');
        const serie = eq.serie ? ` (${eq.serie})` : '';
        return marcaModelo ? `${tipo} ${marcaModelo}${serie}` : `${tipo}${serie}`;
    }

    function labelEquipoMaterial(material) {
        if (!material.equipo_id) return '—';
        if (window.EquipoModel) {
            const eq = window.EquipoModel.cache.find(e => String(e.id) === String(material.equipo_id));
            if (eq) return labelEquipoOption(eq);
        }
        return `Equipo #${material.equipo_id}`;
    }

    /**
     * Filtra equipos en stock que no estén reservados en otros
     * pedidos pendientes.
     *
     * @param {Array} equipos  Lista de equipos (típicamente EquipoModel.cache)
     * @param {number} pedidoId  ID del pedido actual (su reserva no se excluye)
     * @returns {Array}
     */
    function equiposDisponiblesParaPedido(equipos, pedidoId) {
        const reservados = (window.PedidoModel && window.PedidoModel._getEquiposReservadosEnOtros)
            ? window.PedidoModel._getEquiposReservadosEnOtros(pedidoId)
            : new Set();
        return (equipos || []).filter(eq => {
            const enStock = String(eq.estado_inventario || '').toLowerCase() === 'stock';
            if (!enStock) return false;
            return !reservados.has(String(eq.id));
        });
    }

    window.PedidoView = {
        _materialesCache: [],
        _currentPedidoId: null,
        _boundEscape: null,

        renderTable: function (pedidos, search) {
            const tbody = document.getElementById('pedidos-list');
            if (!tbody) return;

            if (pedidos.length === 0) {
                const hasSearch = window.normalizeForSearch(search || '').length > 0;
                tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:24px;">${
                    hasSearch
                        ? `Sin resultados para "<strong>${esc(search)}</strong>". <a href="#" data-action="clear-search">Limpiar búsqueda</a>`
                        : `No hay pedidos todavía. <a href="#" data-action="new-pedido-empty">Crear el primero</a>`
                }</td></tr>`;
                const clear = tbody.querySelector('[data-action="clear-search"]');
                if (clear) clear.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const i = document.getElementById('pedidos-search');
                    if (i) i.value = '';
                    window.PedidoController._onSearchCleared();
                });
                const cta = tbody.querySelector('[data-action="new-pedido-empty"]');
                if (cta) cta.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    this.toggleForm(false, true);
                });
                return;
            }

            tbody.innerHTML = pedidos.map(p => {
                const nombreCliente = `${p.nombres || ''} ${p.apellidos || ''}`.trim() || 'Sin cliente';
                const cedula = p.cedula || '';
                const servicio = labelServicioForPedido(p);
                const equipoLabel = labelEquipoForPedido(p);
                const tecnico = p.tecnico_username || '';
                const id = p.id;
                const estado = String(p.estado || '').toLowerCase();
                const puedeAsignar = estado !== 'completado' && estado !== 'cancelado';
                const puedeCompletar = estado !== 'completado' && estado !== 'cancelado';

                const btnAsignar = puedeAsignar
                    ? `<button type="button" class="table-action btn-asignar-pedido" data-id="${esc(id)}" style="width:auto; padding:4px 8px;">Asignar</button>`
                    : '';
                const btnMateriales = `<button type="button" class="table-action btn-materiales-pedido" data-id="${esc(id)}" style="width:auto; padding:4px 8px;">Materiales</button>`;
                const btnCompletar = puedeCompletar
                    ? `<button type="button" class="table-action btn-completar-pedido" data-id="${esc(id)}" style="width:auto; padding:4px 8px; background:var(--success); color:#fff; border:none;">Completar</button>`
                    : '';
                const btnMapa = (p.servicio_latitud != null && p.servicio_longitud != null)
                    ? `<button type="button" class="table-action btn-mapa-pedido" data-id="${esc(id)}" style="width:auto; padding:4px 8px;">Mapa</button>`
                    : '';
                const btnTicket = p.servicio_id
                    ? `<button type="button" class="table-action btn-ticket-pedido" data-id="${esc(id)}" style="width:auto; padding:4px 8px; background:#fef3c7; color:#92400e;">Ver ticket</button>`
                    : '';

                let equipoCell;
                if (equipoLabel) {
                    if (equipoLabel.startsWith('Reservado:')) {
                        equipoCell = `<span style="color:#b45309;">${esc(equipoLabel)}</span>`;
                    } else {
                        equipoCell = esc(equipoLabel);
                    }
                } else {
                    equipoCell = '<span class="text-muted">Sin equipo</span>';
                }

                return `
                <tr data-module-row="pedidos" data-id="${esc(id)}">
                    <td>${mark(nombreCliente, search)}</td>
                    <td>${cedula ? mark(cedula, search) : '—'}</td>
                    <td>${servicio ? mark(servicio, search) : '<span class="text-muted">Sin servicio</span>'}</td>
                    <td>${equipoCell}</td>
                    <td>${esc(formatTipo(p.tipo))}</td>
                    <td>${tecnico ? mark(tecnico, search) : '<span class="text-muted">Sin asignar</span>'}</td>
                    <td><span class="status ${getPriorityClass(p.prioridad)}">${esc(p.prioridad)}</span></td>
                    <td><span class="status ${getStatusClass(p.estado)}">${esc(formatEstado(p.estado))}</span></td>
                    <td>${p.fecha_programada ? esc(p.fecha_programada) : '—'}</td>
                    <td style="display:flex; gap:6px; flex-wrap:wrap;">${btnAsignar}${btnMateriales}${btnCompletar}${btnMapa}${btnTicket}</td>
                </tr>
                `;
            }).join('');

            tbody.querySelectorAll('tr[data-module-row="pedidos"]').forEach(tr => {
                tr.addEventListener('click', (e) => {
                    if (e.target.closest('button')) return;
                    const id = tr.getAttribute('data-id');
                    const prevId = window.selectedIds ? window.selectedIds['pedidos'] : null;
                    if (prevId) {
                        const prevRow = document.querySelector(`[data-module-row="pedidos"][data-id="${prevId}"]`);
                        if (prevRow) prevRow.classList.remove('selected-row');
                    }
                    if (window.selectedIds) window.selectedIds['pedidos'] = id;
                    tr.classList.add('selected-row');
                    document.querySelectorAll('.toolbar-btn[data-toolbar="pedidos"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete' || action === 'asignar' || action === 'materiales' || action === 'historial' || action === 'ver-en-mapa') {
                            btn.disabled = false;
                        }
                    });
                });
            });

            tbody.querySelectorAll('.btn-asignar-pedido').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.id;
                    if (id && window.PedidoController) window.PedidoController.abrirAsignarTecnico(id);
                });
            });
            tbody.querySelectorAll('.btn-materiales-pedido').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.id;
                    if (id && window.PedidoController) window.PedidoController.abrirMateriales(id);
                });
            });
            tbody.querySelectorAll('.btn-completar-pedido').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.id;
                    if (id && window.PedidoController) window.PedidoController.completarPedido(id);
                });
            });
            tbody.querySelectorAll('.btn-mapa-pedido').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.id;
                    if (id && window.PedidoController) window.PedidoController.verUbicacion(id);
                });
            });
            tbody.querySelectorAll('.btn-ticket-pedido').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.id;
                    if (id && window.PedidoController) window.PedidoController.verTicketRelacionado(id);
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('pedidos-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-pedidos-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-pedidos-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-pedidos-prev')?.addEventListener('click', () => {
                if (window.PedidoController) window.PedidoController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-pedidos-next')?.addEventListener('click', () => {
                if (window.PedidoController) window.PedidoController.renderPage(Math.min(totalPages, page + 1));
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('pedidos-counter');
            if (!span) return;
            if (total === 0) { span.textContent = ''; return; }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        populateSelects: function (clienteId = null, selectedServicioId = null, selectedTecnicoId = null) {
            const selCli = document.getElementById('pedido-cliente');
            if (selCli) {
                const clientes = window.ClienteModel ? window.ClienteModel.cache : [];
                let html = '<option value="">Selecciona un cliente</option>';
                html += clientes.map(c => {
                    const id = c.id || c.id_cliente;
                    const nombre = `${c.nombre || c.nombres || ''} ${c.apellido || c.apellidos || ''}`.trim() || 'Cliente';
                    const ci = c.cedula ? ` (C.I. ${c.cedula})` : '';
                    return `<option value="${esc(id)}">${esc(nombre)}${esc(ci)}</option>`;
                }).join('');
                selCli.innerHTML = html;
                selCli.value = clienteId ? String(clienteId) : '';
            }

            const selSrv = document.getElementById('pedido-servicio');
            if (selSrv) {
                const servicios = window.ServicioModel ? window.ServicioModel.cache : [];
                const filtroCliente = clienteId || (selCli ? selCli.value : '');
                const filtered = filtroCliente
                    ? servicios.filter(s => String(s.cliente_id || s.id_cliente) === String(filtroCliente))
                    : [];
                let html = '<option value="">Sin servicio</option>';
                html += filtered.map(s => {
                    const id = s.id || s.id_servicio;
                    const alias = s.alias || `${s.nombres || ''} ${s.apellidos || ''}`.trim() || `Servicio #${id}`;
                    const dir = s.direccion_texto ? ` — ${s.direccion_texto}` : '';
                    const plan = s.plan_nombre ? ` (${s.plan_nombre})` : '';
                    return `<option value="${esc(id)}">${esc(alias)}${esc(plan)}${esc(dir)}</option>`;
                }).join('');
                if (selectedServicioId && !filtered.some(s => String(s.id || s.id_servicio) === String(selectedServicioId))) {
                    const s = servicios.find(x => String(x.id || x.id_servicio) === String(selectedServicioId));
                    if (s) {
                        const alias = s.alias || `Servicio #${s.id}`;
                        html += `<option value="${esc(s.id || s.id_servicio)}">${esc(alias)} (fuera de filtro)</option>`;
                    }
                }
                selSrv.innerHTML = html;
                selSrv.value = selectedServicioId ? String(selectedServicioId) : '';
            }

            const selTec = document.getElementById('asignar-tecnico-select');
            if (selTec) {
                const usuarios = window.UsuarioModel ? window.UsuarioModel.cache : [];
                const activos = usuarios.filter(u => String(u.estado || '').toLowerCase() === 'activo');
                let html = '<option value="">Selecciona un técnico</option>';
                html += activos.map(u => {
                    const id = u.id;
                    const username = u.username || `Usuario #${id}`;
                    const roles = u.roles_nombres ? ` (${u.roles_nombres})` : '';
                    return `<option value="${esc(id)}">${esc(username)}${esc(roles)}</option>`;
                }).join('');
                selTec.innerHTML = html;
                selTec.value = selectedTecnicoId ? String(selectedTecnicoId) : '';
            }
        },

        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('pedido-modal');
            if (!m) return;
            m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                ['pedido-id', 'pedido-descripcion', 'pedido-fecha-programada'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                const tipo = document.getElementById('pedido-tipo');
                if (tipo) tipo.value = 'instalacion';
                const pri = document.getElementById('pedido-prioridad');
                if (pri) pri.value = 'Media';
                const title = document.getElementById('pedido-modal-title');
                if (title) title.textContent = 'Nuevo pedido';
                const est = document.getElementById('pedido-estado');
                if (est) {
                    est.value = 'pendiente';
                    est.disabled = true;
                }
                ['pedido-cliente', 'pedido-servicio'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.disabled = false;
                });
                this.populateSelects(null, null, null);
                if (window.PedidoController) window.PedidoController._dirty = false;
                const first = document.getElementById('pedido-cliente');
                if (first) setTimeout(() => first.focus(), 50);
            }
        },

        fillForm: function (p) {
            const title = document.getElementById('pedido-modal-title');
            if (title) title.textContent = 'Editar pedido';
            document.getElementById('pedido-id').value = p.id || '';
            document.getElementById('pedido-descripcion').value = p.descripcion || '';
            document.getElementById('pedido-fecha-programada').value = p.fecha_programada || '';
            const tipo = document.getElementById('pedido-tipo');
            if (tipo) tipo.value = p.tipo || 'instalacion';
            const pri = document.getElementById('pedido-prioridad');
            if (pri) pri.value = p.prioridad || 'Media';
            const est = document.getElementById('pedido-estado');
            if (est) {
                est.disabled = false;
                est.value = p.estado || 'pendiente';
            }
            ['pedido-cliente', 'pedido-servicio'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = false;
            });
            this.populateSelects(p.cliente_id, p.servicio_id, null);
            if (window.PedidoController) window.PedidoController._dirty = false;
        },

        renderAsignarTecnicoModal: function (pedido) {
            const m = document.getElementById('asignar-tecnico-modal');
            const title = document.getElementById('asignar-tecnico-title');
            const idInput = document.getElementById('asignar-tecnico-id-pedido');
            if (!m || !title) return;

            const nombreCliente = `${pedido.nombres || ''} ${pedido.apellidos || ''}`.trim() || 'Sin cliente';
            title.textContent = `Asignar técnico — Pedido #${pedido.id}`;
            if (idInput) idInput.value = pedido.id || '';

            const info = document.getElementById('asignar-tecnico-info');
            if (info) {
                info.innerHTML = `
                    <div style="font-size:0.9rem; color:#475569; line-height:1.6;">
                        <div><strong>Cliente:</strong> ${esc(nombreCliente)}</div>
                        <div><strong>Tipo:</strong> ${esc(formatTipo(pedido.tipo))}</div>
                        <div><strong>Técnico actual:</strong> ${pedido.tecnico_username ? esc(pedido.tecnico_username) : '<span style="color:#94a3b8;">Sin asignar</span>'}</div>
                    </div>
                `;
            }

            this.populateSelects(pedido.cliente_id, null, null);
            const selTec = document.getElementById('asignar-tecnico-select');
            if (selTec) selTec.value = pedido.tecnico_asignado_id ? String(pedido.tecnico_asignado_id) : '';

            m.style.display = 'flex';
            if (selTec) setTimeout(() => selTec.focus(), 50);
        },

        renderMaterialesModal: function (pedido, materiales) {
            const m = document.getElementById('materiales-modal');
            const title = document.getElementById('materiales-title');
            const idInput = document.getElementById('materiales-id-pedido');
            const tbody = document.getElementById('materiales-list');
            if (!m || !tbody) return;

            this._materialesCache = materiales || [];
            this._currentPedidoId = pedido.id;

            const nombreCliente = `${pedido.nombres || ''} ${pedido.apellidos || ''}`.trim() || 'Sin cliente';
            if (title) title.textContent = `Materiales — Pedido #${pedido.id}`;
            if (idInput) idInput.value = pedido.id || '';

            const estado = String(pedido.estado || '').toLowerCase();
            const esInstalacion = String(pedido.tipo || '').toLowerCase() === 'instalacion';
            const completado = estado === 'completado';
            const cancelado = estado === 'cancelado';

            const equipoReservadoLabel = pedido.equipo_reservado_tipo
                ? `${pedido.equipo_reservado_tipo} ${pedido.equipo_reservado_mac || ''}`.trim()
                : '';
            const equipoAsignadoLabel = pedido.equipo_tipo
                ? `${pedido.equipo_tipo} ${pedido.equipo_mac || ''}${pedido.equipo_estado ? ` (${pedido.equipo_estado})` : ''}`.trim()
                : '';

            const info = document.getElementById('materiales-info');
            if (info) {
                let card = '';
                if (completado) {
                    card = equipoAsignadoLabel
                        ? `<div style="margin-top:8px; padding:10px 12px; background:#ecfdf5; border:1px solid #86efac; border-radius:10px; font-size:0.9rem;">
                               <strong>Equipo asignado:</strong> ${esc(equipoAsignadoLabel)}
                           </div>`
                        : `<div style="margin-top:8px; padding:10px 12px; background:#f8fafc; border:1px solid var(--border); border-radius:10px; font-size:0.9rem; color:#64748b;">
                               Pedido completado sin equipo asignado.
                           </div>`;
                } else if (cancelado) {
                    card = `<div style="margin-top:8px; padding:10px 12px; background:#f8fafc; border:1px solid var(--border); border-radius:10px; font-size:0.9rem; color:#64748b;">
                               Pedido cancelado. No se pueden modificar materiales ni asignar equipo.
                           </div>`;
                } else if (equipoReservadoLabel) {
                    card = `<div style="margin-top:8px; padding:10px 12px; background:#fef3c7; border:1px solid #fde68a; border-radius:10px; font-size:0.9rem;">
                               <strong>Equipo reservado:</strong> ${esc(equipoReservadoLabel)}
                               <div style="font-size:0.8rem; color:#92400e; margin-top:4px;">Se asignará al servicio cuando el pedido se marque como completado.</div>
                           </div>`;
                } else if (equipoAsignadoLabel) {
                    card = `<div style="margin-top:8px; padding:10px 12px; background:#ecfdf5; border:1px solid #86efac; border-radius:10px; font-size:0.9rem;">
                               <strong>Equipo actual del servicio:</strong> ${esc(equipoAsignadoLabel)}
                           </div>`;
                } else {
                    card = `<div style="margin-top:8px; padding:10px 12px; background:#f8fafc; border:1px solid var(--border); border-radius:10px; font-size:0.9rem; color:#64748b;">
                               Sin equipo reservado ni asignado.
                           </div>`;
                }

                const puedeReservar = !completado && !cancelado && (esInstalacion || !equipoAsignadoLabel);
                const btnReservar = puedeReservar
                    ? `<div style="margin-top:8px;"><button type="button" data-action="open-reservar-equipo" style="width:auto; padding:8px 12px;">${equipoReservadoLabel ? 'Cambiar equipo reservado' : 'Reservar equipo'}</button></div>`
                    : '';

                info.innerHTML = `<div style="font-size:0.9rem; color:#475569;"><strong>Cliente:</strong> ${esc(nombreCliente)} — <strong>Tipo:</strong> ${esc(formatTipo(pedido.tipo))}</div>${card}${btnReservar}`;

                const btn = info.querySelector('[data-action="open-reservar-equipo"]');
                if (btn) {
                    btn.addEventListener('click', () => {
                        if (window.PedidoController) window.PedidoController.abrirReservarEquipo(pedido.id);
                    });
                }
            }

            if (materiales.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:16px;color:#64748b;">Sin materiales registrados.</td></tr>';
            } else {
                tbody.innerHTML = materiales.map(mat => `
                    <tr data-material-id="${esc(mat.id)}">
                        <td>${esc(mat.descripcion)}</td>
                        <td>${esc(mat.cantidad)}</td>
                        <td>${esc(mat.unidad)}</td>
                        <td>${esc(labelEquipoMaterial(mat))}</td>
                        <td>${esc(mat.notas || '—')}</td>
                        <td>
                            <button type="button" class="btn-eliminar-material" data-id="${esc(mat.id)}" style="width:auto;padding:4px 8px;background:var(--danger);color:#fff;border:none;">Eliminar</button>
                        </td>
                    </tr>
                `).join('');

                tbody.querySelectorAll('.btn-eliminar-material').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const id = btn.dataset.id;
                        if (id && window.PedidoController) window.PedidoController.eliminarMaterial(pedido.id, id);
                    });
                });
            }

            ['material-descripcion', 'material-cantidad', 'material-unidad', 'material-equipo', 'material-notas'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });

            // Select de equipo: solo stock, excluye reservados en otros pedidos.
            const selEq = document.getElementById('material-equipo');
            if (selEq) {
                const todos = window.EquipoModel ? window.EquipoModel.cache : [];
                const disponibles = equiposDisponiblesParaPedido(todos, pedido.id);
                if (disponibles.length === 0) {
                    selEq.innerHTML = '<option value="">No hay equipos en stock disponibles. Créalos en el módulo Equipos.</option>';
                } else {
                    let html = '<option value="">Sin equipo</option>';
                    html += disponibles.map(eq => {
                        return `<option value="${esc(eq.id)}">${esc(labelEquipoOption(eq))}</option>`;
                    }).join('');
                    selEq.innerHTML = html;
                }
            }

            m.style.display = 'flex';
            const first = document.getElementById('material-descripcion');
            if (first) setTimeout(() => first.focus(), 50);
        },

        renderReservarEquipoModal: function (pedido) {
            const m = document.getElementById('asignar-equipo-modal');
            const title = document.getElementById('asignar-equipo-title');
            const idInput = document.getElementById('asignar-equipo-id-pedido');
            const select = document.getElementById('asignar-equipo-select');
            if (!m || !select) return;

            const nombreCliente = `${pedido.nombres || ''} ${pedido.apellidos || ''}`.trim() || 'Sin cliente';
            if (title) title.textContent = `Reservar equipo — Pedido #${pedido.id}`;
            if (idInput) idInput.value = pedido.id || '';

            const info = document.getElementById('asignar-equipo-info');
            if (info) {
                const reservadoLabel = pedido.equipo_reservado_tipo
                    ? `${pedido.equipo_reservado_tipo} ${pedido.equipo_reservado_mac || ''}`.trim()
                    : '';
                info.innerHTML = `
                    <div style="font-size:0.9rem; color:#475569; line-height:1.6;">
                        <div><strong>Cliente:</strong> ${esc(nombreCliente)}</div>
                        <div><strong>Tipo:</strong> ${esc(formatTipo(pedido.tipo))}</div>
                        <div><strong>Equipo reservado:</strong> ${reservadoLabel ? esc(reservadoLabel) : '<span style="color:#94a3b8;">Sin reservar</span>'}</div>
                        <div style="font-size:0.8rem; color:#64748b; margin-top:4px;">El equipo se asignará al servicio cuando el pedido se complete.</div>
                    </div>
                `;
            }

            this._renderEquiposStockSelect();

            m.style.display = 'flex';
            if (select) setTimeout(() => select.focus(), 50);
        },

        _renderEquiposStockSelect: function () {
            const select = document.getElementById('asignar-equipo-select');
            if (!select) return;
            const equipos = (window.PedidoModel && window.PedidoModel.equiposStockCache) || [];
            if (equipos.length === 0) {
                select.innerHTML = '<option value="">No hay equipos en stock disponibles. Usa "+ Crear equipo nuevo" o libera otro pedido.</option>';
                return;
            }
            let html = '<option value="">Selecciona un equipo en stock</option>';
            html += equipos.map(eq => {
                const base = labelEquipoOption(eq);
                const mac = eq.direccion_mac ? ` (${eq.direccion_mac})` : '';
                const nap = eq.nap_codigo ? ` — NAP ${eq.nap_codigo}` : '';
                return `<option value="${esc(eq.id)}">${esc(base + mac + nap)}</option>`;
            }).join('');
            select.innerHTML = html;
        },

        renderHistorialModal: function (pedido, asignaciones) {
            const m = document.getElementById('historial-asignaciones-modal');
            const title = document.getElementById('historial-title');
            const tbody = document.getElementById('historial-list');
            if (!m || !tbody) return;

            const nombreCliente = `${pedido.nombres || ''} ${pedido.apellidos || ''}`.trim() || 'Sin cliente';
            if (title) title.textContent = `Historial de asignaciones — Pedido #${pedido.id}`;

            const info = document.getElementById('historial-info');
            if (info) {
                info.innerHTML = `<div style="font-size:0.9rem; color:#475569;"><strong>Cliente:</strong> ${esc(nombreCliente)} — <strong>Tipo:</strong> ${esc(formatTipo(pedido.tipo))}</div>`;
            }

            if (asignaciones.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:16px;color:#64748b;">Sin asignaciones registradas.</td></tr>';
            } else {
                tbody.innerHTML = asignaciones.map(a => {
                    const tecnicoLabel = a.tecnico_username
                        ? a.tecnico_username
                        : (a.tecnico_id ? `Técnico #${a.tecnico_id}` : '—');
                    const asignadorLabel = a.asignador_username
                        ? a.asignador_username
                        : (a.usuario_asignador_id ? `Usuario #${a.usuario_asignador_id}` : '—');
                    return `
                    <tr>
                        <td>${esc(tecnicoLabel)}</td>
                        <td>${esc(asignadorLabel)}</td>
                        <td><span class="status ${a.estado === 'activa' ? 'status-warning' : (a.estado === 'completada' ? 'status-ok' : 'status-alert')}">${esc(a.estado)}</span></td>
                        <td>${esc(a.asignado_en || '—')}</td>
                        <td>${esc(a.cerrado_en || '—')}</td>
                        <td>${esc(a.motivo_rechazo || '—')}</td>
                    </tr>
                    `;
                }).join('');
            }

            m.style.display = 'flex';
        },

        renderUbicacionModal: function (pedido) {
            const m = document.getElementById('ubicacion-modal');
            const title = document.getElementById('ubicacion-title');
            const content = document.getElementById('ubicacion-content');
            if (!m || !content) return;

            const nombreCliente = `${pedido.nombres || ''} ${pedido.apellidos || ''}`.trim() || 'Sin cliente';
            const servicio = labelServicioForPedido(pedido);
            const dir = pedido.servicio_direccion || '';
            const lat = pedido.servicio_latitud;
            const lon = pedido.servicio_longitud;

            if (title) title.textContent = `Ubicación — Pedido #${pedido.id}`;

            if (lat == null || lon == null) {
                content.innerHTML = `
                    <div style="padding:16px; background:#fef3c7; border:1px solid #fde68a; border-radius:10px; color:#92400e;">
                        <p style="margin:0 0 8px;"><strong>Este servicio no tiene coordenadas registradas.</strong></p>
                        <p style="margin:0;">Actualiza la dirección del servicio en el módulo Servicios para verlo en el mapa.</p>
                    </div>
                    <div style="margin-top:16px; font-size:0.9rem; color:#475569;">
                        <div><strong>Cliente:</strong> ${esc(nombreCliente)}</div>
                        <div><strong>Servicio:</strong> ${esc(servicio || '—')}</div>
                        <div><strong>Dirección:</strong> ${esc(dir || '—')}</div>
                    </div>
                `;
            } else {
                const latN = Number(lat);
                const lonN = Number(lon);
                const gmapsUrl = `https://www.google.com/maps?q=${latN},${lonN}`;
                const markerId = pedido.servicio_id ? 'servicio-' + pedido.servicio_id : null;
                content.innerHTML = `
                    <div style="font-size:0.9rem; color:#475569; line-height:1.6; margin-bottom:16px;">
                        <div><strong>Cliente:</strong> ${esc(nombreCliente)}</div>
                        <div><strong>Servicio:</strong> ${esc(servicio || '—')}</div>
                        <div><strong>Dirección:</strong> ${esc(dir || '—')}</div>
                        <div><strong>Coordenadas:</strong> ${latN.toFixed(6)}, ${lonN.toFixed(6)}</div>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <a href="${gmapsUrl}" target="_blank" rel="noopener" style="display:inline-block; padding:10px 14px; background:var(--accent); color:#fff; border-radius:10px; text-decoration:none; font-weight:600;">Abrir en Google Maps</a>
                        <button type="button" data-action="ver-mapa-interno" style="width:auto; padding:10px 14px;">Ver en mapa interno</button>
                    </div>
                `;
                const btnMapa = content.querySelector('[data-action="ver-mapa-interno"]');
                if (btnMapa) {
                    btnMapa.addEventListener('click', () => {
                        document.getElementById('ubicacion-modal').style.display = 'none';
                        if (window.MapaController && window.MapaController.openAt) {
                            window.MapaController.openAt(latN, lonN, `Pedido #${pedido.id} — ${nombreCliente}`, markerId);
                        } else {
                            window.mapaFocus = { lat: latN, lon: lonN, label: `Pedido #${pedido.id} — ${nombreCliente}`, markerId: markerId };
                            window.Router.showPage('mapa');
                        }
                    });
                }
            }

            m.style.display = 'flex';
        },

        _onClienteChange: function () {
            const cli = document.getElementById('pedido-cliente');
            const clienteId = cli ? cli.value : '';
            const selSrv = document.getElementById('pedido-servicio');
            const prevSrv = selSrv ? selSrv.value : null;
            this.populateSelects(clienteId || null, prevSrv || null, null);
        },

        _handleEscape: function () {
            const modals = ['pedido-modal', 'asignar-tecnico-modal', 'materiales-modal', 'asignar-equipo-modal', 'historial-asignaciones-modal', 'ubicacion-modal'];
            for (let i = 0; i < modals.length; i++) {
                const m = document.getElementById(modals[i]);
                if (m && m.style.display === 'flex') {
                    if (modals[i] === 'pedido-modal') {
                        this._requestClose();
                    } else {
                        m.style.display = 'none';
                    }
                    return;
                }
            }
        },

        _requestClose: function () {
            if (!window.PedidoController || !window.PedidoController._dirty) {
                this.toggleForm(true);
                return;
            }
            window.Modal.confirm('¿Descartar cambios sin guardar?', { danger: true, confirmText: 'Descartar' })
                .then(ok => {
                    if (ok) {
                        this.toggleForm(true);
                        if (window.PedidoController) window.PedidoController._dirty = false;
                    }
                });
        },

        _attachListeners: function () {
            if (window.selectedIds) window.selectedIds['pedidos'] = null;
            document.querySelectorAll('.toolbar-btn[data-toolbar="pedidos"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (['edit', 'delete', 'asignar', 'materiales', 'historial', 'ver-en-mapa'].includes(action)) {
                    btn.disabled = true;
                }
            });

            document.querySelectorAll('.toolbar-btn[data-toolbar="pedidos"]').forEach(btn => {
                btn.removeAttribute('onclick');
                const action = btn.dataset.action || '';
                if (action === 'new') {
                    btn.addEventListener('click', () => this.toggleForm());
                } else if (action === 'edit') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['pedidos'] : null;
                        if (id) window.PedidoController.edit(id);
                    });
                } else if (action === 'delete') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['pedidos'] : null;
                        if (id) window.PedidoController.confirmDelete(id);
                    });
                } else if (action === 'asignar') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['pedidos'] : null;
                        if (id) window.PedidoController.abrirAsignarTecnico(id);
                    });
                } else if (action === 'materiales') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['pedidos'] : null;
                        if (id) window.PedidoController.abrirMateriales(id);
                    });
                } else if (action === 'historial') {
                    btn.addEventListener('click', () => {
                        const id = window.selectedIds ? window.selectedIds['pedidos'] : null;
                        if (id) window.PedidoController.abrirHistorial(id);
                    });
                }
            });

            const search = document.getElementById('pedidos-search');
            if (search) {
                search.removeAttribute('oninput');
                search.addEventListener('input', () => {
                    clearTimeout(window.PedidoController._searchTimer);
                    window.PedidoController._searchTimer = setTimeout(() => window.PedidoController.renderPage(1), 250);
                });
            }

            document.querySelectorAll('#pedido-modal [data-action="save-pedido"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.PedidoController.save());
            });
            document.querySelectorAll('#pedido-modal [data-action="cancel-pedido"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => this._requestClose());
            });
            const selCli = document.getElementById('pedido-cliente');
            if (selCli) {
                selCli.removeAttribute('onchange');
                selCli.addEventListener('change', () => this._onClienteChange());
            }
            ['pedido-servicio', 'pedido-tipo', 'pedido-prioridad', 'pedido-estado', 'pedido-fecha-programada', 'pedido-descripcion'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', () => { window.PedidoController._dirty = true; });
                    el.addEventListener('change', () => { window.PedidoController._dirty = true; });
                }
            });

            const formPedido = document.getElementById('pedido-form');
            if (formPedido) {
                formPedido.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                        e.preventDefault();
                        window.PedidoController.save();
                    }
                });
            }

            if (this._boundEscape) {
                document.removeEventListener('keydown', this._boundEscape);
            }
            this._boundEscape = (e) => {
                if (e.key === 'Escape') {
                    this._handleEscape();
                }
            };
            document.addEventListener('keydown', this._boundEscape);

            document.querySelectorAll('#asignar-tecnico-modal [data-action="confirm-asignar"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.PedidoController.confirmarAsignacion());
            });
            document.querySelectorAll('#asignar-tecnico-modal [data-action="cancel-asignar"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => {
                    const m = document.getElementById('asignar-tecnico-modal');
                    if (m) m.style.display = 'none';
                });
            });

            document.querySelectorAll('#materiales-modal [data-action="add-material"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.PedidoController.agregarMaterial());
            });
            document.querySelectorAll('#materiales-modal [data-action="close-materiales"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => {
                    const m = document.getElementById('materiales-modal');
                    if (m) m.style.display = 'none';
                });
            });

            document.querySelectorAll('#asignar-equipo-modal [data-action="confirm-asignar-equipo"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.PedidoController.confirmarReservarEquipo());
            });
            document.querySelectorAll('#asignar-equipo-modal [data-action="cancel-asignar-equipo"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => {
                    const m = document.getElementById('asignar-equipo-modal');
                    if (m) m.style.display = 'none';
                });
            });
            document.querySelectorAll('#asignar-equipo-modal [data-action="reload-equipos"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => {
                    if (window.PedidoController) window.PedidoController.recargarEquiposStock();
                });
            });
            document.querySelectorAll('#asignar-equipo-modal [data-action="new-equipo"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => {
                    if (window.PedidoController) window.PedidoController.abrirCrearEquipo();
                });
            });

            document.querySelectorAll('#historial-asignaciones-modal [data-action="close-historial"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => {
                    const m = document.getElementById('historial-asignaciones-modal');
                    if (m) m.style.display = 'none';
                });
            });

            document.querySelectorAll('#ubicacion-modal [data-action="close-ubicacion"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => {
                    const m = document.getElementById('ubicacion-modal');
                    if (m) m.style.display = 'none';
                });
            });

            const toolbar = document.querySelector('.toolbar[data-toolbar="pedidos"]');
            if (toolbar && !toolbar.querySelector('[data-action="ver-en-mapa"]')) {
                const btnMap = document.createElement('button');
                btnMap.type = 'button';
                btnMap.className = 'toolbar-btn';
                btnMap.dataset.toolbar = 'pedidos';
                btnMap.dataset.action = 'ver-en-mapa';
                btnMap.textContent = 'Ver en mapa';
                btnMap.disabled = true;
                btnMap.addEventListener('click', () => {
                    const id = window.selectedIds?.pedidos;
                    if (!id) return;
                    const p = window.PedidoModel && window.PedidoModel.cache
                        ? window.PedidoModel.cache.find(x => String(x.id) === String(id))
                        : null;
                    if (!p) return;
                    if (p.servicio_latitud == null || p.servicio_longitud == null) {
                        window.Toast.warning('El servicio de este pedido no tiene coordenadas.');
                        return;
                    }
                    const lat = Number(p.servicio_latitud);
                    const lon = Number(p.servicio_longitud);
                    if (!Number.isFinite(lat) || !Number.isFinite(lon) || (lat === 0 && lon === 0)) {
                        window.Toast.warning('El servicio de este pedido no tiene coordenadas válidas.');
                        return;
                    }
                    if (window.MapaController && window.MapaController.openAt) {
                        const markerId = p.servicio_id ? 'servicio-' + p.servicio_id : null;
                        window.MapaController.openAt(lat, lon, 'Pedido #' + p.id, markerId);
                    } else {
                        window.mapaFocus = { lat: lat, lon: lon, label: 'Pedido #' + p.id, markerId: (p.servicio_id ? 'servicio-' + p.servicio_id : null) };
                        if (window.Router) window.Router.showPage('mapa');
                    }
                });
                toolbar.appendChild(btnMap);
            }
        }
    };
})();