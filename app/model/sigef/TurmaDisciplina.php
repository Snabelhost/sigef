<?php
/**
 * TurmaDisciplina Active Record
 * Relação entre Turma e Disciplinas (com formador responsável)
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class TurmaDisciplina extends TRecord
{
    const TABLENAME = 'turma_disciplina';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    private $turma;
    private $disciplina;
    private $formador;
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('turma_id');
        parent::addAttribute('disciplina_id');
        parent::addAttribute('formador_id');
        parent::addAttribute('ordem');
        parent::addAttribute('carga_horaria');
        parent::addAttribute('data_inicio');
        parent::addAttribute('data_fim');
        parent::addAttribute('status');
        parent::addAttribute('created_at');
    }
    
    /**
     * Retorna a turma
     */
    public function get_turma()
    {
        if (empty($this->turma)) {
            $this->turma = new Turma($this->turma_id);
        }
        return $this->turma;
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
    
    /**
     * Retorna o formador
     */
    public function get_formador()
    {
        if (!empty($this->formador_id) && empty($this->formador)) {
            $this->formador = new Formador($this->formador_id);
        }
        return $this->formador;
    }
    
    public function store()
    {
        if (empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        
        parent::store();
    }
}
