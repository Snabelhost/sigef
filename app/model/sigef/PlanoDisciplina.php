<?php
/**
 * PlanoDisciplina Active Record
 * Relação entre Plano de Curso e Disciplinas
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class PlanoDisciplina extends TRecord
{
    const TABLENAME = 'plano_disciplina';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    private $plano_curso;
    private $disciplina;
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('plano_curso_id');
        parent::addAttribute('disciplina_id');
        parent::addAttribute('ordem');
        parent::addAttribute('obrigatoria');
        parent::addAttribute('created_at');
    }
    
    /**
     * Retorna o plano de curso
     */
    public function get_plano_curso()
    {
        if (empty($this->plano_curso)) {
            $this->plano_curso = new PlanoCurso($this->plano_curso_id);
        }
        return $this->plano_curso;
    }
    
    /**
     * Retorna a disciplina
     */
    public function get_disciplina()
    {
        if (empty($this->disciplina)) {
            $this->disciplina = new Disciplina($this->disciplina_id);
        }
        return $this->disciplina;
    }
    
    public function store()
    {
        if (empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        
        parent::store();
    }
}
