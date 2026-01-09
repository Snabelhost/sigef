<?php
/**
 * InstituicaoForm - Formulário de Instituições
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class InstituicaoForm extends TPage
{
    protected $form;
    
    use Adianti\Base\AdiantiStandardFormTrait;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('sigef');
        $this->setActiveRecord('Instituicao');
        
        // Formulário
        $this->form = new BootstrapFormBuilder('form_instituicao');
        $this->form->setFormTitle('Instituição de Ensino');
        
        // Campos
        $id = new TEntry('id');
        $tipo_instituicao_id = new TDBCombo('tipo_instituicao_id', 'sigef', 'TipoInstituicao', 'id', '{codigo} - {designacao}', 'designacao');
        $designacao = new TEntry('designacao');
        $sigla = new TEntry('sigla');
        $endereco = new TText('endereco');
        $telefone = new TEntry('telefone');
        $email = new TEntry('email');
        $logo = new TFile('logo');
        $ativo = new TRadioGroup('ativo');
        
        // Configurações
        $id->setEditable(FALSE);
        $tipo_instituicao_id->setSize('100%');
        $tipo_instituicao_id->enableSearch();
        $designacao->setSize('100%');
        $sigla->setSize('100%');
        $endereco->setSize('100%', 80);
        $telefone->setSize('100%');
        $email->setSize('100%');
        $logo->setAllowedExtensions(['jpg', 'png', 'gif']);
        $ativo->addItems(['Y' => 'Activo', 'N' => 'Inactivo']);
        $ativo->setLayout('horizontal');
        $ativo->setUseButton();
        $ativo->setValue('Y');
        
        // Validações
        $tipo_instituicao_id->addValidation('Tipo de Instituição', new TRequiredValidator);
        $designacao->addValidation('Designação', new TRequiredValidator);
        
        // Layout
        $this->form->addFields([new TLabel('ID')], [$id], [new TLabel('Tipo *')], [$tipo_instituicao_id]);
        $this->form->addFields([new TLabel('Designação *')], [$designacao], [new TLabel('Sigla')], [$sigla]);
        $this->form->addFields([new TLabel('Endereço')], [$endereco]);
        $this->form->addFields([new TLabel('Telefone')], [$telefone], [new TLabel('Email')], [$email]);
        $this->form->addFields([new TLabel('Logo')], [$logo]);
        $this->form->addFields([new TLabel('Estado')], [$ativo]);
        
        // Ações
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Novo', new TAction([$this, 'onEdit']), 'fa:plus blue');
        $this->form->addActionLink('Voltar', new TAction(['InstituicaoList', 'onReload']), 'fa:arrow-left red');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'InstituicaoList'));
        $container->add($this->form);
        
        parent::add($container);
    }
}
