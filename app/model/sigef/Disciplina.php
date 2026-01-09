<?php
/**
 * Disciplina Active Record
 * Disciplinas/matérias dos cursos
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class Disciplina extends TRecord
{
    const TABLENAME = 'disciplina';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('codigo');
        parent::addAttribute('designacao');
        parent::addAttribute('carga_horaria');
        parent::addAttribute('descricao');
        parent::addAttribute('ativo');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }
    
    /**
     * Retorna disciplinas ativas
     */
    public static function getDisciplinasAtivas()
    {
        $repository = new TRepository('Disciplina');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('ativo', '=', 'Y'));
        $criteria->setProperty('order', 'designacao');
        
        return $repository->load($criteria);
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
