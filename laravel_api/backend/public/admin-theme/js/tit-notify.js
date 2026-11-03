(function () {
    'use strict';

    // 1. Toast Container
    let toastContainer = null;
    function getToastContainer() {
        if (!toastContainer || !document.body.contains(toastContainer)) {
            toastContainer = document.getElementById('tit-toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'tit-toast-container';
                toastContainer.className = 'tit-toast-container';
                document.body.appendChild(toastContainer);
            }
        }
        return toastContainer;
    }

    const DURATION_DEFAULTS = {
        success: 4000,
        info: 4000,
        warning: 6000,
        error: 8000
    };

    const ICONS = {
        success: '<i class="fa fa-check-circle tit-toast-icon" aria-hidden="true"></i>',
        info: '<i class="fa fa-info-circle tit-toast-icon" aria-hidden="true"></i>',
        warning: '<i class="fa fa-exclamation-triangle tit-toast-icon" aria-hidden="true"></i>',
        error: '<i class="fa fa-times-circle tit-toast-icon" aria-hidden="true"></i>'
    };

    function showToast(type, message, options = {}) {
        const container = getToastContainer();
        const duration = typeof options.duration === 'number' ? options.duration : (DURATION_DEFAULTS[type] || 4000);
        const title = options.title || '';

        // Max 3 stacked toasts: dismiss oldest if count >= 3
        const existingToasts = container.querySelectorAll('.tit-toast');
        if (existingToasts.length >= 3) {
            for (let i = 0; i <= existingToasts.length - 3; i++) {
                removeToast(existingToasts[i]);
            }
        }

        const toast = document.createElement('div');
        toast.className = `tit-toast tit-toast--${type}`;
        if (type === 'error' || type === 'warning') {
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
        } else {
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
        }

        let innerHTML = `
            <div class="tit-toast-icon-wrap">${ICONS[type] || ICONS.info}</div>
            <div class="tit-toast-content">
                ${title ? `<div class="tit-toast-title font-weight-bold">${escapeHtml(title)}</div>` : ''}
                <div class="tit-toast-message">${escapeHtml(message)}</div>
            </div>
            <button type="button" class="tit-toast-close" aria-label="Close notification">&times;</button>
        `;
        toast.innerHTML = innerHTML;

        // Dismiss logic
        let timer = null;
        let remaining = duration;
        let start = Date.now();

        function dismiss() {
            if (timer) {
                clearTimeout(timer);
                timer = null;
            }
            removeToast(toast);
        }

        function pause() {
            if (timer) {
                clearTimeout(timer);
                timer = null;
                remaining -= (Date.now() - start);
            }
        }

        function resume() {
            if (!timer && remaining > 0) {
                start = Date.now();
                timer = setTimeout(dismiss, remaining);
            }
        }

        if (duration > 0 && duration < Infinity) {
            timer = setTimeout(dismiss, duration);
            toast.addEventListener('mouseenter', pause);
            toast.addEventListener('mouseleave', resume);
            toast.addEventListener('focusin', pause);
            toast.addEventListener('focusout', resume);
        }

        const closeBtn = toast.querySelector('.tit-toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                dismiss();
            });
        }

        container.appendChild(toast);
        return toast;
    }

    function removeToast(toast) {
        if (!toast || toast.dataset.dismissing === 'true') return;
        toast.dataset.dismissing = 'true';
        const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReducedMotion) {
            toast.remove();
        } else {
            toast.classList.add('tit-toast--hiding');
            setTimeout(() => {
                toast.remove();
            }, 200);
        }
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    // 2. Themed Confirm Dialog
    let confirmModalEl = null;

    function getConfirmModal() {
        if (!confirmModalEl || !document.body.contains(confirmModalEl)) {
            confirmModalEl = document.getElementById('tit-confirm-modal');
            if (!confirmModalEl) {
                confirmModalEl = document.createElement('div');
                confirmModalEl.id = 'tit-confirm-modal';
                confirmModalEl.className = 'tit-confirm-overlay';
                confirmModalEl.setAttribute('role', 'dialog');
                confirmModalEl.setAttribute('aria-modal', 'true');
                confirmModalEl.setAttribute('tabindex', '-1');
                confirmModalEl.style.display = 'none';

                confirmModalEl.innerHTML = `
                    <div class="tit-confirm-dialog">
                        <div class="tit-confirm-header">
                            <h5 class="tit-confirm-title" id="tit-confirm-title-text">Are you sure?</h5>
                        </div>
                        <div class="tit-confirm-body" id="tit-confirm-body-text">
                            This action cannot be undone.
                        </div>
                        <div class="tit-confirm-footer">
                            <button type="button" class="btn btn-secondary tit-confirm-btn tit-confirm-cancel-btn" id="tit-confirm-cancel">Cancel</button>
                            <button type="button" class="btn btn-primary tit-confirm-btn tit-confirm-ok-btn" id="tit-confirm-ok">Confirm</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(confirmModalEl);
            }
        }
        return confirmModalEl;
    }

    function showConfirm({ title, text, confirmText = 'Confirm', cancelText = 'Cancel', danger = false }) {
        return new Promise((resolve) => {
            const modal = getConfirmModal();
            const titleEl = document.getElementById('tit-confirm-title-text');
            const bodyEl = document.getElementById('tit-confirm-body-text');
            const cancelBtn = document.getElementById('tit-confirm-cancel');
            const okBtn = document.getElementById('tit-confirm-ok');

            titleEl.textContent = title || 'Are you sure?';
            bodyEl.textContent = text || 'This action cannot be undone.';
            cancelBtn.textContent = cancelText || 'Cancel';
            okBtn.textContent = confirmText || 'Confirm';

            if (danger) {
                okBtn.className = 'btn btn-danger tit-confirm-btn tit-confirm-ok-btn';
            } else {
                okBtn.className = 'btn btn-primary tit-confirm-btn tit-confirm-ok-btn';
            }

            const previousActive = document.activeElement;

            function cleanup(result) {
                document.removeEventListener('keydown', handleKey);
                modal.removeEventListener('click', handleBackdrop);
                cancelBtn.removeEventListener('click', handleCancel);
                okBtn.removeEventListener('click', handleOk);
                modal.style.display = 'none';
                document.body.classList.remove('tit-confirm-open');
                if (previousActive && typeof previousActive.focus === 'function') {
                    previousActive.focus();
                }
                resolve(result);
            }

            function handleCancel() {
                cleanup(false);
            }

            function handleOk() {
                cleanup(true);
            }

            function handleBackdrop(e) {
                if (e.target === modal) {
                    cleanup(false);
                }
            }

            function handleKey(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    cleanup(false);
                } else if (e.key === 'Enter') {
                    if (document.activeElement === cancelBtn) {
                        e.preventDefault();
                        cleanup(false);
                    } else {
                        e.preventDefault();
                        cleanup(true);
                    }
                } else if (e.key === 'Tab') {
                    const focusables = [cancelBtn, okBtn];
                    const first = focusables[0];
                    const last = focusables[focusables.length - 1];

                    if (e.shiftKey) {
                        if (document.activeElement === first) {
                            e.preventDefault();
                            last.focus();
                        }
                    } else {
                        if (document.activeElement === last) {
                            e.preventDefault();
                            first.focus();
                        }
                    }
                }
            }

            cancelBtn.addEventListener('click', handleCancel);
            okBtn.addEventListener('click', handleOk);
            modal.addEventListener('click', handleBackdrop);
            document.addEventListener('keydown', handleKey);

            modal.style.display = 'flex';
            document.body.classList.add('tit-confirm-open');
            if (danger) {
                cancelBtn.focus();
            } else {
                okBtn.focus();
            }
        });
    }

    // 3. TitButton helpers
    const TitButton = {
        setLoading: function (btn, label = 'Saving...') {
            if (!btn) return;
            if (btn.dataset.titOriginalHtml === undefined) {
                btn.dataset.titOriginalHtml = btn.innerHTML;
            }
            if (btn.dataset.titOriginalDisabled === undefined) {
                btn.dataset.titOriginalDisabled = btn.disabled ? 'true' : 'false';
            }
            btn.classList.add('is-loading');
            btn.disabled = true;
            btn.innerHTML = `<i class="fa fa-spinner fa-spin mr-1" aria-hidden="true"></i> <span>${escapeHtml(label)}</span>`;
        },
        clearLoading: function (btn) {
            if (!btn) return;
            btn.classList.remove('is-loading');
            if (btn.dataset.titOriginalHtml !== undefined) {
                btn.innerHTML = btn.dataset.titOriginalHtml;
                delete btn.dataset.titOriginalHtml;
            }
            if (btn.dataset.titOriginalDisabled !== undefined) {
                btn.disabled = btn.dataset.titOriginalDisabled === 'true';
                delete btn.dataset.titOriginalDisabled;
            } else {
                btn.disabled = false;
            }
        }
    };

    // 4. Delegated Event Handlers
    // Handler for .swal-confirm-btn elements (replaces SweetAlert confirm delegation)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.swal-confirm-btn');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const formId = btn.getAttribute('data-form-id');
        const title = btn.getAttribute('data-title') || 'Are you sure?';
        const text = btn.getAttribute('data-text') || 'This action cannot be undone.';
        const confirmText = btn.getAttribute('data-confirm-text') || 'Yes, proceed!';
        const confirmColor = btn.getAttribute('data-confirm-color') || '';
        const danger = /delete|deactivate|cancel|reject/i.test(title) || confirmColor === '#d33';

        showConfirm({
            title,
            text,
            confirmText,
            danger
        }).then(function (ok) {
            if (ok) {
                const form = document.getElementById(formId);
                if (form) form.submit();
            }
        });
    });

    // Delegated handler for forms with class="js-confirm-submit"
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('.js-confirm-submit');
        if (!form) return;

        if (form.dataset.titConfirmed === 'true') {
            form.dataset.titConfirmed = 'false';
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        const title = form.getAttribute('data-title') || 'Are you sure?';
        const text = form.getAttribute('data-text') || 'This action cannot be undone.';
        const confirmText = form.getAttribute('data-confirm-text') || 'Yes, proceed!';
        const danger = form.getAttribute('data-danger') === 'true' || /delete|cancel|reject/i.test(title);

        showConfirm({
            title,
            text,
            confirmText,
            danger
        }).then(function (ok) {
            if (ok) {
                form.dataset.titConfirmed = 'true';
                form.submit();
            }
        });
    });

    // 5. Expose Global APIs
    window.TitNotify = {
        success: (msg, opts) => showToast('success', msg, opts),
        error: (msg, opts) => showToast('error', msg, opts),
        warning: (msg, opts) => showToast('warning', msg, opts),
        info: (msg, opts) => showToast('info', msg, opts),
        confirm: (opts) => showConfirm(opts || {})
    };

    window.TitButton = TitButton;
})();
