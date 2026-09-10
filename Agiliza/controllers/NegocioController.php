<?php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'tipos') {
	header('Content-Type: application/json');
	$id_categoria = (int)($_GET['id_categoria'] ?? 0);

	if ($id_categoria === 0) {
		echo json_encode(['sucesso' => false, 'erro' => 'Categoria não fornecida']);
		exit();
	}

	try {
		$stmt = $pdo->prepare(
			'SELECT id, nome_tipo FROM tipos_negocio
			 WHERE id_categoria = :id_categoria ORDER BY nome_tipo ASC'
		);
		$stmt->execute(['id_categoria' => $id_categoria]);
		echo json_encode(['sucesso' => true, 'dados' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
	} catch (PDOException $e) {
		echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
	}
	exit();
}

if ($action === 'criar') {
	if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(403);
		exit('Acesso negado.');
	}

	if (empty($_POST['id_tipo_negocio'])) {
		exit("Erro: o tipo de negócio é obrigatório.");
	}

	$endereco = trim($_POST['endereco_texto'] ?? '');
	if ($endereco === '') {
		$partes_endereco = [
			trim($_POST['logradouro'] ?? ''),
			trim($_POST['numero_endereco'] ?? ''),
			trim($_POST['bairro'] ?? ''),
			trim($_POST['cidade'] ?? ''),
			trim($_POST['cep'] ?? '')
		];
		$endereco = implode(', ', array_filter($partes_endereco));
	}

	try {
		$pdo->beginTransaction();
		$stmt_negocio = $pdo->prepare(
			'INSERT INTO negocios
				(id_dono, nome_negocio, id_tipo_negocio, endereco_texto, ponto_referencia, latitude, longitude)
			 VALUES (:id_dono, :nome, :id_tipo, :endereco, :ponto, NULL, NULL)'
		);
		$stmt_negocio->execute([
			'id_dono' => $_SESSION['usuario_id'],
			'nome' => $_POST['nome_negocio'] ?? '',
			'id_tipo' => (int)$_POST['id_tipo_negocio'],
			'endereco' => $endereco,
			'ponto' => $_POST['ponto_referencia'] ?? ''
		]);

		$stmt_membro = $pdo->prepare(
			"INSERT INTO negocio_membros (id_usuario, id_negocio, funcao)
			 VALUES (:id_usuario, :id_negocio, 'dono')"
		);
		$novo_negocio_id = (int)$pdo->lastInsertId();
		$stmt_membro->execute([
			'id_usuario' => $_SESSION['usuario_id'],
			'id_negocio' => $novo_negocio_id
		]);
		$pdo->commit();

		$_SESSION['funcao'] = 'dono';
		$_SESSION['id_negocio'] = $novo_negocio_id;

		header('Location: ' . BASE_URL . '/views/painel_dono/index.php');
		exit();
	} catch (PDOException $e) {
		if ($pdo->inTransaction()) {
			$pdo->rollBack();
		}
		http_response_code(500);
		exit('Erro ao criar seu negócio. Tente novamente.');
	}
}

if (!isset($_SESSION['usuario_id'])) {
	header('Location: ' . BASE_URL . '/views/login/login.php');
	exit();
}

try {
	$stmt = $pdo->prepare(
		"SELECT funcao, id_negocio
		 FROM negocio_membros
		 WHERE id_usuario = :id_usuario
		 ORDER BY FIELD(funcao, 'dono', 'funcionario')
		 LIMIT 1"
	);
	$stmt->execute(['id_usuario' => $_SESSION['usuario_id']]);
	$membro = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$membro) {
		header('Location: ' . PUBLIC_URL . '/index.php');
		exit();
	}

	$_SESSION['funcao'] = $membro['funcao'];
	$_SESSION['id_negocio'] = $membro['id_negocio'];

	$destino = $membro['funcao'] === 'dono'
		? '/views/painel_dono/index.php'
		: '/views/painel_funcionario/index.php';

	header('Location: ' . BASE_URL . $destino);
	exit();
} catch (PDOException $e) {
	header('Location: ' . BASE_URL . '/views/login/login.php?erro=db');
	exit();
}
