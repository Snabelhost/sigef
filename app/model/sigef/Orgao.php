<?php
/**
 * Orgao Active Record
 * Órgãos vinculados às instituições de ensino
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class Orgao extends TRecord
{
    const TABLENAME = 'orgao';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    private $instituicao;
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('instituicao_id');
        parent::addAttribute('designacao');
        parent::addAttribute('descricao');
        parent::addAttribute('ativo');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }
    
    /**
     * Retorna a instituição
     */
    public function get_instituicao()
    {
        if (empty($this->instituicao)) {
            $this->instituicao = new Instituicao($this->instituicao_id);
        }
        return $this->instituicao;
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
