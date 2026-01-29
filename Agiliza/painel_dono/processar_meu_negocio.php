<?php
session_start();
require '../conexao.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') {
    die("Acesso negado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_negocio = $_SESSION['id_negocio'];
    
    // 1. Captura os dados do formulário
    $nome_negocio = $_POST['nome_negocio'];
    $endereco_texto = $_POST['endereco_texto'];
    $ponto_referencia = $_POST['ponto_referencia'];
    
    // [A CORREÇÃO] Captura os novos campos que estavam faltando
    $link_maps = $_POST['link_maps'] ?? null;
    $whatsapp = $_POST['whatsapp'] ?? null;
    $instagram = $_POST['instagram'] ?? null;
    $facebook = $_POST['facebook'] ?? null;

    // --- LÓGICA DE UPLOAD (Mantida) ---

    // 2. Puxa os dados atuais para manter as imagens se não forem trocadas
    try {
        $stmt_current = $pdo->prepare("SELECT foto_perfil_url, capa_url FROM negocios WHERE id = :id_negocio");
        $stmt_current->execute(['id_negocio' => $id_negocio]);
        $current_images = $stmt_current->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['erro_negocio'] = "Erro ao ler dados: " . $e->getMessage();
        header("Location: meu_negocio.php");
        exit();
    }

    $foto_perfil_url = $current_images['foto_perfil_url'];
    $capa_url = $current_images['capa_url'];

    // 3. Processa Foto de Perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $file_name = uniqid('perfil_' . $id_negocio . '_') . '.' . strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
            $foto_perfil_url = 'uploads/' . $file_name;
        }
    }

    // 4. Processa Capa
    if (isset($_FILES['capa']) && $_FILES['capa']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $file_name = uniqid('capa_' . $id_negocio . '_') . '.' . strtolower(pathinfo($_FILES['capa']['name'], PATHINFO_EXTENSION));
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['capa']['tmp_name'], $target_file)) {
            $capa_url = 'uploads/' . $file_name;
        }
    }
    
    // 5. Atualiza o banco de dados com TODOS os campos
    try {
        $stmt = $pdo->prepare(
            "UPDATE negocios SET 
                nome_negocio = :nome,
                endereco_texto = :endereco,
                ponto_referencia = :ponto,
                link_maps = :link_maps,       -- [SALVANDO O LINK]
                whatsapp = :whatsapp,
                instagram = :instagram,
                facebook = :facebook,
                foto_perfil_url = :foto_perfil_url,
                capa_url = :capa_url
             WHERE id = :id_negocio"
        );
        
        $stmt->execute([
            'nome' => $nome_negocio,
            'endereco' => $endereco_texto,
            'ponto' => $ponto_referencia,
            'link_maps' => $link_maps,    // Passando a variável capturada
            'whatsapp' => $whatsapp,
            'instagram' => $instagram,
            'facebook' => $facebook,
            'foto_perfil_url' => $foto_perfil_url,
            'capa_url' => $capa_url,
            'id_negocio' => $id_negocio
        ]);

        $_SESSION['sucesso_negocio'] = "Informações atualizadas com sucesso!";
        header("Location: meu_negocio.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['erro_negocio'] = "Erro ao salvar: " . $e->getMessage();
        header("Location: meu_negocio.php");
        exit();
    }
} else {
    header("Location: meu_negocio.php");
    exit();
}
?>