<?php
// public/save_history.php

session_start();
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\App\Conversation;

if (isset($_POST['q'], $_POST['a'])) {
    Conversation::addMessage($_POST['q'], $_POST['a']);
    Conversation::limit(5);
    echo json_encode(["status" => "ok"]);
}
