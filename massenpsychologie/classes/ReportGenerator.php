<?php
class ReportGenerator {

    private LLMClient $llm;

    public function __construct() {
        $this->llm = new LLMClient();
    }

    public function generate(int $projectId): string {
        $db = Database::get();

        $project = $db->prepare('SELECT * FROM projects WHERE id = ?');
        $project->execute([$projectId]);
        $project = $project->fetch();

        $agents = $db->prepare('SELECT * FROM agents WHERE project_id = ?');
        $agents->execute([$projectId]);
        $agents = $agents->fetchAll();

        $groups = $db->prepare('SELECT * FROM `groups` WHERE project_id = ?');
        $groups->execute([$projectId]);
        $groups = $groups->fetchAll();

        $sim = $db->prepare('SELECT * FROM simulations WHERE project_id = ? ORDER BY round_number DESC LIMIT 1');
        $sim->execute([$projectId]);
        $lastSim = $sim->fetch();

        $logs = [];
        if ($lastSim) {
            $logStmt = $db->prepare(
                'SELECT al.*, a.name as agent_name, g.name as group_name
                 FROM agent_logs al
                 LEFT JOIN agents a ON al.agent_id = a.id
                 LEFT JOIN `groups` g ON al.group_id = g.id
                 WHERE al.simulation_id = ?
                 ORDER BY al.round, al.id'
            );
            $logStmt->execute([$lastSim['id']]);
            $logs = $logStmt->fetchAll();
        }

        // Zusammenfassung für den LLM aufbereiten
        $summary = $this->buildSummary($project, $agents, $groups, $logs);

        $system = <<<PROMPT
Du bist ein Sozialwissenschaftler, der Massenpsychologie-Simulationen analysiert.
Deine Analyse basiert auf Le Bons "Psychologie der Massen" und dem mass_dynamics-Algorithmus.
Schreibe den Bericht auf Deutsch in Markdown-Format.
PROMPT;

        $user = <<<PROMPT
Analysiere folgende Simulation und erstelle einen strukturierten Bericht:

$summary

Struktur:
1. ## Zusammenfassung des Ereignisses
2. ## Gruppenbildung und Identitätsdynamik
3. ## Meinungsentwicklung und Konformitätsdruck
4. ## Emotionsverstärkung und Hemmungsreduktion
5. ## Normbildung: Eskalation oder Stabilisierung?
6. ## Gesamtausgang und Prognose
7. ## Erkenntnisse aus Le Bons Perspektive
PROMPT;

        return $this->llm->chat($system, $user, 0.5);
    }

    private function buildSummary(array $project, array $agents, array $groups, array $logs): string {
        $escalated = array_filter($groups, fn($g) => $g['escalated']);
        $avgOpinion = count($agents) > 0
            ? array_sum(array_column($agents, 'opinion')) / count($agents)
            : 0;

        $roundCount = count(array_unique(array_column($logs, 'round')));
        $escalations = count(array_filter($logs, fn($l) => $l['action'] === 'escalate'));

        $groupSummary = '';
        foreach ($groups as $g) {
            $groupSummary .= sprintf(
                "- %s: %d Mitglieder, Durchschnittsmeinung %.2f, Normextremität %.2f, %s\n",
                $g['name'], $g['size'], $g['average_opinion'], $g['norm_extremity'],
                $g['escalated'] ? 'ESKALIERT' : 'stabil'
            );
        }

        $escalatedCount = count($escalated);
        return <<<TEXT
**Projekt:** {$project['name']}
**Ereignis:** {$project['event_description']}
**Agenten:** {$project['agent_count']} (davon {$project['anonymous_count']} anonym)
**Simulationsrunden:** $roundCount
**Gruppen:**
$groupSummary
**Durchschnittliche Endmeinung:** $avgOpinion
**Eskalationsereignisse:** $escalations
**Eskalierte Gruppen:** $escalatedCount
TEXT;
    }
}
