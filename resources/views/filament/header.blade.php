{{-- Layout SIGEF com botão dentro do enquadramento --}}
<style>
    /* Esconder o título padrão do Filament */
    .fi-header-heading {
        display: none !important;
    }

    /* Esconder os breadcrumbs */
    .fi-breadcrumbs {
        display: none !important;
    }

    /* ========== SIDEBAR / MENU LATERAL ========== */
    /* Item ativo do menu */
    .fi-sidebar-item-active {
        background-color: #041842 !important;
    }

    .fi-sidebar-item-active .fi-sidebar-item-label {
        color: white !important;
    }

    .fi-sidebar-item-active .fi-sidebar-item-icon {
        color: white !important;
    }

    /* Hover nos itens do menu */
    .fi-sidebar-item:hover {
        background-color: rgba(4, 24, 66, 0.1) !important;
    }

    /* Grupo ativo do menu */
    .fi-sidebar-group-button[aria-expanded="true"] {
        color: #041842 !important;
    }

    /* Ícones do menu */
    .fi-sidebar-item-icon {
        color: #041842 !important;
    }

    .fi-sidebar-item-active .fi-sidebar-item-icon {
        color: white !important;
    }

    /* Container principal - SIGEF ficará acima da tabela */
    .sigef-full-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 3rem;
        margin-bottom: 0.5rem;
    }

    .sigef-full-header.sigef-dashboard-header-hidden {
        display: none !important;
    }

    .sigef-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* Esconder o header padrão do Filament completamente */
    .fi-header {
        display: none !important;
    }

    /* Reduzir espaçamento da tabela */
    .fi-ta-ctn {
        margin-top: -1rem !important;
    }

    /* Reduzir espaçamento entre seções */
    .fi-widgets {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }

    .fi-wi-stats-overview {
        margin-top: 1.5rem !important;
    }

    .fi-page-content>div {
        gap: 0.5rem !important;
    }

    .filament-widgets-container {
        margin-top: 0 !important;
    }

    /* Ajustar filtros do dashboard */
    .fi-dashboard-page-filters {
        margin-top: 1rem !important;
        margin-bottom: 1.5rem !important;
    }

    .fi-dashboard-page-filters .fi-section {
        padding: 0 !important;
        border-radius: 0.5rem !important;
        overflow: hidden !important;
    }

    /* Estilo do botão customizado */
    .sigef-create-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1rem;
        background-color: #041842;
        color: white;
        font-size: 0.875rem;
        font-weight: 600;
        border-radius: 0.5rem;
        text-decoration: none;
        transition: background-color 0.2s;
        gap: 0.5rem;
    }

    .sigef-create-btn:hover {
        background-color: #0a2d5c;
        color: white;
    }

    .sigef-create-btn svg {
        width: 1rem;
        height: 1rem;
    }
</style>

<div class="sigef-full-header {{ request()->is('admin') || request()->is('admin/') || request()->is('admin/attendance-management') || request()->is('escola/*/attendance-management') || request()->is('admin/shield/roles/create') || request()->is('admin/shield/roles/*') ? 'sigef-dashboard-header-hidden' : '' }}">
    {{-- Lado Esquerdo: Ícone + Texto (dinâmico por página) --}}
    <div class="sigef-header-left">
        {{-- Container para o ícone --}}
        <div id="sigef-dynamic-icon" style="flex-shrink: 0; color: #041842; width: 60px; height: 60px; min-width: 60px; min-height: 60px;">
            {{-- Ícone padrão - será substituído pelo JS --}}
            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 60px !important; height: 60px !important; min-width: 60px !important; min-height: 60px !important;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
        </div>

        {{-- Texto dinâmico --}}
        <div>
            <h1 id="sigef-dynamic-title" style="color: #041842; font-size: 1.75rem; font-weight: 700; margin: 0; line-height: 1.2;">
                Painel de Controlo
            </h1>
            <p id="sigef-dynamic-description" style="color: #6b7280; font-size: 1rem; margin: 0; line-height: 1.4;">
                Visão geral do sistema
            </p>
        </div>
    </div>

    {{-- Lado Direito: Botão de criar --}}
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Função para obter informações dinâmicas da página
        function updateDynamicHeader() {
            const titleEl = document.getElementById('sigef-dynamic-title');
            const descEl = document.getElementById('sigef-dynamic-description');
            const iconEl = document.getElementById('sigef-dynamic-icon');
            const headerEl = document.querySelector('.sigef-full-header');

            // Verificar se é a página do dashboard pela URL
            const currentPath = window.location.pathname;
            const isAdminDashboard = currentPath === '/admin' || currentPath === '/admin/' || currentPath.endsWith('/admin');
            const isAccessForm = currentPath === '/admin/shield/roles/create' || /^\/admin\/shield\/roles\/[^/]+(?:\/edit)?$/.test(currentPath);
            const isAttendanceManagement = currentPath === '/admin/attendance-management' || /^\/escola\/\d+\/attendance-management\/?$/.test(currentPath);
            const isDashboard = currentPath === '/admin' || currentPath === '/admin/' || currentPath.endsWith('/admin') || /^\/escola\/\d+\/?$/.test(currentPath);

            if (headerEl) {
                headerEl.classList.toggle('sigef-dashboard-header-hidden', isAdminDashboard || isAccessForm || isAttendanceManagement);
            }

            if (isAdminDashboard || isAccessForm || isAttendanceManagement) {
                return;
            }

            if (isDashboard && titleEl && descEl && iconEl) {
                titleEl.textContent = 'Painel de Controlo';
                descEl.textContent = 'Visão geral do sistema';
                // Ícone de velocímetro (fa-tachometer-alt)
                iconEl.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 512 512" fill="currentColor" style="width: 60px !important; height: 60px !important; min-width: 60px !important; min-height: 60px !important;">
                <path d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zM288 96a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zM256 416c35.3 0 64-28.7 64-64 0-16.2-6-31.1-16-42.3l69.5-138.9c5.9-11.9 1.1-26.3-10.7-32.2s-26.3-1.1-32.2 10.7L261.1 288.2c-1.7-.1-3.4-.2-5.1-.2-35.3 0-64 28.7-64 64s28.7 64 64 64zM176 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zM96 288a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm352-32a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/>
            </svg>`;
                return; // Não precisa continuar
            }

            // Overrides específicos por URL (para páginas de criação/edição)
            const pageOverrides = {
                '/documents/create': {
                    title: 'Novo Documento',
                    description: 'Criar e enviar um novo documento',
                    icon: `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 60px !important; height: 60px !important;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                </svg>`
                },
                '/mail-settings': {
                    title: 'Servidor de E-mail',
                    description: 'Configurações do servidor de e-mail',
                    icon: `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="currentColor" style="width: 60px !important; height: 60px !important;">
                    <path fill-rule="evenodd" d="M17.834 6.166a8.25 8.25 0 100 11.668.75.75 0 011.06 1.06c-3.807 3.808-9.98 3.808-13.788 0-3.808-3.807-3.808-9.98 0-13.788 3.807-3.808 9.98-3.808 13.788 0A9.722 9.722 0 0121.75 12c0 .975-.296 1.887-.809 2.571-.514.685-1.28 1.179-2.191 1.179-.904 0-1.666-.487-2.18-1.164a5.25 5.25 0 11-.82-6.26V8.25a.75.75 0 011.5 0V12c0 .682.208 1.27.509 1.671.3.401.659.579.991.579.332 0 .69-.178.991-.579.3-.4.509-.99.509-1.671a8.222 8.222 0 00-2.416-5.834zM15.75 12a3.75 3.75 0 10-7.5 0 3.75 3.75 0 007.5 0z" clip-rule="evenodd" />
                </svg>`
                }
            };

            // Overrides com regex (para URLs dinâmicas como /documents/{id}/edit)
            const regexOverrides = [{
                    pattern: /\/documents\/\d+\/edit/,
                    title: 'Editar Documento',
                    description: 'Modificar dados do documento',
                    icon: `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 60px !important; height: 60px !important;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>`
                },
                {
                    pattern: /\/documents\/\d+$/,
                    title: 'Visualizar Documento',
                    description: 'Detalhes do documento',
                    icon: `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 60px !important; height: 60px !important;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>`
                }
            ];

            // Verificar overrides por URL exacta
            for (const [urlPart, config] of Object.entries(pageOverrides)) {
                if (currentPath.includes(urlPart)) {
                    if (titleEl) titleEl.textContent = config.title;
                    if (descEl) descEl.textContent = config.description;
                    if (iconEl) iconEl.innerHTML = config.icon;
                    return;
                }
            }

            // Verificar overrides com regex
            for (const override of regexOverrides) {
                if (override.pattern.test(currentPath)) {
                    if (titleEl) titleEl.textContent = override.title;
                    if (descEl) descEl.textContent = override.description;
                    if (iconEl) iconEl.innerHTML = override.icon;
                    return;
                }
            }

            // Obter título do item ativo no menu
            const activeMenuItem = document.querySelector('.fi-sidebar-item-active .fi-sidebar-item-label');

            if (activeMenuItem && titleEl) {
                const title = activeMenuItem.textContent.trim();
                titleEl.textContent = title;

                // Definir descrições baseadas no título
                const descriptions = {
                    'Painel de Controlo': 'Visão geral do sistema',
                    'Painel de Controlo': 'Visão geral do sistema',
                    'Anos Académicos': 'Gestão dos períodos letivos',
                    'Tipos de Instituição': 'Classificação das instituições',
                    'Órgãos de Proveniência': 'Órgãos, unidades e proveniências registadas no sistema',
                    'Patentes': 'Gestão de patentes militares',
                    'Candidatos': 'Gestão de candidatos ao ingresso',
                    'Alistados': 'Gestão de candidatos ao ingresso',
                    'Tipos De Recrutamento': 'Modalidades de recrutamento',
                    'Provas De Selecção': 'Gestão das provas de seleção',
                    'Mapas de Curso': 'Estrutura curricular dos cursos',
                    'Fases de Curso': 'Etapas de formação',
                    'Planos de Curso': 'Planificação curricular',
                    'Cursos': 'Gestão de cursos de formação',
                    'Disciplinas': 'Gestão de disciplinas',
                    'Formandos': 'Gestão de formandos matriculados',
                    'Formadores': 'Gestão de formadores',
                    'Turmas': 'Gestão de turmas',
                    'Avaliações': 'Gestão de avaliações',
                    'Ausências': 'Registo de ausências',
                    'Instituições': 'Gestão de instituições',
                    'Utilizadores': 'Gestão de utilizadores do sistema',
                    'Atribuição De Meios': 'Gestão de equipamentos atribuídos'
                };

                descEl.textContent = descriptions[title] || 'Gestão de registos do sistema';
            }

            // Copiar o ícone do menu ativo - usando múltiplos seletores para Filament 4
            if (iconEl) {
                let activeIcon = null;

                // Método 1: Tentar encontrar pelo seletor de item ativo
                activeIcon = document.querySelector('.fi-sidebar-item-active .fi-sidebar-item-icon svg');

                // Método 2: Se não encontrou, procurar pelo href que corresponde à URL atual
                if (!activeIcon) {
                    const currentUrl = window.location.href;
                    const sidebarLinks = document.querySelectorAll('.fi-sidebar-item-btn, .fi-sidebar-item a');
                    sidebarLinks.forEach(function(link) {
                        if (link.href === currentUrl) {
                            activeIcon = link.querySelector('.fi-sidebar-item-icon svg') || link.querySelector('svg.fi-icon');
                        }
                    });
                }

                // Método 3: Procurar por item com aria-current="page"
                if (!activeIcon) {
                    activeIcon = document.querySelector('[aria-current="page"] svg.fi-icon');
                }

                // Método 4: Procurar pelo item que contém o nome da página no texto
                if (!activeIcon && titleEl) {
                    const pageTitle = titleEl.textContent.trim().toLowerCase();
                    const allItems = document.querySelectorAll('.fi-sidebar-item');
                    allItems.forEach(function(item) {
                        const label = item.querySelector('.fi-sidebar-item-label');
                        if (label && label.textContent.trim().toLowerCase() === pageTitle) {
                            activeIcon = item.querySelector('svg.fi-icon, svg.fi-sidebar-item-icon');
                        }
                    });
                }

                if (activeIcon) {
                    const clonedIcon = activeIcon.cloneNode(true);
                    clonedIcon.setAttribute('width', '60');
                    clonedIcon.setAttribute('height', '60');
                    clonedIcon.style.cssText = 'width: 60px !important; height: 60px !important; min-width: 60px !important; min-height: 60px !important; color: #041842 !important; fill: currentColor !important;';
                    // Remover classes que possam conflitar
                    clonedIcon.classList.remove('fi-size-lg', 'fi-sidebar-item-icon', 'w-5', 'h-5', 'w-6', 'h-6');
                    iconEl.innerHTML = '';
                    iconEl.appendChild(clonedIcon);
                }
            }
        }

        // Executar após o carregamento
        setTimeout(function() {
            updateDynamicHeader();
        }, 100);

        setTimeout(function() {
            updateDynamicHeader();
        }, 300);

        setTimeout(function() {
            updateDynamicHeader();
        }, 600);
    });
</script>

<script>
    (function() {
        if (window.__sigefMenuHeaderSyncReady) {
            window.dispatchEvent(new Event('sigef:sync-menu-header'));
            return;
        }

        window.__sigefMenuHeaderSyncReady = true;

        const iconStyle = 'width: 60px !important; height: 60px !important; min-width: 60px !important; min-height: 60px !important; color: #041842 !important; fill: currentColor !important;';

        function normalizePath(path) {
            const cleanPath = (path || '/').split('?')[0].replace(/\/+$/, '');

            return cleanPath || '/';
        }

        function isAdminDashboard(path) {
            return normalizePath(path) === '/admin';
        }

        function isTenantDashboard(path) {
            return /^\/escola\/\d+\/?$/.test(path);
        }

        function isAccessForm(path) {
            return path === '/admin/shield/roles/create' || /^\/admin\/shield\/roles\/[^/]+(?:\/edit)?\/?$/.test(path);
        }

        function isAttendanceManagement(path) {
            return normalizePath(path) === '/admin/attendance-management'
                || /^\/escola\/\d+\/attendance-management\/?$/.test(path);
        }

        function dashboardIcon() {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 512 512" fill="currentColor" style="${iconStyle}">
                <path d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zM288 96a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zM256 416c35.3 0 64-28.7 64-64 0-16.2-6-31.1-16-42.3l69.5-138.9c5.9-11.9 1.1-26.3-10.7-32.2s-26.3-1.1-32.2 10.7L261.1 288.2c-1.7-.1-3.4-.2-5.1-.2-35.3 0-64 28.7-64 64s28.7 64 64 64zM176 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zM96 288a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm352-32a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/>
            </svg>`;
        }

        function descriptions() {
            return {
                'Painel de Controlo': 'Visão geral do sistema',
                'Anos Académicos': 'Gestão dos períodos letivos',
                'Tipos de Instituição': 'Classificação das instituições',
                'Órgãos de Proveniência': 'Órgãos, unidades e proveniências registadas no sistema',
                'Patentes': 'Gestão de patentes militares',
                'Candidatos': 'Gestão de candidatos ao ingresso',
                'Alistados': 'Gestão de candidatos ao ingresso',
                'Tipos de Recrutamento': 'Modalidades de recrutamento',
                'Provas de Selecção': 'Gestão das provas de seleção',
                'Mapas de Curso': 'Estrutura curricular dos cursos',
                'Mapas e Planos de Curso': 'Estrutura curricular dos cursos',
                'Fases de Curso': 'Etapas de formação',
                'Planos de Curso': 'Planificação curricular',
                'Cursos': 'Gestão de cursos de formação',
                'Disciplinas': 'Gestão de disciplinas',
                'Formandos': 'Gestão de formandos matriculados',
                'Gestão de Formandos': 'Gestão de formandos matriculados',
                'Formadores': 'Gestão de formadores',
                'Efectivos': 'Gestão de efectivos',
                'Turmas': 'Gestão de turmas',
                'Avaliações': 'Gestão de avaliações',
                'Ausências': 'Registo de ausências',
                'Instituições': 'Gestão de instituições',
                'Utilizadores': 'Gestão de utilizadores do sistema',
                'Acessos': 'Gestão de acessos e permissões',
                'Cartões': 'Gestão de modelos de cartões',
                'Certificados': 'Gestão de certificados',
                'Documentos': 'Gestão de documentos',
                'Mini Pautas': 'Gestão de mini pautas',
                'Pauta Geral': 'Gestão da pauta geral',
                'Atribuição de Turmas': 'Gestão de turmas atribuídas',
                'Atribuição de Meios': 'Gestão de equipamentos atribuídos',
                'Histórico Formandos': 'Histórico de transferências de formandos',
                'Histórico Alistados': 'Histórico de transferências de alistados',
                'Histórico de Transferências': 'Histórico de transferências',
            };
        }

        function pageOverride(path) {
            if (path.includes('/documents/create')) {
                return {
                    title: 'Novo Documento',
                    description: 'Criar e enviar um novo documento',
                    icon: document.querySelector('.fi-sidebar-item a[href*="/documents"] svg, .fi-sidebar-item-btn[href*="/documents"] svg'),
                };
            }

            if (path.includes('/mail-settings')) {
                return {
                    title: 'Servidor de E-mail',
                    description: 'Configurações do servidor de e-mail',
                    icon: null,
                };
            }

            if (/\/documents\/\d+\/edit\/?$/.test(path)) {
                return {
                    title: 'Editar Documento',
                    description: 'Modificar dados do documento',
                    icon: document.querySelector('.fi-sidebar-item a[href*="/documents"] svg, .fi-sidebar-item-btn[href*="/documents"] svg'),
                };
            }

            if (/\/documents\/\d+\/?$/.test(path)) {
                return {
                    title: 'Visualizar Documento',
                    description: 'Detalhes do documento',
                    icon: document.querySelector('.fi-sidebar-item a[href*="/documents"] svg, .fi-sidebar-item-btn[href*="/documents"] svg'),
                };
            }

            return null;
        }

        function closestMenuItem(element) {
            return element ? element.closest('.fi-sidebar-item') : null;
        }

        function menuLabel(menuItem) {
            return menuItem?.querySelector('.fi-sidebar-item-label')?.textContent?.trim() || null;
        }

        function menuIcon(menuItem) {
            return menuItem?.querySelector('.fi-sidebar-item-icon svg, svg.fi-icon') || null;
        }

        function linkPath(link) {
            try {
                return normalizePath(new URL(link.href, window.location.origin).pathname);
            } catch (error) {
                return null;
            }
        }

        function findMenuItemByPath(path) {
            const currentPath = normalizePath(path);
            const links = Array.from(document.querySelectorAll('.fi-sidebar-item a[href], a.fi-sidebar-item-btn[href], .fi-sidebar-item-btn[href]'));
            const candidates = links
                .map((link) => ({
                    path: linkPath(link),
                    item: closestMenuItem(link),
                }))
                .filter((candidate) => {
                    if (!candidate.path || !candidate.item) {
                        return false;
                    }

                    return candidate.path !== '/admin' && !isTenantDashboard(candidate.path);
                });

            const exactMatch = candidates.find((candidate) => candidate.path === currentPath);

            if (exactMatch) {
                return exactMatch.item;
            }

            const prefixMatch = candidates
                .filter((candidate) => currentPath.startsWith(candidate.path + '/'))
                .sort((left, right) => right.path.length - left.path.length)[0];

            if (prefixMatch) {
                return prefixMatch.item;
            }

            return document.querySelector('.fi-sidebar-item-active');
        }

        function nativeTitle() {
            const selectors = [
                '.fi-header-heading',
                '.fi-page-heading h1',
                '[data-slot="heading"]',
                'main h1',
            ];

            for (const selector of selectors) {
                const value = document.querySelector(selector)?.textContent?.trim();

                if (value) {
                    return value;
                }
            }

            return document.title?.split('-')[0]?.trim() || null;
        }

        function setIcon(iconEl, sourceIcon) {
            if (!iconEl || !sourceIcon) {
                return;
            }

            const clonedIcon = sourceIcon.cloneNode(true);
            clonedIcon.setAttribute('width', '60');
            clonedIcon.setAttribute('height', '60');
            clonedIcon.style.cssText = iconStyle;
            clonedIcon.classList.remove('fi-size-lg', 'fi-sidebar-item-icon', 'w-5', 'h-5', 'w-6', 'h-6');
            iconEl.innerHTML = '';
            iconEl.appendChild(clonedIcon);
        }

        function syncHeaderFromMenu() {
            const titleEl = document.getElementById('sigef-dynamic-title');
            const descEl = document.getElementById('sigef-dynamic-description');
            const iconEl = document.getElementById('sigef-dynamic-icon');
            const headerEl = document.querySelector('.sigef-full-header');

            if (!titleEl || !descEl || !iconEl || !headerEl) {
                return;
            }

            const currentPath = normalizePath(window.location.pathname);
            const hideHeader = isAdminDashboard(currentPath) || isAccessForm(currentPath) || isAttendanceManagement(currentPath);
            headerEl.classList.toggle('sigef-dashboard-header-hidden', hideHeader);

            if (hideHeader) {
                return;
            }

            if (isTenantDashboard(currentPath)) {
                titleEl.textContent = 'Painel de Controlo';
                descEl.textContent = descriptions()['Painel de Controlo'];
                iconEl.innerHTML = dashboardIcon();
                return;
            }

            const override = pageOverride(currentPath);

            if (override) {
                const menuItem = findMenuItemByPath(currentPath);
                const sourceIcon = override.icon || menuIcon(menuItem) || document.querySelector('[aria-current="page"] svg.fi-icon');

                titleEl.textContent = override.title;
                descEl.textContent = override.description;
                setIcon(iconEl, sourceIcon);
                return;
            }

            const menuItem = findMenuItemByPath(currentPath);
            const title = menuLabel(menuItem) || nativeTitle() || 'SIGEF';
            const sourceIcon = menuIcon(menuItem) || document.querySelector('[aria-current="page"] svg.fi-icon');

            titleEl.textContent = title;
            descEl.textContent = descriptions()[title] || 'Gestão de registos do sistema';
            setIcon(iconEl, sourceIcon);
        }

        let updateTimer = null;

        function scheduleSync(delay = 80) {
            clearTimeout(updateTimer);
            updateTimer = setTimeout(syncHeaderFromMenu, delay);
        }

        function runSyncSequence() {
            scheduleSync(30);
            setTimeout(syncHeaderFromMenu, 180);
            setTimeout(syncHeaderFromMenu, 460);
            setTimeout(syncHeaderFromMenu, 760);
        }

        function observeSidebar() {
            const sidebar = document.querySelector('.fi-sidebar');

            if (!sidebar || window.__sigefMenuHeaderSidebar === sidebar) {
                return;
            }

            window.__sigefMenuHeaderObserver?.disconnect();
            window.__sigefMenuHeaderSidebar = sidebar;
            window.__sigefMenuHeaderObserver = new MutationObserver(() => scheduleSync());
            window.__sigefMenuHeaderObserver.observe(sidebar, {
                attributes: true,
                attributeFilter: ['class', 'aria-current', 'href'],
                childList: true,
                subtree: true,
            });
        }

        function refreshSync() {
            observeSidebar();
            runSyncSequence();
        }

        if (!window.__sigefMenuHeaderHistoryPatched) {
            window.__sigefMenuHeaderHistoryPatched = true;

            ['pushState', 'replaceState'].forEach(function(method) {
                const original = history[method];

                history[method] = function() {
                    const result = original.apply(this, arguments);
                    window.dispatchEvent(new Event('sigef:path-changed'));

                    return result;
                };
            });
        }

        ['DOMContentLoaded', 'livewire:navigate', 'livewire:navigated', 'popstate', 'hashchange', 'sigef:path-changed', 'sigef:sync-menu-header'].forEach(function(eventName) {
            window.addEventListener(eventName, refreshSync);
            document.addEventListener(eventName, refreshSync);
        });

        refreshSync();
    })();
</script>
