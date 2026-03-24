<?php
// public/memory.php

session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\App\Memory;

$memory = new Memory();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add' && isset($_POST['fact'])) {
    $memory->addFact($_POST['fact']);
    echo json_encode(['status' => 'success', 'facts' => $memory->getFacts()]);
    exit;
}

if ($action === 'remove' && isset($_POST['index'])) {
    $memory->removeFact((int)$_POST['index']);
    echo json_encode(['status' => 'success', 'facts' => $memory->getFacts()]);
    exit;
}

if ($action === 'clear') {
    $memory->clear();
    echo json_encode(['status' => 'success', 'facts' => []]);
    exit;
}

// Default: return facts
echo json_encode($memory->getFacts());
