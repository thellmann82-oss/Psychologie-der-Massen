function renderGraph(agents, groups) {
  const container = document.getElementById('graph-container');
  if (!container) return;
  container.innerHTML = '';

  const width  = container.offsetWidth  || 700;
  const height = container.offsetHeight || 420;

  const svg = d3.select('#graph-container')
    .append('svg')
    .attr('width', width)
    .attr('height', height);

  const groupColor = {
    0: '#4ade80',
    1: '#f87171',
    2: '#facc15',
    3: '#818cf8',
    4: '#fb923c',
  };

  // Gruppen als Hintergrundkreise
  const groupCenters = {};
  groups.forEach((g, i) => {
    const angle  = (i / groups.length) * 2 * Math.PI - Math.PI / 2;
    const radius = Math.min(width, height) * 0.3;
    groupCenters[g.id] = {
      x: width / 2 + radius * Math.cos(angle),
      y: height / 2 + radius * Math.sin(angle),
      color: groupColor[i % Object.keys(groupColor).length],
      name: g.name,
    };
  });

  // Hintergrundkreise für Gruppen
  Object.values(groupCenters).forEach(gc => {
    svg.append('circle')
      .attr('cx', gc.x).attr('cy', gc.y)
      .attr('r', 80)
      .attr('fill', gc.color)
      .attr('opacity', 0.08);
    svg.append('text')
      .attr('x', gc.x).attr('y', gc.y - 90)
      .attr('text-anchor', 'middle')
      .attr('fill', gc.color)
      .attr('font-size', '12px')
      .text(gc.name);
  });

  // Agenten-Nodes
  const nodes = agents.map(a => {
    const gc = groupCenters[a.group_id] || { x: width / 2, y: height / 2, color: '#888' };
    const jitter = 60;
    return {
      id: a.id,
      name: a.name,
      opinion: parseFloat(a.opinion),
      emotional: parseFloat(a.emotional_state),
      anonymity: a.anonymity,
      group_id: a.group_id,
      color: gc.color,
      x: gc.x + (Math.random() - 0.5) * jitter,
      y: gc.y + (Math.random() - 0.5) * jitter,
    };
  });

  const simulation = d3.forceSimulation(nodes)
    .force('charge', d3.forceManyBody().strength(-30))
    .force('collision', d3.forceCollide(10))
    .force('x', d3.forceX(d => groupCenters[d.group_id]?.x || width / 2).strength(0.3))
    .force('y', d3.forceY(d => groupCenters[d.group_id]?.y || height / 2).strength(0.3))
    .stop();

  for (let i = 0; i < 120; i++) simulation.tick();

  const node = svg.selectAll('.node').data(nodes).enter()
    .append('g').attr('class', 'node')
    .attr('transform', d => `translate(${d.x},${d.y})`);

  node.append('circle')
    .attr('r', d => 4 + d.emotional * 6)
    .attr('fill', d => d.color)
    .attr('opacity', 0.85)
    .attr('stroke', d => d.anonymity ? '#fff' : 'none')
    .attr('stroke-width', 1.5);

  node.append('title')
    .text(d => `${d.name}\nMeinung: ${d.opinion.toFixed(2)}\nEmotion: ${d.emotional.toFixed(2)}${d.anonymity ? '\n[anonym]' : ''}`);
}
