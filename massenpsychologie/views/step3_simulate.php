<?php
$projectId = (int)($_GET['id'] ?? 0);
$title = 'Schritt 3: Simulation – Massenpsychologie-Simulator';
?>
<?php include __DIR__ . '/partials/header.php'; ?>

<div class="stepper animate-in">
  <div class="stepper-item done"><div class="stepper-circle">&#10003;</div><div class="stepper-label">Ereignis</div></div>
  <div class="stepper-line"></div>
  <div class="stepper-item done"><div class="stepper-circle">&#10003;</div><div class="stepper-label">Agenten</div></div>
  <div class="stepper-line"></div>
  <div class="stepper-item active"><div class="stepper-circle">3</div><div class="stepper-label">Simulation</div></div>
  <div class="stepper-line"></div>
  <div class="stepper-item"><div class="stepper-circle">4</div><div class="stepper-label">Bericht</div></div>
</div>

<div class="animate-in animate-in-delay-1">
  <div class="section-title" style="margin-bottom:.3rem">Schritt 3 &mdash; Massendynamik simulieren</div>
  <div class="section-desc">Der <code>mass_dynamics</code>-Algorithmus läuft Runde für Runde: Agenten beobachten einander, passen Meinungen an, verstärken Emotionen und bilden oder wechseln Gruppen.</div>

  <!-- Algorithmus-Übersicht -->
  <div class="algo-box">
    <span class="kw">for</span> <span class="fn">person</span> in individuals: &nbsp;<span class="cmt">// jeder Agent</span><br>
    &nbsp;&nbsp;&nbsp;&nbsp;<span class="fn">observe_others</span>(person) &rarr; <span class="str">Nachbarschafts-Meinung</span><br>
    &nbsp;&nbsp;&nbsp;&nbsp;<span class="fn">assign_identity</span>(person) &rarr; <span class="str">Gruppe</span><br>
    &nbsp;&nbsp;&nbsp;&nbsp;<span class="fn">conform_to</span>(group) &nbsp;&nbsp;&nbsp;&rarr; <span class="str">Meinungsanpassung +30%</span><br>
    &nbsp;&nbsp;&nbsp;&nbsp;<span class="fn">amplify_emotions</span>(group) &rarr; <span class="str">×1.1</span><br>
    &nbsp;&nbsp;&nbsp;&nbsp;<span class="kw">if</span> anonymity: <span class="fn">reduce_inhibition</span>(person) &minus;0.15<br>
    <span class="fn">emerge</span>(norms) &rarr; <span class="kw">if</span> |norm| &ge; 0.7: <span class="fn">escalate</span>() <span class="kw">else</span> <span class="fn">stabilize</span>()
  </div>

  <div class="card">
    <div class="card-title"><div class="card-title-icon">&#9654;</div>Simulations-Parameter</div>
    <div style="display:flex;align-items:flex-end;gap:1.5rem;flex-wrap:wrap">
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Anzahl Runden</label>
        <input type="number" id="num-rounds" value="10" min="1" max="20" class="input input-sm">
        <div class="form-hint">1 – 20 Runden</div>
      </div>
      <div class="card-actions" style="margin-top:0;padding-top:.15rem">
        <a href="?page=step2&id=<?= $projectId ?>" class="btn btn-secondary">&larr; Zurück</a>
        <button class="btn btn-primary" id="btn-sim" onclick="startSimulation()">&#9654; Simulation starten</button>
      </div>
    </div>
    <div id="sim-status" class="status-msg" style="display:none"><span></span></div>
  </div>
</div>

<div id="results-section" style="display:none" class="animate-in">

  <div class="section-header"><h2>Gruppen nach Simulation</h2></div>
  <div id="groups-result" class="groups-row"></div>

  <div class="graph-wrap">
    <div class="graph-header">
      <span>&#128202; Agenten-Netzwerkgraph &mdash; Kreisgrö&szlig;e = Emotionslevel &middot; Wei&szlig;er Rand = anonym</span>
      <span id="graph-legend" style="font-size:.75rem;color:var(--text-3)"></span>
    </div>
    <div id="graph-container"></div>
  </div>

  <div class="section-header"><h2>Meinungsentwicklung pro Runde</h2></div>
  <div class="card" style="padding:1rem 1.25rem">
    <div id="timeline" class="timeline"></div>
  </div>

  <div class="card-actions" style="margin-top:1.75rem">
    <button class="btn btn-primary" onclick="location.href='?page=step4&id=<?= $projectId ?>'">
      Weiter: Bericht generieren &rarr;
    </button>
  </div>
</div>

<script>
const PROJECT_ID = <?= $projectId ?>;
const API = '/massenpsychologie/api';

async function startSimulation() {
  const rounds = parseInt(document.getElementById('num-rounds').value) || 10;
  const btn    = document.getElementById('btn-sim');
  const msg    = document.getElementById('sim-status');

  btn.disabled = true;
  btn.textContent = '⏳ Simuliere…';
  msg.style.display = 'flex';
  msg.className = 'status-msg';
  msg.querySelector('span').textContent = `Führe ${rounds} Runden mass_dynamics aus…`;

  try {
    const res  = await fetch(`${API}/simulate.php?action=start`, {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ project_id: PROJECT_ID, rounds })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    msg.className = 'status-msg success';
    msg.querySelector('span').textContent = `${data.rounds} Runden abgeschlossen!`;
    loadResults();
  } catch(e) {
    msg.className = 'status-msg error';
    msg.querySelector('span').textContent = 'Fehler: ' + e.message;
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
  renderGroups(data.groups   || []);
  renderGraph(data.agents    || [], data.groups || []);
  renderTimeline(data.logs   || [], data.groups || []);
}

function renderGroups(groups) {
  document.getElementById('groups-result').innerHTML = groups.map(g => {
    const cls = g.name.toLowerCase().includes('pro') ? 'group-pro'
              : g.name.toLowerCase().includes('kontra') ? 'group-kontra' : 'group-neutral';
    const statusBadge = g.escalated
      ? '<span class="badge badge-red">&#128293; Eskaliert</span>'
      : '<span class="badge badge-green">&#10003; Stabil</span>';
    return `
    <div class="group-card ${cls} ${g.escalated ? 'escalated' : ''}">
      <div class="group-name">${escHtml(g.name)} ${statusBadge}</div>
      <div class="group-stats-row">
        <div class="group-stat-box">
          <div class="group-stat-val" style="color:${opinionColor(g.average_opinion)}">${parseFloat(g.average_opinion).toFixed(3)}</div>
          <span class="group-stat-label">Ø MEINUNG</span>
        </div>
        <div class="group-stat-box">
          <div class="group-stat-val">${parseFloat(g.norm_extremity).toFixed(3)}</div>
          <span class="group-stat-label">NORMEXTREMITÄT</span>
        </div>
        <div class="group-stat-box">
          <div class="group-stat-val">${g.size}</div>
          <span class="group-stat-label">MITGLIEDER</span>
        </div>
      </div>
      <div class="opinion-track">
        <div class="opinion-center-mark"></div>
        <div class="opinion-fill" style="width:${opinionToWidth(g.average_opinion)}%;background:${opinionColor(g.average_opinion)}"></div>
      </div>
    </div>`;
  }).join('');
}

function renderTimeline(logs, groups) {
  const tl = document.getElementById('timeline');
  if (!logs || !logs.length) {
    tl.innerHTML = '<p style="color:var(--text-3);font-size:.875rem;padding:.5rem 0">Noch keine Simulationsdaten vorhanden.</p>';
    return;
  }
  const rounds = {};
  logs.filter(l => l.action === 'opinion_update').forEach(l => {
    if (!rounds[l.round]) rounds[l.round] = [];
    rounds[l.round].push(parseFloat(l.new_opinion));
  });

  document.getElementById('timeline').innerHTML = Object.entries(rounds).map(([round, opinions]) => {
    const avg = opinions.reduce((a, b) => a + b, 0) / opinions.length;
    const escalations = logs.filter(l => l.round == round && l.action === 'escalate').length;
    return `
      <div class="tl-row">
        <span class="tl-round">Runde ${round}</span>
        <div class="tl-bar-wrap">
          <div class="tl-bar" style="width:${opinionToWidth(avg)}%;background:${opinionColor(avg)}"></div>
        </div>
        <span class="tl-val">${avg.toFixed(3)}</span>
        ${escalations ? `<span class="badge badge-red" style="font-size:.68rem">&#128293; ${escalations}x</span>` : '<span style="width:60px"></span>'}
      </div>`;
  }).join('');
}

function opinionToWidth(v) { return Math.round((parseFloat(v) + 1) / 2 * 100); }
function opinionColor(v) {
  const val = parseFloat(v);
  if (val > 0.3)  return 'var(--success)';
  if (val < -0.3) return 'var(--danger)';
  return 'var(--warn)';
}

loadResults();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
