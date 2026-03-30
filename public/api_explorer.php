<?php
// public/api_explorer.php - API endpoint for the folder picker
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\Core\Security;

// Seuls les utilisateurs authentifiés peuvent parcourir le serveur
if (!Container::get('auth')->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$explorer = Container::get('explorer');
$path = $_GET['path'] ?? '';

header('Content-Type: application/json');
echo json_encode($explorer->listDirectories($path));
exit;
