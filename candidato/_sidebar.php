<?php
/**
 * Sidebar do candidato — inclusa em todas as páginas do candidato.
 * Detecta a página atual para marcar o link ativo.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">💼</div>
    <span class="logo-text">JobConnect</span>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Menu</div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon">🏠</span> Dashboard
      </a>
      <a href="minhas_candidaturas.php" class="<?= $currentPage === 'minhas_candidaturas.php' ? 'active' : '' ?>">
        <span class="nav-icon">📋</span> Minhas Candidaturas
      </a>
      <a href="perfil.php" class="<?= $currentPage === 'perfil.php' ? 'active' : '' ?>">
        <span class="nav-icon">👤</span> Meu Perfil / CV
      </a>
    </nav>
  </div>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar"><?= initials($_SESSION['user_name']) ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
        <div class="user-role">Candidato</div>
      </div>
    </div>
    <a href="../logout.php" class="btn btn-ghost btn-sm btn-full" style="margin-top:10px;">
      🚪 Sair
    </a>
  </div>
</aside>
