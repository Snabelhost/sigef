<?php
/**
 * SystemUserList
 *
 * @version    8.4
 * @package    control
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-template
 */
class SystemUserList extends TStandardList
{
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;
    protected $transformCallback;
    
    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        $ini  = AdiantiApplicationConfig::get();
        
        parent::setDatabase('permission');            // defines the database
        parent::setActiveRecord('SystemUser');   // defines the active record
        parent::setDefaultOrder('id', 'asc');         // defines the default order
        parent::addFilterField('id', '=', 'id'); // filterField, operator, formField
        parent::addFilterField('name', 'like', 'name'); // filterField, operator, formField
        parent::addFilterField('email', 'like', 'email'); // filterField, operator, formField
        parent::addFilterField('active', '=', 'active'); // filterField, operator, formField
        parent::setLimit(TSession::getValue(__CLASS__ . '_limit') ?? 5);
        
        parent::setAfterSearchCallback( [$this, 'onAfterSearch' ] );
        
        // creates the form (hidden, for filter purposes)
        $this->form = new BootstrapFormBuilder('form_search_SystemUser');
        
        // create the form fields
        $id = new TEntry('id');
        $name = new TEntry('name');
        $email = new TEntry('email');
        $active = new TCombo('active');
        
        $active->addItems( [ 'Y' => _t('Yes'), 'N' => _t('No') ] );
        
        // add the fields
        $this->form->addFields( [new TLabel('Id')], [$id] );
        $this->form->addFields( [new TLabel(_t('Name'))], [$name] );
        $this->form->addFields( [new TLabel(_t('Email'))], [$email] );
        $this->form->addFields( [new TLabel(_t('Active'))], [$active] );
        
        $id->setSize('30%');
        $name->setSize('100%');
        $email->setSize('100%');
        $active->setSize('100%');
        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('SystemUser_filter_data') );
        
        // add the search form actions
        $btn = $this->form->addAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        
        // creates a DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);
        
        // creates the datagrid columns - Matching the design reference
        $column_id = new TDataGridColumn('id', '#', 'center', 40);
        $column_function = new TDataGridColumn('function_name', 'Função', 'left', 100);
        $column_name = new TDataGridColumn('name', 'Nome Completo', 'left');
        $column_login = new TDataGridColumn('login', 'Utilizador', 'left', 100);
        $column_email = new TDataGridColumn('email', 'Email', 'left');
        $column_active = new TDataGridColumn('active', 'Estado', 'center', 80);
        $column_unit = new TDataGridColumn('unit->name', 'Instituição', 'left', 80);
        $column_created = new TDataGridColumn('created_at', 'Data de Criação', 'center', 130);
        $column_updated = new TDataGridColumn('updated_at', 'Data de Actualização', 'center', 180);
        
        $column_login->enableAutoHide(500);
        $column_email->enableAutoHide(500);
        $column_unit->enableAutoHide(600);
        $column_created->enableAutoHide(800);
        $column_updated->enableAutoHide(800);
        
        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_function);
        $this->datagrid->addColumn($column_name);
        $this->datagrid->addColumn($column_login);
        $this->datagrid->addColumn($column_email);
        $this->datagrid->addColumn($column_active);
        $this->datagrid->addColumn($column_unit);
        $this->datagrid->addColumn($column_created);
        $this->datagrid->addColumn($column_updated);
        
        // Transform function_name to show group name if empty
        $column_function->setTransformer( function($value, $object, $row) {
            if (empty($value)) {
                try {
                    $groups = $object->getSystemUserGroups();
                    if ($groups && count($groups) > 0) {
                        return $groups[0]->name;
                    }
                } catch (Exception $e) {}
                return 'Administrador';
            }
            return $value;
        });
        
        // Transform unit to show name
        $column_unit->setTransformer( function($value, $object, $row) {
            if (empty($value)) {
                return 'Todas';
            }
            return $value;
        });
        
        // Transform dates
        $column_created->setTransformer( function($value, $object, $row) {
            if (empty($value)) {
                return date('Y-m-d');
            }
            return TDate::date2br($value);
        });
        
        $column_updated->setTransformer( function($value, $object, $row) {
            if (empty($value)) {
                return date('Y-m-d');
            }
            return TDate::date2br($value);
        });
        
        // Modern status badge transformer
        $column_active->setTransformer( function($value, $object, $row) {
            $class = ($value=='N') ? 'danger' : 'success';
            $label = ($value=='N') ? 'Inactivo' : 'Activo';
            $div = new TElement('span');
            $div->class = "badge bg-{$class}";
            $div->style = "font-size: 14px; padding: 8px 12px; border-radius: 4px;";
            $div->add($label);
            return $div;
        });
        
        // creates the datagrid column actions
        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);
        
        $order_name = new TAction(array($this, 'onReload'));
        $order_name->setParameter('order', 'name');
        $column_name->setAction($order_name);
        
        $order_login = new TAction(array($this, 'onReload'));
        $order_login->setParameter('order', 'login');
        $column_login->setAction($order_login);
        
        $order_email = new TAction(array($this, 'onReload'));
        $order_email->setParameter('order', 'email');
        $column_email->setAction($order_email);
        
        // Create dropdown action group (three-dot menu) - "Seleccionar" column
        $action_group = new TDataGridActionGroup('', 'fas:ellipsis-v');
        
        // create EDIT action
        $action_edit = new TDataGridAction(array('SystemUserForm', 'onEdit'), ['register_state' => 'false'] );
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit blue');
        $action_edit->setField('id');
        $action_group->addAction($action_edit);
        
        // create DELETE action
        $action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('far:trash-alt red');
        $action_del->setField('id');
        $action_group->addAction($action_del);
        
        // Add separator
        $action_group->addSeparator();
        
        // create CLONE action
        $action_clone = new TDataGridAction(array($this, 'onClone'));
        $action_clone->setLabel(_t('Clone'));
        $action_clone->setImage('far:clone green');
        $action_clone->setField('id');
        $action_group->addAction($action_clone);
        
        // create ONOFF action
        $action_onoff = new TDataGridAction(array($this, 'onTurnOnOff'));
        $action_onoff->setLabel(_t('Activate/Deactivate'));
        $action_onoff->setImage('fa:power-off orange');
        $action_onoff->setField('id');
        $action_group->addAction($action_onoff);
        
        // create Impersonation action
        $action_person = new TDataGridAction(array($this, 'onImpersonation'));
        $action_person->setLabel(_t('Impersonation'));
        $action_person->setImage('far:user-circle gray');
        $action_person->setFields(['id','login']);
        $action_group->addAction($action_person);
        
        $this->datagrid->addActionGroup($action_group);
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // Force dark blue header styling with white text
        $thead = $this->datagrid->getHead();
        if ($thead) {
            $thead->{'style'} = 'background-color: #041c4f !important;';
            // Style the header cells
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
        
        // create the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        // ============================================
        // Create modern header matching the design
        // ============================================
        $header_div = new TElement('div');
        $header_div->class = 'user-list-header-container';
        $header_div->style = 'background: white; padding: 20px 25px; border-radius: 8px 8px 0 0; border-bottom: 1px solid #e0e0e0;';
        
        // Icon and title container
        $title_container = new TElement('div');
        $title_container->style = 'display: flex; align-items: center;';
        
        // User icon (circle with user inside)
        $icon_wrapper = new TElement('div');
        $icon_wrapper->style = 'width: 45px; height: 45px; background: #041c4f; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;';
        $icon_wrapper->add('<i class="fas fa-user" style="color: white; font-size: 20px;"></i>');
        
        // Title and subtitle
        $title_text = new TElement('div');
        $title_text->add('<h4 style="margin: 0; font-weight: 600; color: #1a3a5c; font-size: 22px;">Utilizador</h4>');
        $title_text->add('<p style="margin: 0; color: #6c757d; font-size: 14px;">Utilizadores Registados no Sistema</p>');
        
        $title_container->add($icon_wrapper);
        $title_container->add($title_text);
        $header_div->add($title_container);
        
        // ============================================
        // Create filter bar matching the design
        // ============================================
        $filter_bar = new TElement('div');
        $filter_bar->class = 'user-filter-bar-container';
        $filter_bar->style = 'background: white; padding: 15px 25px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; border-bottom: 1px solid #e0e0e0;';
        
        // Active status dropdown
        $active_filter = new TCombo('active_filter');
        $active_filter->addItems(['Y' => 'Activos', 'N' => 'Inactivos', '' => 'Todos']);
        $active_filter->setValue(TSession::getValue(__CLASS__ . '_active_filter') ?? 'Y');
        $active_filter->setSize(110);
        $active_filter->setFormName('form_filter_bar');
        
        // Items per page dropdown
        $limit_combo = new TCombo('limit_combo');
        $limit_combo->addItems(['5' => '5', '10' => '10', '20' => '20', '50' => '50', '100' => '100']);
        $limit_combo->setValue(TSession::getValue(__CLASS__ . '_limit') ?? '5');
        $limit_combo->setSize(65);
        $limit_combo->setFormName('form_filter_bar');
        
        // Search input
        $search_input = new TEntry('search_name');
        $search_input->setSize(180);
        $search_input->placeholder = 'Pesquisar';
        $search_input->setFormName('form_filter_bar');
        if (TSession::getValue(get_class($this).'_filter_data')) {
            $search_input->setValue(TSession::getValue(get_class($this).'_filter_data')->name ?? '');
        }
        
        // Search button (Pesquisar) - Dark Blue #041c4f
        $btn_search = new TButton('btn_search');
        $btn_search->setAction(new TAction([$this, 'onSearch']), 'Pesquisar');
        $btn_search->setImage('fa:search');
        $btn_search->class = 'btn';
        $btn_search->style = 'height: 38px; font-size: 14px; background-color: #041c4f; border-color: #041c4f; color: #ffffff;';
        
        // Reload button (Recarregar) - Gray with icon and text
        $btn_reload = new TButton('btn_reload');
        $btn_reload->setAction(new TAction([$this, 'onReload']), 'Recarregar');
        $btn_reload->setImage('fa:sync');
        $btn_reload->class = 'btn btn-secondary';
        $btn_reload->style = 'height: 38px; font-size: 14px;';
        
        // Register button (Registrar) - Dark Blue #041c4f
        $btn_register = new TButton('btn_register');
        $btn_register->setAction(new TAction(['SystemUserForm', 'onEdit'], ['register_state' => 'false']), 'Registrar');
        $btn_register->setImage('fa:plus');
        $btn_register->class = 'btn';
        $btn_register->style = 'height: 38px; font-size: 14px; background-color: #041c4f; border-color: #041c4f; color: #ffffff;';
        
        // Export dropdown button
        $dropdown_export = new TDropDown('Exportar', 'fa:file-export');
        $dropdown_export->setButtonClass('btn btn-outline-secondary');
        $dropdown_export->addAction('Salvar como CSV', new TAction([$this, 'onExportCSV']), 'fa:file-csv green');
        $dropdown_export->addAction('Salvar como PDF', new TAction([$this, 'onExportPDF']), 'far:file-pdf red');
        $dropdown_export->addAction('Salvar como XML', new TAction([$this, 'onExportXML']), 'fa:file-code blue');
        $dropdown_export->addAction('Salvar como Excel', new TAction([$this, 'onExportExcel']), 'fa:file-excel green');
        $dropdown_export->addAction('Salvar como Word', new TAction([$this, 'onExportWord']), 'fa:file-word blue');
        
        // Create filter form
        $form_filter = new TForm('form_filter_bar');
        $form_filter->style = 'display: flex; align-items: center; gap: 12px; flex-wrap: wrap; width: 100%;';
        $form_filter->add($active_filter);
        $form_filter->add($limit_combo);
        $form_filter->add($search_input);
        $form_filter->add($btn_search);
        $form_filter->add($btn_reload);
        
        // Spacer to push register button to the right
        $spacer = new TElement('div');
        $spacer->style = 'flex: 1;';
        $form_filter->add($spacer);
        
        $form_filter->add($btn_register);
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
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        // Breadcrumb removed as requested
        $container->add($main_wrapper);
        
        parent::add($container);
    }
    
    /**
     *
     */
    public function onAfterSearch($datagrid, $options)
    {
        if (!empty(TSession::getValue(get_class($this).'_filter_data')))
        {
            $obj = new stdClass;
            $obj->search_name = TSession::getValue(get_class($this).'_filter_data')->name ?? '';
            TForm::sendData('form_filter_bar', $obj);
        }
    }
    
    /**
     *
     */
    public static function onChangeLimit($param)
    {
        TSession::setValue(__CLASS__ . '_limit', $param['limit'] );
        AdiantiCoreApplication::loadPage(__CLASS__, 'onReload');
    }
    
    /**
     *
     */
    public static function onShowCurtainFilters($param = null)
    {
        try
        {
            // create empty page for right panel
            $page = TPage::create();
            $page->setTargetContainer('adianti_right_panel');
            $page->setProperty('override', 'true');
            $page->setPageName(__CLASS__);
            
            $btn_close = new TButton('closeCurtain');
            $btn_close->onClick = "Template.closeRightPanel();";
            $btn_close->setLabel(_t('Close'));
            $btn_close->setImage('fas:times red');
            
            // instantiate self class, populate filters in construct 
            $embed = new self;
            $embed->form->addHeaderWidget($btn_close);
            
            // embed form inside curtain
            $page->add($embed->form);
            $page->show();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    
    /**
     * Turn on/off an user
     */
    public function onTurnOnOff($param)
    {
        try
        {
            TTransaction::open('permission');
            $user = SystemUser::find($param['id']);
            if ($user instanceof SystemUser)
            {
                $user->active = $user->active == 'Y' ? 'N' : 'Y';
                $user->store();
            }
            
            TTransaction::close();
            
            $this->onReload($param);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Clone group
     */
    public function onClone($param)
    {
        try
        {
            TTransaction::open('permission');
            $user = new SystemUser($param['id']);
            $user->cloneUser();
            TTransaction::close();
            
            $this->onReload();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Impersonation user
     */
    public function onImpersonation($param)
    {
        try
        {
            $login_impersonated = TSession::getValue('login');

            TTransaction::open('permission');
            TSession::regenerate();
            $user = SystemUser::validate( $param['login'] );
            ApplicationAuthenticationService::loadSessionVars($user);
            SystemAccessLogService::registerLogin(true, $login_impersonated);
            AdiantiCoreApplication::gotoPage('EmptyPage');
            TTransaction::close();
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
            TTransaction::open('permission');
            $repository = new TRepository('SystemUser');
            $objects = $repository->load(new TCriteria);
            
            $output = "ID;Nome;Login;Email;Estado;Unidade;Data Criação\n";
            
            foreach ($objects as $object)
            {
                $active = $object->active == 'Y' ? 'Activo' : 'Inactivo';
                $unit = $object->unit ? $object->unit->name : 'Todas';
                $output .= "{$object->id};{$object->name};{$object->login};{$object->email};{$active};{$unit};{$object->created_at}\n";
            }
            
            TTransaction::close();
            
            $file = 'tmp/utilizadores_' . date('YmdHis') . '.csv';
            file_put_contents($file, $output);
            
            TPage::openFile($file);
            new TMessage('info', 'Arquivo CSV exportado com sucesso!');
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
            TTransaction::open('permission');
            $repository = new TRepository('SystemUser');
            $objects = $repository->load(new TCriteria);
            
            // If no template exists, use HTML to PDF conversion
            $html = '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    @page { margin: 20mm; }
                    body { font-family: Arial, sans-serif; font-size: 11px; }
                    h1 { color: #041c4f; font-size: 18px; text-align: center; margin-bottom: 5px; }
                    .info { text-align: center; color: #666; font-size: 10px; margin-bottom: 15px; }
                    table { width: 100%; border-collapse: collapse; }
                    th { background-color: #041c4f; color: white; padding: 8px 5px; text-align: left; font-size: 10px; font-weight: bold; }
                    td { border-bottom: 1px solid #ddd; padding: 6px 5px; font-size: 9px; }
                    tr:nth-child(even) { background-color: #f5f5f5; }
                    .footer { text-align: center; margin-top: 20px; font-size: 9px; color: #666; }
                </style>
            </head>
            <body>';
            
            $html .= '<h1>Lista de Utilizadores do Sistema</h1>';
            $html .= '<p class="info">Data de exportação: ' . date('d/m/Y H:i:s') . ' | Total: ' . count($objects) . ' utilizadores</p>';
            $html .= '<table>';
            $html .= '<tr><th style="width:5%">ID</th><th style="width:25%">Nome</th><th style="width:15%">Login</th><th style="width:25%">Email</th><th style="width:10%">Estado</th><th style="width:20%">Unidade</th></tr>';
            
            foreach ($objects as $object)
            {
                $active = $object->active == 'Y' ? 'Activo' : 'Inactivo';
                $unit = $object->unit ? $object->unit->name : 'Todas';
                $html .= "<tr><td>{$object->id}</td><td>{$object->name}</td><td>{$object->login}</td><td>{$object->email}</td><td>{$active}</td><td>{$unit}</td></tr>";
            }
            
            $html .= '</table>';
            $html .= '<p class="footer">SIGEF - Sistema de Gestão Formativa</p>';
            $html .= '</body></html>';
            
            TTransaction::close();
            
            // Generate PDF using Dompdf (included in Adianti)
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'Arial');
            
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            
            $file = 'tmp/utilizadores_' . date('YmdHis') . '.pdf';
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
            TTransaction::open('permission');
            $repository = new TRepository('SystemUser');
            $objects = $repository->load(new TCriteria);
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= "<utilizadores>\n";
            
            foreach ($objects as $object)
            {
                $active = $object->active == 'Y' ? 'Activo' : 'Inactivo';
                $unit = $object->unit ? $object->unit->name : 'Todas';
                
                $xml .= "  <utilizador>\n";
                $xml .= "    <id>{$object->id}</id>\n";
                $xml .= "    <nome><![CDATA[{$object->name}]]></nome>\n";
                $xml .= "    <login>{$object->login}</login>\n";
                $xml .= "    <email>{$object->email}</email>\n";
                $xml .= "    <estado>{$active}</estado>\n";
                $xml .= "    <unidade><![CDATA[{$unit}]]></unidade>\n";
                $xml .= "    <data_criacao>{$object->created_at}</data_criacao>\n";
                $xml .= "  </utilizador>\n";
            }
            
            $xml .= "</utilizadores>";
            
            TTransaction::close();
            
            $file = 'tmp/utilizadores_' . date('YmdHis') . '.xml';
            file_put_contents($file, $xml);
            
            TPage::openFile($file);
            new TMessage('info', 'Arquivo XML exportado com sucesso!');
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
            TTransaction::open('permission');
            $repository = new TRepository('SystemUser');
            $objects = $repository->load(new TCriteria);
            
            $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Utilizadores</x:Name><x:WorksheetOptions><x:Panes></x:Panes></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
                <style>
                    table { border-collapse: collapse; }
                    th { background-color: #041c4f; color: white; font-weight: bold; padding: 8px; border: 1px solid #000; }
                    td { border: 1px solid #ddd; padding: 6px; }
                    .header { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
                </style>
            </head>
            <body>';
            
            $html .= '<p class="header">Lista de Utilizadores - Exportado em: ' . date('d/m/Y H:i:s') . '</p>';
            $html .= '<table>';
            $html .= '<tr><th>ID</th><th>Nome</th><th>Login</th><th>Email</th><th>Estado</th><th>Unidade</th><th>Data Criação</th></tr>';
            
            foreach ($objects as $object)
            {
                $active = $object->active == 'Y' ? 'Activo' : 'Inactivo';
                $unit = $object->unit ? $object->unit->name : 'Todas';
                $html .= "<tr><td>{$object->id}</td><td>{$object->name}</td><td>{$object->login}</td><td>{$object->email}</td><td>{$active}</td><td>{$unit}</td><td>{$object->created_at}</td></tr>";
            }
            
            $html .= '</table></body></html>';
            
            TTransaction::close();
            
            $file = 'tmp/utilizadores_' . date('YmdHis') . '.xls';
            file_put_contents($file, $html);
            
            TPage::openFile($file);
            new TMessage('info', 'Arquivo Excel exportado com sucesso!');
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
            TTransaction::open('permission');
            $repository = new TRepository('SystemUser');
            $objects = $repository->load(new TCriteria);
            
            $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                <!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View></w:WordDocument></xml><![endif]-->
                <style>
                    body { font-family: Arial, sans-serif; font-size: 12px; }
                    h1 { color: #041c4f; font-size: 18px; border-bottom: 2px solid #041c4f; padding-bottom: 10px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th { background-color: #041c4f; color: white; font-weight: bold; padding: 8px; border: 1px solid #000; text-align: left; }
                    td { border: 1px solid #ddd; padding: 6px; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    .info { font-size: 10px; color: #666; margin-top: 5px; }
                </style>
            </head>
            <body>';
            
            $html .= '<h1>Lista de Utilizadores do Sistema</h1>';
            $html .= '<p class="info">Data de exportação: ' . date('d/m/Y H:i:s') . ' | Total de registos: ' . count($objects) . '</p>';
            $html .= '<table>';
            $html .= '<tr><th>ID</th><th>Nome</th><th>Login</th><th>Email</th><th>Estado</th><th>Unidade</th><th>Data Criação</th></tr>';
            
            foreach ($objects as $object)
            {
                $active = $object->active == 'Y' ? 'Activo' : 'Inactivo';
                $unit = $object->unit ? $object->unit->name : 'Todas';
                $html .= "<tr><td>{$object->id}</td><td>{$object->name}</td><td>{$object->login}</td><td>{$object->email}</td><td>{$active}</td><td>{$unit}</td><td>{$object->created_at}</td></tr>";
            }
            
            $html .= '</table></body></html>';
            
            TTransaction::close();
            
            $file = 'tmp/utilizadores_' . date('YmdHis') . '.doc';
            file_put_contents($file, $html);
            
            TPage::openFile($file);
            new TMessage('info', 'Arquivo Word exportado com sucesso!');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
