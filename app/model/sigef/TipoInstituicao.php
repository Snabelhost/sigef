<?php
/**
 * TipoInstituicao Active Record
 * Tipos de instituição: EPP, ISP, Colégio, Centro de Formação
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class TipoInstituicao extends TRecord
{
    const TABLENAME = 'tipo_instituicao';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    // Constantes para tipos
    const EPP = 'EPP';
    const ISP = 'ISP';
    const COLEGIO = 'COL';
    const CENTRO_FORMACAO = 'CFO';
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('codigo');
        parent::addAttribute('designacao');
        parent::addAttribute('nomenclatura_formando');
        parent::addAttribute('permite_ciclo_epp');
        parent::addAttribute('ativo');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }
    
    /**
     * Verifica se permite o ciclo EPP (Alistado → Recruta → Instruendo)
     */
    public function permiteCicloEPP()
    {
        return $this->permite_ciclo_epp === 'Y';
    }
    
    /**
     * Verifica se é EPP
     */
    public function isEPP()
    {
        return $this->codigo === self::EPP;
    }
    
    /**
     * Verifica se é ISP (exige patente de Agente ou superior)
     */
    public function isISP()
    {
        return $this->codigo === self::ISP;
    }
    
    /**
     * Retorna a nomenclatura correta para formando
     */
    public function getNomenclaturaFormando()
    {
        return $this->nomenclatura_formando ?: 'Aluno';
    }
    
    /**
     * Busca por código
     */
    public static function getByCodigo($codigo)
    {
        $repository = new TRepository('TipoInstituicao');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('codigo', '=', $codigo));
        
        $objects = $repository->load($criteria);
        return $objects ? $objects[0] : null;
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
