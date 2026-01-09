<?php
/**
 * DisciplinaForm - Formulário de Disciplinas
 */
class DisciplinaForm extends TPage
{
    protected $form;
    
    use Adianti\Base\AdiantiStandardFormTrait;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('sigef');
        $this->setActiveRecord('Disciplina');
        
        $this->form = new BootstrapFormBuilder('form_disciplina');
        $this->form->setFormTitle('Disciplina');
        
        $id = new TEntry('id');
        $codigo = new TEntry('codigo');
        $designacao = new TEntry('designacao');
        $carga_horaria = new TSpinner('carga_horaria');
        $descricao = new TText('descricao');
        $ativo = new TRadioGroup('ativo');
        
        $id->setEditable(FALSE);
        $codigo->setSize('100%');
        $designacao->setSize('100%');
        $carga_horaria->setRange(1, 500, 1);
        $descricao->setSize('100%', 80);
        $ativo->addItems(['Y' => 'Activo', 'N' => 'Inactivo']);
        $ativo->setLayout('horizontal');
        $ativo->setUseButton();
        $ativo->setValue('Y');
        
        $designacao->addValidation('Designação', new TRequiredValidator);
        
        $this->form->addFields([new TLabel('ID')], [$id], [new TLabel('Código')], [$codigo]);
        $this->form->addFields([new TLabel('Designação *')], [$designacao], [new TLabel('Carga Horária')], [$carga_horaria]);
        $this->form->addFields([new TLabel('Descrição')], [$descricao]);
        $this->form->addFields([new TLabel('Estado')], [$ativo]);
        
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Novo', new TAction([$this, 'onEdit']), 'fa:plus blue');
        $this->form->addActionLink('Voltar', new TAction(['DisciplinaList', 'onReload']), 'fa:arrow-left red');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'DisciplinaList'));
        $container->add($this->form);
        
        parent::add($container);
    }
}
