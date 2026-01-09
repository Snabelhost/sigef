<?php
/**
 * PatenteForm - Formulário de Patentes
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class PatenteForm extends TPage
{
    protected $form;
    
    use Adianti\Base\AdiantiStandardFormTrait;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('sigef');
        $this->setActiveRecord('Patente');
        
        // Formulário
        $this->form = new BootstrapFormBuilder('form_patente');
        $this->form->setFormTitle('Cadastro de Patente');
        
        // Campos
        $id = new TEntry('id');
        $designacao = new TEntry('designacao');
        $abreviatura = new TEntry('abreviatura');
        $nivel_hierarquico = new TSpinner('nivel_hierarquico');
        $ativo = new TRadioGroup('ativo');
        
        // Configurações
        $id->setEditable(FALSE);
        $designacao->setSize('100%');
        $abreviatura->setSize('100%');
        $nivel_hierarquico->setRange(0, 20, 1);
        $nivel_hierarquico->setValue(0);
        $ativo->addItems(['Y' => 'Activo', 'N' => 'Inactivo']);
        $ativo->setLayout('horizontal');
        $ativo->setUseButton();
        $ativo->setValue('Y');
        
        // Validações
        $designacao->addValidation('Designação', new TRequiredValidator);
        
        // Layout
        $this->form->addFields([new TLabel('ID')], [$id]);
        $this->form->addFields([new TLabel('Designação *')], [$designacao]);
        $this->form->addFields([new TLabel('Abreviatura')], [$abreviatura]);
        $this->form->addFields([new TLabel('Nível Hierárquico')], [$nivel_hierarquico]);
        $this->form->addFields([new TLabel('Estado')], [$ativo]);
        
        // Ações
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Novo', new TAction([$this, 'onEdit']), 'fa:plus blue');
        $this->form->addActionLink('Voltar', new TAction(['PatenteList', 'onReload']), 'fa:arrow-left red');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'PatenteList'));
        $container->add($this->form);
        
        parent::add($container);
    }
}
