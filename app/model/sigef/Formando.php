<?php
/**
 * Formando Active Record
 * Gestão de Formandos com ciclo de vida EPP
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class Formando extends TRecord
{
    const TABLENAME = 'formando';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    // Status formativos
    const STATUS_CANDIDATO = 'Candidato';
    const STATUS_ALISTADO = 'Alistado';
    const STATUS_RECRUTA = 'Recruta';
    const STATUS_INSTRUENDO = 'Instruendo';
    const STATUS_ALUNO = 'Aluno';
    const STATUS_FORMADO = 'Formado';
    const STATUS_DESISTENTE = 'Desistente';
    const STATUS_EXPULSO = 'Expulso';
    
    private $instituicao;
    private $patente;
    private $proveniencia;
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('instituicao_id');
        parent::addAttribute('patente_id');
        parent::addAttribute('proveniencia_id');
        parent::addAttribute('numero_ordem');
        parent::addAttribute('nome_completo');
        parent::addAttribute('genero');
        parent::addAttribute('data_nascimento');
        parent::addAttribute('bi');
        parent::addAttribute('telefone');
        parent::addAttribute('email');
        parent::addAttribute('endereco');
        parent::addAttribute('foto');
        parent::addAttribute('status_formativo');
        parent::addAttribute('data_alistamento');
        parent::addAttribute('data_recruta');
        parent::addAttribute('data_instruendo');
        parent::addAttribute('ibm_concluida');
        parent::addAttribute('observacoes');
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
    
    /**
     * Retorna a patente
     */
    public function get_patente()
    {
        if (!empty($this->patente_id) && empty($this->patente)) {
            $this->patente = new Patente($this->patente_id);
        }
        return $this->patente;
    }
    
    /**
     * Retorna a proveniência
     */
    public function get_proveniencia()
    {
        if (!empty($this->proveniencia_id) && empty($this->proveniencia)) {
            $this->proveniencia = new Proveniencia($this->proveniencia_id);
        }
        return $this->proveniencia;
    }
    
    /**
     * Define o status inicial com base no tipo de instituição
     */
    public function definirStatusInicial()
    {
        $instituicao = $this->get_instituicao();
        
        if ($instituicao->isEPP()) {
            // EPP: inicia como Candidato, depois Alistado
            $this->status_formativo = self::STATUS_CANDIDATO;
        } else {
            // Outras instituições: sempre Aluno
            $this->status_formativo = self::STATUS_ALUNO;
        }
    }
    
    /**
     * Transiciona o status formativo (EPP only)
     * Regras:
     * - Candidato → Alistado (após aprovação)
     * - Alistado → Recruta (início 1ª Fase IBM)
     * - Recruta → Instruendo (após IBM concluída)
     */
    public function transicionarStatus($novoStatus)
    {
        $instituicao = $this->get_instituicao();
        
        // Se não for EPP, não permite transições (sempre Aluno)
        if (!$instituicao->isEPP()) {
            throw new Exception('Transição de status só é permitida para instituições EPP.');
        }
        
        $transicoes = [
            self::STATUS_CANDIDATO => [self::STATUS_ALISTADO],
            self::STATUS_ALISTADO => [self::STATUS_RECRUTA, self::STATUS_DESISTENTE],
            self::STATUS_RECRUTA => [self::STATUS_INSTRUENDO, self::STATUS_DESISTENTE, self::STATUS_EXPULSO],
            self::STATUS_INSTRUENDO => [self::STATUS_FORMADO, self::STATUS_DESISTENTE, self::STATUS_EXPULSO],
        ];
        
        // Verificar se transição é permitida
        if (!isset($transicoes[$this->status_formativo]) || 
            !in_array($novoStatus, $transicoes[$this->status_formativo])) {
            throw new Exception("Transição de '{$this->status_formativo}' para '{$novoStatus}' não é permitida.");
        }
        
        // Regra específica: Recruta → Instruendo requer IBM concluída
        if ($this->status_formativo === self::STATUS_RECRUTA && 
            $novoStatus === self::STATUS_INSTRUENDO) {
            if ($this->ibm_concluida !== 'Y') {
                throw new Exception('Não é possível transicionar para Instruendo. A fase IBM (Instrução Básica Militar) deve ser concluída primeiro.');
            }
        }
        
        // Registrar datas de transição
        $this->status_formativo = $novoStatus;
        $data = date('Y-m-d');
        
        switch ($novoStatus) {
            case self::STATUS_ALISTADO:
                $this->data_alistamento = $data;
                break;
            case self::STATUS_RECRUTA:
                $this->data_recruta = $data;
                break;
            case self::STATUS_INSTRUENDO:
                $this->data_instruendo = $data;
                break;
        }
        
        return $this;
    }
    
    /**
     * Marcar IBM como concluída
     */
    public function concluirIBM()
    {
        if ($this->status_formativo !== self::STATUS_RECRUTA) {
            throw new Exception('Apenas Recrutas podem concluir a fase IBM.');
        }
        
        $this->ibm_concluida = 'Y';
        return $this;
    }
    
    /**
     * Retorna o nome do status formatado
     */
    public function getStatusFormatado()
    {
        return $this->status_formativo;
    }
    
    /**
     * Verifica se é civil (não tem patente)
     */
    public function isCivil()
    {
        return empty($this->patente_id);
    }
    
    public function store()
    {
        // Validar instituição
        $instituicao = $this->get_instituicao();
        
        // Se for ISP, validar que não é civil
        if ($instituicao->isISP() && $this->isCivil()) {
            throw new Exception('ISP: Matrícula de civis não é permitida. O formando deve ter patente de Agente ou superior.');
        }
        
        // Definir status inicial se novo registro
        if (empty($this->id) && empty($this->status_formativo)) {
            $this->definirStatusInicial();
        }
        
        if (empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        $this->updated_at = date('Y-m-d H:i:s');
        
        parent::store();
    }
}
