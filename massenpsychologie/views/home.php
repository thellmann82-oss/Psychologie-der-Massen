<?php $title = 'Projekte – Massenpsychologie-Simulator'; ?>
<?php include __DIR__ . '/partials/header.php'; ?>

<!-- Hero -->
<div class="hero animate-in">
  <h1>Massen&shy;psychologie<br>simulieren</h1>
  <p>Erstelle ein gesellschaftliches Ereignis, generiere KI-Agenten mit individuellen Persönlichkeiten und beobachte, wie der <code>mass_dynamics</code>-Algorithmus Gruppendynamiken, Konformität und Eskalation modelliert.</p>
  <div class="hero-pills">
    <span class="badge badge-purple">Le Bon 1895</span>
    <span class="badge badge-cyan">KI-Agenten</span>
    <span class="badge badge-green">Multi-Gruppen</span>
    <span class="badge badge-yellow">Eskalation &amp; Stabilisierung</span>
  </div>
  <button class="btn btn-primary" style="font-size:1rem;padding:.7rem 1.6rem;" onclick="openModal('modal-new-project')">
    + Neues Simulationsprojekt
  </button>
</div>

<!-- Projektliste -->
<div class="section-header animate-in animate-in-delay-1">
  <h2>Meine Projekte</h2>
  <button class="btn btn-secondary btn-sm" onclick="loadProjects()">&#8635; Aktualisieren</button>
</div>

<div id="project-list" class="card-grid animate-in animate-in-delay-2">
  <div class="loading"><div class="spinner"></div><span>Lade Projekte…</span></div>
</div>

<!-- Modal: Neues Projekt -->
<div id="modal-new-project" class="modal-overlay" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <h2>Neues Simulationsprojekt</h2>
      <button class="modal-close" onclick="closeModal('modal-new-project')">&#10005;</button>
    </div>
    <div class="form-group">
      <label class="form-label">Projektname <span style="color:var(--danger)">*</span></label>
      <input type="text" id="proj-name" placeholder="z.B. Protestwelle 2025" class="input">
    </div>
    <div class="form-group">
      <label class="form-label">Kurzbeschreibung <span style="color:var(--text-3)">(optional)</span></label>
      <input type="text" id="proj-desc" placeholder="Für eigene Notizen" class="input">
    </div>
    <div class="form-group">
      <label class="form-label">
        Ereignis / Stimulus <span style="color:var(--danger)">*</span>
        <small> &mdash; Was löst die Massendynamik aus?</small>
      </label>
      <textarea id="proj-event" rows="4" class="input"
        placeholder="z.B. Eine kontroverse Rentenreform wird bekannt gegeben. Hunderttausende fühlen sich betrogen, während andere die Notwendigkeit sehen. Die Medien heizen die Debatte weiter an…"></textarea>
      <div class="form-hint">Je konkreter das Ereignis, desto realistischer die KI-Agenten.</div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closeModal('modal-new-project')">Abbrechen</button>
      <button class="btn btn-primary" onclick="createProject()">Projekt erstellen &rarr;</button>
    </div>
  </div>
</div>

<script>
const API = '/massenpsychologie/api';

async function loadProjects() {
  const list = document.getElementById('project-list');
  list.innerHTML = '<div class="loading"><div class="spinner"></div><span>Lade Projekte…</span></div>';

  const res  = await fetch(`${API}/project.php?action=list`);
  const data = await res.json();

  if (!data.projects || data.projects.length === 0) {
    list.innerHTML = `
      <div class="empty-state" style="grid-column:1/-1">
        <div class="empty-state-icon">&#128202;</div>
        <h3>Noch keine Projekte</h3>
        <p>Starte deine erste Massendynamik-Simulation, indem du oben ein neues Projekt erstellst.</p>
      </div>`;
    return;
  }

  const statusLabel = { created:'Erstellt', agents_ready:'Agenten bereit', simulating:'Läuft', done:'Abgeschlossen' };
  const statusBadge = { created:'badge-grey', agents_ready:'badge-blue', simulating:'badge-yellow', done:'badge-green' };

  list.innerHTML = data.projects.map((p, i) => `
    <div class="card card-project animate-in" style="animation-delay:${i*0.04}s"
         onclick="location.href='?page=step1&id=${p.id}'">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.6rem">
        <div class="card-project-name">${escHtml(p.name)}</div>
        <span class="badge ${statusBadge[p.status] || 'badge-grey'}">${statusLabel[p.status] || p.status}</span>
      </div>
      <div class="card-project-event">${escHtml(p.event_description)}</div>
      <div class="card-project-meta">
        <span>&#128101; ${p.agent_count} Agenten</span>
        <span class="sep">&middot;</span>
        <span>&#128197; ${p.created_at.split(' ')[0]}</span>
        <span style="flex:1"></span>
        <button class="btn btn-danger btn-sm" onclick="event.stopPropagation();deleteProject(${p.id})">Löschen</button>
      </div>
    </div>
  `).join('');
}

async function createProject() {
  const name  = document.getElementById('proj-name').value.trim();
  const desc  = document.getElementById('proj-desc').value.trim();
  const event = document.getElementById('proj-event').value.trim();
  if (!name || !event) { alert('Projektname und Ereignis sind Pflichtfelder.'); return; }

  const res  = await fetch(`${API}/project.php?action=create`, {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ name, description: desc, event_description: event })
  });
  const data = await res.json();
  if (data.success) { closeModal('modal-new-project'); location.href = `?page=step1&id=${data.id}`; }
  else alert('Fehler: ' + data.error);
}

async function deleteProject(id) {
  if (!confirm('Projekt und alle zugehörigen Daten unwiderruflich löschen?')) return;
  await fetch(`${API}/project.php?action=delete&id=${id}`, { method: 'DELETE' });
  loadProjects();
}

loadProjects();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
