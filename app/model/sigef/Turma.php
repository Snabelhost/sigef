<?php
/**
 * Turma Active Record
 * Turmas de formandos vinculadas a mapas de curso
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class Turma extends TRecord
{
    const TABLENAME = 'turma';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    private $mapa_curso;
    private $formandos;
    private $disciplinas;
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('mapa_curso_id');
        parent::addAttribute('designacao');
        parent::addAttribute('codigo');
        parent::addAttribute('ano_lectivo_id');
        parent::addAttribute('turno');
        parent::addAttribute('sala');
        parent::addAttribute('capacidade');
        parent::addAttribute('status');
        parent::addAttribute('observacoes');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }
    
    /**
     * Retorna o mapa de curso
     */
    public function get_mapa_curso()
    {
        if (empty($this->mapa_curso)) {
            $this->mapa_curso = new MapaCurso($this->mapa_curso_id);
        }
        return $this->mapa_curso;
    }
    
    /**
     * Retorna os formandos da turma
     */
    public function get_formandos()
    {
        if (empty($this->formandos)) {
            $repository = new TRepository('TurmaFormando');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('turma_id', '=', $this->id));
            $this->formandos = $repository->load($criteria);
        }
        return $this->formandos;
    }
    
    /**
     * Retorna as disciplinas da turma
     */
    public function get_disciplinas()
    {
        if (empty($this->disciplinas)) {
            $repository = new TRepository('TurmaDisciplina');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('turma_id', '=', $this->id));
            $criteria->setProperty('order', 'ordem');
            $this->disciplinas = $repository->load($criteria);
        }
        return $this->disciplinas;
    }
    
    /**
     * Conta formandos na turma
     */
    public function contarFormandos()
    {
        $repository = new TRepository('TurmaFormando');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('turma_id', '=', $this->id));
        return $repository->count($criteria);
    }
    
    /**
     * Verifica se tem vagas
     */
    public function temVagas()
    {
        return $this->contarFormandos() < $this->capacidade;
    }
    
    /**
     * Adiciona formando à turma
     */
    public function adicionarFormando($formando_id)
    {
        if (!$this->temVagas()) {
            throw new Exception('Esta turma está lotada. Não há vagas disponíveis.');
        }
        
        // Verificar se já está na turma
        $repository = new TRepository('TurmaFormando');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('turma_id', '=', $this->id));
        $criteria->add(new TFilter('formando_id', '=', $formando_id));
        
        if ($repository->count($criteria) > 0) {
            throw new Exception('Este formando já está inscrito nesta turma.');
        }
        
        $turmaFormando = new TurmaFormando;
        $turmaFormando->turma_id = $this->id;
        $turmaFormando->formando_id = $formando_id;
        $turmaFormando->data_inscricao = date('Y-m-d');
        $turmaFormando->status = 'Activo';
        $turmaFormando->store();
        
        return $turmaFormando;
    }
    
    /**
     * Adiciona disciplina à turma
     */
    public function adicionarDisciplina($disciplina_id, $formador_id = null, $ordem = 0)
    {
        $turmaDisciplina = new TurmaDisciplina;
        $turmaDisciplina->turma_id = $this->id;
        $turmaDisciplina->disciplina_id = $disciplina_id;
        $turmaDisciplina->formador_id = $formador_id;
        $turmaDisciplina->ordem = $ordem;
        $turmaDisciplina->store();
        
        return $turmaDisciplina;
    }
    
    public function store()
    {
        if (empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        $this->updated_at = date('Y-m-d H:i:s');
        
        parent::store();
    }
}
