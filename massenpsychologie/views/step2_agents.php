<?php
$projectId = (int)($_GET['id'] ?? 0);
$title = 'Schritt 2: Agenten – Massenpsychologie-Simulator';
?>
<?php include __DIR__ . '/partials/header.php'; ?>

<div class="workflow-steps">
  <span class="step done">1 Ereignis</span>
  <span class="step-sep">›</span>
  <span class="step active">2 Agenten</span>
  <span class="step-sep">›</span>
  <span class="step">3 Simulation</span>
  <span class="step-sep">›</span>
  <span class="step">4 Bericht</span>
</div>

<div class="section">
  <h2>Schritt 2: Agenten generieren</h2>
  <p class="hint">Das LLM erzeugt Agenten mit individuellen Persönlichkeiten, Meinungen und Hemmungsniveaus basierend auf dem Ereignis.</p>

  <div class="card">
    <h3>Konfiguration</h3>
    <label>Anzahl Agenten (5–50)</label>
    <input type="number" id="num-agents" value="20" min="5" max="50" class="input input-sm">
    <div class="card-actions">
      <a href="?page=step1&id=<?= $projectId ?>" class="btn btn-secondary">← Zurück</a>
      <button class="btn btn-primary" id="btn-generate" onclick="generateAgents()">
        &#9889; Agenten per KI generieren
      </button>
    </div>
    <div id="gen-status" class="status-msg" style="display:none"></div>
  </div>

  <div id="agents-section" style="display:none">
    <div class="groups-row" id="groups-row"></div>
    <h3>Alle Agenten</h3>
    <div id="agents-grid" class="agents-grid"></div>
    <div class="card-actions">
      <button class="btn btn-primary" onclick="location.href='?page=step3&id=<?= $projectId ?>'">
        Weiter: Simulation starten →
      </button>
    </div>
  </div>
</div>

<script>
const PROJECT_ID = <?= $projectId ?>;
const API = '/massenpsychologie/api';

async function generateAgents() {
  const n   = parseInt(document.getElementById('num-agents').value) || 20;
  const btn = document.getElementById('btn-generate');
  const msg = document.getElementById('gen-status');
  btn.disabled = true;
  btn.textContent = '⏳ Generiere…';
  msg.style.display = 'block';
  msg.textContent = 'Das LLM erstellt Agenten-Profile. Dies kann 20–60 Sekunden dauern…';

  try {
    const res  = await fetch(`${API}/agents.php?action=generate`, {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ project_id: PROJECT_ID, num_agents: n })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    msg.textContent = `✓ ${data.count} Agenten generiert!`;
    msg.classList.add('success');
    loadAgents();
  } catch(e) {
    msg.textContent = 'Fehler: ' + e.message;
    msg.classList.add('error');
  } finally {
    btn.disabled = false;
    btn.textContent = '⚡ Neu generieren';
  }
}

async function loadAgents() {
  const res  = await fetch(`${API}/agents.php?action=list&project_id=${PROJECT_ID}`);
  const data = await res.json();
  if (!data.success || !data.agents.length) return;

  document.getElementById('agents-section').style.display = 'block';

  // Gruppen
  const groupsRow = document.getElementById('groups-row');
  groupsRow.innerHTML = data.groups.map(g => `
    <div class="group-card ${g.escalated ? 'escalated' : ''}">
      <div class="group-name">${g.name}</div>
      <div class="group-stat">${g.size} Mitglieder</div>
      <div class="group-opinion">Ø Meinung: ${parseFloat(g.average_opinion).toFixed(2)}</div>
      <div class="opinion-bar"><div class="opinion-fill" style="width:${opinionToWidth(g.average_opinion)}%;background:${opinionColor(g.average_opinion)}"></div></div>
    </div>
  `).join('');

  // Agenten
  const grid = document.getElementById('agents-grid');
  grid.innerHTML = data.agents.map(a => `
    <div class="agent-card">
      <div class="agent-name">${escHtml(a.name)}</div>
      <div class="agent-type badge badge-grey">${escHtml(a.personality_type)}</div>
      <div class="agent-bio">${escHtml(a.bio)}</div>
      <div class="agent-stats">
        <span title="Meinung">M: ${parseFloat(a.opinion).toFixed(2)}</span>
        <span title="Emotion">E: ${parseFloat(a.emotional_state).toFixed(2)}</span>
        <span title="Hemmung">H: ${parseFloat(a.inhibition_level).toFixed(2)}</span>
        ${a.anonymity ? '<span class="anon-badge">anonym</span>' : ''}
      </div>
      <div class="opinion-bar">
        <div class="opinion-fill" style="width:${opinionToWidth(a.opinion)}%;background:${opinionColor(a.opinion)}"></div>
      </div>
    </div>
  `).join('');
}

function opinionToWidth(v) { return Math.round((parseFloat(v) + 1) / 2 * 100); }
function opinionColor(v) {
  const val = parseFloat(v);
  if (val > 0.3) return '#4ade80';
  if (val < -0.3) return '#f87171';
  return '#facc15';
}

loadAgents();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
