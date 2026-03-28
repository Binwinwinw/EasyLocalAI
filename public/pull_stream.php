<?php
// public/pull_stream.php
require_once __DIR__ . '/../config/bootstrap.php';

use EasyLocalAI\Core\Container;
use EasyLocalAI\Core\Security;

$modelName = Security::sanitize($_GET['model'] ?? '');
if (!$modelName) {
    die("Aucun nom de modèle spécifié.");
}

$ollama = Container::get('ollama');

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// On désactive le buffering PHP/Serveur pour l'envoi en temps réel
if (ob_get_level() > 0) ob_end_clean();
set_time_limit(3600);

$ollama->pullStream($modelName, function($json) {
    $status = $json['status'] ?? 'Inconnu';
    $progress = 0;
    
    if (isset($json['total']) && $json['total'] > 0) {
        $progress = round(($json['completed'] / $json['total']) * 100, 2);
    }
    
    $eventData = [
        'status' => $status,
        'progress' => $progress
    ];
    
    echo "data: " . json_encode($eventData) . "\n\n";
    flush();
});

echo "data: [DONE]\n\n";
flush();
