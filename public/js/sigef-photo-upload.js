(function () {
    'use strict';

    if (window.__sigefPhotoUploadInitialized) {
        return;
    }

    window.__sigefPhotoUploadInitialized = true;

    const FIELD_SELECTOR = '.sigef-trainer-photo-upload[data-sigef-photo-upload]';
    const ACTION_ATTR = 'data-sigef-photo-action';
    const PREVIEW_ATTR = 'data-sigef-photo-preview';
    const MODAL_ID = 'sigef-photo-capture-modal';
    const PREVIEW_MODAL_ID = 'sigef-photo-preview-modal';

    const modalState = {
        root: null,
        video: null,
        status: null,
        stream: null,
        targetInput: null,
    };

    const previewState = {
        root: null,
        image: null,
        name: null,
    };

    const cameraIcon =
        '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.75 5.5a1 1 0 0 0-.8.4l-1 1.35H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1.95l-1-1.35a1 1 0 0 0-.8-.4h-6.5Zm3.25 3.25a4.25 4.25 0 1 1 0 8.5a4.25 4.25 0 0 1 0-8.5Zm0 1.5a2.75 2.75 0 1 0 0 5.5a2.75 2.75 0 0 0 0-5.5Z"/></svg>';
    const uploadIcon =
        '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a.75.75 0 0 1 .75.75v8.69l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V3.75A.75.75 0 0 1 12 3ZM5.75 15a.75.75 0 0 1 .75.75V18a1 1 0 0 0 1 1h9a1 1 0 0 0 1-1v-2.25a.75.75 0 0 1 1.5 0V18a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 5 18v-2.25a.75.75 0 0 1 .75-.75Z"/></svg>';
    const closeIcon =
        '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.72 5.66a.75.75 0 0 0-1.06 1.06L10.94 12l-5.28 5.28a.75.75 0 1 0 1.06 1.06L12 13.06l5.28 5.28a.75.75 0 1 0 1.06-1.06L13.06 12l5.28-5.28a.75.75 0 0 0-1.06-1.06L12 10.94L6.72 5.66Z"/></svg>';

    function getFileInput(field) {
        if (!field) {
            return null;
        }

        return (
            field.querySelector('input[type="file"][data-sigef-photo-input="true"]') ||
            field.querySelector('input[type="file"].filepond--browser') ||
            field.querySelector('input[type="file"]')
        );
    }

    function isDisabledInput(input) {
        return !input || input.disabled || input.hasAttribute('disabled') || input.getAttribute('aria-disabled') === 'true';
    }

    function findFieldFromElement(element) {
        const directField = element.closest(FIELD_SELECTOR);

        if (directField) {
            return directField;
        }

        const scope =
            element.closest('[role="dialog"]') ||
            element.closest('form') ||
            document;

        return scope.querySelector(FIELD_SELECTOR);
    }

    function setStatus(message) {
        if (!modalState.status) {
            return;
        }

        modalState.status.textContent = message || '';
        modalState.status.hidden = !message;
    }

    function stopStream() {
        if (!modalState.stream) {
            return;
        }

        for (const track of modalState.stream.getTracks()) {
            track.stop();
        }

        modalState.stream = null;

        if (modalState.video) {
            modalState.video.srcObject = null;
        }
    }

    function closeModal() {
        if (!modalState.root) {
            return;
        }

        stopStream();
        modalState.root.classList.remove('is-open');
        modalState.root.setAttribute('aria-hidden', 'true');
        window.SigefLayoutStability?.setLocked('photo-capture', false);
        modalState.targetInput = null;
        setStatus('');
    }

    function closePreviewModal() {
        if (!previewState.root) {
            return;
        }

        previewState.root.classList.remove('is-open');
        previewState.root.setAttribute('aria-hidden', 'true');
        window.SigefLayoutStability?.setLocked('photo-preview', false);

        if (previewState.image) {
            previewState.image.removeAttribute('src');
            previewState.image.removeAttribute('alt');
        }
    }

    function setInputFile(input, file) {
        try {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            input.files = dataTransfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (error) {
            setStatus('Falha ao enviar a foto para o campo.');
        }
    }

    function openUploadPicker(input) {
        if (isDisabledInput(input)) {
            return;
        }

        input.setAttribute('accept', 'image/*');
        input.removeAttribute('capture');
        input.click();
    }

    function openCaptureFallback(input) {
        if (isDisabledInput(input)) {
            return;
        }

        input.setAttribute('accept', 'image/*');
        input.setAttribute('capture', 'user');
        input.click();
    }

    function captureFromVideo() {
        const input = modalState.targetInput;
        const video = modalState.video;

        if (!input || !video || video.readyState < 2) {
            setStatus('A webcam ainda nao esta pronta.');
            return;
        }

        const videoWidth = video.videoWidth || 0;
        const videoHeight = video.videoHeight || 0;

        if (!videoWidth || !videoHeight) {
            setStatus('Nao foi possivel capturar a imagem da webcam.');
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = videoWidth;
        canvas.height = videoHeight;

        const context = canvas.getContext('2d');

        if (!context) {
            setStatus('Nao foi possivel preparar a imagem.');
            return;
        }

        context.drawImage(video, 0, 0, videoWidth, videoHeight);

        canvas.toBlob(
            (blob) => {
                if (!blob) {
                    setStatus('Nao foi possivel gerar a foto capturada.');
                    return;
                }

                const file = new File([blob], 'foto-capturada.jpg', { type: 'image/jpeg' });
                setInputFile(input, file);
                closeModal();
            },
            'image/jpeg',
            0.92,
        );
    }

    async function openCamera(input) {
        if (isDisabledInput(input)) {
            return;
        }

        ensureModal();

        modalState.targetInput = input;
        modalState.root.classList.add('is-open');
        modalState.root.setAttribute('aria-hidden', 'false');
        window.SigefLayoutStability?.setLocked('photo-capture', true);
        setStatus('A iniciar webcam...');

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('Este navegador nao suporta webcam. A abrir captura do dispositivo...');
            openCaptureFallback(input);
            setTimeout(closeModal, 500);
            return;
        }

        stopStream();

        try {
            modalState.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                audio: false,
            });

            modalState.video.srcObject = modalState.stream;
            await modalState.video.play();
            setStatus('');
        } catch (error) {
            setStatus('Nao foi possivel aceder a webcam. Verifique as permissoes.');
        }
    }

    function ensureModal() {
        if (modalState.root) {
            return;
        }

        const existing = document.getElementById(MODAL_ID);

        if (existing) {
            modalState.root = existing;
            modalState.video = existing.querySelector('[data-sigef-photo-video]');
            modalState.status = existing.querySelector('[data-sigef-photo-status]');
            return;
        }

        const modal = document.createElement('div');
        modal.id = MODAL_ID;
        modal.className = 'sigef-photo-capture-modal';
        modal.setAttribute('aria-hidden', 'true');

        modal.innerHTML = `
            <div class="sigef-photo-capture-backdrop" data-sigef-photo-close="true"></div>
            <div class="sigef-photo-capture-dialog" role="dialog" aria-modal="true" aria-labelledby="sigef-photo-capture-title">
                <div class="sigef-photo-capture-header">
                    <h3 id="sigef-photo-capture-title">Capturar Foto</h3>
                    <button type="button" class="sigef-photo-capture-close" data-sigef-photo-close="true" aria-label="Fechar">${closeIcon}</button>
                </div>
                <div class="sigef-photo-capture-body">
                    <video data-sigef-photo-video autoplay playsinline muted></video>
                    <div class="sigef-photo-capture-grid"></div>
                </div>
                <p class="sigef-photo-capture-status" data-sigef-photo-status hidden></p>
                <div class="sigef-photo-capture-actions">
                    <button type="button" class="sigef-photo-action sigef-photo-action-secondary" data-sigef-photo-modal-action="choose-file">${uploadIcon}<span>Carregar foto</span></button>
                    <button type="button" class="sigef-photo-action sigef-photo-action-primary" data-sigef-photo-modal-action="capture">${cameraIcon}<span>Capturar</span></button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        modal.addEventListener('click', (event) => {
            const closeTarget = event.target.closest('[data-sigef-photo-close="true"]');

            if (closeTarget) {
                closeModal();
                return;
            }

            const actionButton = event.target.closest('[data-sigef-photo-modal-action]');

            if (!actionButton) {
                return;
            }

            const action = actionButton.getAttribute('data-sigef-photo-modal-action');

            if (action === 'capture') {
                captureFromVideo();
                return;
            }

            if (action === 'choose-file') {
                openUploadPicker(modalState.targetInput);
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modalState.root?.classList.contains('is-open')) {
                closeModal();
            }
        });

        modalState.root = modal;
        modalState.video = modal.querySelector('[data-sigef-photo-video]');
        modalState.status = modal.querySelector('[data-sigef-photo-status]');
    }

    function ensurePreviewModal() {
        if (previewState.root) {
            return;
        }

        const existing = document.getElementById(PREVIEW_MODAL_ID);

        if (existing) {
            previewState.root = existing;
            previewState.image = existing.querySelector('[data-sigef-photo-preview-image]');
            previewState.name = existing.querySelector('[data-sigef-photo-preview-name]');
            return;
        }

        const modal = document.createElement('div');
        modal.id = PREVIEW_MODAL_ID;
        modal.className = 'sigef-photo-preview-modal';
        modal.setAttribute('aria-hidden', 'true');

        modal.innerHTML = `
            <div class="sigef-photo-preview-backdrop" data-sigef-photo-preview-close="true"></div>
            <div class="sigef-photo-preview-dialog" role="dialog" aria-modal="true" aria-label="Visualizar foto">
                <button type="button" class="sigef-photo-preview-close" data-sigef-photo-preview-close="true" aria-label="Fechar">X</button>
                <div class="sigef-photo-preview-frame">
                    <img data-sigef-photo-preview-image alt="">
                </div>
                <p class="sigef-photo-preview-name" data-sigef-photo-preview-name></p>
            </div>
        `;

        document.body.appendChild(modal);

        modal.addEventListener('click', (event) => {
            if (event.target.closest('[data-sigef-photo-preview-close="true"]')) {
                closePreviewModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && previewState.root?.classList.contains('is-open')) {
                closePreviewModal();
            }
        });

        previewState.root = modal;
        previewState.image = modal.querySelector('[data-sigef-photo-preview-image]');
        previewState.name = modal.querySelector('[data-sigef-photo-preview-name]');
    }

    function openPhotoPreview(button) {
        const photoUrl = button.getAttribute('data-sigef-photo-url') || '';
        const photoName = button.getAttribute('data-sigef-photo-name') || '';

        if (!photoUrl) {
            return;
        }

        ensurePreviewModal();

        previewState.image.src = photoUrl;
        previewState.image.alt = photoName ? `Foto de ${photoName}` : 'Foto';
        previewState.name.textContent = photoName;
        previewState.root.classList.add('is-open');
        previewState.root.setAttribute('aria-hidden', 'false');
        window.SigefLayoutStability?.setLocked('photo-preview', true);
    }

    function setupActionDelegation() {
        document.addEventListener(
            'click',
            (event) => {
                const previewButton = event.target.closest(`[${PREVIEW_ATTR}="true"]`);

                if (previewButton) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (typeof event.stopImmediatePropagation === 'function') {
                        event.stopImmediatePropagation();
                    }

                    openPhotoPreview(previewButton);
                    return;
                }

                const previewGroup = event.target.closest('.sigef-trainer-photo-view-group');

                if (!previewGroup) {
                    return;
                }

                if (event.target.closest(`[${ACTION_ATTR}], a, select, textarea, button:not([${PREVIEW_ATTR}="true"])`)) {
                    return;
                }

                const groupPreviewButton = previewGroup.querySelector(`[${PREVIEW_ATTR}="true"]`);

                if (!groupPreviewButton) {
                    return;
                }

                const targetInput = event.target.closest('input');

                if (targetInput && !isDisabledInput(targetInput)) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                if (typeof event.stopImmediatePropagation === 'function') {
                    event.stopImmediatePropagation();
                }

                openPhotoPreview(groupPreviewButton);
            },
            true,
        );

        document.addEventListener('click', (event) => {
            const previewButton = event.target.closest(`[${PREVIEW_ATTR}="true"]`);

            if (previewButton) {
                event.preventDefault();
                openPhotoPreview(previewButton);
                return;
            }

            const button = event.target.closest(`[${ACTION_ATTR}]`);

            if (!button) {
                return;
            }

            const field = findFieldFromElement(button);
            const input = getFileInput(field);

            if (isDisabledInput(input)) {
                return;
            }

            event.preventDefault();

            const action = button.getAttribute(ACTION_ATTR);

            if (action === 'upload') {
                openUploadPicker(input);
                return;
            }

            if (action === 'capture') {
                openCamera(input);
            }
        });

        document.addEventListener(
            'click',
            (event) => {
                const dropLabel = event.target.closest(`${FIELD_SELECTOR} .filepond--drop-label`);

                if (!dropLabel) {
                    return;
                }

                const field = dropLabel.closest(FIELD_SELECTOR);
                const input = getFileInput(field);

                if (isDisabledInput(input)) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                if (typeof event.stopImmediatePropagation === 'function') {
                    event.stopImmediatePropagation();
                }

                openCamera(input);
            },
            true,
        );
    }

    function init() {
        setupActionDelegation();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
