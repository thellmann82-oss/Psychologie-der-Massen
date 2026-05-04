<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'massenpsychologie');
define('DB_USER', 'root');
define('DB_PASS', '');

// LLM-Konfiguration (OpenAI-kompatibel)
define('LLM_API_KEY',  getenv('LLM_API_KEY')  ?: '');
define('LLM_BASE_URL', getenv('LLM_BASE_URL') ?: 'https://api.openai.com/v1');
define('LLM_MODEL',    getenv('LLM_MODEL')    ?: 'gpt-4o-mini');

// Simulations-Parameter
define('NUM_AGENTS',      20);
define('MAX_ROUNDS',      10);
define('ANONYMITY_RATE',  0.4);   // 40% der Agenten anonym
define('CONFORM_WEIGHT',  0.3);   // Meinungsanpassungsstärke
define('EMOTION_AMPLIFY', 1.1);   // Emotionsverstärkungsfaktor
define('INHIBITION_STEP', 0.15);  // Hemmungsreduktion bei Anonymität
define('EXTREME_THRESHOLD', 0.7); // Ab wann gilt eine Norm als extrem

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8') !== false ?: null;
