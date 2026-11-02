// js/views/PerfilView.js
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

    function formatDateTime(val) {
        if (!val) return '—';
        const s = String(val).replace('T', ' ').trim();
        const looksNaive = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(s);
        const iso = looksNaive ? s.replace(' ', 'T') + 'Z' : s;
        const d = new Date(iso);
        if (isNaN(d.getTime())) return s.slice(0, 19);
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} `
             + `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    }

    window.PerfilView = {
        renderPerfil: function (usuario, sesiones) {
            if (!usuario) return;

            const setText = (id, txt) => {
                const el = document.getElementById(id);
                if (el) el.textContent = txt;
            };

            const name = usuario.username || 'Usuario';
            setText('perfil-username-large', name);
            setText('perfil-rol-badge', usuario.nombre_rol || 'Sin rol');
            setText('perfil-avatar-iniciales', name.charAt(0).toUpperCase());
            setText('perfil-username', name);
            setText('perfil-email', usuario.email || '—');
            setText('perfil-rol', usuario.nombre_rol || '—');

            const tbody = document.getElementById('perfil-sesiones-body');
            if (tbody) {
                if (!sesiones || sesiones.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3">Sin sesiones registradas.</td></tr>';
                } else {
                    tbody.innerHTML = sesiones.map(s => `
                        <tr>
                            <td>${esc(s.id_sesion)}</td>
                            <td>${esc(formatDateTime(s.creado_en))}</td>
                            <td>${esc(formatDateTime(s.expira_en))}</td>
                        </tr>
                    `).join('');
                }
            }

            const pag = document.getElementById('perfil-sesiones-pagination');
            if (pag) pag.innerHTML = '';
        },

        _requestCloseModal: function () {
            const session = window.Session ? window.Session.get() : null;
            if (session && session.must_change_password) {
                if (window.Toast) window.Toast.warning('Debes cambiar tu contraseña antes de continuar.');
                return;
            }
            const modal = document.getElementById('password-modal');
            if (modal) modal.style.display = 'none';
        },

        _attachListeners: function () {
            const btnChange = document.querySelector('#password-modal [data-action="change-password"]');
            if (btnChange) {
                btnChange.removeAttribute('onclick');
                btnChange.addEventListener('click', () => {
                    if (window.PerfilController) window.PerfilController.changePassword();
                });
            }

            const btnClose = document.getElementById('password-modal-close');
            if (btnClose) {
                btnClose.removeAttribute('onclick');
                btnClose.addEventListener('click', () => this._requestCloseModal());
            }

            const modal = document.getElementById('password-modal');
            if (modal) {
                modal.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        this._requestCloseModal();
                    }
                });
            }

            ['password-current', 'password-new', 'password-confirm'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            if (window.PerfilController) window.PerfilController.changePassword();
                        }
                    });
                }
            });
        }
    };
})();