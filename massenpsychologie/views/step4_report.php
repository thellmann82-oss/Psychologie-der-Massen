<?php
$projectId = (int)($_GET['id'] ?? 0);
$title = 'Schritt 4: Bericht – Massenpsychologie-Simulator';
?>
<?php include __DIR__ . '/partials/header.php'; ?>

<div class="stepper animate-in">
  <div class="stepper-item done"><div class="stepper-circle">&#10003;</div><div class="stepper-label">Ereignis</div></div>
  <div class="stepper-line"></div>
  <div class="stepper-item done"><div class="stepper-circle">&#10003;</div><div class="stepper-label">Agenten</div></div>
  <div class="stepper-line"></div>
  <div class="stepper-item done"><div class="stepper-circle">&#10003;</div><div class="stepper-label">Simulation</div></div>
  <div class="stepper-line"></div>
  <div class="stepper-item active"><div class="stepper-circle">4</div><div class="stepper-label">Bericht</div></div>
</div>

<div class="animate-in animate-in-delay-1">
  <div class="section-title" style="margin-bottom:.3rem">Schritt 4 &mdash; Analyse &amp; Bericht</div>
  <div class="section-desc">Das LLM analysiert alle Simulationsdaten nach Le Bons Massenpsychologie und erstellt einen strukturierten Wissenschaftsbericht.</div>

  <div class="card">
    <div class="card-title"><div class="card-title-icon">&#128196;</div>Bericht generieren</div>
    <div class="card-body">
      Der Bericht umfasst 7 Abschnitte: Ereigniszusammenfassung, Gruppenbildung, Meinungsentwicklung,
      Emotionsverstärkung, Normbildung, Gesamtausgang und Le-Bon-Perspektive.
    </div>
    <div class="card-actions">
      <a href="?page=step3&id=<?= $projectId ?>" class="btn btn-secondary">&larr; Zurück</a>
      <button class="btn btn-primary" id="btn-report" onclick="generateReport()">&#128196; Bericht generieren</button>
    </div>
    <div id="report-status" class="status-msg" style="display:none"><span></span></div>
  </div>
</div>

<!-- Bericht -->
<div id="report-content" class="report-box animate-in" style="display:none"></div>

<!-- Chat -->
<div id="chat-section" class="card animate-in" style="display:none;margin-top:1.5rem">
  <div class="card-title">
    <div class="card-title-icon">&#128172;</div>
    Simulation befragen
  </div>
  <div class="card-body" style="margin-bottom:.85rem">
    Stelle Fragen zum Simulationsverlauf &mdash; das KI-Modell antwortet im Kontext der Massendynamik.
  </div>
  <div id="chat-log" class="chat-log"></div>
  <div class="chat-input-row">
    <input type="text" id="chat-input" class="input"
      placeholder="z.B. Warum ist die Kontra-Gruppe eskaliert?"
      onkeydown="if(event.key==='Enter')sendChat()">
    <button class="btn btn-primary" onclick="sendChat()">Senden</button>
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
  msg.style.display = 'flex';
  msg.className = 'status-msg';
  msg.querySelector('span').textContent = 'LLM analysiert Simulationsergebnisse nach Le Bon…';

  try {
    const res  = await fetch(`${API}/report.php?action=generate`, {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ project_id: PROJECT_ID })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    msg.className = 'status-msg success';
    msg.querySelector('span').textContent = 'Bericht erfolgreich generiert!';
    showReport(data.content);
  } catch(e) {
    msg.className = 'status-msg error';
    msg.querySelector('span').textContent = 'Fehler: ' + e.message;
  } finally {
    btn.disabled = false;
    btn.textContent = '&#8635; Neu generieren';
  }
}

function showReport(markdown) {
  const box = document.getElementById('report-content');
  box.style.display = 'block';
  box.innerHTML = marked.parse(markdown);
  document.getElementById('chat-section').style.display = 'block';
  box.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function loadExistingReport() {
  const res  = await fetch(`${API}/report.php?action=get&project_id=${PROJECT_ID}`);
  const data = await res.json();
  if (data.report && data.report.content) showReport(data.report.content);
}

async function sendChat() {
  const input    = document.getElementById('chat-input');
  const question = input.value.trim();
  if (!question) return;
  input.value = '';

  const log = document.getElementById('chat-log');
  log.innerHTML += `
    <div class="chat-bubble user">
      <strong>Du</strong>${escHtml(question)}
    </div>`;
  log.scrollTop = log.scrollHeight;

  const res  = await fetch(`${API}/report.php?action=chat`, {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ project_id: PROJECT_ID, question })
  });
  const data = await res.json();
  log.innerHTML += `
    <div class="chat-bubble agent">
      <strong>Analyse</strong>${escHtml(data.answer || 'Keine Antwort.')}
    </div>`;
  log.scrollTop = log.scrollHeight;
}

loadExistingReport();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
