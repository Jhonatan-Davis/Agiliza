<?php
// --- cadastro/processar_cadastro.php (VERSÃO 2.0 CORRIGIDA) ---

// 1. Iniciar a sessão
session_start();

// 2. Incluir o arquivo de conexão
// (subindo um nível para achar o arquivo)
require '../conexao.php';

// 3. Verificar se o formulário foi enviado (método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 4. Coletar dados do formulário
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $tipo_usuario = $_POST['tipo_usuario'];

    if ($senha !== $confirmar_senha) {
        $_SESSION['erro_cadastro'] = "As senhas não coincidem.";
        header("Location: cadastro.php");
        exit();
    }

    // 6. Validação 2: A senha é forte o bastante?
    if (strlen($senha) < 6) {
        $_SESSION['erro_cadastro'] = "A senha deve ter pelo menos 6 caracteres.";
        header("Location: cadastro.php");
        exit();
    }

    // 7. Validação 3: O e-mail já existe?
    try {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
        $stmt_check->execute(['email' => $email]);
        
        if ($stmt_check->fetchColumn() > 0) {
            $_SESSION['erro_cadastro'] = "Este e-mail já está cadastrado.";
            header("Location: cadastro.php");
            exit();
        }

        // --- FIM DAS VALIDAÇÕES ---

        // 8. Criptografar a Senha (SEGURANÇA!)
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // 9. Inserir o novo usuário no banco
        // **** ESTA É A LINHA CORRIGIDA (SEM tipo_conta) ****
        $stmt_insert = $pdo->prepare(
            "INSERT INTO usuarios (nome, email, senha, telefone) 
             VALUES (:nome, :email, :senha, :telefone)"
        );
        
        $stmt_insert->execute([
            'nome' => $nome,
            'email' => $email,
            'senha' => $senha_hash,
            'telefone' => $telefone
        ]);

        // 10. Sucesso! Redirecionar para o Login
        // 10. Sucesso! 

// Pega o ID do usuário que acabamos de criar
$novo_usuario_id = $pdo->lastInsertId();

// FAZ O LOGIN AUTOMÁTICO DELE
$_SESSION['usuario_id'] = $novo_usuario_id;
$_SESSION['usuario_nome'] = $nome;

// AGORA, A LÓGICA DE REDIRECIONAMENTO (O que você pediu)
if ($tipo_usuario == 'dono') {
    // O usuário é um Dono, manda ele criar o negócio
    header("Location: ../negocio/criar_negocio.php");
    exit();
} else {
    // O usuário é um Cliente, manda para a tela inicial
    header("Location: ../index.php"); // (ou a página principal)
    exit();
}

    } catch (PDOException $e) {
        // Erro de banco de dados
        $_SESSION['erro_cadastro'] = "Erro no sistema. Tente novamente. (" . $e->getMessage() . ")";
        header("Location: cadastro.php");
        exit();
    }

} else {
    // Se alguém tentar acessar esse arquivo direto pela URL
    header("Location: cadastro.php");
    exit();
}
?>