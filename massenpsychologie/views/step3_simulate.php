<?php
$projectId = (int)($_GET['id'] ?? 0);
$title = 'Schritt 3: Simulation – Massenpsychologie-Simulator';
?>
<?php include __DIR__ . '/partials/header.php'; ?>

<div class="workflow-steps">
  <span class="step done">1 Ereignis</span><span class="step-sep">›</span>
  <span class="step done">2 Agenten</span><span class="step-sep">›</span>
  <span class="step active">3 Simulation</span><span class="step-sep">›</span>
  <span class="step">4 Bericht</span>
</div>

<div class="section">
  <h2>Schritt 3: Massendynamik simulieren</h2>
  <p class="hint">Der <code>mass_dynamics</code>-Algorithmus führt Runden aus: Agenten beobachten einander, passen Meinungen an, bilden Gruppen und verstärken Emotionen.</p>

  <div class="card">
    <h3>Simulations-Parameter</h3>
    <label>Anzahl Runden (1–20)</label>
    <input type="number" id="num-rounds" value="10" min="1" max="20" class="input input-sm">
    <div class="card-actions">
      <a href="?page=step2&id=<?= $projectId ?>" class="btn btn-secondary">← Zurück</a>
      <button class="btn btn-primary" id="btn-sim" onclick="startSimulation()">▶ Simulation starten</button>
    </div>
    <div id="sim-status" class="status-msg" style="display:none"></div>
  </div>

  <div id="results-section" style="display:none">
    <h3>Gruppen nach Simulation</h3>
    <div id="groups-result" class="groups-row"></div>

    <h3>Netzwerkgraph</h3>
    <div id="graph-container" style="width:100%;height:420px;background:#111;border-radius:8px;overflow:hidden"></div>

    <h3>Rundenverlauf (Meinungsentwicklung)</h3>
    <div id="timeline" class="timeline"></div>

    <div class="card-actions" style="margin-top:1.5rem">
      <button class="btn btn-primary" onclick="location.href='?page=step4&id=<?= $projectId ?>'">
        Weiter: Bericht generieren →
      </button>
    </div>
  </div>
</div>

<script src="/massenpsychologie/assets/js/simulation.js"></script>
<script src="/massenpsychologie/assets/js/graph.js"></script>
<script>
const PROJECT_ID = <?= $projectId ?>;
const API = '/massenpsychologie/api';

async function startSimulation() {
  const rounds = parseInt(document.getElementById('num-rounds').value) || 10;
  const btn    = document.getElementById('btn-sim');
  const msg    = document.getElementById('sim-status');

  btn.disabled = true;
  btn.textContent = '⏳ Simuliere…';
  msg.style.display = 'block';
  msg.textContent = `Führe ${rounds} Runden aus (mass_dynamics-Algorithmus)…`;

  try {
    const res  = await fetch(`${API}/simulate.php?action=start`, {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ project_id: PROJECT_ID, rounds })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    msg.textContent = `✓ ${data.rounds} Runden abgeschlossen!`;
    msg.classList.add('success');
    loadResults();
  } catch(e) {
    msg.textContent = 'Fehler: ' + e.message;
    msg.classList.add('error');
  } finally {
    btn.disabled = false;
    btn.textContent = '▶ Erneut simulieren';
  }
}

async function loadResults() {
  const res  = await fetch(`${API}/simulate.php?action=status&project_id=${PROJECT_ID}`);
  const data = await res.json();
  if (!data.success || !data.simulation) return;

  document.getElementById('results-section').style.display = 'block';
  renderGroups(data.groups);
  renderGraph(data.agents, data.groups);
  renderTimeline(data.logs);
}

function renderGroups(groups) {
  document.getElementById('groups-result').innerHTML = groups.map(g => `
    <div class="group-card ${g.escalated ? 'escalated' : ''}">
      <div class="group-name">${g.name} ${g.escalated ? '🔥 ESKALIERT' : '✓ Stabil'}</div>
      <div class="group-stat">${g.size} Mitglieder</div>
      <div>Ø Meinung: <strong>${parseFloat(g.average_opinion).toFixed(3)}</strong></div>
      <div>Normextremität: <strong>${parseFloat(g.norm_extremity).toFixed(3)}</strong></div>
      <div class="opinion-bar">
        <div class="opinion-fill" style="width:${opinionToWidth(g.average_opinion)}%;background:${opinionColor(g.average_opinion)}"></div>
      </div>
    </div>
  `).join('');
}

function renderTimeline(logs) {
  const rounds = {};
  logs.filter(l => l.action === 'opinion_update').forEach(l => {
    if (!rounds[l.round]) rounds[l.round] = [];
    rounds[l.round].push(parseFloat(l.new_opinion));
  });

  const tl = document.getElementById('timeline');
  tl.innerHTML = Object.entries(rounds).map(([round, opinions]) => {
    const avg = opinions.reduce((a, b) => a + b, 0) / opinions.length;
    const escalations = logs.filter(l => l.round == round && l.action === 'escalate').length;
    return `
      <div class="tl-row">
        <span class="tl-round">Runde ${round}</span>
        <div class="tl-bar-wrap">
          <div class="tl-bar" style="width:${opinionToWidth(avg)}%;background:${opinionColor(avg)}"></div>
        </div>
        <span class="tl-val">${avg.toFixed(3)}</span>
        ${escalations ? `<span class="badge badge-red">🔥 ${escalations} Eskal.</span>` : ''}
      </div>
    `;
  }).join('');
}

function opinionToWidth(v) { return Math.round((parseFloat(v) + 1) / 2 * 100); }
function opinionColor(v) {
  const val = parseFloat(v);
  if (val > 0.3) return '#4ade80';
  if (val < -0.3) return '#f87171';
  return '#facc15';
}

loadResults();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
