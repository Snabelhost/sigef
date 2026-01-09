<?php
/**
 * MapaCurso Active Record
 * Mapa de Cursos - Planeamento Estratégico
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class MapaCurso extends TRecord
{
    const TABLENAME = 'mapa_curso';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    private $instituicao;
    private $orgao;
    private $ano_lectivo;
    private $planos;
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('instituicao_id');
        parent::addAttribute('orgao_id');
        parent::addAttribute('ano_lectivo_id');
        parent::addAttribute('designacao');
        parent::addAttribute('numero_vagas');
        parent::addAttribute('data_inicio');
        parent::addAttribute('data_fim');
        parent::addAttribute('local');
        parent::addAttribute('status');
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
    
    /**
     * Retorna o órgão
     */
    public function get_orgao()
    {
        if (!empty($this->orgao_id) && empty($this->orgao)) {
            $this->orgao = new Orgao($this->orgao_id);
        }
        return $this->orgao;
    }
    
    /**
     * Retorna o ano lectivo
     */
    public function get_ano_lectivo()
    {
        if (empty($this->ano_lectivo)) {
            $this->ano_lectivo = new AnoLectivo($this->ano_lectivo_id);
        }
        return $this->ano_lectivo;
    }
    
    /**
     * Retorna os planos de curso
     */
    public function get_planos()
    {
        if (empty($this->planos)) {
            $repository = new TRepository('PlanoCurso');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('mapa_curso_id', '=', $this->id));
            $criteria->setProperty('order', 'ordem');
            $this->planos = $repository->load($criteria);
        }
        return $this->planos;
    }
    
    /**
     * Verifica se tem plano de curso activo
     */
    public function temPlanoActivo()
    {
        $repository = new TRepository('PlanoCurso');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('mapa_curso_id', '=', $this->id));
        $criteria->add(new TFilter('status', '=', 'Activo'));
        
        return $repository->count($criteria) > 0;
    }
    
    /**
     * Conta matrículas activas
     */
    public function contarMatriculas()
    {
        $repository = new TRepository('Matricula');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('mapa_curso_id', '=', $this->id));
        $criteria->add(new TFilter('status', '=', 'Activa'));
        
        return $repository->count($criteria);
    }
    
    /**
     * Verifica se há vagas disponíveis
     */
    public function temVagas()
    {
        $matriculados = $this->contarMatriculas();
        return $matriculados < $this->numero_vagas;
    }
    
    /**
     * Valida se pode receber matrículas
     */
    public function podeReceberMatriculas()
    {
        // Verificar se tem plano activo
        if (!$this->temPlanoActivo()) {
            throw new Exception('Este curso não possui Plano de Curso activo. Não é possível realizar matrículas.');
        }
        
        // Verificar se ano lectivo está aberto
        $anoLectivo = $this->get_ano_lectivo();
        if (!$anoLectivo->isAberto()) {
            throw new Exception('O Ano Lectivo deste curso não está aberto.');
        }
        
        // Verificar vagas
        if (!$this->temVagas()) {
            throw new Exception('Não há vagas disponíveis neste curso.');
        }
        
        // Verificar status do curso
        if (!in_array($this->status, ['Aberto', 'Em Curso'])) {
            throw new Exception('Este curso não está aberto para matrículas.');
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
}
