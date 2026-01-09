-- Script para registar o programa Logs do Framework no SIGEF
-- Execute este script na base de dados 'sigef'

-- 1. Inserir o programa na tabela system_program
INSERT INTO system_program (id, name, controller) 
VALUES (
    (SELECT COALESCE(MAX(id), 0) + 1 FROM system_program sp),
    'Logs do Framework',
    'SystemPHPErrorLogView'
) ON DUPLICATE KEY UPDATE name = 'Logs do Framework';

-- 2. Dar permissão ao grupo Administrador (geralmente id=1)
-- Primeiro verificar o ID do programa inserido
INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT 
    (SELECT COALESCE(MAX(id), 0) + 1 FROM system_group_program sgp),
    1, -- ID do grupo Administrador
    (SELECT id FROM system_program WHERE controller = 'SystemPHPErrorLogView' LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM system_group_program 
    WHERE system_group_id = 1 
    AND system_program_id = (SELECT id FROM system_program WHERE controller = 'SystemPHPErrorLogView' LIMIT 1)
);

-- Verificar se foi inserido
SELECT * FROM system_program WHERE controller = 'SystemPHPErrorLogView';
