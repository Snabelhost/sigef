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
        normalizeTableToolbars();

        const locked = isScrollLocked() || hasVisibleDialog();

        root.classList.toggle('sigef-scroll-locked', locked);
        root.style.setProperty('--sigef-scrollbar-width', '0px');
    }

    function findDirectToolbarControl(group, selectors) {
        return Array.from(group.children).find((element) => {
            return element.matches(selectors) || element.querySelector(selectors);
        }) || null;
    }

    function isVisibleToolbarControl(element) {
        if (!element || element.hidden || element.getAttribute('aria-hidden') === 'true') {
            return false;
        }

        const style = window.getComputedStyle(element);

        return style.display !== 'none' && style.visibility !== 'hidden';
    }

    function isLabeledToolbarControl(element, labels) {
        const label = [
            element.getAttribute('aria-label'),
            element.getAttribute('title'),
            element.getAttribute('data-tooltip'),
            element.textContent,
            ...Array.from(element.querySelectorAll('[aria-label], [title], [data-tooltip]')).flatMap((child) => [
                child.getAttribute('aria-label'),
                child.getAttribute('title'),
                child.getAttribute('data-tooltip'),
            ]),
        ]
            .filter(Boolean)
            .join(' ')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();

        return labels.some((item) => label.includes(item));
    }

    function findToolbarControl(group, selectors, labels = []) {
        return findDirectToolbarControl(group, selectors) || Array.from(group.children).find((element) => {
            return labels.length && isLabeledToolbarControl(element, labels);
        }) || null;
    }

    function findTableFilterControl(toolbar, controlsGroup, selectors) {
        const directControl = findToolbarControl(controlsGroup, selectors, ['filtro', 'filter']);

        if (directControl) {
            return directControl;
        }

        const table = toolbar.closest('.fi-ta');

        if (!table) {
            return null;
        }

        return Array.from(table.querySelectorAll(selectors)).find((element) => {
            return !controlsGroup.contains(element) &&
                !element.closest('.fi-modal, .fi-dropdown-panel') &&
                isVisibleToolbarControl(element);
        }) || null;
    }

    function normalizeTableToolbars() {
        document.querySelectorAll('.fi-ta-header-toolbar').forEach((toolbar) => {
            const controlsGroup = Array.from(toolbar.children).find((element) => {
                return element.classList.contains('sigef-table-toolbar-controls') ||
                    element.querySelector('.fi-ta-search-field, .fi-ta-filters-dropdown, .fi-ta-filters-modal, .fi-ta-filters-trigger-action-ctn, .fi-ta-col-manager-dropdown, .fi-ta-col-manager-modal, .fi-ta-col-manager');
            });

            if (!controlsGroup) {
                return;
            }

            controlsGroup.classList.add('sigef-table-toolbar-controls');

            const search = findToolbarControl(controlsGroup, '.fi-ta-search-field');
            const columns = findToolbarControl(
                controlsGroup,
                '.fi-ta-col-manager-dropdown, .fi-ta-col-manager-modal, .fi-ta-col-manager',
                ['alternar colunas', 'colunas', 'toggle columns', 'columns', 'column']
            );
            const filters = findTableFilterControl(
                toolbar,
                controlsGroup,
                '.fi-ta-filters-dropdown, .fi-ta-filters-modal, .fi-ta-filters-trigger-action-ctn'
            );
            const expectedOrder = [search, columns, filters].filter(Boolean);

            if (expectedOrder.length < 2) {
                return;
            }

            const currentOrder = Array.from(controlsGroup.children).filter((element) => expectedOrder.includes(element));
            const alreadyOrdered = currentOrder.length === expectedOrder.length &&
                currentOrder.every((element, index) => element === expectedOrder[index]);

            if (alreadyOrdered) {
                return;
            }

            expectedOrder.forEach((element) => controlsGroup.appendChild(element));
        });
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
