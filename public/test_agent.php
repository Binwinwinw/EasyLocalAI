<?php
// /tmp/test_agent.php
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

$agent = Container::get('agent');

// Test 1: L'heure
$q1 = "Quelle heure est-il précisément ?";
echo "Test 1: $q1\n";
$res1 = $agent->run($q1);
echo "Réponse: $res1\n\n";

// Test 2: La mémoire
$memory = Container::get('memory');
$memory->addFact("L'utilisateur s'appelle Alice et adore le café.");
$q2 = "Quel est mon nom et qu'est-ce que j'aime ?";
echo "Test 2: $q2\n";
$res2 = $agent->run($q2);
echo "Réponse: $res2\n\n";
