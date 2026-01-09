<?php
/**
 * FormadorForm - Formulário de Formadores
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class FormadorForm extends TPage
{
    protected $form;
    
    use Adianti\Base\AdiantiStandardFormTrait;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('sigef');
        $this->setActiveRecord('Formador');
        
        // Formulário
        $this->form = new BootstrapFormBuilder('form_formador');
        $this->form->setFormTitle('Cadastro de Formador');
        
        // Campos
        $id = new TEntry('id');
        $instituicao_id = new TDBCombo('instituicao_id', 'sigef', 'Instituicao', 'id', 'designacao', 'designacao');
        $patente_id = new TDBCombo('patente_id', 'sigef', 'Patente', 'id', 'designacao', 'nivel_hierarquico desc');
        $numero_ordem = new TEntry('numero_ordem');
        $nome_completo = new TEntry('nome_completo');
        $genero = new TRadioGroup('genero');
        $tipo_formador = new TCombo('tipo_formador');
        $grau_academico = new TEntry('grau_academico');
        $especialidade = new TEntry('especialidade');
        $telefone = new TEntry('telefone');
        $email = new TEntry('email');
        $foto = new TFile('foto');
        $status = new TRadioGroup('status');
        
        // Configurações
        $id->setEditable(FALSE);
        $instituicao_id->setSize('100%');
        $instituicao_id->enableSearch();
        $patente_id->setSize('100%');
        $patente_id->enableSearch();
        $numero_ordem->setSize('100%');
        $nome_completo->setSize('100%');
        $genero->addItems(['Masculino' => 'Masculino', 'Feminino' => 'Feminino']);
        $genero->setLayout('horizontal');
        $genero->setUseButton();
        $tipo_formador->addItems([
            'Tempo Integral' => 'Tempo Integral',
            'Tempo Parcial' => 'Tempo Parcial',
            'Convidado' => 'Convidado'
        ]);
        $tipo_formador->setValue('Tempo Integral');
        $grau_academico->setSize('100%');
        $especialidade->setSize('100%');
        $telefone->setSize('100%');
        $email->setSize('100%');
        $foto->setAllowedExtensions(['jpg', 'png', 'gif']);
        $status->addItems(['Activo' => 'Activo', 'Inactivo' => 'Inactivo']);
        $status->setLayout('horizontal');
        $status->setUseButton();
        $status->setValue('Activo');
        
        // Validações
        $instituicao_id->addValidation('Instituição', new TRequiredValidator);
        $nome_completo->addValidation('Nome Completo', new TRequiredValidator);
        
        // Layout
        $this->form->addFields([new TLabel('ID')], [$id], [new TLabel('Nº Ordem')], [$numero_ordem]);
        $this->form->addFields([new TLabel('Instituição *')], [$instituicao_id], [new TLabel('Patente')], [$patente_id]);
        $this->form->addFields([new TLabel('Nome Completo *')], [$nome_completo]);
        $this->form->addFields([new TLabel('Género')], [$genero], [new TLabel('Tipo de Formador')], [$tipo_formador]);
        $this->form->addFields([new TLabel('Grau Académico')], [$grau_academico], [new TLabel('Especialidade')], [$especialidade]);
        $this->form->addFields([new TLabel('Telefone')], [$telefone], [new TLabel('Email')], [$email]);
        $this->form->addFields([new TLabel('Foto')], [$foto], [new TLabel('Estado')], [$status]);
        
        // Ações
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Novo', new TAction([$this, 'onEdit']), 'fa:plus blue');
        $this->form->addActionLink('Voltar', new TAction(['FormadorList', 'onReload']), 'fa:arrow-left red');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'FormadorList'));
        $container->add($this->form);
        
        parent::add($container);
    }
}
