<?php $title = 'Projekte – Massenpsychologie-Simulator'; ?>
<?php include __DIR__ . '/partials/header.php'; ?>

<div class="page-header">
  <h1>Meine Simulationsprojekte</h1>
  <button class="btn btn-primary" onclick="openModal('modal-new-project')">+ Neues Projekt</button>
</div>

<div id="project-list" class="card-grid">
  <div class="loading">Lade Projekte…</div>
</div>

<!-- Modal: Neues Projekt -->
<div id="modal-new-project" class="modal-overlay" style="display:none">
  <div class="modal">
    <h2>Neues Simulationsprojekt</h2>
    <label>Projektname *</label>
    <input type="text" id="proj-name" placeholder="z.B. Protestwelle 2025" class="input">
    <label>Beschreibung</label>
    <input type="text" id="proj-desc" placeholder="Optional" class="input">
    <label>Ereignis / Stimulus * <small>(Was löst die Massendynamik aus?)</small></label>
    <textarea id="proj-event" rows="4" class="input"
      placeholder="z.B. Eine kontroverse Gesetzesreform wird bekannt gegeben. Die Bevölkerung ist gespalten…"></textarea>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closeModal('modal-new-project')">Abbrechen</button>
      <button class="btn btn-primary" onclick="createProject()">Erstellen</button>
    </div>
  </div>
</div>

<script>
const API = '/massenpsychologie/api';

async function loadProjects() {
  const res  = await fetch(`${API}/project.php?action=list`);
  const data = await res.json();
  const list = document.getElementById('project-list');

  if (!data.projects || data.projects.length === 0) {
    list.innerHTML = '<p class="empty">Noch keine Projekte. Erstelle dein erstes!</p>';
    return;
  }

  const statusLabel = { created: 'Erstellt', agents_ready: 'Agenten bereit', simulating: 'Läuft', done: 'Abgeschlossen' };
  const statusClass = { created: 'badge-grey', agents_ready: 'badge-blue', simulating: 'badge-yellow', done: 'badge-green' };

  list.innerHTML = data.projects.map(p => `
    <div class="card" onclick="location.href='?page=step1&id=${p.id}'">
      <div class="card-header">
        <h3>${escHtml(p.name)}</h3>
        <span class="badge ${statusClass[p.status] || 'badge-grey'}">${statusLabel[p.status] || p.status}</span>
      </div>
      <p class="card-event">${escHtml(p.event_description.substring(0, 120))}…</p>
      <div class="card-meta">
        <span>${p.agent_count} Agenten</span>
        <span>${p.created_at}</span>
        <button class="btn btn-danger btn-sm" onclick="event.stopPropagation();deleteProject(${p.id})">Löschen</button>
      </div>
    </div>
  `).join('');
}

async function createProject() {
  const name  = document.getElementById('proj-name').value.trim();
  const desc  = document.getElementById('proj-desc').value.trim();
  const event = document.getElementById('proj-event').value.trim();
  if (!name || !event) { alert('Name und Ereignis sind Pflichtfelder.'); return; }

  const res  = await fetch(`${API}/project.php?action=create`, {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ name, description: desc, event_description: event })
  });
  const data = await res.json();
  if (data.success) { closeModal('modal-new-project'); location.href = `?page=step1&id=${data.id}`; }
  else alert('Fehler: ' + data.error);
}

async function deleteProject(id) {
  if (!confirm('Projekt und alle Daten löschen?')) return;
  await fetch(`${API}/project.php?action=delete&id=${id}`, { method: 'DELETE' });
  loadProjects();
}

function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function escHtml(s)     { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

loadProjects();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
