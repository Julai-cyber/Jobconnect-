<?php
/**
 * JobConnect — empresa/candidatos.php
 * Lista candidatos por vaga e chat com cada candidato.
 */
session_start();
require_once '../config/connection.php';
requireType('empresa');

$uid    = (int) $_SESSION['user_id'];
$vagaId = (int) ($_GET['vaga_id'] ?? 0);

// ── Vagas da empresa ────────────────────────────────────────
$stmt = $mysqli->prepare("SELECT id, titulo FROM vagas WHERE empresa_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $uid); $stmt->execute();
$vagasList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$vagaId && !empty($vagasList)) {
    $vagaId = (int) $vagasList[0]['id'];
}

// ── Verifica que a vaga pertence à empresa ───────────────────
$vagaAtual = null;
if ($vagaId) {
    $stmt = $mysqli->prepare("SELECT id, titulo, descricao FROM vagas WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param('ii', $vagaId, $uid); $stmt->execute();
    $vagaAtual = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ── Candidatos desta vaga ────────────────────────────────────
$candidatos = [];
if ($vagaAtual) {
    $stmt = $mysqli->prepare("
        SELECT c.id AS candidatura_id, c.apresentacao, c.created_at,
               u.id AS candidato_id, u.name AS candidato_nome, u.email
        FROM candidaturas c
        JOIN users u ON u.id = c.candidato_id
        WHERE c.vaga_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param('i', $vagaId); $stmt->execute();
    $candidatos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ── Chat da candidatura selecionada ─────────────────────────
$selectedCandId = (int) ($_GET['candidatura_id'] ?? ($candidatos[0]['candidatura_id'] ?? 0));
$mensagens    = [];
$selectedCand = null;

foreach ($candidatos as $c) {
    if ((int)$c['candidatura_id'] === $selectedCandId) { $selectedCand = $c; break; }
}

if ($selectedCand) {
    $stmt = $mysqli->prepare("
        SELECT m.mensagem, m.created_at, u.name AS remetente, u.id AS remetente_id
        FROM mensagens m
        JOIN users u ON u.id = m.remetente_id
        WHERE m.candidatura_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param('i', $selectedCandId); $stmt->execute();
    $mensagens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Candidatos — JobConnect</title>
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
        <span class="navbar-page-title">Candidatos por Vaga</span>
      </div>
      <div class="navbar-right">
        <select onchange="location='candidatos.php?vaga_id='+this.value"
                style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:14px;outline:none;">
          <?php foreach ($vagasList as $v): ?>
            <option value="<?= $v['id'] ?>" <?= (int)$v['id'] === $vagaId ? 'selected' : '' ?>>
              <?= htmlspecialchars($v['titulo']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <a href="../logout.php" class="btn btn-ghost btn-sm">Sair</a>
      </div>
    </nav>

    <div class="page-content fade-in">
      <?php if (empty($vagasList)): ?>
        <div class="empty-state card">
          <div class="empty-icon">📋</div>
          <h3>Nenhuma vaga publicada</h3>
          <p><a href="dashboard.php">Crie uma vaga</a> para receber candidatos.</p>
        </div>
      <?php elseif (!$vagaAtual): ?>
        <div class="alert alert-danger">Vaga não encontrada.</div>
      <?php elseif (empty($candidatos)): ?>
        <div class="empty-state card">
          <div class="empty-icon">👤</div>
          <h3>Nenhum candidato ainda</h3>
          <p>Aguarde — candidatos aparecerão aqui assim que se inscreverem.</p>
        </div>
      <?php else: ?>

        <!-- Info da vaga -->
        <div class="card" style="margin-bottom:20px;padding:16px 20px;display:flex;align-items:center;gap:16px;">
          <div style="width:44px;height:44px;border-radius:12px;background:var(--gradient);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:18px;flex-shrink:0;">
            <?= strtoupper(substr($vagaAtual['titulo'],0,2)) ?>
          </div>
          <div>
            <div style="font-weight:700;font-size:16px;"><?= htmlspecialchars($vagaAtual['titulo']) ?></div>
            <div style="font-size:13px;color:var(--text-3);">👥 <?= count($candidatos) ?> candidato(s)</div>
          </div>
        </div>

        <div class="chat-layout">

          <!-- Lista de candidatos -->
          <div class="chat-sidebar-panel">
            <div class="chat-panel-title">👥 Candidatos (<?= count($candidatos) ?>)</div>
            <div class="chat-list">
              <?php foreach ($candidatos as $cand): ?>
                <a href="candidatos.php?vaga_id=<?= $vagaId ?>&candidatura_id=<?= $cand['candidatura_id'] ?>" style="text-decoration:none;color:inherit;">
                  <div class="chat-list-item <?= (int)$cand['candidatura_id'] === $selectedCandId ? 'active' : '' ?>">
                    <div class="chat-avatar"><?= initials($cand['candidato_nome']) ?></div>
                    <div>
                      <div class="chat-item-name"><?= htmlspecialchars($cand['candidato_nome']) ?></div>
                      <div class="chat-item-preview"><?= htmlspecialchars($cand['email']) ?></div>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Painel principal -->
          <div style="display:flex;flex-direction:column;gap:16px;">
            <?php if ($selectedCand): ?>

              <!-- Card do candidato -->
              <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:16px;">
                  <div class="user-avatar" style="width:48px;height:48px;font-size:18px;"><?= initials($selectedCand['candidato_nome']) ?></div>
                  <div>
                    <div style="font-weight:700;font-size:17px;"><?= htmlspecialchars($selectedCand['candidato_nome']) ?></div>
                    <div style="font-size:13px;color:var(--text-3);">
                      ✉️ <?= htmlspecialchars($selectedCand['email']) ?> &nbsp;·&nbsp;
                      📅 <?= date('d/m/Y', strtotime($selectedCand['created_at'])) ?>
                    </div>
                  </div>
                </div>
                <a href="ver_perfil.php?candidato_id=<?= $selectedCand['candidato_id'] ?>" class="btn btn-secondary btn-sm" target="_blank">
                  👤 Ver perfil/CV
                </a>
              </div>

              <!-- Carta de apresentação -->
              <?php if ($selectedCand['apresentacao']): ?>
                <div class="card">
                  <div class="card-title" style="margin-bottom:10px;">📄 Carta de apresentação</div>
                  <p style="font-size:14px;line-height:1.7;color:var(--text-2);"><?= nl2br(htmlspecialchars($selectedCand['apresentacao'])) ?></p>
                </div>
              <?php endif; ?>

              <!-- Chat -->
              <div class="chat-main" style="flex:1;min-height:350px;">
                <div class="chat-header">
                  <div class="chat-avatar"><?= initials($selectedCand['candidato_nome']) ?></div>
                  <div>
                    <div style="font-weight:700;font-size:15px;"><?= htmlspecialchars($selectedCand['candidato_nome']) ?></div>
                    <div style="font-size:13px;color:var(--text-3);">Chat direto</div>
                  </div>
                </div>

                <div class="chat-messages" id="chatMessages" style="max-height:300px;">
                  <?php if (empty($mensagens)): ?>
                    <div style="text-align:center;color:var(--text-3);font-size:14px;padding:24px 0;">
                      💬 Inicie a conversa com o candidato!
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
                  <textarea id="msgInput" placeholder="Escreva uma mensagem para o candidato..." rows="2"
                            onkeydown="handleEnter(event)"></textarea>
                  <button class="btn btn-primary" onclick="enviarMensagem()">✈️ Enviar</button>
                </div>
              </div>

            <?php else: ?>
              <div class="empty-state card">
                <div class="empty-icon">👈</div>
                <h3>Selecione um candidato</h3>
                <p>Clique em um candidato à esquerda para ver detalhes e conversar.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const candidaturaId = <?= $selectedCandId ?>;
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
