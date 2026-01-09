<?php
/**
 * TurmaDisciplinaList - Lista de Disciplinas por Turma
 *
 * @version    2.0
 * @package    sigef
 * @author     SIGEF
 */
class TurmaDisciplinaList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $turma_id;
    
    use Adianti\Base\AdiantiStandardListTrait;
    
    public function __construct($param)
    {
        parent::__construct();
        
        $this->turma_id = $param['turma_id'] ?? TSession::getValue('turma_disciplina_turma_id');
        TSession::setValue('turma_disciplina_turma_id', $this->turma_id);
        
        $this->setDatabase('sigef');
        $this->setActiveRecord('TurmaDisciplina');
        $this->setDefaultOrder('ordem', 'asc');
        $this->setLimit(TSession::getValue(__CLASS__ . '_limit') ?? 10);
        
        $this->setCriteria(new TCriteria);
        $this->getCriteria()->add(new TFilter('turma_id', '=', $this->turma_id));
        
        // Info da turma
        $turmaInfo = 'Turma não encontrada';
        try {
            TTransaction::open('sigef');
            $turma = new Turma($this->turma_id);
            $turmaInfo = $turma->designacao;
            TTransaction::close();
        } catch (Exception $e) {}
        
        // Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);
        
        $col_ordem = new TDataGridColumn('ordem', '#', 'center', 50);
        $col_codigo = new TDataGridColumn('disciplina->codigo', 'Código', 'center', 80);
        $col_disciplina = new TDataGridColumn('disciplina->designacao', 'Disciplina', 'left');
        $col_formador = new TDataGridColumn('formador->nome_completo', 'Formador', 'left', 180);
        $col_carga = new TDataGridColumn('carga_horaria', 'Carga H.', 'center', 80);
        $col_status = new TDataGridColumn('status', 'Status', 'center', 90);
        
        $col_formador->enableAutoHide(600);
        
        $col_carga->setTransformer(function($value) { return $value ? $value . 'h' : '-'; });
        $col_status->setTransformer(function($value) {
            $colors = ['Pendente' => 'warning', 'Em Curso' => 'primary', 'Concluída' => 'success'];
            $color = $colors[$value] ?? 'secondary';
            $div = new TElement('span');
            $div->class = "badge bg-{$color}";
            $div->style = "font-size: 11px; padding: 5px 12px; border-radius: 4px;";
            $div->add($value);
            return $div;
        });
        
        $this->datagrid->addColumn($col_ordem);
        $this->datagrid->addColumn($col_codigo);
        $this->datagrid->addColumn($col_disciplina);
        $this->datagrid->addColumn($col_formador);
        $this->datagrid->addColumn($col_carga);
        $this->datagrid->addColumn($col_status);
        
        // Dropdown action group
        $action_group = new TDataGridActionGroup('', 'fas:ellipsis-v');
        
        $action_edit = new TDataGridAction([$this, 'onEditDisciplina'], ['key' => '{id}']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('far:edit blue');
        $action_group->addAction($action_edit);
        
        $action_group->addSeparator();
        
        $action_remove = new TDataGridAction([$this, 'onRemove'], ['key' => '{id}']);
        $action_remove->setLabel('Remover');
        $action_remove->setImage('far:trash-alt red');
        $action_group->addAction($action_remove);
        
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
        
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload'], ['turma_id' => $this->turma_id]));
        
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
        $icon_wrapper->add('<i class="fas fa-book-open" style="color: white; font-size: 20px;"></i>');
        
        $title_text = new TElement('div');
        $title_text->add('<h4 style="margin: 0; font-weight: 600; color: #1a3a5c; font-size: 22px;">Disciplinas da Turma</h4>');
        $title_text->add('<p style="margin: 0; color: #6c757d; font-size: 14px;"><span class="badge bg-primary">' . $turmaInfo . '</span></p>');
        
        $title_container->add($icon_wrapper);
        $title_container->add($title_text);
        $header_div->add($title_container);
        
        // ============================================
        // Create filter bar
        // ============================================
        $filter_bar = new TElement('div');
        $filter_bar->class = 'list-filter-bar-container';
        $filter_bar->style = 'background: white; padding: 15px 25px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; border-bottom: 1px solid #e0e0e0;';
        
        $btn_add = new TButton('btn_add');
        $btn_add->setAction(new TAction([$this, 'onAddDisciplina']), 'Adicionar Disciplina');
        $btn_add->setImage('fa:plus');
        $btn_add->class = 'btn';
        $btn_add->style = 'height: 38px; font-size: 14px; background-color: #041c4f; border-color: #041c4f; color: #ffffff;';
        
        $btn_back = new TButton('btn_back');
        $btn_back->setAction(new TAction(['TurmaList', 'onReload']), 'Voltar às Turmas');
        $btn_back->setImage('fa:arrow-left');
        $btn_back->class = 'btn btn-secondary';
        $btn_back->style = 'height: 38px; font-size: 14px;';
        
        $form_filter = new TForm('form_filter_bar');
        $form_filter->style = 'display: flex; align-items: center; gap: 12px; flex-wrap: wrap; width: 100%;';
        $form_filter->add($btn_add);
        $form_filter->add($btn_back);
        $form_filter->setFields([$btn_add, $btn_back]);
        
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
    
    public function onAddDisciplina($param)
    {
        $form = new BootstrapFormBuilder('form_add_disciplina');
        $form->setFormTitle('Adicionar Disciplina à Turma');
        
        $disciplina_id = new TDBCombo('disciplina_id', 'sigef', 'Disciplina', 'id', '{codigo} - {designacao}', 'designacao');
        $formador_id = new TDBCombo('formador_id', 'sigef', 'Formador', 'id', 'nome_completo', 'nome_completo');
        $carga_horaria = new TSpinner('carga_horaria');
        $ordem = new TSpinner('ordem');
        
        $disciplina_id->setSize('100%');
        $disciplina_id->enableSearch();
        $formador_id->setSize('100%');
        $formador_id->enableSearch();
        $carga_horaria->setRange(1, 200, 1);
        $ordem->setRange(1, 100, 1);
        $ordem->setValue(1);
        
        $form->addFields([new TLabel('Disciplina')], [$disciplina_id]);
        $form->addFields([new TLabel('Formador')], [$formador_id]);
        $form->addFields([new TLabel('Carga Horária')], [$carga_horaria], [new TLabel('Ordem')], [$ordem]);
        $form->addAction('Adicionar', new TAction([$this, 'onConfirmAdd'], ['turma_id' => $this->turma_id]), 'fa:check green');
        
        $window = new TWindow('Adicionar Disciplina');
        $window->setSize(600, null);
        $window->add($form);
        $window->show();
    }
    
    public static function onConfirmAdd($param)
    {
        try {
            TTransaction::open('sigef');
            
            $td = new TurmaDisciplina;
            $td->turma_id = $param['turma_id'];
            $td->disciplina_id = $param['disciplina_id'];
            $td->formador_id = $param['formador_id'] ?? null;
            $td->carga_horaria = $param['carga_horaria'] ?? null;
            $td->ordem = $param['ordem'] ?? 1;
            $td->status = 'Pendente';
            $td->store();
            
            TTransaction::close();
            
            TWindow::closeWindow();
            new TMessage('info', 'Disciplina adicionada com sucesso!');
            AdiantiCoreApplication::loadPage('TurmaDisciplinaList', 'onReload', ['turma_id' => $param['turma_id']]);
        }
        catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onRemove($param)
    {
        $action = new TAction([$this, 'Remove']);
        $action->setParameters($param);
        new TQuestion('Deseja remover esta disciplina da turma?', $action);
    }
    
    public function Remove($param)
    {
        try {
            TTransaction::open('sigef');
            (new TurmaDisciplina($param['key']))->delete();
            TTransaction::close();
            $this->onReload(['turma_id' => $this->turma_id]);
            new TMessage('info', 'Disciplina removida!');
        }
        catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onEditDisciplina($param) {}
}
