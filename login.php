<?php

session_start();
// Se já estiver logado, redireciona para o dashboard correto
if (!empty($_SESSION['user_id'])) {
    $dest = $_SESSION['user_type'] === 'empresa' ? 'empresa/dashboard.php' : 'candidato/dashboard.php';
    header("Location: $dest"); exit;
}
$erro = $_SESSION['login_erro'] ?? '';
unset($_SESSION['login_erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — JobConnect</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">

  <!-- ── Painel de marca ── -->
  <div class="auth-brand">
    <div class="auth-brand-logo">
      <div class="logo-icon">💼</div>
      <span>JobConnect</span>
    </div>
    <h1>Conectando talentos a oportunidades</h1>
    <p>A plataforma mais moderna para candidatos e empresas encontrarem o match perfeito.</p>

    <div class="auth-features">
      <div class="auth-feature-item"><div class="check">✓</div> Vagas exclusivas das melhores empresas</div>
      <div class="auth-feature-item"><div class="check">✓</div> Chat direto entre candidatos e recrutadores</div>
      <div class="auth-feature-item"><div class="check">✓</div> CV profissional em minutos</div>
      <div class="auth-feature-item"><div class="check">✓</div> Acompanhe todas as suas candidaturas</div>
    </div>
  </div>

  <!-- ── Formulário de login ── -->
  <div class="auth-form-panel">
    <div class="auth-form-box fade-in">
      <h2>Bem-vindo de volta 👋</h2>
      <p class="auth-subtitle">Entre na sua conta para continuar.</p>

      <?php if ($erro): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <form action="api/login.php" method="POST">
        <div class="form-group">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" placeholder="seu@email.com" required autofocus>
        </div>

        <div class="form-group">
          <label for="password">Senha</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px;">
          Entrar na conta
        </button>
      </form>

      <div class="auth-link">
        Não tem conta? <a href="register.php">Cadastre-se grátis</a>
      </div>
    </div>
  </div>

</div>

</body>
</html>
