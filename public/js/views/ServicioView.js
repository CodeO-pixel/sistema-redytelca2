// js/views/ServicioView.js
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

    function getStatusClass(status) {
        const s = String(status || '').toLowerCase();
        if (s === 'activo') return 'status-ok';
        if (s === 'retirado') return 'status-alert';
        return 'status-warning';
    }

    function fullName(nombres, apellidos) {
        return `${nombres || ''} ${apellidos || ''}`.trim();
    }

    window.ServicioView = {
        populateSelects: function (selectedClienteId = null, clienteFallback = null) {
            const selCliente = document.getElementById('servicio-cliente');
            if (selCliente) {
                const cache = window.ClienteModel ? window.ClienteModel.cache : [];
                let html = '<option value="">Selecciona un cliente</option>';
                let found = false;

                html += cache.map(c => {
                    const id = c.id || c.id_cliente;
                    if (String(id) === String(selectedClienteId)) found = true;
                    return `<option value="${id}">${escapeHtml(fullName(c.nombre || c.nombres, c.apellido || c.apellidos))}</option>`;
                }).join('');

                // Si el cliente seleccionado no está en el cache actual (ej. paginación),
                // inyectamos la opción usando el nombre que viene del JOIN del backend.
                if (selectedClienteId && !found && clienteFallback) {
                    html += `<option value="${selectedClienteId}">${escapeHtml(clienteFallback)}</option>`;
                }

                selCliente.innerHTML = html;
                if (selectedClienteId) {
                    selCliente.value = selectedClienteId;
                    selCliente.disabled = true;
                } else {
                    selCliente.disabled = false;
                }
            }

            const selPlan = document.getElementById('servicio-plan');
            if (selPlan) {
                const cache = window.PlanModel ? window.PlanModel.cache : [];
                selPlan.innerHTML = cache.map(p => `<option value="${p.id || p.id_plan}">${escapeHtml(p.nombre)}</option>`).join('');
            }

            const selNap = document.getElementById('servicio-nap');
            if (selNap) {
                const cache = window.NapModel ? window.NapModel.cache : [];
                selNap.innerHTML = '<option value="">Sin NAP asignada</option>' + 
                    cache.map(n => {
                        const label = n.olt_codigo ? `${n.codigo} (${n.olt_codigo})` : n.codigo;
                        return `<option value="${n.id || n.id_nap}">${escapeHtml(label)}</option>`;
                    }).join('');
            }
        },
        renderTable: function (servicios, page, totalPages) {
            const tbody = document.getElementById('servicios-list');
            if (!tbody) return;

            tbody.innerHTML = servicios.length ? servicios.map(s => {
                const idServicio = s.id || s.id_servicio;

                let nombreCliente = fullName(s.nombres, s.apellidos);
                if (!nombreCliente) {
                    const clienteObj = window.ClienteModel ? window.ClienteModel.getById(s.cliente_id || s.id_cliente) : null;
                    if (clienteObj) nombreCliente = fullName(clienteObj.nombre || clienteObj.nombres, clienteObj.apellido || clienteObj.apellidos);
                }
                if (!nombreCliente) nombreCliente = 'Sin cliente';

                let nombrePlan = s.plan_nombre;
                if (!nombrePlan) {
                    const planObj = window.PlanModel ? window.PlanModel.cache.find(p => String(p.id || p.id_plan) === String(s.plan_id || s.id_plan)) : null;
                    nombrePlan = planObj ? planObj.nombre : 'Sin plan';
                }

                let codigoNap = s.nap_codigo;
                if (!codigoNap) {
                    const napObj = window.NapModel ? window.NapModel.cache.find(n => String(n.id || n.id_nap) === String(s.nap_id || s.id_naps)) : null;
                    codigoNap = napObj ? napObj.codigo : '—';
                }

                return `
                <tr data-module-row="servicios" data-id="${escapeHtml(idServicio)}">
                    <td>${escapeHtml(nombreCliente)}</td>
                    <td>${escapeHtml(s.alias || '—')}</td>
                    <td>${escapeHtml(nombrePlan)}</td>
                    <td>${escapeHtml(codigoNap)}</td>
                    <td><span class="status ${getStatusClass(s.estado_comercial)}">${escapeHtml(s.estado_comercial)}</span></td>
                    <td>${escapeHtml(s.direccion_texto || s.direccion || '—')}</td>
                </tr>
            `}).join('') : '<tr><td colspan="6">No hay servicios.</td></tr>';

            tbody.querySelectorAll('tr[data-id]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const id = tr.getAttribute('data-id');
                    
                    const prevId = window.selectedIds ? window.selectedIds['servicios'] : null;
                    if (prevId) {
                        const prevRow = document.querySelector(`[data-module-row="servicios"][data-id="${prevId}"]`);
                        if (prevRow) prevRow.classList.remove('selected-row');
                    }
                    if (window.selectedIds) window.selectedIds['servicios'] = id;
                    tr.classList.add('selected-row');
                    
                    document.querySelectorAll(`.toolbar-btn[data-module="servicios"]`).forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete' || action === 'ver-en-mapa') btn.disabled = false;
                    });
                });
            });

            this.renderPagination(page, totalPages);
        },
        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('servicios-pagination');
            if (!pag) return;
            
            pag.innerHTML = `
                <div class="pagination-controls">
                    <button id="btn-servicios-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                    <span>Página ${page} / ${totalPages}</span>
                    <button id="btn-servicios-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
                </div>
            `;

            document.getElementById('btn-servicios-prev')?.addEventListener('click', () => {
                if (window.ServicioController) window.ServicioController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-servicios-next')?.addEventListener('click', () => {
                if (window.ServicioController) window.ServicioController.renderPage(Math.min(totalPages, page + 1));
            });
        },
        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('servicio-modal');
            if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                ['servicio-id', 'servicio-cliente-id', 'servicio-alias', 'servicio-direccion', 'servicio-latitud', 'servicio-longitud', 'servicio-nap'].forEach(id => {
                    const el = document.getElementById(id); if (el) el.value = '';
                });
                const elCliente = document.getElementById('servicio-cliente');
                if (elCliente) { elCliente.value = ''; elCliente.disabled = false; }
                const elEst = document.getElementById('servicio-estado'); if (elEst) elEst.value = 'pendiente';
            }
        },
        fillForm: function (servicio) {
            document.getElementById('servicio-id').value = servicio.id || servicio.id_servicio || '';
            document.getElementById('servicio-cliente-id').value = servicio.cliente_id || servicio.id_cliente || '';
            
            // El select ya se pobló en populateSelects, aquí solo seteamos el valor
            const selCliente = document.getElementById('servicio-cliente');
            if (selCliente) { selCliente.value = servicio.cliente_id || servicio.id_cliente || ''; }
            
            document.getElementById('servicio-alias').value = servicio.alias || '';
            document.getElementById('servicio-direccion').value = servicio.direccion_texto || servicio.direccion || '';
            document.getElementById('servicio-latitud').value = servicio.latitud ?? '';
            document.getElementById('servicio-longitud').value = servicio.longitud ?? '';
            
            const selNap = document.getElementById('servicio-nap');
            if (selNap) selNap.value = servicio.nap_id || servicio.id_naps || '';
            
            const selPlan = document.getElementById('servicio-plan');
            if (selPlan) selPlan.value = servicio.plan_id || servicio.id_plan || '1';
            
            const selEstado = document.getElementById('servicio-estado');
            if (selEstado) selEstado.value = servicio.estado_comercial || 'pendiente';
        },
        _attachListeners: function () {
            if (window.selectedIds) window.selectedIds['servicios'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="servicios"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });

            const btnNew = document.querySelector('.toolbar[data-toolbar="servicios"] [data-action="new"]');
            if (btnNew) {
                btnNew.removeAttribute('onclick');
                btnNew.addEventListener('click', () => {
                    if (window.ServicioController) window.ServicioController.openNew();
                });
                btnNew.disabled = false;
            }

            const btnEdit = document.querySelector('.toolbar[data-toolbar="servicios"] [data-action="edit"]');
            if (btnEdit) {
                btnEdit.removeAttribute('onclick');
                btnEdit.addEventListener('click', () => {
                    const id = window.selectedIds ? window.selectedIds['servicios'] : null;
                    if (id && window.ServicioController) window.ServicioController.edit(id);
                });
            }

            const btnDelete = document.querySelector('.toolbar[data-toolbar="servicios"] [data-action="delete"]');
            if (btnDelete) {
                btnDelete.removeAttribute('onclick');
                btnDelete.addEventListener('click', () => {
                    const id = window.selectedIds ? window.selectedIds['servicios'] : null;
                    if (id && window.ServicioController) window.ServicioController.confirmDelete(id);
                });
            }

            const searchInput = document.getElementById('servicios-search');
            if (searchInput) {
                searchInput.removeAttribute('oninput');
                searchInput.addEventListener('input', () => { if (window.ServicioController) window.ServicioController.renderPage(1); });
            }

            const btnSave = document.querySelector('#servicio-form button[onclick="guardarServicio()"]');
            if (btnSave) {
                btnSave.removeAttribute('onclick');
                btnSave.addEventListener('click', () => { if (window.ServicioController) window.ServicioController.save(); });
            }

            const btnCancel = document.querySelector('#servicio-form button[onclick="toggleServicioForm(true)"]');
            if (btnCancel) {
                btnCancel.removeAttribute('onclick');
                btnCancel.addEventListener('click', () => this.toggleForm(true));
            }

            const btnCloseModal = document.querySelector('#servicio-modal .modal-close');
            if (btnCloseModal) {
                btnCloseModal.removeAttribute('onclick');
                btnCloseModal.addEventListener('click', () => this.toggleForm(true));
            }

            // B3.13 — Botón "Ver en mapa" en el toolbar, junto a Nuevo/Editar/Eliminar.
            // El servicio tiene su propia dirección (tabla `direccion_servicio`)
            // con coordenadas (`latitud`, `longitud`) que vienen del JOIN
            // `direccion_servicio` en el backend (`ServicioRepository::findAll`).
            const toolbar = document.querySelector('.toolbar[data-toolbar="servicios"]');
            if (toolbar && !toolbar.querySelector('[data-action="ver-en-mapa"]')) {
                const btnMap = document.createElement('button');
                btnMap.type = 'button';
                btnMap.className = 'toolbar-btn';
                btnMap.dataset.module = 'servicios';
                btnMap.dataset.action = 'ver-en-mapa';
                btnMap.textContent = 'Ver en mapa';
                btnMap.disabled = true;
                btnMap.addEventListener('click', () => {
                    const id = window.selectedIds?.servicios;
                    if (!id) return;
                    const servicio = window.ServicioModel && window.ServicioModel.cache
                        ? window.ServicioModel.cache.find(s => String(s.id || s.id_servicio) === String(id))
                        : null;
                    if (!servicio) return;
                    const lat = Number(servicio.latitud);
                    const lon = Number(servicio.longitud);
                    if (!Number.isFinite(lat) || !Number.isFinite(lon) || (lat === 0 && lon === 0)) {
                        window.Toast.warning('Este servicio no tiene coordenadas de instalación. Edita el servicio para añadirlas.');
                        return;
                    }
                    if (window.MapaController && window.MapaController.openAt) {
                        const alias = servicio.alias
                            || (servicio.nombres ? `${servicio.nombres} ${servicio.apellidos || ''}`.trim() : `Servicio #${id}`);
                        window.MapaController.openAt(lat, lon, alias, 'servicio-' + id);
                    }
                });
                toolbar.appendChild(btnMap);
            }
        }
    };
})();