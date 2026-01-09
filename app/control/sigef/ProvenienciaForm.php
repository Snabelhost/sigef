<?php
/**
 * ProvenienciaForm - Formulário de Proveniências
 */
class ProvenienciaForm extends TPage
{
    protected $form;
    
    use Adianti\Base\AdiantiStandardFormTrait;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('sigef');
        $this->setActiveRecord('Proveniencia');
        
        $this->form = new BootstrapFormBuilder('form_proveniencia');
        $this->form->setFormTitle('Proveniência');
        
        $id = new TEntry('id');
        $designacao = new TEntry('designacao');
        $descricao = new TText('descricao');
        $ativo = new TRadioGroup('ativo');
        
        $id->setEditable(FALSE);
        $designacao->setSize('100%');
        $descricao->setSize('100%', 80);
        $ativo->addItems(['Y' => 'Activo', 'N' => 'Inactivo']);
        $ativo->setLayout('horizontal');
        $ativo->setUseButton();
        $ativo->setValue('Y');
        
        $designacao->addValidation('Designação', new TRequiredValidator);
        
        $this->form->addFields([new TLabel('ID')], [$id]);
        $this->form->addFields([new TLabel('Designação *')], [$designacao]);
        $this->form->addFields([new TLabel('Descrição')], [$descricao]);
        $this->form->addFields([new TLabel('Estado')], [$ativo]);
        
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Novo', new TAction([$this, 'onEdit']), 'fa:plus blue');
        $this->form->addActionLink('Voltar', new TAction(['ProvenienciaList', 'onReload']), 'fa:arrow-left red');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'ProvenienciaList'));
        $container->add($this->form);
        
        parent::add($container);
    }
}
