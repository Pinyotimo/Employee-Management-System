document.addEventListener('DOMContentLoaded', () => {
    initNavToggle();
    initLiveTimers();
    initAutoDismissAlerts();
    initCopyButtons();
    initCollapsibleSections();
    initManagedForms();
    initPrintButtons();
});

function initNavToggle() {
    const navToggle = document.querySelector('.nav-toggle');
    const navPanel = document.querySelector('.nav-panel');

    if (!navToggle || !navPanel) {
        return;
    }

    const closeMenu = () => {
        navToggle.setAttribute('aria-expanded', 'false');
        navPanel.classList.remove('is-open');
    };

    navToggle.addEventListener('click', () => {
        const expanded = navToggle.getAttribute('aria-expanded') === 'true';
        navToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        navPanel.classList.toggle('is-open');
    });

    document.addEventListener('click', (event) => {
        if (!navPanel.classList.contains('is-open')) {
            return;
        }

        if (!navPanel.contains(event.target) && !navToggle.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });
}

function formatDuration(totalSeconds) {
    const safeSeconds = Math.max(0, Math.floor(totalSeconds));
    const hours = String(Math.floor(safeSeconds / 3600)).padStart(2, '0');
    const minutes = String(Math.floor((safeSeconds % 3600) / 60)).padStart(2, '0');
    const seconds = String(safeSeconds % 60).padStart(2, '0');
    return `${hours}:${minutes}:${seconds}`;
}

function computeElapsedSeconds(node, now) {
    const startTime = Number(node.dataset.startTime || 0);
    const breakStart = Number(node.dataset.breakStart || 0);
    const baseBreakSeconds = Number(node.dataset.breakSeconds || 0);
    const isPaused = node.dataset.paused === 'true';
    const currentBreakSeconds = isPaused && breakStart > 0 ? Math.max(0, now - breakStart) : 0;

    return Math.max(0, now - startTime - baseBreakSeconds - currentBreakSeconds);
}

function initLiveTimers() {
    const timerNodes = document.querySelectorAll('[data-target="timer"]');
    const workedHourNodes = document.querySelectorAll('[data-target="worked-hours"]');
    const breakTimerNodes = document.querySelectorAll('[data-target="break-timer"]');

    if (timerNodes.length === 0 && workedHourNodes.length === 0 && breakTimerNodes.length === 0) {
        return;
    }

    const updateTimers = () => {
        const now = Math.floor(Date.now() / 1000);

        timerNodes.forEach((node) => {
            node.textContent = formatDuration(computeElapsedSeconds(node, now));
        });

        workedHourNodes.forEach((node) => {
            const baseHours = Number(node.dataset.baseHours || 0);
            const startTime = Number(node.dataset.startTime || 0);
            const extraHours = startTime > 0 ? computeElapsedSeconds(node, now) / 3600 : 0;
            const prefix = node.dataset.labelPrefix || '';
            node.textContent = `${prefix}${(baseHours + extraHours).toFixed(2)} hrs`;
        });

        breakTimerNodes.forEach((node) => {
            const breakStart = Number(node.dataset.breakStart || 0);
            const prefix = node.dataset.labelPrefix || '';
            const currentBreakSeconds = breakStart > 0 ? Math.max(0, now - breakStart) : 0;
            node.textContent = `${prefix}${formatDuration(currentBreakSeconds)}`;
        });
    };

    updateTimers();
    window.setInterval(updateTimers, 1000);
}

function initAutoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');

    alerts.forEach((alert) => {
        const delay = Number(alert.dataset.autoDismiss || 0);
        if (delay <= 0) {
            return;
        }

        window.setTimeout(() => {
            alert.classList.add('is-hiding');
            window.setTimeout(() => {
                alert.remove();
            }, 220);
        }, delay);
    });
}

function initCopyButtons() {
    const buttons = document.querySelectorAll('[data-copy-value]');

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            const copyValue = button.dataset.copyValue || '';
            const feedbackTarget = button.dataset.copyFeedback
                ? document.querySelector(button.dataset.copyFeedback)
                : null;
            const successMessage = button.dataset.copySuccess || 'Copied to clipboard.';
            const failureMessage = 'Copy failed. Please copy it manually.';
            const originalLabel = button.textContent;

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(copyValue);
                } else {
                    const tempInput = document.createElement('textarea');
                    tempInput.value = copyValue;
                    tempInput.setAttribute('readonly', 'true');
                    tempInput.style.position = 'absolute';
                    tempInput.style.left = '-9999px';
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    tempInput.remove();
                }

                button.textContent = 'Copied';
                if (feedbackTarget) {
                    feedbackTarget.textContent = successMessage;
                }
            } catch (error) {
                if (feedbackTarget) {
                    feedbackTarget.textContent = failureMessage;
                }
            }

            window.setTimeout(() => {
                button.textContent = originalLabel;
            }, 1400);
        });
    });
}

function initCollapsibleSections() {
    const toggles = document.querySelectorAll('[data-section-toggle]');

    toggles.forEach((toggle) => {
        const sectionSelector = toggle.dataset.sectionToggle;
        const sectionBody = sectionSelector ? document.querySelector(sectionSelector) : null;

        if (!sectionBody) {
            return;
        }

        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            sectionBody.hidden = expanded;
            toggle.textContent = expanded ? 'Show' : 'Hide';
        });
    });
}

function initManagedForms() {
    const managedForms = document.querySelectorAll('form[data-managed-submit]');

    managedForms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            const confirmationMessage = form.dataset.confirmMessage;
            if (confirmationMessage && !window.confirm(confirmationMessage)) {
                event.preventDefault();
                return;
            }

            const submitButton = form.querySelector('[type="submit"]');
            if (!submitButton) {
                return;
            }

            const loadingLabel = submitButton.dataset.loadingLabel || 'Working...';
            submitButton.dataset.originalLabel = submitButton.textContent;
            submitButton.textContent = loadingLabel;
            submitButton.disabled = true;
        });
    });
}

function initPrintButtons() {
    const buttons = document.querySelectorAll('[data-print-profile]');

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            window.print();
        });
    });
}
