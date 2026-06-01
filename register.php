<?php

session_start();
if (!empty($_SESSION['user_id'])) {
    $dest = $_SESSION['user_type'] === 'empresa' ? 'empresa/dashboard.php' : 'candidato/dashboard.php';
    header("Location: $dest"); exit;
}
$erro    = $_SESSION['register_erro']    ?? '';
$sucesso = $_SESSION['register_sucesso'] ?? '';
unset($_SESSION['register_erro'], $_SESSION['register_sucesso']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro — JobConnect</title>
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
    <h1>Crie sua conta gratuita</h1>
    <p>Junte-se a milhares de candidatos e empresas que já usam o JobConnect.</p>

    <div class="auth-features">
      <div class="auth-feature-item"><div class="check">✓</div> Cadastro 100% gratuito</div>
      <div class="auth-feature-item"><div class="check">✓</div> Acesso imediato às vagas</div>
      <div class="auth-feature-item"><div class="check">✓</div> Chat integrado com recrutadores</div>
      <div class="auth-feature-item"><div class="check">✓</div> Perfil profissional completo</div>
    </div>
  </div>

  <!-- ── Formulário de cadastro ── -->
  <div class="auth-form-panel">
    <div class="auth-form-box fade-in">
      <h2>Criar conta</h2>
      <p class="auth-subtitle">Escolha seu tipo de conta para começar.</p>

      <?php if ($erro): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <?php if ($sucesso): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($sucesso) ?></div>
      <?php endif; ?>

      <!-- Seletor de tipo de conta -->
      <label style="font-size:13px;font-weight:600;color:var(--text-2);margin-bottom:8px;display:block;">Tipo de conta</label>
      <div class="type-selector" id="typeSelector">
        <label class="type-card active" id="card-candidato">
          <input type="radio" name="type_display" value="candidato" checked>
          <div class="type-icon">👤</div>
          <span>Candidato</span>
        </label>
        <label class="type-card" id="card-empresa">
          <input type="radio" name="type_display" value="empresa">
          <div class="type-icon">🏢</div>
          <span>Empresa</span>
        </label>
      </div>

      <form action="api/register.php" method="POST" id="registerForm">
        <input type="hidden" name="type" id="hiddenType" value="candidato">

        <div class="form-group">
          <label for="name" id="nameLabel">Nome completo</label>
          <input type="text" id="name" name="name" placeholder="Seu nome completo" required>
        </div>

        <div class="form-group">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" placeholder="seu@email.com" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" placeholder="Mín. 6 caracteres" required minlength="6">
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirmar senha</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repita a senha" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:4px;">
          Criar conta gratuita
        </button>
      </form>

      <div class="auth-link">
        Já tem conta? <a href="login.php">Fazer login</a>
      </div>
    </div>
  </div>

</div>

<script>
// Alterna tipo de conta e atualiza label do nome
const cards   = document.querySelectorAll('.type-card');
const hidden  = document.getElementById('hiddenType');
const nameLabel = document.getElementById('nameLabel');

cards.forEach(card => {
  card.addEventListener('click', () => {
    cards.forEach(c => c.classList.remove('active'));
    card.classList.add('active');
    const val = card.querySelector('input').value;
    hidden.value = val;
    nameLabel.textContent = val === 'empresa' ? 'Nome da empresa' : 'Nome completo';
  });
});
</script>
</body>
</html>
