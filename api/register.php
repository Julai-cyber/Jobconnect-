<?php
/**
 * JobConnect — api/register.php
 * Processa o cadastro de novo usuário usando mysqli.
 */
session_start();
require_once '../config/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../register.php');
}

$name     = trim($_POST['name']             ?? '');
$email    = trim($_POST['email']            ?? '');
$password = trim($_POST['password']         ?? '');
$confirm  = trim($_POST['confirm_password'] ?? '');
$type     = trim($_POST['type']             ?? '');

// Validações
if (!$name || !$email || !$password || !$confirm || !$type) {
    $_SESSION['register_erro'] = 'Preencha todos os campos.';
    redirect('../register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_erro'] = 'E-mail inválido.';
    redirect('../register.php');
}

if (strlen($password) < 6) {
    $_SESSION['register_erro'] = 'A senha deve ter no mínimo 6 caracteres.';
    redirect('../register.php');
}

if ($password !== $confirm) {
    $_SESSION['register_erro'] = 'As senhas não coincidem.';
    redirect('../register.php');
}

if (!in_array($type, ['candidato', 'empresa'])) {
    $_SESSION['register_erro'] = 'Tipo de conta inválido.';
    redirect('../register.php');
}

// Verifica duplicidade de e-mail
$stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $_SESSION['register_erro'] = 'Este e-mail já está cadastrado.';
    $stmt->close();
    redirect('../register.php');
}
$stmt->close();

// Insere usuário com senha criptografada
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $mysqli->prepare("INSERT INTO users (name, email, password, type) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssss', $name, $email, $hash, $type);
$stmt->execute();
$userId = $mysqli->insert_id;
$stmt->close();

// Se candidato, cria perfil vazio
if ($type === 'candidato') {
    $stmt = $mysqli->prepare("INSERT INTO candidato_perfil (user_id) VALUES (?)");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}

$_SESSION['register_sucesso'] = 'Conta criada com sucesso! Faça login para continuar.';
redirect('../login.php');
