<?php
/**
 * FormandoList - Lista de Formandos
 *
 * @version    2.0
 * @package    sigef
 * @author     SIGEF
 */
class FormandoList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    
    use Adianti\Base\AdiantiStandardListTrait;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('sigef');
        $this->setActiveRecord('Formando');
        $this->setDefaultOrder('nome_completo', 'asc');
        $this->addFilterField('nome_completo', 'like', 'nome_completo');
        $this->addFilterField('status_formativo', '=', 'status_formativo');
        $this->setLimit(TSession::getValue(__CLASS__ . '_limit') ?? 10);
        
        // Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);
        
        $col_id = new TDataGridColumn('id', '#', 'center', 50);
        $col_patente = new TDataGridColumn('patente->abreviatura', 'Patente', 'center', 80);
        $col_nome = new TDataGridColumn('nome_completo', 'Nome Completo', 'left');
        $col_genero = new TDataGridColumn('genero', 'Género', 'center', 80);
        $col_status = new TDataGridColumn('status_formativo', 'Estado', 'center', 100);
        $col_tipo = new TDataGridColumn('instituicao->tipo_instituicao->codigo', 'Tipo', 'center', 80);
        $col_proveniencia = new TDataGridColumn('proveniencia->designacao', 'Proveniência', 'left', 120);
        
        $col_genero->enableAutoHide(500);
        $col_tipo->enableAutoHide(600);
        $col_proveniencia->enableAutoHide(700);
        
        $col_status->setTransformer(function($value) {
            $colors = [
                'Candidato' => 'secondary',
                'Alistado' => 'info',
                'Recruta' => 'primary',
                'Instruendo' => 'warning',
                'Aluno' => 'success',
                'Formado' => 'success',
                'Desistente' => 'danger',
                'Expulso' => 'danger'
            ];
            $color = $colors[$value] ?? 'secondary';
            $div = new TElement('span');
            $div->class = "badge bg-{$color}";
            $div->style = "font-size: 11px; padding: 5px 12px; border-radius: 4px;";
            $div->add($value);
            return $div;
        });
        
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_patente);
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_genero);
        $this->datagrid->addColumn($col_status);
        $this->datagrid->addColumn($col_tipo);
        $this->datagrid->addColumn($col_proveniencia);
        
        // Ordenação
        $col_id->setAction(new TAction([$this, 'onReload'], ['order' => 'id']));
        $col_nome->setAction(new TAction([$this, 'onReload'], ['order' => 'nome_completo']));
        
        // Dropdown action group
        $action_group = new TDataGridActionGroup('', 'fas:ellipsis-v');
        
        $action_edit = new TDataGridAction(['FormandoForm', 'onEdit'], ['key' => '{id}']);
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit blue');
        $action_group->addAction($action_edit);
        
        $action_transition = new TDataGridAction([$this, 'onTransition'], ['key' => '{id}']);
        $action_transition->setLabel('Transição');
        $action_transition->setImage('fas:exchange-alt green');
        $action_group->addAction($action_transition);
        
        $action_group->addSeparator();
        
        $action_delete = new TDataGridAction([$this, 'onDelete'], ['key' => '{id}']);
        $action_delete->setLabel(_t('Delete'));
        $action_delete->setImage('far:trash-alt red');
        $action_group->addAction($action_delete);
        
        $this->datagrid->addActionGroup($action_group);
        $this->datagrid->createModel();
        
        // Style header
        $thead = $this->datagrid->getHead();
        if ($thead) {
            $thead->{'style'} = 'background-color: #041c4f !important;';
            $children = $thead->getChildren();
            if ($children) {
                foreach ($children as $row) {
                    if (method_exists($row, 'getChildren')) {
                        $cells = $row->getChildren();
                        if ($cells) {
                            foreach ($cells as $cell) {
                                $cell->{'style'} = ($cell->{'style'} ?? '') . '; background-color: #041c4f !important; color: #ffffff !important; padding: 15px 10px !important;';
                            }
                        }
                    }
                }
            }
        }
        
        // Paginação
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        
        // ============================================
        // Create modern header
        // ============================================
        $header_div = new TElement('div');
        $header_div->class = 'list-header-container';
        $header_div->style = 'background: white; padding: 20px 25px; border-radius: 8px 8px 0 0; border-bottom: 1px solid #e0e0e0;';
        
        $title_container = new TElement('div');
        $title_container->style = 'display: flex; align-items: center;';
        
        $icon_wrapper = new TElement('div');
        $icon_wrapper->style = 'width: 45px; height: 45px; background: #041c4f; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;';
        $icon_wrapper->add('<i class="fas fa-user-graduate" style="color: white; font-size: 20px;"></i>');
        
        $title_text = new TElement('div');
        $title_text->add('<h4 style="margin: 0; font-weight: 600; color: #1a3a5c; font-size: 22px;">Formandos</h4>');
        $title_text->add('<p style="margin: 0; color: #6c757d; font-size: 14px;">Formandos Registados no Sistema</p>');
        
        $title_container->add($icon_wrapper);
        $title_container->add($title_text);
        $header_div->add($title_container);
        
        // ============================================
        // Create filter bar
        // ============================================
        $filter_bar = new TElement('div');
        $filter_bar->class = 'list-filter-bar-container';
        $filter_bar->style = 'background: white; padding: 15px 25px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; border-bottom: 1px solid #e0e0e0;';
        
        $status_filter = new TCombo('status_filter');
        $status_filter->addItems([
            'Candidato' => 'Candidatos',
            'Alistado' => 'Alistados',
            'Recruta' => 'Recrutas',
            'Instruendo' => 'Instruendos',
            'Formado' => 'Formados',
            '' => 'Todos'
        ]);
        $status_filter->setValue(TSession::getValue(__CLASS__ . '_status_filter') ?? '');
        $status_filter->setSize(120);
        $status_filter->setFormName('form_filter_bar');
        
        $limit_combo = new TCombo('limit_combo');
        $limit_combo->addItems(['5' => '5', '10' => '10', '20' => '20', '50' => '50', '100' => '100']);
        $limit_combo->setValue(TSession::getValue(__CLASS__ . '_limit') ?? '10');
        $limit_combo->setSize(65);
        $limit_combo->setFormName('form_filter_bar');
        
        $search_input = new TEntry('search_name');
        $search_input->setSize(180);
        $search_input->placeholder = 'Pesquisar';
        $search_input->setFormName('form_filter_bar');
        
        $btn_search = new TButton('btn_search');
        $btn_search->setAction(new TAction([$this, 'onSearch']), 'Pesquisar');
        $btn_search->setImage('fa:search');
        $btn_search->class = 'btn';
        $btn_search->style = 'height: 38px; font-size: 14px; background-color: #041c4f; border-color: #041c4f; color: #ffffff;';
        
        $btn_reload = new TButton('btn_reload');
        $btn_reload->setAction(new TAction([$this, 'onReload']), 'Recarregar');
        $btn_reload->setImage('fa:sync');
        $btn_reload->class = 'btn btn-secondary';
        $btn_reload->style = 'height: 38px; font-size: 14px;';
        
        $btn_register = new TButton('btn_register');
        $btn_register->setAction(new TAction(['FormandoForm', 'onEdit']), 'Registar');
        $btn_register->setImage('fa:plus');
        $btn_register->class = 'btn';
        $btn_register->style = 'height: 38px; font-size: 14px; background-color: #041c4f; border-color: #041c4f; color: #ffffff;';
        
        $form_filter = new TForm('form_filter_bar');
        $form_filter->style = 'display: flex; align-items: center; gap: 12px; flex-wrap: wrap; width: 100%;';
        $form_filter->add($status_filter);
        $form_filter->add($limit_combo);
        $form_filter->add($search_input);
        $form_filter->add($btn_search);
        $form_filter->add($btn_reload);
        
        $spacer = new TElement('div');
        $spacer->style = 'flex: 1;';
        $form_filter->add($spacer);
        
        $form_filter->add($btn_register);
        $form_filter->setFields([$status_filter, $limit_combo, $search_input, $btn_search, $btn_reload, $btn_register]);
        
        $filter_bar->add($form_filter);
        
        // Panel for datagrid
        $panel = new TPanelGroup;
        $panel->add($this->datagrid)->style = 'overflow: visible;';
        $panel->addFooter($this->pageNavigation);
        $panel->getBody()->style = 'padding: 0; overflow: visible;';
        $panel->getHeader()->style = 'display: none;';
        
        // Main container wrapper
        $main_wrapper = new TElement('div');
        $main_wrapper->class = 'list-wrapper';
        $main_wrapper->style = 'background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);';
        $main_wrapper->add($header_div);
        $main_wrapper->add($filter_bar);
        $main_wrapper->add($panel);
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($main_wrapper);
        
        parent::add($container);
    }
    
    public function onTransition($param)
    {
        try
        {
            TTransaction::open('sigef');
            $formando = new Formando($param['key']);
            
            $form = new TQuickForm('form_transition');
            $form->style = 'padding: 20px';
            
            $info = new TElement('div');
            $info->class = 'alert alert-info';
            $info->add("Formando: <strong>{$formando->nome_completo}</strong><br>Status actual: <strong>{$formando->status_formativo}</strong>");
            $form->add($info);
            
            $novoStatus = new TCombo('novo_status');
            
            $transicoes = [
                'Candidato' => ['Alistado' => 'Alistado'],
                'Alistado' => ['Recruta' => 'Recruta', 'Desistente' => 'Desistente'],
                'Recruta' => ['Instruendo' => 'Instruendo', 'Desistente' => 'Desistente', 'Expulso' => 'Expulso'],
                'Instruendo' => ['Formado' => 'Formado', 'Desistente' => 'Desistente', 'Expulso' => 'Expulso']
            ];
            
            if (isset($transicoes[$formando->status_formativo])) {
                $novoStatus->addItems($transicoes[$formando->status_formativo]);
            }
            
            $form->addQuickField('Novo Status', $novoStatus, '100%');
            
            if ($formando->status_formativo === 'Recruta' && $formando->ibm_concluida !== 'Y') {
                $alert = new TElement('div');
                $alert->class = 'alert alert-warning mt-2';
                $alert->add('<i class="fas fa-exclamation-triangle"></i> A fase IBM ainda não foi concluída.');
                $form->add($alert);
                
                $btnIBM = new TButton('btn_ibm');
                $btnIBM->setLabel('Concluir IBM');
                $btnIBM->setImage('fas:check green');
                $btnIBM->setAction(new TAction([$this, 'onConcluirIBM'], ['key' => $formando->id]), 'Concluir IBM');
                $form->add($btnIBM);
            }
            
            $form->addQuickAction('Confirmar', new TAction([$this, 'onConfirmTransition'], ['key' => $formando->id]), 'fa:check green');
            
            TTransaction::close();
            
            $window = new TWindow('Transição de Status');
            $window->setSize(500, null);
            $window->add($form);
            $window->show();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onConfirmTransition($param)
    {
        try
        {
            TTransaction::open('sigef');
            $data = $this->form->getData();
            
            $formando = new Formando($param['key']);
            $formando->transicionarStatus($param['novo_status'] ?? $data->novo_status);
            $formando->store();
            
            TTransaction::close();
            
            TWindow::closeWindow();
            new TMessage('info', 'Status atualizado com sucesso!');
            $this->onReload();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onConcluirIBM($param)
    {
        try
        {
            TTransaction::open('sigef');
            $formando = new Formando($param['key']);
            $formando->concluirIBM();
            $formando->store();
            TTransaction::close();
            
            TWindow::closeWindow();
            new TMessage('info', 'Fase IBM concluída!');
            $this->onReload();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onDelete($param)
    {
        $action = new TAction([$this, 'Delete']);
        $action->setParameters($param);
        
        new TQuestion('Deseja realmente eliminar este formando?', $action);
    }
    
    public function Delete($param)
    {
        try
        {
            TTransaction::open('sigef');
            $object = new Formando($param['key']);
            $object->delete();
            TTransaction::close();
            
            $this->onReload();
            new TMessage('info', 'Formando eliminado com sucesso!');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
