<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Agent.php';
require_once __DIR__ . '/../classes/Group.php';
require_once __DIR__ . '/../classes/LLMClient.php';
require_once __DIR__ . '/../classes/ReportGenerator.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::get();

    if ($method === 'POST' && $action === 'generate') {
        $body      = json_decode(file_get_contents('php://input'), true);
        $projectId = (int)($body['project_id'] ?? 0);

        $gen     = new ReportGenerator();
        $content = $gen->generate($projectId);

        $stmt = $db->prepare('INSERT INTO reports (project_id, content) VALUES (?,?)');
        $stmt->execute([$projectId, $content]);
        $reportId = $db->lastInsertId();

        echo json_encode(['success' => true, 'report_id' => $reportId, 'content' => $content]);

    } elseif ($method === 'GET' && $action === 'get') {
        $projectId = (int)($_GET['project_id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM reports WHERE project_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$projectId]);
        $report = $stmt->fetch();
        echo json_encode(['success' => true, 'report' => $report]);

    } elseif ($method === 'POST' && $action === 'chat') {
        $body      = json_decode(file_get_contents('php://input'), true);
        $projectId = (int)($body['project_id'] ?? 0);
        $question  = trim($body['question'] ?? '');

        // Kontext laden
        $sim = $db->prepare('SELECT * FROM simulations WHERE project_id = ? ORDER BY id DESC LIMIT 1');
        $sim->execute([$projectId]);
        $sim = $sim->fetch();

        $groups = $db->prepare('SELECT * FROM `groups` WHERE project_id = ?');
        $groups->execute([$projectId]);
        $groups = $groups->fetchAll();

        $groupContext = '';
        foreach ($groups as $g) {
            $groupContext .= "- {$g['name']}: {$g['size']} Mitglieder, Meinung {$g['average_opinion']}, " .
                             ($g['escalated'] ? 'ESKALIERT' : 'stabil') . "\n";
        }

        $llm    = new LLMClient();
        $answer = $llm->chat(
            "Du bist ein Experte für Massenpsychologie. Beantworte Fragen zur laufenden Simulation auf Deutsch.",
            "Gruppenzustand:\n$groupContext\n\nFrage: $question"
        );

        echo json_encode(['success' => true, 'answer' => $answer]);

    } else {
        echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
