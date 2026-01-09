<?php
/**
 * PlanoCurso Active Record
 * Plano de Curso - Estrutura Pedagógica
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class PlanoCurso extends TRecord
{
    const TABLENAME = 'plano_curso';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    // Fases do curso EPP
    const FASE_IBM = 'IBM';
    const FASE_FORMACAO_POLICIAL = 'Formacao Policial';
    const FASE_UNICA = 'Unica';
    
    private $mapa_curso;
    private $disciplinas;
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('mapa_curso_id');
        parent::addAttribute('fase');
        parent::addAttribute('designacao');
        parent::addAttribute('ordem');
        parent::addAttribute('status');
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
     * Retorna as disciplinas do plano
     */
    public function get_disciplinas()
    {
        if (empty($this->disciplinas)) {
            $repository = new TRepository('PlanoDisciplina');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('plano_curso_id', '=', $this->id));
            $criteria->setProperty('order', 'ordem');
            $this->disciplinas = $repository->load($criteria);
        }
        return $this->disciplinas;
    }
    
    /**
     * Verifica se é fase IBM
     */
    public function isIBM()
    {
        return $this->fase === self::FASE_IBM;
    }
    
    /**
     * Verifica se está activo
     */
    public function isActivo()
    {
        return $this->status === 'Activo';
    }
    
    /**
     * Adiciona disciplina ao plano
     */
    public function adicionarDisciplina($disciplina_id, $ordem = 0, $obrigatoria = 'Y')
    {
        $planoDisciplina = new PlanoDisciplina;
        $planoDisciplina->plano_curso_id = $this->id;
        $planoDisciplina->disciplina_id = $disciplina_id;
        $planoDisciplina->ordem = $ordem;
        $planoDisciplina->obrigatoria = $obrigatoria;
        $planoDisciplina->store();
        
        return $planoDisciplina;
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
