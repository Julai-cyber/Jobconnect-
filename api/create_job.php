<?php
/**
 * JobConnect — api/create_job.php
 * Cria uma nova vaga (apenas empresas) usando mysqli.
 */
session_start();
require_once '../config/connection.php';
requireType('empresa');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../empresa/dashboard.php');
}

$titulo    = trim($_POST['titulo']    ?? '');
$descricao = trim($_POST['descricao'] ?? '');

if (!$titulo || !$descricao) {
    $_SESSION['empresa_erro'] = 'Preencha título e descrição da vaga.';
    redirect('../empresa/dashboard.php');
}

$empresaId = (int) $_SESSION['user_id'];

$stmt = $mysqli->prepare("INSERT INTO vagas (empresa_id, titulo, descricao) VALUES (?, ?, ?)");
$stmt->bind_param('iss', $empresaId, $titulo, $descricao);
$stmt->execute();
$stmt->close();

$_SESSION['empresa_sucesso'] = 'Vaga publicada com sucesso!';
redirect('../empresa/dashboard.php');
