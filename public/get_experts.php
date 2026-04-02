<?php
// public/get_experts.php
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');

$division = $_GET['division'] ?? 'engineering';
$division = preg_replace('/[^a-z0-9_\-]/', '', strtolower($division));

$filePath = __DIR__ . "/../data/experts/{$division}.json";

if (file_exists($filePath)) {
    echo file_get_contents($filePath);
} else {
    echo json_encode(['error' => 'Division non trouvée', 'experts' => []]);
}
