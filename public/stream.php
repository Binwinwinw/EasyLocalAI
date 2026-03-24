<?php
// public/stream.php

session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Config;
use EasyLocalAI\Core\Ollama;
use EasyLocalAI\App\Conversation;
use EasyLocalAI\RAG\RAG;

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$q = $_GET['q'] ?? '';
if (!$q || mb_strlen($q) < 5) {
    echo "data: [DONE]\n\n";
    exit;
}

$config = new Config();
$rag = new RAG();
$ollama = new Ollama($config);

$history = Conversation::getHistory();
$rag_context = $rag->getContext($q);
$prompt_with_rag = $rag_context ? $rag_context . "\n\n" . $q : $q;

// Exécuter le streaming réel
$ollama->stream($prompt_with_rag, $history);

echo "data: [DONE]\n\n";
flush();
