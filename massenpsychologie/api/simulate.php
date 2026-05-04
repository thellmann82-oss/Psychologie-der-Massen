<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Agent.php';
require_once __DIR__ . '/../classes/Group.php';
require_once __DIR__ . '/../classes/MassDynamics.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::get();

    if ($method === 'POST' && $action === 'start') {
        $body      = json_decode(file_get_contents('php://input'), true);
        $projectId = (int)($body['project_id'] ?? 0);
        $maxRounds = min((int)($body['rounds'] ?? MAX_ROUNDS), 20);

        $pstmt = $db->prepare('SELECT * FROM projects WHERE id = ?');
        $pstmt->execute([$projectId]);
        $project = $pstmt->fetch();
        if (!$project) { echo json_encode(['success' => false, 'error' => 'Projekt nicht gefunden']); exit; }

        // Simulation anlegen
        $db->prepare('INSERT INTO simulations (project_id, round_number, status) VALUES (?,0,"running")')
           ->execute([$projectId]);
        $simId = (int)$db->lastInsertId();

        $engine = new MassDynamics();
        $logInsert = $db->prepare(
            'INSERT INTO agent_logs (simulation_id, round, agent_id, action, old_opinion, new_opinion, group_id, data)
             VALUES (?,?,?,?,?,?,?,?)'
        );

        for ($round = 1; $round <= $maxRounds; $round++) {
            $agents = Agent::allForProject($projectId);
            $groups = Group::forProject($projectId);

            $result = $engine->runRound($agents, $groups, $simId, $round);

            // Agenten und Gruppen in DB speichern
            foreach ($result['agents'] as $agent) { $agent->save(); }
            foreach ($result['groups'] as $group)  { $group->save(); }

            // Logs schreiben
            foreach ($result['logs'] as $log) {
                $logInsert->execute([
                    $log['simulation_id'],
                    $log['round'],
                    $log['agent_id'],
                    $log['action'],
                    $log['old_opinion'],
                    $log['new_opinion'],
                    $log['group_id'],
                    $log['data'],
                ]);
            }

            $db->prepare('UPDATE simulations SET round_number=? WHERE id=?')->execute([$round, $simId]);
        }

        // Simulation abschliessen
        $db->prepare('UPDATE simulations SET status="done" WHERE id=?')->execute([$simId]);
        $db->prepare('UPDATE projects SET status="done" WHERE id=?')->execute([$projectId]);

        echo json_encode(['success' => true, 'simulation_id' => $simId, 'rounds' => $maxRounds]);

    } elseif ($method === 'GET' && $action === 'status') {
        $projectId = (int)($_GET['project_id'] ?? 0);

        $agents = $db->prepare('SELECT * FROM agents WHERE project_id = ?');
        $agents->execute([$projectId]);
        $agents = $agents->fetchAll();

        $groups = $db->prepare('SELECT * FROM `groups` WHERE project_id = ?');
        $groups->execute([$projectId]);
        $groups = $groups->fetchAll();

        $sim = $db->prepare('SELECT * FROM simulations WHERE project_id = ? ORDER BY id DESC LIMIT 1');
        $sim->execute([$projectId]);
        $sim = $sim->fetch();

        $logs = [];
        if ($sim) {
            $lStmt = $db->prepare(
                'SELECT al.*, a.name as agent_name
                 FROM agent_logs al
                 LEFT JOIN agents a ON al.agent_id = a.id
                 WHERE al.simulation_id = ?
                 ORDER BY al.round, al.id'
            );
            $lStmt->execute([$sim['id']]);
            $logs = $lStmt->fetchAll();
        }

        echo json_encode([
            'success'    => true,
            'agents'     => $agents,
            'groups'     => $groups,
            'simulation' => $sim,
            'logs'       => $logs,
        ]);

    } else {
        echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
