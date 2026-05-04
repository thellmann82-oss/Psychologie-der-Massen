// Simulation-spezifische Hilfsfunktionen
function opinionLabel(v) {
  if (v > 0.6)  return 'stark dafür';
  if (v > 0.2)  return 'dafür';
  if (v > -0.2) return 'neutral';
  if (v > -0.6) return 'dagegen';
  return 'stark dagegen';
}

function buildOpinionHistogram(agents) {
  const buckets = Array(10).fill(0);
  agents.forEach(a => {
    const idx = Math.min(9, Math.floor((parseFloat(a.opinion) + 1) / 2 * 10));
    buckets[idx]++;
  });
  return buckets;
}
