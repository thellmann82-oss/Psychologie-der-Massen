<?php
$projectId = (int)($_GET['id'] ?? 0);
$title = 'Schritt 1: Ereignis – Massenpsychologie-Simulator';
?>
<?php include __DIR__ . '/partials/header.php'; ?>

<!-- Stepper -->
<div class="stepper animate-in">
  <div class="stepper-item active">
    <div class="stepper-circle">1</div>
    <div class="stepper-label">Ereignis</div>
  </div>
  <div class="stepper-line"></div>
  <div class="stepper-item">
    <div class="stepper-circle">2</div>
    <div class="stepper-label">Agenten</div>
  </div>
  <div class="stepper-line"></div>
  <div class="stepper-item">
    <div class="stepper-circle">3</div>
    <div class="stepper-label">Simulation</div>
  </div>
  <div class="stepper-line"></div>
  <div class="stepper-item">
    <div class="stepper-circle">4</div>
    <div class="stepper-label">Bericht</div>
  </div>
</div>

<div class="animate-in animate-in-delay-1">
  <div class="page-header-left" style="margin-bottom:1.5rem">
    <div class="section-title">Schritt 1 &mdash; Ereignis &amp; Szenario</div>
    <div class="section-desc">Das Ereignis ist der Auslöser der Massendynamik. Es definiert den Kontext, in dem KI-Agenten ihre Meinungen, Emotionen und Gruppenidentitäten entwickeln.</div>
  </div>

  <div id="project-info" class="info-box">
    <span class="info-box-icon">&#9432;</span>
    <span>Lade Projektdaten…</span>
  </div>

  <div class="card">
    <div class="card-title">
      <div class="card-title-icon">&#128196;</div>
      Ereignisbeschreibung (Stimulus)
    </div>
    <textarea id="event-text" class="input" rows="6" readonly
      placeholder="Das Ereignis wird nach dem Laden angezeigt…"></textarea>
    <div class="card-body" style="margin-top:.25rem">
      Dieser Text wird dem LLM übergeben, um realistische Agenten-Profile und Meinungen zu generieren.
    </div>
    <div class="card-actions">
      <a href="?page=home" class="btn btn-secondary">&larr; Zur Projektliste</a>
      <button class="btn btn-primary" onclick="goToStep2()">Weiter: Agenten generieren &rarr;</button>
    </div>
  </div>
</div>

<script>
const PROJECT_ID = <?= $projectId ?>;
const API = '/massenpsychologie/api';

async function loadProject() {
  const res  = await fetch(`${API}/project.php?action=get&id=${PROJECT_ID}`);
  const data = await res.json();
  if (!data.success) {
    document.getElementById('project-info').innerHTML = '<span class="info-box-icon">&#9888;</span><span style="color:var(--danger)">Projekt nicht gefunden.</span>';
    return;
  }
  const p = data.project;
  const badgeClass = { created:'badge-grey', agents_ready:'badge-blue', simulating:'badge-yellow', done:'badge-green' };
  const statusLabel = { created:'Erstellt', agents_ready:'Agenten bereit', simulating:'Läuft', done:'Abgeschlossen' };
  document.getElementById('project-info').innerHTML =
    `<span class="info-box-icon">&#128193;</span>
     <span><strong>${escHtml(p.name)}</strong> &nbsp;<span class="badge ${badgeClass[p.status]||'badge-grey'}">${statusLabel[p.status]||p.status}</span>
     &nbsp;&middot;&nbsp; ${p.agent_count} Agenten &middot;&nbsp;Erstellt: ${p.created_at.split(' ')[0]}</span>`;
  document.getElementById('event-text').value = p.event_description;
}

function goToStep2() { location.href = `?page=step2&id=${PROJECT_ID}`; }

loadProject();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
