<?php
// 1. Iniciar a sessão
session_start();

// 2. Incluir o arquivo de conexão
require '../conexao.php';

// 3. Verificar se o formulário foi enviado (método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['email'];
    $senha_digitada = $_POST['senha'];

    try {
        // 4. Buscar o usuário pelo E-MAIL
        // Usamos Prepared Statements para segurança MÁXIMA contra SQL Injection
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        
        // fetch() pega o primeiro (e único) resultado
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // 5. Verificar se o usuário existe E se a senha está correta
        // password_verify() compara a senha digitada com o HASH salvo no banco
        if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
            
            // 6. SUCESSO! Senha correta!
            // Guardamos os dados importantes do usuário na sessão
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            
            // Limpa qualquer erro de login antigo que possa existir
            unset($_SESSION['erro_login']);

            // 7. Redirecionar para o painel principal
            // O dashboard.php vai ler a sessão e descobrir o que fazer.
            header("Location: ../dashboard.php");
            exit();

        } else {
            // 8. FALHA no login (email não encontrado ou senha errada)
            $_SESSION['erro_login'] = "E-mail ou senha inválidos.";
            header("Location: login.php"); // Manda de volta para a tela de login
            exit();
        }

    } catch (PDOException $e) {
        // Erro de banco de dados
        $_SESSION['erro_login'] = "Erro no sistema. Tente novamente mais tarde.";
        header("Location: login.php");
        exit();
    }

} else {
    // Se alguém tentar acessar esse arquivo direto pela URL
    header("Location: login.php");
    exit();
}
?>