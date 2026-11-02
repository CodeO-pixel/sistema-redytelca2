// js/views/ClienteView.js
(function () {
    'use strict';

    function escapeHtml(unsafe) {
        return String(unsafe ?? '')
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function buildDireccionCliente(c) {
        // Direccion del cliente (tabla `direccion`, JOIN). Si no hay
        // ningun dato, muestra "No registrada".
        const parts = [];
        if (c.direccion_cliente) parts.push(String(c.direccion_cliente).trim());
        if (c.cliente_carrera) parts.push('Carrera ' + String(c.cliente_carrera).trim());
        if (c.cliente_avenida) parts.push('Avenida ' + String(c.cliente_avenida).trim());
        return parts.filter(Boolean).join(', ');
    }

    window.ClienteView = {
        renderTable: function (clientes) {
            const tbody = document.getElementById('clientes-list');
            if (!tbody) return;

            tbody.innerHTML = clientes.length ? clientes.map(c => {
                const nombre = c.nombre || c.nombres || '';
                const apellido = c.apellido || c.apellidos || '';
                const idCliente = c.id || c.id_cliente;

                const dirCliente = buildDireccionCliente(c);
                const dirDisplay = dirCliente
                    ? escapeHtml(dirCliente)
                    : '<span style="color:#94a3b8;">No registrada</span>';

                const coordsTitle = (c.cliente_latitud != null && c.cliente_longitud != null)
                    ? `Lat ${c.cliente_latitud}, Lon ${c.cliente_longitud}`
                    : '';

                return `
                <tr data-module-row="clientes" data-id="${escapeHtml(idCliente)}" data-client-id="${escapeHtml(idCliente)}">
                    <td><strong>${escapeHtml(`${nombre} ${apellido}`.trim())}</strong></td>
                    <td>${escapeHtml(c.cedula)}</td>
                    <td>${escapeHtml(c.num_telefono || c.telefono || '')}</td>
                    <td>${escapeHtml(c.correo)}</td>
                    <td title="${escapeHtml(coordsTitle)}">${dirDisplay}</td>
                    <td>
                        <span class="status status-ok">${Number(c.total_servicios || 0)} servicio(s)</span>
                        <button type="button" class="btn-ficha" data-ficha-id="${escapeHtml(idCliente)}" style="width:auto; padding:4px 8px; margin-left:6px; background:#eef4ff; color:var(--accent); font-size:0.8rem;">Ver ficha</button>
                    </td>
                </tr>
            `}).join('') : `<tr><td colspan="6">No hay clientes.</td></tr>`;

            // Click en la fila: solo selecciona. NO abre la ficha.
            tbody.querySelectorAll('tr[data-client-id]').forEach(tr => {
                tr.addEventListener('click', (e) => {
                    if (e.target.closest('.btn-ficha')) return;
                    const id = tr.getAttribute('data-client-id');

                    const prevId = window.selectedIds ? window.selectedIds['clientes'] : null;
                    if (prevId) {
                        const prevRow = document.querySelector(`[data-module-row="clientes"][data-id="${prevId}"]`);
                        if (prevRow) prevRow.classList.remove('selected-row');
                    }
                    if (window.selectedIds) window.selectedIds['clientes'] = id;
                    tr.classList.add('selected-row');

                    const hasSel = !!id;
                    document.querySelectorAll(`.toolbar-btn[data-module="clientes"]`).forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit') btn.disabled = !hasSel;
                        else if (action === 'delete') btn.disabled = !hasSel;
                        else if (action === 'ver-en-mapa') btn.disabled = !hasSel;
                    });
                });
            });

            // Botón "Ver ficha": abre la ficha. NO selecciona (stopPropagation).
            tbody.querySelectorAll('.btn-ficha').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.fichaId;
                    if (window.ClienteController) window.ClienteController.showProfile(id);
                });
            });
        },
        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('clientes-pagination');
            if (!pag) return;

            pag.innerHTML = `
                <div class="pagination-controls">
                    <button id="btn-clientes-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                    <span>Página ${page} / ${totalPages}</span>
                    <button id="btn-clientes-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
                </div>
            `;

            document.getElementById('btn-clientes-prev')?.addEventListener('click', () => {
                if (window.ClienteController) window.ClienteController.load(window.currentClienteSearch || '', Math.max(1, page - 1));
            });
            document.getElementById('btn-clientes-next')?.addEventListener('click', () => {
                if (window.ClienteController) window.ClienteController.load(window.currentClienteSearch || '', Math.min(totalPages, page + 1));
            });
        },
        renderProfile: function (cliente, servicios) {
            const m = document.getElementById('client-detail-modal');
            const cont = document.getElementById('client-detail-content');
            if (!m || !cont || !cliente) return;

            const idCliente = cliente.id || cliente.id_cliente;
            const nombre = cliente.nombre || cliente.nombres || '';
            const apellido = cliente.apellido || cliente.apellidos || '';

            // Direccion del cliente (tabla `direccion`). Puede ser null.
            const dirCliente = buildDireccionCliente(cliente);
            const coordsCliente = (cliente.cliente_latitud != null && cliente.cliente_longitud != null)
                ? `${Number(cliente.cliente_latitud).toFixed(6)}, ${Number(cliente.cliente_longitud).toFixed(6)}`
                : '—';

            cont.innerHTML = `
                <div class="detail-grid">
                    <div class="detail-card"><span class="detail-label">Nombre</span><div class="detail-value">${escapeHtml(`${nombre} ${apellido}`.trim())}</div></div>
                    <div class="detail-card"><span class="detail-label">Cédula</span><div class="detail-value">${escapeHtml(cliente.cedula)}</div></div>
                    <div class="detail-card"><span class="detail-label">Teléfono</span><div class="detail-value">${escapeHtml(cliente.num_telefono || cliente.telefono || '')}</div></div>
                    <div class="detail-card"><span class="detail-label">Correo</span><div class="detail-value">${escapeHtml(cliente.correo)}</div></div>
                    <div class="detail-card" style="grid-column: 1 / -1;">
                        <span class="detail-label">Dirección del cliente</span>
                        <div class="detail-value">${dirCliente ? escapeHtml(dirCliente) : '<span style="color:#94a3b8;">No registrada</span>'}</div>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Coordenadas del cliente</span>
                        <div class="detail-value">${escapeHtml(coordsCliente)}</div>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Servicios</span>
                        <div class="detail-value">${Number(cliente.total_servicios || 0)}</div>
                    </div>
                </div>
                <div style="margin-top:20px;">
                    <div class="card-title-row">
                        <h3>Servicios del cliente</h3>
                        <button type="button" class="table-action" id="btn-add-servicio-${idCliente}">+ Añadir servicio</button>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Servicio</th><th>Plan</th><th>NAP</th><th>Estado</th><th>Acciones</th></tr></thead>
                            <tbody id="client-servicios-list"></tbody>
                        </table>
                    </div>
                </div>
            `;

            document.getElementById(`btn-add-servicio-${idCliente}`)?.addEventListener('click', () => {
                if (window.ServicioController) window.ServicioController.openNewForClient(idCliente);
            });

            const tbody = document.getElementById('client-servicios-list');
            if (tbody) {
                tbody.innerHTML = servicios.length ? servicios.map(s => {
                    const statusClass = (s.estado_comercial === 'activo') ? 'status-ok' : (s.estado_comercial === 'retirado' ? 'status-alert' : 'status-warning');
                    const idServicio = s.id || s.id_servicio;

                    // Fix: usar nombres del JOIN del backend (vienen en s.nombres/s.apellidos).
                    const planObj = window.PlanModel ? window.PlanModel.cache.find(p => String(p.id || p.id_plan) === String(s.plan_id || s.id_plan)) : null;
                    const nombrePlan = s.plan_nombre || (planObj ? planObj.nombre : 'Sin plan');

                    return `
                    <tr>
                        <td><strong>${escapeHtml(s.alias || 'Servicio')}</strong><br><small>${escapeHtml(s.direccion_texto || s.direccion || '')}</small></td>
                        <td>${escapeHtml(nombrePlan)}</td>
                        <td>${escapeHtml(s.nap_codigo || '—')}</td>
                        <td><span class="status ${statusClass}">${escapeHtml(s.estado_comercial)}</span></td>
                        <td>
                            <button class="btn-edit-srv" data-id="${idServicio}" style="width:auto;padding:4px 8px;">Editar</button>
                            <button class="btn-del-srv" data-id="${idServicio}" style="width:auto;padding:4px 8px;background:var(--danger);color:#fff;border:none;">Eliminar</button>
                        </td>
                    </tr>
                `}).join('') : '<tr><td colspan="5">Sin servicios.</td></tr>';

                tbody.querySelectorAll('.btn-edit-srv').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (window.ServicioController) window.ServicioController.edit(btn.dataset.id);
                    });
                });

                tbody.querySelectorAll('.btn-del-srv').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (window.ServicioController) window.ServicioController.confirmDelete(btn.dataset.id);
                    });
                });
            }

            m.style.display = 'flex';
        },
        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('client-modal');
            if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                [
                    'client-id', 'client-nombres', 'client-apellidos', 'client-cedula',
                    'client-telefono', 'client-correo',
                    'client-calle', 'client-carrera', 'client-avenida',
                    'client-latitud', 'client-longitud'
                ].forEach(id => {
                    const el = document.getElementById(id); if (el) el.value = '';
                });
            }
        },
        fillForm: function (cliente) {
            document.getElementById('client-id').value = cliente.id || cliente.id_cliente || '';
            document.getElementById('client-nombres').value = cliente.nombre || cliente.nombres || '';
            document.getElementById('client-apellidos').value = cliente.apellido || cliente.apellidos || '';
            document.getElementById('client-cedula').value = cliente.cedula || '';
            document.getElementById('client-telefono').value = cliente.num_telefono || cliente.telefono || '';
            document.getElementById('client-correo').value = cliente.correo || '';
            // Direccion del cliente (tabla `direccion`).
            document.getElementById('client-calle').value = cliente.direccion_cliente || '';
            document.getElementById('client-carrera').value = cliente.cliente_carrera || '';
            document.getElementById('client-avenida').value = cliente.cliente_avenida || '';
            document.getElementById('client-latitud').value = cliente.cliente_latitud != null ? cliente.cliente_latitud : '';
            document.getElementById('client-longitud').value = cliente.cliente_longitud != null ? cliente.cliente_longitud : '';
        },
        _attachListeners: function () {
            // Estado inicial sin selección, edit/delete disabled.
            if (window.selectedIds) window.selectedIds['clientes'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="clientes"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });

            const btnNew = document.querySelector('.toolbar[data-toolbar="clientes"] [data-action="new"]');
            if (btnNew) {
                btnNew.removeAttribute('onclick');
                btnNew.addEventListener('click', () => this.toggleForm());
                btnNew.disabled = false;
            }

            const btnEdit = document.querySelector('.toolbar[data-toolbar="clientes"] [data-action="edit"]');
            if (btnEdit) {
                btnEdit.removeAttribute('onclick');
                btnEdit.addEventListener('click', () => { if (window.ClienteController) window.ClienteController.edit(); });
            }

            const btnDelete = document.querySelector('.toolbar[data-toolbar="clientes"] [data-action="delete"]');
            if (btnDelete) {
                btnDelete.removeAttribute('onclick');
                btnDelete.addEventListener('click', () => { if (window.ClienteController) window.ClienteController.confirmDelete(); });
            }

            const searchInput = document.getElementById('search-client');
            if (searchInput) {
                searchInput.removeAttribute('oninput');
                searchInput.addEventListener('input', (e) => { if (window.ClienteController) window.ClienteController.load(e.target.value, 1); });
            }

            const btnSave = document.querySelector('#client-form button[onclick="guardarCliente()"]');
            if (btnSave) {
                btnSave.removeAttribute('onclick');
                btnSave.addEventListener('click', () => { if (window.ClienteController) window.ClienteController.save(); });
            }

            const btnCancel = document.querySelector('#client-form button[onclick="toggleClientForm(true)"]');
            if (btnCancel) {
                btnCancel.removeAttribute('onclick');
                btnCancel.addEventListener('click', () => this.toggleForm(true));
            }

            const btnCloseModal = document.querySelector('#client-modal .modal-close');
            if (btnCloseModal) {
                btnCloseModal.removeAttribute('onclick');
                btnCloseModal.addEventListener('click', () => this.toggleForm(true));
            }

            const btnCloseDetail = document.querySelector('#client-detail-modal .modal-close');
            if (btnCloseDetail) {
                btnCloseDetail.removeAttribute('onclick');
                btnCloseDetail.addEventListener('click', () => {
                    document.getElementById('client-detail-modal').style.display = 'none';
                });
            }

            // B3.13 — Botón "Ver en mapa" en el toolbar, junto a Nuevo/Editar/Eliminar.
            // El cliente tiene su propia dirección (tabla `direccion`) con
            // coordenadas (`cliente_latitud`, `cliente_longitud`) que vienen
            // del JOIN del backend (`ClienteRepository::paginateWithSearch`).
            const toolbar = document.querySelector('.toolbar[data-toolbar="clientes"]');
            if (toolbar && !toolbar.querySelector('[data-action="ver-en-mapa"]')) {
                const btnMap = document.createElement('button');
                btnMap.type = 'button';
                btnMap.className = 'toolbar-btn';
                btnMap.dataset.module = 'clientes';
                btnMap.dataset.action = 'ver-en-mapa';
                btnMap.textContent = 'Ver en mapa';
                btnMap.disabled = true;
                btnMap.addEventListener('click', () => {
                    const id = window.selectedIds?.clientes;
                    if (!id) return;
                    const cliente = window.ClienteModel && window.ClienteModel.cache
                        ? window.ClienteModel.cache.find(c => String(c.id || c.id_cliente) === String(id))
                        : null;
                    if (!cliente) return;
                    const lat = Number(cliente.cliente_latitud);
                    const lon = Number(cliente.cliente_longitud);
                    if (!Number.isFinite(lat) || !Number.isFinite(lon) || (lat === 0 && lon === 0)) {
                        window.Toast.warning('Este cliente no tiene coordenadas válidas. Añade la dirección del cliente en su ficha.');
                        return;
                    }
                    if (window.MapaController && window.MapaController.openAt) {
                        const nombre = `${cliente.nombre || cliente.nombres || ''} ${cliente.apellido || cliente.apellidos || ''}`.trim();
                        window.MapaController.openAt(lat, lon, nombre || 'Cliente', 'cliente-' + id);
                    }
                });
                toolbar.appendChild(btnMap);
            }
        }
    };
})();