<?php
/**
 * FormadorList - Lista de Formadores
 *
 * @version    2.0
 * @package    sigef
 * @author     SIGEF
 */
class FormadorList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    
    use Adianti\Base\AdiantiStandardListTrait;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('sigef');
        $this->setActiveRecord('Formador');
        $this->setDefaultOrder('nome_completo', 'asc');
        $this->addFilterField('nome_completo', 'like', 'nome_completo');
        $this->addFilterField('status', '=', 'status');
        $this->setLimit(TSession::getValue(__CLASS__ . '_limit') ?? 10);
        
        // Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);
        
        $col_id = new TDataGridColumn('id', '#', 'center', 50);
        $col_patente = new TDataGridColumn('patente->abreviatura', 'Patente', 'center', 80);
        $col_nome = new TDataGridColumn('nome_completo', 'Nome Completo', 'left');
        $col_genero = new TDataGridColumn('genero', 'Género', 'center', 80);
        $col_status = new TDataGridColumn('status', 'Estado', 'center', 80);
        $col_tipo = new TDataGridColumn('tipo_formador', 'Tipo', 'center', 100);
        $col_grau = new TDataGridColumn('grau_academico', 'Grau Académico', 'center', 120);
        $col_especialidade = new TDataGridColumn('especialidade', 'Especialidade', 'left', 150);
        
        $col_genero->enableAutoHide(500);
        $col_tipo->enableAutoHide(600);
        $col_grau->enableAutoHide(700);
        $col_especialidade->enableAutoHide(800);
        
        $col_status->setTransformer(function($value) {
            $class = ($value === 'Inactivo') ? 'danger' : 'success';
            $label = ($value === 'Inactivo') ? 'Inactivo' : 'Activo';
            $div = new TElement('span');
            $div->class = "badge bg-{$class}";
            $div->style = "font-size: 11px; padding: 5px 12px; border-radius: 4px;";
            $div->add($label);
            return $div;
        });
        
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_patente);
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_genero);
        $this->datagrid->addColumn($col_status);
        $this->datagrid->addColumn($col_tipo);
        $this->datagrid->addColumn($col_grau);
        $this->datagrid->addColumn($col_especialidade);
        
        // Ordenação
        $col_id->setAction(new TAction([$this, 'onReload'], ['order' => 'id']));
        $col_nome->setAction(new TAction([$this, 'onReload'], ['order' => 'nome_completo']));
        
        // Dropdown action group (three-dot menu)
        $action_group = new TDataGridActionGroup('', 'fas:ellipsis-v');
        
        $action_edit = new TDataGridAction(['FormadorForm', 'onEdit'], ['key' => '{id}']);
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit blue');
        $action_group->addAction($action_edit);
        
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
        $icon_wrapper->add('<i class="fas fa-chalkboard-teacher" style="color: white; font-size: 20px;"></i>');
        
        $title_text = new TElement('div');
        $title_text->add('<h4 style="margin: 0; font-weight: 600; color: #1a3a5c; font-size: 22px;">Formadores</h4>');
        $title_text->add('<p style="margin: 0; color: #6c757d; font-size: 14px;">Formadores Registados no Sistema</p>');
        
        $title_container->add($icon_wrapper);
        $title_container->add($title_text);
        $header_div->add($title_container);
        
        // ============================================
        // Create filter bar
        // ============================================
        $filter_bar = new TElement('div');
        $filter_bar->class = 'list-filter-bar-container';
        $filter_bar->style = 'background: white; padding: 15px 25px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; border-bottom: 1px solid #e0e0e0;';
        
        $active_filter = new TCombo('status_filter');
        $active_filter->addItems(['Activo' => 'Activos', 'Inactivo' => 'Inactivos', '' => 'Todos']);
        $active_filter->setValue(TSession::getValue(__CLASS__ . '_status_filter') ?? 'Activo');
        $active_filter->setSize(110);
        $active_filter->setFormName('form_filter_bar');
        
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
        $btn_register->setAction(new TAction(['FormadorForm', 'onEdit']), 'Registar');
        $btn_register->setImage('fa:plus');
        $btn_register->class = 'btn';
        $btn_register->style = 'height: 38px; font-size: 14px; background-color: #041c4f; border-color: #041c4f; color: #ffffff;';
        
        $form_filter = new TForm('form_filter_bar');
        $form_filter->style = 'display: flex; align-items: center; gap: 12px; flex-wrap: wrap; width: 100%;';
        $form_filter->add($active_filter);
        $form_filter->add($limit_combo);
        $form_filter->add($search_input);
        $form_filter->add($btn_search);
        $form_filter->add($btn_reload);
        
        $spacer = new TElement('div');
        $spacer->style = 'flex: 1;';
        $form_filter->add($spacer);
        
        $form_filter->add($btn_register);
        
        // Export dropdown button
        $dropdown_export = new TDropDown('Exportar', 'fa:file-export');
        $dropdown_export->setButtonClass('btn btn-outline-secondary');
        $dropdown_export->addAction('Salvar como CSV', new TAction([$this, 'onExportCSV']), 'fa:file-csv green');
        $dropdown_export->addAction('Salvar como PDF', new TAction([$this, 'onExportPDF']), 'far:file-pdf red');
        $dropdown_export->addAction('Salvar como XML', new TAction([$this, 'onExportXML']), 'fa:file-code blue');
        $dropdown_export->addAction('Salvar como Excel', new TAction([$this, 'onExportExcel']), 'fa:file-excel green');
        $dropdown_export->addAction('Salvar como Word', new TAction([$this, 'onExportWord']), 'fa:file-word blue');
        $form_filter->add($dropdown_export);
        
        $form_filter->setFields([$active_filter, $limit_combo, $search_input, $btn_search, $btn_reload, $btn_register]);
        
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
    
    public function onSearch($param = null)
    {
        $data = $this->form->getData();
        
        TSession::setValue(__CLASS__.'_filter_data', $data);
        
        $this->form->setData($data);
        
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }
    
    public function onDelete($param)
    {
        $action = new TAction([$this, 'Delete']);
        $action->setParameters($param);
        
        new TQuestion('Deseja realmente eliminar este formador?', $action);
    }
    
    public function Delete($param)
    {
        try
        {
            TTransaction::open('sigef');
            $object = new Formador($param['key']);
            $object->delete();
            TTransaction::close();
            
            $this->onReload();
            new TMessage('info', 'Formador eliminado com sucesso!');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Export to CSV
     */
    public function onExportCSV($param)
    {
        try
        {
            TTransaction::open('sigef');
            $repository = new TRepository('Formador');
            $objects = $repository->load(new TCriteria);
            
            $output = "ID;Patente;Nome Completo;Género;Estado;Tipo;Grau Académico;Especialidade\n";
            
            foreach ($objects as $object)
            {
                $patente = $object->patente ? $object->patente->abreviatura : '';
                $output .= "{$object->id};{$patente};{$object->nome_completo};{$object->genero};{$object->status};{$object->tipo_formador};{$object->grau_academico};{$object->especialidade}\n";
            }
            
            TTransaction::close();
            
            $file = 'tmp/formadores_' . date('YmdHis') . '.csv';
            file_put_contents($file, $output);
            
            TPage::openFile($file);
            new TMessage('info', 'CSV exportado com sucesso!');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Export to PDF
     */
    public function onExportPDF($param)
    {
        try
        {
            TTransaction::open('sigef');
            $repository = new TRepository('Formador');
            $objects = $repository->load(new TCriteria);
            
            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
                <style>
                    @page { margin: 20mm; }
                    body { font-family: Arial, sans-serif; font-size: 11px; }
                    h1 { color: #041c4f; font-size: 18px; text-align: center; }
                    .info { text-align: center; color: #666; font-size: 10px; margin-bottom: 15px; }
                    table { width: 100%; border-collapse: collapse; }
                    th { background-color: #041c4f; color: white; padding: 8px 5px; text-align: left; font-size: 10px; }
                    td { border-bottom: 1px solid #ddd; padding: 6px 5px; font-size: 9px; }
                    tr:nth-child(even) { background-color: #f5f5f5; }
                </style>
            </head><body>';
            
            $html .= '<h1>Lista de Formadores</h1>';
            $html .= '<p class="info">Data: ' . date('d/m/Y H:i:s') . ' | Total: ' . count($objects) . '</p>';
            $html .= '<table><tr><th>ID</th><th>Patente</th><th>Nome Completo</th><th>Género</th><th>Estado</th><th>Tipo</th><th>Grau</th></tr>';
            
            foreach ($objects as $object)
            {
                $patente = $object->patente ? $object->patente->abreviatura : '';
                $html .= "<tr><td>{$object->id}</td><td>{$patente}</td><td>{$object->nome_completo}</td><td>{$object->genero}</td><td>{$object->status}</td><td>{$object->tipo_formador}</td><td>{$object->grau_academico}</td></tr>";
            }
            
            $html .= '</table></body></html>';
            TTransaction::close();
            
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            
            $file = 'tmp/formadores_' . date('YmdHis') . '.pdf';
            file_put_contents($file, $dompdf->output());
            
            TPage::openFile($file);
            new TMessage('info', 'PDF exportado com sucesso!');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Export to XML
     */
    public function onExportXML($param)
    {
        try
        {
            TTransaction::open('sigef');
            $repository = new TRepository('Formador');
            $objects = $repository->load(new TCriteria);
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<formadores>\n";
            
            foreach ($objects as $object)
            {
                $patente = $object->patente ? $object->patente->abreviatura : '';
                $xml .= "  <formador>\n";
                $xml .= "    <id>{$object->id}</id>\n";
                $xml .= "    <patente><![CDATA[{$patente}]]></patente>\n";
                $xml .= "    <nome><![CDATA[{$object->nome_completo}]]></nome>\n";
                $xml .= "    <genero>{$object->genero}</genero>\n";
                $xml .= "    <estado>{$object->status}</estado>\n";
                $xml .= "    <tipo>{$object->tipo_formador}</tipo>\n";
                $xml .= "    <grau><![CDATA[{$object->grau_academico}]]></grau>\n";
                $xml .= "  </formador>\n";
            }
            
            $xml .= "</formadores>";
            TTransaction::close();
            
            $file = 'tmp/formadores_' . date('YmdHis') . '.xml';
            file_put_contents($file, $xml);
            
            TPage::openFile($file);
            new TMessage('info', 'XML exportado com sucesso!');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Export to Excel
     */
    public function onExportExcel($param)
    {
        try
        {
            TTransaction::open('sigef');
            $repository = new TRepository('Formador');
            $objects = $repository->load(new TCriteria);
            
            $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8">
                <style>table { border-collapse: collapse; } th { background-color: #041c4f; color: white; padding: 8px; border: 1px solid #000; } td { border: 1px solid #ddd; padding: 6px; }</style>
            </head><body>';
            $html .= '<p><b>Lista de Formadores - ' . date('d/m/Y H:i:s') . '</b></p>';
            $html .= '<table><tr><th>ID</th><th>Patente</th><th>Nome Completo</th><th>Género</th><th>Estado</th><th>Tipo</th><th>Grau</th></tr>';
            
            foreach ($objects as $object)
            {
                $patente = $object->patente ? $object->patente->abreviatura : '';
                $html .= "<tr><td>{$object->id}</td><td>{$patente}</td><td>{$object->nome_completo}</td><td>{$object->genero}</td><td>{$object->status}</td><td>{$object->tipo_formador}</td><td>{$object->grau_academico}</td></tr>";
            }
            
            $html .= '</table></body></html>';
            TTransaction::close();
            
            $file = 'tmp/formadores_' . date('YmdHis') . '.xls';
            file_put_contents($file, $html);
            
            TPage::openFile($file);
            new TMessage('info', 'Excel exportado com sucesso!');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Export to Word
     */
    public function onExportWord($param)
    {
        try
        {
            TTransaction::open('sigef');
            $repository = new TRepository('Formador');
            $objects = $repository->load(new TCriteria);
            
            $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word"><head><meta charset="UTF-8">
                <style>body { font-family: Arial; } h1 { color: #041c4f; } table { width: 100%; border-collapse: collapse; } th { background-color: #041c4f; color: white; padding: 8px; border: 1px solid #000; } td { border: 1px solid #ddd; padding: 6px; }</style>
            </head><body>';
            $html .= '<h1>Lista de Formadores</h1>';
            $html .= '<p>Data: ' . date('d/m/Y H:i:s') . ' | Total: ' . count($objects) . '</p>';
            $html .= '<table><tr><th>ID</th><th>Patente</th><th>Nome Completo</th><th>Género</th><th>Estado</th><th>Tipo</th><th>Grau</th></tr>';
            
            foreach ($objects as $object)
            {
                $patente = $object->patente ? $object->patente->abreviatura : '';
                $html .= "<tr><td>{$object->id}</td><td>{$patente}</td><td>{$object->nome_completo}</td><td>{$object->genero}</td><td>{$object->status}</td><td>{$object->tipo_formador}</td><td>{$object->grau_academico}</td></tr>";
            }
            
            $html .= '</table></body></html>';
            TTransaction::close();
            
            $file = 'tmp/formadores_' . date('YmdHis') . '.doc';
            file_put_contents($file, $html);
            
            TPage::openFile($file);
            new TMessage('info', 'Word exportado com sucesso!');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
