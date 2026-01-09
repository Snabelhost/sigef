-- ============================================
-- SIGEF - Sistema de Gestão Formativa
-- Polícia Nacional de Angola
-- Script de Criação da Base de Dados MySQL
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- TABELAS DO SISTEMA ADIANTI (PERMISSÕES)
-- ============================================

-- Tabela de grupos
CREATE TABLE IF NOT EXISTS system_group (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(256)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tabela de programas
CREATE TABLE IF NOT EXISTS system_program (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(256),
    controller VARCHAR(256)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tabela de unidades
CREATE TABLE IF NOT EXISTS system_unit (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(256),
    connection_name VARCHAR(256),
    custom_code VARCHAR(256)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tabela de roles
CREATE TABLE IF NOT EXISTS system_role (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(256),
    custom_code VARCHAR(256)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tabela de preferências
CREATE TABLE IF NOT EXISTS system_preference (
    id VARCHAR(256) PRIMARY KEY,
    value TEXT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS system_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(256),
    login VARCHAR(256),
    password VARCHAR(256),
    email VARCHAR(256),
    accepted_term_policy CHAR(1),
    phone VARCHAR(256),
    address VARCHAR(256),
    function_name VARCHAR(256),
    about TEXT,
    accepted_term_policy_at VARCHAR(20),
    accepted_term_policy_data TEXT,
    frontpage_id INT,
    system_unit_id INT,
    active CHAR(1),
    custom_code VARCHAR(256),
    otp_secret VARCHAR(256),
    INDEX idx_frontpage (frontpage_id),
    INDEX idx_unit (system_unit_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tabela de relação usuário-unidade
CREATE TABLE IF NOT EXISTS system_user_unit (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_user_id INT,
    system_unit_id INT,
    INDEX idx_user (system_user_id),
    INDEX idx_unit (system_unit_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tabela de relação usuário-grupo
CREATE TABLE IF NOT EXISTS system_user_group (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_user_id INT,
    system_group_id INT,
    INDEX idx_user (system_user_id),
    INDEX idx_group (system_group_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tabela de relação usuário-role
CREATE TABLE IF NOT EXISTS system_user_role (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_user_id INT,
    system_role_id INT,
    INDEX idx_user (system_user_id),
    INDEX idx_role (system_role_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tabela de relação grupo-programa
CREATE TABLE IF NOT EXISTS system_group_program (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_group_id INT,
    system_program_id INT,
    INDEX idx_group (system_group_id),
    INDEX idx_program (system_program_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tabela de relação usuário-programa
CREATE TABLE IF NOT EXISTS system_user_program (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_user_id INT,
    system_program_id INT,
    INDEX idx_user (system_user_id),
    INDEX idx_program (system_program_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tabela senhas antigas
CREATE TABLE IF NOT EXISTS system_user_old_password (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_user_id INT,
    password VARCHAR(256),
    created_at VARCHAR(20),
    INDEX idx_user (system_user_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tabela método-role programa
CREATE TABLE IF NOT EXISTS system_program_method_role (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_program_id INT,
    system_role_id INT,
    method_name VARCHAR(256),
    INDEX idx_program (system_program_id),
    INDEX idx_role (system_role_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- TABELAS SIGEF - CONFIGURAÇÕES CENTRAIS
-- ============================================

-- Patentes (hierarquia policial)
CREATE TABLE IF NOT EXISTS patente (
    id INT PRIMARY KEY AUTO_INCREMENT,
    designacao VARCHAR(100) NOT NULL,
    abreviatura VARCHAR(20),
    nivel_hierarquico INT DEFAULT 0,
    ativo CHAR(1) DEFAULT 'Y',
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_designacao (designacao),
    INDEX idx_nivel (nivel_hierarquico)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Proveniência
CREATE TABLE IF NOT EXISTS proveniencia (
    id INT PRIMARY KEY AUTO_INCREMENT,
    designacao VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo CHAR(1) DEFAULT 'Y',
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_designacao (designacao)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Ano Lectivo
CREATE TABLE IF NOT EXISTS ano_lectivo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ano VARCHAR(9) NOT NULL,
    data_inicio DATE,
    data_fim DATE,
    status ENUM(
        'Aberto',
        'Fechado',
        'Planeamento'
    ) DEFAULT 'Planeamento',
    ativo CHAR(1) DEFAULT 'Y',
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_ano (ano),
    INDEX idx_status (status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Tipo de Instituição
CREATE TABLE IF NOT EXISTS tipo_instituicao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(10) NOT NULL,
    designacao VARCHAR(100) NOT NULL,
    nomenclatura_formando VARCHAR(50) DEFAULT 'Aluno',
    permite_ciclo_epp CHAR(1) DEFAULT 'N',
    ativo CHAR(1) DEFAULT 'Y',
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE KEY uk_codigo (codigo),
    INDEX idx_designacao (designacao)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- TABELAS SIGEF - ESTRUTURA ORGANIZACIONAL
-- ============================================

-- Instituição de Ensino
CREATE TABLE IF NOT EXISTS instituicao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_unit_id INT,
    tipo_instituicao_id INT NOT NULL,
    designacao VARCHAR(200) NOT NULL,
    sigla VARCHAR(20),
    endereco TEXT,
    telefone VARCHAR(50),
    email VARCHAR(100),
    logo VARCHAR(500),
    ativo CHAR(1) DEFAULT 'Y',
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_tipo (tipo_instituicao_id),
    INDEX idx_designacao (designacao),
    INDEX idx_unit (system_unit_id),
    FOREIGN KEY (tipo_instituicao_id) REFERENCES tipo_instituicao (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Órgão
CREATE TABLE IF NOT EXISTS orgao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instituicao_id INT NOT NULL,
    designacao VARCHAR(200) NOT NULL,
    descricao TEXT,
    ativo CHAR(1) DEFAULT 'Y',
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_instituicao (instituicao_id),
    INDEX idx_designacao (designacao),
    FOREIGN KEY (instituicao_id) REFERENCES instituicao (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- TABELAS SIGEF - GESTÃO ACADÉMICA
-- ============================================

-- Disciplina
CREATE TABLE IF NOT EXISTS disciplina (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(20),
    designacao VARCHAR(200) NOT NULL,
    carga_horaria INT,
    descricao TEXT,
    ativo CHAR(1) DEFAULT 'Y',
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_codigo (codigo),
    INDEX idx_designacao (designacao)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Mapa de Curso
CREATE TABLE IF NOT EXISTS mapa_curso (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instituicao_id INT NOT NULL,
    orgao_id INT,
    ano_lectivo_id INT NOT NULL,
    designacao VARCHAR(200) NOT NULL,
    numero_vagas INT DEFAULT 0,
    data_inicio DATE,
    data_fim DATE,
    local VARCHAR(200),
    status ENUM(
        'Planeamento',
        'Aberto',
        'Em Curso',
        'Concluído',
        'Cancelado'
    ) DEFAULT 'Planeamento',
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_instituicao (instituicao_id),
    INDEX idx_ano_lectivo (ano_lectivo_id),
    INDEX idx_status (status),
    INDEX idx_designacao (designacao),
    FOREIGN KEY (instituicao_id) REFERENCES instituicao (id),
    FOREIGN KEY (orgao_id) REFERENCES orgao (id),
    FOREIGN KEY (ano_lectivo_id) REFERENCES ano_lectivo (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Plano de Curso
CREATE TABLE IF NOT EXISTS plano_curso (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mapa_curso_id INT NOT NULL,
    fase ENUM(
        'IBM',
        'Formacao Policial',
        'Unica'
    ) DEFAULT 'Unica',
    designacao VARCHAR(200),
    ordem INT DEFAULT 1,
    status ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_mapa_curso (mapa_curso_id),
    INDEX idx_status (status),
    FOREIGN KEY (mapa_curso_id) REFERENCES mapa_curso (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Plano Disciplina (relação Plano-Disciplina)
CREATE TABLE IF NOT EXISTS plano_disciplina (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plano_curso_id INT NOT NULL,
    disciplina_id INT NOT NULL,
    ordem INT DEFAULT 0,
    obrigatoria CHAR(1) DEFAULT 'Y',
    created_at DATETIME,
    INDEX idx_plano (plano_curso_id),
    INDEX idx_disciplina (disciplina_id),
    FOREIGN KEY (plano_curso_id) REFERENCES plano_curso (id) ON DELETE CASCADE,
    FOREIGN KEY (disciplina_id) REFERENCES disciplina (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- TABELAS SIGEF - GESTÃO DE FORMANDOS
-- ============================================

-- Formando
CREATE TABLE IF NOT EXISTS formando (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instituicao_id INT NOT NULL,
    patente_id INT,
    proveniencia_id INT,
    numero_ordem VARCHAR(50),
    nome_completo VARCHAR(200) NOT NULL,
    genero ENUM('Masculino', 'Feminino'),
    data_nascimento DATE,
    bi VARCHAR(20),
    telefone VARCHAR(50),
    email VARCHAR(100),
    endereco TEXT,
    foto VARCHAR(500),
    status_formativo ENUM(
        'Candidato',
        'Alistado',
        'Recruta',
        'Instruendo',
        'Aluno',
        'Formado',
        'Desistente',
        'Expulso'
    ) DEFAULT 'Candidato',
    data_alistamento DATE,
    data_recruta DATE,
    data_instruendo DATE,
    ibm_concluida CHAR(1) DEFAULT 'N',
    observacoes TEXT,
    ativo CHAR(1) DEFAULT 'Y',
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_instituicao (instituicao_id),
    INDEX idx_patente (patente_id),
    INDEX idx_proveniencia (proveniencia_id),
    INDEX idx_nome (nome_completo),
    INDEX idx_status (status_formativo),
    INDEX idx_bi (bi),
    FOREIGN KEY (instituicao_id) REFERENCES instituicao (id),
    FOREIGN KEY (patente_id) REFERENCES patente (id),
    FOREIGN KEY (proveniencia_id) REFERENCES proveniencia (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Matrícula
CREATE TABLE IF NOT EXISTS matricula (
    id INT PRIMARY KEY AUTO_INCREMENT,
    formando_id INT NOT NULL,
    mapa_curso_id INT NOT NULL,
    ano_lectivo_id INT NOT NULL,
    data_matricula DATE,
    status ENUM(
        'Activa',
        'Concluída',
        'Cancelada',
        'Suspensa'
    ) DEFAULT 'Activa',
    observacoes TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_formando (formando_id),
    INDEX idx_mapa_curso (mapa_curso_id),
    INDEX idx_ano_lectivo (ano_lectivo_id),
    INDEX idx_status (status),
    FOREIGN KEY (formando_id) REFERENCES formando (id),
    FOREIGN KEY (mapa_curso_id) REFERENCES mapa_curso (id),
    FOREIGN KEY (ano_lectivo_id) REFERENCES ano_lectivo (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- TABELAS SIGEF - FORMADORES
-- ============================================

-- Formador
CREATE TABLE IF NOT EXISTS formador (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instituicao_id INT NOT NULL,
    patente_id INT,
    numero_ordem VARCHAR(50),
    nome_completo VARCHAR(200) NOT NULL,
    genero ENUM('Masculino', 'Feminino'),
    tipo_formador ENUM(
        'Tempo Integral',
        'Tempo Parcial',
        'Convidado'
    ) DEFAULT 'Tempo Integral',
    grau_academico VARCHAR(100),
    especialidade VARCHAR(200),
    telefone VARCHAR(50),
    email VARCHAR(100),
    foto VARCHAR(500),
    status ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_instituicao (instituicao_id),
    INDEX idx_patente (patente_id),
    INDEX idx_nome (nome_completo),
    INDEX idx_status (status),
    FOREIGN KEY (instituicao_id) REFERENCES instituicao (id),
    FOREIGN KEY (patente_id) REFERENCES patente (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Formador Disciplina (relação Formador-Disciplina)
CREATE TABLE IF NOT EXISTS formador_disciplina (
    id INT PRIMARY KEY AUTO_INCREMENT,
    formador_id INT NOT NULL,
    disciplina_id INT NOT NULL,
    created_at DATETIME,
    INDEX idx_formador (formador_id),
    INDEX idx_disciplina (disciplina_id),
    FOREIGN KEY (formador_id) REFERENCES formador (id) ON DELETE CASCADE,
    FOREIGN KEY (disciplina_id) REFERENCES disciplina (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ============================================
-- TABELAS SIGEF - TURMAS
-- ============================================

-- Turma
CREATE TABLE IF NOT EXISTS turma (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mapa_curso_id INT NOT NULL,
    ano_lectivo_id INT,
    designacao VARCHAR(200) NOT NULL,
    codigo VARCHAR(50),
    turno ENUM(
        'Manhã',
        'Tarde',
        'Noite',
        'Integral'
    ) DEFAULT 'Manhã',
    sala VARCHAR(100),
    capacidade INT DEFAULT 30,
    status ENUM(
        'Activa',
        'Concluída',
        'Cancelada'
    ) DEFAULT 'Activa',
    observacoes TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_mapa_curso (mapa_curso_id),
    INDEX idx_ano_lectivo (ano_lectivo_id),
    INDEX idx_designacao (designacao),
    INDEX idx_status (status),
    FOREIGN KEY (mapa_curso_id) REFERENCES mapa_curso (id) ON DELETE CASCADE,
    FOREIGN KEY (ano_lectivo_id) REFERENCES ano_lectivo (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Turma Formando (relação Turma-Formando)
CREATE TABLE IF NOT EXISTS turma_formando (
    id INT PRIMARY KEY AUTO_INCREMENT,
    turma_id INT NOT NULL,
    formando_id INT NOT NULL,
    data_inscricao DATE,
    status ENUM(
        'Activo',
        'Inactivo',
        'Transferido'
    ) DEFAULT 'Activo',
    observacoes TEXT,
    created_at DATETIME,
    INDEX idx_turma (turma_id),
    INDEX idx_formando (formando_id),
    UNIQUE KEY uk_turma_formando (turma_id, formando_id),
    FOREIGN KEY (turma_id) REFERENCES turma (id) ON DELETE CASCADE,
    FOREIGN KEY (formando_id) REFERENCES formando (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Turma Disciplina (relação Turma-Disciplina com Formador)
CREATE TABLE IF NOT EXISTS turma_disciplina (
    id INT PRIMARY KEY AUTO_INCREMENT,
    turma_id INT NOT NULL,
    disciplina_id INT NOT NULL,
    formador_id INT,
    ordem INT DEFAULT 1,
    carga_horaria INT,
    data_inicio DATE,
    data_fim DATE,
    status ENUM(
        'Pendente',
        'Em Curso',
        'Concluída'
    ) DEFAULT 'Pendente',
    created_at DATETIME,
    INDEX idx_turma (turma_id),
    INDEX idx_disciplina (disciplina_id),
    INDEX idx_formador (formador_id),
    FOREIGN KEY (turma_id) REFERENCES turma (id) ON DELETE CASCADE,
    FOREIGN KEY (disciplina_id) REFERENCES disciplina (id),
    FOREIGN KEY (formador_id) REFERENCES formador (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- DADOS INICIAIS - GRUPOS E PROGRAMAS
-- ============================================

-- Grupos
INSERT INTO system_group (id, name) VALUES (1, 'Administradores');

INSERT INTO system_group (id, name) VALUES (2, 'Utilizadores');

INSERT INTO
    system_group (id, name)
VALUES (3, 'Gestores Académicos');

INSERT INTO system_group (id, name) VALUES (4, 'Docentes');

-- Programas do Sistema
INSERT INTO
    system_program (id, name, controller)
VALUES (
        1,
        'System Administration Dashboard',
        'SystemAdministrationDashboard'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        2,
        'System Program Form',
        'SystemProgramForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        3,
        'System Program List',
        'SystemProgramList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        4,
        'System Group Form',
        'SystemGroupForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        5,
        'System Group List',
        'SystemGroupList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        6,
        'System Unit Form',
        'SystemUnitForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        7,
        'System Unit List',
        'SystemUnitList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        8,
        'System Role Form',
        'SystemRoleForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        9,
        'System Role List',
        'SystemRoleList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        10,
        'System User Form',
        'SystemUserForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        11,
        'System User List',
        'SystemUserList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        12,
        'System Preference Form',
        'SystemPreferenceForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        13,
        'System Profile View',
        'SystemProfileView'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        14,
        'System Profile Form',
        'SystemProfileForm'
    );

-- Programas SIGEF
INSERT INTO
    system_program (id, name, controller)
VALUES (
        50,
        'SIGEF Dashboard',
        'SigefDashboard'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        51,
        'Patentes - Lista',
        'PatenteList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        52,
        'Patentes - Formulário',
        'PatenteForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        53,
        'Proveniências - Lista',
        'ProvenienciaList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        54,
        'Proveniências - Formulário',
        'ProvenienciaForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        55,
        'Anos Lectivos - Lista',
        'AnoLectivoList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        56,
        'Anos Lectivos - Formulário',
        'AnoLectivoForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        57,
        'Tipos Instituição - Lista',
        'TipoInstituicaoList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        58,
        'Tipos Instituição - Formulário',
        'TipoInstituicaoForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        59,
        'Instituições - Lista',
        'InstituicaoList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        60,
        'Instituições - Formulário',
        'InstituicaoForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        61,
        'Órgãos - Lista',
        'OrgaoList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        62,
        'Órgãos - Formulário',
        'OrgaoForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        63,
        'Disciplinas - Lista',
        'DisciplinaList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        64,
        'Disciplinas - Formulário',
        'DisciplinaForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        65,
        'Mapa de Cursos - Lista',
        'MapaCursoList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        66,
        'Mapa de Cursos - Formulário',
        'MapaCursoForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        67,
        'Plano de Cursos - Lista',
        'PlanoCursoList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        68,
        'Plano de Cursos - Formulário',
        'PlanoCursoForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        69,
        'Formandos - Lista',
        'FormandoList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        70,
        'Formandos - Formulário',
        'FormandoForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        71,
        'Matrículas - Lista',
        'MatriculaList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        72,
        'Matrículas - Formulário',
        'MatriculaForm'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        73,
        'Formadores - Lista',
        'FormadorList'
    );

INSERT INTO
    system_program (id, name, controller)
VALUES (
        74,
        'Formadores - Formulário',
        'FormadorForm'
    );

-- Usuário Administrador (senha: admin)
INSERT INTO
    system_users (
        id,
        name,
        login,
        password,
        email,
        active,
        frontpage_id
    )
VALUES (
        1,
        'Administrador',
        'admin',
        '$2y$10$xuR3XEc3J6tpv7myC9gPj.Ab5GacSeHSZoYUTYtOg.cEc22G.iBwa',
        'admin@sigef.gov.ao',
        'Y',
        50
    );

-- Associar admin aos grupos
INSERT INTO
    system_user_group (
        id,
        system_user_id,
        system_group_id
    )
VALUES (1, 1, 1);

INSERT INTO
    system_user_group (
        id,
        system_user_id,
        system_group_id
    )
VALUES (2, 1, 2);

INSERT INTO
    system_user_group (
        id,
        system_user_id,
        system_group_id
    )
VALUES (3, 1, 3);

-- Associar programas ao grupo Administradores
INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (1, 1, 1);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (2, 1, 2);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (3, 1, 3);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (4, 1, 4);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (5, 1, 5);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (6, 1, 6);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (7, 1, 7);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (8, 1, 8);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (9, 1, 9);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (10, 1, 10);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (11, 1, 11);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (12, 1, 12);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (13, 1, 13);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (14, 1, 14);

-- SIGEF Programs para Administradores
INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (50, 1, 50);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (51, 1, 51);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (52, 1, 52);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (53, 1, 53);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (54, 1, 54);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (55, 1, 55);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (56, 1, 56);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (57, 1, 57);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (58, 1, 58);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (59, 1, 59);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (60, 1, 60);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (61, 1, 61);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (62, 1, 62);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (63, 1, 63);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (64, 1, 64);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (65, 1, 65);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (66, 1, 66);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (67, 1, 67);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (68, 1, 68);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (69, 1, 69);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (70, 1, 70);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (71, 1, 71);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (72, 1, 72);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (73, 1, 73);

INSERT INTO
    system_group_program (
        id,
        system_group_id,
        system_program_id
    )
VALUES (74, 1, 74);

-- ============================================
-- DADOS INICIAIS - SIGEF
-- ============================================

-- Tipos de Instituição
INSERT INTO
    tipo_instituicao (
        id,
        codigo,
        designacao,
        nomenclatura_formando,
        permite_ciclo_epp,
        created_at
    )
VALUES (
        1,
        'EPP',
        'Escola da Polícia',
        'Recruta',
        'Y',
        NOW()
    ),
    (
        2,
        'ISP',
        'Instituto Superior da Polícia',
        'Aluno',
        'N',
        NOW()
    ),
    (
        3,
        'COL',
        'Colégio',
        'Aluno',
        'N',
        NOW()
    ),
    (
        4,
        'CFO',
        'Centro de Formação',
        'Aluno',
        'N',
        NOW()
    );

-- Patentes (hierarquia policial angolana)
INSERT INTO
    patente (
        id,
        designacao,
        abreviatura,
        nivel_hierarquico,
        ativo,
        created_at
    )
VALUES (
        1,
        'Comissário-Geral',
        'Com.Gen.',
        10,
        'Y',
        NOW()
    ),
    (
        2,
        'Comissário',
        'Com.',
        9,
        'Y',
        NOW()
    ),
    (
        3,
        'Subcomissário',
        'Subcom.',
        8,
        'Y',
        NOW()
    ),
    (
        4,
        'Intendente',
        'Int.',
        7,
        'Y',
        NOW()
    ),
    (
        5,
        'Subintendente',
        'Subint.',
        6,
        'Y',
        NOW()
    ),
    (
        6,
        'Chefe',
        'Chf.',
        5,
        'Y',
        NOW()
    ),
    (
        7,
        'Subchefe',
        'Subchf.',
        4,
        'Y',
        NOW()
    ),
    (
        8,
        'Agente Principal',
        'Ag.Ppal.',
        3,
        'Y',
        NOW()
    ),
    (
        9,
        'Agente de 1ª Classe',
        'Ag.1ªCl.',
        2,
        'Y',
        NOW()
    ),
    (
        10,
        'Agente de 2ª Classe',
        'Ag.2ªCl.',
        1,
        'Y',
        NOW()
    );

-- Proveniências
INSERT INTO
    proveniencia (
        id,
        designacao,
        descricao,
        ativo,
        created_at
    )
VALUES (
        1,
        'Civil',
        'Candidato proveniente da sociedade civil',
        'Y',
        NOW()
    ),
    (
        2,
        'Militar',
        'Candidato proveniente das Forças Armadas',
        'Y',
        NOW()
    ),
    (
        3,
        'Reingresso',
        'Agente em situação de reingresso',
        'Y',
        NOW()
    ),
    (
        4,
        'Transferência',
        'Transferência de outra instituição policial',
        'Y',
        NOW()
    ),
    (
        5,
        'Promoção',
        'Promoção por mérito ou antiguidade',
        'Y',
        NOW()
    );

-- Ano Lectivo atual
INSERT INTO
    ano_lectivo (
        id,
        ano,
        data_inicio,
        data_fim,
        status,
        ativo,
        created_at
    )
VALUES (
        1,
        '2024/2025',
        '2024-09-01',
        '2025-07-31',
        'Aberto',
        'Y',
        NOW()
    );