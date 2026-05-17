<?php
/**
 * JobConnect — candidato/minhas_candidaturas.php
 * Lista candidaturas do candidato e exibe chat com a empresa.
 */
session_start();
require_once '../config/connection.php';
requireType('candidato');

$uid = (int) $_SESSION['user_id'];

// ── Candidaturas do candidato ───────────────────────────────
$stmt = $mysqli->prepare("
    SELECT c.id AS candidatura_id,
           c.apresentacao,
           c.created_at,
           v.titulo    AS vaga_titulo,
           v.descricao AS vaga_descricao,
           u.name      AS empresa_nome,
           u.id        AS empresa_id
    FROM candidaturas c
    JOIN vagas v ON v.id = c.vaga_id
    JOIN users u ON u.id = v.empresa_id
    WHERE c.candidato_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$candidaturas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Candidatura selecionada para o chat ─────────────────────
$selectedId = (int) ($_GET['candidatura_id'] ?? ($candidaturas[0]['candidatura_id'] ?? 0));

$mensagens    = [];
$selectedCand = null;

foreach ($candidaturas as $c) {
    if ((int)$c['candidatura_id'] === $selectedId) { $selectedCand = $c; break; }
}

if ($selectedId && $selectedCand) {
    $stmt = $mysqli->prepare("
        SELECT m.mensagem, m.created_at, u.name AS remetente, u.id AS remetente_id
        FROM mensagens m
        JOIN users u ON u.id = m.remetente_id
        WHERE m.candidatura_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param('i', $selectedId);
    $stmt->execute();
    $mensagens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Minhas Candidaturas — JobConnect</title>
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
        <span class="navbar-page-title">Minhas Candidaturas</span>
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
      <?php if (empty($candidaturas)): ?>
        <div class="empty-state card">
          <div class="empty-icon">📭</div>
          <h3>Nenhuma candidatura ainda</h3>
          <p>Explore as vagas disponíveis e se candidate!</p>
          <a href="dashboard.php" class="btn btn-primary" style="margin-top:16px;">Ver Vagas</a>
        </div>
      <?php else: ?>
        <div class="chat-layout">

          <!-- Lista de candidaturas -->
          <div class="chat-sidebar-panel">
            <div class="chat-panel-title">📋 Candidaturas (<?= count($candidaturas) ?>)</div>
            <div class="chat-list">
              <?php foreach ($candidaturas as $cand): ?>
                <a href="?candidatura_id=<?= $cand['candidatura_id'] ?>" style="text-decoration:none;color:inherit;">
                  <div class="chat-list-item <?= (int)$cand['candidatura_id'] === $selectedId ? 'active' : '' ?>">
                    <div class="chat-avatar"><?= strtoupper(substr($cand['empresa_nome'],0,2)) ?></div>
                    <div>
                      <div class="chat-item-name"><?= htmlspecialchars($cand['vaga_titulo']) ?></div>
                      <div class="chat-item-preview">🏢 <?= htmlspecialchars($cand['empresa_nome']) ?></div>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Painel de chat -->
          <div class="chat-main">
            <?php if ($selectedCand): ?>
              <div class="chat-header">
                <div class="chat-avatar"><?= strtoupper(substr($selectedCand['empresa_nome'],0,2)) ?></div>
                <div>
                  <div style="font-weight:700;font-size:15px;"><?= htmlspecialchars($selectedCand['vaga_titulo']) ?></div>
                  <div style="font-size:13px;color:var(--text-3);">
                    🏢 <?= htmlspecialchars($selectedCand['empresa_nome']) ?> &nbsp;·&nbsp;
                    Candidatado em <?= date('d/m/Y', strtotime($selectedCand['created_at'])) ?>
                  </div>
                </div>
              </div>

              <div class="chat-messages" id="chatMessages">
                <?php if ($selectedCand['apresentacao']): ?>
                  <div class="msg-bubble sent">
                    <div class="msg-text"><strong>📄 Carta de apresentação:</strong><br><?= nl2br(htmlspecialchars($selectedCand['apresentacao'])) ?></div>
                    <div class="msg-time"><?= date('d/m/Y H:i', strtotime($selectedCand['created_at'])) ?></div>
                  </div>
                <?php endif; ?>

                <?php if (empty($mensagens)): ?>
                  <div style="text-align:center;color:var(--text-3);font-size:14px;padding:24px 0;">
                    💬 Nenhuma mensagem ainda. Inicie a conversa!
                  </div>
                <?php else: ?>
                  <?php foreach ($mensagens as $msg): ?>
                    <div class="msg-bubble <?= (int)$msg['remetente_id'] === $uid ? 'sent' : 'received' ?>">
                      <div class="msg-text"><?= nl2br(htmlspecialchars($msg['mensagem'])) ?></div>
                      <div class="msg-time"><?= htmlspecialchars($msg['remetente']) ?> · <?= date('d/m H:i', strtotime($msg['created_at'])) ?></div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>

              <div class="chat-input-area">
                <textarea id="msgInput" placeholder="Digite sua mensagem..." rows="2"
                          onkeydown="handleEnter(event)"></textarea>
                <button class="btn btn-primary" onclick="enviarMensagem()">✈️ Enviar</button>
              </div>

            <?php else: ?>
              <div class="empty-state" style="margin:auto;">
                <div class="empty-icon">💬</div>
                <h3>Selecione uma candidatura</h3>
                <p>Clique em uma candidatura para ver o chat.</p>
              </div>
            <?php endif; ?>
          </div>

        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const candidaturaId = <?= $selectedId ?>;
const userId = <?= $uid ?>;

function scrollBottom() {
  const box = document.getElementById('chatMessages');
  if (box) box.scrollTop = box.scrollHeight;
}
scrollBottom();

function handleEnter(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); enviarMensagem(); }
}

function enviarMensagem() {
  const input = document.getElementById('msgInput');
  const texto = input.value.trim();
  if (!texto || !candidaturaId) return;

  fetch('../api/send_message.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ candidatura_id: candidaturaId, mensagem: texto })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      input.value = '';
      const box = document.getElementById('chatMessages');
      const div = document.createElement('div');
      div.className = 'msg-bubble sent';
      div.innerHTML = `<div class="msg-text">${data.message.mensagem.replace(/\n/g,'<br>')}</div><div class="msg-time">Você · agora</div>`;
      box.appendChild(div);
      scrollBottom();
    }
  });
}

function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
</script>
</body>
</html>
