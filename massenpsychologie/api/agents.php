<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Agent.php';
require_once __DIR__ . '/../classes/Group.php';
require_once __DIR__ . '/../classes/LLMClient.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::get();

    if ($method === 'GET' && $action === 'list') {
        $pid  = (int)($_GET['project_id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM agents WHERE project_id = ? ORDER BY id');
        $stmt->execute([$pid]);
        $agents = $stmt->fetchAll();

        $gstmt = $db->prepare('SELECT * FROM `groups` WHERE project_id = ?');
        $gstmt->execute([$pid]);
        $groups = $gstmt->fetchAll();

        echo json_encode(['success' => true, 'agents' => $agents, 'groups' => $groups]);

    } elseif ($method === 'POST' && $action === 'generate') {
        $body      = json_decode(file_get_contents('php://input'), true);
        $projectId = (int)($body['project_id'] ?? 0);
        $numAgents = min((int)($body['num_agents'] ?? NUM_AGENTS), 50);

        $pstmt = $db->prepare('SELECT * FROM projects WHERE id = ?');
        $pstmt->execute([$projectId]);
        $project = $pstmt->fetch();
        if (!$project) { echo json_encode(['success' => false, 'error' => 'Projekt nicht gefunden']); exit; }

        // Alte Agenten und Gruppen löschen
        $db->prepare('DELETE FROM agents WHERE project_id = ?')->execute([$projectId]);
        $db->prepare('DELETE FROM `groups` WHERE project_id = ?')->execute([$projectId]);

        $llm = new LLMClient();

        $system = 'Du bist ein Sozialpsychologie-Experte. Antworte ausschließlich mit gültigem JSON.';
        $user   = <<<PROMPT
Erstelle $numAgents realistische Agenten für folgendes gesellschaftliches Ereignis:
"{$project['event_description']}"

Gib ein JSON-Array zurück. Jedes Objekt hat exakt diese Felder:
- name: string (Vorname Nachname)
- personality_type: string (eines von: Agitator, Mitläufer, Zweifler, Beobachter, Extremist, Vermittler)
- bio: string (max. 120 Zeichen, beschreibt die Person)
- initial_opinion: float (-1.0 bis 1.0, negativ=dagegen, positiv=dafür)
- emotional_state: float (0.1 bis 0.9)
- inhibition_level: float (0.1 bis 0.9, hoch=gehemmter)
- anonymity: boolean (ca. {$project['event_description']} % anonym)

Antworte nur mit dem JSON-Array, ohne Erklärungen.
PROMPT;

        $agentsData = $llm->chatJson($system, $user);

        // 3 Gruppen anlegen (Pro, Kontra, Neutral)
        $groupNames = ['Pro-Gruppe', 'Kontra-Gruppe', 'Neutrale Gruppe'];
        $groupIds   = [];
        foreach ($groupNames as $gName) {
            $db->prepare('INSERT INTO `groups` (project_id, name) VALUES (?,?)')->execute([$projectId, $gName]);
            $groupIds[] = (int)$db->lastInsertId();
        }

        $anonymousCount = 0;
        $insertStmt = $db->prepare(
            'INSERT INTO agents (project_id, name, personality_type, bio, opinion,
             emotional_state, inhibition_level, anonymity, group_id)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );

        foreach ($agentsData as $a) {
            $opinion   = max(-1.0, min(1.0, (float)($a['initial_opinion'] ?? 0)));
            $groupId   = $opinion > 0.2
                ? $groupIds[0]
                : ($opinion < -0.2 ? $groupIds[1] : $groupIds[2]);
            $anon      = !empty($a['anonymity']) ? 1 : 0;
            if ($anon) $anonymousCount++;

            $insertStmt->execute([
                $projectId,
                $a['name']             ?? 'Agent',
                $a['personality_type'] ?? 'Mitläufer',
                $a['bio']              ?? '',
                $opinion,
                max(0.0, min(1.0, (float)($a['emotional_state']  ?? 0.3))),
                max(0.0, min(1.0, (float)($a['inhibition_level'] ?? 0.7))),
                $anon,
                $groupId,
            ]);
        }

        // Gruppenstatistiken initialisieren
        foreach ($groupIds as $gid) {
            $stmt = $db->prepare('SELECT AVG(opinion), COUNT(*) FROM agents WHERE group_id = ?');
            $stmt->execute([$gid]);
            [$avg, $cnt] = $stmt->fetch(PDO::FETCH_NUM);
            $db->prepare('UPDATE `groups` SET average_opinion=?, norm_extremity=?, size=? WHERE id=?')
               ->execute([round($avg ?? 0, 4), round(abs($avg ?? 0), 4), $cnt, $gid]);
        }

        $db->prepare('UPDATE projects SET status=?, agent_count=?, anonymous_count=? WHERE id=?')
           ->execute(['agents_ready', count($agentsData), $anonymousCount, $projectId]);

        echo json_encode(['success' => true, 'count' => count($agentsData)]);

    } else {
        echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
