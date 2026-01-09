<?php
/**
 * Matricula Active Record
 * Matrículas de formandos em cursos
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class Matricula extends TRecord
{
    const TABLENAME = 'matricula';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    private $formando;
    private $mapa_curso;
    private $ano_lectivo;
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('formando_id');
        parent::addAttribute('mapa_curso_id');
        parent::addAttribute('ano_lectivo_id');
        parent::addAttribute('data_matricula');
        parent::addAttribute('status');
        parent::addAttribute('observacoes');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
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
    
    /**
     * Retorna o mapa de curso
     */
    public function get_mapa_curso()
    {
        if (empty($this->mapa_curso)) {
            $this->mapa_curso = new MapaCurso($this->mapa_curso_id);
        }
        return $this->mapa_curso;
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
     * Verifica se está activa
     */
    public function isActiva()
    {
        return $this->status === 'Activa';
    }
    
    /**
     * Cancela a matrícula
     */
    public function cancelar($motivo = null)
    {
        $this->status = 'Cancelada';
        if ($motivo) {
            $this->observacoes = ($this->observacoes ? $this->observacoes . "\n" : '') . 
                                 "Cancelada em " . date('Y-m-d') . ": " . $motivo;
        }
        $this->store();
    }
    
    /**
     * Conclui a matrícula
     */
    public function concluir()
    {
        $this->status = 'Concluída';
        $this->store();
    }
    
    public function store()
    {
        // Validações antes de salvar
        if (empty($this->id)) {
            // Nova matrícula
            $mapaCurso = $this->get_mapa_curso();
            $formando = $this->get_formando();
            
            // Verificar se curso pode receber matrículas
            $mapaCurso->podeReceberMatriculas();
            
            // Verificar se formando pode ser matriculado na instituição
            $instituicao = $mapaCurso->get_instituicao();
            $instituicao->validarFormando($formando);
            
            // Verificar se formando já está matriculado neste curso
            $repository = new TRepository('Matricula');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('formando_id', '=', $this->formando_id));
            $criteria->add(new TFilter('mapa_curso_id', '=', $this->mapa_curso_id));
            $criteria->add(new TFilter('status', '=', 'Activa'));
            
            if ($repository->count($criteria) > 0) {
                throw new Exception('Este formando já está matriculado neste curso.');
            }
            
            // Definir data de matrícula
            if (empty($this->data_matricula)) {
                $this->data_matricula = date('Y-m-d');
            }
            
            // Definir ano lectivo
            if (empty($this->ano_lectivo_id)) {
                $this->ano_lectivo_id = $mapaCurso->ano_lectivo_id;
            }
        }
        
        if (empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        $this->updated_at = date('Y-m-d H:i:s');
        
        parent::store();
    }
}
