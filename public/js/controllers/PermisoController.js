// js/controllers/PermisoController.js
(function () {
    'use strict';

    window.PermisoController = {
        _dirty: false,

        init: function () {
            if (window.PermisoView) window.PermisoView._attachListeners();
        },

        load: async function (idRol = null) {
            if (window.RolModel && window.RolModel.cache.length === 0) {
                await window.RolModel.load();
            }

            const roles = window.RolModel ? window.RolModel.cache : [];
            if (roles.length === 0) {
                window.Toast.error('No hay roles configurados.');
                return;
            }

            this._populateRoleSelect(roles);

            const selector = document.getElementById('modulos-role-select');
            let target;

            if (idRol !== null && idRol !== undefined && String(idRol) !== '') {
                target = String(idRol);
            } else {
                try {
                    const persisted = sessionStorage.getItem('redytelca.permisos.idRol');
                    if (persisted && roles.some(r => String(r.id_rol) === String(persisted))) {
                        target = String(persisted);
                    }
                } catch (e) { /* no-op */ }
                if (!target) {
                    target = String(roles[0].id_rol);
                }
            }

            if (!roles.some(r => String(r.id_rol) === target)) {
                target = String(roles[0].id_rol);
            }

            // Dirty check: si hay cambios sin guardar y se intenta cambiar de rol
            const currentId = window.PermisoModel.cache.lastIdRol;
            if (this._dirty && currentId !== null && String(currentId) !== target) {
                const ok = await window.Modal.confirm(
                    'Hay cambios sin guardar en el rol actual. ¿Descartar y cambiar de rol?',
                    { danger: true, confirmText: 'Descartar' }
                );
                if (!ok) {
                    if (selector) selector.value = String(currentId);
                    return;
                }
                this._dirty = false;
            }

            if (selector) selector.value = target;

            const feedback = document.getElementById('modulos-permissions-feedback');
            if (feedback) feedback.textContent = '';

            try {
                const result = await window.PermisoModel.loadMatrix(target);
                window.PermisoModel.cache.lastIdRol = target;
                this._persist(target);
                window.PermisoView.renderMatrix(result.modulos || [], target);
            } catch (e) {
                window.Toast.error(e.message || 'Error al cargar la matriz.');
            }
        },

        save: async function () {
            const selector = document.getElementById('modulos-role-select');
            const idRol = selector ? selector.value : '';
            if (!idRol) {
                window.Toast.warning('Selecciona un rol.');
                return;
            }

            const btn = document.getElementById('modulos-save-btn');
            if (btn && btn.disabled) return;
            if (btn) {
                btn.disabled = true;
                btn.dataset.originalText = btn.textContent;
                btn.textContent = 'Guardando…';
            }

            try {
                const paginas = window.PermisoView.collectMatrix();
                const res = await window.PermisoModel.saveMatrix({ id_rol: idRol, paginas: paginas });

                const feedback = document.getElementById('modulos-permissions-feedback');
                if (res.status === 'success') {
                    this._dirty = false;
                    if (window.Toast) window.Toast.success(res.message || 'Permisos actualizados correctamente');
                    if (feedback) { feedback.style.color = 'green'; feedback.textContent = res.message || 'Permisos actualizados correctamente'; }
                    await this.load(idRol);
                } else {
                    if (window.Toast) window.Toast.error(res.message || 'Error al guardar permisos');
                    if (feedback) { feedback.style.color = '#dc2626'; feedback.textContent = res.message || 'Error al guardar permisos'; }
                }
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al guardar permisos');
                const feedback = document.getElementById('modulos-permissions-feedback');
                if (feedback) { feedback.style.color = '#dc2626'; feedback.textContent = e.message || 'Error al guardar permisos'; }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = btn.dataset.originalText || 'Guardar';
                }
            }
        },

        _populateRoleSelect: function (roles) {
            const sel = document.getElementById('modulos-role-select');
            if (!sel) return;

            const current = sel.value;
            sel.innerHTML = roles.map(r =>
                `<option value="${r.id_rol}">${r.nombre_rol}</option>`
            ).join('');
            if (current) sel.value = current;
        },

        _persist: function (idRol) {
            try {
                sessionStorage.setItem('redytelca.permisos.idRol', String(idRol || ''));
            } catch (e) { /* no-op */ }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.PermisoController.init());
})();