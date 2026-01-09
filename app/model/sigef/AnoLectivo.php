<?php
/**
 * AnoLectivo Active Record
 * Gestão de anos lectivos
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class AnoLectivo extends TRecord
{
    const TABLENAME = 'ano_lectivo';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('ano');
        parent::addAttribute('data_inicio');
        parent::addAttribute('data_fim');
        parent::addAttribute('status');
        parent::addAttribute('ativo');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }
    
    /**
     * Retorna o ano lectivo ativo/aberto atual
     */
    public static function getAnoLectivoAberto()
    {
        $repository = new TRepository('AnoLectivo');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('status', '=', 'Aberto'));
        $criteria->add(new TFilter('ativo', '=', 'Y'));
        $criteria->setProperty('limit', 1);
        
        $objects = $repository->load($criteria);
        return $objects ? $objects[0] : null;
    }
    
    /**
     * Verifica se o ano lectivo está aberto
     */
    public function isAberto()
    {
        return $this->status === 'Aberto';
    }
    
    /**
     * Abre o ano lectivo (fecha os anteriores)
     */
    public function abrir()
    {
        TTransaction::open('sigef');
        
        // Fechar todos os anos lectivos abertos
        $repository = new TRepository('AnoLectivo');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('status', '=', 'Aberto'));
        $criteria->add(new TFilter('id', '!=', $this->id));
        
        $objects = $repository->load($criteria);
        foreach ($objects as $obj) {
            $obj->status = 'Fechado';
            $obj->store();
        }
        
        // Abrir este ano
        $this->status = 'Aberto';
        $this->store();
        
        TTransaction::close();
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
