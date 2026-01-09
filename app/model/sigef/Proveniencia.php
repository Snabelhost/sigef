<?php
/**
 * Proveniencia Active Record
 * Origem do formando (Civil, Militar, Reingresso, etc.)
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class Proveniencia extends TRecord
{
    const TABLENAME = 'proveniencia';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('designacao');
        parent::addAttribute('descricao');
        parent::addAttribute('ativo');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }
    
    /**
     * Retorna proveniências ativas
     */
    public static function getProvenienciasAtivas()
    {
        $repository = new TRepository('Proveniencia');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('ativo', '=', 'Y'));
        $criteria->setProperty('order', 'designacao');
        
        return $repository->load($criteria);
    }
    
    /**
     * Verifica se é civil
     */
    public function isCivil()
    {
        return strtolower($this->designacao) === 'civil';
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
