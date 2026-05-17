<?php
/**
 * JobConnect — empresa/ver_perfil.php
 * Exibe o perfil/CV completo de um candidato para a empresa.
 */
session_start();
require_once '../config/connection.php';
requireType('empresa');

$uid         = (int) $_SESSION['user_id'];
$candidatoId = (int) ($_GET['candidato_id'] ?? 0);

if (!$candidatoId) redirect('candidatos.php');

// Verifica se há candidatura deste candidato em vaga da empresa
$stmt = $mysqli->prepare("
    SELECT 1 FROM candidaturas c
    JOIN vagas v ON v.id = c.vaga_id
    WHERE c.candidato_id = ? AND v.empresa_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $candidatoId, $uid);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    die('<p style="padding:40px;color:red;">Acesso negado.</p>');
}
$stmt->close();

// Dados do candidato
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ? AND type = 'candidato'");
$stmt->bind_param('i', $candidatoId); $stmt->execute();
$candidato = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$candidato) redirect('candidatos.php');

// Perfil
$stmt = $mysqli->prepare("SELECT * FROM candidato_perfil WHERE user_id = ?");
$stmt->bind_param('i', $candidatoId); $stmt->execute();
$perfil = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$skills = array_filter(array_map('trim', explode(',', $perfil['habilidades'] ?? '')));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perfil de <?= htmlspecialchars($candidato['name']) ?> — JobConnect</title>
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
        <span class="navbar-page-title">Perfil do Candidato</span>
      </div>
      <div class="navbar-right">
        <a href="javascript:history.back()" class="btn btn-ghost btn-sm">← Voltar</a>
        <a href="../logout.php" class="btn btn-ghost btn-sm">Sair</a>
      </div>
    </nav>

    <div class="page-content fade-in" style="max-width:780px;margin:0 auto;">

      <div class="breadcrumb">
        <a href="dashboard.php">Dashboard</a>
        <span>›</span>
        <a href="candidatos.php">Candidatos</a>
        <span>›</span>
        <span><?= htmlspecialchars($candidato['name']) ?></span>
      </div>

      <!-- Hero -->
      <div class="profile-hero" style="margin-bottom:20px;">
        <div class="profile-avatar-large"><?= initials($candidato['name']) ?></div>
        <div class="profile-hero-info">
          <h2><?= htmlspecialchars($candidato['name']) ?></h2>
          <p>✉️ <?= htmlspecialchars($candidato['email']) ?></p>
          <p style="color:rgba(255,255,255,0.6);font-size:13px;margin-top:4px;">
            Membro desde <?= date('M/Y', strtotime($candidato['created_at'])) ?>
          </p>
          <div style="display:flex;gap:12px;margin-top:12px;flex-wrap:wrap;">
            <?php if (!empty($perfil['linkedin'])): ?>
              <a href="<?= htmlspecialchars($perfil['linkedin']) ?>" target="_blank"
                 class="btn btn-sm" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.3);">
                🔗 LinkedIn
              </a>
            <?php endif; ?>
            <?php if (!empty($perfil['portfolio'])): ?>
              <a href="<?= htmlspecialchars($perfil['portfolio']) ?>" target="_blank"
                 class="btn btn-sm" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.3);">
                🌐 Portfólio
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (!empty($skills)): ?>
        <div class="card" style="margin-bottom:16px;">
          <div class="profile-section-title">⚡ Habilidades</div>
          <div class="skill-tags">
            <?php foreach ($skills as $s): ?>
              <span class="skill-tag"><?= htmlspecialchars($s) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="card" style="margin-bottom:16px;">
        <div class="profile-section-title">🎓 Formação Acadêmica</div>
        <?php if (!empty($perfil['formacao'])): ?>
          <p style="font-size:15px;line-height:1.8;color:var(--text-2);"><?= nl2br(htmlspecialchars($perfil['formacao'])) ?></p>
        <?php else: ?>
          <p style="color:var(--text-3);font-size:14px;">Não informado.</p>
        <?php endif; ?>
      </div>

      <div class="card" style="margin-bottom:16px;">
        <div class="profile-section-title">💼 Experiência Profissional</div>
        <?php if (!empty($perfil['experiencia'])): ?>
          <p style="font-size:15px;line-height:1.8;color:var(--text-2);"><?= nl2br(htmlspecialchars($perfil['experiencia'])) ?></p>
        <?php else: ?>
          <p style="color:var(--text-3);font-size:14px;">Não informado.</p>
        <?php endif; ?>
      </div>

      <div class="card" style="text-align:center;padding:28px;">
        <p style="font-size:15px;color:var(--text-2);margin-bottom:16px;">
          Interessado neste candidato? Entre em contato diretamente.
        </p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
          <a href="mailto:<?= htmlspecialchars($candidato['email']) ?>" class="btn btn-primary">✉️ Enviar e-mail</a>
          <a href="candidatos.php" class="btn btn-ghost">💬 Abrir chat</a>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
</script>
</body>
</html>
