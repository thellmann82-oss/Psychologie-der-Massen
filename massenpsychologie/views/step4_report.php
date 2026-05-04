<?php
$projectId = (int)($_GET['id'] ?? 0);
$title = 'Schritt 4: Bericht – Massenpsychologie-Simulator';
?>
<?php include __DIR__ . '/partials/header.php'; ?>

<div class="workflow-steps">
  <span class="step done">1 Ereignis</span><span class="step-sep">›</span>
  <span class="step done">2 Agenten</span><span class="step-sep">›</span>
  <span class="step done">3 Simulation</span><span class="step-sep">›</span>
  <span class="step active">4 Bericht</span>
</div>

<div class="section">
  <h2>Schritt 4: Analyse &amp; Bericht</h2>

  <div class="card">
    <h3>Bericht generieren</h3>
    <p>Das LLM analysiert die Simulationsergebnisse nach Le Bons Massenpsychologie und dem mass_dynamics-Modell.</p>
    <div class="card-actions">
      <a href="?page=step3&id=<?= $projectId ?>" class="btn btn-secondary">← Zurück</a>
      <button class="btn btn-primary" id="btn-report" onclick="generateReport()">&#128196; Bericht generieren</button>
    </div>
    <div id="report-status" class="status-msg" style="display:none"></div>
  </div>

  <div id="report-content" class="report-box" style="display:none"></div>

  <!-- Agenten-Chat -->
  <div id="chat-section" class="card" style="display:none;margin-top:1.5rem">
    <h3>&#128172; Agenten befragen</h3>
    <p>Stelle Fragen zur Simulation und erhalte Antworten aus dem Kontext der Massendynamik.</p>
    <div id="chat-log" class="chat-log"></div>
    <div class="chat-input-row">
      <input type="text" id="chat-input" class="input" placeholder="z.B. Warum ist Gruppe 1 eskaliert?" onkeydown="if(event.key==='Enter')sendChat()">
      <button class="btn btn-primary" onclick="sendChat()">Senden</button>
    </div>
  </div>
</div>

<script>
const PROJECT_ID = <?= $projectId ?>;
const API = '/massenpsychologie/api';

async function generateReport() {
  const btn = document.getElementById('btn-report');
  const msg = document.getElementById('report-status');
  btn.disabled = true;
  btn.textContent = '⏳ Analysiere…';
  msg.style.display = 'block';
  msg.textContent = 'LLM analysiert Simulationsergebnisse…';

  try {
    const res  = await fetch(`${API}/report.php?action=generate`, {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ project_id: PROJECT_ID })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    msg.textContent = '✓ Bericht fertig!';
    msg.classList.add('success');
    showReport(data.content);
  } catch(e) {
    msg.textContent = 'Fehler: ' + e.message;
    msg.classList.add('error');
  } finally {
    btn.disabled = false;
    btn.textContent = '↻ Neu generieren';
  }
}

function showReport(markdown) {
  const box = document.getElementById('report-content');
  box.style.display = 'block';
  box.innerHTML = marked.parse(markdown);
  document.getElementById('chat-section').style.display = 'block';
}

async function loadExistingReport() {
  const res  = await fetch(`${API}/report.php?action=get&project_id=${PROJECT_ID}`);
  const data = await res.json();
  if (data.report && data.report.content) showReport(data.report.content);
}

async function sendChat() {
  const input   = document.getElementById('chat-input');
  const question = input.value.trim();
  if (!question) return;
  input.value = '';

  const log = document.getElementById('chat-log');
  log.innerHTML += `<div class="chat-msg user"><strong>Du:</strong> ${escHtml(question)}</div>`;

  const res  = await fetch(`${API}/report.php?action=chat`, {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ project_id: PROJECT_ID, question })
  });
  const data = await res.json();
  const answer = data.answer || 'Keine Antwort.';
  log.innerHTML += `<div class="chat-msg agent"><strong>Analyse:</strong> ${escHtml(answer)}</div>`;
  log.scrollTop = log.scrollHeight;
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

loadExistingReport();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
