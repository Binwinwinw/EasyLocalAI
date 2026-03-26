<?php
// public/memory.php - DI Refactor
session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;

$memory = Container::get('memory');

$action = $_REQUEST['action'] ?? "";

if ($action === "add" && isset($_POST['fact'])) {
    $memory->addFact($_POST['fact']);
    echo json_encode(["status" => "ok"]);
}

if ($action === "clear") {
    $memory->clear();
    echo json_encode(["status" => "ok"]);
}
