// js/core/Modal.js
(function () {
    'use strict';

    let cssInjected = false;
    let currentResolve = null;
    let currentModalEl = null;

    function injectCss() {
        if (cssInjected) return;
        const style = document.createElement('style');
        style.textContent = `
            .core-modal-overlay {
                position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55);
                display: flex; align-items: center; justify-content: center;
                padding: 20px; z-index: 9999;
            }
            .core-modal-card {
                width: min(400px, 100%); background: white; border-radius: 16px;
                padding: 24px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
                display: flex; flex-direction: column; gap: 16px;
            }
            .core-modal-title { margin: 0; font-size: 1.2rem; color: #0f172a; }
            .core-modal-message { margin: 0; color: #475569; font-size: 0.95rem; line-height: 1.5; }
            .core-modal-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px; }
            .core-modal-btn {
                padding: 10px 16px; border-radius: 10px; font-weight: 600;
                cursor: pointer; border: none; font-size: 0.9rem;
            }
            .core-modal-btn-cancel { background: #e2e8f0; color: #475569; }
            .core-modal-btn-cancel:hover { background: #cbd5e1; }
            .core-modal-btn-primary { background: var(--accent, #104aa8); color: white; }
            .core-modal-btn-primary:hover { opacity: 0.9; }
            .core-modal-btn-danger { background: var(--danger, #dc2626); color: white; }
            .core-modal-btn-danger:hover { opacity: 0.9; }
        `;
        document.head.appendChild(style);
        cssInjected = true;
    }

    function closeCurrent(result) {
        if (currentModalEl) {
            currentModalEl.remove();
            currentModalEl = null;
        }
        if (currentResolve) {
            currentResolve(result);
            currentResolve = null;
        }
    }

    function createModal(message, options, isConfirm) {
        injectCss();
        closeCurrent(isConfirm ? false : undefined);

        const title = options.title || (isConfirm ? 'Confirmar' : 'Aviso');
        const confirmText = options.confirmText || 'Aceptar';
        const cancelText = options.cancelText || 'Cancelar';
        const danger = !!options.danger;

        const overlay = document.createElement('div');
        overlay.className = 'core-modal-overlay';

        const card = document.createElement('div');
        card.className = 'core-modal-card';

        const titleEl = document.createElement('h3');
        titleEl.className = 'core-modal-title';
        titleEl.textContent = title;

        const msgEl = document.createElement('p');
        msgEl.className = 'core-modal-message';
        msgEl.textContent = message;

        const actions = document.createElement('div');
        actions.className = 'core-modal-actions';

        let cancelBtn = null;
        if (isConfirm) {
            cancelBtn = document.createElement('button');
            cancelBtn.className = 'core-modal-btn core-modal-btn-cancel';
            cancelBtn.textContent = cancelText;
            cancelBtn.onclick = () => closeCurrent(false);
            actions.appendChild(cancelBtn);
        }

        const confirmBtn = document.createElement('button');
        confirmBtn.className = `core-modal-btn ${danger ? 'core-modal-btn-danger' : 'core-modal-btn-primary'}`;
        confirmBtn.textContent = confirmText;
        confirmBtn.onclick = () => closeCurrent(isConfirm ? true : undefined);
        actions.appendChild(confirmBtn);

        card.appendChild(titleEl);
        card.appendChild(msgEl);
        card.appendChild(actions);
        overlay.appendChild(card);

        overlay.addEventListener('mousedown', (e) => {
            if (e.target === overlay) {
                closeCurrent(isConfirm ? false : undefined);
            }
        });

        overlay.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                e.stopPropagation();
                closeCurrent(isConfirm ? false : undefined);
            } else if (e.key === 'Enter') {
                e.stopPropagation();
                closeCurrent(isConfirm ? true : undefined);
            }
        });

        document.body.appendChild(overlay);
        currentModalEl = overlay;

        setTimeout(() => {
            if (isConfirm && cancelBtn) {
                cancelBtn.focus();
            } else {
                confirmBtn.focus();
            }
        }, 10);

        return new Promise(resolve => {
            currentResolve = resolve;
        });
    }

    window.Modal = {
        confirm: function (message, options = {}) {
            return createModal(message, options, true);
        },
        alert: function (message, options = {}) {
            return createModal(message, options, false);
        }
    };
})();