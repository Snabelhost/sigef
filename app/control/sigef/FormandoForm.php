<?php
/**
 * FormandoForm - Formulário de Formandos
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class FormandoForm extends TPage
{
    protected $form;
    
    use Adianti\Base\AdiantiStandardFormTrait;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('sigef');
        $this->setActiveRecord('Formando');
        
        // Formulário
        $this->form = new BootstrapFormBuilder('form_formando');
        $this->form->setFormTitle('Cadastro de Formando');
        
        // Campos
        $id = new TEntry('id');
        $instituicao_id = new TDBCombo('instituicao_id', 'sigef', 'Instituicao', 'id', 'designacao', 'designacao');
        $patente_id = new TDBCombo('patente_id', 'sigef', 'Patente', 'id', 'designacao', 'nivel_hierarquico desc');
        $proveniencia_id = new TDBCombo('proveniencia_id', 'sigef', 'Proveniencia', 'id', 'designacao', 'designacao');
        $numero_ordem = new TEntry('numero_ordem');
        $nome_completo = new TEntry('nome_completo');
        $genero = new TRadioGroup('genero');
        $data_nascimento = new TDate('data_nascimento');
        $bi = new TEntry('bi');
        $telefone = new TEntry('telefone');
        $email = new TEntry('email');
        $endereco = new TText('endereco');
        $foto = new TFile('foto');
        $status_formativo = new TEntry('status_formativo');
        $observacoes = new TText('observacoes');
        $ativo = new TRadioGroup('ativo');
        
        // Configurações
        $id->setEditable(FALSE);
        $status_formativo->setEditable(FALSE);
        $instituicao_id->setSize('100%');
        $instituicao_id->enableSearch();
        $patente_id->setSize('100%');
        $patente_id->enableSearch();
        $proveniencia_id->setSize('100%');
        $numero_ordem->setSize('100%');
        $nome_completo->setSize('100%');
        $genero->addItems(['Masculino' => 'Masculino', 'Feminino' => 'Feminino']);
        $genero->setLayout('horizontal');
        $genero->setUseButton();
        $data_nascimento->setMask('dd/mm/yyyy');
        $data_nascimento->setDatabaseMask('yyyy-mm-dd');
        $bi->setSize('100%');
        $telefone->setSize('100%');
        $email->setSize('100%');
        $endereco->setSize('100%', 60);
        $foto->setAllowedExtensions(['jpg', 'png', 'gif']);
        $observacoes->setSize('100%', 80);
        $ativo->addItems(['Y' => 'Activo', 'N' => 'Inactivo']);
        $ativo->setLayout('horizontal');
        $ativo->setUseButton();
        $ativo->setValue('Y');
        
        // Validações
        $instituicao_id->addValidation('Instituição', new TRequiredValidator);
        $nome_completo->addValidation('Nome Completo', new TRequiredValidator);
        
        // Evento para mostrar alerta ISP
        $instituicao_id->setChangeAction(new TAction([$this, 'onInstituicaoChange']));
        
        // Layout
        $this->form->appendPage('Dados Pessoais');
        
        $this->form->addFields([new TLabel('ID')], [$id], [new TLabel('Nº Ordem')], [$numero_ordem]);
        $this->form->addFields([new TLabel('Instituição *')], [$instituicao_id], [new TLabel('Status')], [$status_formativo]);
        $this->form->addFields([new TLabel('Nome Completo *')], [$nome_completo]);
        $this->form->addFields([new TLabel('Patente')], [$patente_id], [new TLabel('Proveniência')], [$proveniencia_id]);
        $this->form->addFields([new TLabel('Género')], [$genero], [new TLabel('Data Nascimento')], [$data_nascimento]);
        $this->form->addFields([new TLabel('BI')], [$bi], [new TLabel('Telefone')], [$telefone]);
        $this->form->addFields([new TLabel('Email')], [$email]);
        $this->form->addFields([new TLabel('Endereço')], [$endereco]);
        
        $this->form->appendPage('Outros');
        
        $this->form->addFields([new TLabel('Foto')], [$foto]);
        $this->form->addFields([new TLabel('Observações')], [$observacoes]);
        $this->form->addFields([new TLabel('Estado')], [$ativo]);
        
        // Ações
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Novo', new TAction([$this, 'onEdit']), 'fa:plus blue');
        $this->form->addActionLink('Voltar', new TAction(['FormandoList', 'onReload']), 'fa:arrow-left red');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'FormandoList'));
        $container->add($this->form);
        
        parent::add($container);
    }
    
    public static function onInstituicaoChange($param)
    {
        if (!empty($param['instituicao_id']))
        {
            try
            {
                TTransaction::open('sigef');
                $instituicao = new Instituicao($param['instituicao_id']);
                
                if ($instituicao->isISP())
                {
                    TScript::create("
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atenção - ISP',
                            text: 'O Instituto Superior da Polícia só aceita formandos com patente de Agente ou superior. Civis não são permitidos.',
                            confirmButtonColor: '#3085d6'
                        });
                    ");
                }
                
                TTransaction::close();
            }
            catch (Exception $e)
            {
                TTransaction::rollback();
            }
        }
    }
}
