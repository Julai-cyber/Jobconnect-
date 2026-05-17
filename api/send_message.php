<?php
/**
 * JobConnect — api/send_message.php
 * Envia mensagem no chat de uma candidatura usando mysqli.
 * Suporta POST normal e AJAX (JSON).
 */
session_start();
require_once '../config/connection.php';
requireLogin();

// Detecta se é requisição AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Lê dados (JSON ou POST)
if ($isAjax) {
    $body          = json_decode(file_get_contents('php://input'), true) ?? [];
    $candidaturaId = (int) ($body['candidatura_id'] ?? $_POST['candidatura_id'] ?? 0);
    $mensagem      = trim($body['mensagem']          ?? $_POST['mensagem']        ?? '');
} else {
    $candidaturaId = (int) ($_POST['candidatura_id'] ?? 0);
    $mensagem      = trim($_POST['mensagem']          ?? '');
}

$uid = (int) $_SESSION['user_id'];

// Validação
if (!$candidaturaId || !$mensagem) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
        exit;
    }
    redirect('../login.php');
}

// Verifica permissão: somente o candidato ou a empresa da vaga podem enviar
$stmt = $mysqli->prepare("
    SELECT c.id, c.candidato_id, v.empresa_id
    FROM candidaturas c
    JOIN vagas v ON v.id = c.vaga_id
    WHERE c.id = ?
");
$stmt->bind_param('i', $candidaturaId);
$stmt->execute();
$cand = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cand || ($uid !== (int)$cand['candidato_id'] && $uid !== (int)$cand['empresa_id'])) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Sem permissão.']);
        exit;
    }
    redirect('../login.php');
}

// Insere a mensagem
$stmt = $mysqli->prepare("INSERT INTO mensagens (candidatura_id, remetente_id, mensagem) VALUES (?, ?, ?)");
$stmt->bind_param('iis', $candidaturaId, $uid, $mensagem);
$stmt->execute();
$msgId = $mysqli->insert_id;
$stmt->close();

// Busca a mensagem recém-inserida para retornar ao front
$stmt = $mysqli->prepare("
    SELECT m.id, m.mensagem, m.created_at, u.name AS remetente_nome
    FROM mensagens m
    JOIN users u ON u.id = m.remetente_id
    WHERE m.id = ?
");
$stmt->bind_param('i', $msgId);
$stmt->execute();
$nova = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $nova]);
    exit;
}

// Fallback sem JS
$type = $_SESSION['user_type'];
if ($type === 'empresa') {
    redirect('../empresa/candidatos.php?candidatura_id=' . $candidaturaId);
} else {
    redirect('../candidato/minhas_candidaturas.php?candidatura_id=' . $candidaturaId);
}
