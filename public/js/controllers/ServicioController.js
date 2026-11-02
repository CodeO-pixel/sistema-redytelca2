// js/controllers/ServicioController.js
(function () {
    'use strict';

    function normalizeForSearch(str) {
        return String(str ?? '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    window.ServicioController = {
        init: function () {
            if (window.ServicioView) {
                window.ServicioView._attachListeners();
            }
        },
        load: async function (page = 1) {
            if (window.ServicioModel && window.ServicioView) {
                if (window.ClienteModel && window.ClienteModel.cache.length === 0) {
                    await window.ClienteModel.load();
                }
                if (window.PlanModel && window.PlanModel.cache.length === 0) await window.PlanModel.load();
                if (window.NapModel && window.NapModel.cache.length === 0) await window.NapModel.load();

                await window.ServicioModel.load();
                if (window.selectedIds) window.selectedIds['servicios'] = null;
                document.querySelectorAll('.toolbar-btn[data-module="servicios"]').forEach(btn => {
                    const action = btn.dataset.action || '';
                    if (action === 'edit' || action === 'delete' || action === 'ver-en-mapa') btn.disabled = true;
                });
                this.renderPage(page);
            }
        },
        renderPage: function (page) {
            const searchInput = document.getElementById('servicios-search');
            const search = normalizeForSearch(searchInput ? searchInput.value : '');
            
            let cache = window.ServicioModel ? window.ServicioModel.cache : [];
            
            let filtered = cache;
            if (search) {
                filtered = cache.filter(s => {
                    const haystack = [s.alias, s.estado_comercial, s.direccion_texto, s.nombres, s.apellidos].join(' ');
                    return normalizeForSearch(haystack).includes(search);
                });
            }

            const pageSize = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
            const safePage = Math.min(Math.max(1, page), totalPages);
            
            const start = (safePage - 1) * pageSize;
            const vis = filtered.slice(start, start + pageSize);

            window.__servicioCurrentPage = safePage;

            if (window.ServicioView) {
                window.ServicioView.renderTable(vis, safePage, totalPages);
            }
        },
        openNewForClient: function (clienteId) {
            if (window.ServicioView) {
                window.ServicioView.toggleForm(false, true);
                
                // Buscamos el cliente en cache para pasarlo como fallback
                const c = window.ClienteModel ? window.ClienteModel.getById(clienteId) : null;
                const fallback = c ? `${c.nombre || c.nombres} ${c.apellido || c.apellidos}`.trim() : 'Cliente Seleccionado';
                
                window.ServicioView.populateSelects(clienteId, fallback);
            }
        },
        openNew: function () {
            if (window.ServicioView) {
                window.ServicioView.toggleForm(false, true);
                window.ServicioView.populateSelects(null, null);
            }
        },
        save: async function () {
            const id = document.getElementById('servicio-id').value;
            const idCliente = document.getElementById('servicio-cliente').value;
            const wasEditing = !!id;
            const currentPage = window.__servicioCurrentPage || 1;
            
            function normalizarCoordenada(val) {
                const txt = String(val ?? '').trim();
                if (!txt) return null;
                const norm = txt.replace(',', '.').trim();
                if (!/^-?\d{1,3}(\.\d+)?$/.test(norm)) return null;
                return norm;
            }

            const payload = {
                cliente_id: idCliente,
                alias: document.getElementById('servicio-alias').value.trim(),
                direccion_texto: document.getElementById('servicio-direccion').value.trim(),
                latitud: normalizarCoordenada(document.getElementById('servicio-latitud').value),
                longitud: normalizarCoordenada(document.getElementById('servicio-longitud').value),
                nap_id: document.getElementById('servicio-nap').value,
                plan_id: document.getElementById('servicio-plan').value,
                estado_comercial: document.getElementById('servicio-estado').value
            };

            if (!idCliente) {
                if (window.Toast) window.Toast.warning('Cliente obligatorio');
                else alert('Cliente obligatorio');
                return;
            }
            if (!payload.direccion_texto) {
                if (window.Toast) window.Toast.warning('Dirección obligatoria');
                else alert('Dirección obligatoria');
                return;
            }

            try {
                let res;
                if (wasEditing) {
                    res = await window.ServicioModel.update(id, payload);
                } else {
                    res = await window.ServicioModel.create(payload);
                }

                if (res.status === 'success') {
                    if (window.Toast) window.Toast.success(res.message);
                    else alert(res.message);
                    
                    if (window.ServicioView) window.ServicioView.toggleForm(true);

                    await this.load(wasEditing ? currentPage : 1);

                    if (!wasEditing) {
                        const cache = window.ServicioModel ? window.ServicioModel.cache : [];
                        const pageSize = window.pageSize || 8;
                        const lastPage = Math.max(1, Math.ceil(cache.length / pageSize));
                        this.renderPage(lastPage);
                    }
                    
                    if (window.ClienteController) {
                        window.ClienteController.load(window.currentClienteSearch || '', 1);
                        const c = window.ClienteModel ? window.ClienteModel.getById(idCliente) : null;
                        if (c) window.ClienteController.showProfile(idCliente);
                    }
                } else {
                    if (window.Toast) window.Toast.error(res.message);
                    else alert(res.message);
                }
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al guardar');
                else alert(e.message || 'Error al guardar');
            }
        },
        edit: async function (id) {
            if (window.ServicioModel && window.ServicioModel.cache.length === 0) {
                await window.ServicioModel.load();
            }
            if (window.ClienteModel && window.ClienteModel.cache.length === 0) {
                await window.ClienteModel.load();
            }
            if (window.PlanModel && window.PlanModel.cache.length === 0) {
                await window.PlanModel.load();
            }
            if (window.NapModel && window.NapModel.cache.length === 0) {
                await window.NapModel.load();
            }

            const servicio = window.ServicioModel ? window.ServicioModel.getById(id) : null;
            if (!servicio) return;
            
            if (window.ServicioView) {
                window.ServicioView.toggleForm(false, false);
                
                // Armamos el nombre del cliente usando el JOIN del backend para evitar que el select quede vacío
                const nombreFallback = servicio.nombres 
                    ? `${servicio.nombres} ${servicio.apellidos || ''}`.trim() 
                    : 'Cliente Desconocido';

                window.ServicioView.populateSelects(servicio.cliente_id || servicio.id_cliente, nombreFallback);
                window.ServicioView.fillForm(servicio);
            }
        },
        confirmDelete: async function (id) {
            let confirmed = false;
            if (window.Modal) {
                confirmed = await window.Modal.confirm('¿Eliminar este servicio?', { danger: true });
            } else {
                confirmed = confirm('¿Eliminar este servicio?');
            }

            if (!confirmed) return;

            const s = window.ServicioModel ? window.ServicioModel.getById(id) : null;
            const currentPage = window.__servicioCurrentPage || 1;

            try {
                const res = await window.ServicioModel.delete(id);
                if (res.status === 'success') {
                    if (window.Toast) window.Toast.success(res.message);
                    else alert(res.message);
                    
                    await this.load(currentPage);
                    
                    if (window.ClienteController) {
                        window.ClienteController.load(window.currentClienteSearch || '', 1);
                        if (s) {
                            const c = window.ClienteModel ? window.ClienteModel.getById(s.cliente_id || s.id_cliente) : null;
                            if (c) window.ClienteController.showProfile(s.cliente_id || s.id_cliente);
                        }
                    }
                } else {
                    if (window.Toast) window.Toast.error(res.message);
                    else alert(res.message);
                }
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al eliminar');
                else alert(e.message || 'Error al eliminar');
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.ServicioController.init();
    });
})();