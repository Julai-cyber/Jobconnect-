<?php
/**
 * JobConnect — candidato/dashboard.php
 * Dashboard do candidato: lista vagas disponíveis e permite candidatura.
 */
session_start();
require_once '../config/connection.php';
requireType('candidato');

$uid = (int) $_SESSION['user_id'];

// ── Estatísticas ────────────────────────────────────────────
$res        = $mysqli->query("SELECT COUNT(*) AS total FROM vagas WHERE ativa = 1");
$totalVagas = (int) $res->fetch_assoc()['total'];

$stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM candidaturas WHERE candidato_id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$totalCandidaturas = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ── Vagas com flag "já candidatei" ──────────────────────────
$stmt = $mysqli->prepare("
    SELECT v.id, v.titulo, v.descricao, v.created_at,
           u.name AS empresa_nome,
           (SELECT id FROM candidaturas
            WHERE candidato_id = ? AND vaga_id = v.id
            LIMIT 1) AS candidatura_id
    FROM vagas v
    JOIN users u ON u.id = v.empresa_id
    WHERE v.ativa = 1
    ORDER BY v.created_at DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$vagas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Flash messages ───────────────────────────────────────────
$erro    = $_SESSION['candidato_erro']    ?? '';
$sucesso = $_SESSION['candidato_sucesso'] ?? '';
unset($_SESSION['candidato_erro'], $_SESSION['candidato_sucesso']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — JobConnect</title>
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
        <span class="navbar-page-title">Vagas Disponíveis</span>
      </div>
      <div class="navbar-right">
        <div class="navbar-user">
          <div class="user-avatar"><?= initials($_SESSION['user_name']) ?></div>
          <?= htmlspecialchars($_SESSION['user_name']) ?>
        </div>
        <a href="../logout.php" class="btn btn-ghost btn-sm">Sair</a>
      </div>
    </nav>

    <div class="page-content fade-in">
      <?php if ($erro): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <?php if ($sucesso): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($sucesso) ?></div>
      <?php endif; ?>

      <!-- Estatísticas -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">📋</div>
          <div class="stat-info">
            <div class="stat-value"><?= $totalVagas ?></div>
            <div class="stat-label">Vagas disponíveis</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">✅</div>
          <div class="stat-info">
            <div class="stat-value"><?= $totalCandidaturas ?></div>
            <div class="stat-label">Minhas candidaturas</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange">🎯</div>
          <div class="stat-info">
            <div class="stat-value"><?= max(0, $totalVagas - $totalCandidaturas) ?></div>
            <div class="stat-label">Novas oportunidades</div>
          </div>
        </div>
      </div>

      <!-- Lista de vagas -->
      <div class="page-header">
        <div>
          <h1>Explorar Vagas</h1>
          <p>Encontre a oportunidade perfeita para você.</p>
        </div>
      </div>

      <?php if (empty($vagas)): ?>
        <div class="empty-state card">
          <div class="empty-icon">🔍</div>
          <h3>Nenhuma vaga disponível</h3>
          <p>Volte em breve — novas oportunidades são publicadas frequentemente.</p>
        </div>
      <?php else: ?>
        <div class="jobs-grid">
          <?php foreach ($vagas as $vaga): ?>
            <div class="job-card">
              <div class="job-card-header">
                <div>
                  <div class="job-title"><?= htmlspecialchars($vaga['titulo']) ?></div>
                  <div class="job-company">🏢 <?= htmlspecialchars($vaga['empresa_nome']) ?></div>
                </div>
                <div class="job-company-badge"><?= strtoupper(substr($vaga['empresa_nome'], 0, 2)) ?></div>
              </div>

              <div class="job-desc"><?= nl2br(htmlspecialchars($vaga['descricao'])) ?></div>

              <div class="job-meta">
                <span class="job-tag">📅 <?= date('d/m/Y', strtotime($vaga['created_at'])) ?></span>
                <?php if ($vaga['candidatura_id']): ?>
                  <span class="job-tag applied">✅ Já candidatado</span>
                <?php endif; ?>
              </div>

              <?php if ($vaga['candidatura_id']): ?>
                <button class="btn btn-ghost btn-sm" disabled>✅ Já Candidatado</button>
              <?php else: ?>
                <button class="btn btn-primary btn-sm"
                        onclick="abrirModal(<?= $vaga['id'] ?>, '<?= addslashes(htmlspecialchars($vaga['titulo'])) ?>')">
                  🚀 Candidatar-se
                </button>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal candidatura -->
<div class="modal-overlay" id="modalCandidatura">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="modalTitulo">Candidatar-se</span>
      <button class="modal-close" onclick="fecharModal()">✕</button>
    </div>
    <form action="../api/apply_job.php" method="POST">
      <input type="hidden" name="vaga_id" id="modalVagaId">
      <div class="modal-body">
        <div class="form-group">
          <label for="apresentacao">Carta de apresentação</label>
          <textarea id="apresentacao" name="apresentacao"
                    placeholder="Apresente-se brevemente e explique por que você é o candidato ideal..."
                    rows="5"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="fecharModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">🚀 Enviar candidatura</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModal(vagaId, titulo) {
  document.getElementById('modalVagaId').value = vagaId;
  document.getElementById('modalTitulo').textContent = 'Candidatar-se: ' + titulo;
  document.getElementById('modalCandidatura').classList.add('open');
}
function fecharModal() {
  document.getElementById('modalCandidatura').classList.remove('open');
}
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
document.getElementById('modalCandidatura').addEventListener('click', function(e) {
  if (e.target === this) fecharModal();
});
</script>
</body>
</html>
