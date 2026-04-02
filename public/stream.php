<?php
// public/stream.php - Agent & Tool Calling Refactor (Hybrid Edition)
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\App\Conversation;

use EasyLocalAI\Core\Security;

// 1. Récupération des données (Support POST JSON ou GET)
$q      = "";
$key    = "";
$images = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $expert = Security::sanitize($input['expert'] ?? "");
    $csrf   = $input['csrf_token'] ?? "";
} else {
    $q   = Security::sanitize($_GET['q'] ?? "");
    $key = Security::sanitize($_GET['key'] ?? "");
    $expert = Security::sanitize($_GET['expert'] ?? "");
    $csrf = $_GET['csrf_token'] ?? "";
}

// 2. Vérification de sécurité CRITIQUE (CSRF)
if (!Security::checkCsrf($csrf)) {
    header('Content-Type: text/event-stream');
    echo "data: " . json_encode(['message' => ['content' => "Erreur de sécurité : Jeton CSRF invalide ou manquant."]]) . "\n\n";
    echo "data: [DONE]\n\n";
    exit;
}

if (!$q) exit;

// IA Hybride : On stocke la clé reçue en session pour le service CloudLlm
$config = Container::get('config');
$activeProvider = $config->get('active_provider', 'ollama');
if ($activeProvider !== 'ollama' && !empty($key)) {
    $_SESSION["cloud_{$activeProvider}_api_key"] = $key;
}

$agent  = Container::get('agent');
$rag    = Container::get('rag');

$history = Conversation::getHistory();
$rag_context = $rag->getContext($q);
$prompt_with_rag = $rag_context ? $rag_context . "\n\n" . $q : $q;

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

try {
    $result = $agent->run($prompt_with_rag, $history, function($step) {
        echo "event: thought\n";
        echo "data: " . json_encode(['content' => $step]) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }, $images, $expert);
    
    // On envoie la réponse finale
    $response = [
        'message' => [
            'content' => $result
        ]
    ];
    
    echo "data: " . json_encode($response) . "\n\n";
    echo "data: [DONE]\n\n";
} catch (\Exception $e) {
    echo "data: " . json_encode(['message' => ['content' => "Erreur Agent: " . $e->getMessage()]]) . "\n\n";
    echo "data: [DONE]\n\n";
}
