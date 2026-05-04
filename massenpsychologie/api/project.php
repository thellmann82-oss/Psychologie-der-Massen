<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::get();

    if ($method === 'GET' && $action === 'list') {
        $stmt = $db->query('SELECT * FROM projects ORDER BY created_at DESC');
        echo json_encode(['success' => true, 'projects' => $stmt->fetchAll()]);

    } elseif ($method === 'GET' && $action === 'get') {
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM projects WHERE id = ?');
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        if (!$project) { echo json_encode(['success' => false, 'error' => 'Nicht gefunden']); exit; }
        echo json_encode(['success' => true, 'project' => $project]);

    } elseif ($method === 'POST' && $action === 'create') {
        $body = json_decode(file_get_contents('php://input'), true);
        $name  = trim($body['name'] ?? '');
        $desc  = trim($body['description'] ?? '');
        $event = trim($body['event_description'] ?? '');
        if (!$name || !$event) { echo json_encode(['success' => false, 'error' => 'Name und Ereignis erforderlich']); exit; }

        $stmt = $db->prepare('INSERT INTO projects (name, description, event_description) VALUES (?,?,?)');
        $stmt->execute([$name, $desc, $event]);
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);

    } elseif ($method === 'DELETE' && $action === 'delete') {
        $id = (int)($_GET['id'] ?? 0);
        $db->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
