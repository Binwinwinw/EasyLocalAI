<?php
// public/stream.php - DI Refactor
session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\App\Conversation;

$ollama = Container::get('ollama');
$rag    = Container::get('rag');

$q = $_GET['q'] ?? "";
if (!$q) exit;

$history = Conversation::getHistory();
$rag_context = $rag->getContext($q);
$prompt_with_rag = $rag_context ? $rag_context . "\n\n" . $q : $q;

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$ollama->stream($prompt_with_rag, $history);
