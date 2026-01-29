<?php
session_start();
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login/login.php");
    exit();
}

// --- Busca as Categorias "Mãe" ---
try {
    $stmt_cat = $pdo->query("SELECT * FROM categorias_negocio ORDER BY nome_categoria ASC");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar categorias: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configure seu Negócio - Agiliza</title>
    <link rel="stylesheet" href="../login/style_login.css">
</head>
<body>
    <div class="login-container" style="max-width: 500px;">
        <h1>Seu Negócio</h1>
        <p style="text-align:center; color: #555; margin-top: -1rem; margin-bottom: 2rem;">
            Parabéns! Vamos configurar sua empresa.
        </p>

        <form action="processar_negocio.php" method="POST">
            
            <div class="form-group">
                <label for="nome_negocio">Nome do seu Negócio (Ex: Barbearia do Zé)</label>
                <input type="text" id="nome_negocio" name="nome_negocio" required>
            </div>
            
            <div class="form-group">
                <label for="id_categoria">Qual é o nicho do seu negócio?</label>
                <select id="id_categoria" name="id_categoria" required>
                    <option value="">-- Selecione um nicho --</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['nome_categoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="id_tipo_negocio">Qual o tipo do seu negócio?</label>
                <select id="id_tipo_negocio" name="id_tipo_negocio" required disabled>
                    <option value="">-- Escolha um nicho primeiro --</option>
                </select>
            </div>
            
             <div class="form-group">
                <label for="endereco_texto">Endereço Completo (para o mapa)</label>
                <input type="text" id="endereco_texto" name="endereco_texto" placeholder="Rua, Número, Bairro, Cidade">
            </div>
            
             <div class="form-group">
                <label for="ponto_referencia">Ponto de Referência (Opcional)</label>
                <input type="text" id="ponto_referencia" name="ponto_referencia" placeholder="Ex: Ao lado da padaria">
            </div>
            
            <button type="submit" class="login-button">Salvar e Concluir</button>
            
        </form>
    </div>

<script>
    const selectCategoria = document.getElementById('id_categoria');
    const selectTipo = document.getElementById('id_tipo_negocio');

    selectCategoria.addEventListener('change', async () => {
        const idCategoria = selectCategoria.value;
        
        selectTipo.innerHTML = '<option value="">Carregando...</option>';
        selectTipo.disabled = true;

        if (!idCategoria) {
            selectTipo.innerHTML = '<option value="">-- Escolha um nicho primeiro --</option>';
            return;
        }

        try {
            // O fetch está correto (ambos os arquivos estão na pasta /negocio/)
            const response = await fetch(`buscar_tipos.php?id_categoria=${idCategoria}`);
            
            // [NOVO DEBUG] Verifica se o arquivo não foi encontrado (404)
            if (!response.ok) {
                 throw new Error(`Erro de rede: ${response.statusText} (Arquivo 'buscar_tipos.php' não encontrado ou com erro)`);
            }
            
            const tipos = await response.json();

            if (tipos.sucesso && tipos.dados.length > 0) {
                selectTipo.innerHTML = '<option value="">-- Selecione o tipo --</option>';
                tipos.dados.forEach(tipo => {
                    const option = document.createElement('option');
                    option.value = tipo.id;
                    option.textContent = tipo.nome_tipo;
                    selectTipo.appendChild(option);
                });
                selectTipo.disabled = false; // Habilita o dropdown
            } else {
                selectTipo.innerHTML = '<option value="">-- Nenhum tipo encontrado --</option>';
            }
        } catch (error) {
            console.error("Erro ao buscar tipos:", error);
            selectTipo.innerHTML = `<option value="">-- Erro ao carregar --</option>`;
        }
    });
</script>

</body>
</html>