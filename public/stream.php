<?php
// public/stream.php - Agent & Tool Calling Refactor
session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\App\Conversation;

$agent  = Container::get('agent');
$rag    = Container::get('rag');

$q = $_GET['q'] ?? "";
if (!$q) exit;

$history = Conversation::getHistory();
$rag_context = $rag->getContext($q);
$prompt_with_rag = $rag_context ? $rag_context . "\n\n" . $q : $q;

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

/**
 * Note: L'Agent V2 utilise actuellement un appel synchrone pour gérer 
 * le cycle de réflexion et les outils. Le "streaming" simulera 
 * l'envoi du bloc final.
 */

try {
    $result = $agent->run($prompt_with_rag, $history);
    
    // On simule une réponse JSON compatible avec ce que le frontend attend de Ollama
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
