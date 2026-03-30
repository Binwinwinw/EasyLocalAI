<?php
require_once __DIR__ . '/../config/bootstrap.php';
use EasyLocalAI\Core\Container;

$agent = Container::get('agent');
$query = "Quelle heure est-il et peux-tu me donner un résumé du fichier 'test.txt' s'il existe ?";

echo "--- DÉBUT DU TEST AGENT EXPERT ---\n";
$result = $agent->run($query, [], function($step) {
    echo "[PENSÉE] $step\n";
});

echo "\n--- RÉPONSE FINALE ---\n";
echo $result . "\n";
