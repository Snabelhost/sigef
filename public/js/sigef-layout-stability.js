(function () {
    'use strict';

    if (window.__sigefLayoutStabilityInitialized) {
        return;
    }

    window.__sigefLayoutStabilityInitialized = true;

    const root = document.documentElement;
    const manualLocks = new Set();
    let originalBodyOverflow = null;
    let syncQueued = false;

    function hasVisibleDialog() {
        return Array.from(document.querySelectorAll('.fi-modal, [role="dialog"], .sigef-photo-capture-modal')).some((element) => {
            if (element.hidden || element.getAttribute('aria-hidden') === 'true') {
                return false;
            }

            const style = window.getComputedStyle(element);

            return style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity || 1) !== 0;
        });
    }

    function isScrollLocked() {
        const bodyStyle = window.getComputedStyle(document.body);

        return manualLocks.size > 0 ||
            document.body.style.overflow === 'hidden' ||
            bodyStyle.overflow === 'hidden' ||
            bodyStyle.overflowY === 'hidden' ||
            root.style.overflow === 'hidden';
    }

    function sync() {
        const locked = isScrollLocked() || hasVisibleDialog();

        root.classList.toggle('sigef-scroll-locked', locked);
        root.style.setProperty('--sigef-scrollbar-width', '0px');
    }

    function setLocked(name, locked) {
        const key = name || 'manual';

        if (locked) {
            manualLocks.add(key);

            if (manualLocks.size === 1) {
                originalBodyOverflow = document.body.style.overflow;
            }

            document.body.style.overflow = 'hidden';
            sync();
            return;
        }

        manualLocks.delete(key);

        if (manualLocks.size === 0) {
            document.body.style.overflow = originalBodyOverflow || '';

            originalBodyOverflow = null;
        }

        sync();
    }

    window.SigefLayoutStability = {
        sync,
        setLocked,
    };

    function scheduleSync() {
        if (syncQueued) {
            return;
        }

        syncQueued = true;

        window.requestAnimationFrame(() => {
            syncQueued = false;
            sync();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', sync);
    } else {
        sync();
    }

    window.addEventListener('resize', scheduleSync);
    window.addEventListener('load', scheduleSync);

    new MutationObserver(scheduleSync).observe(document.documentElement, {
        attributes: true,
        childList: true,
        subtree: true,
        attributeFilter: ['style', 'class', 'hidden', 'aria-hidden'],
    });
})();
