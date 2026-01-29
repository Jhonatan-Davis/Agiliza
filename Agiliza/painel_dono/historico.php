<?php
session_start();
require '../conexao.php'; // Conexão com o banco

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    header("Location: ../login/login.php");
    exit();
}

$id_dono = $_SESSION['usuario_id'];
$id_negocio = $_SESSION['id_negocio'];

// --- Verifica se o Dono também é Funcionário (Para a Sidebar) ---
$stmt_check_func = $pdo->prepare(
    "SELECT COUNT(*) FROM negocio_membros 
     WHERE id_usuario = :id_dono AND id_negocio = :id_negocio AND funcao = 'funcionario'"
);
$stmt_check_func->execute(['id_dono' => $id_dono, 'id_negocio' => $id_negocio]);
$dono_e_funcionario = $stmt_check_func->fetchColumn() > 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico e Vendas - Agiliza</title>
    <link rel="stylesheet" href="style_painel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Layout Específico desta Página */
        .grid-graficos {
            display: grid;
            grid-template-columns: 2fr 1fr; /* 2/3 para Vendas, 1/3 para Dias */
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .grafico-container {
            background-color: #ffffff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            border: 1px solid #eee;
        }
        .grafico-container h3 {
            margin-top: 0;
            color: #333;
            flex-shrink: 0;
        }
        
        /* Wrapper essencial para o Chart.js não travar */
        .grafico-wrapper {
            position: relative;
            flex-grow: 1;
            min-height: 250px;
            width: 100%;
        }
        
        .no-data-message {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            min-height: 200px;
            color: #888;
            font-style: italic;
            text-align: center;
            padding: 1rem;
        }
        
        @media (max-width: 900px) {
            .grid-graficos {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="painel-container">
        <header class="mobile-header">
            <button class="menu-toggle" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Agili<span>za</span></h1>
        </header>
        <nav class="sidebar">
          
            <div class="sidebar-nav">
                <a href="index.php">Dashboard</a> 
                <a href="agenda_completa.php">Agenda Completa</a>
                <a href="meu_negocio.php">Meu Negócio</a>
                <a href="minha_equipe.php">Minha Equipe</a>
                <a href="meus_servicos.php">Meus Serviços</a>
                <a href="meus_horarios.php">Meus Horários</a>
                <a href="historico.php" class="active">Histórico e Vendas</a> <?php if ($dono_e_funcionario): ?>
                    <a href="financeiro.php">Financeiro</a>
                    <a href="meus_clientes.php">Meus Clientes</a>
                    <a href="meu_perfil.php" class="link-extra">
                        <i class="fas fa-user-cog"></i> Meu Perfil (Pessoal)
                    </a>
                    <a href="meu_portfolio.php" class="link-extra">
                        <i class="fas fa-camera"></i> Meu Portfólio
                    </a>
                <?php endif; ?>
            </div>
            <div class="sidebar-logout">
                <a href="../logout.php">Sair</a>
            </div>
        </nav>

        <main class="main-content">
            <h2>Histórico e Vendas</h2>
            <p>Acompanhe o desempenho financeiro e operacional do seu negócio.</p>
            
            <div class="grafico-container" style="margin-bottom: 1.5rem; border-left: 5px solid #4CAF50;">
                <h3 style="color: #2E7D32;">Lucro Líquido (Faturamento - Despesas)</h3>
                <div class="grafico-wrapper" style="height: 300px;">
                    <canvas id="graficoLucro"></canvas>
                </div>
            </div>

            <div class="grid-graficos">
                <div class="grafico-container">
                    <h3>Faturamento Bruto (Últimos 30 dias)</h3>
                    <div class="grafico-wrapper">
                        <canvas id="graficoVendas"></canvas>
                    </div>
                </div>
                <div class="grafico-container">
                    <h3>Dias Mais Movimentados</h3>
                    <div class="grafico-wrapper">
                        <canvas id="graficoDias"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="grafico-container">
                <h3>Rendimento por Funcionário (Top Performance)</h3>
                <div class="grafico-wrapper">
                    <canvas id="graficoFuncionarios"></canvas>
                </div>
            </div>
        </main>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        
        const corRoxa = '#7E57C2';
        const corRoxaClara = 'rgba(126, 87, 194, 0.2)';
        const corVerde = '#4CAF50'; 
        const corVerdeClara = 'rgba(76, 175, 80, 0.2)';

        // --- 1. Gráfico de LUCRO MENSAL (Verde) ---
        const ctxLucro = document.getElementById('graficoLucro').getContext('2d');
        fetch('buscar_dados_graficos.php?grafico=lucro_mensal')
            .then(response => response.json())
            .then(data => {
                // Verifica se tem algum valor diferente de zero
                const temDados = data.valores && data.valores.some(v => v !== 0);

                if (!temDados) {
                    ctxLucro.canvas.parentNode.innerHTML = '<div class="no-data-message">Sem dados financeiros (vendas ou despesas concluídas) nos últimos 6 meses.</div>';
                    return;
                }

                new Chart(ctxLucro, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Lucro Líquido (R$)',
                            data: data.valores,
                            backgroundColor: corVerdeClara,
                            borderColor: corVerde,
                            borderWidth: 2,
                            borderRadius: 4
                        }]
                    },
                    options: { 
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            });

        // --- 2. Gráfico de VENDAS (Linha Roxa) ---
        const ctxVendas = document.getElementById('graficoVendas').getContext('2d');
        fetch('buscar_dados_graficos.php?grafico=vendas_30_dias')
            .then(response => response.json())
            .then(data => {
                if (!data.labels || data.labels.length === 0) {
                    ctxVendas.canvas.parentNode.innerHTML = '<div class="no-data-message">Nenhum agendamento concluído nos últimos 30 dias.</div>';
                    return;
                }

                new Chart(ctxVendas, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'R$ Faturado',
                            data: data.valores,
                            backgroundColor: corRoxaClara,
                            borderColor: corRoxa,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: { 
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            });

        // --- 3. Gráfico de DIAS (Pizza) ---
        const ctxDias = document.getElementById('graficoDias').getContext('2d');
        fetch('buscar_dados_graficos.php?grafico=dias_mais_movimentados')
            .then(response => response.json())
            .then(data => {
                if (!data.labels || data.labels.length === 0) {
                    ctxDias.canvas.parentNode.innerHTML = '<div class="no-data-message">Nenhum dado disponível.</div>';
                    return;
                }

                new Chart(ctxDias, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.valores,
                            backgroundColor: ['#7E57C2', '#29B6F6', '#66BB6A', '#FFCA28', '#EF5350', '#AB47BC', '#FF7043'],
                        }]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false
                    }
                });
            });

        // --- 4. Gráfico de FUNCIONÁRIOS (Barras) ---
        const ctxFunc = document.getElementById('graficoFuncionarios').getContext('2d');
        fetch('buscar_dados_graficos.php?grafico=rendimento_funcionarios')
            .then(response => response.json())
            .then(data => {
                if (!data.labels || data.labels.length === 0) {
                    ctxFunc.canvas.parentNode.innerHTML = '<div class="no-data-message">Nenhum agendamento concluído por funcionários.</div>';
                    return;
                }

                new Chart(ctxFunc, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'R$ Gerado',
                            data: data.valores,
                            backgroundColor: corRoxa,
                            borderRadius: 4
                        }]
                    },
                    options: { 
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            });
            
    });
    
</script>
<script src="script_painel.js"></script>
</body>
</html>