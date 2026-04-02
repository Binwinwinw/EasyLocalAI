<?php
// tests/test_agent_v5.php
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

$config = Container::get('config');
$config->set('api_base_url', 'http://localhost:11434/v1/chat/completions');

$agent = Container::get('agent');

echo "--- Test de l'Agent V5 (Advanced) ---\n";

// Étape 1: Créer une nouvelle connaissance
echo "\n1. Création d'une connaissance secrète...\n";
$q1 = "Crée un fichier nommé 'knowledge/secret_project.txt' avec le contenu : 'Le nom de code du projet secret est NEBULA-9'.";
$res1 = $agent->run($q1, [], function($s) { echo "  [PENSÉE] $s\n"; });
echo "RÉPONSE: $res1\n";

// Étape 2: Vérifier l'Auto-RAG
echo "\n2. Test de l'Auto-RAG (Recherche sémantique immédiate)...\n";
$q2 = "Quel est le nom de code du projet secret ? Cherche dans tes connaissances.";
$res2 = $agent->run($q2, [], function($s) { echo "  [PENSÉE] $s\n"; });
echo "RÉPONSE: $res2\n";

// Étape 3: Vérifier le CodeRunner
echo "\n3. Test du CodeRunner (Calcul déporté)...\n";
$q3 = "Écris et exécute un script PHP qui calcule la somme des nombres de 1 à 50 et affiche le résultat.";
$res3 = $agent->run($q3, [], function($s) { echo "  [PENSÉE] $s\n"; });
echo "RÉPONSE: $res3\n";

echo "\n--- Fin des tests Phase 5 ---\n";
