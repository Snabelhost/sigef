<?php
/**
 * SigefDashboard - Dashboard principal do SIGEF
 *
 * @version    2.0
 * @package    sigef
 * @author     SIGEF
 */
class SigefDashboard extends TPage
{
    public function __construct($param)
    {
        parent::__construct();
        
        try
        {
            TTransaction::open('sigef');
            
            // Estatísticas
            $totalCursos = (new TRepository('MapaCurso'))->count(new TCriteria);
            $totalFormandos = (new TRepository('Formando'))->count(new TCriteria);
            $totalFormadores = (new TRepository('Formador'))->count(new TCriteria);
            $totalInstituicoes = (new TRepository('Instituicao'))->count(new TCriteria);
            
            // Ano lectivo aberto
            $anoLectivo = AnoLectivo::getAnoLectivoAberto();
            $anoLectivoTexto = $anoLectivo ? $anoLectivo->ano : 'Nenhum aberto';
            
            TTransaction::close();
            
            // Container principal com CSS inline
            $style = new TElement('style');
            $style->add('
                .sigef-dashboard { padding: 0; }
                .sigef-header { margin-bottom: 1.5rem; }
                .sigef-header h2 { font-size: 1.75rem; font-weight: 700; color: #1D2D53; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
                .sigef-header p { color: #64748b; margin: 0.25rem 0 0; }
                .sigef-header .logo-icon { width: 40px; height: 40px; background: #1D2D53; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; }
                
                .stat-card { background: white; border-radius: 12px; padding: 1.25rem; border: 1px solid #e2e8f0; transition: all 0.2s; }
                .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-2px); }
                .stat-card .stat-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
                .stat-card .stat-title { font-size: 0.875rem; font-weight: 600; color: #1D2D53; margin: 0; }
                .stat-card .stat-subtitle { font-size: 0.75rem; color: #94a3b8; margin: 0.25rem 0 0; }
                .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
                .stat-card .stat-icon.blue { background: rgba(29, 45, 83, 0.1); color: #1D2D53; }
                .stat-card .stat-value { font-size: 1.75rem; font-weight: 700; color: #1D2D53; margin: 0.5rem 0; }
                .stat-card .stat-label { font-size: 0.75rem; color: #94a3b8; }
                .stat-card .stat-arrow { width: 28px; height: 28px; background: #5B68FF; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; margin-top: 0.5rem; cursor: pointer; }
                
                .section-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
                .section-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
                .section-header h5 { font-size: 1rem; font-weight: 600; color: #1D2D53; margin: 0; }
                .section-header small { color: #94a3b8; font-size: 0.8rem; }
                .section-header .btn-link { color: #5B68FF; font-size: 0.875rem; text-decoration: none; }
                
                .data-table { width: 100%; }
                .data-table th { background: #1D2D53; color: white; padding: 0.75rem 1rem; font-size: 0.8rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; }
                .data-table td { padding: 0.875rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; color: #475569; }
                .data-table tr:last-child td { border-bottom: none; }
                .data-table .badge-initial { width: 32px; height: 32px; border-radius: 8px; background: #5B68FF; color: white; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; }
                
                .formador-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; }
                .formador-item:last-child { border-bottom: none; }
                .formador-item .avatar { width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 1rem; }
                .formador-item .info { flex: 1; }
                .formador-item .name { font-size: 0.875rem; font-weight: 500; color: #1D2D53; margin: 0; }
                .formador-item .role { font-size: 0.75rem; color: #94a3b8; margin: 0; }
                
                .registro-info { text-align: center; padding: 1rem; color: #64748b; font-size: 0.875rem; }
            ');
            parent::add($style);
            
            $container = new TElement('div');
            $container->class = 'sigef-dashboard';
            
            // Cabeçalho
            $header = new TElement('div');
            $header->class = 'sigef-header';
            $header->add('<div style="display: flex; align-items: center; gap: 1rem;">
                <div class="logo-icon" style="background: none; padding: 0;">
                    <img src="app/images/sigef-logo.png" alt="SIGEF" style="width: 48px; height: 48px;">
                </div>
                <div>
                    <h2>SIGEF</h2>
                    <p style="font-size: 1.1rem; margin: 0;">Sistema Integrado de Gestão Formativa</p>
                </div>
            </div>');
            $container->add($header);
            
            // Row principal com cards de estatísticas
            $mainRow = new TElement('div');
            $mainRow->class = 'row';
            
            // Coluna esquerda (8 colunas)
            $leftCol = new TElement('div');
            $leftCol->class = 'col-lg-8';
            
            // Cards de estatísticas
            $statsRow = new TElement('div');
            $statsRow->class = 'row mb-4';
            
            // Card Cursos
            $statsRow->add($this->createModernCard('Curso', 'Dados Fornecidos pelo Sistema', $totalCursos, 'users'));
            // Card Formadores
            $statsRow->add($this->createModernCard('Formadores', 'Dados Fornecidos pelo Sistema', $totalFormadores, 'users'));
            // Card Formandos
            $statsRow->add($this->createModernCard('Formandos', 'Dados Fornecidos pelo Sistema', $totalFormandos, 'user-graduate'));
            
            $leftCol->add($statsRow);
            
            // Segunda linha - Card Instituição
            $instRow = new TElement('div');
            $instRow->class = 'row mb-4';
            $instRow->add($this->createModernCard('Instituição', 'Dados Fornecidos pelo Sistema', $totalInstituicoes, 'building'));
            $leftCol->add($instRow);
            
            $mainRow->add($leftCol);
            
            // Coluna direita (4 colunas)
            $rightCol = new TElement('div');
            $rightCol->class = 'col-lg-4';
            
            // Card Corporativo
            $rightCol->add($this->createCorpCard($anoLectivoTexto, $totalFormadores, $totalFormandos));
            
            $mainRow->add($rightCol);
            $container->add($mainRow);
            
            // Nova row para as duas listagens lado a lado
            $listingsRow = new TElement('div');
            $listingsRow->class = 'row';
            
            // Coluna Mapas dos Cursos (8 colunas)
            $coursesCol = new TElement('div');
            $coursesCol->class = 'col-lg-8';
            
            // Texto de registro por cima da listagem de cursos
            $regCursosText = new TElement('div');
            $regCursosText->style = 'text-align: left; padding: 0.5rem 0; color: #64748b; font-size: 0.875rem; margin-bottom: 0.5rem;';
            $regCursosText->add('Registro dos mapas dos cursos - ' . date('Y'));
            $coursesCol->add($regCursosText);
            
            $coursesCol->add($this->createCoursesSection());
            $listingsRow->add($coursesCol);
            
            // Coluna Formadores (4 colunas)
            $formadoresCol = new TElement('div');
            $formadoresCol->class = 'col-lg-4';
            
            // Texto de registro por cima da listagem de formadores
            $regFormadoresText = new TElement('div');
            $regFormadoresText->style = 'text-align: left; padding: 0.5rem 0; color: #64748b; font-size: 0.875rem; margin-bottom: 0.5rem;';
            $regFormadoresText->add('Registro dos formadores e formandos - ' . date('Y'));
            $formadoresCol->add($regFormadoresText);
            
            $formadoresCol->add($this->createFormadoresSection());
            $listingsRow->add($formadoresCol);
            
            $container->add($listingsRow);
            parent::add($container);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    
    private function createModernCard($title, $subtitle, $value, $iconType)
    {
        $col = new TElement('div');
        $col->class = 'col-md-4 mb-3';
        
        // Ícones SVG simples
        $icons = [
            'users' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
            'user-graduate' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>',
            'building' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path></svg>'
        ];
        
        $icon = $icons[$iconType] ?? $icons['users'];
        
        $col->add("
            <div class='stat-card'>
                <div class='stat-header'>
                    <div>
                        <h6 class='stat-title'>{$title}</h6>
                        <p class='stat-subtitle'>{$subtitle}</p>
                    </div>
                    <div class='stat-icon blue'>{$icon}</div>
                </div>
                <div class='stat-value'>{$value}</div>
                <div class='stat-label'>Dados dinâmicos</div>
                <div class='stat-arrow'>
                    <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='3'><path d='M5 12h14M12 5l7 7-7 7'/></svg>
                </div>
            </div>
        ");
        
        return $col;
    }
    
    private function createCoursesSection()
    {
        $card = new TElement('div');
        $card->style = 'background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden;';
        
        $card->add('
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h5 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1D2D53;">Mapas dos Cursos</h5>
                        <small style="color: #94a3b8;">Lista dos 5 últimos mapas de cursos registados</small>
                    </div>
                    <a href="index.php?class=MapaCursoList" style="color: #5B68FF; font-size: 0.875rem; text-decoration: none; font-weight: 500;">Ver todos</a>
                </div>
            </div>
        ');
        
        try {
            TTransaction::open('sigef');
            
            $repository = new TRepository('MapaCurso');
            $criteria = new TCriteria;
            $criteria->setProperty('order', 'id DESC');
            $criteria->setProperty('limit', 5);
            $cursos = $repository->load($criteria);
            
            TTransaction::close();
            
            $listHtml = '<div style="padding: 0;">';
            
            if ($cursos) {
                foreach ($cursos as $curso) {
                    $initial = strtoupper(substr($curso->designacao ?? 'C', 0, 1));
                    $statusColor = $this->getStatusColor($curso->status);
                    $listHtml .= "
                        <div style='display: flex; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9;'>
                            <div style='width: 36px; height: 36px; border-radius: 50%; background: #f59e0b; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; margin-right: 1rem;'>{$initial}</div>
                            <div style='flex: 1; min-width: 0;'>
                                <div style='font-weight: 600; color: #1D2D53;'>{$curso->designacao}</div>
                                <div style='font-size: 0.75rem; color: #94a3b8;'>{$curso->sigla}</div>
                            </div>
                            <div style='text-align: center; padding: 0 0.75rem;'>
                                <div style='font-size: 0.7rem; color: #64748b; margin-bottom: 0.25rem;'>Estádo</div>
                                <span style='background:{$statusColor};color:white;padding:4px 10px;border-radius:20px;font-size:0.7rem;'>{$curso->status}</span>
                            </div>
                            <div style='text-align: center; padding: 0 0.75rem;'>
                                <div style='font-size: 0.7rem; color: #64748b; margin-bottom: 0.25rem;'>Local</div>
                                <div style='font-size: 0.8rem; color: #475569;'>{$curso->local}</div>
                            </div>
                            <div style='text-align: center; padding: 0 0.75rem;'>
                                <div style='font-size: 0.7rem; color: #64748b; margin-bottom: 0.25rem;'>Data de Início</div>
                                <div style='font-size: 0.8rem; color: #475569;'>{$curso->data_inicio}</div>
                            </div>
                            <div style='text-align: center; padding: 0 0.75rem;'>
                                <div style='font-size: 0.7rem; color: #64748b; margin-bottom: 0.25rem;'>Data de Final</div>
                                <div style='font-size: 0.8rem; color: #475569;'>{$curso->data_fim}</div>
                            </div>
                        </div>";
                }
            } else {
                $listHtml .= '<div style="padding:2rem;text-align:center;color:#94a3b8;">Nenhum curso registado</div>';
            }
            
            $listHtml .= '</div>';
            $card->add($listHtml);
        }
        catch (Exception $e) {
            $card->add("<div style='padding:1rem;color:#94a3b8;'>{$e->getMessage()}</div>");
        }
        
        return $card;
    }
    
    private function createCorpCard($anoLectivo, $totalFormadores, $totalFormandos)
    {
        $card = new TElement('div');
        $card->id = 'corp-card';
        $card->style = 'background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-radius: 20px; padding: 2rem; margin-bottom: 1rem; min-height: 450px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 8px 32px rgba(0,0,0,0.08);';
        
        $card->add('
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.02); }
                }
                @keyframes glow {
                    0%, 100% { box-shadow: 0 0 5px rgba(91,104,255,0.2); }
                    50% { box-shadow: 0 0 15px rgba(91,104,255,0.4); }
                }
                #corp-card .corp-header { animation: fadeIn 0.5s ease-out; }
                #corp-card .corp-fields { animation: fadeIn 0.5s ease-out 0.1s both; }
                #corp-card .corp-stats { animation: fadeIn 0.5s ease-out 0.2s both; }
                #corp-card .stat-box:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 12px 28px rgba(0,0,0,0.25);
                }
                #realtime-clock {
                    font-family: "Inter", monospace;
                    font-variant-numeric: tabular-nums;
                    animation: glow 2s ease-in-out infinite;
                }
            </style>
            
            <!-- Header with Logo and Title -->
            <div class="corp-header" style="display: flex; align-items: center; gap: 1.25rem; margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, #041c4f, #0a2d6e); padding: 14px; border-radius: 14px; box-shadow: 0 6px 20px rgba(4,28,79,0.25);">
                    <img src="app/images/policia-nacional-logo.png" alt="República de Angola" 
                        style="width: 48px; height: 48px; object-fit: contain;"
                        onerror="this.src=\'app/images/sigef-logo.png\'">
                </div>
                <div>
                    <h5 style="margin: 0; font-size: 1.35rem; font-weight: 700; color: #041c4f;">Dados Corporativos</h5>
                    <small style="color: #64748b; font-size: 0.875rem;">Dados fornecidos pela Logística</small>
                </div>
            </div>
            
            <!-- Operação and Data de registro -->
            <div class="corp-fields" style="display: flex; gap: 1.25rem; margin-bottom: 2rem;">
                <div style="flex: 1;">
                    <label style="font-size: 0.85rem; color: #041c4f; font-weight: 600; display: block; margin-bottom: 0.5rem;">
                        <i class="fas fa-clock" style="margin-right: 6px; color: #5B68FF;"></i>Operação
                    </label>
                    <div id="realtime-clock" style="background: white; border: 2px solid #e2e8f0; border-radius: 10px; padding: 1rem 1.25rem; font-size: 1.1rem; color: #041c4f; font-weight: 700; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                        --:--:--
                    </div>
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 0.85rem; color: #041c4f; font-weight: 600; display: block; margin-bottom: 0.5rem;">
                        <i class="fas fa-calendar-alt" style="margin-right: 6px; color: #5B68FF;"></i>Data de registro
                    </label>
                    <div style="background: white; border: 2px solid #e2e8f0; border-radius: 10px; padding: 1rem 1.25rem; font-size: 1.1rem; color: #041c4f; font-weight: 700; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                        ' . date('d/m/Y') . '
                    </div>
                </div>
            </div>
            
            <!-- Status Cards -->
            <div class="corp-stats" style="display: flex; gap: 1.25rem; margin-top: auto;">
                <div class="stat-box" style="flex: 1; background: linear-gradient(135deg, #041c4f 0%, #0a3d7e 100%); border-radius: 14px; padding: 1.5rem; color: white; transition: all 0.3s ease; cursor: pointer; box-shadow: 0 8px 24px rgba(4,28,79,0.3);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <small style="font-size: 0.8rem; opacity: 0.9; display: block; font-weight: 500;">Status actual</small>
                            <small style="font-size: 0.75rem; opacity: 0.7; display: block; margin-top: 3px;">
                                <i class="fas fa-users" style="margin-right: 4px;"></i>Formadores
                            </small>
                        </div>
                        <div style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 8px;">
                            <i class="fas fa-chalkboard-teacher" style="font-size: 1.1rem;"></i>
                        </div>
                    </div>
                    <strong style="font-size: 2.5rem; display: block; margin-top: 0.75rem; font-weight: 700;">' . $totalFormadores . '</strong>
                    <small style="font-size: 0.7rem; opacity: 0.7;">Data: ' . date('d-m-Y') . '</small>
                </div>
                <div class="stat-box" style="flex: 1; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 14px; padding: 1.5rem; color: white; transition: all 0.3s ease; cursor: pointer; box-shadow: 0 8px 24px rgba(15,23,42,0.35);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <small style="font-size: 0.8rem; opacity: 0.9; display: block; font-weight: 500;">Status actual</small>
                            <small style="font-size: 0.75rem; opacity: 0.7; display: block; margin-top: 3px;">
                                <i class="fas fa-user-graduate" style="margin-right: 4px;"></i>Formandos
                            </small>
                        </div>
                        <div style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 8px;">
                            <i class="fas fa-graduation-cap" style="font-size: 1.1rem;"></i>
                        </div>
                    </div>
                    <strong style="font-size: 2.5rem; display: block; margin-top: 0.75rem; font-weight: 700;">' . $totalFormandos . '</strong>
                    <small style="font-size: 0.7rem; opacity: 0.7;">Data: ' . date('d-m-Y') . '</small>
                </div>
            </div>
            
            <!-- Real-time Clock JavaScript -->
            <script>
                (function() {
                    function updateClock() {
                        var now = new Date();
                        var hours = String(now.getHours()).padStart(2, "0");
                        var minutes = String(now.getMinutes()).padStart(2, "0");
                        var seconds = String(now.getSeconds()).padStart(2, "0");
                        var clockElement = document.getElementById("realtime-clock");
                        if (clockElement) {
                            clockElement.innerHTML = hours + ":" + minutes + ":" + seconds;
                        }
                    }
                    updateClock();
                    setInterval(updateClock, 1000);
                })();
            </script>
        ');
        
        return $card;
    }
    
    private function createFormadoresSection()
    {
        $card = new TElement('div');
        $card->style = 'background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden;';
        
        $card->add('
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h5 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1D2D53;">Formadores</h5>
                        <small style="color: #94a3b8;">Lista dos 5 últimos mapas de cursos registados</small>
                    </div>
                    <a href="index.php?class=FormadorList" style="color: #5B68FF; font-size: 0.875rem; text-decoration: none; font-weight: 500;">Ver todos</a>
                </div>
            </div>
        ');
        
        try {
            TTransaction::open('sigef');
            
            $repository = new TRepository('Formador');
            $criteria = new TCriteria;
            $criteria->setProperty('order', 'id DESC');
            $criteria->setProperty('limit', 3);
            $formadores = $repository->load($criteria);
            
            TTransaction::close();
            
            $listHtml = '<div style="padding: 0;">';
            
            if ($formadores) {
                foreach ($formadores as $formador) {
                    $nome = $formador->nome ?? 'Formador';
                    $especialidade = $formador->especialidade ?? 'Técnico Médio';
                    $listHtml .= "
                        <div style='display: flex; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9;'>
                            <div style='width: 40px; height: 40px; border-radius: 50%; background: #1D2D53; color: white; display: flex; align-items: center; justify-content: center; margin-right: 1rem;'>
                                <svg width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path><circle cx='12' cy='7' r='4'></circle></svg>
                            </div>
                            <div>
                                <div style='font-weight: 600; color: #1D2D53; font-size: 0.9rem;'>{$nome}</div>
                                <div style='font-size: 0.75rem; color: #94a3b8;'>{$especialidade}</div>
                            </div>
                        </div>";
                }
            } else {
                $listHtml .= '<div style="padding:2rem;text-align:center;color:#94a3b8;">Nenhum formador registado</div>';
            }
            
            $listHtml .= '</div>';
            $card->add($listHtml);
        }
        catch (Exception $e) {
            $card->add("<div style='padding:1rem;color:#94a3b8;'>{$e->getMessage()}</div>");
        }
        
        return $card;
    }
    
    private function getStatusColor($status)
    {
        $colors = [
            'Planeamento' => '#6b7280',
            'Pendente' => '#f59e0b',
            'Aberto' => '#10b981',
            'Em Curso' => '#3b82f6',
            'Concluído' => '#8b5cf6',
            'Cancelado' => '#ef4444'
        ];
        
        return $colors[$status] ?? '#f59e0b';
    }
}
