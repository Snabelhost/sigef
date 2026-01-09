<?php
/**
 * Instituicao Active Record
 * Instituições de ensino da Polícia Nacional
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class Instituicao extends TRecord
{
    const TABLENAME = 'instituicao';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    private $tipo_instituicao;
    private $orgaos;
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('system_unit_id');
        parent::addAttribute('tipo_instituicao_id');
        parent::addAttribute('designacao');
        parent::addAttribute('sigla');
        parent::addAttribute('endereco');
        parent::addAttribute('telefone');
        parent::addAttribute('email');
        parent::addAttribute('logo');
        parent::addAttribute('ativo');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }
    
    /**
     * Retorna o tipo de instituição
     */
    public function get_tipo_instituicao()
    {
        if (empty($this->tipo_instituicao)) {
            $this->tipo_instituicao = new TipoInstituicao($this->tipo_instituicao_id);
        }
        return $this->tipo_instituicao;
    }
    
    /**
     * Retorna os órgãos da instituição
     */
    public function get_orgaos()
    {
        if (empty($this->orgaos)) {
            $repository = new TRepository('Orgao');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('instituicao_id', '=', $this->id));
            $criteria->add(new TFilter('ativo', '=', 'Y'));
            $criteria->setProperty('order', 'designacao');
            $this->orgaos = $repository->load($criteria);
        }
        return $this->orgaos;
    }
    
    /**
     * Verifica se é EPP
     */
    public function isEPP()
    {
        return $this->get_tipo_instituicao()->isEPP();
    }
    
    /**
     * Verifica se é ISP
     */
    public function isISP()
    {
        return $this->get_tipo_instituicao()->isISP();
    }
    
    /**
     * Retorna a nomenclatura correta para formandos
     */
    public function getNomenclaturaFormando()
    {
        return $this->get_tipo_instituicao()->getNomenclaturaFormando();
    }
    
    /**
     * Valida se um formando pode ser matriculado nesta instituição
     */
    public function validarFormando($formando)
    {
        // ISP: apenas agentes ou superiores
        if ($this->isISP()) {
            if (empty($formando->patente_id)) {
                throw new Exception('ISP: Matrícula de civis não é permitida. O formando deve ter patente de Agente ou superior.');
            }
            
            $patente = new Patente($formando->patente_id);
            if (!$patente->permiteISP()) {
                throw new Exception('ISP: O formando deve ter patente de Agente de 2ª Classe ou superior.');
            }
        }
        
        return true;
    }
    
    public function store()
    {
        if (empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        $this->updated_at = date('Y-m-d H:i:s');
        
        parent::store();
    }
    
    public function delete($id = NULL)
    {
        // Verificar se há formandos vinculados
        $repository = new TRepository('Formando');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('instituicao_id', '=', $this->id));
        $count = $repository->count($criteria);
        
        if ($count > 0) {
            throw new Exception('Não é possível eliminar esta instituição pois existem formandos vinculados.');
        }
        
        parent::delete($id);
    }
}
