<?php
// Define o tipo de resposta como JSON
header('Content-Type: application/json');

session_start();
require '../conexao.php';
date_default_timezone_set('America/Sao_Paulo');

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    echo json_encode(['erro' => 'Acesso negado']);
    exit();
}

$id_negocio = $_SESSION['id_negocio'];

// Pega qual gráfico o JavaScript está pedindo
$grafico_pedido = $_GET['grafico'] ?? 'vendas_30_dias';

// Nosso tradutor de dias (para o gráfico de "dias da semana")
$dias_traduzidos = [1 => 'Dom', 2 => 'Seg', 3 => 'Ter', 4 => 'Qua', 5 => 'Qui', 6 => 'Sex', 7 => 'Sáb'];

try {
    
    // ==========================================================
    // GRÁFICO 1: Número de Vendas (Faturamento nos últimos 30 dias)
    // ==========================================================
    if ($grafico_pedido == 'vendas_30_dias') {
        $stmt = $pdo->prepare(
            "SELECT 
                DATE(a.data_hora_inicio) as dia, 
                SUM(s.preco) as total_faturado
            FROM agendamentos a
            JOIN servicos s ON a.id_servico = s.id
            WHERE a.id_negocio = :id_negocio 
              AND a.status = 'concluido' 
              AND a.data_hora_inicio >= CURDATE() - INTERVAL 30 DAY
            GROUP BY DATE(a.data_hora_inicio)
            ORDER BY dia ASC"
        );
        $stmt->execute(['id_negocio' => $id_negocio]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $valores = [];
        foreach($dados as $dado) {
            $labels[] = date('d/m', strtotime($dado['dia']));
            $valores[] = $dado['total_faturado'];
        }
        echo json_encode(['labels' => $labels, 'valores' => $valores]);
        exit();
    }

    // ==========================================================
    // GRÁFICO 2: Dias da Semana com Mais Atendimentos
    // ==========================================================
    else if ($grafico_pedido == 'dias_mais_movimentados') {
        $stmt = $pdo->prepare(
            "SELECT 
                DAYOFWEEK(data_hora_inicio) as dia_num, 
                COUNT(id) as total_atendimentos
            FROM agendamentos
            WHERE id_negocio = :id_negocio AND status = 'concluido'
            GROUP BY dia_num
            ORDER BY dia_num ASC"
        );
        $stmt->execute(['id_negocio' => $id_negocio]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $valores = [];
        foreach($dados as $dado) {
            $labels[] = $dias_traduzidos[$dado['dia_num']];
            $valores[] = $dado['total_atendimentos'];
        }
        echo json_encode(['labels' => $labels, 'valores' => $valores]);
        exit();
    }
    
    // ==========================================================
    // GRÁFICO 3: Rendimento dos Funcionários (CORRIGIDO)
    // ==========================================================
    else if ($grafico_pedido == 'rendimento_funcionarios') {
        
        // [A CORREÇÃO ESTÁ AQUI]
        // Adicionamos um JOIN na tabela 'negocio_membros'
        // E um WHERE m.funcao = 'funcionario'
        $stmt = $pdo->prepare(
            "SELECT 
                u.nome, 
                SUM(s.preco) as total_faturado
            FROM agendamentos a
            JOIN usuarios u ON a.id_funcionario = u.id
            JOIN servicos s ON a.id_servico = s.id
            JOIN negocio_membros m ON u.id = m.id_usuario 
            WHERE a.id_negocio = :id_negocio 
              AND a.status = 'concluido'
              AND m.funcao = 'funcionario' -- Só pega funcionários
              AND m.id_negocio = :id_negocio -- Garante que é deste negócio
            GROUP BY a.id_funcionario, u.nome
            ORDER BY total_faturado DESC"
        );
        $stmt->execute(['id_negocio' => $id_negocio]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $valores = [];
        foreach($dados as $dado) {
            $labels[] = $dado['nome'];
            $valores[] = $dado['total_faturado'];
        }
        echo json_encode(['labels' => $labels, 'valores' => $valores]);
        exit();
    }

    // ==========================================================
    // GRÁFICO 4: RENDIMENTO MENSAL (Para o Dashboard)
    // ==========================================================
    else if ($grafico_pedido == 'rendimento_mensal') {
        $stmt = $pdo->prepare(
            "SELECT 
                DATE_FORMAT(a.data_hora_inicio, '%Y-%m') as mes_ano, 
                SUM(s.preco) as total_faturado
            FROM agendamentos a
            JOIN servicos s ON a.id_servico = s.id
            WHERE a.id_negocio = :id_negocio 
              AND a.status = 'concluido' 
              AND a.data_hora_inicio >= CURDATE() - INTERVAL 6 MONTH
            GROUP BY DATE_FORMAT(a.data_hora_inicio, '%Y-%m')
            ORDER BY mes_ano ASC"
        );
        $stmt->execute(['id_negocio' => $id_negocio]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $valores = [];
        foreach($dados as $dado) {
            $labels[] = strftime('%b/%y', strtotime($dado['mes_ano'] . '-01'));
            $valores[] = $dado['total_faturado'];
        }
        echo json_encode(['labels' => $labels, 'valores' => $valores]);
        exit();
    }
    // ... (códigos anteriores) ...

    // ==========================================================
    // [NOVO] GRÁFICO 5: LUCRO LÍQUIDO (Faturamento - Despesas)
    // ==========================================================
    else if ($grafico_pedido == 'lucro_mensal') {
        
        // 1. Cria um array com os últimos 6 meses (para garantir a linha do tempo)
        $dados_meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes_chave = date('Y-m', strtotime("-$i months")); // Ex: 2025-11
            $dados_meses[$mes_chave] = [
                'faturamento' => 0,
                'despesas' => 0
            ];
        }

        // 2. Busca o FATURAMENTO (Entradas)
        $stmt_fat = $pdo->prepare(
            "SELECT DATE_FORMAT(a.data_hora_inicio, '%Y-%m') as mes, SUM(s.preco) as total
             FROM agendamentos a
             JOIN servicos s ON a.id_servico = s.id
             WHERE a.id_negocio = :id_negocio 
               AND a.status = 'concluido' 
               AND a.data_hora_inicio >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY mes"
        );
        $stmt_fat->execute(['id_negocio' => $id_negocio]);
        while ($row = $stmt_fat->fetch(PDO::FETCH_ASSOC)) {
            if (isset($dados_meses[$row['mes']])) {
                $dados_meses[$row['mes']]['faturamento'] = $row['total'];
            }
        }

        // 3. Busca as DESPESAS (Saídas)
        $stmt_desp = $pdo->prepare(
            "SELECT DATE_FORMAT(data_despesa, '%Y-%m') as mes, SUM(valor) as total
             FROM despesas
             WHERE id_negocio = :id_negocio
               AND data_despesa >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY mes"
        );
        $stmt_desp->execute(['id_negocio' => $id_negocio]);
        while ($row = $stmt_desp->fetch(PDO::FETCH_ASSOC)) {
            if (isset($dados_meses[$row['mes']])) {
                $dados_meses[$row['mes']]['despesas'] = $row['total'];
            }
        }

        // 4. Calcula o LUCRO e formata para o gráfico
        $labels = [];
        $valores = [];
        
        foreach ($dados_meses as $mes => $valores_mes) {
            $lucro = $valores_mes['faturamento'] - $valores_mes['despesas'];
            
            // Formata a data (Ex: Nov/25)
            $labels[] = strftime('%b/%y', strtotime($mes . '-01'));
            $valores[] = $lucro;
        }

        echo json_encode(['labels' => $labels, 'valores' => $valores]);
        exit();
    }

} catch (PDOException $e) {
    echo json_encode(['erro' => $e->getMessage()]);
    exit();
}
?>