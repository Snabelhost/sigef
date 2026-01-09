<?php
/**
 * AnoLectivoForm - Formulário de Anos Lectivos
 */
class AnoLectivoForm extends TPage
{
    protected $form;
    
    use Adianti\Base\AdiantiStandardFormTrait;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('sigef');
        $this->setActiveRecord('AnoLectivo');
        
        $this->form = new BootstrapFormBuilder('form_ano_lectivo');
        $this->form->setFormTitle('Ano Lectivo');
        
        $id = new TEntry('id');
        $ano = new TEntry('ano');
        $data_inicio = new TDate('data_inicio');
        $data_fim = new TDate('data_fim');
        $status = new TCombo('status');
        $ativo = new TRadioGroup('ativo');
        
        $id->setEditable(FALSE);
        $ano->setSize('100%');
        $ano->setMask('9999/9999');
        $data_inicio->setMask('dd/mm/yyyy');
        $data_inicio->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setMask('dd/mm/yyyy');
        $data_fim->setDatabaseMask('yyyy-mm-dd');
        $status->addItems(['Aberto' => 'Aberto', 'Fechado' => 'Fechado', 'Planeamento' => 'Planeamento']);
        $status->setValue('Planeamento');
        $ativo->addItems(['Y' => 'Activo', 'N' => 'Inactivo']);
        $ativo->setLayout('horizontal');
        $ativo->setUseButton();
        $ativo->setValue('Y');
        
        $ano->addValidation('Ano', new TRequiredValidator);
        
        $this->form->addFields([new TLabel('ID')], [$id], [new TLabel('Status')], [$status]);
        $this->form->addFields([new TLabel('Ano Lectivo *')], [$ano], [new TLabel('Estado')], [$ativo]);
        $this->form->addFields([new TLabel('Data Início')], [$data_inicio], [new TLabel('Data Fim')], [$data_fim]);
        
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Novo', new TAction([$this, 'onEdit']), 'fa:plus blue');
        $this->form->addActionLink('Voltar', new TAction(['AnoLectivoList', 'onReload']), 'fa:arrow-left red');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'AnoLectivoList'));
        $container->add($this->form);
        
        parent::add($container);
    }
}
