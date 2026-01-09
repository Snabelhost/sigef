<?php
/**
 * Formador Active Record
 * Gestão de Formadores/Instrutores
 *
 * @version    1.0
 * @package    sigef
 * @author     SIGEF
 */
class Formador extends TRecord
{
    const TABLENAME = 'formador';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    private $instituicao;
    private $patente;
    private $disciplinas;
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('instituicao_id');
        parent::addAttribute('patente_id');
        parent::addAttribute('numero_ordem');
        parent::addAttribute('nome_completo');
        parent::addAttribute('genero');
        parent::addAttribute('tipo_formador');
        parent::addAttribute('grau_academico');
        parent::addAttribute('especialidade');
        parent::addAttribute('telefone');
        parent::addAttribute('email');
        parent::addAttribute('foto');
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
     * Retorna as disciplinas que leciona
     */
    public function get_disciplinas()
    {
        if (empty($this->disciplinas)) {
            $repository = new TRepository('FormadorDisciplina');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('formador_id', '=', $this->id));
            $this->disciplinas = $repository->load($criteria);
        }
        return $this->disciplinas;
    }
    
    /**
     * Adiciona disciplina ao formador
     */
    public function adicionarDisciplina($disciplina_id)
    {
        $formadorDisciplina = new FormadorDisciplina;
        $formadorDisciplina->formador_id = $this->id;
        $formadorDisciplina->disciplina_id = $disciplina_id;
        $formadorDisciplina->store();
        
        return $formadorDisciplina;
    }
    
    /**
     * Remove disciplina do formador
     */
    public function removerDisciplina($disciplina_id)
    {
        $repository = new TRepository('FormadorDisciplina');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('formador_id', '=', $this->id));
        $criteria->add(new TFilter('disciplina_id', '=', $disciplina_id));
        
        $repository->delete($criteria);
    }
    
    /**
     * Verifica se está activo
     */
    public function isActivo()
    {
        return $this->status === 'Activo';
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
