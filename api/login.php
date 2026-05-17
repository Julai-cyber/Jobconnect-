<?php
/**
 * JobConnect — api/login.php
 * Processa o formulário de login usando mysqli.
 */
session_start();
require_once '../config/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../login.php');
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if (!$email || !$password) {
    $_SESSION['login_erro'] = 'Preencha todos os campos.';
    redirect('../login.php');
}

// Busca usuário pelo e-mail (prepared statement)
$stmt = $mysqli->prepare("SELECT id, name, email, password, type FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['login_erro'] = 'E-mail ou senha incorretos.';
    redirect('../login.php');
}

// Cria sessão
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_name']  = $user['name'];
$_SESSION['user_type']  = $user['type'];
$_SESSION['user_email'] = $user['email'];

// Redireciona conforme tipo
if ($user['type'] === 'empresa') {
    redirect('../empresa/dashboard.php');
} else {
    redirect('../candidato/dashboard.php');
}
