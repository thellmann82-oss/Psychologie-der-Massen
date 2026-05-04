<?php
// Einmaliges Datenbank-Setup – aufrufen via http://localhost/massenpsychologie/install.php

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS massenpsychologie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE massenpsychologie");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS projects (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            name            VARCHAR(255) NOT NULL,
            description     TEXT,
            event_description TEXT NOT NULL,
            status          ENUM('created','agents_ready','simulating','done') DEFAULT 'created',
            agent_count     INT DEFAULT 0,
            anonymous_count INT DEFAULT 0,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agents (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            project_id       INT NOT NULL,
            name             VARCHAR(100) NOT NULL,
            personality_type VARCHAR(50),
            bio              TEXT,
            opinion          FLOAT DEFAULT 0,
            emotional_state  FLOAT DEFAULT 0.3,
            inhibition_level FLOAT DEFAULT 0.7,
            anonymity        TINYINT(1) DEFAULT 0,
            group_id         INT DEFAULT NULL,
            created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `groups` (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            project_id      INT NOT NULL,
            name            VARCHAR(100) NOT NULL,
            average_opinion FLOAT DEFAULT 0,
            norm_extremity  FLOAT DEFAULT 0,
            size            INT DEFAULT 0,
            escalated       TINYINT(1) DEFAULT 0,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS simulations (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            project_id   INT NOT NULL,
            round_number INT DEFAULT 0,
            status       ENUM('running','done','stopped') DEFAULT 'running',
            outcome      TEXT,
            created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agent_logs (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            simulation_id INT NOT NULL,
            round         INT NOT NULL,
            agent_id      INT DEFAULT NULL,
            action        VARCHAR(50),
            old_opinion   FLOAT DEFAULT NULL,
            new_opinion   FLOAT DEFAULT NULL,
            group_id      INT DEFAULT NULL,
            data          JSON,
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reports (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            content    LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    echo '<h2 style="font-family:sans-serif;color:green">✓ Datenbank erfolgreich eingerichtet!</h2>';
    echo '<p style="font-family:sans-serif"><a href="index.php">→ Zur Anwendung</a></p>';

} catch (PDOException $e) {
    echo '<h2 style="font-family:sans-serif;color:red">Fehler: ' . htmlspecialchars($e->getMessage()) . '</h2>';
    echo '<p style="font-family:sans-serif">Stellen Sie sicher, dass XAMPP läuft und MySQL erreichbar ist.</p>';
}
