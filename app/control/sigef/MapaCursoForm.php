<?php
/**
 * MapaCursoForm - Formulário de Mapa de Cursos
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class MapaCursoForm extends TPage
{
    protected $form;
    
    use Adianti\Base\AdiantiStandardFormTrait;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('sigef');
        $this->setActiveRecord('MapaCurso');
        
        // Formulário
        $this->form = new BootstrapFormBuilder('form_mapa_curso');
        $this->form->setFormTitle('Mapa de Curso');
        
        // Campos
        $id = new TEntry('id');
        $instituicao_id = new TDBCombo('instituicao_id', 'sigef', 'Instituicao', 'id', 'designacao', 'designacao');
        $orgao_id = new TDBCombo('orgao_id', 'sigef', 'Orgao', 'id', 'designacao', 'designacao');
        $ano_lectivo_id = new TDBCombo('ano_lectivo_id', 'sigef', 'AnoLectivo', 'id', 'ano', 'ano desc');
        $designacao = new TEntry('designacao');
        $numero_vagas = new TSpinner('numero_vagas');
        $data_inicio = new TDate('data_inicio');
        $data_fim = new TDate('data_fim');
        $local = new TEntry('local');
        $status = new TCombo('status');
        
        // Configurações
        $id->setEditable(FALSE);
        $instituicao_id->setSize('100%');
        $instituicao_id->enableSearch();
        $orgao_id->setSize('100%');
        $orgao_id->enableSearch();
        $ano_lectivo_id->setSize('100%');
        $designacao->setSize('100%');
        $numero_vagas->setRange(1, 1000, 1);
        $numero_vagas->setValue(30);
        $data_inicio->setMask('dd/mm/yyyy');
        $data_inicio->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setMask('dd/mm/yyyy');
        $data_fim->setDatabaseMask('yyyy-mm-dd');
        $local->setSize('100%');
        $status->addItems([
            'Planeamento' => 'Planeamento',
            'Aberto' => 'Aberto',
            'Em Curso' => 'Em Curso',
            'Concluído' => 'Concluído',
            'Cancelado' => 'Cancelado'
        ]);
        $status->setValue('Planeamento');
        
        // Validações
        $instituicao_id->addValidation('Instituição', new TRequiredValidator);
        $ano_lectivo_id->addValidation('Ano Lectivo', new TRequiredValidator);
        $designacao->addValidation('Designação', new TRequiredValidator);
        
        // Layout
        $this->form->addFields([new TLabel('ID')], [$id], [new TLabel('Status')], [$status]);
        $this->form->addFields([new TLabel('Instituição *')], [$instituicao_id], [new TLabel('Órgão')], [$orgao_id]);
        $this->form->addFields([new TLabel('Ano Lectivo *')], [$ano_lectivo_id], [new TLabel('Nº Vagas')], [$numero_vagas]);
        $this->form->addFields([new TLabel('Designação *')], [$designacao]);
        $this->form->addFields([new TLabel('Data Início')], [$data_inicio], [new TLabel('Data Fim')], [$data_fim]);
        $this->form->addFields([new TLabel('Local')], [$local]);
        
        // Ações
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Novo', new TAction([$this, 'onEdit']), 'fa:plus blue');
        $this->form->addActionLink('Voltar', new TAction(['MapaCursoList', 'onReload']), 'fa:arrow-left red');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'MapaCursoList'));
        $container->add($this->form);
        
        parent::add($container);
    }
}
