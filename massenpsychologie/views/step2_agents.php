<?php
$projectId = (int)($_GET['id'] ?? 0);
$title = 'Schritt 2: Agenten – Massenpsychologie-Simulator';
?>
<?php include __DIR__ . '/partials/header.php'; ?>

<div class="stepper animate-in">
  <div class="stepper-item done"><div class="stepper-circle">&#10003;</div><div class="stepper-label">Ereignis</div></div>
  <div class="stepper-line"></div>
  <div class="stepper-item active"><div class="stepper-circle">2</div><div class="stepper-label">Agenten</div></div>
  <div class="stepper-line"></div>
  <div class="stepper-item"><div class="stepper-circle">3</div><div class="stepper-label">Simulation</div></div>
  <div class="stepper-line"></div>
  <div class="stepper-item"><div class="stepper-circle">4</div><div class="stepper-label">Bericht</div></div>
</div>

<div class="animate-in animate-in-delay-1">
  <div class="section-title" style="margin-bottom:.3rem">Schritt 2 &mdash; Agenten generieren</div>
  <div class="section-desc">Das LLM erzeugt realistische Personas mit Persönlichkeitstyp, Anfangsmeinung, Emotionslevel und Anonymitätsstatus &mdash; basierend auf dem Ereignis.</div>

  <div class="card">
    <div class="card-title"><div class="card-title-icon">&#9889;</div>KI-Generierung</div>
    <div style="display:flex;align-items:flex-end;gap:1.5rem;flex-wrap:wrap">
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Anzahl Agenten</label>
        <input type="number" id="num-agents" value="20" min="5" max="50" class="input input-sm">
        <div class="form-hint">5 – 50 Agenten</div>
      </div>
      <div class="card-actions" style="margin-top:0;padding-top:.15rem">
        <a href="?page=step1&id=<?= $projectId ?>" class="btn btn-secondary">&larr; Zurück</a>
        <button class="btn btn-primary" id="btn-generate" onclick="generateAgents()">
          &#9889; Agenten per KI generieren
        </button>
      </div>
    </div>
    <div id="gen-status" class="status-msg" style="display:none"><span></span></div>
  </div>
</div>

<div id="agents-section" style="display:none" class="animate-in">

  <div class="section-header">
    <h2>Gruppen (Anfangszustand)</h2>
  </div>
  <div id="groups-row" class="groups-row"></div>

  <div class="section-header">
    <h2>Alle Agenten</h2>
    <span id="agent-count" class="badge badge-grey"></span>
  </div>
  <div id="agents-grid" class="agents-grid"></div>

  <div class="card-actions">
    <button class="btn btn-primary" onclick="location.href='?page=step3&id=<?= $projectId ?>'">
      Weiter: Simulation starten &rarr;
    </button>
  </div>
</div>

<script>
const PROJECT_ID = <?= $projectId ?>;
const API = '/massenpsychologie/api';

const AVATAR_CLASS = {
  'agitator':   'avatar-agitator',
  'extremist':  'avatar-extremist',
  'mitläufer':  'avatar-mitläufer',
  'zweifler':   'avatar-zweifler',
  'beobachter': 'avatar-beobachter',
  'vermittler': 'avatar-vermittler',
};

function avatarClass(type) {
  return AVATAR_CLASS[(type||'').toLowerCase()] || 'avatar-default';
}
function initials(name) {
  return (name||'?').split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
}

async function generateAgents() {
  const n   = parseInt(document.getElementById('num-agents').value) || 20;
  const btn = document.getElementById('btn-generate');
  const msg = document.getElementById('gen-status');
  btn.disabled = true;
  btn.textContent = '⏳ Generiere…';
  msg.style.display = 'flex';
  msg.className = 'status-msg';
  msg.querySelector('span').textContent = `Das LLM erstellt ${n} Agenten-Profile. Bitte warten (20–60 Sek.)…`;

  try {
    const res  = await fetch(`${API}/agents.php?action=generate`, {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ project_id: PROJECT_ID, num_agents: n })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    msg.className = 'status-msg success';
    msg.querySelector('span').textContent = `${data.count} Agenten erfolgreich generiert!`;
    loadAgents();
  } catch(e) {
    msg.className = 'status-msg error';
    msg.querySelector('span').textContent = 'Fehler: ' + e.message;
  } finally {
    btn.disabled = false;
    btn.textContent = '⚡ Neu generieren';
  }
}

async function loadAgents() {
  const res  = await fetch(`${API}/agents.php?action=list&project_id=${PROJECT_ID}`);
  const data = await res.json();
  if (!data.success || !data.agents || !data.agents.length) return;

  document.getElementById('agents-section').style.display = 'block';
  document.getElementById('agent-count').textContent = data.agents.length + ' Agenten';

  // Gruppen
  document.getElementById('groups-row').innerHTML = data.groups.map(g => {
    const cls = g.name.toLowerCase().includes('pro') ? 'group-pro'
              : g.name.toLowerCase().includes('kontra') ? 'group-kontra' : 'group-neutral';
    return `
    <div class="group-card ${cls}">
      <div class="group-name">
        <span>${escHtml(g.name)}</span>
        <span class="badge ${cls==='group-pro'?'badge-green':cls==='group-kontra'?'badge-red':'badge-yellow'}">${g.size} Mitglieder</span>
      </div>
      <div class="group-stats-row">
        <div class="group-stat-box">
          <div class="group-stat-val" style="color:${opinionColor(g.average_opinion)}">${parseFloat(g.average_opinion).toFixed(2)}</div>
          <span class="group-stat-label">Ø MEINUNG</span>
        </div>
        <div class="group-stat-box">
          <div class="group-stat-val">${parseFloat(g.norm_extremity).toFixed(2)}</div>
          <span class="group-stat-label">EXTREMITÄT</span>
        </div>
      </div>
      <div class="opinion-track">
        <div class="opinion-center-mark"></div>
        <div class="opinion-fill" style="width:${opinionToWidth(g.average_opinion)}%;background:${opinionColor(g.average_opinion)}"></div>
      </div>
    </div>`;
  }).join('');

  // Agenten
  document.getElementById('agents-grid').innerHTML = data.agents.map(a => `
    <div class="agent-card">
      <div class="agent-header">
        <div class="agent-avatar ${avatarClass(a.personality_type)}">${initials(a.name)}</div>
        <div class="agent-header-info">
          <div class="agent-name">${escHtml(a.name)}</div>
          <span class="badge badge-grey" style="font-size:.68rem">${escHtml(a.personality_type)}</span>
          ${a.anonymity ? '<span class="anon-tag" style="margin-left:.25rem">&#128373; anonym</span>' : ''}
        </div>
      </div>
      <div class="agent-bio">${escHtml(a.bio)}</div>
      <div class="agent-stats">
        <div class="agent-stat">
          <span class="agent-stat-label">MEINUNG</span>
          <span class="agent-stat-val" style="color:${opinionColor(a.opinion)}">${parseFloat(a.opinion).toFixed(2)}</span>
        </div>
        <div class="agent-stat">
          <span class="agent-stat-label">EMOTION</span>
          <span class="agent-stat-val">${parseFloat(a.emotional_state).toFixed(2)}</span>
        </div>
        <div class="agent-stat">
          <span class="agent-stat-label">HEMMUNG</span>
          <span class="agent-stat-val">${parseFloat(a.inhibition_level).toFixed(2)}</span>
        </div>
      </div>
      <div class="opinion-track">
        <div class="opinion-center-mark"></div>
        <div class="opinion-fill" style="width:${opinionToWidth(a.opinion)}%;background:${opinionColor(a.opinion)}"></div>
      </div>
    </div>
  `).join('');
}

function opinionToWidth(v) { return Math.round((parseFloat(v) + 1) / 2 * 100); }
function opinionColor(v) {
  const val = parseFloat(v);
  if (val > 0.3)  return 'var(--success)';
  if (val < -0.3) return 'var(--danger)';
  return 'var(--warn)';
}

loadAgents();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
