<x-filament-panels::page>
    @if($this->hasInfolist())
    <div id="doc-view-layout">
        <div class="drag-handle-hint">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L11 6.414V13.586l1.293-1.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 011.414-1.414L9 13.586V6.414L7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3z" clip-rule="evenodd" />
            </svg>
            Arraste as secções para reorganizar a página
        </div>

        <div class="doc-columns">
            {{-- Left column: Conversação --}}
            <div id="col-left" class="doc-col doc-col-left"></div>
            {{-- Right column: Informações + Conteúdo + Anexos --}}
            <div id="col-right" class="doc-col doc-col-right"></div>
        </div>

        {{-- Render the infolist hidden, then JS distributes sections --}}
        <div id="infolist-source" style="display:none;">
            {{ $this->infolist }}
        </div>
    </div>
    @endif

    <style>
        .drag-handle-hint {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            color: #3b82f6;
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }

        .dark .drag-handle-hint {
            background: rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }

        .doc-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .doc-columns {
                grid-template-columns: 1fr;
            }
        }

        .doc-col {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            min-height: 100px;
            border-radius: 12px;
            padding: 4px;
            transition: background 0.2s ease;
        }

        .doc-col.sortable-drag-over {
            background: rgba(59, 130, 246, 0.03);
        }

        .doc-col>* {
            cursor: grab;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .doc-col>*:active {
            cursor: grabbing;
        }

        .sortable-ghost {
            opacity: 0.35;
            background: rgba(59, 130, 246, 0.05) !important;
            border: 2px dashed rgba(59, 130, 246, 0.4) !important;
            border-radius: 12px;
        }

        .sortable-drag {
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.12) !important;
            transform: rotate(0.5deg);
            border-radius: 12px;
            z-index: 9999;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const source = document.querySelector('#infolist-source > div');
            const colLeft = document.getElementById('col-left');
            const colRight = document.getElementById('col-right');

            if (!source || !colLeft || !colRight) return;

            const url = window.location.pathname;
            const storageKey = 'doc-layout-v2-' + url;

            // Collect all sections from the infolist
            const allSections = Array.from(source.children);

            // Assign IDs based on heading text
            allSections.forEach(function(section, idx) {
                const heading = section.querySelector('.fi-section-header-heading, h3, [class*="heading"]');
                const text = heading ? heading.textContent.trim().toLowerCase() : '';

                if (text.includes('conversa')) {
                    section.dataset.sectionId = 'conversacao';
                } else if (text.includes('conte')) {
                    section.dataset.sectionId = 'conteudo';
                } else if (text.includes('anexo')) {
                    section.dataset.sectionId = 'anexos';
                } else if (text.includes('informa')) {
                    section.dataset.sectionId = 'informacoes';
                } else {
                    section.dataset.sectionId = 'section-' + idx;
                }
            });

            // Default layout: Conversação left; Informações + Conteúdo + Anexos right
            const defaultLeft = ['conversacao'];
            const defaultRight = ['informacoes', 'conteudo', 'anexos'];

            // Try to restore saved layout
            let layout = null;
            try {
                layout = JSON.parse(localStorage.getItem(storageKey));
            } catch (e) {}

            if (layout && layout.left && layout.right) {
                // Restore saved layout
                const sectionMap = {};
                allSections.forEach(function(s) {
                    sectionMap[s.dataset.sectionId] = s;
                });

                layout.left.forEach(function(id) {
                    if (sectionMap[id]) colLeft.appendChild(sectionMap[id]);
                });
                layout.right.forEach(function(id) {
                    if (sectionMap[id]) colRight.appendChild(sectionMap[id]);
                });

                // Any sections not in saved layout go to right column
                allSections.forEach(function(s) {
                    if (!s.parentElement || s.parentElement === source) {
                        colRight.appendChild(s);
                    }
                });
            } else {
                // Default layout
                allSections.forEach(function(section) {
                    if (defaultLeft.includes(section.dataset.sectionId)) {
                        colLeft.appendChild(section);
                    } else {
                        colRight.appendChild(section);
                    }
                });
            }

            // Save current layout
            function saveLayout() {
                const leftIds = Array.from(colLeft.children).map(function(el) {
                    return el.dataset.sectionId;
                });
                const rightIds = Array.from(colRight.children).map(function(el) {
                    return el.dataset.sectionId;
                });
                localStorage.setItem(storageKey, JSON.stringify({
                    left: leftIds,
                    right: rightIds
                }));
            }

            // Make both columns sortable with cross-column dragging
            const sortableOptions = {
                group: 'doc-sections',
                animation: 200,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                handle: '.fi-section-header, .fi-section-header-ctn',
                delay: 80,
                delayOnTouchOnly: true,
                onEnd: saveLayout
            };

            new Sortable(colLeft, sortableOptions);
            new Sortable(colRight, sortableOptions);
        });
    </script>
</x-filament-panels::page>