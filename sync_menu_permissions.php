<?php
/**
 * Script para sincronizar menu.xml com system_program e activar permissões
 * Execute via browser: http://localhost/gestformativa/sync_menu_permissions.php
 */
require_once 'init.php';

new TSession;

// Verificar se está logado como admin
if (!TSession::getValue('logged')) {
    die('Deve estar logado para executar este script.');
}

try {
    TTransaction::open('permission');
    
    // Carregar menu.xml
    $xml = new SimpleXMLElement(file_get_contents('menu.xml'));
    
    $programs_added = [];
    $programs_linked = [];
    
    // Obter grupo de administrador (geralmente id=1)
    $admin_group = SystemGroup::find(1);
    if (!$admin_group) {
        throw new Exception('Grupo de administrador não encontrado');
    }
    
    // Função recursiva para processar menu
    function processMenu($xml, &$programs_added, &$programs_linked, $admin_group) {
        foreach ($xml as $menuitem) {
            $label = (string) $menuitem->attributes()['label'];
            $action = (string) $menuitem->action;
            
            // Se tem submenu, processar recursivamente
            if ($menuitem->menu) {
                processMenu($menuitem->menu->menuitem, $programs_added, $programs_linked, $admin_group);
            }
            
            // Se tem action (é um item final)
            if ($action) {
                // Extrair nome da classe
                $parts = explode('#', $action);
                $class_name = $parts[0];
                
                // Verificar se o programa já existe
                $program = SystemProgram::where('controller', '=', $class_name)->first();
                
                if (!$program) {
                    // Criar novo programa
                    $program = new SystemProgram();
                    $program->name = $label;
                    $program->controller = $class_name;
                    $program->store();
                    $programs_added[] = $class_name;
                }
                
                // Verificar se já está associado ao grupo admin
                $exists = SystemGroupProgram::where('system_group_id', '=', $admin_group->id)
                    ->where('system_program_id', '=', $program->id)
                    ->first();
                
                if (!$exists) {
                    // Associar programa ao grupo admin
                    $group_program = new SystemGroupProgram();
                    $group_program->system_group_id = $admin_group->id;
                    $group_program->system_program_id = $program->id;
                    $group_program->store();
                    $programs_linked[] = $class_name;
                }
            }
        }
    }
    
    // Processar o menu
    processMenu($xml->menuitem, $programs_added, $programs_linked, $admin_group);
    
    TTransaction::close();
    
    echo "<h2>Sincronização Concluída!</h2>";
    echo "<h3>Programas Adicionados (" . count($programs_added) . "):</h3>";
    echo "<ul>";
    foreach ($programs_added as $p) {
        echo "<li>$p</li>";
    }
    echo "</ul>";
    
    echo "<h3>Programas Associados ao Grupo Admin (" . count($programs_linked) . "):</h3>";
    echo "<ul>";
    foreach ($programs_linked as $p) {
        echo "<li>$p</li>";
    }
    echo "</ul>";
    
    echo "<p><strong>Faça logout e login novamente para ver o menu actualizado!</strong></p>";
    echo "<p><a href='index.php?class=LoginForm&method=onLogout'>Clique aqui para fazer logout</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Erro:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    TTransaction::rollback();
}
