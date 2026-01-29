<?php
// 1. Iniciar a sessão e conexão
session_start();
require '../conexao.php';

// 2. O "Porteiro"
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    die("Acesso negado.");
}

// [CORREÇÃO] Verifica se os dados REALMENTE chegaram
if (!isset($_POST['id_tipo_negocio']) || empty($_POST['id_tipo_negocio'])) {
    die("Erro: O 'Tipo de Negócio' é obrigatório. Volte e selecione um nicho e um tipo.");
}

try {
    // 3. Coletar dados
    $id_dono = $_SESSION['usuario_id'];
    $nome_negocio = $_POST['nome_negocio'];
    $id_tipo_negocio = $_POST['id_tipo_negocio']; // Agora sabemos que ele existe
    $endereco_texto = $_POST['endereco_texto'];
    $ponto_referencia = $_POST['ponto_referencia'];

    $latitude = NULL;
    $longitude = NULL;

    // 4. Inicia a Transação
    $pdo->beginTransaction();

    // INSERÇÃO 1: Criar o negócio
    $stmt_negocio = $pdo->prepare(
        "INSERT INTO negocios 
            (id_dono, nome_negocio, id_tipo_negocio, endereco_texto, ponto_referencia, latitude, longitude)
         VALUES 
            (:id_dono, :nome, :id_tipo, :endereco, :ponto, :lat, :lon)"
    );
    
    $stmt_negocio->execute([
        'id_dono' => $id_dono,
        'nome' => $nome_negocio,
        'id_tipo' => $id_tipo_negocio,
        'endereco' => $endereco_texto,
        'ponto' => $ponto_referencia,
        'lat' => $latitude,
        'lon' => $longitude
    ]);

    $novo_negocio_id = $pdo->lastInsertId();

    // INSERÇÃO 2: Ligar o usuário como 'dono' deste negócio
    $stmt_membro = $pdo->prepare(
        "INSERT INTO negocio_membros (id_usuario, id_negocio, funcao)
         VALUES (:id_usuario, :id_negocio, 'dono')"
    );
    $stmt_membro->execute([
        'id_usuario' => $id_dono,
        'id_negocio' => $novo_negocio_id
    ]);

    // Se tudo deu certo, confirma
    $pdo->commit();

    // 5. Redirecionar para o painel
    header("Location: ../painel_dono/index.php");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Erro ao criar seu negócio. Tente novamente. (" . $e->getMessage() . ")");
}
?>