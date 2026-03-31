<?php
// public/memory.php - Memory API
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

$memory = Container::get('memory');
$action = $_POST['action'] ?? '';

if ($action === 'add' && isset($_POST['fact'])) {
    $fact = $_POST['fact'];
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
