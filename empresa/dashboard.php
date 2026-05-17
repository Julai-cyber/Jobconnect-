<?php
/**
 * JobConnect — empresa/dashboard.php
 * Dashboard da empresa: cria vagas, lista vagas e estatísticas.
 */
session_start();
require_once '../config/connection.php';
requireType('empresa');

$uid = (int) $_SESSION['user_id'];

// ── Estatísticas ────────────────────────────────────────────
$stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM vagas WHERE empresa_id = ?");
$stmt->bind_param('i', $uid); $stmt->execute();
$totalVagas = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $mysqli->prepare("
    SELECT COUNT(*) AS total FROM candidaturas c
    JOIN vagas v ON v.id = c.vaga_id WHERE v.empresa_id = ?
");
$stmt->bind_param('i', $uid); $stmt->execute();
$totalCands = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ── Vagas com contagem de candidatos ────────────────────────
$stmt = $mysqli->prepare("
    SELECT v.id, v.titulo, v.descricao, v.ativa, v.created_at,
           COUNT(c.id) AS total_candidatos
    FROM vagas v
    LEFT JOIN candidaturas c ON c.vaga_id = v.id
    WHERE v.empresa_id = ?
    GROUP BY v.id
    ORDER BY v.created_at DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$vagas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Flash messages ───────────────────────────────────────────
$erro    = $_SESSION['empresa_erro']    ?? '';
$sucesso = $_SESSION['empresa_sucesso'] ?? '';
unset($_SESSION['empresa_erro'], $_SESSION['empresa_sucesso']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Empresa — JobConnect</title>
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
        <span class="navbar-page-title">Dashboard da Empresa</span>
      </div>
      <div class="navbar-right">
        <div class="navbar-user">
          <div class="user-avatar"><?= initials($_SESSION['user_name']) ?></div>
          <?= htmlspecialchars($_SESSION['user_name']) ?>
        </div>
        <button class="btn btn-primary btn-sm" onclick="abrirModalVaga()">+ Nova Vaga</button>
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
            <div class="stat-label">Vagas publicadas</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">👥</div>
          <div class="stat-info">
            <div class="stat-value"><?= $totalCands ?></div>
            <div class="stat-label">Total de candidatos</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange">⚡</div>
          <div class="stat-info">
            <div class="stat-value"><?= $totalVagas > 0 ? round($totalCands / $totalVagas, 1) : 0 ?></div>
            <div class="stat-label">Candidatos por vaga</div>
          </div>
        </div>
      </div>

      <!-- Lista de vagas -->
      <div class="page-header">
        <div>
          <h1>Minhas Vagas</h1>
          <p>Gerencie as oportunidades publicadas pela sua empresa.</p>
        </div>
        <button class="btn btn-primary" onclick="abrirModalVaga()">+ Publicar nova vaga</button>
      </div>

      <?php if (empty($vagas)): ?>
        <div class="empty-state card">
          <div class="empty-icon">📝</div>
          <h3>Nenhuma vaga publicada</h3>
          <p>Crie sua primeira vaga para começar a receber candidatos.</p>
          <button class="btn btn-primary" style="margin-top:16px;" onclick="abrirModalVaga()">+ Publicar vaga</button>
        </div>
      <?php else: ?>
        <div class="card">
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Vaga</th>
                  <th>Publicada em</th>
                  <th>Candidatos</th>
                  <th>Status</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($vagas as $v): ?>
                  <tr>
                    <td>
                      <strong><?= htmlspecialchars($v['titulo']) ?></strong>
                      <div style="font-size:12px;color:var(--text-3);margin-top:2px;">
                        <?= mb_substr(strip_tags($v['descricao']), 0, 80) ?>...
                      </div>
                    </td>
                    <td><?= date('d/m/Y', strtotime($v['created_at'])) ?></td>
                    <td><span class="badge badge-primary">👥 <?= $v['total_candidatos'] ?></span></td>
                    <td>
                      <?php if ($v['ativa']): ?>
                        <span class="badge badge-success">✅ Ativa</span>
                      <?php else: ?>
                        <span class="badge badge-warning">⏸ Encerrada</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="candidatos.php?vaga_id=<?= $v['id'] ?>" class="btn btn-secondary btn-sm">
                        👥 Ver candidatos
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal nova vaga -->
<div class="modal-overlay" id="modalVaga">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">📝 Publicar nova vaga</span>
      <button class="modal-close" onclick="fecharModalVaga()">✕</button>
    </div>
    <form action="../api/create_job.php" method="POST">
      <div class="modal-body">
        <div class="form-group">
          <label>Título da vaga</label>
          <input type="text" name="titulo" placeholder="Ex: Desenvolvedor Full Stack Pleno" required>
        </div>
        <div class="form-group">
          <label>Descrição da vaga</label>
          <textarea name="descricao" rows="6" placeholder="Descreva responsabilidades, requisitos, benefícios..." required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="fecharModalVaga()">Cancelar</button>
        <button type="submit" class="btn btn-primary">📢 Publicar vaga</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModalVaga() { document.getElementById('modalVaga').classList.add('open'); }
function fecharModalVaga() { document.getElementById('modalVaga').classList.remove('open'); }
document.getElementById('modalVaga').addEventListener('click', function(e) { if (e.target === this) fecharModalVaga(); });
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
</script>
</body>
</html>
