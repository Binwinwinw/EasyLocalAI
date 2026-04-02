<?php
// tests/test_agent_v3.php

require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

$config = Container::get('config');
// Override pour le test local (Windows Host) car Docker map 11434:11434
$config->set('api_base_url', 'http://localhost:11434/v1/chat/completions');

$agent = Container::get('agent');

echo "--- Test de l'Agent V3 ---\n";

$query = "Dis-moi quelle heure il est et donne-moi les 5 premières lignes du fichier README.md";

echo "Question: $query\n\n";

$result = $agent->run($query, [], function($step) {
    echo "[THOUGHT] $step\n";
});

echo "\n--- Réponse Finale ---\n";
echo $result . "\n";
echo "-----------------------\n";
