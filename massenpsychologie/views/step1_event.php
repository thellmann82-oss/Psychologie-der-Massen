<?php
$projectId = (int)($_GET['id'] ?? 0);
$title = 'Schritt 1: Ereignis – Massenpsychologie-Simulator';
?>
<?php include __DIR__ . '/partials/header.php'; ?>

<div class="workflow-steps">
  <span class="step active">1 Ereignis</span>
  <span class="step-sep">›</span>
  <span class="step">2 Agenten</span>
  <span class="step-sep">›</span>
  <span class="step">3 Simulation</span>
  <span class="step-sep">›</span>
  <span class="step">4 Bericht</span>
</div>

<div class="section">
  <h2>Schritt 1: Ereignis &amp; Szenario</h2>
  <div id="project-info" class="info-box">Lade Projektdaten…</div>

  <div class="card">
    <h3>Ereignis (Stimulus)</h3>
    <p>Das Ereignis ist der Auslöser der Massendynamik. Es bestimmt, wie Agenten reagieren und welche Gruppen sich bilden.</p>
    <textarea id="event-text" class="input" rows="5" readonly></textarea>
    <div class="card-actions">
      <a href="?page=home" class="btn btn-secondary">← Zurück</a>
      <button class="btn btn-primary" onclick="goToStep2()">Weiter: Agenten generieren →</button>
    </div>
  </div>
</div>

<script>
const PROJECT_ID = <?= $projectId ?>;
const API = '/massenpsychologie/api';

async function loadProject() {
  const res  = await fetch(`${API}/project.php?action=get&id=${PROJECT_ID}`);
  const data = await res.json();
  if (!data.success) { document.getElementById('project-info').textContent = 'Fehler beim Laden.'; return; }
  const p = data.project;
  document.getElementById('project-info').innerHTML =
    `<strong>${p.name}</strong> &mdash; Status: <span class="badge badge-${p.status === 'done' ? 'green' : 'blue'}">${p.status}</span>
     &mdash; ${p.agent_count} Agenten`;
  document.getElementById('event-text').value = p.event_description;
}

function goToStep2() {
  location.href = `?page=step2&id=${PROJECT_ID}`;
}

loadProject();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
