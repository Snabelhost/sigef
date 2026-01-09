<?php
/**
 * TurmaFormando Active Record
 * Relação entre Turma e Formandos
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class TurmaFormando extends TRecord
{
    const TABLENAME = 'turma_formando';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    private $turma;
    private $formando;
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('turma_id');
        parent::addAttribute('formando_id');
        parent::addAttribute('data_inscricao');
        parent::addAttribute('status');
        parent::addAttribute('observacoes');
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
     * Retorna o formando
     */
    public function get_formando()
    {
        if (empty($this->formando)) {
            $this->formando = new Formando($this->formando_id);
        }
        return $this->formando;
    }
    
    public function store()
    {
        if (empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        
        parent::store();
    }
}
