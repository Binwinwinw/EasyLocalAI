<?php
// public/stream.php - Agent & Tool Calling Refactor (Hybrid Edition)
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\App\Conversation;

$q = $_GET['q'] ?? "";
$key = $_GET['key'] ?? "";

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
    });
    
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
