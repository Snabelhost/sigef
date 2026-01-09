<?php
/**
 * FormadorDisciplina Active Record
 * Relação entre Formador e Disciplinas
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class FormadorDisciplina extends TRecord
{
    const TABLENAME = 'formador_disciplina';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    private $formador;
    private $disciplina;
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('formador_id');
        parent::addAttribute('disciplina_id');
        parent::addAttribute('created_at');
    }
    
    /**
     * Retorna o formador
     */
    public function get_formador()
    {
        if (empty($this->formador)) {
            $this->formador = new Formador($this->formador_id);
        }
        return $this->formador;
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
