// Globale Hilfsfunktionen
function escHtml(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function opinionToWidth(v) {
  return Math.round((parseFloat(v) + 1) / 2 * 100);
}

function opinionColor(v) {
  const val = parseFloat(v);
  if (val >  0.3) return '#4ade80';
  if (val < -0.3) return '#f87171';
  return '#facc15';
}

function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// Escape-Taste schließt Modals
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
  }
});
