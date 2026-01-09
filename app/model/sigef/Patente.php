<?php
/**
 * Patente Active Record
 * Hierarquia policial da Polícia Nacional de Angola
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class Patente extends TRecord
{
    const TABLENAME = 'patente';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('designacao');
        parent::addAttribute('abreviatura');
        parent::addAttribute('nivel_hierarquico');
        parent::addAttribute('ativo');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }
    
    /**
     * Retorna patentes ativas ordenadas por nível hierárquico
     */
    public static function getPatentesAtivas()
    {
        $repository = new TRepository('Patente');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('ativo', '=', 'Y'));
        $criteria->setProperty('order', 'nivel_hierarquico DESC');
        
        return $repository->load($criteria);
    }
    
    /**
     * Verifica se patente permite acesso ao ISP (Agente ou superior)
     */
    public function permiteISP()
    {
        return $this->nivel_hierarquico >= 1; // Agente de 2ª Classe ou superior
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
