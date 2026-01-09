<?php
/**
 * TurmaForm - Formulário de Turmas
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class TurmaForm extends TPage
{
    protected $form;
    
    use Adianti\Base\AdiantiStandardFormTrait;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('sigef');
        $this->setActiveRecord('Turma');
        
        // Formulário
        $this->form = new BootstrapFormBuilder('form_turma');
        $this->form->setFormTitle('Turma');
        
        // Campos
        $id = new TEntry('id');
        $mapa_curso_id = new TDBCombo('mapa_curso_id', 'sigef', 'MapaCurso', 'id', 'designacao', 'designacao');
        $ano_lectivo_id = new TDBCombo('ano_lectivo_id', 'sigef', 'AnoLectivo', 'id', 'ano', 'ano desc');
        $designacao = new TEntry('designacao');
        $codigo = new TEntry('codigo');
        $turno = new TCombo('turno');
        $sala = new TEntry('sala');
        $capacidade = new TSpinner('capacidade');
        $status = new TCombo('status');
        $observacoes = new TText('observacoes');
        
        // Configurações
        $id->setEditable(FALSE);
        $mapa_curso_id->setSize('100%');
        $mapa_curso_id->enableSearch();
        $ano_lectivo_id->setSize('100%');
        $designacao->setSize('100%');
        $codigo->setSize('100%');
        $turno->addItems([
            'Manhã' => 'Manhã',
            'Tarde' => 'Tarde',
            'Noite' => 'Noite',
            'Integral' => 'Integral'
        ]);
        $sala->setSize('100%');
        $capacidade->setRange(1, 200, 1);
        $capacidade->setValue(30);
        $status->addItems([
            'Activa' => 'Activa',
            'Concluída' => 'Concluída',
            'Cancelada' => 'Cancelada'
        ]);
        $status->setValue('Activa');
        $observacoes->setSize('100%', 80);
        
        // Validações
        $mapa_curso_id->addValidation('Curso', new TRequiredValidator);
        $designacao->addValidation('Designação', new TRequiredValidator);
        
        // Layout
        $this->form->addFields([new TLabel('ID')], [$id], [new TLabel('Código')], [$codigo]);
        $this->form->addFields([new TLabel('Curso *')], [$mapa_curso_id], [new TLabel('Ano Lectivo')], [$ano_lectivo_id]);
        $this->form->addFields([new TLabel('Designação *')], [$designacao]);
        $this->form->addFields([new TLabel('Turno')], [$turno], [new TLabel('Sala')], [$sala]);
        $this->form->addFields([new TLabel('Capacidade')], [$capacidade], [new TLabel('Status')], [$status]);
        $this->form->addFields([new TLabel('Observações')], [$observacoes]);
        
        // Ações
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Novo', new TAction([$this, 'onEdit']), 'fa:plus blue');
        $this->form->addActionLink('Voltar', new TAction(['TurmaList', 'onReload']), 'fa:arrow-left red');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'TurmaList'));
        $container->add($this->form);
        
        parent::add($container);
    }
}
