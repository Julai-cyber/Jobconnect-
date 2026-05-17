<?php
/**
 * JobConnect — candidato/perfil.php
 * Visualização e edição do perfil/CV do candidato.
 */
session_start();
require_once '../config/connection.php';
requireType('candidato');

$uid = (int) $_SESSION['user_id'];

// ── Salvar perfil ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formacao    = trim($_POST['formacao']    ?? '');
    $experiencia = trim($_POST['experiencia'] ?? '');
    $habilidades = trim($_POST['habilidades'] ?? '');
    $linkedin    = trim($_POST['linkedin']    ?? '');
    $portfolio   = trim($_POST['portfolio']   ?? '');

    $stmt = $mysqli->prepare("
        INSERT INTO candidato_perfil (user_id, formacao, experiencia, habilidades, linkedin, portfolio)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            formacao    = VALUES(formacao),
            experiencia = VALUES(experiencia),
            habilidades = VALUES(habilidades),
            linkedin    = VALUES(linkedin),
            portfolio   = VALUES(portfolio)
    ");
    $stmt->bind_param('isssss', $uid, $formacao, $experiencia, $habilidades, $linkedin, $portfolio);
    $stmt->execute();
    $stmt->close();

    $_SESSION['perfil_sucesso'] = 'Perfil atualizado com sucesso!';
    header('Location: perfil.php'); exit;
}

// ── Carrega dados ────────────────────────────────────────────
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $mysqli->prepare("SELECT * FROM candidato_perfil WHERE user_id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$perfil = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$sucesso = $_SESSION['perfil_sucesso'] ?? '';
unset($_SESSION['perfil_sucesso']);

$skills = array_filter(array_map('trim', explode(',', $perfil['habilidades'] ?? '')));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meu Perfil — JobConnect</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-layout">
  <?php include '_sidebar.php'; ?>
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

  <div class="main-content">
    <nav class="navbar">
      <div class="navbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="navbar-page-title">Meu Perfil / CV</span>
      </div>
      <div class="navbar-right">
        <div class="navbar-user">
          <div class="user-avatar"><?= initials($user['name']) ?></div>
          <?= htmlspecialchars($user['name']) ?>
        </div>
        <a href="../logout.php" class="btn btn-ghost btn-sm">Sair</a>
      </div>
    </nav>

    <div class="page-content fade-in">
      <?php if ($sucesso): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($sucesso) ?></div>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;" class="profile-grid">

        <!-- Coluna esquerda: visualização -->
        <div>
          <div class="profile-hero">
            <div class="profile-avatar-large"><?= initials($user['name']) ?></div>
            <div class="profile-hero-info">
              <h2><?= htmlspecialchars($user['name']) ?></h2>
              <p>✉️ <?= htmlspecialchars($user['email']) ?></p>
              <?php if (!empty($perfil['linkedin'])): ?>
                <p style="margin-top:4px;"><a href="<?= htmlspecialchars($perfil['linkedin']) ?>" target="_blank" style="color:rgba(255,255,255,0.9);">🔗 LinkedIn</a></p>
              <?php endif; ?>
              <?php if (!empty($perfil['portfolio'])): ?>
                <p><a href="<?= htmlspecialchars($perfil['portfolio']) ?>" target="_blank" style="color:rgba(255,255,255,0.9);">🌐 Portfólio</a></p>
              <?php endif; ?>
            </div>
          </div>

          <div class="card profile-section">
            <div class="profile-section-title">🎓 Formação Acadêmica</div>
            <?php if (!empty($perfil['formacao'])): ?>
              <p style="font-size:15px;line-height:1.7;color:var(--text-2);"><?= nl2br(htmlspecialchars($perfil['formacao'])) ?></p>
            <?php else: ?>
              <p style="color:var(--text-3);font-size:14px;">Nenhuma formação cadastrada.</p>
            <?php endif; ?>
          </div>

          <div class="card profile-section" style="margin-top:16px;">
            <div class="profile-section-title">💼 Experiência Profissional</div>
            <?php if (!empty($perfil['experiencia'])): ?>
              <p style="font-size:15px;line-height:1.7;color:var(--text-2);"><?= nl2br(htmlspecialchars($perfil['experiencia'])) ?></p>
            <?php else: ?>
              <p style="color:var(--text-3);font-size:14px;">Nenhuma experiência cadastrada.</p>
            <?php endif; ?>
          </div>

          <div class="card profile-section" style="margin-top:16px;">
            <div class="profile-section-title">⚡ Habilidades</div>
            <?php if (!empty($skills)): ?>
              <div class="skill-tags">
                <?php foreach ($skills as $s): ?>
                  <span class="skill-tag"><?= htmlspecialchars($s) ?></span>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p style="color:var(--text-3);font-size:14px;">Nenhuma habilidade cadastrada.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Coluna direita: formulário -->
        <div>
          <div class="card">
            <div class="card-header">
              <div>
                <div class="card-title">✏️ Editar Perfil</div>
                <div class="card-subtitle">Mantenha seu CV sempre atualizado.</div>
              </div>
            </div>
            <form method="POST">
              <div class="form-group">
                <label>Formação acadêmica</label>
                <textarea name="formacao" rows="4" placeholder="Ex: Bacharelado em Ciência da Computação..."><?= htmlspecialchars($perfil['formacao'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label>Experiência profissional</label>
                <textarea name="experiencia" rows="5" placeholder="Ex: Desenvolvedor Backend — Empresa X (2023–atual)..."><?= htmlspecialchars($perfil['experiencia'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label>Habilidades <small style="color:var(--text-3)">(separe por vírgula)</small></label>
                <input type="text" name="habilidades" value="<?= htmlspecialchars($perfil['habilidades'] ?? '') ?>" placeholder="PHP, MySQL, JavaScript, React...">
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>LinkedIn</label>
                  <input type="url" name="linkedin" value="<?= htmlspecialchars($perfil['linkedin'] ?? '') ?>" placeholder="https://linkedin.com/in/...">
                </div>
                <div class="form-group">
                  <label>Portfólio / Site</label>
                  <input type="url" name="portfolio" value="<?= htmlspecialchars($perfil['portfolio'] ?? '') ?>" placeholder="https://meusite.com">
                </div>
              </div>
              <button type="submit" class="btn btn-primary btn-full">💾 Salvar alterações</button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<style>
@media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr !important; } }
</style>
<script>
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
</script>
</body>
</html>
