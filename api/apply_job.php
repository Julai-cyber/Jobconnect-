<?php
/**
 * JobConnect — api/apply_job.php
 * Registra candidatura de um candidato a uma vaga usando mysqli.
 */
session_start();
require_once '../config/connection.php';
requireType('candidato');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../candidato/dashboard.php');
}

$vagaId       = (int) ($_POST['vaga_id']      ?? 0);
$apresentacao = trim($_POST['apresentacao']    ?? '');
$candidatoId  = (int) $_SESSION['user_id'];

if (!$vagaId) {
    $_SESSION['candidato_erro'] = 'Vaga inválida.';
    redirect('../candidato/dashboard.php');
}

// Verifica se vaga existe e está ativa
$stmt = $mysqli->prepare("SELECT id FROM vagas WHERE id = ? AND ativa = 1");
$stmt->bind_param('i', $vagaId);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $_SESSION['candidato_erro'] = 'Vaga não encontrada ou encerrada.';
    $stmt->close();
    redirect('../candidato/dashboard.php');
}
$stmt->close();

// Verifica candidatura duplicada
$stmt = $mysqli->prepare("SELECT id FROM candidaturas WHERE candidato_id = ? AND vaga_id = ?");
$stmt->bind_param('ii', $candidatoId, $vagaId);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $_SESSION['candidato_erro'] = 'Você já se candidatou a esta vaga.';
    $stmt->close();
    redirect('../candidato/dashboard.php');
}
$stmt->close();

// Insere candidatura
$stmt = $mysqli->prepare("INSERT INTO candidaturas (candidato_id, vaga_id, apresentacao) VALUES (?, ?, ?)");
$stmt->bind_param('iis', $candidatoId, $vagaId, $apresentacao);
$stmt->execute();
$stmt->close();

$_SESSION['candidato_sucesso'] = 'Candidatura enviada com sucesso!';
redirect('../candidato/dashboard.php');
