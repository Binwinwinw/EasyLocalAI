<?php
// public/save_history.php
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\App\Conversation;

$q = $_POST['q'] ?? '';
$a = $_POST['a'] ?? '';

if ($q && $a) {
    Conversation::addMessage($q, $a);
    Conversation::limit(10); // Garder les 10 derniers messages
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
}
