// js/controllers/ClienteController.js
(function () {
    'use strict';

    function normalizarCoordenada(val) {
        const txt = String(val ?? '').trim();
        if (!txt) return null;
        const norm = txt.replace(',', '.').trim();
        if (!/^-?\d{1,3}(\.\d+)?$/.test(norm)) return null;
        return norm;
    }

    window.ClienteController = {
        init: function () {
            if (window.ClienteView) {
                window.ClienteView._attachListeners();
            }
        },
        load: async function (filter = '', page = 1) {
            window.currentClienteSearch = filter;
            if (window.ClienteModel && window.ClienteView) {
                const res = await window.ClienteModel.load(filter, page);
                window.ClienteView.renderTable(res.clientes || []);
                window.ClienteView.renderPagination(res.page || 1, res.pages || 1);

                // Resetear selección y deshabilitar edit/delete/ver-en-mapa al recargar.
                if (window.selectedIds) window.selectedIds['clientes'] = null;
                document.querySelectorAll('.toolbar-btn[data-module="clientes"]').forEach(btn => {
                    const action = btn.dataset.action || '';
                    if (action === 'edit' || action === 'delete' || action === 'ver-en-mapa') btn.disabled = true;
                });

                // Recordar página actual (server-side, no client-side).
                window.__clienteCurrentPage = res.page || 1;
            }
        },
        save: async function () {
            const id = document.getElementById('client-id').value;
            const wasEditing = !!id;
            const currentPage = window.__clienteCurrentPage || 1;

            const payload = {
                nombre: document.getElementById('client-nombres').value.trim(),
                apellido: document.getElementById('client-apellidos').value.trim(),
                cedula: document.getElementById('client-cedula').value.trim(),
                telefono: document.getElementById('client-telefono').value.trim(),
                correo: document.getElementById('client-correo').value.trim(),
                direccion: {
                    calle: document.getElementById('client-calle').value.trim(),
                    carrera: document.getElementById('client-carrera').value.trim(),
                    avenida: document.getElementById('client-avenida').value.trim(),
                    latitud: normalizarCoordenada(document.getElementById('client-latitud').value),
                    longitud: normalizarCoordenada(document.getElementById('client-longitud').value)
                }
            };

            if (!payload.nombre || !payload.cedula) {
                if (window.Toast) window.Toast.warning('Faltan datos obligatorios');
                else alert('Faltan datos');
                return;
            }

            try {
                let res;
                if (wasEditing) {
                    res = await window.ClienteModel.update(id, payload);
                } else {
                    res = await window.ClienteModel.create(payload);
                }

                if (res.status === 'success') {
                    if (window.Toast) window.Toast.success(res.message);
                    else alert(res.message);

                    if (window.ClienteView) window.ClienteView.toggleForm(true);

                    // Si edición: volver a la misma página. Si creación: página 1
                    // (el backend ordena por fecha_registro DESC, el nuevo sale arriba).
                    await this.load(window.currentClienteSearch || '', wasEditing ? currentPage : 1);
                } else {
                    if (window.Toast) window.Toast.error(res.message);
                    else alert(res.message);
                }
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al guardar');
                else alert(e.message || 'Error al guardar');
            }
        },
        edit: async function () {
            const id = window.selectedIds ? window.selectedIds['clientes'] : null;
            if (!id) return;

            // Defensivo: si el cache está vacío (re-login sin onShow), recargar.
            if (window.ClienteModel.cache.length === 0) {
                await window.ClienteModel.load(window.currentClienteSearch || '', 1);
            }

            const cliente = window.ClienteModel ? window.ClienteModel.getById(id) : null;
            if (!cliente) return;

            if (window.ClienteView) {
                window.ClienteView.fillForm(cliente);
                window.ClienteView.toggleForm(false, false);
            }
        },
        confirmDelete: async function () {
            const id = window.selectedIds ? window.selectedIds['clientes'] : null;
            if (!id) return;

            const currentPage = window.__clienteCurrentPage || 1;

            let confirmed = false;
            if (window.Modal) {
                confirmed = await window.Modal.confirm('¿Desactivar este cliente?', { danger: true });
            } else {
                confirmed = confirm('¿Desactivar este cliente?');
            }

            if (!confirmed) return;

            try {
                const res = await window.ClienteModel.deactivate(id);
                if (res.status === 'success') {
                    if (window.Toast) window.Toast.success(res.message);
                    else alert(res.message);

                    await this.load(window.currentClienteSearch || '', currentPage);
                } else {
                    if (window.Toast) window.Toast.error(res.message);
                    else alert(res.message);
                }
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al eliminar');
                else alert(e.message || 'Error al eliminar');
            }
        },
        showProfile: function (id) {
            const cliente = window.ClienteModel ? window.ClienteModel.getById(id) : null;
            if (!cliente) return;

            let servicios = [];
            if (window.ServicioModel) {
                servicios = window.ServicioModel.cache.filter(s => String(s.cliente_id || s.id_cliente) === String(id));
            }

            if (window.ClienteView) {
                window.ClienteView.renderProfile(cliente, servicios);
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.ClienteController.init();
    });
})();