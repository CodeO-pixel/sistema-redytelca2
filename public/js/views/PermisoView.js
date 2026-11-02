// js/views/PermisoView.js
//
// Fase 4.3 — Matriz de permisos como tabla grid (no cards).
// Filas = páginas, columnas = Ver / Leer / Crear / Editar / Eliminar / Validar.
// Agrupada por módulo con una fila header por módulo.
// Los permisos NO aplicables a una página se pintan como "—" (celda no-interactiva).
// Patrón Salesforce Profiles / Splynx Roles / SAP Authorization / Odoo Access Rights.
//
// applyVisibleState sigue funcionando: cuando se desmarca "Ver" de una fila,
// se deshabilitan los checkboxes de operación de ESA fila (no de otras).
// Los checkboxes marcados con data-sod-disabled="1" (módulo Configuración para
// no-Admin) nunca se re-habilitan por este método.
(function () {
    'use strict';

    function escapeHtml(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    window.PermisoView = {
        renderMatrix: function (modulos, idRolActual) {
            const cont = document.getElementById('modulos-permissions-list');
            if (!cont) return;

            if (!modulos || modulos.length === 0) {
                cont.innerHTML = '<p style="color:#64748b; padding:16px;">No hay módulos configurados.</p>';
                return;
            }

            const esAdmin = String(idRolActual) === '1';
            const rowsHtml = [];

            modulos.forEach(m => {
                const isConfig = Number(m.id_modulo) === 4;
                const disableSection = !esAdmin && isConfig;
                const disabledAttr = disableSection ? 'disabled' : '';
                const sodAttr = disableSection ? 'data-sod-disabled="1"' : '';
                const titleAttr = disableSection ? 'title="Solo un Administrador puede otorgar permisos sobre el módulo Configuración."' : '';
                const rowStyle = disableSection ? 'opacity: 0.55;' : '';
                const warningMsg = disableSection
                    ? '<tr class="permiso-module-warning"><td colspan="7">Solo Administradores pueden modificar esta sección.</td></tr>'
                    : '';

                rowsHtml.push(
                    `<tr class="permiso-module-row"><td colspan="7">${escapeHtml(m.nombre_modulo)}</td></tr>`
                );
                if (warningMsg) rowsHtml.push(warningMsg);

                m.vistas.forEach(v => {
                    const aplicables = Array.isArray(v.permisos_aplicables)
                        ? v.permisos_aplicables.map(Number)
                        : [1, 2, 3, 4];

                    const permisoById = {};
                    (v.permisos || []).forEach(p => {
                        permisoById[Number(p.id_permiso)] = p;
                    });

                    const cellFor = (idPermiso) => {
                        if (!aplicables.includes(idPermiso)) {
                            return '<td class="permiso-cell permiso-cell-na">—</td>';
                        }
                        const p = permisoById[idPermiso];
                        if (!p) {
                            return '<td class="permiso-cell permiso-cell-na">—</td>';
                        }
                        return `<td class="permiso-cell"><label ${titleAttr}><input type="checkbox" class="permiso-operacion" data-pagina="${v.id_pagina}" data-permiso="${idPermiso}" value="${idPermiso}" ${p.asignado ? 'checked' : ''} ${disabledAttr} ${sodAttr}></label></td>`;
                    };

                    rowsHtml.push(`
                        <tr data-pagina-block="${v.id_pagina}" style="${rowStyle}">
                            <td class="permiso-pagina">${escapeHtml(v.nombre_pagina)}</td>
                            <td class="permiso-cell"><label ${titleAttr}><input type="checkbox" class="permiso-visible" data-pagina="${v.id_pagina}" ${v.visible ? 'checked' : ''} ${disabledAttr} ${sodAttr}></label></td>
                            ${cellFor(1)}
                            ${cellFor(2)}
                            ${cellFor(3)}
                            ${cellFor(4)}
                            ${cellFor(5)}
                        </tr>
                    `);
                });
            });

            cont.innerHTML = `
                <div class="permissions-matrix-table-wrap">
                    <table class="permissions-matrix-table">
                        <thead>
                            <tr>
                                <th>Página</th>
                                <th>Ver</th>
                                <th>Leer</th>
                                <th>Crear</th>
                                <th>Editar</th>
                                <th>Eliminar</th>
                                <th>Validar</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml.join('')}
                        </tbody>
                    </table>
                </div>
            `;

            cont.querySelectorAll('.permiso-visible').forEach(chk => {
                if (!chk.disabled) this.applyVisibleState(chk);
            });
        },

        applyVisibleState: function (visibleChk) {
            if (!visibleChk || visibleChk.disabled) return;
            const block = visibleChk.closest('[data-pagina-block]');
            if (!block) return;

            const visible = visibleChk.checked;
            block.querySelectorAll('.permiso-operacion').forEach(op => {
                if (!op.dataset.sodDisabled) {
                    op.disabled = !visible;
                }
            });
        },

        collectMatrix: function () {
            const paginas = [];
            const blocks = document.querySelectorAll('#modulos-permissions-list [data-pagina-block]');

            blocks.forEach(block => {
                const idPagina = Number(block.dataset.paginaBlock);
                const visibleChk = block.querySelector('.permiso-visible');
                const visible = visibleChk ? visibleChk.checked : false;
                const permisos = [];

                block.querySelectorAll('.permiso-operacion').forEach(op => {
                    if (op.checked) permisos.push(Number(op.dataset.permiso));
                });

                if (visible || permisos.length > 0) {
                    paginas.push({ id_pagina: idPagina, visible: visible, permisos: permisos });
                }
            });

            return paginas;
        },

        _attachListeners: function () {
            const roleSelect = document.getElementById('modulos-role-select');
            if (roleSelect) {
                roleSelect.removeAttribute('onchange');
                roleSelect.addEventListener('change', () => {
                    if (window.PermisoController) {
                        window.PermisoController.load(roleSelect.value);
                    }
                });
            }

            const saveBtn = document.getElementById('modulos-save-btn');
            if (saveBtn) {
                saveBtn.removeAttribute('onclick');
                saveBtn.addEventListener('click', () => {
                    if (window.PermisoController) window.PermisoController.save();
                });
            }

            const list = document.getElementById('modulos-permissions-list');
            if (list) {
                list.addEventListener('change', (e) => {
                    const target = e.target;
                    if (target && target.classList.contains('permiso-visible')) {
                        this.applyVisibleState(target);
                    }
                    if (target && (target.classList.contains('permiso-visible') || target.classList.contains('permiso-operacion'))) {
                        if (window.PermisoController) window.PermisoController._dirty = true;
                    }
                });
            }
        }
    };
})();