<?php
session_start();
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono' || $_SERVER["REQUEST_METHOD"] != "POST") {
    die("Acesso negado.");
}

$id_negocio = $_SESSION['id_negocio'];
$dias_da_semana = [0, 1, 2, 3, 4, 5, 6];

try {
    // Prepara a query (INSERT ... ON DUPLICATE KEY UPDATE)
    // Isso é muito eficiente: se o dia já existe, atualiza. Se não, insere.
    $sql = "INSERT INTO horarios_funcionamento 
                (id_negocio, dia_semana, aberto, hora_abertura_manha, hora_fechamento_manha, hora_abertura_tarde, hora_fechamento_tarde)
            VALUES 
                (:id_negocio, :dia, :aberto, :ab_m, :fc_m, :ab_t, :fc_t)
            ON DUPLICATE KEY UPDATE
                aberto = :aberto,
                hora_abertura_manha = :ab_m,
                hora_fechamento_manha = :fc_m,
                hora_abertura_tarde = :ab_t,
                hora_fechamento_tarde = :fc_t";
                
    $stmt = $pdo->prepare($sql);
    
    // Faz um loop pelos 7 dias da semana
    foreach ($dias_da_semana as $dia) {
        $aberto = isset($_POST['aberto'][$dia]) ? 1 : 0;
        
        // Se estiver fechado, salva NULL nos horários
        $ab_m = $aberto ? ($_POST['abertura_manha'][$dia] ?: NULL) : NULL;
        $fc_m = $aberto ? ($_POST['fechamento_manha'][$dia] ?: NULL) : NULL;
        $ab_t = $aberto ? ($_POST['abertura_tarde'][$dia] ?: NULL) : NULL;
        $fc_t = $aberto ? ($_POST['fechamento_tarde'][$dia] ?: NULL) : NULL;
        
        $stmt->execute([
            'id_negocio' => $id_negocio,
            'dia' => $dia,
            'aberto' => $aberto,
            'ab_m' => $ab_m,
            'fc_m' => $fc_m,
            'ab_t' => $ab_t,
            'fc_t' => $fc_t
        ]);
    }
    
    // Sucesso! Volta para a página
    header("Location: meus_horarios.php?sucesso=1");
    exit();

} catch (PDOException $e) {
    die("Erro ao salvar horários: " . $e->getMessage());
}
?>