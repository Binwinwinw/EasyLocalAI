<?php
// public/memory.php - Memory API
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

use EasyLocalAI\Core\Security;

$memory = Container::get('memory');
$action = Security::sanitize($_POST['action'] ?? '');

if (!Security::checkCsrf($_POST['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'CSRF Token Invalid']);
    exit;
}

if ($action === 'add' && isset($_POST['fact'])) {
    $fact = Security::sanitize($_POST['fact']);
    $memory->addFact($fact);
    $memory->save();
    echo json_encode(['status' => 'success']);
} elseif ($action === 'delete' && isset($_POST['index'])) {
    $index = (int)$_POST['index'];
    $memory->removeFact($index);
    $memory->save();
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
